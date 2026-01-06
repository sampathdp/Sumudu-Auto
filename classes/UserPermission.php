<?php
class UserPermission
{
    public $id;
    public $company_id;
    public $user_id;
    public $page_id;
    public $permission_id;
    public $is_granted = 1;
    public $created_at;
    public $created_by;
    public $expires_at;

    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    private function loadById($id)
    {
        $query = "SELECT id, company_id, user_id, page_id, permission_id, is_granted, created_at, created_by, expires_at 
                  FROM user_permissions WHERE id = ?";
        $result = $this->db->prepareSelect($query, [$id]);

        if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            $this->is_granted = (bool)$this->is_granted;
        }
    }

    public function create()
    {
        if (empty($this->company_id) || empty($this->user_id) || empty($this->page_id) || empty($this->permission_id)) {
            return false;
        }

        if ($this->checkCombinationExists($this->company_id, $this->user_id, $this->page_id, $this->permission_id)) {
            return false;
        }

        $query = "INSERT INTO user_permissions (company_id, user_id, page_id, permission_id, is_granted, created_by, expires_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->user_id,
            $this->page_id,
            $this->permission_id,
            (int)$this->is_granted,
            $this->created_by ?? ($_SESSION['id'] ?? 1),
            $this->expires_at
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }

        return $success;
    }

    public function update()
    {
        if (!$this->id) return false;

        if ($this->checkCombinationExists($this->company_id, $this->user_id, $this->page_id, $this->permission_id, $this->id)) {
            return false;
        }

        $query = "UPDATE user_permissions 
                  SET user_id = ?, page_id = ?, permission_id = ?, is_granted = ?, expires_at = ?
                  WHERE id = ?";

        return $this->db->prepareExecute($query, [
            $this->user_id,
            $this->page_id,
            $this->permission_id,
            (int)$this->is_granted,
            $this->expires_at,
            $this->id
        ]);
    }

    public function delete()
    {
        if (!$this->id) return false;

        $query = "DELETE FROM user_permissions WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    public function all($companyId = null)
    {
        $query = "SELECT 
                     up.id, up.company_id, up.user_id, u.username AS user_name,
                     up.page_id, p.page_name,
                     up.permission_id, perm.permission_name,
                     up.is_granted, up.created_at, up.expires_at
                  FROM user_permissions up
                  JOIN users u ON up.user_id = u.id
                  JOIN pages p ON up.page_id = p.id
                  JOIN permissions perm ON up.permission_id = perm.id";
        
        $params = [];
        if ($companyId) {
            $query .= " WHERE up.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY up.created_at DESC";

        $result = $this->db->prepareSelect($query, $params);
        $list = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row['is_granted'] = (bool)$row['is_granted'];
            $list[] = $row;
        }

        return $list;
    }

    private function checkCombinationExists($companyId, $user_id, $page_id, $permission_id, $excludeId = null)
    {
        $query = "SELECT id FROM user_permissions 
                  WHERE company_id = ? AND user_id = ? AND page_id = ? AND permission_id = ?";
        $params = [$companyId, $user_id, $page_id, $permission_id];

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->prepareSelect($query, $params);
        return $result && $result->rowCount() > 0;
    }

    public function getAllUsersForSelect($companyId = null)
    {
        $query = "SELECT id, username FROM users WHERE is_active = 1";
        $params = [];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY username";

        $result = $this->db->prepareSelect($query, $params);
        $users = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $users[] = [
                'id' => (int)$row['id'],
                'username' => $row['username']
            ];
        }

        return $users;
    }

    public function getUsersByRole($role_id = null, $companyId = null)
    {
        if (!$role_id) {
            return $this->getAllUsersForSelect($companyId);
        }

        $query = "SELECT id, username 
                  FROM users 
                  WHERE role_id = ? AND is_active = 1";
        $params = [$role_id];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY username";

        $result = $this->db->prepareSelect($query, $params);
        $users = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $users[] = [
                'id' => (int)$row['id'],
                'username' => $row['username']
            ];
        }

        return $users;
    }

    public function getAllPagesForSelect()
    {
        $query = "SELECT id, page_name 
                  FROM pages 
                  WHERE is_active = 1 
                  ORDER BY page_category, display_order, page_name";

        $result = $this->db->prepareSelect($query);
        $pages = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $pages[] = [
                'id' => (int)$row['id'],
                'page_name' => $row['page_name']
            ];
        }

        return $pages;
    }

    public function getAllPermissionsForSelect()
    {
        $query = "SELECT id, permission_name 
                  FROM permissions 
                  WHERE is_active = 1
                  ORDER BY permission_name";

        $result = $this->db->prepareSelect($query);
        $permissions = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = [
                'id' => (int)$row['id'],
                'permission_name' => $row['permission_name']
            ];
        }

        return $permissions;
    }

    public function getUserPermissions($user_id, $companyId = null)
    {
        $query = "SELECT p.id AS page_id, p.page_name, p.page_category,
                         perm.id AS permission_id, perm.permission_name,
                         IFNULL(up.is_granted, 0) AS is_granted
                  FROM pages p
                  CROSS JOIN permissions perm
                  LEFT JOIN user_permissions up 
                       ON up.page_id = p.id 
                      AND up.permission_id = perm.id 
                      AND up.user_id = ?";
        
        $params = [$user_id];
        
        if ($companyId) {
            $query .= " AND up.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " WHERE p.is_active = 1
                  ORDER BY p.page_category, p.display_order, p.page_name, perm.id";

        $result = $this->db->prepareSelect($query, $params);
        $perms = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $perms[] = [
                'page_id' => (int)$row['page_id'],
                'page_name' => $row['page_name'],
                'page_category' => $row['page_category'] ?: 'General',
                'permission_id' => (int)$row['permission_id'],
                'permission_name' => $row['permission_name'],
                'is_granted' => (bool)$row['is_granted']
            ];
        }

        return $perms;
    }

    public function saveUserPermissions($user_id, $permissions, $companyId = null)
    {
        // Delete existing permissions for this user (and company if specified)
        $deleteQuery = "DELETE FROM user_permissions WHERE user_id = ?";
        $deleteParams = [$user_id];
        
        if ($companyId) {
            $deleteQuery .= " AND company_id = ?";
            $deleteParams[] = $companyId;
        }
        
        $this->db->prepareExecute($deleteQuery, $deleteParams);

        if (empty($permissions)) return true;
        
        // Handle JSON string input
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
        }
        
        // Ensure it's an array
        if (!is_array($permissions)) {
            return false;
        }

        $query = "INSERT INTO user_permissions (company_id, user_id, page_id, permission_id, is_granted, created_by) 
                  VALUES (?, ?, ?, ?, 1, ?)";

        $created_by = $_SESSION['id'] ?? 1;
        $insertCompanyId = $companyId ?? ($_SESSION['company_id'] ?? 1);

        foreach ($permissions as $p) {
            $this->db->prepareExecute($query, [
                $insertCompanyId,
                $user_id,
                $p['page_id'],
                $p['permission_id'],
                $created_by
            ]);
        }

        return true;
    }

    public function getAllPagesWithPermissions()
    {
        $query = "SELECT 
                     p.id AS page_id, p.page_name, p.page_category,
                     GROUP_CONCAT(CONCAT(perm.id, ':', perm.permission_name) SEPARATOR '|') AS permissions
                  FROM pages p
                  CROSS JOIN permissions perm
                  WHERE p.is_active = 1
                  GROUP BY p.id, p.page_name, p.page_category
                  ORDER BY p.page_category, p.display_order, p.page_name";

        $result = $this->db->prepareSelect($query);
        $pages = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            $perms = [];
            if ($row['permissions']) {
                foreach (explode('|', $row['permissions']) as $pair) {
                    $parts = explode(':', $pair);
                    $id = $parts[0];
                    $name = $parts[1];
                    $perms[] = ['id' => (int)$id, 'name' => $name];
                }
            }

            $pages[] = [
                'id' => (int)$row['page_id'],
                'name' => $row['page_name'],
                'category' => $row['page_category'] ?: 'General',
                'permissions' => $perms
            ];
        }

        return $pages;
    }

    public function getData()
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'page_id' => $this->page_id,
            'permission_id' => $this->permission_id,
            'is_granted' => $this->is_granted,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at
        ];
    }
    
    public static function hasPermission($userId, $permissionName, $pageRoute = null, $companyId = null)
    {
        $up = new self();
        
        $pageId = null;
        if ($pageRoute) {
            $query = "SELECT id FROM pages WHERE page_route = ? AND is_active = 1 LIMIT 1";
            $result = $up->db->prepareSelect($query, [$pageRoute]);
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $pageId = $row['id'];
            } else {
                return false;
            }
        }
        
        $query = "SELECT id FROM permissions WHERE permission_name = ? LIMIT 1";
        $result = $up->db->prepareSelect($query, [$permissionName]);
        if (!$result || !$row = $result->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }
        $permissionId = $row['id'];
        
        $baseQuery = "SELECT is_granted FROM user_permissions WHERE user_id = ? AND permission_id = ?";
        $params = [$userId, $permissionId];
        
        if ($pageId) {
            $baseQuery .= " AND page_id = ?";
            $params[] = $pageId;
        }
        
        if ($companyId) {
            $baseQuery .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $baseQuery .= " LIMIT 1";
        
        $result = $up->db->prepareSelect($baseQuery, $params);
        if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
            return (bool)$row['is_granted'];
        }
        
        return false;
    }
    
    
    public static function getCurrentPageRoute()
    {
        // Get the current script path
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Extract directory name from path like /VSC/views/Customer/index.php
        $pathParts = explode('/', trim($scriptName, '/'));
        
        // Find "views" in the path and get the next part
        $viewsIndex = array_search('views', $pathParts);
        if ($viewsIndex !== false && isset($pathParts[$viewsIndex + 1])) {
            $directory = $pathParts[$viewsIndex + 1];
            
            // Check for subdirectory (for Reports subdirectories like CustomerSales, etc.)
            $subdirectory = $pathParts[$viewsIndex + 2] ?? null;
            
            // Map directory names to database page routes
            $directoryToRoute = [
                'Dashboard' => 'views/Dashboard/',
                'Service' => 'views/Service/',
                'Customer' => 'views/Customer/',
                'Vehicle' => 'views/Vehicle/',
                'User' => 'views/User/',
                'Role' => 'views/Role/',
                'Permission' => 'views/Permission/',
                'Page' => 'views/Page/',
                'UserPermission' => 'views/UserPermission/',
                'Service Package' => 'views/Service Package/',
                'ServiceStage' => 'views/ServiceStage/',
                'Booking' => 'views/Booking/approve.php',
                'Supplier' => 'views/Supplier/',
                'InventoryCategory' => 'views/InventoryCategory/',
                'InventoryItem' => 'views/InventoryItem/',
                'GRN' => 'views/GRN/',
                'Invoice' => 'views/Invoice/',
                'Reports' => 'views/Reports/',
                'Settings' => 'views/Settings/',
                'Employee' => 'views/Employee/',
                'Quotation' => 'views/Quotation/',
                'Expenses' => 'views/Expenses/',
                'ModuleVisibility' => 'views/ModuleVisibility/',
                'UIVisibility' => 'views/UIVisibility/',
                'Company' => 'views/Company/',
                'Supplier' => 'views/Supplier/',
                'Branch' => 'views/Branch/',
                'SupplierPayment' => 'views/SupplierPayment/',
                'CustomerPayment' => 'views/CustomerPayment/',
                'Finance' => 'views/Finance/'
            ];
            
            // Map for Reports subdirectories - check subdirectory first
            if ($directory === 'Reports' && $subdirectory) {
                // Reports are still a bit tricky as they share a parent page or have their own
                // In the DB I added:
                // 'views/Reports/CustomerSales/', 'views/Reports/ServiceHistory/', 'views/Reports/Stock/', 'views/Reports/EmployeeSalary/'
                
                $reportsSubdirectoryToRoute = [
                    'CustomerSales' => 'views/Reports/CustomerSales/',
                    'EmployeeSalary' => 'views/Reports/EmployeeSalary/',
                    'ServiceHistory' => 'views/Reports/ServiceHistory/',
                    'Stock' => 'views/Reports/Stock/',
                    'CustomerVehicles' => 'views/Reports/CustomerVehicles/'
                ];
                
                if (isset($reportsSubdirectoryToRoute[$subdirectory])) {
                    return $reportsSubdirectoryToRoute[$subdirectory];
                }
            }
            
            // Special case: Check for specific files that have their own routes
            $fileToRoute = [
                'Employee/earnings.php' => 'views/Employee/earnings.php',
                'Service/service_list.php' => 'views/Service/service_list.php',
                'Finance/accounts.php' => 'views/Finance/accounts.php',
                // 'Booking/approve.php' is handled by directory mapping above if index is default, but let's be safe
            ];
            
            // Check specific file match first
            // $pathParts has [..., 'views', 'Service', 'service_list.php']
            // $directory is 'Service'
            $fileName = $pathParts[$viewsIndex + 2] ?? '';
            if ($fileName && isset($fileToRoute["$directory/$fileName"])) {
                return $fileToRoute["$directory/$fileName"];
            }
            
            // Return the mapped route
            if (isset($directoryToRoute[$directory])) {
                return $directoryToRoute[$directory];
            }
        }
        
        // Fallback: try to construct from REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = trim($path, '/');
        
        // Try to extract from URL pattern
        if (preg_match('#views/([^/]+)#', $path, $matches)) {
            $dir = $matches[1];
            // Use mapping if available
            if (isset($directoryToRoute[$dir])) {
                return $directoryToRoute[$dir];
            }
        }
        
        // Last resort fallback
        return '/unknown';
    }
    
    public static function checkPagePermission($userId, $permissionName, $companyId = null)
    {
        $currentRoute = self::getCurrentPageRoute();
        return self::hasPermission($userId, $permissionName, $currentRoute, $companyId);
    }
}
?>
