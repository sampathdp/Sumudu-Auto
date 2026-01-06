<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

$classesPath = dirname(__DIR__, 2) . '/classes/Includes.php';
if (!file_exists($classesPath)) die(json_encode(['status' => 'error', 'message' => 'Classes not found']));
require_once $classesPath;

// Get session company_id (default to 1 for backward compatibility)
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

// CSRF for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $up = new UserPermission();

    switch ($action) {
        case 'list':
            echo json_encode(['status' => 'success', 'data' => $up->all($sessionCompanyId)]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $up = new UserPermission($id);
            // Security: check company_id
            if ($up->id && $up->company_id == $sessionCompanyId) {
                echo json_encode(['status' => 'success', 'data' => $up->getData()]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Not found']);
            }
            break;

        case 'create':
        case 'update':
            $up->company_id = $sessionCompanyId;
            $up->user_id = (int)($_POST['user_id'] ?? 0);
            $up->page_id = (int)($_POST['page_id'] ?? 0);
            $up->permission_id = (int)($_POST['permission_id'] ?? 0);
            $up->is_granted = !empty($_POST['is_granted']);
            $up->created_by = $_SESSION['id'];
            $up->expires_at = $_POST['expires_at'] ?? null;

            if ($action === 'update') {
                $up->id = (int)($_POST['id'] ?? 0);
                // Security: verify existing permission belongs to company
                $existing = new UserPermission($up->id);
                if (!$existing->id || $existing->company_id != $sessionCompanyId) {
                    echo json_encode(['status' => 'error', 'message' => 'Permission not found or access denied']);
                    break;
                }
                $success = $up->update();
            } else {
                $success = $up->create();
            }
            echo json_encode($success
                ? ['status' => 'success', 'message' => 'Saved successfully']
                : ['status' => 'error', 'message' => 'Combination already exists']
            );
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $up = new UserPermission($id);
            // Security: check company_id
            if (!$up->id || $up->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Permission not found or access denied']);
                break;
            }
            echo json_encode($up->delete()
                ? ['status' => 'success', 'message' => 'Deleted']
                : ['status' => 'error', 'message' => 'Failed to delete']
            );
            break;

        case 'list_users':
            echo json_encode(['status' => 'success', 'data' => $up->getAllUsersForSelect($sessionCompanyId)]);
            break;

        case 'list_users_by_role':
            $roleId = (int)($_GET['role_id'] ?? $_POST['role_id'] ?? 0);
            echo json_encode(['status' => 'success', 'data' => $up->getUsersByRole($roleId ?: null, $sessionCompanyId)]);
            break;

        case 'list_pages':
            echo json_encode(['status' => 'success', 'data' => $up->getAllPagesForSelect()]);
            break;

        case 'list_permissions':
            echo json_encode(['status' => 'success', 'data' => $up->getAllPermissionsForSelect()]);
            break;

        case 'get_user_permissions':
            $userId = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
            echo json_encode(['status' => 'success', 'data' => $up->getUserPermissions($userId, $sessionCompanyId)]);
            break;

        case 'save_user_permissions':
            $userId = (int)($_POST['user_id'] ?? 0);
            $permissions = $_POST['permissions'] ?? [];
            $result = $up->saveUserPermissions($userId, $permissions, $sessionCompanyId);
            echo json_encode($result
                ? ['status' => 'success', 'message' => 'Permissions saved successfully']
                : ['status' => 'error', 'message' => 'Failed to save permissions']
            );
            break;

        case 'get_pages_with_permissions':
            echo json_encode(['status' => 'success', 'data' => $up->getAllPagesWithPermissions()]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>