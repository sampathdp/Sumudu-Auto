<?php
class Supplier
{
    public $id;
    public $company_id;
    public $supplier_name;
    public $contact_person;
    public $phone;
    public $email;
    public $address;
    public $tax_id;
    public $payment_terms;
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
     * Load supplier by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM suppliers WHERE id = ?";
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
        $this->supplier_name = $row['supplier_name'];
        $this->contact_person = $row['contact_person'];
        $this->phone = $row['phone'];
        $this->email = $row['email'];
        $this->address = $row['address'];
        $this->tax_id = $row['tax_id'];
        $this->payment_terms = $row['payment_terms'];
        $this->is_active = $row['is_active'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    /**
     * Create a new supplier.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->supplier_name)) {
            return false;
        }

        // Check if supplier name exists within company
        if ($this->checkNameExists($this->company_id, $this->supplier_name)) {
            return false;
        }

        $query = "INSERT INTO suppliers (company_id, supplier_name, contact_person, phone, email, address, tax_id, payment_terms, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->supplier_name,
            $this->contact_person ?? null,
            $this->phone ?? null,
            $this->email ?? null,
            $this->address ?? null,
            $this->tax_id ?? null,
            $this->payment_terms ?? null,
            $this->is_active ?? 1
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update supplier details.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check if name exists (excluding current supplier) within company
        if ($this->checkNameExists($this->company_id, $this->supplier_name, $this->id)) {
            return false;
        }

        $query = "UPDATE suppliers SET supplier_name = ?, contact_person = ?, phone = ?, email = ?, 
                  address = ?, tax_id = ?, payment_terms = ?, is_active = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->supplier_name,
            $this->contact_person ?? null,
            $this->phone ?? null,
            $this->email ?? null,
            $this->address ?? null,
            $this->tax_id ?? null,
            $this->payment_terms ?? null,
            $this->is_active ?? 1,
            $this->id
        ]);
    }

    /**
     * Delete supplier.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM suppliers WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all suppliers for a company.
     */
    public function all($companyId = null)
    {
        $query = "SELECT * FROM suppliers";
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
     * Get active suppliers for a company.
     */
    public function getActive($companyId = null)
    {
        $query = "SELECT * FROM suppliers WHERE is_active = 1";
        $params = [];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY supplier_name ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Check if supplier name exists (exclude current ID) within company.
     */
    private function checkNameExists($companyId, $name, $excludeId = null)
    {
        $query = "SELECT id FROM suppliers WHERE company_id = ? AND supplier_name = ?";
        $params = [$companyId, $name];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Count suppliers for a company
     */
    /**
     * Get supplier statistics for a company.
     */
    public static function getStats($companyId)
    {
        $db = new Database();
        $stats = [];
        
        // Total Suppliers
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM suppliers WHERE company_id = ?", [$companyId]);
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Active Suppliers
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM suppliers WHERE company_id = ? AND is_active = 1", [$companyId]);
        $stats['active'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        // Inactive (Calculated)
        $stats['inactive'] = $stats['total'] - $stats['active'];
        
        return $stats;
    }
}
?>
