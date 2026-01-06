<?php
class CompanyProfile
{
    public $id;
    public $company_id;
    public $name;
    public $address;
    public $mobile_number_1;
    public $mobile_number_2;
    public $email;
    public $image_name;
    public $is_active;
    public $is_vat;
    public $tax_number;
    public $tax_percentage;
    public $customer_id;
    public $company_code;
    public $theme;
    public $favicon;
    public $cashbook_opening_balance;
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load company profile by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM company_profile WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                foreach ($row as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
            }
        }
    }

    /**
     * Load active company profile for a specific company.
     */
    public function loadActive($companyId = null)
    {
        $query = "SELECT * FROM company_profile WHERE is_active = 1";
        $params = [];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " LIMIT 1";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                foreach ($row as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Load company profile by company_id.
     */
    public function loadByCompanyId($companyId)
    {
        $query = "SELECT * FROM company_profile WHERE company_id = ? LIMIT 1";
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                foreach ($row as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Create a new company profile.
     */
    public function create()
    {
        if (empty($this->company_id)) {
            return false;
        }

        $query = "INSERT INTO company_profile (company_id, name, address, mobile_number_1, mobile_number_2, 
                  email, image_name, is_active, is_vat, tax_number, tax_percentage, 
                  customer_id, company_code, theme, favicon, cashbook_opening_balance) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->name,
            $this->address,
            $this->mobile_number_1,
            $this->mobile_number_2 ?? '',
            $this->email,
            $this->image_name ?? '',
            $this->is_active ?? 1,
            $this->is_vat ?? 0,
            $this->tax_number ?? '',
            $this->tax_percentage ?? 0,
            $this->customer_id ?? 0,
            $this->company_code ?? '',
            $this->theme ?? 'dark',
            $this->favicon ?? '',
            $this->cashbook_opening_balance ?? 0
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update company profile.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        $query = "UPDATE company_profile SET 
                  name = ?, address = ?, mobile_number_1 = ?, mobile_number_2 = ?, 
                  email = ?, image_name = ?, is_active = ?, is_vat = ?, 
                  tax_number = ?, tax_percentage = ?, customer_id = ?, 
                  company_code = ?, theme = ?, favicon = ?, cashbook_opening_balance = ? 
                  WHERE id = ?";

        return $this->db->prepareExecute($query, [
            $this->name,
            $this->address,
            $this->mobile_number_1,
            $this->mobile_number_2 ?? '',
            $this->email,
            $this->image_name ?? '',
            $this->is_active ?? 1,
            $this->is_vat ?? 0,
            $this->tax_number ?? '',
            $this->tax_percentage ?? 0,
            $this->customer_id ?? 0,
            $this->company_code ?? '',
            $this->theme ?? 'dark',
            $this->favicon ?? '',
            $this->cashbook_opening_balance ?? 0,
            $this->id
        ]);
    }

    /**
     * Upload company logo.
     */
    public function uploadLogo($file)
    {
        $uploadDir = __DIR__ . '/../uploads/company/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds 5MB limit.'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . $this->company_id . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Delete old logo if exists
            if ($this->image_name && file_exists($uploadDir . $this->image_name)) {
                unlink($uploadDir . $this->image_name);
            }

            $this->image_name = $filename;
            $this->update();

            return ['success' => true, 'filename' => $filename, 'message' => 'Logo uploaded successfully.'];
        }

        return ['success' => false, 'message' => 'Failed to upload logo.'];
    }

    /**
     * Upload company favicon.
     */
    public function uploadFavicon($file)
    {
        $uploadDir = __DIR__ . '/../uploads/company/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg'];
        $maxSize = 1 * 1024 * 1024; // 1MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only ICO and PNG are allowed.'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds 1MB limit.'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'favicon_' . $this->company_id . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Delete old favicon if exists
            if ($this->favicon && file_exists($uploadDir . $this->favicon)) {
                unlink($uploadDir . $this->favicon);
            }

            $this->favicon = $filename;
            $this->update();

            return ['success' => true, 'filename' => $filename, 'message' => 'Favicon uploaded successfully.'];
        }

        return ['success' => false, 'message' => 'Failed to upload favicon.'];
    }

    /**
     * Get all company profiles (optionally filtered by company_id).
     */
    public function all($companyId = null)
    {
        $query = "SELECT cp.*, c.name as master_company_name 
                  FROM company_profile cp
                  LEFT JOIN companies c ON cp.company_id = c.id";
        $params = [];
        
        if ($companyId) {
            $query .= " WHERE cp.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY cp.is_active DESC, cp.name ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Delete company profile.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }

        // Delete associated images
        $uploadDir = __DIR__ . '/../uploads/company/';
        if ($this->image_name && file_exists($uploadDir . $this->image_name)) {
            unlink($uploadDir . $this->image_name);
        }
        if ($this->favicon && file_exists($uploadDir . $this->favicon)) {
            unlink($uploadDir . $this->favicon);
        }

        $query = "DELETE FROM company_profile WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Check if profile exists for company.
     */
    public static function existsForCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT id FROM company_profile WHERE company_id = ?", [$companyId]);
        return $stmt && $stmt->rowCount() > 0;
    }
}
?>
