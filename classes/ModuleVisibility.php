<?php
class ModuleVisibility {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Get all pages grouped by category with visibility status for a specific company.
     * Used for rendering the Sidebar and the Admin Visibility Settings page.
     * 
     * @param int $companyId
     * @return array [ 'CategoryName' => [ 'pages' => [ ...page info... ], 'icon' => '...' ] ]
     */
    public function getSidebarTree($companyId) {
        $query = "SELECT 
                    p.id, p.page_name, p.page_route, p.page_category, p.icon, p.display_order,
                    COALESCE(sm.is_visible, 1) as is_visible -- Default to visible if not set
                  FROM pages p
                  LEFT JOIN sidebar_modules sm ON p.id = sm.page_id AND sm.company_id = ?
                  WHERE p.is_active = 1
                  ORDER BY 
                    CASE 
                        WHEN p.page_category = 'Dashboard' THEN 1
                        WHEN p.page_category = 'Customer & Vehicle' THEN 2
                        WHEN p.page_category = 'Service Management' THEN 3
                        WHEN p.page_category = 'Sales & Billing' THEN 4
                        WHEN p.page_category = 'Inventory' THEN 5
                        WHEN p.page_category = 'Reports' THEN 6
                        WHEN p.page_category = 'Employees' THEN 7
                        WHEN p.page_category = 'System Admin' THEN 8
                        ELSE 9 
                    END,
                    p.display_order ASC";

        $result = $this->db->prepareSelect($query, [$companyId]);
        $tree = [];

        // predefined category icons
        $categoryIcons = [
            'Dashboard' => 'fas fa-th-large',
            'Customer & Vehicle' => 'fas fa-id-card',
            'Service Management' => 'fas fa-cogs',
            'Sales & Billing' => 'fas fa-file-invoice-dollar',
            'Inventory' => 'fas fa-boxes',
            'Reports' => 'fas fa-chart-line',
            'Employees' => 'fas fa-users',
            'System Admin' => 'fas fa-sliders-h'
        ];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $cat = $row['page_category'] ?? 'Other';
            
            if (!isset($tree[$cat])) {
                $tree[$cat] = [
                    'icon' => $categoryIcons[$cat] ?? 'fas fa-folder',
                    'pages' => []
                ];
            }

            $tree[$cat]['pages'][] = [
                'id' => $row['id'],
                'name' => $row['page_name'],
                'route' => $row['page_route'],
                'icon' => $row['icon'],
                'is_visible' => (bool)$row['is_visible']
            ];
        }

        return $tree;
    }

    /**
     * Toggle visibility of a page for a company.
     */
    public function toggleVisibility($companyId, $pageId, $isVisible) {
        // Check if record exists
        $checkQuery = "SELECT id FROM sidebar_modules WHERE company_id = ? AND page_id = ?";
        $exists = $this->db->prepareSelect($checkQuery, [$companyId, $pageId]);

        if ($exists && $exists->rowCount() > 0) {
            $query = "UPDATE sidebar_modules SET is_visible = ? WHERE company_id = ? AND page_id = ?";
            return $this->db->prepareExecute($query, [$isVisible, $companyId, $pageId]);
        } else {
            $query = "INSERT INTO sidebar_modules (company_id, page_id, is_visible) VALUES (?, ?, ?)";
            return $this->db->prepareExecute($query, [$companyId, $pageId, $isVisible]);
        }
    }
}
