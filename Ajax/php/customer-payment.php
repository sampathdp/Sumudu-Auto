<?php
/**
 * Customer Payment AJAX Handler
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$sessionCompanyId = $_SESSION['company_id'] ?? 1;
$sessionBranchId = $_SESSION['branch_id'] ?? null;
$sessionUserId = $_SESSION['id'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        // ============================================
        // Get outstanding invoices for a customer
        // ============================================
        case 'get_outstanding_invoices':
            $customerId = (int)($_GET['customer_id'] ?? 0);
            if (!$customerId) {
                echo json_encode(['status' => 'error', 'message' => 'Customer ID required']);
                exit;
            }
            $invoices = CustomerPayment::getOutstandingInvoices($sessionCompanyId, $customerId);
            echo json_encode(['status' => 'success', 'data' => $invoices]);
            break;

        // ============================================
        // Create new payment
        // ============================================
        case 'create':
            $customerId = (int)($_POST['customer_id'] ?? 0);
            $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
            $notes = trim($_POST['notes'] ?? '');
            $methods = json_decode($_POST['methods'] ?? '[]', true);
            $allocations = json_decode($_POST['allocations'] ?? '[]', true);

            if (!$customerId) {
                echo json_encode(['status' => 'error', 'message' => 'Customer is required']);
                exit;
            }
            if (empty($methods)) {
                echo json_encode(['status' => 'error', 'message' => 'At least one payment method is required']);
                exit;
            }

            $payment = new CustomerPayment();
            $paymentData = [
                'company_id' => $sessionCompanyId,
                'branch_id' => $sessionBranchId,
                'payment_date' => $paymentDate,
                'customer_id' => $customerId,
                'notes' => $notes,
                'status' => 'draft',
                'created_by' => $sessionUserId
            ];

            $paymentId = $payment->create($paymentData, $methods, $allocations);

            if ($paymentId) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Payment received successfully',
                    'data' => [
                        'id' => $paymentId,
                        'payment_number' => $payment->payment_number
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create payment']);
            }
            break;

        // ============================================
        // Confirm payment
        // ============================================
        case 'confirm':
            $paymentId = (int)($_POST['id'] ?? 0);
            if (!$paymentId) {
                echo json_encode(['status' => 'error', 'message' => 'Payment ID required']);
                exit;
            }

            $payment = new CustomerPayment($paymentId);
            if (!$payment->id || $payment->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
                exit;
            }

            if ($payment->confirm($sessionUserId)) {
                echo json_encode(['status' => 'success', 'message' => 'Payment confirmed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to confirm payment']);
            }
            break;

        // ============================================
        // Cancel payment
        // ============================================
        case 'cancel':
            $paymentId = (int)($_POST['id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            
            if (!$paymentId) {
                echo json_encode(['status' => 'error', 'message' => 'Payment ID required']);
                exit;
            }

            $payment = new CustomerPayment($paymentId);
            if (!$payment->id || $payment->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
                exit;
            }

            if ($payment->cancel($reason)) {
                echo json_encode(['status' => 'success', 'message' => 'Payment cancelled successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to cancel payment. Only draft payments can be cancelled.']);
            }
            break;

        // ============================================
        // Get single payment details
        // ============================================
        case 'get':
            $paymentId = (int)($_GET['id'] ?? 0);
            if (!$paymentId) {
                echo json_encode(['status' => 'error', 'message' => 'Payment ID required']);
                exit;
            }

            $payment = new CustomerPayment($paymentId);
            if (!$payment->id || $payment->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
                exit;
            }

            $methods = $payment->getMethods($paymentId);
            $allocations = $payment->getAllocations($paymentId);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => $payment->payment_date,
                    'customer_id' => $payment->customer_id,
                    'customer_name' => $payment->customer_name,
                    'total_amount' => $payment->total_amount,
                    'allocated_amount' => $payment->allocated_amount,
                    'unallocated_amount' => $payment->unallocated_amount,
                    'status' => $payment->status,
                    'notes' => $payment->notes,
                    'created_at' => $payment->created_at,
                    'methods' => $methods,
                    'allocations' => $allocations
                ]
            ]);
            break;

        // ============================================
        // List all payments
        // ============================================
        case 'list':
            $status = $_GET['status'] ?? null;
            $payments = CustomerPayment::all($sessionCompanyId, $status);
            echo json_encode(['status' => 'success', 'data' => $payments]);
            break;

        // ============================================
        // Get customer balance
        // ============================================
        case 'get_customer_balance':
            $customerId = (int)($_GET['customer_id'] ?? 0);
            if (!$customerId) {
                echo json_encode(['status' => 'error', 'message' => 'Customer ID required']);
                exit;
            }
            $balance = CustomerPayment::getCustomerBalance($sessionCompanyId, $customerId);
            echo json_encode(['status' => 'success', 'data' => ['balance' => $balance]]);
            break;

        // ============================================
        // Get customer statement
        // ============================================
        case 'get_customer_statement':
            $customerId = (int)($_GET['customer_id'] ?? 0);
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            if (!$customerId) {
                echo json_encode(['status' => 'error', 'message' => 'Customer ID required']);
                exit;
            }
            
            $statement = CustomerPayment::getCustomerStatement($sessionCompanyId, $customerId, $startDate, $endDate);
            $balance = CustomerPayment::getCustomerBalance($sessionCompanyId, $customerId);
            
            echo json_encode(['status' => 'success', 'data' => [
                'statement' => $statement,
                'current_balance' => $balance
            ]]);
            break;

        // ============================================
        // Get payments by customer
        // ============================================
        case 'get_by_customer':
            $customerId = (int)($_GET['customer_id'] ?? 0);
            if (!$customerId) {
                echo json_encode(['status' => 'error', 'message' => 'Customer ID required']);
                exit;
            }
            $payments = CustomerPayment::getByCustomer($sessionCompanyId, $customerId);
            echo json_encode(['status' => 'success', 'data' => $payments]);
            break;

        // ============================================
        // Get stats
        // ============================================
        case 'get_stats':
            $stats = CustomerPayment::getStats($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $stats]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Customer Payment AJAX Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
