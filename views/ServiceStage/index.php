<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');

// Get stage counts
$companyId = $_SESSION['company_id'] ?? 1;
$stats = ServiceStage::getStats($companyId);
$totalStages = $stats['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Service Stage Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        body { background-color: var(--bg-light); font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .content { margin-top: 70px; padding: 1.5rem; }
        @media (min-width: 768px) { .content { padding: 2rem; } }
        .page-header { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; padding: 1.25rem 1.5rem; border-left: 4px solid var(--primary-color); }
        .page-header-content { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
        .page-header .title-section h1 { font-size: 1.25rem; font-weight: 600; color: var(--text-dark); margin: 0; }
        .page-header .title-section p { font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; border-radius: 8px; padding: 1.25rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .stat-card .icon.primary { background: rgba(67, 97, 238, 0.1); color: var(--primary-color); }
        .stat-card .details h3 { font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-dark); }
        .stat-card .details p { font-size: 0.8125rem; color: var(--text-muted); margin: 0; }
        .data-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .data-card-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); background-color: var(--bg-light); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .data-card-header h5 { margin: 0; font-size: 0.9375rem; font-weight: 600; color: var(--text-dark); }
        .table { margin-bottom: 0; font-size: 0.875rem; }
        .table thead th { background-color: var(--bg-light); border-bottom: 2px solid var(--border-color); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); padding: 0.875rem 1rem; white-space: nowrap; }
        .table tbody td { padding: 0.875rem 1rem; vertical-align: middle; border-bottom: 1px solid var(--border-color); color: var(--text-dark); }
        .table tbody tr:hover { background-color: var(--bg-light); }
        .btn { font-size: 0.875rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 6px; }
        .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { background-color: -var(--primary-hover); border-color: var(--primary-hover); }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-color); background: #fff; color: var(--text-muted); }
        .btn-action:hover { border-color: var(--primary-color); color: var(--primary-color); background: rgba(67, 97, 238, 0.05); }
        .btn-action.danger:hover { border-color: var(--danger-color); color: var(--danger-color); background: rgba(239, 68, 68, 0.05); }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 1rem 1.5rem; font-size: 0.875rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-color) !important; border-color: var(--primary-color) !important; color: #fff !important; border-radius: 6px; }
        .modal-header { background-color: var(--bg-light); border-bottom: 1px solid var(--border-color); }
        .modal-footer { background-color: var(--bg-light); border-top: 1px solid var(--border-color); }
        .form-label { font-size: 0.8125rem; font-weight: 500; color: var(--text-dark); margin-bottom: 0.375rem; }
        .form-control, .form-select { font-size: 0.875rem; border-color: var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1); }
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
                                <h1><i class="fas fa-list-ol me-2"></i>Service Stage Management</h1>
                                <p>Manage service workflows and process steps</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon primary"><i class="fas fa-layer-group"></i></div>
                            <div class="details">
                                <h3><?php echo $totalStages; ?></h3>
                                <p>Total Stages</p>
                            </div>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <h5><i class="fas fa-list me-2"></i>Stages List</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stageModal"><i class="fas fa-plus me-2"></i>Add New Stage</button>
                        </div>
                        <div class="data-card-body">
                            <div class="table-responsive">
                                <table id="stagesTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Stage Name</th>
                                            <th>Order</th>
                                            <th>Icon</th>
                                            <th>Est. Duration</th>
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

    <!-- Add/Edit Stage Modal -->
    <div class="modal fade" id="stageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="stageForm" class="needs-validation" novalidate>
                    <div class="modal-header"><h5 class="modal-title" id="stageModalLabel"><i class="fas fa-plus-circle me-2"></i>Add New Stage</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" id="stage_id" name="id">
                        
                        <div class="mb-3">
                            <label for="stage_name" class="form-label">Stage Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="stage_name" name="stage_name" required placeholder="e.g. Initial Inspection">
                            <div class="invalid-feedback">Please provide a stage name.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stage_order" class="form-label">Order <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stage_order" name="stage_order" min="1" required placeholder="e.g. 1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="icon" class="form-label">Icon (FontAwesome/Emoji)</label>
                                <input type="text" class="form-control" id="icon" name="icon" placeholder="e.g. fas fa-check">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="estimated_duration" class="form-label">Estimated Duration (minutes)</label>
                            <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" min="0" placeholder="e.g. 30">
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="saveStageBtn"><i class="fas fa-save me-1"></i>Save</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Stage Modal -->
    <div class="modal fade" id="viewStageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="viewStageModalLabel">Stage Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div id="stageDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editFromViewStage"><i class="fas fa-edit me-1"></i>Edit</button>
                    <button type="button" class="btn btn-danger" id="deleteFromViewStage"><i class="fas fa-trash me-1"></i>Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/service-stage.js"></script>
</body>
</html>