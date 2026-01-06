<?php
/**
 * Supplier Payment AJAX Handler
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
        // Get outstanding GRNs for a supplier
        // ============================================
        case 'get_outstanding_grns':
            $supplierId = (int)($_GET['supplier_id'] ?? 0);
            if (!$supplierId) {
                echo json_encode(['status' => 'error', 'message' => 'Supplier ID required']);
                exit;
            }
            $grns = SupplierPayment::getOutstandingGRNs($sessionCompanyId, $supplierId);
            echo json_encode(['status' => 'success', 'data' => $grns]);
            break;

        // ============================================
        // Create new payment
        // ============================================
        case 'create':
            $supplierId = (int)($_POST['supplier_id'] ?? 0);
            $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
            $notes = trim($_POST['notes'] ?? '');
            $methods = json_decode($_POST['methods'] ?? '[]', true);
            $allocations = json_decode($_POST['allocations'] ?? '[]', true);

            if (!$supplierId) {
                echo json_encode(['status' => 'error', 'message' => 'Supplier is required']);
                exit;
            }
            if (empty($methods)) {
                echo json_encode(['status' => 'error', 'message' => 'At least one payment method is required']);
                exit;
            }

            $payment = new SupplierPayment();
            $paymentData = [
                'company_id' => $sessionCompanyId,
                'branch_id' => $sessionBranchId,
                'payment_date' => $paymentDate,
                'supplier_id' => $supplierId,
                'notes' => $notes,
                'status' => 'draft',
                'created_by' => $sessionUserId
            ];

            $paymentId = $payment->create($paymentData, $methods, $allocations);

            if ($paymentId) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Payment created successfully',
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

            $payment = new SupplierPayment($paymentId);
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

            $payment = new SupplierPayment($paymentId);
            if (!$payment->id || $payment->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
                exit;
            }

            if ($payment->cancel($reason)) {
                echo json_encode(['status' => 'success', 'message' => 'Payment cancelled successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to cancel payment']);
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

            $payment = new SupplierPayment($paymentId);
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
                    'supplier_id' => $payment->supplier_id,
                    'supplier_name' => $payment->supplier_name,
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
            $payments = SupplierPayment::all($sessionCompanyId, $status);
            echo json_encode(['status' => 'success', 'data' => $payments]);
            break;

        // ============================================
        // Get supplier balance
        // ============================================
        case 'get_supplier_balance':
            $supplierId = (int)($_GET['supplier_id'] ?? 0);
            if (!$supplierId) {
                echo json_encode(['status' => 'error', 'message' => 'Supplier ID required']);
                exit;
            }
            $balance = SupplierPayment::getSupplierBalance($sessionCompanyId, $supplierId);
            echo json_encode(['status' => 'success', 'data' => ['balance' => $balance]]);
            break;

        // ============================================
        // Get supplier statement
        // ============================================
        case 'get_supplier_statement':
            $supplierId = (int)($_GET['supplier_id'] ?? 0);
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            if (!$supplierId) {
                echo json_encode(['status' => 'error', 'message' => 'Supplier ID required']);
                exit;
            }
            
            $statement = SupplierPayment::getSupplierStatement($sessionCompanyId, $supplierId, $startDate, $endDate);
            $balance = SupplierPayment::getSupplierBalance($sessionCompanyId, $supplierId);
            
            echo json_encode(['status' => 'success', 'data' => [
                'statement' => $statement,
                'current_balance' => $balance
            ]]);
            break;

        // ============================================
        // Get payments by supplier
        // ============================================
        case 'get_by_supplier':
            $supplierId = (int)($_GET['supplier_id'] ?? 0);
            if (!$supplierId) {
                echo json_encode(['status' => 'error', 'message' => 'Supplier ID required']);
                exit;
            }
            $payments = SupplierPayment::getBySupplier($sessionCompanyId, $supplierId);
            echo json_encode(['status' => 'success', 'data' => $payments]);
            break;

        // ============================================
        // Get stats
        // ============================================
        case 'get_stats':
            $stats = SupplierPayment::getStats($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $stats]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Supplier Payment AJAX Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
