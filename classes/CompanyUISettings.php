<?php
class CompanyUISettings
{
    private $db;
    private static $cache = [];

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Check if a UI component is visible for a company
     * @param int $companyId
     * @param string $componentKey
     * @param int|null $pageId
     * @return bool
     */
    public static function isVisible($companyId, $componentKey, $pageId = null)
    {
        // Simple caching
        $cacheKey = "{$companyId}_{$componentKey}_" . ($pageId ?? 'global');
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $db = new Database();
        
        // Resolve Component Key to ID first (could cache this mapping too)
        // We do a JOIN to skip a separate query if possible, or just look it up.
        // Let's do a JOIN in the main query.
        
        // If component doesn't exist in DB, what to do? Default true or false?
        // Default to TRUE for backward compatibility / safety, but logging it would be good.
        
        $params = [$companyId, $componentKey];
        $sql = "SELECT s.is_visible 
                FROM company_ui_settings s
                JOIN ui_components c ON s.ui_component_id = c.id
                WHERE s.company_id = ? AND c.component_key = ?";
        
        if ($pageId) {
            $sql .= " AND (s.page_id = ? OR s.page_id IS NULL) ORDER BY s.page_id DESC LIMIT 1";
            $params[] = $pageId;
        } else {
            $sql .= " AND s.page_id IS NULL LIMIT 1";
        }

        $stmt = $db->prepareSelect($sql, $params);
        $result = $stmt ? $stmt->fetch() : false;

        // Logic:
        // 1. If rule exists -> return is_visible
        // 2. If NO rule exists:
        //    a. Check if component exists? 
        //    b. Default to TRUE (Visible)
        
        if ($result) {
            $isVisible = (bool)$result['is_visible'];
        } else {
            $isVisible = true; 
        }

        self::$cache[$cacheKey] = $isVisible;
        return $isVisible;
    }

    /**
     * Set visibility rule
     * Uses component Key to look up ID, then saves.
     */
    public function setVisibility($companyId, $componentKey, $isVisible, $pageId = null, $description = null)
    {
        $adminId = $_SESSION['id'] ?? 1;
        
        // Get Component ID
        $compStmt = $this->db->prepareSelect("SELECT id FROM ui_components WHERE component_key = ?", [$componentKey]);
        $comp = $compStmt ? $compStmt->fetch() : false;
        
        if (!$comp) {
            // Component doesn't exist in DB yet.
            // Option 1: Create it automatically?
            // Option 2: Error.
            // Let's create it automatically for transition safety, or Error.
            // Plan said we migrated data. So it should exist.
            // Returning false for now.
            return false;
        }
        $componentId = $comp['id'];

        // Check if rule exists
        $checkParams = [$companyId, $componentId];
        $checkSql = "SELECT id FROM company_ui_settings WHERE company_id = ? AND ui_component_id = ?";
        if ($pageId) {
            $checkSql .= " AND page_id = ?";
            $checkParams[] = $pageId;
        } else {
            $checkSql .= " AND page_id IS NULL";
        }
        
        $stmt = $this->db->prepareSelect($checkSql, $checkParams);
        $exists = $stmt ? $stmt->fetch() : false;

        if ($exists) {
            // Update
            $sql = "UPDATE company_ui_settings SET is_visible = ?, updated_by = ? WHERE id = ?";
            return $this->db->prepareExecute($sql, [(int)$isVisible, $adminId, $exists['id']]);
        } else {
            // Insert
            $sql = "INSERT INTO company_ui_settings (company_id, page_id, ui_component_id, description, is_visible, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            return $this->db->prepareExecute($sql, [
                $companyId, 
                $pageId, 
                $componentId, 
                $description, 
                (int)$isVisible, 
                $adminId
            ]);
        }
    }

    /**
     * Get all active settings for a company merged with all available components
     */
    public function getAllForCompany($companyId)
    {
        // Fetch all components
        $compSql = "SELECT * FROM ui_components WHERE is_active = 1 ORDER BY category, name";
        $compStmt = $this->db->prepareSelect($compSql, []);
        $components = $compStmt ? $compStmt->fetchAll() : [];

        // Fetch existing rules for this company
        $ruleSql = "SELECT ui_component_id, is_visible FROM company_ui_settings WHERE company_id = ? AND page_id IS NULL";
        $ruleStmt = $this->db->prepareSelect($ruleSql, [$companyId]);
        $rules = $ruleStmt ? $ruleStmt->fetchAll() : [];
        
        // Map rules
        $ruleMap = [];
        foreach ($rules as $r) {
            $ruleMap[$r['ui_component_id']] = (bool)$r['is_visible'];
        }

        // Merge
        $result = [];
        foreach ($components as $c) {
            $c['is_visible'] = isset($ruleMap[$c['id']]) ? $ruleMap[$c['id']] : true; // Default true
            $result[] = $c;
        }
        
        return $result;
    }
}
?>
