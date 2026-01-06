<?php
class ServicePackage
{
    public $id;
    public $company_id;
    public $package_name;
    public $description;
    public $base_price;
    public $estimated_duration;
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
     * Load package by ID
     */
    private function loadById($id)
    {
        $query = "SELECT id, company_id, package_name, description, base_price, estimated_duration, is_active, created_at
                  FROM service_packages WHERE id = ?";

        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id                = (int)$row['id'];
                $this->company_id        = $row['company_id'];
                $this->package_name      = $row['package_name'];
                $this->description       = $row['description'];
                $this->base_price        = (float)$row['base_price'];
                $this->estimated_duration = (int)$row['estimated_duration'];
                $this->is_active         = (bool)$row['is_active'];
                $this->created_at        = $row['created_at'];
            }
        }
    }

    /**
     * Create a new service package
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->package_name)) {
            return false;
        }

        if ($this->checkPackageNameExists($this->company_id, $this->package_name)) {
            return false;
        }

        $query = "INSERT INTO service_packages 
                  (company_id, package_name, description, base_price, estimated_duration, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->package_name,
            $this->description ?? null,
            $this->base_price ?? 0.00,
            $this->estimated_duration ?? 0,
            (int)$this->is_active
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }

        return $success;
    }

    /**
     * Update existing package
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        if ($this->checkPackageNameExists($this->company_id, $this->package_name, $this->id)) {
            return false;
        }

        $query = "UPDATE service_packages SET 
                  package_name = ?, description = ?, base_price = ?, 
                  estimated_duration = ?, is_active = ? 
                  WHERE id = ?";

        return $this->db->prepareExecute($query, [
            $this->package_name,
            $this->description ?? null,
            $this->base_price ?? 0.00,
            $this->estimated_duration ?? 0,
            (int)$this->is_active,
            $this->id
        ]);
    }

    /**
     * Delete package
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }

        $query = "DELETE FROM service_packages WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all packages (optionally filtered by company)
     */
    public function all($companyId = null)
    {
        $query = "SELECT id, company_id, package_name, description, base_price, estimated_duration, is_active, created_at 
                  FROM service_packages";
        $params = [];
        
        if ($companyId) {
            $query .= " WHERE company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get only active packages (for dropdowns, etc.)
     */
    public function active($companyId = null)
    {
        $query = "SELECT id, package_name, base_price, estimated_duration 
                  FROM service_packages 
                  WHERE is_active = 1";
        $params = [];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY package_name ASC";

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Check if package name already exists within company (excluding current record)
     */
    private function checkPackageNameExists($companyId, $package_name, $excludeId = null)
    {
        $query = "SELECT id FROM service_packages WHERE company_id = ? AND package_name = ?";
        $params = [$companyId, $package_name];

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get service package statistics for a company.
     */
    public static function getStatistics($companyId)
    {
        $db = new Database();
        $stats = [];
        
        // Total Packages
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM service_packages WHERE company_id = ?", [$companyId]);
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Active Packages
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM service_packages WHERE company_id = ? AND is_active = 1", [$companyId]);
        $stats['active'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Inactive (Calculated)
        $stats['inactive'] = $stats['total'] - $stats['active'];
        
        return $stats;
    }

    /**
     * Count packages for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM service_packages WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>