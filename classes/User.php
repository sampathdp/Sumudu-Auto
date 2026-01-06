<?php
class User
{
    public $id;
    public $company_id;
    public $branch_id;
    public $username;
    public $password; // Plaintext for create/change
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;
    public $role_id;
    private $password_hash;
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load user by ID using prepared statement.
     */
    private function loadById($id)
    {
        $query = "SELECT id, company_id, branch_id, username, password_hash, is_active, last_login, created_at, updated_at, role_id 
                  FROM users WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id = $row['id'];
                $this->company_id = $row['company_id'];
                $this->branch_id = $row['branch_id'];
                $this->username = $row['username'];
                $this->password_hash = $row['password_hash'];
                $this->is_active = (bool) $row['is_active'];
                $this->last_login = $row['last_login'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                $this->role_id = $row['role_id'];
            }
        }
    }

    /**
     * Create a new user.
     * Returns true on success, false on failure.
     */
    public function create()
    {
        if (empty($this->username) || empty($this->password) || empty($this->company_id)) {
            return false;
        }

        // Check if username exists within the same company
        if ($this->checkUsernameExists($this->company_id, $this->username)) {
            return false;
        }

        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        $query = "INSERT INTO users (company_id, branch_id, username, password_hash, is_active, role_id) VALUES (?, ?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->branch_id ?? null,
            $this->username, 
            $password_hash, 
            (int)$this->is_active, 
            $this->role_id ? (int)$this->role_id : null
        ]);
        if ($success) {
            $this->id = $this->db->getLastInsertId();
            $this->password_hash = $password_hash;
        }
        return $success;
    }

    /**
     * Login user by username.
     * Updates last_login on success.
     * Returns true on success, false on failure.
     * @return bool
     */
    public function login($username, $password, $companyId = null): bool
    {
        $query = "SELECT id, company_id, branch_id, username, password_hash, is_active, role_id FROM users WHERE username = ?";
        $params = [$username];

        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }

        $stmt = $this->db->prepareSelect($query, $params);
        if (!$stmt) {
            return false;
        }

        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        if (!$row['is_active'] || !password_verify($password, $row['password_hash'])) {
            return false;
        }

        // Update last_login
        $updateQuery = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
        $this->db->prepareExecute($updateQuery, [$row['id']]);

        // Set session
        $this->id = $row['id'];
        $this->company_id = $row['company_id'];
        $this->branch_id = $row['branch_id'];
        $this->username = $row['username'];
        $this->is_active = (bool)$row['is_active'];
        $this->role_id = $row['role_id'];
        $this->setUserSession();

        return true;
    }

    /**
     * Update user details.
     * Returns true on success, false on failure.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check if username exists within the same company (excluding current user)
        if ($this->checkUsernameExists($this->company_id, $this->username, $this->id)) {
            return false;
        }

        $query = "UPDATE users SET branch_id = ?, username = ?, is_active = ?, role_id = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->branch_id ?? null,
            $this->username, 
            (int)$this->is_active, 
            $this->role_id ? (int)$this->role_id : null, 
            $this->id
        ]);
    }

    /**
     * Change password.
     * Returns true on success, false on failure.
     */
    public function changePassword($id, $newPassword)
    {
        if (empty($newPassword)) {
            return false;
        }

        $password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $query = "UPDATE users SET password_hash = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$password_hash, $id]);
    }

    /**
     * Check if old password is correct.
     */
    public function checkOldPassword($id, $oldPassword)
    {
        $query = "SELECT password_hash FROM users WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                return password_verify($oldPassword, $row['password_hash']);
            }
        }
        return false;
    }

    /**
     * Get all users (optionally filtered by company_id).
     */
    public function all($companyId = null)
    {
        $query = "SELECT u.id, u.company_id, u.branch_id, u.username, u.is_active, u.created_at, u.last_login, u.role_id, 
                         r.role_name, b.branch_name, c.name as company_name
                  FROM users u 
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN branches b ON u.branch_id = b.id
                  LEFT JOIN companies c ON u.company_id = c.id";
        
        $params = [];
        if ($companyId) {
            $query .= " WHERE u.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get users by branch.
     */
    public function getByBranch($companyId, $branchId)
    {
        $query = "SELECT u.id, u.company_id, u.branch_id, u.username, u.is_active, u.created_at, u.last_login, u.role_id, 
                         r.role_name, b.branch_name
                  FROM users u 
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN branches b ON u.branch_id = b.id
                  WHERE u.company_id = ? AND (u.branch_id = ? OR u.branch_id IS NULL)
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, [$companyId, $branchId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Authenticate current session.
     */
    public function authenticate()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionId = $_SESSION['id'] ?? null;
        if (!$sessionId) {
            return false;
        }
        $query = "SELECT id FROM users WHERE id = ? AND is_active = 1";
        $stmt = $this->db->prepareSelect($query, [$sessionId]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Logout.
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        return true;
    }

    /**
     * Set user session with company_id and branch_id.
     */
    private function setUserSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_lifetime' => 3200]);
        }
        session_regenerate_id(true);
        $_SESSION['id'] = $this->id;
        $_SESSION['company_id'] = $this->company_id;
        $_SESSION['branch_id'] = $this->branch_id;
        $_SESSION['username'] = $this->username;
        $_SESSION['is_active'] = $this->is_active;
        $_SESSION['role_id'] = $this->role_id;
    }

    /**
     * Check if username exists within company (exclude current ID).
     */
    private function checkUsernameExists($companyId, $username, $excludeId = null)
    {
        $query = "SELECT id FROM users WHERE company_id = ? AND username = ?";
        $params = [$companyId, $username];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get last inserted ID.
     */
    public function getLastId()
    {
        return $this->db->getLastInsertId();
    }

    /**
     * Delete user.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM users WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get user statistics for a company.
     */
    public function getStatistics($companyId)
    {
        $query = "SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users
            FROM users WHERE company_id = ?";
        
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Count users for a company.
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM users WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>