<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

// Only allow admin users (role_id = 1)
if (!isset($_SESSION['id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $sidebarModule = new SidebarModule();

    switch ($action) {
        case 'get_companies':
            // Get all companies for dropdown
            $db = new Database();
            $query = "SELECT id, name FROM companies ORDER BY name";
            $stmt = $db->prepareSelect($query);
            $companies = $stmt ? $stmt->fetchAll() : [];
            
            $response = [
                'status' => 'success',
                'data' => $companies
            ];
            break;

        case 'list':
            // Get company_id from request or default to session
            $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : ($_SESSION['company_id'] ?? 1);
            
            // Get all modules grouped by parent for the specified company
            $parents = $sidebarModule->getParents($companyId);
            $modules = [];
            
            foreach ($parents as $parent) {
                $parent['children'] = $sidebarModule->getChildren($parent['module_key'], $companyId);
                $modules[] = $parent;
            }
            
            // If no modules found, try to initialize from company 1
            if (empty($modules) && $companyId != 1) {
                $sidebarModule->initializeForCompany($companyId);
                // Reload after initialization
                $parents = $sidebarModule->getParents($companyId);
                $modules = [];
                foreach ($parents as $parent) {
                    $parent['children'] = $sidebarModule->getChildren($parent['module_key'], $companyId);
                    $modules[] = $parent;
                }
            }
            
            $response = [
                'status' => 'success',
                'data' => $modules
            ];
            break;

        case 'toggle':
            $moduleKey = $_POST['module_key'] ?? '';
            $isVisible = isset($_POST['is_visible']) ? (int)$_POST['is_visible'] : 0;
            $companyId = isset($_POST['company_id']) ? (int)$_POST['company_id'] : ($_SESSION['company_id'] ?? 1);
            
            if (empty($moduleKey)) {
                $response['message'] = 'Module key is required';
                break;
            }
            
            // Update the main module
            $result = $sidebarModule->setVisibility($moduleKey, $isVisible, $companyId);
            
            // Check if this is a parent module (has no parent_key)
            $module = $sidebarModule->getByKey($moduleKey, $companyId);
            $childrenUpdated = 0;
            
            if ($module && empty($module['parent_key'])) {
                // This is a parent module - update all children
                $children = $sidebarModule->getChildren($moduleKey, $companyId);
                foreach ($children as $child) {
                    $sidebarModule->setVisibility($child['module_key'], $isVisible, $companyId);
                    $childrenUpdated++;
                }
            }
            
            if ($result) {
                $response = [
                    'status' => 'success',
                    'message' => 'Module visibility updated',
                    'children_updated' => $childrenUpdated
                ];
            } else {
                $response['message'] = 'Failed to update visibility';
            }
            break;

        case 'get':
            $moduleKey = $_GET['module_key'] ?? '';
            $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : ($_SESSION['company_id'] ?? 1);
            
            if (empty($moduleKey)) {
                $response['message'] = 'Module key is required';
                break;
            }
            
            $module = $sidebarModule->getByKey($moduleKey, $companyId);
            if ($module) {
                $response = [
                    'status' => 'success',
                    'data' => $module
                ];
            } else {
                $response['message'] = 'Module not found';
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (Exception $e) {
    error_log("Sidebar Module AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
