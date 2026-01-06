<?php
/**
 * Expense AJAX Handler
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';
require_once '../../classes/Expense.php';
require_once '../../classes/UserPermission.php';
require_once '../../classes/AuditLog.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$sessionCompanyId = $_SESSION['company_id'] ?? 1;
$sessionBranchId = $_SESSION['branch_id'] ?? null;
$sessionUserId = $_SESSION['id'];

// Define Page Route for Permissions (Expenses usually under views/Expenses/ or similar)
// UserPermission::getCurrentPageRoute() logic usually works for Views, here we manual check if needed
// For now, we rely on specific permission checks like 'Create', 'Approve'
// Let's assume 'views/Expenses/' is the route.
$pageRoute = 'views/Expenses/'; 

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ===================================
        // List Expenses
        // ===================================
        case 'list':
            $expense = new Expense();
            $expenses = $expense->all($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $expenses]);
            break;

        // ===================================
        // Create Expense
        // ===================================
        case 'create':
            if (!UserPermission::hasPermission($sessionUserId, 'Create', $pageRoute, $sessionCompanyId)) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }

            $expense = new Expense();
            $expense->company_id = $sessionCompanyId;
            $expense->branch_id = $sessionBranchId;
            $expense->category_id = $_POST['category_id'] ?? 0;
            $expense->account_id = !empty($_POST['account_id']) ? $_POST['account_id'] : null;
            $expense->expense_date = $_POST['expense_date'] ?? date('Y-m-d');
            $expense->amount = $_POST['amount'] ?? 0;
            $expense->reference_number = $_POST['reference_number'] ?? '';
            $expense->description = $_POST['description'] ?? '';
            $expense->paid_to = $_POST['paid_to'] ?? '';
            $expense->payment_method = $_POST['payment_method'] ?? 'cash';
            $expense->created_by = $sessionUserId;
            
            // Auto-approve if user has 'Approve' permission? Or Config setting?
            // For now, default to pending unless explicitly handled.
            $expense->status = 'pending';

            if ($expense->create()) {
                echo json_encode(['status' => 'success', 'message' => 'Expense created successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create expense']);
            }
            break;

        // ===================================
        // Update Expense
        // ===================================
        case 'update':
            // Check Edit permission
            if (!UserPermission::hasPermission($sessionUserId, 'Edit', $pageRoute, $sessionCompanyId)) {
                 echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                 exit;
            }

            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
                exit;
            }

            $expense = new Expense($id);
            if (!$expense->id || $expense->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Expense not found']);
                exit;
            }
            
            // Don't allow editing if approved/rejected?
            if ($expense->status !== 'pending') {
                echo json_encode(['status' => 'error', 'message' => 'Cannot edit processed expense']);
                exit;
            }

            $expense->category_id = $_POST['category_id'] ?? $expense->category_id;
            $expense->account_id = !empty($_POST['account_id']) ? $_POST['account_id'] : null;
            $expense->expense_date = $_POST['expense_date'] ?? $expense->expense_date;
            $expense->amount = $_POST['amount'] ?? $expense->amount;
            $expense->reference_number = $_POST['reference_number'] ?? $expense->reference_number;
            $expense->description = $_POST['description'] ?? $expense->description;
            $expense->paid_to = $_POST['paid_to'] ?? $expense->paid_to;
            $expense->payment_method = $_POST['payment_method'] ?? $expense->payment_method;

            if ($expense->update()) {
                echo json_encode(['status' => 'success', 'message' => 'Expense updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update expense']);
            }
            break;

        // ===================================
        // Approve Expense
        // ===================================
        case 'approve':
            if (!UserPermission::hasPermission($sessionUserId, 'Approve', $pageRoute, $sessionCompanyId)) {
                 echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                 exit;
            }

            $id = $_POST['id'] ?? 0;
            $expense = new Expense($id);
            
            if (!$expense->id || $expense->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Expense not found']);
                exit;
            }
            
            if ($expense->status !== 'pending') {
                echo json_encode(['status' => 'error', 'message' => 'Expense already processed']);
                exit;
            }

            if ($expense->approve($sessionUserId)) {
                echo json_encode(['status' => 'success', 'message' => 'Expense approved']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to approve expense']);
            }
            break;

        // ===================================
        // Reject Expense
        // ===================================
        case 'reject':
            if (!UserPermission::hasPermission($sessionUserId, 'Approve', $pageRoute, $sessionCompanyId)) { // Usually same permission as approve
                 echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                 exit;
            }

            $id = $_POST['id'] ?? 0;
            $expense = new Expense($id);
            
            if (!$expense->id || $expense->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Expense not found']);
                exit;
            }

            if ($expense->reject($sessionUserId)) {
                echo json_encode(['status' => 'success', 'message' => 'Expense rejected']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to reject expense']);
            }
            break;

        // ===================================
        // Delete Expense
        // ===================================
        case 'delete':
            if (!UserPermission::hasPermission($sessionUserId, 'Delete', $pageRoute, $sessionCompanyId)) {
                 echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                 exit;
            }

            $id = $_POST['id'] ?? 0;
            $expense = new Expense($id);
            
            if (!$expense->id || $expense->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Expense not found']);
                exit;
            }
            
            if ($expense->status == 'approved') {
                 // Deleting approved expense might complex (reverse transaction?). Block for now.
                 echo json_encode(['status' => 'error', 'message' => 'Cannot delete approved expense']);
                 exit;
            }

            if ($expense->delete()) {
                echo json_encode(['status' => 'success', 'message' => 'Expense deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete expense']);
            }
            break;
            
        // ===================================
        // Get Stats
        // ===================================
        case 'get_stats':
            $stats = Expense::getStats($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $stats]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log("Expense AJAX Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
