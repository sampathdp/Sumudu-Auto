<?php
class QRCode
{
    public $id;
    public $company_id;
    public $qr_code;
    public $service_id;
    public $status;
    public $color_code;
    public $generated_at;
    public $expires_at;
    public $scanned_count;
    public $last_scanned_at;
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load QR code by ID using prepared statement.
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM qr_codes WHERE id = ?";
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
        $this->qr_code = $row['qr_code'];
        $this->service_id = $row['service_id'];
        $this->status = $row['status'];
        $this->color_code = $row['color_code'];
        $this->generated_at = $row['generated_at'];
        $this->expires_at = $row['expires_at'];
        $this->scanned_count = (int) $row['scanned_count'];
        $this->last_scanned_at = $row['last_scanned_at'];
    }

    /**
     * Create a new QR code.
     * Returns true on success, false on failure.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->qr_code)) {
            return false;
        }

        // Check if QR code already exists within company
        if ($this->checkQRCodeExists($this->company_id, $this->qr_code)) {
            return false;
        }

        $query = "INSERT INTO qr_codes (company_id, qr_code, service_id, status, color_code) 
                  VALUES (?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->qr_code,
            $this->service_id,
            $this->status ?: 'active',
            $this->color_code ?: 'red'
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update QR code details.
     * Returns true on success, false on failure.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        $query = "UPDATE qr_codes SET 
            service_id = ?,
            status = ?,
            color_code = ?,
            expires_at = ?,
            scanned_count = ?,
            last_scanned_at = ?
            WHERE id = ?";

        return $this->db->prepareExecute($query, [
            $this->service_id,
            $this->status,
            $this->color_code,
            $this->expires_at,
            $this->scanned_count,
            $this->last_scanned_at,
            $this->id
        ]);
    }

    /**
     * Delete QR code.
     * Returns true on success, false on failure.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM qr_codes WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get QR code by code string within company
     */
    public function getByCode($qrCode, $companyId = null)
    {
        $query = "SELECT * FROM qr_codes WHERE qr_code = ?";
        $params = [$qrCode];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
                return true;
            }
        }
        return false;
    }

    /**
     * Increment scan count
     */
    public function incrementScanCount()
    {
        if (!$this->id) {
            return false;
        }

        $this->scanned_count++;
        $this->last_scanned_at = date('Y-m-d H:i:s');

        $query = "UPDATE qr_codes SET 
            scanned_count = scanned_count + 1,
            last_scanned_at = NOW()
            WHERE id = ?";

        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Update color code based on service progress
     */
    public function updateColorCode($progressPercentage)
    {
        if (!$this->id) {
            return false;
        }

        // Determine color based on progress
        if ($progressPercentage < 33) {
            $this->color_code = 'red';
        } elseif ($progressPercentage < 66) {
            $this->color_code = 'yellow';
        } else {
            $this->color_code = 'green';
        }

        $query = "UPDATE qr_codes SET color_code = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->color_code, $this->id]);
    }

    /**
     * Mark QR code as expired
     */
    public function markAsExpired()
    {
        if (!$this->id) {
            return false;
        }

        $this->status = 'expired';
        $query = "UPDATE qr_codes SET status = 'expired' WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Mark QR code as completed
     */
    public function markAsCompleted()
    {
        if (!$this->id) {
            return false;
        }

        $this->status = 'completed';
        $this->color_code = 'green';
        $query = "UPDATE qr_codes SET status = 'completed', color_code = 'green' WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all QR codes (optionally filtered by company)
     */
    public function all($companyId = null)
    {
        $query = "SELECT 
            qr.*,
            s.job_number,
            s.status as service_status
        FROM qr_codes qr
        LEFT JOIN services s ON qr.service_id = s.id";
        
        $params = [];
        
        if ($companyId) {
            $query .= " WHERE qr.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY qr.generated_at DESC";

        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Check if QR code exists within company
     */
    private function checkQRCodeExists($companyId, $qrCode, $excludeId = null)
    {
        $query = "SELECT id FROM qr_codes WHERE company_id = ? AND qr_code = ?";
        $params = [$companyId, $qrCode];

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get active QR codes for a company
     */
    public function getActive($companyId = null)
    {
        $query = "SELECT * FROM qr_codes WHERE status = 'active'";
        $params = [];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY generated_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get QR codes by service ID
     */
    public function getByServiceId($serviceId, $companyId = null)
    {
        $query = "SELECT * FROM qr_codes WHERE service_id = ?";
        $params = [$serviceId];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
                return true;
            }
        }
        return false;
    }

    /**
     * Count QR codes for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM qr_codes WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }

    /**
     * Generate unique QR code string for a company
     */
    public static function generateUniqueCode($companyId, $prefix = 'QR')
    {
        $db = new Database();
        do {
            $code = $prefix . '-' . strtoupper(bin2hex(random_bytes(6)));
            $stmt = $db->prepareSelect("SELECT id FROM qr_codes WHERE company_id = ? AND qr_code = ?", [$companyId, $code]);
        } while ($stmt && $stmt->rowCount() > 0);
        
        return $code;
    }
}
