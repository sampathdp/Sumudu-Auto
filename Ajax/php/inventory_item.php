<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

// Get session company_id and branch_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;
$sessionBranchId = $_SESSION['branch_id'] ?? null;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $item = new InventoryItem();
            $item->company_id = $sessionCompanyId;
            // Branch: Use posted branch_id if provided, else session branch, else NULL (company-wide)
            // If user specifically wants company-wide (branch_id=0 or empty), they should send empty. 
            // If user is logged in as branch user, they might be forced to create for their branch.
            // For now, let's allow optional branch_id from POST, defaulting to session branch.
            $postedBranchId = $_POST['branch_id'] ?? '';
            $item->branch_id = ($postedBranchId !== '') ? (int)$postedBranchId : $sessionBranchId;
            
            $item->item_code        = trim($_POST['item_code'] ?? '');
            $item->item_name        = trim($_POST['item_name'] ?? '');
            $item->description      = trim($_POST['description'] ?? '') ?: null;
            $item->category_id      = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $item->unit_of_measure  = trim($_POST['unit_of_measure'] ?? '');
            $item->reorder_level    = (float)($_POST['reorder_level'] ?? 0);
            $item->max_stock_level  = !empty($_POST['max_stock_level']) ? (float)$_POST['max_stock_level'] : null;
            $item->unit_cost        = (float)($_POST['unit_cost'] ?? 0);
            $item->unit_price       = (float)($_POST['unit_price'] ?? 0);
            $item->is_active        = (int)($_POST['is_active'] ?? 1);

            if ($item->create()) {
                $response = ['status' => 'success', 'message' => 'Item created successfully'];
            } else {
                $response['message'] = 'Failed to create item. Item code may already exist.';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid item ID';
                break;
            }
            $item = new InventoryItem($id);
            
            // Security: ensure item belongs to same company
            if (!$item->id || $item->company_id != $sessionCompanyId) {
                $response['message'] = 'Item not found';
                break;
            }

            $postedBranchId = $_POST['branch_id'] ?? '';
            // Only update branch_id if explicitly provided, otherwise keep existing
            if ($postedBranchId !== '') {
                $item->branch_id = (int)$postedBranchId;
            }
            
            $item->item_code        = trim($_POST['item_code'] ?? $item->item_code);
            $item->item_name        = trim($_POST['item_name'] ?? $item->item_name);
            $item->description      = trim($_POST['description'] ?? '') ?: null;
            $item->category_id      = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $item->unit_of_measure  = trim($_POST['unit_of_measure'] ?? $item->unit_of_measure);
            $item->reorder_level    = (float)($_POST['reorder_level'] ?? $item->reorder_level);
            $item->max_stock_level  = !empty($_POST['max_stock_level']) ? (float)$_POST['max_stock_level'] : null;
            $item->unit_cost        = (float)($_POST['unit_cost'] ?? $item->unit_cost);
            $item->unit_price       = (float)($_POST['unit_price'] ?? $item->unit_price);
            $item->is_active        = (int)($_POST['is_active'] ?? $item->is_active);

            if ($item->update()) {
                $response = ['status' => 'success', 'message' => 'Item updated successfully'];
            } else {
                $response['message'] = 'Failed to update item. Item code may already exist.';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid item ID';
                break;
            }
            $item = new InventoryItem($id);
            
            // Security: ensure item belongs to same company
            if ($item->id && $item->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                        'id'               => $item->id,
                        'company_id'       => $item->company_id,
                        'branch_id'        => $item->branch_id,
                        'item_code'        => $item->item_code,
                        'item_name'        => $item->item_name,
                        'description'      => $item->description ?? '',
                        'category_id'      => $item->category_id ?? '',
                        'unit_of_measure'  => $item->unit_of_measure,
                        'current_stock'    => $item->current_stock,
                        'reorder_level'    => $item->reorder_level,
                        'max_stock_level'  => $item->max_stock_level ?? '',
                        'unit_cost'        => $item->unit_cost,
                        'unit_price'       => $item->unit_price,
                        'is_active'        => $item->is_active
                    ]
                ];
            } else {
                $response['message'] = 'Item not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid item ID';
                break;
            }
            $item = new InventoryItem($id);
            
            // Security: ensure item belongs to same company
            if (!$item->id || $item->company_id != $sessionCompanyId) {
                $response['message'] = 'Item not found';
                break;
            }
            
            if ($item->delete()) {
                $response = ['status' => 'success', 'message' => 'Item deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete item.';
            }
            break;

        case 'categories':
            $category = new InventoryCategory();
            $categories = $category->getActiveCategories($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $categories];
            break;

        case 'search':
            $term = $_GET['term'] ?? '';
            $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $sessionBranchId;
            $item = new InventoryItem();
            $results = $item->search($term, $sessionCompanyId, $branchId);
            $response = ['status' => 'success', 'data' => $results];
            break;

        case 'list':
        default:
            $item = new InventoryItem();
            $filterBranchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $sessionBranchId;
            // Make branch filtering optional if user wants to see all (e.g. admin) - but for now default to session branch if set
            
            $items = $item->all($sessionCompanyId, $filterBranchId);
            $response = ['status' => 'success', 'data' => $items];
            break;
            
        case 'get_branches':
            $branch = new Branch();
            $branches = $branch->getActiveByCompany($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $branches];
            break;
    }
} catch (Exception $e) {
    error_log("InventoryItem AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
