<?php
class Branch
{
    public $id;
    public $company_id;
    public $branch_code;
    public $branch_name;
    public $address;
    public $phone;
    public $email;
    public $manager_id;
    public $is_main;
    public $is_active;
    public $settings_json;
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
     * Load branch by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT id, company_id, branch_code, branch_name, address, phone, email, 
                         manager_id, is_main, is_active, settings_json, created_at, updated_at
                  FROM branches WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id = $row['id'];
                $this->company_id = $row['company_id'];
                $this->branch_code = $row['branch_code'];
                $this->branch_name = $row['branch_name'];
                $this->address = $row['address'];
                $this->phone = $row['phone'];
                $this->email = $row['email'];
                $this->manager_id = $row['manager_id'];
                $this->is_main = $row['is_main'];
                $this->is_active = $row['is_active'];
                $this->settings_json = $row['settings_json'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
            }
        }
    }

    /**
     * Create a new branch.
     */
    public function create()
    {
        if (empty($this->branch_code) || empty($this->branch_name) || empty($this->company_id)) {
            return false;
        }

        // Check if branch code exists in this company
        if ($this->checkCodeExists($this->company_id, $this->branch_code)) {
            return false;
        }

        // Check company branch limit
        if (!$this->canAddBranch($this->company_id)) {
            return false;
        }

        $query = "INSERT INTO branches (company_id, branch_code, branch_name, address, phone, 
                                       email, manager_id, is_main, is_active, settings_json) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            strtoupper($this->branch_code),
            $this->branch_name,
            $this->address ?? null,
            $this->phone ?? null,
            $this->email ?? null,
            $this->manager_id ?? null,
            $this->is_main ?? 0,
            $this->is_active ?? 1,
            $this->settings_json ?? null
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update branch details.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check if code exists (excluding current branch)
        if ($this->checkCodeExists($this->company_id, $this->branch_code, $this->id)) {
            return false;
        }

        $query = "UPDATE branches SET branch_code = ?, branch_name = ?, address = ?, 
                  phone = ?, email = ?, manager_id = ?, is_main = ?, is_active = ?, 
                  settings_json = ? WHERE id = ?";
        
        return $this->db->prepareExecute($query, [
            strtoupper($this->branch_code),
            $this->branch_name,
            $this->address ?? null,
            $this->phone ?? null,
            $this->email ?? null,
            $this->manager_id ?? null,
            $this->is_main ?? 0,
            $this->is_active ?? 1,
            $this->settings_json,
            $this->id
        ]);
    }

    /**
     * Delete branch.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        
        // Prevent deletion of main branch
        if ($this->is_main) {
            return false;
        }
        
        // Check if branch has users
        $userCount = $this->getUserCount();
        if ($userCount > 0) {
            return false;
        }
        
        $query = "DELETE FROM branches WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all branches (optionally filtered by company).
     */
    public function all($companyId = null)
    {
        $query = "SELECT b.id, b.company_id, b.branch_code, b.branch_name, b.address, 
                         b.phone, b.email, b.is_main, b.is_active, b.created_at,
                         c.name as company_name,
                         (SELECT COUNT(*) FROM users WHERE branch_id = b.id) as user_count,
                         (SELECT COUNT(*) FROM employees WHERE branch_id = b.id) as employee_count
                  FROM branches b
                  LEFT JOIN companies c ON b.company_id = c.id";
        
        $params = [];
        if ($companyId) {
            $query .= " WHERE b.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY b.company_id, b.is_main DESC, b.branch_name";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get active branches for a company.
     */
    public function getActiveByCompany($companyId)
    {
        $query = "SELECT id, branch_code, branch_name, is_main 
                  FROM branches 
                  WHERE company_id = ? AND is_active = 1 
                  ORDER BY is_main DESC, branch_name";
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get user count for this branch.
     */
    public function getUserCount()
    {
        if (!$this->id) {
            return 0;
        }
        $query = "SELECT COUNT(*) as count FROM users WHERE branch_id = ?";
        $stmt = $this->db->prepareSelect($query, [$this->id]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }

    /**
     * Get employee count for this branch.
     */
    public function getEmployeeCount()
    {
        if (!$this->id) {
            return 0;
        }
        $query = "SELECT COUNT(*) as count FROM employees WHERE branch_id = ?";
        $stmt = $this->db->prepareSelect($query, [$this->id]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }

    /**
     * Check if branch code exists in company (exclude current ID).
     */
    private function checkCodeExists($companyId, $code, $excludeId = null)
    {
        $query = "SELECT id FROM branches WHERE company_id = ? AND branch_code = ?";
        $params = [$companyId, strtoupper($code)];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Check if company can add more branches.
     */
    private function canAddBranch($companyId)
    {
        $company = new Company($companyId);
        if (!$company->id) {
            return false;
        }
        
        $currentCount = $this->db->prepareSelect(
            "SELECT COUNT(*) as count FROM branches WHERE company_id = ?",
            [$companyId]
        )->fetch()['count'] ?? 0;
        
        return $currentCount < $company->max_branches;
    }

    /**
     * Set as main branch (unset others).
     */
    public function setAsMain()
    {
        if (!$this->id || !$this->company_id) {
            return false;
        }
        
        // Unset current main branch
        $this->db->prepareExecute(
            "UPDATE branches SET is_main = 0 WHERE company_id = ?",
            [$this->company_id]
        );
        
        // Set this as main
        $this->is_main = 1;
        return $this->db->prepareExecute(
            "UPDATE branches SET is_main = 1 WHERE id = ?",
            [$this->id]
        );
    }

    /**
     * Get branch statistics.
     */
    public static function getStats($companyId = null)
    {
        $db = new Database();
        $stats = [];
        
        $whereClause = $companyId ? " WHERE company_id = ?" : "";
        $params = $companyId ? [$companyId] : [];
        
        // Total branches
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM branches" . $whereClause, $params);
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Active branches
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM branches WHERE is_active = 1" . 
            ($companyId ? " AND company_id = ?" : ""), $params);
        $stats['active'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Inactive branches
        $stats['inactive'] = $stats['total'] - $stats['active'];
        
        // Main branches
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM branches WHERE is_main = 1" . 
            ($companyId ? " AND company_id = ?" : ""), $params);
        $stats['main'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        return $stats;
    }
}
