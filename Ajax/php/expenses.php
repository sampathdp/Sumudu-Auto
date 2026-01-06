<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

$sessionCompanyId = $_SESSION['company_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_categories':
            $category = new ExpenseCategory();
            $categories = $category->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $categories];
            break;

        case 'create':
            $expense = new Expense();
            $expense->company_id = $sessionCompanyId;
            $expense->category_id = (int)($_POST['category_id'] ?? 0);
            $expense->expense_date = trim($_POST['expense_date'] ?? date('Y-m-d'));
            $expense->amount = (float)($_POST['amount'] ?? 0);
            $expense->reference_number = trim($_POST['reference_number'] ?? '');
            $expense->description = trim($_POST['description'] ?? '');
            $expense->paid_to = trim($_POST['paid_to'] ?? '');
            $expense->payment_method = trim($_POST['payment_method'] ?? 'cash');
            $expense->account_id = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null; // Added
            $expense->created_by = $_SESSION['id'] ?? 0;
            $expense->status = 'pending'; // Default to pending
            
            // Handle file upload if any here (omitted for brevity, can check $_FILES['attachment'])
            
            if ($expense->create()) {
                $response = ['status' => 'success', 'message' => 'Expense record added successfully'];
            } else {
                $response['message'] = 'Failed to add expense record';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid expense ID';
                break;
            }
            
            $expense = new Expense($id);
            if (!$expense->id || $expense->company_id != $sessionCompanyId) {
                $response['message'] = 'Expense not found';
                break;
            }
            
            $expense->category_id = (int)($_POST['category_id'] ?? $expense->category_id);
            $expense->expense_date = trim($_POST['expense_date'] ?? $expense->expense_date);
            $expense->amount = (float)($_POST['amount'] ?? $expense->amount);
            $expense->reference_number = trim($_POST['reference_number'] ?? $expense->reference_number);
            $expense->description = trim($_POST['description'] ?? $expense->description);
            $expense->paid_to = trim($_POST['paid_to'] ?? $expense->paid_to);
            $expense->payment_method = trim($_POST['payment_method'] ?? $expense->payment_method);
            $expense->account_id = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : $expense->account_id; // Added
            
            if ($expense->update()) {
                $response = ['status' => 'success', 'message' => 'Expense updated successfully'];
            } else {
                $response['message'] = 'Failed to update expense';
            }
            break;
            
        case 'approve':
            $id = (int)($_POST['id'] ?? 0);
            $expense = new Expense($id);
            if ($expense->id && $expense->company_id == $sessionCompanyId) {
                if ($expense->approve($_SESSION['id'])) {
                    $response = ['status' => 'success', 'message' => 'Expense approved'];
                } else {
                    $response['message'] = 'Failed to approve expense';
                }
            } else {
                $response['message'] = 'Expense not found';
            }
            break;

        case 'reject':
            $id = (int)($_POST['id'] ?? 0);
            $expense = new Expense($id);
            if ($expense->id && $expense->company_id == $sessionCompanyId) {
                if ($expense->reject($_SESSION['id'])) {
                    $response = ['status' => 'success', 'message' => 'Expense rejected'];
                } else {
                    $response['message'] = 'Failed to reject expense';
                }
            } else {
                $response['message'] = 'Expense not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $expense = new Expense($id);
            if ($expense->id && $expense->company_id == $sessionCompanyId) {
                if ($expense->delete()) {
                    $response = ['status' => 'success', 'message' => 'Expense deleted successfully'];
                } else {
                    $response['message'] = 'Failed to delete expense';
                }
            } else {
                $response['message'] = 'Expense not found';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $expense = new Expense($id);
            if ($expense->id && $expense->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $expense->id,
                        'category_id' => $expense->category_id,
                        'expense_date' => $expense->expense_date,
                        'amount' => $expense->amount,
                        'reference_number' => $expense->reference_number,
                        'description' => $expense->description,
                        'paid_to' => $expense->paid_to,
                        'payment_method' => $expense->payment_method,
                        'account_id' => $expense->account_id, // Added
                        'status' => $expense->status
                    ]
                ];
            } else {
                $response['message'] = 'Expense not found';
            }
            break;

        case 'list':
            $expense = new Expense();
            $data = $expense->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $data];
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (Exception $e) {
    error_log("Expenses AJAX Error: " . $e->getMessage());
    $response['message'] = 'System error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
