<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

$classesPath = dirname(__DIR__, 2) . '/classes/Includes.php';
if (!file_exists($classesPath)) {
    die(json_encode(['status' => 'error', 'message' => 'Classes not found']));
}
require_once $classesPath;

// CSRF Protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $permission = new Permission();

    switch ($action) {

        case 'list':
        case '':
            echo json_encode(['status' => 'success', 'data' => $permission->all()]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
                exit;
            }
            $permission = new Permission($id);
            if ($permission->id) {
                echo json_encode(['status' => 'success', 'data' => $permission->getData()]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Permission not found']);
            }
            break;

        case 'create':
            $permission->permission_name = trim($_POST['permission_name'] ?? '');
            $permission->permission_code = strtoupper(trim($_POST['permission_code'] ?? ''));
            $permission->description    = trim($_POST['description'] ?? '');

            if ($permission->create()) {
                echo json_encode(['status' => 'success', 'message' => 'Permission created successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create. Permission code already exists.']);
            }
            break;

        case 'update':
            $id = (int)($_POST['permission_id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid permission ID']);
                exit;
            }
            $permission = new Permission($id);
            $permission->permission_name = trim($_POST['permission_name'] ?? $permission->permission_name);
            $permission->permission_code = strtoupper(trim($_POST['permission_code'] ?? $permission->permission_code));
            $permission->description    = trim($_POST['description'] ?? $permission->description);

            if ($permission->update()) {
                echo json_encode(['status' => 'success', 'message' => 'Permission updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update. Permission code already exists.']);
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
                exit;
            }
            $permission = new Permission($id);
            if ($permission->delete()) {
                echo json_encode(['status' => 'success', 'message' => 'Permission deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete permission']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>