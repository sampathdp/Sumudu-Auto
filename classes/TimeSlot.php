<?php
class TimeSlot
{
    public $id;
    public $company_id;
    public $slot_start;
    public $slot_end;
    public $max_bookings;
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
     * Load time slot by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT id, company_id, slot_start, slot_end, max_bookings, is_active, created_at
                  FROM time_slots WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id = $row['id'];
                $this->company_id = $row['company_id'];
                $this->slot_start = $row['slot_start'];
                $this->slot_end = $row['slot_end'];
                $this->max_bookings = $row['max_bookings'];
                $this->is_active = $row['is_active'];
                $this->created_at = $row['created_at'];
            }
        }
    }

    /**
     * Create a new time slot.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->slot_start) || empty($this->slot_end)) {
            return false;
        }

        // Check for conflicts within company
        if ($this->checkConflict($this->company_id, $this->slot_start, $this->slot_end)) {
            return false;
        }

        $query = "INSERT INTO time_slots (company_id, slot_start, slot_end, max_bookings, is_active) VALUES (?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->slot_start,
            $this->slot_end,
            $this->max_bookings ?? 3,
            $this->is_active ?? 1
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update existing time slot.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check for conflicts within company (excluding current)
        if ($this->checkConflict($this->company_id, $this->slot_start, $this->slot_end, $this->id)) {
            return false;
        }

        $query = "UPDATE time_slots SET slot_start = ?, slot_end = ?, max_bookings = ?, is_active = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->slot_start,
            $this->slot_end,
            $this->max_bookings,
            $this->is_active,
            $this->id
        ]);
    }

    /**
     * Delete time slot.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM time_slots WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all time slots (optionally filtered by company).
     */
    public function all($companyId = null)
    {
        $query = "SELECT id, company_id, slot_start, slot_end, max_bookings, is_active, created_at 
                  FROM time_slots";
        $params = [];
        
        if ($companyId) {
            $query .= " WHERE company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY slot_start ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get active time slots for a company.
     */
    public function active($companyId = null)
    {
        $query = "SELECT id, slot_start, slot_end, max_bookings 
                  FROM time_slots WHERE is_active = 1";
        $params = [];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY slot_start ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Check for conflicting time slots within company.
     * Returns true if conflict exists.
     */
    private function checkConflict($companyId, $start, $end, $excludeId = null)
    {
        // Check for exact duplicate start/end within company
        $query = "SELECT id FROM time_slots WHERE company_id = ? AND slot_start = ? AND slot_end = ?";
        $params = [$companyId, $start, $end];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Count time slots for a company.
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM time_slots WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>
