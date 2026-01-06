<?php
class Company
{
    public $id;
    public $company_code;
    public $name;
    public $package_type;
    public $settings_json;
    public $status;
    public $trial_ends_at;
    public $max_users;
    public $max_employees;
    public $max_branches;
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
     * Load company by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT id, company_code, name, package_type, settings_json, status, 
                         trial_ends_at, max_users, max_employees, max_branches, created_at, updated_at
                  FROM companies WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id = $row['id'];
                $this->company_code = $row['company_code'];
                $this->name = $row['name'];
                $this->package_type = $row['package_type'];
                $this->settings_json = $row['settings_json'];
                $this->status = $row['status'];
                $this->trial_ends_at = $row['trial_ends_at'];
                $this->max_users = $row['max_users'];
                $this->max_employees = $row['max_employees'];
                $this->max_branches = $row['max_branches'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
            }
        }
    }

    /**
     * Load company by code.
     */
    public function loadByCode($code)
    {
        $query = "SELECT id FROM companies WHERE company_code = ?";
        $stmt = $this->db->prepareSelect($query, [$code]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->loadById($row['id']);
                return true;
            }
        }
        return false;
    }

    /**
     * Create a new company.
     */
    public function create()
    {
        if (empty($this->name)) {
            return false;
        }

        // Auto-generate code if missing
        if (empty($this->company_code)) {
            $this->company_code = $this->generateUniqueCode();
        }

        // Check if company code exists (double check collision if passed manually)
        if ($this->checkCodeExists($this->company_code)) {
            return false;
        }

        $query = "INSERT INTO companies (company_code, name, package_type, settings_json, status, 
                                        trial_ends_at, max_users, max_employees, max_branches) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $success = $this->db->prepareExecute($query, [
            $this->company_code,
            $this->name,
            $this->package_type ?? 'starter',
            $this->settings_json ?? $this->getDefaultSettings(),
            $this->status ?? 'trial',
            $this->trial_ends_at ?? null,
            $this->max_users ?? 5,
            $this->max_employees ?? 10,
            $this->max_branches ?? 1
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Generate a unique 6-digit numeric code
     */
    private function generateUniqueCode()
    {
        do {
            $code = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while ($this->checkCodeExists($code));
        return $code;
    }

    /**
     * Update company details.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check if code exists (excluding current company)
        if ($this->checkCodeExists($this->company_code, $this->id)) {
            return false;
        }

        $query = "UPDATE companies SET company_code = ?, name = ?, package_type = ?, 
                  settings_json = ?, status = ?, trial_ends_at = ?, 
                  max_users = ?, max_employees = ?, max_branches = ? WHERE id = ?";
        
        return $this->db->prepareExecute($query, [
            $this->company_code,
            $this->name,
            $this->package_type ?? 'starter',
            $this->settings_json,
            $this->status ?? 'active',
            $this->trial_ends_at,
            $this->max_users ?? 5,
            $this->max_employees ?? 10,
            $this->max_branches ?? 1,
            $this->id
        ]);
    }

    /**
     * Delete company.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        
        // Prevent deletion if company has users
        $userCount = $this->getUserCount();
        if ($userCount > 0) {
            return false;
        }
        
        $query = "DELETE FROM companies WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all companies.
     */
    public function all()
    {
        $query = "SELECT c.id, c.company_code, c.name, c.package_type, c.status, 
                         c.max_users, c.max_employees, c.max_branches, c.created_at,
                         (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
                         (SELECT COUNT(*) FROM branches WHERE company_id = c.id) as branch_count
                  FROM companies c 
                  ORDER BY c.created_at DESC";
        $stmt = $this->db->prepareSelect($query);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get active companies.
     */
    public function getActive()
    {
        $query = "SELECT id, company_code, name, package_type 
                  FROM companies 
                  WHERE status IN ('active', 'trial') 
                  ORDER BY name";
        $stmt = $this->db->prepareSelect($query);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get user count for this company.
     */
    public function getUserCount()
    {
        if (!$this->id) {
            return 0;
        }
        $query = "SELECT COUNT(*) as count FROM users WHERE company_id = ?";
        $stmt = $this->db->prepareSelect($query, [$this->id]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }

    /**
     * Get branch count for this company.
     */
    public function getBranchCount()
    {
        if (!$this->id) {
            return 0;
        }
        $query = "SELECT COUNT(*) as count FROM branches WHERE company_id = ?";
        $stmt = $this->db->prepareSelect($query, [$this->id]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }

    /**
     * Check if company code exists (exclude current ID).
     */
    private function checkCodeExists($code, $excludeId = null)
    {
        $query = "SELECT id FROM companies WHERE company_code = ?";
        $params = [$code];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get default settings JSON.
     */
    private function getDefaultSettings()
    {
        return json_encode([
            'features' => [
                'inventory_enabled' => true,
                'quotations_enabled' => true,
                'bookings_enabled' => true,
                'reports_enabled' => true,
                'multi_branch' => false
            ],
            'branding' => [
                'primary_color' => '#3B82F6',
                'logo_url' => null
            ],
            'notifications' => [
                'sms_enabled' => false,
                'email_enabled' => true
            ],
            'tax' => [
                'enabled' => true,
                'rate' => 15.0
            ]
        ]);
    }

    /**
     * Get settings as array.
     */
    public function getSettings()
    {
        if (!$this->settings_json) {
            return json_decode($this->getDefaultSettings(), true);
        }
        return json_decode($this->settings_json, true);
    }

    /**
     * Update settings.
     */
    public function updateSettings($settings)
    {
        if (!$this->id) {
            return false;
        }
        $this->settings_json = json_encode($settings);
        return $this->update();
    }

    /**
     * Check if feature is enabled.
     */
    public function isFeatureEnabled($feature)
    {
        $settings = $this->getSettings();
        return $settings['features'][$feature] ?? false;
    }

    /**
     * Get package limits.
     */
    public static function getPackageLimits($packageType)
    {
        $limits = [
            'starter' => ['max_users' => 3, 'max_employees' => 5, 'max_branches' => 1],
            'business' => ['max_users' => 10, 'max_employees' => 25, 'max_branches' => 3],
            'pro' => ['max_users' => 25, 'max_employees' => 50, 'max_branches' => 10],
            'enterprise' => ['max_users' => 999, 'max_employees' => 999, 'max_branches' => 999]
        ];
        return $limits[$packageType] ?? $limits['starter'];
    }

    /**
     * Get company statistics.
     */
    public static function getStats()
    {
        $db = new Database();
        $stats = [];
        
        // Total companies
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM companies");
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Active companies
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM companies WHERE status = 'active'");
        $stats['active'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Trial companies
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM companies WHERE status = 'trial'");
        $stats['trial'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Suspended companies
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM companies WHERE status = 'suspended'");
        $stats['suspended'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        return $stats;
    }
}
?>
