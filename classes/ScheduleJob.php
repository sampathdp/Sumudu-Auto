<?php
class ScheduleJob
{
    public $id;
    public $qr_code;
    public $qr_link;
    public $whatsapp_number;
    public $status;
    public $scheduled_date_time;
    public $customer_id;
    public $customer_note;
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
     * Load job by ID
     */
    public function loadById($id)
    {
        $query = "SELECT * FROM schedule_jobs WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);

        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->assignData($row);
            }
        }
    }

    /**
     * Assign database row to object properties
     */
    private function assignData(array $row)
    {
        foreach ($row as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Create new schedule job
     */
    public function create()
    {
        if (empty($this->qr_code) || empty($this->qr_link)) {
            return false;
        }

        if ($this->existsQR($this->qr_code)) {
            return false;
        }

        $query = "INSERT INTO schedule_jobs 
            (qr_code, qr_link, whatsapp_number, status, scheduled_date_time, customer_id, customer_note) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->qr_code,
            $this->qr_link,
            $this->whatsapp_number ?? null,
            $this->status ?? 'pending',
            $this->scheduled_date_time,
            $this->customer_id ?? null,
            $this->customer_note ?? null
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }

        return $success;
    }

    /**
     * Update existing job
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        $query = "UPDATE schedule_jobs SET 
            qr_code = ?, 
            qr_link = ?, 
            whatsapp_number = ?, 
            status = ?, 
            scheduled_date_time = ?, 
            customer_id = ?, 
            customer_note = ?
            WHERE id = ?";

        return $this->db->prepareExecute($query, [
            $this->qr_code,
            $this->qr_link,
            $this->whatsapp_number ?? null,
            $this->status ?? 'pending',
            $this->scheduled_date_time,
            $this->customer_id ?? null,
            $this->customer_note ?? null,
            $this->id
        ]);
    }

    /**
     * Delete job
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }

        $query = "DELETE FROM schedule_jobs WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all jobs
     */
    public function all()
    {
        $query = "SELECT * FROM schedule_jobs ORDER BY created_at DESC";
        $stmt = $this->db->prepareSelect($query);

        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Check if QR code already exists
     */
    private function existsQR($qr)
    {
        $query = "SELECT id FROM schedule_jobs WHERE qr_code = ?";
        $stmt = $this->db->prepareSelect($query, [$qr]);

        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Find job by QR code
     */
    public function findByQR($qr)
    {
        $query = "SELECT * FROM schedule_jobs WHERE qr_code = ?";
        $stmt = $this->db->prepareSelect($query, [$qr]);

        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->assignData($row);
                return true;
            }
        }

        return false;
    }

    /**
     * Update only the status
     */
    public function updateStatus($status)
    {
        if (!$this->id || empty($status)) {
            return false;
        }

        $query = "UPDATE schedule_jobs SET status = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$status, $this->id]);
    }

    /**
     * Save (create or update)
     */
    public function save()
    {
        return $this->id ? $this->update() : $this->create();
    }
}
?>