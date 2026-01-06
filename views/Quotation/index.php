<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Quotation Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --info-color: #0891b2;
            --purple-color: #7c3aed;
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
            border-left: 4px solid var(--purple-color);
        }

        .page-header-content {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
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

        .stat-card .icon.purple { background: rgba(124, 58, 237, 0.1); color: var(--purple-color); }
        .stat-card .icon.warning { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
        .stat-card .icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-card .icon.danger { background: rgba(220, 38, 38, 0.1); color: var(--danger-color); }

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
        .badge-pending { background-color: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
        .badge-accepted { background-color: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .badge-rejected { background-color: rgba(220, 38, 38, 0.1); color: var(--danger-color); }

        .btn { font-size: 0.875rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 6px; }
        .btn-primary { background-color: var(--purple-color); border-color: var(--purple-color); }
        .btn-primary:hover { background-color: #6d28d9; border-color: #6d28d9; }

        .btn-action {
            width: 32px; height: 32px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 6px; border: 1px solid var(--border-color);
            background: #fff; color: var(--text-muted);
        }
        .btn-action:hover { border-color: var(--purple-color); color: var(--purple-color); background: rgba(124, 58, 237, 0.05); }
        .btn-action.danger:hover { border-color: var(--danger-color); color: var(--danger-color); background: rgba(220, 38, 38, 0.05); }

        .quotation-number { font-weight: 600; color: var(--purple-color); }
        .amount { font-weight: 600; }

        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 1rem 1.5rem; font-size: 0.875rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--purple-color) !important; border-color: var(--purple-color) !important; color: #fff !important; border-radius: 6px; }

        .modal-header { background-color: var(--bg-light); border-bottom: 1px solid var(--border-color); }
        .modal-footer { background-color: var(--bg-light); border-top: 1px solid var(--border-color); }
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
                                <h1><i class="fas fa-file-contract me-2"></i>Quotation Management</h1>
                                <p>Manage and track quotations</p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon purple"><i class="fas fa-file-alt"></i></div>
                            <div class="details"><h3 id="totalQuotations">0</h3><p>Total Quotations</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon warning"><i class="fas fa-clock"></i></div>
                            <div class="details"><h3 id="pendingQuotations">0</h3><p>Pending</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon success"><i class="fas fa-check-circle"></i></div>
                            <div class="details"><h3 id="acceptedQuotations">0</h3><p>Accepted</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon danger"><i class="fas fa-times-circle"></i></div>
                            <div class="details"><h3 id="rejectedQuotations">0</h3><p>Rejected</p></div>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <h5><i class="fas fa-list me-2"></i>Quotations List</h5>
                            <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Create Quotation</a>
                        </div>
                        <div class="data-card-body">
                            <div class="table-responsive">
                                <table id="quotationsTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Quotation #</th>
                                            <th>Customer</th>
                                            <th>Total</th>
                                            <th>Valid Until</th>
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

    <!-- View Quotation Modal -->
    <div class="modal fade" id="viewQuotationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-contract me-2"></i>Quotation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="quotationContent">
                        <div class="d-flex justify-content-between mb-4">
                            <div>
                                <h4 id="view_quotation_number" class="fw-bold mb-1" style="color: var(--purple-color);"></h4>
                                <span id="view_status_badge" class="badge"></span>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Created Date</small>
                                <span id="view_created_at" class="fw-bold"></span>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <h6 class="text-uppercase text-muted mb-3" style="font-size: 0.75rem;">Customer Info</h6>
                                    <p class="mb-1 fw-bold" id="view_customer_name"></p>
                                    <p class="mb-0 text-muted" id="view_customer_mobile"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <h6 class="text-uppercase text-muted mb-3" style="font-size: 0.75rem;">Validity</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Valid Until:</span>
                                        <span class="fw-bold" id="view_valid_until"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-center" width="100">Type</th>
                                        <th class="text-center" width="80">Qty</th>
                                        <th class="text-end" width="120">Unit</th>
                                        <th class="text-end" width="120">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="view_quotation_items"></tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><td class="text-end">Subtotal:</td><td class="text-end fw-bold" id="view_subtotal"></td></tr>
                                    <tr><td class="text-end">Tax:</td><td class="text-end fw-bold" id="view_tax"></td></tr>
                                    <tr><td class="text-end">Discount:</td><td class="text-end text-danger fw-bold" id="view_discount"></td></tr>
                                    <tr class="border-top"><td class="text-end fs-5 fw-bold">Total:</td><td class="text-end fs-5 fw-bold" style="color: var(--purple-color);" id="view_total"></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="view_print_btn"><i class="fas fa-print me-2"></i>Print Quotation</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/quotation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
