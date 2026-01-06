<?php
// Include required files
require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in and has permission to view this page
requirePagePermission('View');

// Page title
$pageTitle = 'Sales Summary Report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo APP_NAME; ?> - Sales Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
    <?php include '../../includes/main-css.php'; ?>
    
    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
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
        
        /* Filter Panel */
        .filter-panel {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .filter-section {
            margin-bottom: 1rem;
        }

        .filter-section:last-child {
            margin-bottom: 0;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: block;
        }

        .quick-date-btns {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .quick-date-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-date-btn:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .quick-date-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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

        .icon-primary { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .icon-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .icon-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
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

        .summary-change {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Chart Card */
        .chart-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.75rem;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Table */
        .table-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.75rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .table thead th {
            border-bottom: 2px solid var(--border-color);
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:hover {
            background: rgba(99, 102, 241, 0.02);
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.paid { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-badge.pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

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

        /* Checkbox Group */
        .checkbox-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Loading Spinner */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border-color);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
        <?php include '../../includes/sidebar.php'; ?>

        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>

            <div class="container-fluid">
                <div class="page-inner">
                    <!-- Page Header -->
                    <div class="page-header" style="margin-top: 70px;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h1 class="page-title">
                                    <i class="fas fa-chart-line me-2"></i>Sales Summary Report
                                </h1>
                                <p class="text-secondary mb-0">Comprehensive sales analytics and insights</p>
                            </div> 
                        </div>
                    </div>

                    <!-- Filter Panel -->
                    <div class="filter-panel">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="filter-label">Date Range</label>
                                <div class="quick-date-btns mb-3">
                                    <button class="quick-date-btn" data-range="today">Today</button>
                                    <button class="quick-date-btn" data-range="yesterday">Yesterday</button>
                                    <button class="quick-date-btn active" data-range="this_week">This Week</button>
                                    <button class="quick-date-btn" data-range="this_month">This Month</button>
                                    <button class="quick-date-btn" data-range="last_month">Last Month</button>
                                    <button class="quick-date-btn" data-range="custom">Custom Range</button>
                                </div>
                                <div class="row" id="customDateRange" style="display: none;">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="startDate" placeholder="Start Date">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="endDate" placeholder="End Date">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 filter-section">
                                <label class="filter-label">Categories</label>
                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="cat_service" value="service" checked>
                                        <label for="cat_service">Services</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="cat_inventory" value="inventory" checked>
                                        <label for="cat_inventory">Inventory</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="cat_labor" value="labor" checked>
                                        <label for="cat_labor">Labor</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="cat_other" value="other" checked>
                                        <label for="cat_other">Other</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 filter-section">
                                <label class="filter-label">Payment Method</label>
                                <select class="form-control" id="paymentMethod">
                                    <option value="all">All Methods</option>
                                    <option value="cash" selected>Cash</option>
                                    <option value="card">Card</option> 
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-4 filter-section">
                                <label class="filter-label">Payment Status</label>
                                <select class="form-control" id="paymentStatus">
                                    <option value="">All Status</option>
                                    <option value="paid">Paid</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <button class="btn btn-primary" id="applyFilters">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                                <button class="btn btn-outline-primary" id="resetFilters">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4" id="summaryCards">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-primary">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="summary-value" id="totalRevenue">LKR 0.00</div>
                                <div class="summary-label">Total Revenue</div>
                                <div class="summary-change text-success" id="revenueChange">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>0% from previous period</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-success">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div class="summary-value" id="totalInvoices">0</div>
                                <div class="summary-label">Total Invoices</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-info">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="summary-value" id="avgOrderValue">LKR 0.00</div>
                                <div class="summary-label">Average Order Value</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="summary-value" id="pendingAmount">LKR 0.00</div>
                                <div class="summary-label">Pending Payments</div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="chart-title">Revenue Trend</h5>
                                </div>
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="chart-title">Category Breakdown</h5>
                                </div>
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method & Status Charts -->
                    <div class="row mb-4">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="chart-title">Payment Methods</h5>
                                </div>
                                <div class="chart-container">
                                    <canvas id="paymentMethodChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="chart-title">Payment Status</h5>
                                </div>
                                <div class="chart-container">
                                    <canvas id="paymentStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Selling Items -->
                    <div class="table-card mb-4">
                        <div class="chart-header">
                            <h5 class="chart-title">Top Selling Items</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item/Service</th>
                                        <th>Type</th>
                                        <th>Times Sold</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="topItemsTable">
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Invoice List -->
                    <div class="table-card">
                        <div class="chart-header">
                            <h5 class="chart-title">Invoice Details</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Job #</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="invoiceTable">
                                    <tr>
                                        <td colspan="7" class="text-center py-4">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div id="paginationInfo"></div>
                            <div id="pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="<?php echo BASE_URL; ?>Ajax/js/report.js"></script>
</body>
</html>
