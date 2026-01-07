<?php
class InventoryItem
{
    public $id;
    public $company_id;
    public $branch_id;
    public $item_code;
    public $item_name;
    public $description;
    public $category_id;
    public $unit_of_measure;
    public $current_stock;
    public $reorder_level;
    public $max_stock_level;
    public $unit_cost;
    public $unit_price;
    public $is_active;
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
     * Load item by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM inventory_items WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
            }
        }
    }

    /**
     * Populate object properties from array
     */
    private function populateFromArray($row)
    {
        $this->id = $row['id'];
        $this->company_id = $row['company_id'];
        $this->branch_id = $row['branch_id'];
        $this->item_code = $row['item_code'];
        $this->item_name = $row['item_name'];
        $this->description = $row['description'];
        $this->category_id = $row['category_id'];
        $this->unit_of_measure = $row['unit_of_measure'];
        $this->current_stock = $row['current_stock'];
        $this->reorder_level = $row['reorder_level'];
        $this->max_stock_level = $row['max_stock_level'];
        $this->unit_cost = $row['unit_cost'];
        $this->unit_price = $row['unit_price'];
        $this->is_active = $row['is_active'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    /**
     * Create a new item.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->item_code) || empty($this->item_name)) {
            return false;
        }

        // Check if item code exists within company and branch (if specified)
        if ($this->checkCodeExists($this->company_id, $this->item_code, $this->branch_id)) {
            return false;
        }

        $query = "INSERT INTO inventory_items (company_id, branch_id, item_code, item_name, description, category_id, 
                  unit_of_measure, current_stock, reorder_level, max_stock_level, unit_cost, 
                  unit_price, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->branch_id,
            $this->item_code,
            $this->item_name,
            $this->description ?? null,
            $this->category_id ?? null,
            $this->unit_of_measure,
            $this->current_stock ?? 0,
            $this->reorder_level ?? 0,
            $this->max_stock_level ?? null,
            $this->unit_cost ?? 0,
            $this->unit_price ?? 0,
            $this->is_active ?? 1
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update item details.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check if code exists (excluding current item) within company and branch
        if ($this->checkCodeExists($this->company_id, $this->item_code, $this->branch_id, $this->id)) {
            return false;
        }

        $query = "UPDATE inventory_items SET branch_id = ?, item_code = ?, item_name = ?, description = ?, 
                  category_id = ?, unit_of_measure = ?, reorder_level = ?, max_stock_level = ?, 
                  unit_cost = ?, unit_price = ?, is_active = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->branch_id,
            $this->item_code,
            $this->item_name,
            $this->description ?? null,
            $this->category_id ?? null,
            $this->unit_of_measure,
            $this->reorder_level ?? 0,
            $this->max_stock_level ?? null,
            $this->unit_cost ?? 0,
            $this->unit_price ?? 0,
            $this->is_active ?? 1,
            $this->id
        ]);
    }

    /**
     * Delete item.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM inventory_items WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Search items by name or code
     */
    public function search($term, $companyId, $branchId = null)
    {
        $term = "%$term%";
        $query = "SELECT i.id, i.company_id, i.branch_id, i.item_code, i.item_name, i.description, 
                  i.category_id, i.unit_of_measure, i.current_stock, i.reorder_level, 
                  i.max_stock_level, i.unit_cost, i.unit_price, i.is_active,
                  c.category_name, b.branch_name
                  FROM inventory_items i
                  LEFT JOIN inventory_categories c ON i.category_id = c.id
                  LEFT JOIN branches b ON i.branch_id = b.id
                  WHERE i.company_id = ? AND i.is_active = 1 AND (i.item_name LIKE ? OR i.item_code LIKE ?)";
        
        $params = [$companyId, $term, $term];
        
        if ($branchId) {
             // Optional: if dealing with multi-branch stock, filter by branch
             // For now assume shared access or specific requirement
        }
        
        $query .= " ORDER BY i.item_name ASC LIMIT 20";
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get all items for a company, optionally filtered by branch.
     */
    public function all($companyId = null, $branchId = null)
    {
        $query = "SELECT i.id, i.company_id, i.branch_id, i.item_code, i.item_name, i.description, 
                  i.category_id, i.unit_of_measure, i.current_stock, i.reorder_level, 
                  i.max_stock_level, i.unit_cost, i.unit_price, i.is_active, i.created_at,
                  c.category_name, b.branch_name
                  FROM inventory_items i
                  LEFT JOIN inventory_categories c ON i.category_id = c.id
                  LEFT JOIN branches b ON i.branch_id = b.id
                  WHERE 1=1";
        
        $params = [];
        if ($companyId) {
            $query .= " AND i.company_id = ?";
            $params[] = $companyId;
        }
        
        if ($branchId) {
            $query .= " AND (i.branch_id = ? OR i.branch_id IS NULL)"; // Include company-wide items too if desired, or strict filtering? 
            // Usually inventory is branch specific OR company wide. Let's assume strict filtering if branch is selected, 
            // but commonly users want to see "My Branch" + "Global". 
            // For now, let's implement strict branch filtering if provided, but maybe allow NULL if that's the intent.
            // Actually, based on schema `branch_id` DEFAULT NULL means company-wide. 
            // Let's stick to strict filtering for now to match other modules.
            $params[] = $branchId;
        }
        
        $query .= " ORDER BY i.item_name ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Check if item code exists (exclude current ID) within company/branch scope.
     */
    private function checkCodeExists($companyId, $code, $branchId = null, $excludeId = null)
    {
        $query = "SELECT id FROM inventory_items WHERE company_id = ? AND item_code = ?";
        $params = [$companyId, $code];
        
        if ($branchId) {
            $query .= " AND branch_id = ?";
            $params[] = $branchId;
        } else {
            $query .= " AND branch_id IS NULL";
        }
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get inventory statistics for a company.
     */
    public static function getStats($companyId)
    {
        $db = new Database();
        $stats = [];
        
        // Total Items
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM inventory_items WHERE company_id = ?", [$companyId]);
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Active Items
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM inventory_items WHERE company_id = ? AND is_active = 1", [$companyId]);
        $stats['active'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Low Stock Items
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM inventory_items WHERE company_id = ? AND current_stock > 0 AND current_stock <= reorder_level", [$companyId]);
        $stats['low_stock'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Out of Stock Items
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM inventory_items WHERE company_id = ? AND current_stock = 0", [$companyId]);
        $stats['out_of_stock'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;

        // Total Stock Quantity
        $stmt = $db->prepareSelect("SELECT COALESCE(SUM(current_stock), 0) as total FROM inventory_items WHERE company_id = ?", [$companyId]);
        $stats['total_stock'] = $stmt ? ($stmt->fetch()['total'] ?? 0) : 0;
        
        // Total Stock Value (FIFO based from batches)
        $stmt = $db->prepareSelect("SELECT COALESCE(SUM(quantity_remaining * unit_cost), 0) as total FROM inventory_batches WHERE company_id = ? AND is_active = 1", [$companyId]);
        $stats['total_value'] = $stmt ? ($stmt->fetch()['total'] ?? 0) : 0;
        
        return $stats;
    }
}
?>
