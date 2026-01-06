<?php
class InventoryCategory
{
    public $id;
    public $company_id;
    public $category_name;
    public $description;
    public $parent_category_id;
    public $is_active;
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
     * Load category by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM inventory_categories WHERE id = ?";
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
        $this->category_name = $row['category_name'];
        $this->description = $row['description'];
        $this->parent_category_id = $row['parent_category_id'];
        $this->is_active = $row['is_active'];
        $this->created_at = $row['created_at'];
    }

    /**
     * Create a new category.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->category_name)) {
            return false;
        }

        // Check if category name exists within company
        if ($this->checkNameExists($this->company_id, $this->category_name)) {
            return false;
        }

        $query = "INSERT INTO inventory_categories (company_id, category_name, description, parent_category_id, is_active) 
                  VALUES (?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->category_name,
            $this->description ?? null,
            $this->parent_category_id ?? null,
            $this->is_active ?? 1
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update category details.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check if name exists (excluding current category) within company
        if ($this->checkNameExists($this->company_id, $this->category_name, $this->id)) {
            return false;
        }

        $query = "UPDATE inventory_categories SET category_name = ?, description = ?, 
                  parent_category_id = ?, is_active = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->category_name,
            $this->description ?? null,
            $this->parent_category_id ?? null,
            $this->is_active ?? 1,
            $this->id
        ]);
    }

    /**
     * Delete category.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM inventory_categories WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all categories for a company.
     */
    public function all($companyId = null)
    {
        $query = "SELECT c.id, c.company_id, c.category_name, c.description, c.parent_category_id, 
                  c.is_active, c.created_at, p.category_name as parent_name
                  FROM inventory_categories c
                  LEFT JOIN inventory_categories p ON c.parent_category_id = p.id
                  WHERE 1=1";
        
        $params = [];
        if ($companyId) {
            $query .= " AND c.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY c.category_name ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get active categories for dropdown.
     */
    public function getActiveCategories($companyId = null)
    {
        $query = "SELECT id, category_name FROM inventory_categories 
                  WHERE is_active = 1";
        $params = [];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY category_name ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Check if category name exists (exclude current ID) within company.
     */
    private function checkNameExists($companyId, $name, $excludeId = null)
    {
        $query = "SELECT id FROM inventory_categories WHERE company_id = ? AND category_name = ?";
        $params = [$companyId, $name];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get category statistics for a company.
     */
    public function getStatistics($companyId)
    {
        $query = "SELECT 
            COUNT(*) as total_categories,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_categories,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_categories
            FROM inventory_categories WHERE company_id = ?";
        
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Count categories for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM inventory_categories WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>
