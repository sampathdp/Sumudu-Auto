<?php
class Report
{
    private $db;
    private $companyId;

    public function __construct($companyId)
    {
        $this->db = new Database();
        $this->companyId = $companyId;
    }

    /**
     * Get comprehensive sales summary with filters
     */
    public function getSalesSummary($startDate, $endDate, $filters = [])
    {
        $params = [$this->companyId, $startDate, $endDate];
        $whereConditions = ["i.company_id = ?", "DATE(i.created_at) BETWEEN ? AND ?", "(i.status IS NULL OR i.status = 'active')"];

        // Apply item type filter (categories in the UI/JS)
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
            $whereConditions[] = "i.id IN (SELECT invoice_id FROM invoice_items WHERE item_type IN ($placeholders))";
            $params = array_merge($params, $filters['categories']);
        }

        // Apply payment method filter
        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $whereConditions[] = "i.payment_method = ?";
            $params[] = $filters['payment_method'];
        }

        // Apply payment status filter
        if (!empty($filters['payment_status'])) {
            if ($filters['payment_status'] === 'paid') {
                $whereConditions[] = "i.payment_date IS NOT NULL";
            } elseif ($filters['payment_status'] === 'pending') {
                $whereConditions[] = "i.payment_date IS NULL";
            }
        }

        $whereClause = implode(' AND ', $whereConditions);

        $query = "SELECT 
            COUNT(i.id) as total_invoices,
            COALESCE(SUM(i.total_amount), 0) as total_revenue,
            COALESCE(AVG(i.total_amount), 0) as average_order_value,
            COALESCE(SUM(CASE WHEN i.payment_date IS NULL THEN i.total_amount ELSE 0 END), 0) as pending_amount,
            COALESCE(SUM(CASE WHEN i.payment_date IS NOT NULL THEN i.total_amount ELSE 0 END), 0) as paid_amount
        FROM invoices i
        WHERE $whereClause";

        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return [];
    }

    /**
     * Get customer sales report
     * Group sales by customer for a given period
     */
    public function getCustomerSalesReport($startDate, $endDate)
    {
        $query = "
            SELECT 
                c.id as customer_id,
                COALESCE(c.name, i.customer_name, 'Walk-in Customer') as customer_name,
                c.phone as customer_phone,
                COUNT(DISTINCT i.id) as invoice_count,
                SUM(ii.total_price) as total_revenue,
                SUM(CASE WHEN ii.item_type = 'service' THEN ii.total_price ELSE 0 END) as service_revenue,
                SUM(CASE WHEN ii.item_type = 'inventory' THEN ii.total_price ELSE 0 END) as inventory_revenue,
                SUM(CASE WHEN ii.item_type = 'labor' THEN ii.total_price ELSE 0 END) as labor_revenue
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
            WHERE i.company_id = ?
            AND i.status != 'cancelled'
            AND DATE(i.created_at) BETWEEN ? AND ?
            GROUP BY c.id, customer_name, c.phone
            ORDER BY total_revenue DESC
        ";

        $stmt = $this->db->prepareSelect($query, [$this->companyId, $startDate, $endDate]);
        
        $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if (empty($items)) {
            return [
                'data' => [],
                'summary' => [
                    'total_revenue' => 0,
                    'service_revenue' => 0,
                    'inventory_revenue' => 0,
                    'labor_revenue' => 0
                ]
            ];
        }

        // Calculate totals
        $totalRevenue = 0;
        $serviceRevenue = 0;
        $inventoryRevenue = 0;
        $laborRevenue = 0;

        foreach ($items as $item) {
            $totalRevenue += $item['total_revenue'];
            $serviceRevenue += $item['service_revenue'];
            $inventoryRevenue += $item['inventory_revenue'];
            $laborRevenue += $item['labor_revenue'];
        }

        return [
            'data' => $items,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'service_revenue' => $serviceRevenue,
                'inventory_revenue' => $inventoryRevenue,
                'labor_revenue' => $laborRevenue
            ]
        ];
    }

    /**
     * Get revenue breakdown by category (service, inventory, labor, other)
     */
    public function getRevenueByCategory($startDate, $endDate)
    {
        $query = "SELECT 
            ii.item_type as category,
            COUNT(ii.id) as item_count,
            COALESCE(SUM(ii.total_price), 0) as total_revenue
        FROM invoice_items ii
        INNER JOIN invoices i ON ii.invoice_id = i.id
        WHERE i.company_id = ? AND DATE(i.created_at) BETWEEN ? AND ?
        AND (i.status IS NULL OR i.status = 'active')
        GROUP BY ii.item_type
        ORDER BY total_revenue DESC";

        $stmt = $this->db->prepareSelect($query, [$this->companyId, $startDate, $endDate]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get revenue breakdown by payment method
     */
    public function getRevenueByPaymentMethod($startDate, $endDate)
    {
        $query = "SELECT 
            COALESCE(payment_method, 'Not Specified') as payment_method,
            COUNT(id) as invoice_count,
            COALESCE(SUM(total_amount), 0) as total_revenue
        FROM invoices
        WHERE company_id = ? AND DATE(created_at) BETWEEN ? AND ?
        AND (status IS NULL OR status = 'active')
        GROUP BY payment_method
        ORDER BY total_revenue DESC";

        $stmt = $this->db->prepareSelect($query, [$this->companyId, $startDate, $endDate]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get revenue breakdown by payment status
     */
    public function getRevenueByPaymentStatus($startDate, $endDate)
    {
        $query = "SELECT 
            CASE 
                WHEN payment_date IS NOT NULL THEN 'Paid'
                ELSE 'Pending'
            END as payment_status,
            COUNT(id) as invoice_count,
            COALESCE(SUM(total_amount), 0) as total_amount
        FROM invoices
        WHERE company_id = ? AND DATE(created_at) BETWEEN ? AND ?
        AND (status IS NULL OR status = 'active')
        GROUP BY payment_status";

        $stmt = $this->db->prepareSelect($query, [$this->companyId, $startDate, $endDate]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get top selling items/services
     */
    public function getTopSellingItems($startDate, $endDate, $limit = 10)
    {
        $query = "SELECT 
            ii.item_type,
            ii.description,
            COUNT(ii.id) as times_sold,
            COALESCE(SUM(ii.quantity), 0) as total_quantity,
            COALESCE(SUM(ii.total_price), 0) as total_revenue
        FROM invoice_items ii
        INNER JOIN invoices i ON ii.invoice_id = i.id
        WHERE i.company_id = ? AND DATE(i.created_at) BETWEEN ? AND ?
        AND (i.status IS NULL OR i.status = 'active')
        GROUP BY ii.item_type, ii.description
        ORDER BY total_revenue DESC
        LIMIT ?";

        $stmt = $this->db->prepareSelect($query, [$this->companyId, $startDate, $endDate, $limit]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get daily sales data for trend charts
     */
    public function getDailySalesData($startDate, $endDate)
    {
        $query = "SELECT 
            DATE(created_at) as sale_date,
            COUNT(id) as invoice_count,
            COALESCE(SUM(total_amount), 0) as daily_revenue
        FROM invoices
        WHERE company_id = ? AND DATE(created_at) BETWEEN ? AND ?
        AND (status IS NULL OR status = 'active')
        GROUP BY DATE(created_at)
        ORDER BY sale_date ASC";

        $stmt = $this->db->prepareSelect($query, [$this->companyId, $startDate, $endDate]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get detailed invoice list with filters
     */
    public function getInvoiceList($startDate, $endDate, $filters = [], $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $params = [$this->companyId, $startDate, $endDate];
        $whereConditions = ["i.company_id = ?", "DATE(i.created_at) BETWEEN ? AND ?", "(i.status IS NULL OR i.status = 'active')"];

        // Apply payment method filter
        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $whereConditions[] = "i.payment_method = ?";
            $params[] = $filters['payment_method'];
        }

        // Apply payment status filter
        if (!empty($filters['payment_status'])) {
            if ($filters['payment_status'] === 'paid') {
                $whereConditions[] = "i.payment_date IS NOT NULL";
            } elseif ($filters['payment_status'] === 'pending') {
                $whereConditions[] = "i.payment_date IS NULL";
            }
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get total count
        $countQuery = "SELECT COUNT(DISTINCT i.id) as total 
                      FROM invoices i 
                      WHERE $whereClause";
        $countStmt = $this->db->prepareSelect($countQuery, $params);
        $totalRecords = $countStmt ? $countStmt->fetch()['total'] : 0;

        // Get paginated data
        $query = "SELECT 
            i.id,
            i.invoice_number,
            i.service_id,
            i.created_at,
            i.total_amount,
            i.payment_method,
            i.payment_date,
            s.job_number,
            c.name as customer_name,
            c.phone as customer_phone,
            v.registration_number,
            GROUP_CONCAT(DISTINCT ii.item_type) as item_types
        FROM invoices i
        LEFT JOIN services s ON i.service_id = s.id
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.id
        LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
        WHERE $whereClause
        GROUP BY i.id
        ORDER BY i.created_at DESC
        LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepareSelect($query, $params);
        $invoices = $stmt ? $stmt->fetchAll() : [];

        return [
            'data' => $invoices,
            'pagination' => [
                'total' => $totalRecords,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($totalRecords / $perPage)
            ]
        ];
    }

    /**
     * Get comparison data with previous period
     */
    public function getPeriodComparison($startDate, $endDate)
    {
        // Calculate previous period dates
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $diff = $start->diff($end);
        $days = $diff->days + 1;

        $prevEnd = clone $start;
        $prevEnd->modify('-1 day');
        $prevStart = clone $prevEnd;
        $prevStart->modify("-$days days");

        // Current period - exclude cancelled invoices
        $currentQuery = "SELECT COALESCE(SUM(total_amount), 0) as revenue 
                        FROM invoices 
                        WHERE company_id = ? AND DATE(created_at) BETWEEN ? AND ?
                        AND (status IS NULL OR status = 'active')";
        $currentStmt = $this->db->prepareSelect($currentQuery, [$this->companyId, $startDate, $endDate]);
        $currentRevenue = $currentStmt ? $currentStmt->fetch()['revenue'] : 0;

        // Previous period
        $prevStmt = $this->db->prepareSelect($currentQuery, [
            $this->companyId,
            $prevStart->format('Y-m-d'), 
            $prevEnd->format('Y-m-d')
        ]);
        $prevRevenue = $prevStmt ? $prevStmt->fetch()['revenue'] : 0;

        // Calculate percentage change
        $percentageChange = 0;
        if ($prevRevenue > 0) {
            $percentageChange = (($currentRevenue - $prevRevenue) / $prevRevenue) * 100;
        }

        return [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $prevRevenue,
            'percentage_change' => round($percentageChange, 2),
            'trend' => $percentageChange >= 0 ? 'up' : 'down'
        ];
    }

    /**
     * Get Stock Report
     * 
     * @param array $filters Filter parameters (level, category, search, sort)
     * @return array Array with data and total value
     */
    public function getStockReport($filters = [])
    {
        $level = $filters['level'] ?? 'all';
        $category = $filters['category'] ?? '';
        $search = $filters['search'] ?? '';
        $sort = $filters['sort'] ?? 'item_name';

        // Base query with FIFO value calculation (Batches SUM or Legacy fallback)
        $query = "SELECT i.id, i.item_code, i.item_name, i.description, i.category_id, 
                  i.unit_of_measure, i.current_stock, i.reorder_level, 
                  i.unit_cost, c.category_name,
                  COALESCE(
                    (SELECT SUM(b.quantity_remaining * b.unit_cost) 
                     FROM inventory_batches b 
                     WHERE b.item_id = i.id AND b.company_id = i.company_id AND b.is_active = 1),
                    (i.current_stock * i.unit_cost)
                  ) as fifo_value
                  FROM inventory_items i
                  LEFT JOIN inventory_categories c ON i.category_id = c.id
                  WHERE i.company_id = ?";

        $params = [$this->companyId];

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

        // Sorting
        switch ($sort) {
            case 'item_name_desc': $query .= " ORDER BY i.item_name DESC"; break;
            case 'stock_low': $query .= " ORDER BY i.current_stock ASC"; break;
            case 'stock_high': $query .= " ORDER BY i.current_stock DESC"; break;
            case 'value_high': $query .= " ORDER BY fifo_value DESC"; break;
            case 'value_low': $query .= " ORDER BY fifo_value ASC"; break;
            default: $query .= " ORDER BY i.item_name ASC"; break;
        }

        $items = $this->db->prepareSelect($query, $params)->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total value from FIFO values
        $totalValue = 0;
        foreach ($items as $item) {
            $totalValue += $item['fifo_value'];
        }
        
        return [
            'data' => $items,
            'total_value' => $totalValue
        ];
    }

    // ============================================
    // SERVICE HISTORY REPORT METHODS
    // ============================================

    /**
     * Get service history with filters and pagination
     */
    public function getServiceHistory($filters = [], $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $params = [$this->companyId];
        $whereConditions = ["s.company_id = ?"];

        // Date range filter
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $whereConditions[] = "DATE(s.created_at) BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }

        // Customer filter
        if (!empty($filters['customer_id'])) {
            $whereConditions[] = "s.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        // Vehicle filter
        if (!empty($filters['vehicle_id'])) {
            $whereConditions[] = "s.vehicle_id = ?";
            $params[] = $filters['vehicle_id'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $whereConditions[] = "s.status = ?";
            $params[] = $filters['status'];
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            if ($filters['payment_status'] === 'paid') {
                $whereConditions[] = "i.payment_date IS NOT NULL";
            } elseif ($filters['payment_status'] === 'pending') {
                $whereConditions[] = "(i.id IS NOT NULL AND i.payment_date IS NULL)";
            } elseif ($filters['payment_status'] === 'no_invoice') {
                $whereConditions[] = "i.id IS NULL";
            }
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get total count
        $countQuery = "SELECT COUNT(DISTINCT s.id) as total 
                       FROM services s
                       LEFT JOIN invoices i ON i.service_id = s.id AND (i.status IS NULL OR i.status = 'active')
                       WHERE $whereClause";
        $countStmt = $this->db->prepareSelect($countQuery, $params);
        $totalRecords = $countStmt ? $countStmt->fetch()['total'] : 0;

        // Get paginated data
        $query = "SELECT 
            s.id,
            s.job_number,
            s.status,
            s.progress_percentage,
            s.total_amount as service_amount,
            s.payment_status as service_payment_status,
            s.notes,
            s.created_at,
            s.start_time,
            s.actual_completion_time,
            c.id as customer_id,
            c.name as customer_name,
            c.phone as customer_phone,
            c.email as customer_email,
            v.id as vehicle_id,
            v.registration_number,
            v.make,
            v.model,
            v.year as vehicle_year,
            v.color as vehicle_color,
            v.current_mileage,
            sp.package_name,
            sp.base_price,
            i.id as invoice_id,
            i.invoice_number,
            i.total_amount as invoice_amount,
            i.payment_method,
            i.payment_date,
            ss.stage_name as current_stage
        FROM services s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.id
        LEFT JOIN service_packages sp ON s.package_id = sp.id
        LEFT JOIN service_stages ss ON s.current_stage_id = ss.id
        LEFT JOIN invoices i ON i.service_id = s.id AND (i.status IS NULL OR i.status = 'active')
        WHERE $whereClause
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepareSelect($query, $params);
        $services = $stmt ? $stmt->fetchAll() : [];

        return [
            'data' => $services,
            'pagination' => [
                'total' => (int)$totalRecords,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($totalRecords / $perPage)
            ]
        ];
    }

    /**
     * Get service history summary statistics
     */
    public function getServiceHistorySummary($filters = [])
    {
        $params = [$this->companyId];
        $whereConditions = ["s.company_id = ?"];

        // Apply same filters
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $whereConditions[] = "DATE(s.created_at) BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['customer_id'])) {
            $whereConditions[] = "s.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['vehicle_id'])) {
            $whereConditions[] = "s.vehicle_id = ?";
            $params[] = $filters['vehicle_id'];
        }

        if (!empty($filters['status'])) {
            $whereConditions[] = "s.status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        $query = "SELECT 
            COUNT(DISTINCT s.id) as total_services,
            SUM(CASE WHEN s.status = 'delivered' OR s.status = 'completed' THEN 1 ELSE 0 END) as completed_services,
            SUM(CASE WHEN s.status = 'in_progress' OR s.status = 'waiting' THEN 1 ELSE 0 END) as active_services,
            SUM(CASE WHEN s.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_services,
            COALESCE(SUM(i.total_amount), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN i.payment_date IS NOT NULL THEN i.total_amount ELSE 0 END), 0) as paid_amount,
            COALESCE(SUM(CASE WHEN i.payment_date IS NULL AND i.id IS NOT NULL THEN i.total_amount ELSE 0 END), 0) as pending_amount
        FROM services s
        LEFT JOIN invoices i ON i.service_id = s.id AND (i.status IS NULL OR i.status = 'active')
        WHERE $whereClause";

        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return [
            'total_services' => 0,
            'completed_services' => 0,
            'active_services' => 0,
            'cancelled_services' => 0,
            'total_revenue' => 0,
            'paid_amount' => 0,
            'pending_amount' => 0
        ];
    }

    /**
     * Get all customers for filter dropdown
     */
    public function getAllCustomersForFilter()
    {
        $query = "SELECT c.id, c.name, c.phone,
                  (SELECT COUNT(*) FROM services WHERE customer_id = c.id AND company_id = ?) as service_count
                  FROM customers c
                  WHERE c.company_id = ?
                  ORDER BY c.name ASC";
        $stmt = $this->db->prepareSelect($query, [$this->companyId, $this->companyId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get vehicles by customer ID for filter dropdown
     */
    public function getVehiclesByCustomer($customerId)
    {
        $query = "SELECT v.id, v.registration_number, v.make, v.model, v.year, v.color,
                  (SELECT COUNT(*) FROM services WHERE vehicle_id = v.id AND company_id = ?) as service_count
                  FROM vehicles v
                  WHERE v.customer_id = ? AND v.company_id = ?
                  ORDER BY v.registration_number ASC";
        $stmt = $this->db->prepareSelect($query, [$this->companyId, $customerId, $this->companyId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get all vehicles for filter dropdown (when no customer is selected)
     */
    public function getAllVehiclesForFilter()
    {
        $query = "SELECT v.id, v.registration_number, v.make, v.model, v.year,
                  c.name as customer_name,
                  (SELECT COUNT(*) FROM services WHERE vehicle_id = v.id AND company_id = ?) as service_count
                  FROM vehicles v
                  JOIN customers c ON v.customer_id = c.id
                  WHERE v.company_id = ?
                  ORDER BY v.registration_number ASC";
        $stmt = $this->db->prepareSelect($query, [$this->companyId, $this->companyId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
}
?>
