<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

// Get session company_id (movements are strictly within a company)
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // Logic to manually create a movement (e.g., adjustment/damage)
            // GRN and Sales usage often come from other modules, but this endpoint allows direct adjustments.
            
            $movement = new StockMovement();
            $movement->company_id = $sessionCompanyId;
            $movement->item_id = (int)($_POST['item_id'] ?? 0);
            
            // Security: Verify item belongs to this company
            $item = new InventoryItem($movement->item_id);
            if (!$item->id || $item->company_id != $sessionCompanyId) {
                $response['message'] = 'Item not found or access denied';
                break;
            }

            $movement->movement_type = $_POST['movement_type'] ?? ''; // usage, adjustment, return, damage
            $movement->quantity_change = (float)($_POST['quantity_change'] ?? 0);
            $movement->unit_cost = !empty($_POST['unit_cost']) ? (float)$_POST['unit_cost'] : null;
            $movement->notes = trim($_POST['notes'] ?? '') ?: null;
            $employee = new Employee();
            $currentEmployee = $employee->getByUserId($_SESSION['id']);
            $movement->created_by_employee_id = $currentEmployee ? $currentEmployee->id : null;
            $movement->reference_type = $_POST['reference_type'] ?? 'manual_adjustment';
            $movement->reference_id = !empty($_POST['reference_id']) ? (int)$_POST['reference_id'] : null;

            if ($movement->create()) {
                $response = ['status' => 'success', 'message' => 'Stock movement recorded successfully'];
            } else {
                $response['message'] = 'Failed to record stock movement';
            }
            break;

        case 'get_by_item':
            $itemId = (int)($_GET['item_id'] ?? 0);
            if (!$itemId) {
                $response['message'] = 'Item ID required';
                break;
            }
            
            // Security check
            $item = new InventoryItem($itemId);
            if (!$item->id || $item->company_id != $sessionCompanyId) {
                $response['message'] = 'Item not found';
                break;
            }

            $movement = new StockMovement();
            $movements = $movement->getByItem($itemId, $sessionCompanyId);
            $response = ['status' => 'success', 'data' => $movements];
            break;

        case 'list':
            // List recent movements for the company
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $movement = new StockMovement();
            $movements = $movement->getByDateRange($sessionCompanyId, $startDate, $endDate);
            $response = ['status' => 'success', 'data' => $movements];
            break;

        default:
            $response['message'] = 'Unknown action';
            break;
    }
} catch (Exception $e) {
    error_log("StockMovement AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
