<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized'];

if (!isset($_SESSION['id']) || $_SESSION['role_id'] != 1) { // Admin only
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';
$mv = new ModuleVisibility();

try {
    switch ($action) {
        case 'get_companies':
            $db = new Database();
            $stmt = $db->prepareSelect("SELECT id, name as company_name FROM companies ORDER BY name");
            $companies = $stmt ? $stmt->fetchAll() : [];
            $response = ['status' => 'success', 'data' => $companies];
            break;

        case 'get_tree':
            $companyId = $_SESSION['company_id'] ?? 1; // Default to own company, or allow passing ID for super admin
            if (isset($_POST['company_id'])) $companyId = (int)$_POST['company_id'];
            
            $tree = $mv->getSidebarTree($companyId);
            $response = ['status' => 'success', 'data' => $tree];
            break;

        case 'toggle':
            $companyId = (int)$_POST['company_id'];
            $pageId = (int)$_POST['page_id'];
            $isVisible = (int)$_POST['is_visible'];
            
            if ($mv->toggleVisibility($companyId, $pageId, $isVisible)) {
                $response = ['status' => 'success', 'message' => 'Visibility updated'];
            } else {
                $response['message'] = 'Failed to update visibility';
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (Exception $e) {
    error_log("ModuleVisibility Error: " . $e->getMessage());
    $response['message'] = 'System error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
