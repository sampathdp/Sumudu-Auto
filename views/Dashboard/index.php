<?php
// Include required files
require_once __DIR__ . '/../../classes/Includes.php';

// Dashboard is accessible to all logged-in users
requireLogin();

// Initialize Service class
$service = new Service();

// Get list of recent services
$companyId = $_SESSION['company_id'] ?? 1;
$branchId = $_SESSION['branch_id'] ?? null;

// Get services data
$todaysServices = $service->all($companyId, $branchId, 'today');
$currentMonth = date('Y-m');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$monthServices = $service->getByDateRange($companyId, $monthStart, $monthEnd, $branchId);
$allServices = $service->all($companyId, $branchId);

// Calculate statistics
$totalServices = count($allServices);
$todayCount = count($todaysServices);
$monthCount = count($monthServices);

// Calculate revenue
$totalRevenue = $monthRevenue = $todayRevenue = 0;
$today = date('Y-m-d');
$currentMonth = date('Y-m');

foreach ($allServices as $s) {
    $totalRevenue += $s['total_amount'];
    $serviceDate = date('Y-m-d', strtotime($s['created_at']));
    $serviceMonth = date('Y-m', strtotime($s['created_at']));
    
    if ($serviceDate === $today) {
        $todayRevenue += $s['total_amount'];
    }
    if ($serviceMonth === $currentMonth) {
        $monthRevenue += $s['total_amount'];
    }
}

// Get services by status
$statusCounts = array_fill_keys(['waiting', 'in_progress', 'completed', 'delivered', 'cancelled'], 0);
foreach ($allServices as $s) {
    if (isset($statusCounts[$s['status']])) {
        $statusCounts[$s['status']]++;
    }
}

// Get recent services
$recentServices = array_slice($allServices, 0, 5);

// Calculate month-over-month changes
$lastMonthStart = date('Y-m-01', strtotime('last month'));
$lastMonthEnd = date('Y-m-t', strtotime('last month'));
$lastMonthServices = $service->getByDateRange($companyId, $lastMonthStart, $lastMonthEnd, $branchId);
$lastMonthCount = count($lastMonthServices);

$serviceChange = $lastMonthCount > 0 ? round((($monthCount - $lastMonthCount) / $lastMonthCount) * 100, 1) : 100;

$lastMonthRevenue = array_sum(array_column($lastMonthServices, 'total_amount'));
$revenueChange = $lastMonthRevenue > 0 ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) : 100;

// Chart data function
function getChartData($period, $allServices) {
    $today = new DateTime();
    $data = ['labels' => [], 'values' => []];

    if ($period === 'today') {
        $data['labels'] = array_map(function($h) { return $h . ':00'; }, range(0, 23));
        $data['values'] = array_fill(0, 24, 0);
        
        foreach ($allServices as $service) {
            $serviceDate = new DateTime($service['created_at']);
            if ($serviceDate->format('Y-m-d') === $today->format('Y-m-d')) {
                $hour = (int)$serviceDate->format('H');
                $data['values'][$hour]++;
            }
        }
    } elseif ($period === 'week') {
        $data['labels'] = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $data['values'] = array_fill(0, 7, 0);
        
        foreach ($allServices as $service) {
            $serviceDate = new DateTime($service['created_at']);
            $dayOfWeek = (int)$serviceDate->format('N') - 1;
            if ($dayOfWeek >= 0 && $dayOfWeek < 7) {
                $data['values'][$dayOfWeek]++;
            }
        }
    } elseif ($period === 'month') {
        $data['labels'] = array_map(function($w) { return 'Week ' . $w; }, range(1, 5));
        $data['values'] = array_fill(0, 5, 0);
        
        foreach ($allServices as $service) {
            $serviceDate = new DateTime($service['created_at']);
            $weekOfMonth = (int)ceil($serviceDate->format('d') / 7) - 1;
            if ($weekOfMonth >= 0 && $weekOfMonth < 5) {
                $data['values'][$weekOfMonth]++;
            }
        }
    }

    return $data;
}

$chartData = getChartData('week', $allServices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo APP_NAME; ?> - ServiceFlow Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --success-light: #34d399;
            --warning: #f59e0b;
            --warning-light: #fbbf24;
            --danger: #ef4444;
            --danger-light: #f87171;
            --info: #3b82f6;
            --info-light: #60a5fa;
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --bg-gradient-start: #f0f9ff;
            --bg-gradient-end: #e0f2fe;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --border-light: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .page-inner { padding: 2rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        /* Cards */
        .stat-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 1.75rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-light), var(--primary));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before { opacity: 1; }
        .stat-card:hover {
            box-shadow: var(--shadow-xl);
            transform: translateY(-4px);
            border-color: var(--primary-light);
        }

        .stat-card-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: var(--text-primary);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-change {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .icon-primary { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .icon-success { background: rgba(34, 197, 94, 0.1); color: var(--success); }
        .icon-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .icon-info { background: rgba(59, 130, 246, 0.1); color: var(--info); }

        /* Chart Card */
        .chart-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.75rem;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }

        .chart-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-light);
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(180deg, var(--primary), var(--primary-light));
            border-radius: 2px;
        }

        .chart-container {
            position: relative;
            height: 280px;
        }

        /* Table */
        .table-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 1.75rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }

        .table-card:hover { box-shadow: var(--shadow-md); }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-light);
        }

        .table {
            margin-bottom: 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead th {
            border-bottom: 2px solid var(--border-color);
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            background: var(--bg-gradient-start);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.02), rgba(99, 102, 241, 0.05));
            transform: scale(1.01);
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

        .status-badge.active { background: rgba(34, 197, 94, 0.1); color: var(--success); }
        .status-badge.pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .status-badge.completed { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .status-badge.cancelled { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .status-dot.active { background: var(--success); }
        .status-dot.pending { background: var(--warning); }

        /* QR Code Card */
        .qr-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .qr-container {
            width: 180px;
            height: 180px;
            margin: 1.5rem auto;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed var(--border-color);
        }

        #qrcode { display: flex; justify-content: center; align-items: center; }
        #qrcode img { border-radius: 8px; }

        /* Quick Actions */
        .quick-actions-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: var(--bg-card);
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: var(--text-primary);
        }

        .action-btn:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .action-btn i {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .action-btn span {
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Buttons */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before { left: 100%; }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            border-color: var(--primary-dark);
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-sm { padding: 0.5rem 1rem; font-size: 0.8125rem; }
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }

        /* Form */
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .input-group .btn { border-radius: 0 8px 8px 0; }
        .chart-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 10px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 10;
            font-size: 14px;
            color: #4b5563;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .page-inner { padding: 1rem; }
            .stat-card { margin-bottom: 1rem; }
        }

        /* Quick Nav Cards */
        .quick-nav-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .quick-nav-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .quick-nav-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .quick-nav-card:hover .quick-nav-icon {
            transform: scale(1.1);
        }

        .quick-nav-content {
            flex: 1;
        }

        .quick-nav-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            display: block;
        }

        .quick-nav-desc {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0;
        }

        .quick-nav-arrow {
            color: var(--text-muted);
            transition: transform 0.3s ease;
        }

        .quick-nav-card:hover .quick-nav-arrow {
            transform: translateX(4px);
            color: var(--primary);
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
                    <!-- Quick Navigation -->
                    <div class="row mb-4" style="margin-top: 70px;">

                        <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                            <a href="<?php echo BASE_URL; ?>views/Invoice/index.php" class="quick-nav-card">
                                <div class="quick-nav-icon icon-success">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <div class="quick-nav-content">
                                    <span class="quick-nav-title">Invoices</span>
                                    <p class="quick-nav-desc">View invoices</p>
                                </div>
                                <i class="fas fa-chevron-right quick-nav-arrow"></i>
                            </a>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                            <a href="<?php echo BASE_URL; ?>views/InventoryItem/index.php" class="quick-nav-card">
                                <div class="quick-nav-icon icon-warning">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div class="quick-nav-content">
                                    <span class="quick-nav-title">Inventory</span>
                                    <p class="quick-nav-desc">Stock management</p>
                                </div>
                                <i class="fas fa-chevron-right quick-nav-arrow"></i>
                            </a>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <a href="<?php echo BASE_URL; ?>views/Reports/index.php" class="quick-nav-card">
                                <div class="quick-nav-icon icon-info">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="quick-nav-content">
                                    <span class="quick-nav-title">Reports</span>
                                    <p class="quick-nav-desc">View analytics</p>
                                </div>
                                <i class="fas fa-chevron-right quick-nav-arrow"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <h1 class="page-title"><i class="fas fa-car-side me-2"></i>Service Analytics</h1>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Overview -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <div class="stat-card">
                                <div class="stat-card-content">
                                    <div class="stat-info">
                                        <p class="stat-label">Active Services</p>
                                        <h3><?php echo $totalServices; ?></h3>
                                        <div class="stat-change text-success">
                                            <i class="fas fa-arrow-up"></i>
                                            <span><?php echo $serviceChange; ?>% from last month</span>
                                        </div>
                                    </div>
                                    <div class="stat-icon icon-primary">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <div class="stat-card">
                                <div class="stat-card-content">
                                    <div class="stat-info">
                                        <p class="stat-label">Today's Services</p>
                                        <h3><?php echo $todayCount; ?></h3>
                                        <div class="stat-change text-muted">
                                            <span>as of <?php echo date('h:i A'); ?></span>
                                        </div>
                                    </div>
                                    <div class="stat-icon icon-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <div class="stat-card-content">
                                    <div class="stat-info">
                                        <p class="stat-label">This Month</p>
                                        <h3><?php echo $monthCount; ?></h3>
                                        <div class="stat-change <?php echo $serviceChange >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <i class="fas fa-arrow-<?php echo $serviceChange >= 0 ? 'up' : 'down'; ?>"></i>
                                            <span><?php echo abs($serviceChange); ?>% from last month</span>
                                        </div>
                                    </div>
                                    <div class="stat-icon icon-warning">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <div class="stat-card-content">
                                    <div class="stat-info">
                                        <span class="stat-label">Monthly Income</span>
                                        <h3>LKR <?php echo number_format($monthRevenue, 2); ?></h3>
                                        <div class="stat-change <?php echo $revenueChange >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <i class="fas fa-arrow-<?php echo $revenueChange >= 0 ? 'up' : 'down'; ?>"></i>
                                            <span><?php echo abs($revenueChange); ?>% from last month</span>
                                        </div>
                                    </div>
                                    <div class="stat-icon icon-success">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Activity Chart -->
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="chart-title">Service Activity</h5>
                                    <div class="dropdown period-filter">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            This Week
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" data-period="today">Today</a></li>
                                            <li><a class="dropdown-item active" href="#" data-period="week">This Week</a></li>
                                            <li><a class="dropdown-item" href="#" data-period="month">This Month</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>

                            <!-- Active Services Table -->
                            <div class="table-card">
                                <div class="table-header">
                                    <h5 class="chart-title">Active Services</h5>
                                    <a href="<?php echo BASE_URL; ?>views/Service/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Job ID</th>
                                                <th>Customer</th>
                                                <th>Vehicle</th>
                                                <th>Status</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recentServices) > 0): ?>
                                                <?php foreach ($recentServices as $service): ?>
                                                    <tr>
                                                        <td>#<?php echo htmlspecialchars($service['job_number']); ?></td>
                                                        <td><?php echo htmlspecialchars($service['customer_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars(($service['make'] ?? '') . ' ' . ($service['model'] ?? '')); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusMap = [
                                                                'in_progress' => ['class' => 'active', 'text' => 'In Progress'],
                                                                'completed' => ['class' => 'completed', 'text' => 'Completed'],
                                                                'waiting' => ['class' => 'pending', 'text' => 'Waiting'],
                                                                'cancelled' => ['class' => 'cancelled', 'text' => 'Cancelled']
                                                            ];
                                                            $status = $statusMap[$service['status']] ?? ['class' => 'pending', 'text' => ucfirst($service['status'])];
                                                            ?>
                                                            <span class="status-badge <?php echo $status['class']; ?>">
                                                                <span class="status-dot"></span> <?php echo $status['text']; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">LKR <?php echo number_format($service['total_amount'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4">No recent services found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            <!-- QR Code -->
                            <div class="qr-card">
                                <h5 class="chart-title mb-3">Book Appointment QR</h5>
                                <div class="qr-container">
                                    <div id="qrcode"></div>
                                </div>
                                <p class="text-secondary small mb-3">Scan to book service appointment</p>

                                <div class="mb-3">
                                    <label class="form-label small text-muted">Customer Mobile Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">+94</span>
                                        <input type="text" class="form-control" id="customerMobile" placeholder="7XXXXXXXX" maxlength="9">
                                    </div>
                                    <div class="form-text small">Enter customer's mobile number to generate booking QR</div>
                                </div>

                                <button class="btn btn-primary w-100 mb-2" id="generateQR">
                                    <i class="fas fa-qrcode me-2"></i>Generate Booking QR
                                </button>

                                <div class="mb-3">
                                    <label class="form-label small text-muted">Booking Link</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="bookingLink" readonly placeholder="Generate QR first">
                                        <button class="btn btn-outline-primary" type="button" id="copyLink" disabled>
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <button class="btn btn-success w-100" id="shareWhatsApp" disabled>
                                    <i class="fab fa-whatsapp me-2"></i>Share via WhatsApp
                                </button>
                            </div>

                            <!-- Quick Actions -->
                            <div class="quick-actions-card">
                                <h5 class="chart-title mb-3">Quick Actions</h5>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <a href="<?php echo BASE_URL; ?>views/Service/index.php" class="action-btn">
                                            <i class="fas fa-plus-circle"></i>
                                            <span>New Service</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="<?php echo BASE_URL; ?>views/Customer/index.php" class="action-btn">
                                            <i class="fas fa-user-plus"></i>
                                            <span>Add Customer</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="<?php echo BASE_URL; ?>views/Service%20Package/index.php" class="action-btn">
                                            <i class="fas fa-box"></i>
                                            <span>New packages</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="<?php echo BASE_URL; ?>views/ScheduleJob/index.php" class="action-btn">
                                            <i class="fas fa-calendar-plus"></i>
                                            <span>Schedule</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <script>
        // Chart Configuration
        const ctx = document.getElementById('activityChart').getContext('2d');
        let chart;

        // Initialize chart
        const initialData = <?php echo json_encode($chartData); ?>;
        
        const chartConfig = {
            type: 'line',
            data: {
                labels: initialData.labels,
                datasets: [{
                    label: 'Services',
                    data: initialData.values,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#0f172a',
                        bodyColor: '#64748b',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: ctx => `${ctx.parsed.y} service${ctx.parsed.y !== 1 ? 's' : ''}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { color: '#64748b', font: { size: 11 } }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { color: '#64748b', font: { size: 11 } }
                    }
                }
            }
        };

        chart = new Chart(ctx, chartConfig);

        // Period filter
        document.querySelectorAll('.period-filter .dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const period = this.getAttribute('data-period');
                const button = this.closest('.dropdown').querySelector('.dropdown-toggle');
                
                const periodText = {
                    'today': 'Today',
                    'week': 'This Week',
                    'month': 'This Month'
                };
                
                button.textContent = periodText[period];
                
                document.querySelectorAll('.period-filter .dropdown-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                updateChart(period);
            });
        });

        function updateChart(period) {
            const chartData = {
                'today': <?php echo json_encode(getChartData('today', $allServices)); ?>,
                'week': <?php echo json_encode(getChartData('week', $allServices)); ?>,
                'month': <?php echo json_encode(getChartData('month', $allServices)); ?>
            };
            
            const data = chartData[period];
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.update();
        }

        // QR Code Generation for Booking
        let qrcodeInstance = null;
        
        document.getElementById('generateQR').addEventListener('click', function() {
            const mobileInput = document.getElementById('customerMobile');
            const mobileNumber = mobileInput.value.trim();
            const bookingLinkInput = document.getElementById('bookingLink');
            const copyBtn = document.getElementById('copyLink');
            const shareWhatsAppBtn = document.getElementById('shareWhatsApp');
            const qrcodeDiv = document.getElementById('qrcode');
            
            // Validate mobile number
            if (!mobileNumber || !/^\d{9}$/.test(mobileNumber)) {
                alert('Please enter a valid 9-digit mobile number');
                mobileInput.focus();
                return;
            }
            
            // Generate booking URL
            const baseUrl = window.location.origin + '/views/Booking/index.php';
            const bookingUrl = baseUrl + '?mobile=' + encodeURIComponent('94' + mobileNumber);
            const fullMobileNumber = '94' + mobileNumber;
            const whatsappMessage = encodeURIComponent('Book your service appointment here: ' + bookingUrl + '\n\nScan the QR code or click the link to schedule your service!');
            const whatsappUrl = `https://wa.me/${fullMobileNumber}?text=${whatsappMessage}`;
            
            // Clear existing QR code
            qrcodeDiv.innerHTML = '';
            
            // Generate new QR code
            try {
                qrcodeInstance = new QRCode(qrcodeDiv, {
                    text: bookingUrl,
                    width: 150,
                    height: 150,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
                
                // Update UI
                bookingLinkInput.value = bookingUrl;
                copyBtn.disabled = false;
                shareWhatsAppBtn.disabled = false;
                
                // Set WhatsApp button action
                shareWhatsAppBtn.onclick = () => window.open(whatsappUrl, '_blank');
                
                // Success feedback
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check me-2"></i>QR Generated';
                this.classList.remove('btn-primary');
                this.classList.add('btn-success');
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-primary');
                }, 2000);
                
            } catch (error) {
                console.error('QR Generation Error:', error);
                alert('Failed to generate QR code. Please try again.');
            }
        });

        // Copy link functionality
        document.getElementById('copyLink').addEventListener('click', function() {
            const bookingLinkInput = document.getElementById('bookingLink');
            
            if (bookingLinkInput.value) {
                bookingLinkInput.select();
                bookingLinkInput.setSelectionRange(0, 99999); // For mobile
                
                try {
                    document.execCommand('copy');
                    
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-success');
                    
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-primary');
                    }, 2000);
                } catch (err) {
                    console.error('Copy failed:', err);
                    alert('Failed to copy link');
                }
            }
        });

        // Allow only numbers in mobile input
        document.getElementById('customerMobile').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });
    </script>
</body>
</html>