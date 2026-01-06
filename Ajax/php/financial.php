<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../classes/Includes.php';
require_once __DIR__ . '/../../classes/FinancialAccount.php';
require_once __DIR__ . '/../../classes/UserPermission.php';

// Auth Check
if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$sessionCompanyId = $_SESSION['company_id'] ?? 1;
// FinancialAccount is a Manager class, initialized with Company ID
$finance = new FinancialAccount($sessionCompanyId);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Define the page route for permission checks
// This maps to the Finance module in the URL structure
$pageRoute = 'views/Finance/'; 

try {
    switch ($action) {
        
        // ===================================
        // ACCOUNT MANAGEMENT
        // ===================================
        
        case 'get_accounts':
            // View permission required
            if (!UserPermission::hasPermission($_SESSION['id'], 'View', $pageRoute, $sessionCompanyId)) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }
            
            $type = $_GET['type'] ?? null;
            $accounts = $finance->getAccounts($type, false);
            echo json_encode(['status' => 'success', 'data' => $accounts]);
            break;

        case 'save_account':
            // Create for new, Edit for existing
            $permission = !empty($_POST['id']) ? 'Edit' : 'Create';
            
            if (!UserPermission::hasPermission($_SESSION['id'], $permission, $pageRoute, $sessionCompanyId)) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }

            $data = $_POST;
            
            // Basic validation
            if(empty($data['account_name']) || empty($data['account_type'])) {
                echo json_encode(['status' => 'error', 'message' => 'Account Name and Type are required']);
                exit;
            }

            if (!empty($data['id'])) {
                if($finance->updateAccount($data['id'], $data)) {
                    echo json_encode(['status' => 'success', 'message' => 'Account updated successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update account']);
                }
            } else {
                if($finance->createAccount($data)) {
                    echo json_encode(['status' => 'success', 'message' => 'Account created successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create account']);
                }
            }
            break;

        case 'get_account':
            if (!UserPermission::hasPermission($_SESSION['id'], 'View', $pageRoute, $sessionCompanyId)) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }
            
            $id = $_GET['id'] ?? 0;
            $account = $finance->getAccountById($id);
            if ($account) {
                echo json_encode(['status' => 'success', 'data' => $account]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Account not found']);
            }
            break;

        case 'get_stats':
            if (!UserPermission::hasPermission($_SESSION['id'], 'View', $pageRoute, $sessionCompanyId)) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }
            $stats = FinancialAccount::getStats($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $stats]);
            break;

        // ===================================
        // TRANSACTIONS / CASHBOOK
        // ===================================

        case 'get_ledger':
            if (!UserPermission::hasPermission($_SESSION['id'], 'View', $pageRoute, $sessionCompanyId)) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }
            $filters = [
                'account_id' => $_GET['account_id'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date'   => $_GET['end_date'] ?? null
            ];
            
            $transactions = $finance->getLedger($filters);
            echo json_encode(['status' => 'success', 'data' => $transactions]);
            break;

        case 'add_transaction':
            if (!UserPermission::hasPermission($_SESSION['id'], 'Create', $pageRoute, $sessionCompanyId)) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }
            
            $accountId = $_POST['account_id'] ?? 0;
            $type = $_POST['type'] ?? ''; 
            $amount = (float)($_POST['amount'] ?? 0);
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? 'General';
            $date = $_POST['date'] ?? date('Y-m-d');
            
            if (!$accountId || !$type || $amount <= 0 || !$description) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid transaction details']);
                exit;
            }

            $meta = [
                'date' => $date,
                'category_type' => $category,
                'reference_type' => 'manual',
                'created_by' => $_SESSION['id']
            ];

            $result = $finance->recordTransaction($accountId, $type, $amount, $description, $meta);
            
            if ($result === true) {
                echo json_encode(['status' => 'success', 'message' => 'Transaction recorded']);
            } else {
                echo json_encode(['status' => 'error', 'message' => is_array($result) ? $result['error'] : 'Unknown error']);
            }
            break;

        case 'transfer':
            if (!UserPermission::hasPermission($_SESSION['id'], 'Create', $pageRoute, $sessionCompanyId)) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }
            
            $fromId = $_POST['from_account_id'] ?? 0;
            $toId = $_POST['to_account_id'] ?? 0;
            $amount = (float)($_POST['amount'] ?? 0);
            $description = $_POST['description'] ?? '';
            $date = $_POST['date'] ?? date('Y-m-d');

            if ($fromId == $toId) {
                echo json_encode(['status' => 'error', 'message' => 'Cannot transfer to same account']);
                exit;
            }
            
            if (!$fromId || !$toId || $amount <= 0) {
                 echo json_encode(['status' => 'error', 'message' => 'Invalid transfer details']);
                 exit;
            }
            
            $result = $finance->transferFunds($fromId, $toId, $amount, $description, $date, $_SESSION['id']);
            
            if ($result === true) {
                echo json_encode(['status' => 'success', 'message' => 'Transfer successful']);
            } else {
                echo json_encode(['status' => 'error', 'message' => is_array($result) ? $result['error'] : 'Transfer failed']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log("Financial AJAX Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}
