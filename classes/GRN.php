<?php
require_once __DIR__ . '/SupplierLedger.php';

class GRN
{
    public $id;
    public $company_id;
    public $branch_id;
    public $grn_number;
    public $supplier_id;
    public $grn_date;
    public $due_date;
    public $invoice_number;
    public $total_amount;
    public $tax_amount;
    public $discount_amount;
    public $net_amount;
    public $status;
    public $received_by_employee_id;
    public $verified_by_employee_id;
    public $notes;
    public $created_at;
    public $updated_at;
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load GRN by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM grn WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
            }
        }
    }

    private function populateFromArray($row)
    {
        $this->id = $row['id'];
        $this->company_id = $row['company_id'];
        $this->branch_id = $row['branch_id'];
        $this->grn_number = $row['grn_number'];
        $this->supplier_id = $row['supplier_id'];
        $this->grn_date = $row['grn_date'];
        $this->due_date = $row['due_date'] ?? null;
        $this->invoice_number = $row['invoice_number'];
        $this->total_amount = $row['total_amount'];
        $this->tax_amount = $row['tax_amount'];
        $this->discount_amount = $row['discount_amount'];
        $this->net_amount = $row['net_amount'];
        $this->status = $row['status'];
        $this->received_by_employee_id = $row['received_by_employee_id'];
        $this->verified_by_employee_id = $row['verified_by_employee_id'];
        $this->notes = $row['notes'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    /**
     * Generate unique GRN number for the company.
     */
    public function generateGRNNumber($companyId)
    {
        $prefix = 'GRN';
        $date = date('Ymd');
        $query = "SELECT COUNT(*) as count FROM grn WHERE company_id = ? AND DATE(created_at) = CURDATE()";
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        $row = $stmt->fetch();
        $count = $row['count'] + 1;
        return $prefix . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new GRN.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->supplier_id)) {
            return false;
        }

        if (empty($this->grn_number)) {
            $this->grn_number = $this->generateGRNNumber($this->company_id);
        }

        $query = "INSERT INTO grn (company_id, branch_id, grn_number, supplier_id, grn_date, due_date, invoice_number, 
                  total_amount, tax_amount, discount_amount, net_amount, status, 
                  received_by_employee_id, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->branch_id ?? null,
            $this->grn_number,
            $this->supplier_id,
            $this->grn_date,
            $this->due_date ?? null,
            $this->invoice_number ?? null,
            $this->total_amount ?? 0,
            $this->tax_amount ?? 0,
            $this->discount_amount ?? 0,
            $this->net_amount ?? 0,
            $this->status ?? 'draft',
            $this->received_by_employee_id ?? null,
            $this->notes ?? null
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update GRN.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        $query = "UPDATE grn SET supplier_id = ?, branch_id = ?, grn_date = ?, due_date = ?, invoice_number = ?, 
                  total_amount = ?, tax_amount = ?, discount_amount = ?, net_amount = ?, 
                  status = ?, notes = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->supplier_id,
            $this->branch_id ?? null,
            $this->grn_date,
            $this->due_date ?? null,
            $this->invoice_number ?? null,
            $this->total_amount,
            $this->tax_amount ?? 0,
            $this->discount_amount ?? 0,
            $this->net_amount,
            $this->status,
            $this->notes ?? null,
            $this->id
        ]);
    }

    /**
     * Delete GRN.
     */
    /**
     * Delete GRN.
     * @deprecated Deletion is no longer allowed. Use cancel() instead.
     */
    public function delete()
    {
        // Deletion disabled as per requirements
        return false;
    }

    /**
     * Get all GRNs for a company.
     */
    public function all($companyId, $branchId = null)
    {
        $query = "SELECT g.*, s.supplier_name, CONCAT(e.first_name, ' ', e.last_name) as received_by_name, b.branch_name
                  FROM grn g
                  LEFT JOIN suppliers s ON g.supplier_id = s.id
                  LEFT JOIN employees e ON g.received_by_employee_id = e.id
                  LEFT JOIN branches b ON g.branch_id = b.id
                  WHERE g.company_id = ?";
        
        $params = [$companyId];
        
        if ($branchId) {
            $query .= " AND g.branch_id = ?";
            $params[] = $branchId;
        }
        
        $query .= " ORDER BY g.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get GRN items.
     */
    public function getItems($grnId)
    {
        $query = "SELECT gi.*, i.item_name, i.item_code, i.unit_of_measure
                  FROM grn_items gi
                  LEFT JOIN inventory_items i ON gi.item_id = i.id
                  WHERE gi.grn_id = ?";
        
        $params = [$grnId];
        
        // strict company check if object is loaded
        if (!empty($this->company_id)) {
            $query .= " AND gi.company_id = ?";
            $params[] = $this->company_id;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    public function addItem($itemData)
    {
        if (!$this->id || empty($this->company_id)) {
            return false;
        }

        $query = "INSERT INTO grn_items (company_id, grn_id, item_id, quantity, unit_price, total_price, 
                  batch_number, expiry_date, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->db->prepareExecute($query, [
            $this->company_id,
            $this->id,
            $itemData['item_id'],
            $itemData['quantity'],
            $itemData['unit_price'],
            $itemData['total_price'],
            $itemData['batch_number'] ?? null,
            $itemData['expiry_date'] ?? null,
            $itemData['notes'] ?? null
        ]);
    }

    /**
     * Replace all items in GRN (for update/draft editing).
     */
    public function replaceItems($items)
    {
        if (!$this->id || empty($this->company_id)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Delete existing items
            $deleteQuery = "DELETE FROM grn_items WHERE grn_id = ? AND company_id = ?";
            $this->db->prepareExecute($deleteQuery, [$this->id, $this->company_id]);

            // Add new items
            foreach ($items as $item) {
                // Ensure item belongs to company (basic check)
                $item['item_id'] = (int)$item['item_id'];
                $this->addItem($item);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("GRN Replace Items Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark GRN as received.
     */
    /**
     * Mark GRN as received.
     */
    public function receive($employeeId)
    {
        if (!$this->id || $this->status !== 'draft') {
            return false;
        }

        $query = "UPDATE grn SET status = 'received', received_by_employee_id = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$employeeId, $this->id]);
    }

    /**
     * Cancel GRN.
     */
    public function cancel($employeeId)
    {
        if (!$this->id) {
            return false;
        }

        // Only allow cancelling if 'draft' or 'received' (but verified has stock implications)
        // Basic requirement usually implies cancelling drafts.
        if (!in_array($this->status, ['draft', 'received'])) {
            return false;
        }

        $query = "UPDATE grn SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]); // Could also track cancelled_by if column existed
    }

    /**
     * Verify GRN and update stock.
     */
    /**
     * Verify GRN and update stock.
     */
    public function verify($employeeId)
    {
        if (!$this->id || $this->status === 'verified') {
            return false; // Already verified or invalid
        }

        try {
            $this->db->beginTransaction();

            // Update GRN status
            $query = "UPDATE grn SET status = 'verified', verified_by_employee_id = ? WHERE id = ?";
            $this->db->prepareExecute($query, [$employeeId, $this->id]);

            // Update stock for each item using StockMovement for full traceability
            $items = $this->getItems($this->id);
            $inventoryBatch = new InventoryBatch(); 

            foreach ($items as $item) {
                // Determine branch_id for stock update
                // If GRN has branch_id, stock goes there. If NULL (company-wide), stock goes to NULL branch (global stock).
                
                // Get current stock for this item (and correct branch)
                // Note: InventoryItem might need to be branch-aware. 
                // Assuming items are per-branch or global based on their own definition + GRN destination
                // The GRN branch_id dictates where the stock is added.
                
                // Verify item ownership and existence first
                $checkItem = "SELECT id FROM inventory_items WHERE id = ? AND company_id = ?";
                $stmtCheck = $this->db->prepareSelect($checkItem, [$item['item_id'], $this->company_id]);
                if (!$stmtCheck || !$stmtCheck->fetch()) {
                    throw new Exception("Inventory Integrity Error: Item ID {$item['item_id']} not found for Company ID {$this->company_id}");
                }

                // Update stock AND update unit_cost to the latest price (Last Purchase Price)
                $updateStock = "UPDATE inventory_items SET current_stock = current_stock + ?, unit_cost = ? WHERE id = ? AND company_id = ?";
                $success = $this->db->prepareExecute($updateStock, [$item['quantity'], $item['unit_price'], $item['item_id'], $this->company_id]);
                
                if (!$success) {
                    throw new Exception("Failed to update stock for Item ID {$item['item_id']}");
                }

                // Create Inventory Batch for FIFO
                // We trust the GRN item unit price as the cost for this batch
                $inventoryBatch->addBatch(
                    $this->company_id,
                    $item['item_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $this->branch_id,
                    $item['batch_number'] ?? null,
                    $item['id'], // grn_item_id as reference
                    $item['expiry_date'] ?? null
                );

                // Record stock movement
                // We need to fetch the NEW balance for the movement record
                $balanceQuery = "SELECT current_stock, unit_cost FROM inventory_items WHERE id = ? AND company_id = ?";
                $stmt = $this->db->prepareSelect($balanceQuery, [$item['item_id'], $this->company_id]);
                $row = $stmt->fetch();
                $newBalance = $row['current_stock'] ?? 0;
                $unitCost = $row['unit_cost'] ?? $item['unit_price']; // Use item cost if available, else new price? usually keep avg cost or similar.

                $movement = "INSERT INTO stock_movements (company_id, item_id, movement_type, reference_type, reference_id, 
                            quantity_change, balance_after, unit_cost, notes, created_by_employee_id) 
                            VALUES (?, ?, 'grn', 'grn', ?, ?, ?, ?, ?, ?)";
                $this->db->prepareExecute($movement, [
                    $this->company_id,
                    $item['item_id'],
                    $this->id,
                    $item['quantity'],
                    $newBalance,
                    $item['unit_price'], // Cost of this specific batch
                    "Received via GRN #" . $this->grn_number,
                    $employeeId
                ]);
            }

            // Record Supplier Ledger entry (Debit - we owe them)
            if ($this->supplier_id && $this->net_amount > 0) {
                $supplierLedger = new SupplierLedger();
                $supplierLedger->recordGRN(
                    $this->company_id,
                    $this->supplier_id,
                    $this->id,
                    $this->grn_number,
                    $this->net_amount,
                    $this->grn_date,
                    $employeeId
                );
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("GRN Verification Transaction Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get GRN statistics for a company.
     */
    public function getStatistics($companyId, $branchId = null)
    {
        $query = "SELECT 
            COUNT(*) as total_grns,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
            SUM(CASE WHEN status IN ('received', 'verified') THEN 1 ELSE 0 END) as received_count,
            SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_count,
            SUM(CASE WHEN status != 'cancelled' THEN net_amount ELSE 0 END) as total_value
            FROM grn WHERE company_id = ?";
        
        $params = [$companyId];
        
        if ($branchId) {
            $query .= " AND branch_id = ?";
            $params[] = $branchId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Count GRNs for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM grn WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>
