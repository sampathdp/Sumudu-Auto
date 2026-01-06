<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');
$companyId = $_SESSION['company_id'] ?? 1;
$allStages = (new ServiceStage())->all($companyId); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Jobs Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #eef2ff;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius-md: 10px;
            --radius-lg: 16px;
        }
        
        body { background-color: var(--bg-light); font-family: 'Inter', system-ui, -apple-system, sans-serif; color: var(--text-dark); }
        .content { margin-top: 70px; padding: 20px; }
        
        /* Modern Card Styles */
        .job-card {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }
        
        .job-card .card-header {
            padding: 1.25rem;
            background: #fff;
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .job-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
            display: block;
            text-decoration: underline;
            text-decoration-color: rgba(67, 97, 238, 0.3);
            text-underline-offset: 4px;
        }
        
        .job-date {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-light);
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        
        .job-card .card-body {
            padding: 1.25rem;
            flex: 1;
        }
        
        .info-row {
            display: flex;
            gap: 12px;
            margin-bottom: 1rem;
        }
        
        .info-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .icon-user { background: #e0f2fe; color: #0284c7; }
        .icon-car { background: #dcfce7; color: #16a34a; }
        
        .info-content h6 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            line-height: 1.4;
        }
        
        .info-content p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .price-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--bg-light);
        }
        
        .service-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .service-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .job-card .card-footer {
            padding: 1rem 1.25rem;
            background: #fff;
            border-top: none;
        }
        
        /* Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.6;
        }
        
        .badge-waiting { background: #f1f5f9; color: #475569; }
        .badge-in_progress { background: #eff6ff; color: #3b82f6; }
        .badge-quality_check { background: #fff7ed; color: #f97316; }
        .badge-completed { background: #ecfdf5; color: #10b981; }
        .badge-delivered { background: #f0fdf4; color: #059669; border: 1px solid #bbf7d0; }
        .badge-cancelled { background: #fef2f2; color: #ef4444; }
        
        /* Progress Bar */
        .progress-container {
            margin-bottom: 1rem;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        
        .custom-progress {
            height: 6px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .custom-progress .bar {
            height: 100%;
            background: var(--primary-color);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        /* Form Elements */
        .status-select {
            font-size: 0.85rem;
            border-color: var(--border-color);
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            background-color: #fff;
        }
        
        .status-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .btn-view-details {
            display: block;
            width: 100%;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none;
            margin-top: 10px;
        }
        
        .btn-view-details:hover {
            background: #3651d4;
            color: white;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
        }
        
        /* Filter Section */
        .filter-card {
            background: #fff;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        
        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="page-header">
                        <div class="page-header-content">
                            <div class="title-section">
                                <h1><i class="fas fa-tasks me-2"></i>Jobs Management</h1>
                                <p>View and manage all service jobs</p>
                            </div>
                            <a href="<?php echo BASE_URL; ?>views/Service/index.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Create New Job</a>
                        </div>
                    </div> 
                    <div class="filter-card">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="dateFilter" class="form-label">Date Filter</label>
                                <select class="form-select" id="dateFilter">
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="this_week">This Week</option>
                                    <option value="last_week">Last Week</option>
                                    <option value="this_month">This Month</option>
                                    <option value="last_month">Last Month</option>
                                    <option value="custom">Custom Range</option>
                                    <option value="all">All Time</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="customDateRange" style="display: none;">
                                <label class="form-label">Custom Date Range</label>
                                <div class="input-group"><input type="date" class="form-control" id="startDate"><span class="input-group-text">to</span><input type="date" class="form-control" id="endDate"></div>
                            </div>
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label">Stage Filter</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Stages</option>
                                    <?php foreach ($allStages as $stage): ?>
                                    <option value="<?php echo $stage['id']; ?>"><?php echo htmlspecialchars($stage['stage_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2"><button id="applyFilters" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button></div>
                        </div>
                    </div>
                    <div class="row" id="jobsContainer">
                        <div class="col-12 text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 mb-0 text-muted">Loading jobs...</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Job Stage</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="job_id" name="id">
                        <div class="mb-3"><label for="status_select" class="form-label">Select Stage</label><select class="form-select" id="status_select" name="stage_id" required><option value="">Choose stage...</option><?php foreach ($allStages as $stage): ?><option value="<?php echo $stage['id']; ?>"><?php echo htmlspecialchars($stage['stage_name']); ?></option><?php endforeach; ?></select></div>
                        <small class="text-muted">Progress will be calculated automatically based on stage</small>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="updateStatusBtn">Update Status</button></div>
            </div>
        </div>
    </div>
    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/service.js"></script>
</body>
</html>