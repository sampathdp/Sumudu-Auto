<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');

// Get inventory item counts
// Get inventory item counts
$companyId = $_SESSION['company_id'] ?? 1;
$stats = InventoryItem::getStats($companyId);
$totalItems = $stats['total'];
$activeItems = $stats['active'];
$lowStockItems = $stats['low_stock'];
$outOfStock = $stats['out_of_stock'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Inventory Items</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --indigo-color: #4f46e5;
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
            border-left: 4px solid var(--indigo-color);
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

        .stat-card .icon.indigo { background: rgba(79, 70, 229, 0.1); color: var(--indigo-color); }
        .stat-card .icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-card .icon.warning { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
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
        .badge-active { background-color: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .badge-inactive { background-color: rgba(100, 116, 139, 0.1); color: var(--secondary-color); }
        .badge-low-stock { background-color: rgba(220, 38, 38, 0.1); color: var(--danger-color); }

        .btn { font-size: 0.875rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 6px; }
        .btn-primary { background-color: var(--indigo-color); border-color: var(--indigo-color); }
        .btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }

        .btn-action {
            width: 32px; height: 32px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 6px; border: 1px solid var(--border-color);
            background: #fff; color: var(--text-muted);
        }
        .btn-action:hover { border-color: var(--indigo-color); color: var(--indigo-color); background: rgba(79, 70, 229, 0.05); }
        .btn-action.danger:hover { border-color: var(--danger-color); color: var(--danger-color); background: rgba(220, 38, 38, 0.05); }

        .item-code { font-weight: 600; color: var(--indigo-color); font-family: monospace; }
        .price { font-weight: 600; }
        .stock-qty { font-weight: 600; }
        .stock-low { color: var(--danger-color); }

        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 1rem 1.5rem; font-size: 0.875rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--indigo-color) !important; border-color: var(--indigo-color) !important; color: #fff !important; border-radius: 6px; }

        .modal-header { background-color: var(--bg-light); border-bottom: 1px solid var(--border-color); }
        .modal-footer { background-color: var(--bg-light); border-top: 1px solid var(--border-color); }
        .form-label { font-size: 0.8125rem; font-weight: 500; color: var(--text-dark); margin-bottom: 0.375rem; }
        .form-control, .form-select { font-size: 0.875rem; border-color: var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .form-control:focus, .form-select:focus { border-color: var(--indigo-color); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
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
                                <h1><i class="fas fa-boxes me-2"></i>Inventory Items</h1>
                                <p>Manage stock items and products</p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon indigo"><i class="fas fa-box"></i></div>
                            <div class="details"><h3><?php echo $totalItems; ?></h3><p>Total Items</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon success"><i class="fas fa-check-circle"></i></div>
                            <div class="details"><h3><?php echo $activeItems; ?></h3><p>Active</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="details"><h3><?php echo $lowStockItems; ?></h3><p>Low Stock</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon danger"><i class="fas fa-times-circle"></i></div>
                            <div class="details"><h3><?php echo $outOfStock; ?></h3><p>Out of Stock</p></div>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <h5><i class="fas fa-list me-2"></i>Items List</h5>
                            <div class="d-flex gap-2">
                                <a href="import.php" class="btn btn-success">
                                    <i class="fas fa-file-import me-2"></i>Import
                                </a>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">
                                    <i class="fas fa-plus me-2"></i>Add Item
                                </button>
                            </div>
                        </div>
                        <div class="data-card-body">
                            <div class="table-responsive">
                                <table id="itemsTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Code</th>
                                            <th>Item Name</th>
                                            <th>Category</th>
                                            <th>Unit</th>
                                            <th>Stock</th>
                                            <th>Price</th>
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

    <!-- Item Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="itemForm" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-box me-2"></i>Add New Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="item_id" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="item_code" class="form-label">Item Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="item_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="item_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id">
                                    <option value="">-- Select Category --</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit_of_measure" class="form-label">Unit of Measure <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="unit_of_measure" placeholder="e.g., pcs, liters, kg" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" step="0.01" class="form-control" id="reorder_level" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="max_stock_level" class="form-label">Max Stock</label>
                                <input type="number" step="0.01" class="form-control" id="max_stock_level">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="unit_cost" class="form-label">Unit Cost</label>
                                <input type="number" step="0.01" class="form-control" id="unit_cost" value="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unit_price" class="form-label">Selling Price</label>
                                <input type="number" step="0.01" class="form-control" id="unit_price" value="0">
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active Status</label>
                                </div>
                            </div>
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

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/inventory_item.js"></script>
</body>
</html>
