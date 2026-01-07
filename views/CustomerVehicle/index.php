<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');

$companyId = $_SESSION['company_id'] ?? 1;
$customerStats = Customer::getStats($companyId);
$vehicleStats = Vehicle::getStats($companyId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Customer & Vehicle Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #28a745;
            --primary-hover: #218838;
            --secondary-color: #64748b;
            --success-color: #059669;
            --info-color: #0ea5e9;
            --danger-color: #dc2626;
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
        .stat-card .icon.primary { background: rgba(40, 167, 69, 0.1); color: var(--primary-color); }
        .stat-card .icon.info { background: rgba(14, 165, 233, 0.1); color: var(--info-color); }
        .stat-card .icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-card .details h3 { font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-dark); }
        .stat-card .details p { font-size: 0.8125rem; color: var(--text-muted); margin: 0; }
        .data-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1.5rem; }
        .data-card-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); background-color: var(--bg-light); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .data-card-header h5 { margin: 0; font-size: 0.9375rem; font-weight: 600; color: var(--text-dark); }
        .table { margin-bottom: 0; font-size: 0.875rem; }
        .table thead th { background-color: var(--bg-light); border-bottom: 2px solid var(--border-color); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); padding: 0.875rem 1rem; white-space: nowrap; }
        .table tbody td { padding: 0.875rem 1rem; vertical-align: middle; border-bottom: 1px solid var(--border-color); color: var(--text-dark); }
        .table tbody tr:hover { background-color: var(--bg-light); }
        .btn { font-size: 0.875rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 6px; }
        .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { background-color: var(--primary-hover); border-color: var(--primary-hover); }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-color); background: #fff; color: var(--text-muted); }
        .btn-action:hover { border-color: var(--primary-color); color: var(--primary-color); background: rgba(40, 167, 69, 0.05); }
        .btn-action.danger:hover { border-color: var(--danger-color); color: var(--danger-color); background: rgba(220, 38, 38, 0.05); }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 1rem 1.5rem; font-size: 0.875rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-color) !important; border-color: var(--primary-color) !important; color: #fff !important; border-radius: 6px; }
        .modal-header.bg-success { background-color: var(--primary-color) !important; }
        .modal-footer { background-color: var(--bg-light); border-top: 1px solid var(--border-color); }
        .form-label { font-size: 0.8125rem; font-weight: 500; color: var(--text-dark); margin-bottom: 0.375rem; }
        .form-control, .form-select { font-size: 0.875rem; border-color: var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1); }
        .section-title { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); padding-bottom: 0.5rem; margin-bottom: 1rem; border-bottom: 2px solid; }
        .section-title.customer { color: var(--info-color); border-color: var(--info-color); }
        .section-title.vehicle { color: var(--primary-color); border-color: var(--primary-color); }
        .reg-number { font-weight: 600; color: var(--primary-color); font-family: monospace; }
        .nav-tabs .nav-link { color: var(--text-muted); font-weight: 500; }
        .nav-tabs .nav-link.active { color: var(--primary-color); border-color: var(--primary-color); border-bottom-color: #fff; }
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
                                <h1><i class="fas fa-users-cog me-2"></i>Customer & Vehicle Management</h1>
                                <p>Manage customers and their vehicles in one place</p>
                            </div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBothModal">
                                <i class="fas fa-plus me-2"></i>Add Customer & Vehicle
                            </button>
                        </div>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon info"><i class="fas fa-users"></i></div>
                            <div class="details"><h3><?php echo $customerStats['total']; ?></h3><p>Total Customers</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon success"><i class="fas fa-user-plus"></i></div>
                            <div class="details"><h3><?php echo $customerStats['new_this_month']; ?></h3><p>New Customers</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon primary"><i class="fas fa-car"></i></div>
                            <div class="details"><h3><?php echo $vehicleStats['total']; ?></h3><p>Total Vehicles</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon success"><i class="fas fa-wrench"></i></div>
                            <div class="details"><h3><?php echo $vehicleStats['serviced_this_month']; ?></h3><p>Serviced This Month</p></div>
                        </div>
                    </div>

                    <!-- Tabs for Customers and Vehicles -->
                    <ul class="nav nav-tabs mb-3" id="dataTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles-panel" type="button" role="tab">
                                <i class="fas fa-car me-2"></i>Vehicles
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers-panel" type="button" role="tab">
                                <i class="fas fa-users me-2"></i>Customers
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="dataTabContent">
                        <!-- Vehicles Tab -->
                        <div class="tab-pane fade show active" id="vehicles-panel" role="tabpanel">
                            <div class="data-card">
                                <div class="data-card-header">
                                    <h5><i class="fas fa-list me-2"></i>Vehicles List</h5>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#vehicleModal">
                                        <i class="fas fa-car me-1"></i>Add Vehicle Only
                                    </button>
                                </div>
                                <div class="data-card-body">
                                    <div class="table-responsive">
                                        <table id="vehiclesTable" class="table table-hover" style="width:100%">
                                            <thead><tr><th>Vehicle No</th><th>Vehicle</th><th>Customer Name</th><th>Customer No</th><th>Cur. Mileage</th><th>Last Service</th><th class="text-end">Actions</th></tr></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customers Tab -->
                        <div class="tab-pane fade" id="customers-panel" role="tabpanel">
                            <div class="data-card">
                                <div class="data-card-header">
                                    <h5><i class="fas fa-list me-2"></i>Customers List</h5>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
                                        <i class="fas fa-user-plus me-1"></i>Add Customer Only
                                    </button>
                                </div>
                                <div class="data-card-body">
                                    <div class="table-responsive">
                                        <table id="customersTable" class="table table-hover" style="width:100%">
                                            <thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Vehicles</th><th class="text-end">Actions</th></tr></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer & Vehicle Modal -->
    <div class="modal fade" id="addBothModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="addBothForm" class="needs-validation" novalidate>
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-bolt me-2"></i>Quick Add: Customer & Vehicle</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Customer Section -->
                            <div class="col-md-6">
                                <h6 class="section-title customer"><i class="fas fa-user me-2"></i>Customer Details</h6>
                                <div class="mb-3">
                                    <label for="both_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="both_name" name="name" required placeholder="Enter customer name">
                                </div>
                                <div class="mb-3">
                                    <label for="both_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="both_phone" name="phone" required placeholder="Enter phone number">
                                </div>
                                <div class="mb-3">
                                    <label for="both_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="both_email" name="email" placeholder="Enter email (optional)">
                                </div>
                            </div>
                            <!-- Vehicle Section -->
                            <div class="col-md-6">
                                <h6 class="section-title vehicle"><i class="fas fa-car me-2"></i>Vehicle Details</h6>
                                <div class="mb-3">
                                    <label for="both_make" class="form-label">Make <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="both_make" name="make" required placeholder="e.g., Toyota">
                                </div>
                                <div class="mb-3">
                                    <label for="both_model" class="form-label">Model <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="both_model" name="model" required placeholder="e.g., Corolla">
                                </div>
                                <div class="mb-3">
                                    <label for="both_registration" class="form-label">Registration <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="both_registration" name="registration_number" required placeholder="e.g., ABC-1234">
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label for="both_year" class="form-label">Year</label>
                                        <input type="number" class="form-control" id="both_year" name="year" min="1900" max="2100" placeholder="Year">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label for="both_mileage" class="form-label">Mileage (km)</label>
                                        <input type="number" class="form-control" id="both_mileage" name="current_mileage" min="0" placeholder="Mileage">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="saveBothBtn"><i class="fas fa-save me-1"></i>Save Both</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Customer Only Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="customerForm" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="customerModalLabel"><i class="fas fa-user-plus me-2"></i>Add Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="customer_id" name="id">
                        <div class="mb-3">
                            <label for="c_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="c_name" name="name" required placeholder="Enter customer name">
                        </div>
                        <div class="mb-3">
                            <label for="c_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="c_phone" name="phone" required placeholder="Enter phone number">
                        </div>
                        <div class="mb-3">
                            <label for="c_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="c_email" name="email" placeholder="Enter email (optional)">
                        </div>
                        <div class="mb-3">
                            <label for="c_address" class="form-label">Address</label>
                            <textarea class="form-control" id="c_address" name="address" rows="2" placeholder="Enter address (optional)"></textarea>
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

    <!-- Vehicle Only Modal -->
    <div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="vehicleForm" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="vehicleModalLabel"><i class="fas fa-car me-2"></i>Add Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="vehicle_id" name="id">
                        <div class="mb-3">
                            <label for="v_customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select" id="v_customer_id" name="customer_id" required>
                                <option value="">Select Customer...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="v_registration" class="form-label">Registration Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="v_registration" name="registration_number" required placeholder="Enter registration number">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="v_make" class="form-label">Make <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="v_make" name="make" required placeholder="Enter make">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="v_model" class="form-label">Model <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="v_model" name="model" required placeholder="Enter model">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="v_year" class="form-label">Year</label>
                                <input type="number" class="form-control" id="v_year" name="year" min="1900" max="2100" placeholder="Year">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="v_color" class="form-label">Color</label>
                                <input type="text" class="form-control" id="v_color" name="color" placeholder="Color">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="v_mileage" class="form-label">Mileage</label>
                                <input type="number" class="form-control" id="v_mileage" name="current_mileage" min="0" placeholder="Mileage">
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
    <script src="../../Ajax/js/customer-vehicle.js"></script>
</body>
</html>
