<?php
class ExpenseCategory
{
    public $id;
    public $company_id;
    public $category_name;
    public $description;
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

    private function loadById($id)
    {
        $query = "SELECT * FROM expense_categories WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id = $row['id'];
                $this->company_id = $row['company_id'];
                $this->category_name = $row['category_name'];
                $this->description = $row['description'];
                $this->is_active = $row['is_active'];
                $this->created_at = $row['created_at'];
            }
        }
    }

    public function create()
    {
        if (empty($this->company_id) || empty($this->category_name)) {
            return false;
        }

        $query = "INSERT INTO expense_categories (company_id, category_name, description, is_active) VALUES (?, ?, ?, ?)";
        $params = [
            $this->company_id,
            $this->category_name,
            $this->description,
            $this->is_active ?? 1
        ];
        
        $success = $this->db->prepareExecute($query, $params);
        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    public function update()
    {
        if (!$this->id) return false;

        $query = "UPDATE expense_categories SET category_name = ?, description = ?, is_active = ? WHERE id = ?";
        $params = [
            $this->category_name,
            $this->description,
            $this->is_active,
            $this->id
        ];
        return $this->db->prepareExecute($query, $params);
    }

    public function delete()
    {
        if (!$this->id) return false;
        // Soft delete instead of hard delete usually preferred, but schema implies hard delete or is_active toggle
        // For now, hard delete to match simple CRUD, or check for dependencies
        $query = "DELETE FROM expense_categories WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    public function all($companyId, $activeOnly = true)
    {
        $query = "SELECT * FROM expense_categories WHERE company_id = ?";
        if ($activeOnly) {
            $query .= " AND is_active = 1";
        }
        $query .= " ORDER BY category_name ASC";
        
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        return $stmt ? $stmt->fetchAll() : [];
    }
}
