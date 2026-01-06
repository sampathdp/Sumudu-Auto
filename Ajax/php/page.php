<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

$classesPath = dirname(dirname(__DIR__)) . '/classes/Includes.php';
if (!file_exists($classesPath)) {
    die(json_encode(['status' => 'error', 'message' => 'Classes not found']));
}
require_once $classesPath;

// CSRF Protection for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $page = new Page();

    // List all pages
    if ($action === 'list' || $action === '') {
        echo json_encode(['status' => 'success', 'data' => $page->all()]);
        exit;
    }

    // Get parents for dropdown
    if ($action === 'list_parents') {
        echo json_encode(['status' => 'success', 'data' => $page->getAllForSelect()]);
        exit;
    }

    // Get single page
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }
        $page = new Page($id);
        if ($page->id) {
            echo json_encode(['status' => 'success', 'data' => $page->getData()]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Page not found']);
        }
        exit;
    }

    // Create
    if ($action === 'create') {
        $page->page_name     = trim($_POST['page_name'] ?? '');
        $page->page_route    = trim($_POST['page_route'] ?? '');
        $page->page_category = trim($_POST['page_category'] ?? '');
        $page->description   = trim($_POST['description'] ?? '');
        $page->icon          = trim($_POST['icon'] ?? '');
        $page->display_order = (int)($_POST['display_order'] ?? 0);
        $page->is_active     = !empty($_POST['is_active']) ? 1 : 0;
        $page->parent_page_id = !empty($_POST['parent_page_id']) ? (int)$_POST['parent_page_id'] : null;

        if ($page->create()) {
            echo json_encode(['status' => 'success', 'message' => 'Page created successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create page. Route may already exist or invalid parent.']);
        }
        exit;
    }

    // Update
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid page ID']);
            exit;
        }

        $page = new Page($id);
        $page->page_name     = trim($_POST['page_name'] ?? $page->page_name);
        $page->page_route    = trim($_POST['page_route'] ?? $page->page_route);
        $page->page_category = trim($_POST['page_category'] ?? $page->page_category);
        $page->description   = trim($_POST['description'] ?? $page->description);
        $page->icon          = trim($_POST['icon'] ?? $page->icon);
        $page->display_order = (int)($_POST['display_order'] ?? $page->display_order);
        $page->is_active     = !empty($_POST['is_active']) ? 1 : 0;
        $page->parent_page_id = !empty($_POST['parent_page_id']) ? (int)$_POST['parent_page_id'] : null;

        if ($page->update()) {
            echo json_encode(['status' => 'success', 'message' => 'Page updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update. Route exists or self-parenting.']);
        }
        exit;
    }

    // Delete
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }
        $page = new Page($id);
        if ($page->delete()) {
            echo json_encode(['status' => 'success', 'message' => 'Page deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete page with child pages']);
        }
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
