<?php

require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in and has permission to view this page
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Add New Job</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --light-bg: #f8f9fa;
            --card-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7fb;
            color: #3c4858;
            font-family: 'Public Sans', sans-serif;
        }

        .content {
            margin-top: 70px;
            padding: 2rem;
            transition: var(--transition);
        }

        .page-header {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            padding: 1.5rem 2rem;
            border-left: 4px solid var(--primary);
        }

        .page-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.25rem;
            font-size: 1.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            font-size: 1.1rem;
        }

        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        .btn {
            font-weight: 500;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .step.active .step-circle {
            background: var(--primary);
            color: white;
        }

        .step.completed .step-circle {
            background: var(--success);
            color: white;
        }

        .step-label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
        }

        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        @media (max-width: 991.98px) {
            .content {
                padding: 1rem;
                margin-left: 0;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="animate-fade-in">
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>

            <div class="content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h1 class="page-title"><i class="fas fa-plus-circle me-2"></i>Add New Job</h1>
                                <p class="page-subtitle">Create a new service job with customer, vehicle, and package details</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Customer Info</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="step-label">Vehicle Details</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="step-label">Service Package</div>
                        </div>
                        <div class="step" data-step="4">
                            <div class="step-circle">4</div>
                            <div class="step-label">Confirmation</div>
                        </div>
                    </div>

                    <!-- Job Form -->
                    <form id="jobForm" class="needs-validation" novalidate>
                        <div class="row">
                            <!-- Left Column - Form Steps -->
                            <div class="col-lg-8">
                                <!-- Step 1: Customer Information -->
                                <div class="card form-step" id="step1">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="fas fa-user me-2"></i>Customer Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="customer_id" class="form-label">Select Customer <span class="text-danger">*</span></label>
                                                <select class="form-select" id="customer_id" name="customer_id" required>
                                                    <option value="">Choose a customer...</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a customer.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">&nbsp;</label>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-primary flex-fill" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                                        <i class="fas fa-user-plus"></i> New Customer
                                                    </button>
                                                    <button type="button" class="btn btn-success flex-fill" data-bs-toggle="modal" data-bs-target="#quickAddBothModal">
                                                        <i class="fas fa-bolt"></i> Quick Add Both
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="customerDetails" class="mt-3" style="display: none;">
                                            <div class="alert alert-info">
                                                <h6 class="mb-2"><strong>Customer Details:</strong></h6>
                                                <div class="info-row">
                                                    <span class="info-label">Name:</span>
                                                    <span class="info-value" id="customer_name_display"></span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="info-label">Phone:</span>
                                                    <span class="info-value" id="customer_phone_display"></span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="info-label">Email:</span>
                                                    <span class="info-value" id="customer_email_display"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end mt-3">
                                            <button type="button" class="btn btn-primary next-step" data-next="2">
                                                Next <i class="fas fa-arrow-right ms-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2: Vehicle Information -->
                                <div class="card form-step" id="step2" style="display: none;">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="fas fa-car me-2"></i>Vehicle Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="vehicle_id" class="form-label">Select Vehicle <span class="text-danger">*</span></label>
                                                <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                                    <option value="">Choose a vehicle...</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a vehicle.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                                                    <i class="fas fa-car-alt"></i> Add New Vehicle
                                                </button>
                                            </div>
                                        </div>
                                        <div id="vehicleDetails" class="mt-3" style="display: none;">
                                            <div class="alert alert-success">
                                                <h6 class="mb-2"><strong>Vehicle Details:</strong></h6>
                                                <div class="info-row">
                                                    <span class="info-label">Make & Model:</span>
                                                    <span class="info-value" id="vehicle_make_model_display"></span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="info-label">Registration:</span>
                                                    <span class="info-value" id="vehicle_reg_display"></span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="info-label">Year:</span>
                                                    <span class="info-value" id="vehicle_year_display"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="button" class="btn btn-outline-secondary prev-step" data-prev="1">
                                                <i class="fas fa-arrow-left me-1"></i> Previous
                                            </button>
                                            <button type="button" class="btn btn-primary next-step" data-next="3">
                                                Next <i class="fas fa-arrow-right ms-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 3: Service Packages (Multi-Select) -->
                                <div class="card form-step" id="step3" style="display: none;">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="fas fa-boxes me-2"></i>Service Packages</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Add Package Row -->
                                        <div class="row align-items-end mb-3">
                                            <div class="col-md-8">
                                                <label for="package_selector" class="form-label">Select Package to Add <span class="text-danger">*</span></label>
                                                <select class="form-select" id="package_selector">
                                                    <option value="">Choose a package...</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-primary w-100" id="addPackageBtn" disabled>
                                                    <i class="fas fa-plus me-1"></i> Add Package
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Selected Packages Table -->
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="selectedPackagesTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Package Name</th>
                                                        <th>Description</th>
                                                        <th class="text-end">Price (LKR)</th>
                                                        <th class="text-center" style="width: 80px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="selectedPackagesBody">
                                                    <tr id="noPackagesRow">
                                                        <td colspan="4" class="text-center text-muted py-4">
                                                            <i class="fas fa-info-circle me-2"></i>No packages added yet. Select a package above.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-success">
                                                        <th colspan="2" class="text-end">Total:</th>
                                                        <th class="text-end" id="packagesTotal">LKR 0.00</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        
                                        <!-- Hidden input to store selected package IDs -->
                                        <input type="hidden" id="selected_packages" name="selected_packages" value="[]">
                                        <div class="invalid-feedback" id="packagesError" style="display: none;">Please add at least one package.</div>
                                        <div id="overrideSection" class="mt-3" style="display: none;">
                                            <h6 class="mb-2"><strong>Override Package Details (Optional):</strong></h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="override_price" class="form-label">Custom Price</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">LKR</span>
                                                        <input type="number" class="form-control" id="override_price" min="0" step="0.01">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="override_duration" class="form-label">Custom Duration (min)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                        <input type="number" class="form-control" id="override_duration" min="1">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Assign Employee -->
                                        <div class="row mt-3">
                                            <div class="col-md-12 mb-3">
                                                <label for="employee_id" class="form-label"><i class="fas fa-user-cog me-1"></i>Assign Employee</label>
                                                <select class="form-select" id="employee_id" name="employee_id">
                                                    <option value="">Select an employee (optional)</option>
                                                </select>
                                                <small class="text-muted">Assign a technician or employee to this job</small>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12 mb-3">
                                                <label for="notes" class="form-label">Additional Notes</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any special instructions or notes..."></textarea>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="button" class="btn btn-outline-secondary prev-step" data-prev="2">
                                                <i class="fas fa-arrow-left me-1"></i> Previous
                                            </button>
                                            <button type="button" class="btn btn-primary next-step" data-next="4">
                                                Next <i class="fas fa-arrow-right ms-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 4: Confirmation -->
                                <div class="card form-step" id="step4" style="display: none;">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="fas fa-check-circle me-2"></i>Confirmation</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-primary">
                                            <h6 class="mb-3"><strong>Review Job Details:</strong></h6>
                                            <div id="summaryDetails"></div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="button" class="btn btn-outline-secondary prev-step" data-prev="3">
                                                <i class="fas fa-arrow-left me-1"></i> Previous
                                            </button>
                                            <button type="submit" class="btn btn-success" id="submitJobBtn">
                                                <i class="fas fa-check-circle me-1"></i> Create Job
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Summary -->
                            <div class="col-lg-4">
                                <div class="card position-sticky" style="top: 90px;">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="fas fa-clipboard-list me-2"></i>Job Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="jobSummary">
                                            <p class="text-muted text-center py-3">Fill in the form to see job summary</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal (Quick Add) -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickAddCustomerForm">
                        <div class="mb-3">
                            <label for="quick_customer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="quick_customer_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="quick_customer_phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="quick_customer_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="quick_customer_email">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveQuickCustomer">
                        <i class="fas fa-save"></i> Save Customer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal (Quick Add) -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVehicleModalLabel">Add New Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickAddVehicleForm">
                        <div class="mb-3">
                            <label for="quick_vehicle_make" class="form-label">Make <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_vehicle_make" required>
                        </div>
                        <div class="mb-3">
                            <label for="quick_vehicle_model" class="form-label">Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_vehicle_model" required>
                        </div>
                        <div class="mb-3">
                            <label for="quick_vehicle_registration" class="form-label">Registration Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_vehicle_registration" required>
                        </div>
                        <div class="mb-3">
                            <label for="quick_vehicle_year" class="form-label">Year</label>
                            <input type="number" class="form-control" id="quick_vehicle_year" min="1900" max="2100">
                        </div>
                        <div class="mb-3">
                            <label for="quick_vehicle_mileage" class="form-label">Current Mileage (km)</label>
                            <input type="number" class="form-control" id="quick_vehicle_mileage" min="0" placeholder="e.g., 45000">
                            <small class="text-muted">Enter the current odometer reading</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveQuickVehicle">
                        <i class="fas fa-save"></i> Save Vehicle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Both (Customer + Vehicle) Modal -->
    <div class="modal fade" id="quickAddBothModal" tabindex="-1" aria-labelledby="quickAddBothModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="quickAddBothModalLabel">
                        <i class="fas fa-bolt me-2"></i>Quick Add: Customer & Vehicle
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickAddBothForm">
                        <div class="row">
                            <!-- Customer Section -->
                            <div class="col-md-6">
                                <h6 class="mb-3 text-primary border-bottom pb-2">
                                    <i class="fas fa-user me-2"></i>Customer Details
                                </h6>
                                <div class="mb-3">
                                    <label for="both_customer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="both_customer_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="both_customer_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="both_customer_phone" required>
                                </div>
                                <div class="mb-3">
                                    <label for="both_customer_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="both_customer_email">
                                </div>
                            </div>

                            <!-- Vehicle Section -->
                            <div class="col-md-6">
                                <h6 class="mb-3 text-success border-bottom pb-2">
                                    <i class="fas fa-car me-2"></i>Vehicle Details
                                </h6>
                                <div class="mb-3">
                                    <label for="both_vehicle_make" class="form-label">Make <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="both_vehicle_make" required placeholder="e.g., Toyota">
                                </div>
                                <div class="mb-3">
                                    <label for="both_vehicle_model" class="form-label">Model <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="both_vehicle_model" required placeholder="e.g., Corolla">
                                </div>
                                <div class="mb-3">
                                    <label for="both_vehicle_registration" class="form-label">Registration <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="both_vehicle_registration" required placeholder="e.g., ABC-1234">
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label for="both_vehicle_year" class="form-label">Year</label>
                                        <input type="number" class="form-control" id="both_vehicle_year" min="1900" max="2100">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label for="both_vehicle_mileage" class="form-label">Mileage (km)</label>
                                        <input type="number" class="form-control" id="both_vehicle_mileage" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveQuickBoth">
                        <i class="fas fa-save me-1"></i> Save & Go to Packages
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/service.js"></script>
</body>

</html>