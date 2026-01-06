<?php
class StockMovement
{
    public $id;
    public $company_id;
    public $item_id;
    public $movement_type; // 'grn','usage','adjustment','return','damage'
    public $reference_type; // 'service','grn','adjustment'
    public $reference_id;
    public $quantity_change; // Positive for IN, Negative for OUT
    public $balance_after;
    public $unit_cost;
    public $notes;
    public $created_by_employee_id;
    public $created_at;
    
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load movement by ID
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM stock_movements WHERE id = ?";
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
        $this->item_id = $row['item_id'];
        $this->movement_type = $row['movement_type'];
        $this->reference_type = $row['reference_type'];
        $this->reference_id = $row['reference_id'];
        $this->quantity_change = $row['quantity_change'];
        $this->balance_after = $row['balance_after'];
        $this->unit_cost = $row['unit_cost'];
        $this->notes = $row['notes'];
        $this->created_by_employee_id = $row['created_by_employee_id'];
        $this->created_at = $row['created_at'];
    }

    /**
     * Create a new stock movement
     * This also updates the inventory_items current_stock
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->item_id) || empty($this->movement_type) || $this->quantity_change == 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // 1. Get current stock and lock the item row for update
            $itemQuery = "SELECT current_stock, unit_cost FROM inventory_items WHERE id = ? AND company_id = ? FOR UPDATE";
            $stmt = $this->db->prepareSelect($itemQuery, [$this->item_id, $this->company_id]);
            $item = $stmt->fetch();

            if (!$item) {
                $this->db->rollBack();
                return false; // Item not found or wrong company
            }

            $currentStock = $item['current_stock'];
            $newStock = $currentStock + $this->quantity_change;

            // Prevent negative stock if needed (optional logic, usually good for strict inventory)
            // if ($newStock < 0) { ... }

            // 2. Insert movement
            $this->balance_after = $newStock;
            // Use item's unit cost if not provided
            if ($this->unit_cost === null) {
                $this->unit_cost = $item['unit_cost'];
            }

            $insertQuery = "INSERT INTO stock_movements (
                company_id, item_id, movement_type, reference_type, reference_id, 
                quantity_change, balance_after, unit_cost, notes, created_by_employee_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $success = $this->db->prepareExecute($insertQuery, [
                $this->company_id,
                $this->item_id,
                $this->movement_type,
                $this->reference_type ?? null,
                $this->reference_id ?? null,
                $this->quantity_change,
                $this->balance_after,
                $this->unit_cost,
                $this->notes ?? null,
                $this->created_by_employee_id ?? null
            ]);

            if (!$success) {
                $this->db->rollBack();
                return false;
            }

            $this->id = $this->db->getLastInsertId();

            // 3. Update inventory item stock
            $updateQuery = "UPDATE inventory_items SET current_stock = ? WHERE id = ?";
            $updateSuccess = $this->db->prepareExecute($updateQuery, [$newStock, $this->item_id]);

            if (!$updateSuccess) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("StockMovement Transaction Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get movements for a specific item
     */
    public function getByItem($itemId, $companyId)
    {
        $query = "SELECT sm.*, 
                  CONCAT(e.first_name, ' ', e.last_name) as employee_name
                  FROM stock_movements sm
                  LEFT JOIN employees e ON sm.created_by_employee_id = e.id
                  WHERE sm.item_id = ? AND sm.company_id = ?
                  ORDER BY sm.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, [$itemId, $companyId]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Get all movements for a company (filtered by date range)
     */
    public function getByDateRange($companyId, $startDate, $endDate)
    {
        $query = "SELECT sm.*, i.item_name, i.item_code,
                  CONCAT(e.first_name, ' ', e.last_name) as employee_name
                  FROM stock_movements sm
                  JOIN inventory_items i ON sm.item_id = i.id
                  LEFT JOIN employees e ON sm.created_by_employee_id = e.id
                  WHERE sm.company_id = ? 
                  AND DATE(sm.created_at) BETWEEN ? AND ?
                  ORDER BY sm.created_at DESC";

        $stmt = $this->db->prepareSelect($query, [$companyId, $startDate, $endDate]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Count movements for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM stock_movements WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>
