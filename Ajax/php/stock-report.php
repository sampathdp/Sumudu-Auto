<?php
/**
 * Stock Report AJAX Handler
 * Handles live stock report data requests
 */

require_once __DIR__ . '/../../classes/Includes.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        getStockList();
        break;
    case 'summary':
        getStockSummary();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

/**
 * Get stock list with filters
 */
function getStockList() {
    $db = new Database();
    
    $level = $_GET['level'] ?? 'all';
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'item_name';
    
    // Base query with FIFO value calculation (Batches SUM or Legacy fallback)
    $companyId = $_SESSION['company_id'] ?? 1;
    $query = "SELECT i.id, i.item_code, i.item_name, i.description, i.category_id, 
              i.unit_of_measure, i.current_stock, i.reorder_level, i.max_stock_level, 
              i.unit_cost, i.unit_price, i.is_active,
              c.category_name,
              COALESCE(
                (SELECT SUM(b.quantity_remaining * b.unit_cost) 
                 FROM inventory_batches b 
                 WHERE b.item_id = i.id AND b.company_id = i.company_id AND b.is_active = 1),
                (i.current_stock * i.unit_cost)
              ) as fifo_value
              FROM inventory_items i
              LEFT JOIN inventory_categories c ON i.category_id = c.id
              WHERE i.company_id = ?";
    
    $params = [$companyId];
    
    // Stock level filter
    switch ($level) {
        case 'in_stock':
            $query .= " AND i.current_stock > i.reorder_level";
            break;
        case 'low_stock':
            $query .= " AND i.current_stock > 0 AND i.current_stock <= i.reorder_level";
            break;
        case 'out_of_stock':
            $query .= " AND i.current_stock = 0";
            break;
        // 'all' - no filter
    }
    
    // Category filter
    if (!empty($category)) {
        $query .= " AND i.category_id = ?";
        $params[] = $category;
    }
    
    // Search filter
    if (!empty($search)) {
        $query .= " AND (i.item_name LIKE ? OR i.item_code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Sorting (using computed column alias fifo_value where supported, or repeating expression)
    // MySQL 5.7+ supports alias in ORDER BY
    switch ($sort) {
        case 'item_name_desc':
            $query .= " ORDER BY i.item_name DESC";
            break;
        case 'stock_low':
            $query .= " ORDER BY i.current_stock ASC";
            break;
        case 'stock_high':
            $query .= " ORDER BY i.current_stock DESC";
            break;
        case 'value_high':
            $query .= " ORDER BY fifo_value DESC";
            break;
        case 'value_low':
            $query .= " ORDER BY fifo_value ASC";
            break;
        default: // item_name
            $query .= " ORDER BY i.item_name ASC";
            break;
    }
    
    try {
        $stmt = $db->prepareSelect($query, $params);
        $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        echo json_encode([
            'status' => 'success',
            'data' => $items,
            'count' => count($items)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get stock summary statistics
 */
function getStockSummary() {
    $db = new Database();
    
    try {
        $companyId = $_SESSION['company_id'] ?? 1;
        $totalItems = $db->prepareSelect("SELECT COUNT(*) as count FROM inventory_items WHERE company_id = ?", [$companyId])->fetch()['count'] ?? 0;
        $totalStock = $db->prepareSelect("SELECT COALESCE(SUM(current_stock), 0) as total FROM inventory_items WHERE company_id = ?", [$companyId])->fetch()['total'] ?? 0;
        $lowStockItems = $db->prepareSelect("SELECT COUNT(*) as count FROM inventory_items WHERE company_id = ? AND current_stock > 0 AND current_stock <= reorder_level", [$companyId])->fetch()['count'] ?? 0;
        $outOfStock = $db->prepareSelect("SELECT COUNT(*) as count FROM inventory_items WHERE company_id = ? AND current_stock = 0", [$companyId])->fetch()['count'] ?? 0;
        
        // Revised Total Value: Use FIFO Batches primarily + Legacy fallback? 
        // Actually, simple FIFO sum is best. Legacy items with 0 batches will count as 0 value unless we mix.
        // Let's stick to PURE FIFO for accuracy. If no batch, value is technically undefined/zero until we migrate or stocktake.
        // OR better: SUM(fifo_value) logic from above?
        // Querying sum of batches is faster and strictly correct for new system.
        $totalValue = $db->prepareSelect("SELECT COALESCE(SUM(quantity_remaining * unit_cost), 0) as total FROM inventory_batches WHERE company_id = ? AND is_active = 1", [$companyId])->fetch()['total'] ?? 0;
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_items' => (int)$totalItems,
                'total_stock' => (int)$totalStock,
                'low_stock_items' => (int)$lowStockItems,
                'out_of_stock' => (int)$outOfStock,
                'total_value' => (float)$totalValue
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>
