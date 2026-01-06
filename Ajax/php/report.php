<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../../classes/Includes.php';

// Clear any output that might have been generated
ob_end_clean();

// Set JSON header before any output
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

try {
    $sessionCompanyId = $_SESSION['company_id'];
    $report = new Report($sessionCompanyId);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'sales_summary':
            // Get date range
            $startDate = $_GET['start_date'] ?? date('Y-m-d');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            // Get filters
            $filters = [];
            if (!empty($_GET['categories'])) {
                $filters['categories'] = explode(',', $_GET['categories']);
            }
            if (!empty($_GET['payment_method'])) {
                $filters['payment_method'] = $_GET['payment_method'];
            }
            if (!empty($_GET['payment_status'])) {
                $filters['payment_status'] = $_GET['payment_status'];
            }

            // Get all report data
            $summary = $report->getSalesSummary($startDate, $endDate, $filters);
            $categoryBreakdown = $report->getRevenueByCategory($startDate, $endDate);
            $paymentMethodBreakdown = $report->getRevenueByPaymentMethod($startDate, $endDate);
            $paymentStatusBreakdown = $report->getRevenueByPaymentStatus($startDate, $endDate);
            $topItems = $report->getTopSellingItems($startDate, $endDate, 10);
            $dailySales = $report->getDailySalesData($startDate, $endDate);
            $comparison = $report->getPeriodComparison($startDate, $endDate);

            echo json_encode([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'category_breakdown' => $categoryBreakdown,
                    'payment_method_breakdown' => $paymentMethodBreakdown,
                    'payment_status_breakdown' => $paymentStatusBreakdown,
                    'top_items' => $topItems,
                    'daily_sales' => $dailySales,
                    'comparison' => $comparison
                ]
            ]);
            break;

        case 'invoice_list':
            $startDate = $_GET['start_date'] ?? date('Y-m-d');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $filters = [];
            if (!empty($_GET['payment_method'])) {
                $filters['payment_method'] = $_GET['payment_method'];
            }
            if (!empty($_GET['payment_status'])) {
                $filters['payment_status'] = $_GET['payment_status'];
            }

            $result = $report->getInvoiceList($startDate, $endDate, $filters, $page, $perPage);

            echo json_encode([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ]);
            break;

        case 'export_pdf':
            // TODO: Implement PDF export using TCPDF or FPDF
            echo json_encode([
                'success' => false,
                'message' => 'PDF export will be implemented in next phase'
            ]);
            break;

        case 'export_excel':
            // TODO: Implement Excel export using PhpSpreadsheet
            echo json_encode([
                'success' => false,
                'message' => 'Excel export will be implemented in next phase'
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
