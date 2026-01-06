<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

// Get session company_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $category = new InventoryCategory();
            $category->company_id = $sessionCompanyId;
            $category->category_name      = trim($_POST['category_name'] ?? '');
            $category->description        = trim($_POST['description'] ?? '') ?: null;
            $category->parent_category_id = !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null;
            $category->is_active          = (int)($_POST['is_active'] ?? 1);

            if ($category->create()) {
                $response = ['status' => 'success', 'message' => 'Category created successfully'];
            } else {
                $response['message'] = 'Failed to create category. Category name may already exist.';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid category ID';
                break;
            }
            $category = new InventoryCategory($id);
            
            // Security: ensure category belongs to same company
            if (!$category->id || $category->company_id != $sessionCompanyId) {
                $response['message'] = 'Category not found';
                break;
            }

            $category->category_name      = trim($_POST['category_name'] ?? $category->category_name);
            $category->description        = trim($_POST['description'] ?? '') ?: null;
            $category->parent_category_id = !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null;
            $category->is_active          = (int)($_POST['is_active'] ?? $category->is_active);

            if ($category->update()) {
                $response = ['status' => 'success', 'message' => 'Category updated successfully'];
            } else {
                $response['message'] = 'Failed to update category. Category name may already exist.';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid category ID';
                break;
            }
            $category = new InventoryCategory($id);
            
            // Security: ensure category belongs to same company
            if ($category->id && $category->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                        'id'                 => $category->id,
                        'company_id'         => $category->company_id,
                        'category_name'      => $category->category_name,
                        'description'        => $category->description ?? '',
                        'parent_category_id' => $category->parent_category_id ?? '',
                        'is_active'          => $category->is_active
                    ]
                ];
            } else {
                $response['message'] = 'Category not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid category ID';
                break;
            }
            $category = new InventoryCategory($id);
            
            // Security: ensure category belongs to same company
            if (!$category->id || $category->company_id != $sessionCompanyId) {
                $response['message'] = 'Category not found';
                break;
            }
            
            if ($category->delete()) {
                $response = ['status' => 'success', 'message' => 'Category deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete category.';
            }
            break;

        case 'list':
        default:
            $category = new InventoryCategory();
            $categories = $category->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $categories];
            break;
            
        case 'get_active':
            $category = new InventoryCategory();
            $categories = $category->getActiveCategories($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $categories];
            break;
    }
} catch (Exception $e) {
    error_log("InventoryCategory AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
