<?php
class Vehicle
{
    public $id;
    public $company_id;
    public $customer_id;
    public $registration_number;
    public $make;
    public $model;
    public $year;
    public $color;
    public $current_mileage;
    public $last_service_date;
    public $last_oil_change_date;
    public $created_at;
    public $updated_at;
    public $customer_name;
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load vehicle by ID using prepared statement.
     */
    private function loadById($id)
    {
        $query = "SELECT v.id, v.company_id, v.customer_id, v.registration_number, v.make, v.model, v.year, v.color, 
                         v.current_mileage, v.last_service_date, v.last_oil_change_date, v.created_at, v.updated_at, 
                         c.name as customer_name
                  FROM vehicles v 
                  JOIN customers c ON v.customer_id = c.id 
                  WHERE v.id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id = $row['id'];
                $this->company_id = $row['company_id'];
                $this->customer_id = $row['customer_id'];
                $this->registration_number = $row['registration_number'];
                $this->make = $row['make'];
                $this->model = $row['model'];
                $this->year = (int) $row['year'];
                $this->color = $row['color'];
                $this->current_mileage = (int) $row['current_mileage'];
                $this->last_service_date = $row['last_service_date'];
                $this->last_oil_change_date = $row['last_oil_change_date'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                $this->customer_name = $row['customer_name'];
            }
        }
    }

    /**
     * Create a new vehicle.
     * Returns true on success, false on failure.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->customer_id) || empty($this->registration_number) || empty($this->make) || empty($this->model)) {
            return false;
        }
        
        // Check if registration_number exists within company
        if ($this->checkRegExists($this->company_id, $this->registration_number)) {
            return false;
        }
        
        $query = "INSERT INTO vehicles (company_id, customer_id, registration_number, make, model, year, color, current_mileage, last_service_date, last_oil_change_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $this->company_id,
            (int) $this->customer_id,
            strtoupper($this->registration_number),
            $this->make,
            $this->model,
            $this->year ?: null,
            $this->color ?: null,
            $this->current_mileage ?: null,
            $this->last_service_date ?: null,
            $this->last_oil_change_date ?: null
        ];
        $success = $this->db->prepareExecute($query, $params);
        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update vehicle details.
     * Returns true on success, false on failure.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }
        
        // Check if registration_number exists within company (excluding current)
        if ($this->checkRegExists($this->company_id, $this->registration_number, $this->id)) {
            return false;
        }
        
        $query = "UPDATE vehicles SET customer_id = ?, registration_number = ?, make = ?, model = ?, year = ?, 
                  color = ?, current_mileage = ?, last_service_date = ?, last_oil_change_date = ? WHERE id = ?";
        $params = [
            (int) $this->customer_id,
            strtoupper($this->registration_number),
            $this->make,
            $this->model,
            $this->year ?: null,
            $this->color ?: null,
            $this->current_mileage ?: null,
            $this->last_service_date ?: null,
            $this->last_oil_change_date ?: null,
            $this->id
        ];
        return $this->db->prepareExecute($query, $params);
    }

    /**
     * Delete vehicle.
     * Returns true on success, false on failure.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM vehicles WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all vehicles with customer name (optionally filtered by company).
     */
    public function all($companyId = null)
    {
        $query = "SELECT v.id, v.company_id, v.customer_id, v.registration_number, v.make, v.model, v.year, v.color, 
                         v.current_mileage, v.last_service_date, v.last_oil_change_date, v.created_at, c.name as customer_name, c.phone as customer_phone 
                  FROM vehicles v 
                  JOIN customers c ON v.customer_id = c.id";
        
        $params = [];
        if ($companyId) {
            $query .= " WHERE v.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY v.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get vehicles by customer.
     */
    public function getByCustomer($customerId, $companyId = null)
    {
        $query = "SELECT v.id, v.company_id, v.customer_id, v.registration_number, v.make, v.model, v.year, v.color, 
                         v.current_mileage, v.last_service_date, v.last_oil_change_date, v.created_at 
                  FROM vehicles v 
                  WHERE v.customer_id = ?";
        $params = [$customerId];
        
        if ($companyId) {
            $query .= " AND v.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY v.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get vehicle by registration number within company.
     */
    public function getByRegistration($regNumber, $companyId = null)
    {
        $query = "SELECT v.id, v.company_id, v.customer_id, v.registration_number, v.make, v.model, v.year, v.color, 
                         v.current_mileage, v.last_service_date, v.last_oil_change_date, c.name as customer_name
                  FROM vehicles v 
                  JOIN customers c ON v.customer_id = c.id
                  WHERE v.registration_number = ?";
        $params = [strtoupper($regNumber)];
        
        if ($companyId) {
            $query .= " AND v.company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                foreach ($row as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
                return $row;
            }
        }
        return false;
    }

    /**
     * Update mileage.
     */
    public function updateMileage($mileage)
    {
        if (!$this->id) {
            return false;
        }
        $query = "UPDATE vehicles SET current_mileage = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$mileage, $this->id]);
    }

    /**
     * Update last service date.
     */
    public function updateLastServiceDate($date = null)
    {
        if (!$this->id) {
            return false;
        }
        $date = $date ?: date('Y-m-d');
        $query = "UPDATE vehicles SET last_service_date = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$date, $this->id]);
    }

    /**
     * Check if registration_number exists within company (exclude current ID).
     */
    private function checkRegExists($companyId, $reg, $excludeId = null)
    {
        $query = "SELECT id FROM vehicles WHERE company_id = ? AND registration_number = ?";
        $params = [$companyId, strtoupper($reg)];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Count vehicles for a company.
     */
    /**
     * Get vehicle statistics for a company.
     */
    public static function getStats($companyId)
    {
        $db = new Database();
        $stats = [];
        
        // Total Vehicles
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM vehicles WHERE company_id = ?", [$companyId]);
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Serviced This Month
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM vehicles WHERE company_id = ? AND last_service_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$companyId]);
        $stats['serviced_this_month'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        return $stats;
    }
}
