<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get session company_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_earnings_summary':
            $date = $_GET['date'] ?? date('Y-m-d');
            
            $employee = new Employee();
            $allEmployees = $employee->all($sessionCompanyId, null, true); // Active only, filtered by company
            
            $earnings = [];
            foreach ($allEmployees as $emp) {
                $empObj = new Employee($emp['id']);
                
                // Security: verify employee belongs to company
                if ($empObj->company_id != $sessionCompanyId) {
                    continue;
                }
                
                $dayEarnings = $empObj->calculateEarnings($date, $sessionCompanyId);
                $pendingBalance = $empObj->getPendingBalance($sessionCompanyId);
                $paymentRecord = $empObj->getPaymentRecord($date, $sessionCompanyId);
                
                // Determine payment status
                $isPaid = $paymentRecord && $paymentRecord['status'] === 'paid';
                
                $earnings[] = [
                    'id' => $emp['id'],
                    'employee_code' => $emp['employee_code'],
                    'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                    'position' => $emp['position'],
                    'branch_name' => $emp['branch_name'] ?? null,
                    'salary_type' => $emp['salary_type'] ?? 'monthly',
                    'salary' => floatval($emp['salary']),
                    'jobs_count' => $dayEarnings['jobs_count'],
                    'base_amount' => $dayEarnings['base_amount'],
                    'commission_amount' => $dayEarnings['commission_amount'],
                    'today_earnings' => $dayEarnings['today_earnings'],
                    'pending_balance' => $pendingBalance,
                    'total_payable' => $dayEarnings['today_earnings'] + $pendingBalance,
                    'is_paid' => $isPaid,
                    'paid_at' => $isPaid ? $paymentRecord['paid_at'] : null
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $earnings, 'date' => $date]);
            break;

        case 'get_employee_jobs':
            $employeeId = (int)($_GET['employee_id'] ?? 0);
            $date = $_GET['date'] ?? date('Y-m-d');
            
            if (!$employeeId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee ID required']);
                exit;
            }
            
            $employee = new Employee($employeeId);
            
            // Security: verify employee belongs to company
            if (!$employee->id || $employee->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
                exit;
            }
            
            $earnings = $employee->calculateEarnings($date, $sessionCompanyId);
            $pendingBalance = $employee->getPendingBalance($sessionCompanyId);
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->getFullName(),
                        'salary_type' => $employee->salary_type,
                        'salary' => floatval($employee->salary)
                    ],
                    'jobs' => $earnings['jobs'],
                    'earnings' => [
                        'base_amount' => $earnings['base_amount'],
                        'commission_amount' => $earnings['commission_amount'],
                        'today_earnings' => $earnings['today_earnings'],
                        'pending_balance' => $pendingBalance,
                        'total_payable' => $earnings['today_earnings'] + $pendingBalance
                    ]
                ]
            ]);
            break;

        case 'process_payment':
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $date = $_POST['date'] ?? date('Y-m-d');
            
            if (!$employeeId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee ID required']);
                exit;
            }
            
            $employee = new Employee($employeeId);
            
            // Security: verify employee belongs to company
            if (!$employee->id || $employee->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
                exit;
            }
            
            // First, save current day's earnings if not exists
            $earnings = $employee->calculateEarnings($date, $sessionCompanyId);
            $pendingBalance = $employee->getPendingBalance($sessionCompanyId);
            
            // Save today's record if there are earnings
            if ($earnings['today_earnings'] > 0 || $pendingBalance > 0) {
                $employee->savePaymentRecord($date, $earnings, $sessionCompanyId, $pendingBalance, 'unpaid');
            }
            
            // Now mark all unpaid as paid
            if ($employee->processPayment($date, $_SESSION['id'], $sessionCompanyId)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Payment processed successfully',
                    'data' => [
                        'total_paid' => $earnings['today_earnings'] + $pendingBalance
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to process payment']);
            }
            break;

        case 'get_payment_history':
            $employeeId = (int)($_GET['employee_id'] ?? 0);
            
            if (!$employeeId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee ID required']);
                exit;
            }
            
            $employee = new Employee($employeeId);
            
            // Security: verify employee belongs to company
            if (!$employee->id || $employee->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
                exit;
            }
            
            $history = $employee->getPaymentHistory($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $history]);
            break;

        case 'save_daily_earnings':
            // Called at end of day to save earnings for all employees
            $date = $_POST['date'] ?? date('Y-m-d');
            
            $employee = new Employee();
            $allEmployees = $employee->all($sessionCompanyId, null, true);
            
            $saved = 0;
            foreach ($allEmployees as $emp) {
                $empObj = new Employee($emp['id']);
                
                // Security: verify employee belongs to company
                if ($empObj->company_id != $sessionCompanyId) {
                    continue;
                }
                
                $earnings = $empObj->calculateEarnings($date, $sessionCompanyId);
                
                if ($earnings['today_earnings'] > 0) {
                    $pendingBalance = $empObj->getPendingBalance($sessionCompanyId);
                    $empObj->savePaymentRecord($date, $earnings, $sessionCompanyId, $pendingBalance, 'unpaid');
                    $saved++;
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => "Saved earnings for $saved employees"
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Employee Earnings AJAX Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}
?>
