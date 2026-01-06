<?php
/**
 * Inventory Batch Management for FIFO
 */
class InventoryBatch
{
    private $db;
    public $id;
    public $company_id;
    public $branch_id;
    public $item_id;
    public $batch_number;
    public $grn_item_id;
    public $quantity_initial;
    public $quantity_remaining;
    public $unit_cost;
    public $expiry_date;
    public $received_date;
    public $is_active;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    private function loadById($id)
    {
        $query = "SELECT * FROM inventory_batches WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populate($row);
            }
        }
    }

    private function populate($row)
    {
        foreach ($row as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Add new batch (e.g., from GRN)
     */
    public function addBatch($companyId, $itemId, $quantity, $unitCost, $branchId = null, $batchNo = null, $grnItemId = null, $expiryDate = null)
    {
        $query = "INSERT INTO inventory_batches (company_id, branch_id, item_id, batch_number, grn_item_id, 
                  quantity_initial, quantity_remaining, unit_cost, expiry_date, received_date, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 1)";
        
        $success = $this->db->prepareExecute($query, [
            $companyId,
            $branchId,
            $itemId,
            $batchNo,
            $grnItemId,
            $quantity,
            $quantity, // Initial remaining = initial quantity
            $unitCost,
            $expiryDate
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
            return $this->id;
        }
        return false;
    }

    /**
     * Deduct stock using FIFO logic
     * Returns array of batches used with cost details for COGS calculation
     */
    public function deductStockFIFO($companyId, $itemId, $quantityNeeded, $branchId = null)
    {
        $batchesUsed = [];
        $remainingNeeded = $quantityNeeded;

        // Get active batches with stock, ordered by received_date ASC (FIFO)
        $query = "SELECT * FROM inventory_batches 
                  WHERE company_id = ? AND item_id = ? AND quantity_remaining > 0 AND is_active = 1";
        $params = [$companyId, $itemId];

        if ($branchId) {
            $query .= " AND (branch_id = ? OR branch_id IS NULL)";
            $params[] = $branchId;
        }

        $query .= " ORDER BY received_date ASC, id ASC"; // FIFO

        $stmt = $this->db->prepareSelect($query, $params);
        $batches = $stmt ? $stmt->fetchAll() : [];

        foreach ($batches as $batch) {
            if ($remainingNeeded <= 0) break;

            $qtyToTake = min($remainingNeeded, $batch['quantity_remaining']);
            
            // Update batch
            $newRemaining = $batch['quantity_remaining'] - $qtyToTake;
            $isActive = ($newRemaining > 0) ? 1 : 0; // Close batch if empty? Maybe keep active but 0 for history? Usually 0 is fine.
            // Actually better to keep is_active=1 but qty=0 so we know it existed. 
            // But query above filters qty > 0. So effectively it's "closed" for deduction.

            $update = "UPDATE inventory_batches SET quantity_remaining = ? WHERE id = ?";
            $this->db->prepareExecute($update, [$newRemaining, $batch['id']]);

            $batchesUsed[] = [
                'batch_id' => $batch['id'],
                'quantity' => $qtyToTake,
                'unit_cost' => $batch['unit_cost'],
                'total_cost' => $qtyToTake * $batch['unit_cost']
            ];

            $remainingNeeded -= $qtyToTake;
        }

        if ($remainingNeeded > 0) {
            // Not enough stock in batches!
            // Depending on policy: Allow negative stock (untracked cost) or Error.
            // For P&L, negative stock is tricky. 
            // We return what we found. The caller must handle the shortfall (e.g. record as "pending cost" or block transaction).
            // Usually, we return the shortfall amount in the response.
        }

        return [
            'batches_used' => $batchesUsed,
            'quantity_deducted' => $quantityNeeded - $remainingNeeded,
            'shortfall' => $remainingNeeded
        ];
    }

    /**
     * Get active batches for an item
     */
    public function getActiveBatches($companyId, $itemId)
    {
        $query = "SELECT * FROM inventory_batches 
                  WHERE company_id = ? AND item_id = ? AND quantity_remaining > 0 
                  ORDER BY received_date ASC";
        return $this->db->prepareSelect($query, [$companyId, $itemId])->fetchAll();
    }

    /**
     * Restore stock to existing batches (for cancellations/returns)
     * Restores quantity back to batches that were likely depleted, starting with oldest (FIFO reverse)
     * 
     * @param int $companyId
     * @param int $itemId
     * @param float $quantity - total quantity to restore
     * @param float $unitCost - the cost at which the item was originally purchased
     * @param int|null $branchId
     * @return bool
     */
    public function restoreStock($companyId, $itemId, $quantity, $unitCost, $branchId = null)
    {
        $remainingToRestore = $quantity;

        // First priority: Find depleted batches (quantity_remaining = 0 or less than initial)
        // These are the batches that were likely used during the original sale
        // Order by received_date ASC to match FIFO pattern (oldest first)
        $query = "SELECT * FROM inventory_batches 
                  WHERE company_id = ? AND item_id = ? 
                  AND quantity_remaining < quantity_initial
                  ORDER BY received_date ASC, id ASC";
        $params = [$companyId, $itemId];

        $stmt = $this->db->prepareSelect($query, $params);
        $depletedBatches = $stmt ? $stmt->fetchAll() : [];

        // Restore to depleted batches first
        foreach ($depletedBatches as $batch) {
            if ($remainingToRestore <= 0) break;

            // Calculate how much was taken from this batch
            $depleted = $batch['quantity_initial'] - $batch['quantity_remaining'];
            
            // Restore up to what was depleted, or what we need to restore
            $toRestore = min($remainingToRestore, $depleted);
            
            if ($toRestore > 0) {
                $newRemaining = $batch['quantity_remaining'] + $toRestore;
                $update = "UPDATE inventory_batches SET quantity_remaining = ? WHERE id = ?";
                $this->db->prepareExecute($update, [$newRemaining, $batch['id']]);
                $remainingToRestore -= $toRestore;
            }
        }

        // If we still have quantity to restore (shouldn't happen normally, but just in case)
        // Add to the most recent batch or create a new one
        if ($remainingToRestore > 0) {
            // Try to find any existing batch for this item
            $query2 = "SELECT * FROM inventory_batches 
                      WHERE company_id = ? AND item_id = ?
                      ORDER BY received_date DESC, id DESC LIMIT 1";
            $stmt2 = $this->db->prepareSelect($query2, [$companyId, $itemId]);
            $anyBatch = $stmt2 ? $stmt2->fetch() : null;

            if ($anyBatch) {
                // Add remaining to the most recent batch
                $newRemaining = $anyBatch['quantity_remaining'] + $remainingToRestore;
                $newInitial = $anyBatch['quantity_initial'] + $remainingToRestore; // Also update initial
                $update = "UPDATE inventory_batches 
                           SET quantity_remaining = ?, quantity_initial = ? 
                           WHERE id = ?";
                $this->db->prepareExecute($update, [$newRemaining, $newInitial, $anyBatch['id']]);
            } else {
                // Fallback: No batches exist - create one (rare edge case)
                $this->addBatch($companyId, $itemId, $remainingToRestore, $unitCost, $branchId, 'RESTORED', null, null);
            }
        }

        return true;
    }
}
?>
