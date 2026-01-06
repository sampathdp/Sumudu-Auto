<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');

// Get service package counts
$companyId = $_SESSION['company_id'] ?? 1;
$stats = ServicePackage::getStatistics($companyId);
$totalPackages = $stats['total'];
$activePackages = $stats['active'];
$inactivePackages = $stats['inactive'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Service Package Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --teal-color: #0d9488;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .content { margin-top: 70px; padding: 1.5rem; }
        @media (min-width: 768px) { .content { padding: 2rem; } }

        .page-header {
            background: #fff;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.5rem;
            border-left: 4px solid var(--teal-color);
        }

        .page-header-content {
            display: flex; flex-wrap: wrap; align-items: center;
            justify-content: space-between; gap: 1rem;
        }

        .page-header .title-section h1 { font-size: 1.25rem; font-weight: 600; color: var(--text-dark); margin: 0; }
        .page-header .title-section p { font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card .icon {
            width: 48px; height: 48px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
        }

        .stat-card .icon.teal { background: rgba(13, 148, 136, 0.1); color: var(--teal-color); }
        .stat-card .icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-card .icon.secondary { background: rgba(100, 116, 139, 0.1); color: var(--secondary-color); }

        .stat-card .details h3 { font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-dark); }
        .stat-card .details p { font-size: 0.8125rem; color: var(--text-muted); margin: 0; }

        .data-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .data-card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .data-card-header h5 { margin: 0; font-size: 0.9375rem; font-weight: 600; color: var(--text-dark); }

        .table { margin-bottom: 0; font-size: 0.875rem; }
        .table thead th {
            background-color: var(--bg-light);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600; font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--text-muted); padding: 0.875rem 1rem; white-space: nowrap;
        }
        .table tbody td { padding: 0.875rem 1rem; vertical-align: middle; border-bottom: 1px solid var(--border-color); color: var(--text-dark); }
        .table tbody tr:hover { background-color: var(--bg-light); }

        .badge { font-size: 0.75rem; font-weight: 500; padding: 0.375rem 0.75rem; border-radius: 50px; }
        .badge-active { background-color: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .badge-inactive { background-color: rgba(100, 116, 139, 0.1); color: var(--secondary-color); }

        .btn { font-size: 0.875rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 6px; }
        .btn-primary { background-color: var(--teal-color); border-color: var(--teal-color); }
        .btn-primary:hover { background-color: #0f766e; border-color: #0f766e; }

        .btn-action {
            width: 32px; height: 32px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 6px; border: 1px solid var(--border-color);
            background: #fff; color: var(--text-muted);
        }
        .btn-action:hover { border-color: var(--teal-color); color: var(--teal-color); background: rgba(13, 148, 136, 0.05); }
        .btn-action.danger:hover { border-color: var(--danger-color); color: var(--danger-color); background: rgba(220, 38, 38, 0.05); }

        .package-name { font-weight: 600; color: var(--teal-color); }
        .price { font-weight: 600; }

        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 1rem 1.5rem; font-size: 0.875rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--teal-color) !important; border-color: var(--teal-color) !important; color: #fff !important; border-radius: 6px; }

        .modal-header { background-color: var(--bg-light); border-bottom: 1px solid var(--border-color); }
        .modal-footer { background-color: var(--bg-light); border-top: 1px solid var(--border-color); }
        .form-label { font-size: 0.8125rem; font-weight: 500; color: var(--text-dark); margin-bottom: 0.375rem; }
        .form-control, .form-select { font-size: 0.875rem; border-color: var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .form-control:focus, .form-select:focus { border-color: var(--teal-color); box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1); }
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
                                <h1><i class="fas fa-box-open me-2"></i>Service Package Management</h1>
                                <p>Manage service packages with ease</p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon teal"><i class="fas fa-cubes"></i></div>
                            <div class="details"><h3><?php echo $totalPackages; ?></h3><p>Total Packages</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon success"><i class="fas fa-check-circle"></i></div>
                            <div class="details"><h3><?php echo $activePackages; ?></h3><p>Active</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon secondary"><i class="fas fa-pause-circle"></i></div>
                            <div class="details"><h3><?php echo $inactivePackages; ?></h3><p>Inactive</p></div>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <h5><i class="fas fa-list me-2"></i>Service Packages List</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#packageModal">
                                <i class="fas fa-plus me-2"></i>Add Package
                            </button>
                        </div>
                        <div class="data-card-body">
                            <div class="table-responsive">
                                <table id="packagesTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Package Name</th>
                                            <th>Description</th>
                                            <th>Base Price</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Created At</th>
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

    <!-- Add/Edit Package Modal -->
    <div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="packageForm" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-box-open me-2"></i>Add New Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="package_id" name="id">
                        <div class="mb-3">
                            <label for="package_name" class="form-label">Package Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="package_name" name="package_name" required placeholder="Enter package name">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Enter description (optional)"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="base_price" class="form-label">Base Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="base_price" name="base_price" min="0" step="0.01" required placeholder="0.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="estimated_duration" class="form-label">Duration (min) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" min="1" required placeholder="Minutes">
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Active Package</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Package Modal -->
    <div class="modal fade" id="viewPackageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-box-open me-2"></i>Package Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="packageDetails"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editFromViewPackage"><i class="fas fa-edit me-1"></i>Edit</button>
                    <button type="button" class="btn btn-danger" id="deleteFromViewPackage"><i class="fas fa-trash-alt me-1"></i>Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/service-package.js"></script>
</body>
</html>