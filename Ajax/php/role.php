<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

$classesPath = dirname(dirname(__DIR__)) . '/classes/Includes.php';
if (!file_exists($classesPath)) {
    die(json_encode(['status' => 'error', 'message' => 'Classes not found']));
}
require_once $classesPath;

// CSRF protection for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $role = new Role();

    // List all roles
    if ($action === 'list' || empty($action)) {
        $roles = $role->all();
        echo json_encode(['status' => 'success', 'data' => $roles]);
        exit;
    }

    // Get single role
    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }
        $role = new Role($id);
        if ($role->id) {
            echo json_encode(['status' => 'success', 'data' => $role->getData()]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Role not found']);
        }
        exit;
    }

    // Create role
    if ($action === 'create') {
        $role->role_name = trim($_POST['role_name'] ?? '');
        $role->description = trim($_POST['description'] ?? '');
        $role->is_system_role = !empty($_POST['is_system_role']) ? 1 : 0;

        if ($role->create()) {
            echo json_encode(['status' => 'success', 'message' => 'Role created successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create role. Name may already exist.']);
        }
        exit;
    }

    // Update role
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid role ID']);
            exit;
        }

        $role = new Role($id);
        $role->role_name = trim($_POST['role_name'] ?? $role->role_name);
        $role->description = trim($_POST['description'] ?? $role->description);
        $role->is_system_role = !empty($_POST['is_system_role']) ? 1 : 0;

        if ($role->update()) {
            echo json_encode(['status' => 'success', 'message' => 'Role updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update role. Name may already exist.']);
        }
        exit;
    }

    // Delete role
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid role ID']);
            exit;
        }
        $role = new Role($id);
        if ($role->delete()) {
            echo json_encode(['status' => 'success', 'message' => 'Role deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete system role or role not found']);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>