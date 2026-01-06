<?php
/**
 * Customer Sales Report AJAX Handler
 * Handles customer sales breakdown data
 */

require_once __DIR__ . '/../../classes/Includes.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get session company_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        getReportData($sessionCompanyId);
        break;
    case 'get_customer_invoices':
        getCustomerInvoices($sessionCompanyId);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

function getCustomerInvoices($companyId) {
    $db = new Database();
    $customerId = $_GET['customer_id'] ?? 0;
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    if (!$customerId) {
        echo json_encode(['status' => 'error', 'message' => 'Customer ID required']);
        return;
    }
    
    $query = "
        SELECT 
            i.id, 
            i.invoice_number, 
            i.created_at, 
            i.total_amount, 
            i.status,
            CASE WHEN i.payment_date IS NOT NULL THEN i.total_amount ELSE 0 END as paid_amount
        FROM invoices i
        WHERE i.customer_id = ? 
        AND i.company_id = ?
        AND i.status != 'cancelled'
        AND DATE(i.created_at) BETWEEN ? AND ?
        ORDER BY i.created_at DESC
    ";
    
    try {
        $invoices = $db->prepareSelect($query, [$customerId, $companyId, $startDate, $endDate])->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $invoices]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getReportData($companyId) {
    $db = new Database();
    
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Validate dates
    if (!strtotime($startDate) || !strtotime($endDate)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid date range']);
        return;
    }
    
    // Query to get sales aggregated by customer
    // Join invoice_items to get breakdown by type
    // We only consider invoices that are not cancelled
    $query = "
        SELECT 
            c.id as customer_id,
            COALESCE(c.name, i.customer_name, 'Walk-in Customer') as customer_name,
            c.phone as customer_phone,
            COUNT(DISTINCT i.id) as invoice_count,
            SUM(ii.total_price) as total_revenue,
            SUM(CASE WHEN ii.item_type = 'service' THEN ii.total_price ELSE 0 END) as service_revenue,
            SUM(CASE WHEN ii.item_type = 'inventory' THEN ii.total_price ELSE 0 END) as inventory_revenue,
            SUM(CASE WHEN ii.item_type = 'labor' THEN ii.total_price ELSE 0 END) as labor_revenue,
            SUM(CASE WHEN ii.item_type NOT IN ('service', 'inventory', 'labor') THEN ii.total_price ELSE 0 END) as other_revenue
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
        WHERE i.company_id = ?
        AND i.status != 'cancelled'
        AND DATE(i.created_at) BETWEEN ? AND ?
        GROUP BY c.id, customer_name, c.phone
        ORDER BY total_revenue DESC
    ";
    
    try {
        $stmt = $db->prepareSelect($query, [$companyId, $startDate, $endDate]);
        $data = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        // Calculate totals for summary
        $summary = [
            'total_revenue' => 0,
            'service_revenue' => 0,
            'inventory_revenue' => 0,
            'labor_revenue' => 0,
            'invoice_count' => 0
        ];
        
        foreach ($data as $row) {
            $summary['total_revenue'] += $row['total_revenue'];
            $summary['service_revenue'] += $row['service_revenue'];
            $summary['inventory_revenue'] += $row['inventory_revenue'];
            $summary['labor_revenue'] += $row['labor_revenue'];
            $summary['invoice_count'] += $row['invoice_count'];
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $data,
            'summary' => $summary
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>
