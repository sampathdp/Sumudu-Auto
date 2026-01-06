<?php
class HealthCheckReport
{
    public $id;
    public $company_id;
    public $job_id;
    public $tyre_condition;
    public $brake_condition;
    public $oil_level;
    public $filter_status;
    public $battery_health;
    public $additional_notes;
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
     * Load report by ID
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM health_check_reports WHERE id = ?";
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
     * Load report by job_id (one report per job, optionally filtered by company)
     */
    public function loadByJobId($jobId, $companyId = null)
    {
        $query = "SELECT * FROM health_check_reports WHERE job_id = ?";
        $params = [$jobId];
        
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
     * Create new report
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->job_id)) {
            return false;
        }

        $query = "INSERT INTO health_check_reports (
            company_id, job_id, tyre_condition, brake_condition, oil_level,
            filter_status, battery_health, additional_notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->job_id,
            $this->tyre_condition,
            $this->brake_condition,
            $this->oil_level,
            $this->filter_status,
            $this->battery_health,
            $this->additional_notes ?? null
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Update existing report
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        $query = "UPDATE health_check_reports SET 
            tyre_condition = ?, brake_condition = ?, oil_level = ?,
            filter_status = ?, battery_health = ?, additional_notes = ?
            WHERE id = ?";

        return $this->db->prepareExecute($query, [
            $this->tyre_condition,
            $this->brake_condition,
            $this->oil_level,
            $this->filter_status,
            $this->battery_health,
            $this->additional_notes ?? null,
            $this->id
        ]);
    }

    /**
     * Save (create or update)
     */
    public function save()
    {
        return $this->id ? $this->update() : $this->create();
    }

    /**
     * Delete report
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM health_check_reports WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all reports for a company
     */
    public function all($companyId = null)
    {
        $query = "SELECT hr.*, s.job_number 
                  FROM health_check_reports hr
                  LEFT JOIN services s ON hr.job_id = s.id";
        $params = [];
        
        if ($companyId) {
            $query .= " WHERE hr.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY hr.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get a score (0-100) for a condition
     */
    public function getConditionScore($field)
    {
        if (!isset($this->$field)) {
            return 0;
        }

        $scores = [
            'tyre_condition' => [
                'excellent' => 100,
                'good' => 75,
                'fair' => 50,
                'poor' => 25,
                'bad' => 10
            ],
            'brake_condition' => [
                'excellent' => 100,
                'good' => 75,
                'fair' => 40,
                'poor' => 15
            ],
            'battery_health' => [
                'excellent' => 100,
                'good' => 75,
                'fair' => 40,
                'poor' => 15
            ],
            'oil_level' => [
                'full' => 100,
                'low' => 60,
                'very_low' => 20,
                'overfilled' => 10
            ],
            'filter_status' => [
                'clean' => 100,
                'slightly_dirty' => 70,
                'dirty' => 40,
                'very_dirty' => 10
            ]
        ];

        return $scores[$field][$this->$field] ?? 50;
    }

    /**
     * Get Bootstrap color class based on score
     */
    public function getConditionColor($field)
    {
        $score = $this->getConditionScore($field);
        
        if ($score >= 80) return 'success';
        if ($score >= 50) return 'info';
        if ($score >= 30) return 'warning';
        return 'danger';
    }

    /**
     * Count reports for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM health_check_reports WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>