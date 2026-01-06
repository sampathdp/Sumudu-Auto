<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Only allow admin users (role_id = 1)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ' . BASE_URL . 'views/Dashboard/');
    exit;
}

// Get company statistics
$stats = Company::getStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Companies Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
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
        .page-header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 12px; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; padding: 1.5rem 2rem; color: #fff; }
        .page-header-content { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
        .page-header .title-section h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .page-header .title-section p { font-size: 0.9rem; opacity: 0.9; margin: 0.25rem 0 0; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; border-radius: 12px; padding: 1.5rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgb(0 0 0 / 0.1); }
        .stat-card .icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-card .icon.primary { background: rgba(99, 102, 241, 0.1); color: var(--primary-color); }
        .stat-card .icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-card .icon.info { background: rgba(8, 145, 178, 0.1); color: var(--info-color); }
        .stat-card .icon.warning { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
        .stat-card .details h3 { font-size: 2rem; font-weight: 700; margin: 0; color: var(--text-dark); }
        .stat-card .details p { font-size: 0.875rem; color: var(--text-muted); margin: 0; }
        .data-card { background: #fff; border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .data-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); background-color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .data-card-header h5 { margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-dark); }
        .table { margin-bottom: 0; font-size: 0.875rem; }
        .table thead th { background-color: var(--bg-light); border-bottom: 2px solid var(--border-color); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); padding: 1rem; white-space: nowrap; }
        .table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid var(--border-color); color: var(--text-dark); }
        .table tbody tr:hover { background-color: var(--bg-light); }
        .badge { font-size: 0.75rem; font-weight: 500; padding: 0.4rem 0.75rem; border-radius: 6px; }
        .btn { font-size: 0.875rem; font-weight: 500; padding: 0.625rem 1.25rem; border-radius: 8px; }
        .btn-primary { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); }
        .btn-icon { width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
        .company-actions { display: flex; gap: 0.5rem; justify-content: flex-end; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 1rem 1.5rem; font-size: 0.875rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem 0.75rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-color) !important; border-color: var(--primary-color) !important; color: #fff !important; border-radius: 8px; }
        .modal-header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; border: none; border-radius: 12px 12px 0 0; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .modal-content { border-radius: 12px; border: none; }
        .modal-footer { background-color: var(--bg-light); border-top: 1px solid var(--border-color); }
        .form-label { font-size: 0.8125rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem; }
        .form-control, .form-select { font-size: 0.875rem; border-color: var(--border-color); border-radius: 8px; padding: 0.625rem 0.875rem; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
        code { background: rgba(99, 102, 241, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8125rem; }
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
                                <h1><i class="fas fa-building me-2"></i>Companies Management</h1>
                                <p>Manage tenant companies and their subscriptions</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon primary"><i class="fas fa-building"></i></div>
                            <div class="details">
                                <h3 id="stat-total"><?php echo $stats['total']; ?></h3>
                                <p>Total Companies</p>
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
                            <div class="icon info"><i class="fas fa-clock"></i></div>
                            <div class="details">
                                <h3 id="stat-trial"><?php echo $stats['trial']; ?></h3>
                                <p>On Trial</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon warning"><i class="fas fa-pause-circle"></i></div>
                            <div class="details">
                                <h3 id="stat-suspended"><?php echo $stats['suspended']; ?></h3>
                                <p>Suspended</p>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table Card -->
                    <div class="data-card">
                        <div class="data-card-header">
                            <h5><i class="fas fa-list me-2"></i>Companies List</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#companyModal">
                                <i class="fas fa-plus me-2"></i>Add Company
                            </button>
                        </div>
                        <div class="data-card-body">
                            <div class="table-responsive">
                                <table id="companiesTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Code</th>
                                            <th>Company Name</th>
                                            <th>Package</th>
                                            <th>Status</th>
                                            <th>Users</th>
                                            <th>Branches</th>
                                            <th>Created</th>
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

    <!-- Company Modal -->
    <div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="companyForm" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="companyModalLabel">
                            <i class="fas fa-building me-2"></i>Add New Company
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="company_id" name="id">
                        
                        <!-- Basic Info -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="company_code" class="form-label">Company Code</label>
                                <input type="text" class="form-control bg-light" id="company_code" readonly 
                                       placeholder="Auto-generated (System)" style="cursor: not-allowed;">
                                <div class="form-text">Unique 6-digit code will be generated automatically</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="company_name" required placeholder="e.g., Acme Corporation">
                            </div>
                        </div>

                        <!-- Initial Admin User -->
                        <div class="row" id="initialAdminSection">
                            <h6 class="mb-3 text-primary"><i class="fas fa-user-shield me-2"></i>Initial Admin User</h6>
                            <div class="col-md-6 mb-3">
                                <label for="admin_username" class="form-label">Admin Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="admin_username" 
                                       placeholder="e.g., admin">
                                <div class="form-text">Will be created with role 'Admin'</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="admin_password" class="form-label">Admin Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="admin_password" 
                                       placeholder="Min 8 characters">
                            </div>
                            <hr class="mb-3">
                        </div>

                        <!-- Package & Status -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="package_type" class="form-label">Package Type</label>
                                <select class="form-select" id="package_type">
                                    <option value="starter">Starter</option>
                                    <option value="business">Business</option>
                                    <option value="pro" selected>Pro</option>
                                    <option value="enterprise">Enterprise</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status">
                                    <option value="trial" selected>Trial</option>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <!-- Limits -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="max_users" class="form-label">Max Users</label>
                                <input type="number" class="form-control" id="max_users" value="25" min="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="max_employees" class="form-label">Max Employees</label>
                                <input type="number" class="form-control" id="max_employees" value="50" min="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="max_branches" class="form-label">Max Branches</label>
                                <input type="number" class="form-control" id="max_branches" value="10" min="1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveCompanyBtn">
                            <i class="fas fa-save me-1"></i>Save Company
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/company.js"></script>
</body>
</html>
