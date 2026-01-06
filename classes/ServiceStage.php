<?php
class ServiceStage
{
    public $id;
    public $company_id;
    public $stage_name;
    public $stage_order;
    public $icon;
    public $estimated_duration;
    public $created_at;

    // Fixed Stage Names
    const STAGE_REGISTRATION = 'Registration';
    const STAGE_PENDING = 'Pending';
    const STAGE_READY = 'Ready for Delivery';
    const STAGE_DELIVERED = 'Delivered';
    const STAGE_CANCELLED = 'Cancelled';

    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load stage by ID using prepared statement.
     */
    private function loadById($id)
    {
        $query = "SELECT id, company_id, stage_name, stage_order, icon, estimated_duration, created_at
                  FROM service_stages WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);

        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->id                = $row['id'];
                $this->company_id        = $row['company_id'];
                $this->stage_name        = $row['stage_name'];
                $this->stage_order       = (int)$row['stage_order'];
                $this->icon             = $row['icon'];
                $this->estimated_duration = (int)$row['estimated_duration'];
                $this->created_at        = $row['created_at'];
            }
        }
    }

    /**
     * Create a new service stage.
     * Returns true on success, false on failure.
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->stage_name) || $this->stage_order < 0) {
            return false;
        }

        // Prevent duplicate stage names within company
        if ($this->checkStageNameExists($this->company_id, $this->stage_name)) {
            return false;
        }

        $query = "INSERT INTO service_stages 
                  (company_id, stage_name, stage_order, icon, estimated_duration) 
                  VALUES (?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->stage_name,
            $this->stage_order,
            $this->icon ?: null,
            $this->estimated_duration > 0 ? $this->estimated_duration : null
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
            $this->reorderStages($this->company_id);
        }

        return $success;
    }

    /**
     * Update existing stage.
     * Returns true on success, false on failure.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check if new name conflicts within company (excluding current record)
        if ($this->checkStageNameExists($this->company_id, $this->stage_name, $this->id)) {
            return false;
        }

        $query = "UPDATE service_stages 
                  SET stage_name = ?, stage_order = ?, icon = ?, estimated_duration = ? 
                  WHERE id = ?";

        $success = $this->db->prepareExecute($query, [
            $this->stage_name,
            $this->stage_order,
            $this->icon ?: null,
            $this->estimated_duration > 0 ? $this->estimated_duration : null,
            $this->id
        ]);

        if ($success) {
            $this->reorderStages($this->company_id);
        }

        return $success;
    }

    /**
     * Delete stage.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }

        $query = "DELETE FROM service_stages WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all stages ordered by stage_order (optionally filtered by company)
     */
    public function all($companyId = null)
    {
        $query = "SELECT id, company_id, stage_name, stage_order, icon, estimated_duration, created_at 
                  FROM service_stages";
        $params = [];
        
        if ($companyId) {
            $query .= " WHERE company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY stage_order ASC, id ASC";

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Check if stage_name already exists within company (exclude current ID if updating)
     */
    private function checkStageNameExists($companyId, $stage_name, $excludeId = null)
    {
        $query = "SELECT id FROM service_stages WHERE company_id = ? AND stage_name = ?";
        $params = [$companyId, $stage_name];

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get next stage order for a company
     */
    public static function getNextOrder($companyId = null)
    {
        $db = new Database();
        $query = "SELECT COALESCE(MAX(stage_order), 0) + 1 FROM service_stages";
        $params = [];
        
        if ($companyId) {
            $query .= " WHERE company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row ? (int)$row[0] : 1;
        }
        return 1;
    }

    /**
     * Count stages for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM service_stages WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }

    /**
     * Get stage statistics for a company.
     */
    public static function getStats($companyId)
    {
        $db = new Database();
        $stats = [];
        
        // Total Stages
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM service_stages WHERE company_id = ?", [$companyId]);
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        return $stats;
    }

    /**
     * Initialize default stages for a company
     */
    public function initializeDefaults($companyId)
    {
        // Check if stages already exist
        if (self::countByCompany($companyId) > 0) {
            return true;
        }
        
        $defaults = [
            ['name' => self::STAGE_REGISTRATION, 'order' => 1, 'icon' => 'fa-edit'],
            ['name' => self::STAGE_PENDING, 'order' => 2, 'icon' => 'fa-clock'],
            // Middle stages would go here
            ['name' => self::STAGE_READY, 'order' => 98, 'icon' => 'fa-check-circle'],
            ['name' => self::STAGE_DELIVERED, 'order' => 99, 'icon' => 'fa-truck'],
            ['name' => self::STAGE_CANCELLED, 'order' => 100, 'icon' => 'fa-times-circle']
        ];
        
        foreach ($defaults as $stage) {
            $query = "INSERT INTO service_stages (company_id, stage_name, stage_order, icon) VALUES (?, ?, ?, ?)";
            $this->db->prepareExecute($query, [$companyId, $stage['name'], $stage['order'], $stage['icon']]);
        }
        
        return true;
    }

    /**
     * Enforce strict stage ordering:
     * 1. Registration
     * 2. Pending
     * ... User defined stages ...
     * N-2. Ready for Delivery
     * N-1. Delivered
     * N. Cancelled
     */
    public function reorderStages($companyId)
    {
        // Get all stages
        $stages = $this->all($companyId);
        
        $fixedStart = [self::STAGE_REGISTRATION, self::STAGE_PENDING];
        $fixedEnd = [self::STAGE_READY, self::STAGE_DELIVERED, self::STAGE_CANCELLED];
        
        $startStages = [];
        $middleStages = [];
        $endStages = []; // Keyed by name for easy sorting
        
        // Categorize stages
        foreach ($stages as $stage) {
            $name = $stage['stage_name'];
            if (in_array($name, $fixedStart)) {
                $startStages[] = $stage;
            } elseif (in_array($name, $fixedEnd)) {
                $endStages[$name] = $stage;
            } else {
                $middleStages[] = $stage;
            }
        }
        
        // Sort fixed start stages
        usort($startStages, function($a, $b) use ($fixedStart) {
            return array_search($a['stage_name'], $fixedStart) - array_search($b['stage_name'], $fixedStart);
        });
        
        // Sort middle stages by current order to preserve user intent
        usort($middleStages, function($a, $b) {
            return $a['stage_order'] - $b['stage_order'];
        });

        // Reconstruct full list in correct order
        $orderedStages = array_merge($startStages, $middleStages);
        
        // Append fixed end stages in correct order
        foreach ($fixedEnd as $name) {
            if (isset($endStages[$name])) {
                $orderedStages[] = $endStages[$name];
            }
        }
        
        // Update database with new orders
        $order = 1;
        $db = new Database(); // Use new connection for transaction safety if needed
        foreach ($orderedStages as $stage) {
            $update = "UPDATE service_stages SET stage_order = ? WHERE id = ?";
            $db->prepareExecute($update, [$order++, $stage['id']]);
        }
        
        return true;
    }
}
?>