<?php
// Include required files
require_once __DIR__ . '/../../../classes/Includes.php';

// Check if user is logged in
requirePagePermission('View');

// Page title
$pageTitle = 'Service History Report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo APP_NAME; ?> - Service History Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
    <?php include '../../../includes/main-css.php'; ?>
    
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
            background: rgba(99, 102, 241, 0.02);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-waiting { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .status-progress { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .status-check { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .status-completed { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-delivered { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .status-cancelled { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .status-default { background: rgba(100, 116, 139, 0.1); color: var(--text-secondary); }

        /* Payment Badges */
        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .payment-badge.paid { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .payment-badge.pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .payment-badge.no-invoice { background: rgba(100, 116, 139, 0.1); color: var(--text-secondary); }

        /* Customer/Vehicle Info */
        .customer-info, .vehicle-info {
            line-height: 1.4;
        }

        .customer-info strong, .vehicle-info strong {
            color: var(--text-primary);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-buttons .btn {
            padding: 0.375rem 0.5rem;
            border-radius: 8px;
        }

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
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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

        /* Empty State */
        .empty-state {
            padding: 3rem;
        }

        .empty-state i {
            opacity: 0.5;
        }

        /* Pagination */
        .pagination {
            gap: 0.25rem;
        }

        .page-link {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 0.875rem;
        }

        .page-item.active .page-link {
            background: var(--primary);
            border-color: var(--primary);
        }

        .page-item.disabled .page-link {
            color: var(--text-secondary);
        }

        /* Date Cell */
        .date-cell .date {
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .page-inner { padding: 1rem; }
            .summary-card { margin-bottom: 1rem; }
        }

        /* Print Styles */
        @media print {
            .sidebar, .main-header, .filter-panel, .action-buttons, 
            .btn-outline-primary, .pagination, #loadingOverlay {
                display: none !important;
            }
            .main-panel {
                margin-left: 0 !important;
            }
            .page-inner {
                padding: 0 !important;
            }
            .table-card {
                box-shadow: none !important;
                border: none !important;
            }
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
                                    <i class="fas fa-history me-2"></i>Service History Report
                                </h1>
                                <p class="text-secondary mb-0">View complete service history by customer and vehicle</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary" id="exportPDF">
                                    <i class="fas fa-file-pdf me-2"></i>Export PDF
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
                            <!-- Date Range -->
                            <div class="col-12 mb-3">
                                <label class="filter-label">Date Range</label>
                                <div class="quick-date-btns mb-3">
                                    <button class="quick-date-btn" data-range="today">Today</button>
                                    <button class="quick-date-btn" data-range="yesterday">Yesterday</button>
                                    <button class="quick-date-btn" data-range="this_week">This Week</button>
                                    <button class="quick-date-btn active" data-range="this_month">This Month</button>
                                    <button class="quick-date-btn" data-range="last_month">Last Month</button>
                                    <button class="quick-date-btn" data-range="this_year">This Year</button>
                                    <button class="quick-date-btn" data-range="custom">Custom Range</button>
                                </div>
                                <div class="row" id="customDateRange" style="display: none;">
                                    <div class="col-md-6 mb-2">
                                        <input type="text" class="form-control" id="startDate" placeholder="Start Date">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" class="form-control" id="endDate" placeholder="End Date">
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Filter -->
                            <div class="col-md-6 col-lg-3 filter-section">
                                <label class="filter-label">Customer</label>
                                <select class="form-select" id="customerFilter">
                                    <option value="">All Customers</option>
                                </select>
                            </div>

                            <!-- Vehicle Filter -->
                            <div class="col-md-6 col-lg-3 filter-section">
                                <label class="filter-label">Vehicle</label>
                                <select class="form-select" id="vehicleFilter">
                                    <option value="">All Vehicles</option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-6 col-lg-3 filter-section">
                                <label class="filter-label">Service Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="waiting">Waiting</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="quality_check">Quality Check</option>
                                    <option value="completed">Completed</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <!-- Payment Status Filter -->
                            <div class="col-md-6 col-lg-3 filter-section">
                                <label class="filter-label">Payment Status</label>
                                <select class="form-select" id="paymentStatusFilter">
                                    <option value="">All</option>
                                    <option value="paid">Paid</option>
                                    <option value="pending">Pending</option>
                                    <option value="no_invoice">No Invoice</option>
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
                                    <i class="fas fa-wrench"></i>
                                </div>
                                <div class="summary-value" id="totalServices">0</div>
                                <div class="summary-label">Total Services</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="summary-value" id="completedServices">0</div>
                                <div class="summary-label">Completed Services</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="summary-card">
                                <div class="summary-card-icon icon-info">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="summary-value" id="totalRevenue">LKR 0.00</div>
                                <div class="summary-label">Total Revenue</div>
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

                    <!-- Service History Table -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5 class="table-title">Service History Details</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Job #</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Package</th>
                                        <th>Status</th>
                                        <th class="text-end">Amount</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="serviceHistoryTable">
                                    <tr>
                                        <td colspan="9" class="text-center py-4">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-3">
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

    <?php include '../../../includes/main-js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script src="<?php echo BASE_URL; ?>Ajax/js/service_history.js"></script>
</body>
</html>
