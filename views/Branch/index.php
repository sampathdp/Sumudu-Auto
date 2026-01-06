<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Only allow admin users (role_id = 1)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ' . BASE_URL . 'views/Dashboard/');
    exit;
}

// Get branch statistics
$companyId = $_SESSION['company_id'] ?? 1;
$stats = Branch::getStats($companyId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Branch Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #059669;
            --primary-hover: #047857;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --info-color: #0891b2;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        body { background-color: var(--bg-light); font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .content { margin-top: 70px; padding: 1.5rem; }
        @media (min-width: 768px) { .content { padding: 2rem; } }
        .page-header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius: 12px; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; padding: 1.5rem 2rem; color: #fff; }
        .page-header-content { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
        .page-header .title-section h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .page-header .title-section p { font-size: 0.9rem; opacity: 0.9; margin: 0.25rem 0 0; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; border-radius: 12px; padding: 1.25rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgb(0 0 0 / 0.1); }
        .stat-card .icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .stat-card .icon.primary { background: rgba(5, 150, 105, 0.1); color: var(--primary-color); }
        .stat-card .icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-card .icon.secondary { background: rgba(100, 116, 139, 0.1); color: var(--secondary-color); }
        .stat-card .icon.warning { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
        .stat-card .details h3 { font-size: 1.75rem; font-weight: 700; margin: 0; color: var(--text-dark); }
        .stat-card .details p { font-size: 0.8125rem; color: var(--text-muted); margin: 0; }
        .data-card { background: #fff; border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .data-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); background-color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .data-card-header h5 { margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-dark); }
        .table { margin-bottom: 0; font-size: 0.875rem; }
        .table thead th { background-color: var(--bg-light); border-bottom: 2px solid var(--border-color); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); padding: 1rem; white-space: nowrap; }
        .table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid var(--border-color); color: var(--text-dark); }
        .table tbody tr:hover { background-color: var(--bg-light); }
        .badge { font-size: 0.75rem; font-weight: 500; padding: 0.4rem 0.75rem; border-radius: 6px; }
        .btn { font-size: 0.875rem; font-weight: 500; padding: 0.625rem 1.25rem; border-radius: 8px; }
        .btn-primary { background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #047857 0%, #059669 100%); }
        .btn-icon { width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
        .branch-actions { display: flex; gap: 0.5rem; justify-content: flex-end; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 1rem 1.5rem; font-size: 0.875rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem 0.75rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-color) !important; border-color: var(--primary-color) !important; color: #fff !important; border-radius: 8px; }
        .modal-header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #fff; border: none; border-radius: 12px 12px 0 0; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .modal-content { border-radius: 12px; border: none; }
        .modal-footer { background-color: var(--bg-light); border-top: 1px solid var(--border-color); }
        .form-label { font-size: 0.8125rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem; }
        .form-control, .form-select { font-size: 0.875rem; border-color: var(--border-color); border-radius: 8px; padding: 0.625rem 0.875rem; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
        code { background: rgba(5, 150, 105, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8125rem; color: var(--primary-color); }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="page-header-content">
                            <div class="title-section">
                                <h1><i class="fas fa-code-branch me-2"></i>Branch Management</h1>
                                <p>Manage company branches and locations</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon primary"><i class="fas fa-code-branch"></i></div>
                            <div class="details">
                                <h3 id="stat-total"><?php echo $stats['total']; ?></h3>
                                <p>Total Branches</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon success"><i class="fas fa-check-circle"></i></div>
                            <div class="details">
                                <h3 id="stat-active"><?php echo $stats['active']; ?></h3>
                                <p>Active</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon secondary"><i class="fas fa-pause-circle"></i></div>
                            <div class="details">
                                <h3 id="stat-inactive"><?php echo $stats['inactive']; ?></h3>
                                <p>Inactive</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon warning"><i class="fas fa-star"></i></div>
                            <div class="details">
                                <h3 id="stat-main"><?php echo $stats['main']; ?></h3>
                                <p>Main Offices</p>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table Card -->
                    <div class="data-card">
                        <div class="data-card-header">
                            <h5><i class="fas fa-list me-2"></i>Branches List</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#branchModal">
                                <i class="fas fa-plus me-2"></i>Add Branch
                            </button>
                        </div>
                        <div class="data-card-body">
                            <div class="table-responsive">
                                <table id="branchesTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Code</th>
                                            <th>Branch Name</th>
                                            <th>Company</th>
                                            <th>Phone</th>
                                            <th>Employees</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Modal -->
    <div class="modal fade" id="branchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="branchForm" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="branchModalLabel">
                            <i class="fas fa-code-branch me-2"></i>Add New Branch
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="branch_id" name="id">
                        
                        <!-- Company Selection -->
                        <div class="mb-3">
                            <label for="company_id" class="form-label">Company <span class="text-danger">*</span></label>
                            <select class="form-select" id="company_id" required>
                                <option value="">Select Company</option>
                            </select>
                        </div>

                        <!-- Basic Info -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="branch_code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_code" required 
                                       placeholder="e.g., MAIN, BR001" style="text-transform: uppercase;">
                                <div class="form-text">Unique code within the company</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="branch_name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_name" required placeholder="e.g., Main Branch">
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" placeholder="e.g., +94 11 234 5678">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" placeholder="e.g., branch@company.com">
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" rows="2" placeholder="Full address"></textarea>
                        </div>

                        <!-- Switches -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_main">
                                    <label class="form-check-label" for="is_main">
                                        <i class="fas fa-star text-warning me-1"></i>Main Branch (Head Office)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active Status</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveBranchBtn">
                            <i class="fas fa-save me-1"></i>Save Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/branch.js"></script>
</body>
</html>
