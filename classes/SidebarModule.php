<?php
/**
 * SidebarModule Class
 * Manages sidebar module visibility settings per company
 * Uses page_id to reference the pages table
 */
class SidebarModule
{
    private static $visiblePageIds = null;
    private static $cachedCompanyId = null;
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get all modules for a company (joined with pages table)
     */
    public function getAll($companyId = null)
    {
        if (!$companyId) {
            $companyId = $_SESSION['company_id'] ?? 1;
        }
        
        $query = "SELECT sm.*, p.page_name, p.page_route, p.page_category, p.icon, p.display_order 
                  FROM sidebar_modules sm 
                  JOIN pages p ON sm.page_id = p.id 
                  WHERE sm.company_id = ? 
                  ORDER BY p.display_order, sm.id";
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get visible page IDs for company (cached)
     */
    public static function getVisiblePageIds($companyId = null)
    {
        if (!$companyId) {
            $companyId = $_SESSION['company_id'] ?? 1;
        }
        
        // Invalidate cache if company changed
        if (self::$cachedCompanyId !== $companyId) {
            self::$visiblePageIds = null;
            self::$cachedCompanyId = $companyId;
        }
        
        if (self::$visiblePageIds === null) {
            $db = new Database();
            $query = "SELECT page_id FROM sidebar_modules WHERE company_id = ? AND is_visible = 1";
            $stmt = $db->prepareSelect($query, [$companyId]);
            $results = $stmt ? $stmt->fetchAll() : [];
            self::$visiblePageIds = array_column($results, 'page_id');
        }
        return self::$visiblePageIds;
    }

    /**
     * Get visible page routes for company
     */
    public static function getVisibleRoutes($companyId = null)
    {
        if (!$companyId) {
            $companyId = $_SESSION['company_id'] ?? 1;
        }
        
        $db = new Database();
        $query = "SELECT p.page_route 
                  FROM sidebar_modules sm 
                  JOIN pages p ON sm.page_id = p.id 
                  WHERE sm.company_id = ? AND sm.is_visible = 1";
        $stmt = $db->prepareSelect($query, [$companyId]);
        $results = $stmt ? $stmt->fetchAll() : [];
        return array_column($results, 'page_route');
    }

    /**
     * Check if a page is visible for company by page_id
     */
    public static function isPageVisible($pageId, $companyId = null)
    {
        $visibleIds = self::getVisiblePageIds($companyId);
        return in_array($pageId, $visibleIds);
    }
    
    /**
     * Check if a route is visible for company
     */
    public static function isRouteVisible($route, $companyId = null)
    {
        $visibleRoutes = self::getVisibleRoutes($companyId);
        foreach ($visibleRoutes as $visibleRoute) {
            if (strpos($route, $visibleRoute) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set module visibility for company by page_id
     */
    public function setVisibility($pageId, $isVisible, $companyId = null)
    {
        if (!$companyId) {
            $companyId = $_SESSION['company_id'] ?? 1;
        }
        
        // Check if record exists
        $check = $this->db->prepareSelect(
            "SELECT id FROM sidebar_modules WHERE company_id = ? AND page_id = ?",
            [$companyId, $pageId]
        );
        
        if ($check && $check->fetch()) {
            // Update existing
            $query = "UPDATE sidebar_modules SET is_visible = ?, updated_by = ?, updated_at = NOW() WHERE page_id = ? AND company_id = ?";
            $result = $this->db->prepareExecute($query, [$isVisible ? 1 : 0, $_SESSION['id'] ?? null, $pageId, $companyId]);
        } else {
            // Insert new
            $query = "INSERT INTO sidebar_modules (company_id, page_id, is_visible, updated_by) VALUES (?, ?, ?, ?)";
            $result = $this->db->prepareExecute($query, [$companyId, $pageId, $isVisible ? 1 : 0, $_SESSION['id'] ?? null]);
        }
        
        // Clear cache
        self::$visiblePageIds = null;
        
        return $result;
    }

    /**
     * Get module by page_id for company
     */
    public function getByPageId($pageId, $companyId = null)
    {
        if (!$companyId) {
            $companyId = $_SESSION['company_id'] ?? 1;
        }
        
        $query = "SELECT sm.*, p.page_name, p.page_route, p.icon 
                  FROM sidebar_modules sm 
                  JOIN pages p ON sm.page_id = p.id 
                  WHERE sm.page_id = ? AND sm.company_id = ?";
        $stmt = $this->db->prepareSelect($query, [$pageId, $companyId]);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Get modules grouped by category
     */
    public function getByCategory($companyId = null)
    {
        if (!$companyId) {
            $companyId = $_SESSION['company_id'] ?? 1;
        }
        
        $query = "SELECT sm.*, p.page_name, p.page_route, p.page_category, p.icon, p.display_order 
                  FROM sidebar_modules sm 
                  JOIN pages p ON sm.page_id = p.id 
                  WHERE sm.company_id = ? 
                  ORDER BY p.page_category, p.display_order";
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        $results = $stmt ? $stmt->fetchAll() : [];
        
        // Group by category
        $grouped = [];
        foreach ($results as $row) {
            $category = $row['page_category'] ?? 'Other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $row;
        }
        
        return $grouped;
    }

    /**
     * Check if category has any visible items
     */
    public static function categoryHasVisibleItems($category, $companyId = null)
    {
        if (!$companyId) {
            $companyId = $_SESSION['company_id'] ?? 1;
        }
        
        $db = new Database();
        $query = "SELECT COUNT(*) as count 
                  FROM sidebar_modules sm 
                  JOIN pages p ON sm.page_id = p.id 
                  WHERE sm.company_id = ? AND p.page_category = ? AND sm.is_visible = 1";
        $stmt = $db->prepareSelect($query, [$companyId, $category]);
        $result = $stmt ? $stmt->fetch() : null;
        return $result && $result['count'] > 0;
    }
    
    /**
     * Initialize default modules for a new company (from pages table)
     */
    public function initializeForCompany($companyId)
    {
        // Check if company already has modules
        $check = $this->db->prepareSelect(
            "SELECT COUNT(*) as count FROM sidebar_modules WHERE company_id = ?",
            [$companyId]
        );
        $row = $check ? $check->fetch() : null;
        
        if ($row && $row['count'] > 0) {
            return true; // Already initialized
        }
        
        // Get all active pages
        $query = "SELECT id FROM pages WHERE is_active = 1";
        $stmt = $this->db->prepareSelect($query);
        $pages = $stmt ? $stmt->fetchAll() : [];
        
        // Insert for new company (all visible by default)
        foreach ($pages as $page) {
            $insertQuery = "INSERT INTO sidebar_modules (company_id, page_id, is_visible) VALUES (?, ?, 1)";
            $this->db->prepareExecute($insertQuery, [$companyId, $page['id']]);
        }
        
        return true;
    }
    
    /**
     * Sync modules with pages table (add missing, don't remove existing)
     */
    public function syncWithPages($companyId = null)
    {
        if (!$companyId) {
            $companyId = $_SESSION['company_id'] ?? 1;
        }
        
        // Get pages not yet in sidebar_modules for this company
        $query = "SELECT p.id 
                  FROM pages p 
                  LEFT JOIN sidebar_modules sm ON p.id = sm.page_id AND sm.company_id = ?
                  WHERE p.is_active = 1 AND sm.id IS NULL";
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        $missingPages = $stmt ? $stmt->fetchAll() : [];
        
        foreach ($missingPages as $page) {
            $insertQuery = "INSERT INTO sidebar_modules (company_id, page_id, is_visible) VALUES (?, ?, 1)";
            $this->db->prepareExecute($insertQuery, [$companyId, $page['id']]);
        }
        
        return count($missingPages);
    }
}
?>
