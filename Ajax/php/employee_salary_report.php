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
    $db = new Database();
    
    switch ($action) {
        case 'get_employees':
            $query = "SELECT id, employee_code, CONCAT(first_name, ' ', last_name) as name 
                      FROM employees WHERE company_id = ? AND is_active = 1 ORDER BY first_name";
            $stmt = $db->prepareSelect($query, [$sessionCompanyId]);
            $employees = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            echo json_encode(['status' => 'success', 'data' => $employees]);
            break;

        case 'get_report':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $employeeId = $_GET['employee_id'] ?? '';
            $salaryType = $_GET['salary_type'] ?? '';
            $status = $_GET['status'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            // Build WHERE clause - always filter by company_id
            $where = "WHERE ep.company_id = ? AND ep.payment_date BETWEEN ? AND ?";
            $params = [$sessionCompanyId, $startDate, $endDate];

            if ($employeeId) {
                $where .= " AND ep.employee_id = ?";
                $params[] = $employeeId;
            }

            if ($salaryType) {
                $where .= " AND ep.salary_type = ?";
                $params[] = $salaryType;
            }

            if ($status) {
                $where .= " AND ep.status = ?";
                $params[] = $status;
            }

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM employee_payments ep $where";
            $countStmt = $db->prepareSelect($countQuery, $params);
            $total = $countStmt ? $countStmt->fetch(PDO::FETCH_ASSOC)['total'] : 0;

            // Get summary
            $summaryQuery = "SELECT 
                COUNT(DISTINCT ep.employee_id) as total_employees,
                SUM(ep.total_amount) as total_earnings,
                SUM(CASE WHEN ep.status = 'paid' THEN ep.total_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN ep.status = 'unpaid' THEN ep.total_amount ELSE 0 END) as total_pending
                FROM employee_payments ep $where";
            $summaryStmt = $db->prepareSelect($summaryQuery, $params);
            $summary = $summaryStmt ? $summaryStmt->fetch(PDO::FETCH_ASSOC) : [];

            // Get records
            $query = "SELECT ep.*, 
                      CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                      e.employee_code
                      FROM employee_payments ep
                      LEFT JOIN employees e ON ep.employee_id = e.id
                      $where
                      ORDER BY ep.payment_date DESC, e.first_name
                      LIMIT $perPage OFFSET $offset";
            
            $stmt = $db->prepareSelect($query, $params);
            $records = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            echo json_encode([
                'status' => 'success',
                'data' => $records,
                'summary' => [
                    'total_employees' => (int)($summary['total_employees'] ?? 0),
                    'total_earnings' => floatval($summary['total_earnings'] ?? 0),
                    'total_paid' => floatval($summary['total_paid'] ?? 0),
                    'total_pending' => floatval($summary['total_pending'] ?? 0)
                ],
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Employee Salary Report AJAX Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
}
?>
