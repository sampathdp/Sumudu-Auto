<?php
// Include required files
require_once __DIR__ . '/../../../classes/Includes.php';

// Check if user is logged in
requirePagePermission('View');

// Page title
$pageTitle = 'Live Stock Report';

// Get inventory item counts for summary
$companyId = $_SESSION['company_id'] ?? 1;
$stats = InventoryItem::getStats($companyId);
$totalItems = $stats['total'];
$totalStock = $stats['total_stock'];
$lowStockItems = $stats['low_stock'];
$outOfStock = $stats['out_of_stock'];
$totalValue = $stats['total_value'];

// Get categories for filter
$categoryModel = new InventoryCategory();
$categories = $categoryModel->getActiveCategories($companyId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo APP_NAME; ?> - Live Stock Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
    <?php include '../../../includes/main-css.php'; ?>
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --bg-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .page-inner { padding: 2rem; }
        
        /* Page Header */
        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        /* Filter Panel */
        .filter-panel {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Stock Level Buttons */
        .stock-level-btns {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .stock-level-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .stock-level-btn:hover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.05);
        }

        .stock-level-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .stock-level-btn.danger.active {
            background: var(--danger);
            border-color: var(--danger);
        }

        .stock-level-btn.warning.active {
            background: var(--warning);
            border-color: var(--warning);
        }

        /* Summary Cards */
        .summary-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            height: 100%;
        }

        .summary-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .summary-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .icon-primary { background: rgba(79, 70, 229, 0.1); color: var(--primary); }
        .icon-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .icon-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .icon-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .icon-info { background: rgba(59, 130, 246, 0.1); color: var(--info); }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .summary-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Table Card */
        .table-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.75rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .table thead th {
            border-bottom: 2px solid var(--border-color);
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 1rem;
            background: #f8fafc;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:hover {
            background: rgba(79, 70, 229, 0.02);
        }

        /* Stock Status Badges */
        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .stock-badge.in-stock { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stock-badge.low-stock { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stock-badge.out-of-stock { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        /* Stock Level Bar */
        .stock-level-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .stock-level-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }

        .stock-level-fill.success { background: var(--success); }
        .stock-level-fill.warning { background: var(--warning); }
        .stock-level-fill.danger { background: var(--danger); }

        /* Buttons */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* Item Code */
        .item-code {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.8125rem;
            color: var(--primary);
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .page-inner { padding: 1rem; }
            .summary-card { margin-bottom: 1rem; }
        }


    </style>
</head>

<body>
    <div class="wrapper">
        <?php include '../../../includes/sidebar.php'; ?>

        <div class="main-panel">
            <?php include '../../../includes/header.php'; ?>

            <div class="container-fluid">
                <div class="page-inner">
                    <!-- Page Header -->
                    <div class="page-header" style="margin-top: 70px;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h1 class="page-title">
                                    <i class="fas fa-boxes me-2"></i>Live Stock Report
                                </h1>
                                <p class="text-secondary mb-0">Real-time inventory stock levels and analysis</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary" id="exportExcel">
                                    <i class="fas fa-file-excel me-2"></i>Export Excel
                                </button>
                                <button class="btn btn-primary" id="printReport">
                                    <i class="fas fa-print me-2"></i>Print
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Panel -->
                    <div class="filter-panel">
                        <div class="row">
                            <!-- Stock Level Filter -->
                            <div class="col-12 mb-3">
                                <label class="filter-label">Stock Level</label>
                                <div class="stock-level-btns">
                                    <button class="stock-level-btn active" data-level="all">All Items</button>
                                    <button class="stock-level-btn" data-level="in_stock">In Stock</button>
                                    <button class="stock-level-btn warning" data-level="low_stock">Low Stock</button>
                                    <button class="stock-level-btn danger" data-level="out_of_stock">Out of Stock</button>
                                </div>
                            </div>

                            <!-- Category Filter -->
                            <div class="col-md-6 col-lg-4">
                                <label class="filter-label">Category</label>
                                <select class="form-select" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Search -->
                            <div class="col-md-6 col-lg-4">
                                <label class="filter-label">Search</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search by item name or code...">
                            </div>

                            <!-- Sort By -->
                            <div class="col-md-6 col-lg-4">
                                <label class="filter-label">Sort By</label>
                                <select class="form-select" id="sortFilter">
                                    <option value="item_name">Item Name (A-Z)</option>
                                    <option value="item_name_desc">Item Name (Z-A)</option>
                                    <option value="stock_low">Stock Level (Low to High)</option>
                                    <option value="stock_high">Stock Level (High to Low)</option>
                                    <option value="value_high">Stock Value (High to Low)</option>
                                    <option value="value_low">Stock Value (Low to High)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4" id="summaryCards">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-primary">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="summary-value"><?php echo number_format($totalItems); ?></div>
                                <div class="summary-label">Total Items</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-success">
                                    <i class="fas fa-cubes"></i>
                                </div>
                                <div class="summary-value"><?php echo number_format($totalStock); ?></div>
                                <div class="summary-label">Total Stock Units</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="summary-value"><?php echo number_format($lowStockItems); ?></div>
                                <div class="summary-label">Low Stock Items</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="summary-value"><?php echo number_format($outOfStock); ?></div>
                                <div class="summary-label">Out of Stock</div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Stock Value -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="summary-card" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="summary-label" style="color: rgba(255,255,255,0.8);">Total Stock Value</div>
                                        <div class="summary-value" style="color: white;">LKR <?php echo number_format($totalValue, 2); ?></div>
                                    </div>
                                    <div style="font-size: 3rem; opacity: 0.3;">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Table -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5 class="table-title">Stock Details</h5>
                            <span id="itemCount" class="text-secondary"></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="stockTable">
                                <thead>
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th class="text-center">Current Stock</th>
                                        <th class="text-center">Reorder Level</th>
                                        <th>Stock Level</th>
                                        <th>Status</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">Stock Value</th>
                                    </tr>
                                </thead>
                                <tbody id="stockTableBody">
                                    <tr>
                                        <td colspan="10" class="text-center py-4">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../../includes/main-js.php'; ?>
    <script>
        $(document).ready(function() {
            let currentLevel = 'all';
            let currentCategory = '';
            let currentSearch = '';
            let currentSort = 'item_name';

            // Load stock data
            function loadStockData() {
                const tbody = $('#stockTableBody');
                tbody.html('<tr><td colspan="10" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');

                $.ajax({
                    url: '<?php echo BASE_URL; ?>Ajax/php/stock-report.php',
                    type: 'GET',
                    data: {
                        action: 'list',
                        level: currentLevel,
                        category: currentCategory,
                        search: currentSearch,
                        sort: currentSort
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success' && response.data) {
                            renderTable(response.data);
                            $('#itemCount').text(`Showing ${response.data.length} items`);
                        } else {
                            tbody.html('<tr><td colspan="10" class="text-center py-4 text-danger">Failed to load data</td></tr>');
                        }
                    },
                    error: function() {
                        tbody.html('<tr><td colspan="10" class="text-center py-4 text-danger">Error loading stock data</td></tr>');
                    }
                });
            }

            // Render table
            function renderTable(data) {
                const tbody = $('#stockTableBody');
                
                if (data.length === 0) {
                    tbody.html(`
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-secondary mb-3" style="opacity: 0.5;"></i>
                                <p class="text-secondary mb-0">No items found matching your criteria</p>
                            </td>
                        </tr>
                    `);
                    return;
                }

                let html = '';
                data.forEach(function(item) {
                    const stockPercent = item.reorder_level > 0 ? Math.min((item.current_stock / (item.reorder_level * 2)) * 100, 100) : 100;
                    let levelClass = 'success';
                    let statusBadge = '<span class="stock-badge in-stock"><i class="fas fa-check-circle"></i> In Stock</span>';
                    
                    if (item.current_stock == 0) {
                        levelClass = 'danger';
                        statusBadge = '<span class="stock-badge out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>';
                    } else if (item.current_stock <= item.reorder_level) {
                        levelClass = 'warning';
                        statusBadge = '<span class="stock-badge low-stock"><i class="fas fa-exclamation-triangle"></i> Low Stock</span>';
                    }

                    const stockValue = parseFloat(item.fifo_value).toFixed(2);

                    html += `
                        <tr>
                            <td><span class="item-code">${item.item_code}</span></td>
                            <td><strong>${item.item_name}</strong></td>
                            <td>${item.category_name || '<span class="text-muted">Uncategorized</span>'}</td>
                            <td>${item.unit_of_measure}</td>
                            <td class="text-center fw-bold">${parseInt(item.current_stock).toLocaleString()}</td>
                            <td class="text-center">${parseInt(item.reorder_level).toLocaleString()}</td>
                            <td>
                                <div class="stock-level-bar">
                                    <div class="stock-level-fill ${levelClass}" style="width: ${stockPercent}%;"></div>
                                </div>
                            </td>
                            <td>${statusBadge}</td>
                            <td class="text-end">LKR ${parseFloat(item.unit_cost).toFixed(2)}</td>
                            <td class="text-end fw-bold">LKR ${parseFloat(stockValue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        </tr>
                    `;
                });

                tbody.html(html);
            }

            // Stock level filter buttons
            $('.stock-level-btn').on('click', function() {
                $('.stock-level-btn').removeClass('active');
                $(this).addClass('active');
                currentLevel = $(this).data('level');
                loadStockData();
            });

            // Category filter
            $('#categoryFilter').on('change', function() {
                currentCategory = $(this).val();
                loadStockData();
            });

            // Search
            let searchTimeout;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                const search = $(this).val();
                searchTimeout = setTimeout(function() {
                    currentSearch = search;
                    loadStockData();
                }, 300);
            });

            // Sort filter
            $('#sortFilter').on('change', function() {
                currentSort = $(this).val();
                loadStockData();
            });

            // Print report
            $('#printReport').on('click', function() {
                const params = new URLSearchParams({
                    level: currentLevel,
                    category: currentCategory,
                    search: currentSearch,
                    sort: currentSort
                });
                window.open('print.php?' + params.toString(), '_blank');
            });

            // Export to Excel (simple CSV export)
            $('#exportExcel').on('click', function() {
                const table = document.getElementById('stockTable');
                let csv = [];
                const rows = table.querySelectorAll('tr');
                
                for (let row of rows) {
                    let cols = row.querySelectorAll('td, th');
                    let rowData = [];
                    for (let col of cols) {
                        rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
                    }
                    csv.push(rowData.join(','));
                }
                
                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'stock_report_' + new Date().toISOString().slice(0,10) + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Initial load
            loadStockData();
        });
    </script>
</body>
</html>
