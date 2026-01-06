<?php
class Customer
{
    public $id;
    public $company_id;
    public $name;
    public $phone;
    public $email;
    public $address;
    public $total_visits;
    public $last_visit_date;
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
     * Load customer by ID using prepared statement.
     */
    private function loadById($id)
    {
        $query = "SELECT id, company_id, name, phone, email, address, total_visits, last_visit_date, created_at
                  FROM customers WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id = $row['id'];
                $this->company_id = $row['company_id'];
                $this->name = $row['name'];
                $this->phone = $row['phone'];
                $this->email = $row['email'];
                $this->address = $row['address'];
                $this->total_visits = $row['total_visits'];
                $this->last_visit_date = $row['last_visit_date'];
                $this->created_at = $row['created_at'];
            }
        }
    }
    
    /**
     * Get customer by phone number within company.
     * Returns customer data array or false.
     */
    public function getByPhone($phone, $companyId = null)
    {
        $query = "SELECT id, company_id, name, phone, email, address, total_visits, last_visit_date, created_at 
                  FROM customers WHERE phone = ?";
        $params = [$phone];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                // Populate current object if found
                $this->id = $row['id'];
                $this->company_id = $row['company_id'];
                $this->name = $row['name'];
                $this->phone = $row['phone'];
                $this->email = $row['email'];
                $this->address = $row['address'];
                $this->total_visits = $row['total_visits'];
                $this->last_visit_date = $row['last_visit_date'];
                $this->created_at = $row['created_at'];
                return $row;
            }
        }
        return false;
    }

    /**
     * Create a new customer.
     * Returns true on success, false on failure.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->name) || empty($this->phone)) {
            return false;
        }

        // Check if phone exists within company
        if ($this->checkPhoneExists($this->company_id, $this->phone)) {
            return false;
        }

        $query = "INSERT INTO customers (company_id, name, phone, email, address) VALUES (?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->name,
            $this->phone,
            $this->email ?? null,
            $this->address ?? null
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update customer details.
     * Returns true on success, false on failure.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check if phone exists within company (excluding current customer)
        if ($this->checkPhoneExists($this->company_id, $this->phone, $this->id)) {
            return false;
        }

        $query = "UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->name,
            $this->phone,
            $this->email ?? null,
            $this->address ?? null,
            $this->id
        ]);
    }

    /**
     * Delete customer.
     * Returns true on success, false on failure.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM customers WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all customers (optionally filtered by company).
     */
    public function all($companyId = null)
    {
        $query = "SELECT id, company_id, name, phone, email, address, total_visits, last_visit_date, created_at 
                  FROM customers";
        $params = [];
        
        if ($companyId) {
            $query .= " WHERE company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Search customers by name or phone within company.
     */
    public function search($term, $companyId = null)
    {
        $query = "SELECT id, company_id, name, phone, email, address 
                  FROM customers 
                  WHERE (name LIKE ? OR phone LIKE ?)";
        $params = ["%$term%", "%$term%"];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY name LIMIT 20";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Update visit stats.
     */
    public function updateVisitStats()
    {
        if (!$this->id) {
            return false;
        }
        
        $query = "UPDATE customers SET total_visits = total_visits + 1, last_visit_date = CURDATE() WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Check if phone exists within company (exclude current ID).
     */
    private function checkPhoneExists($companyId, $phone, $excludeId = null)
    {
        $query = "SELECT id FROM customers WHERE company_id = ? AND phone = ?";
        $params = [$companyId, $phone];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get customer statistics for a company.
     */
    public static function getStats($companyId)
    {
        $db = new Database();
        $stats = [];
        
        // Total Customers
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM customers WHERE company_id = ?", [$companyId]);
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // New This Month
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM customers WHERE company_id = ? AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$companyId]);
        $stats['new_this_month'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        return $stats;
    }
}
?>