<?php

require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in and has permission to view this page
requirePagePermission('View');

if (!isset($_GET['id'])) {
    header('Location: jobs.php');
    exit();
}

$serviceId = (int) $_GET['id'];
$service = new Service($serviceId);

if (!$service->id) {
    header('Location: jobs.php');
    exit();
}

// Get related data
$customer = new Customer($service->customer_id);
$vehicle = new Vehicle($service->vehicle_id);
$package = new ServicePackage($service->package_id);
$qrCode = new QRCode($service->qr_id);

// Load service items (multi-package support)
$serviceItems = ServiceItem::getByServiceId($serviceId);

// Load health check report if exists
$healthCheck = new HealthCheckReport();
$hasHealthCheck = $healthCheck->loadByJobId($serviceId);

// Get all service stages
$companyId = $_SESSION['company_id'] ?? 1;
$allStages = (new ServiceStage())->all($companyId);

// Check if view-only mode (from service history report)
$viewOnly = isset($_GET['view_only']) && $_GET['view_only'] == '1';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Job Details</title>
    <?php include '../../includes/main-css.php'; ?>
    <!-- Select2 for searchable dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary: #4361ee;
            --success: #28a745;
            --info: #17a2b8;
            --light-bg: #f8f9fa;
            --card-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #f5f7fb;
            color: #3c4858;
            font-family: 'Public Sans', sans-serif;
        }

        .content {
            margin-top: 70px;
            padding: 2rem;
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

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }

        .qr-display {
            text-align: center;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
        }

        .qr-code-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        #qrcode {
            margin: 0 auto;
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

        .btn {
            font-weight: 500;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media print {
            /* Hide UI elements */
            .no-print,
            .wrapper > .sidebar,
            .main-panel > .navbar,
            .btn,
            button {
                display: none !important;
            }

            /* Reset page styling */
            body {
                background: white !important;
                margin: 0;
                padding: 20px;
            }

            .wrapper,
            .main-panel {
                margin: 0 !important;
                padding: 0 !important;
            }

            .content {
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Invoice Header */
            .invoice-header {
                display: block !important;
                border-bottom: 3px solid #4361ee;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }

            .company-info {
                text-align: center;
                margin-bottom: 20px;
            }

            .company-info h1 {
                font-size: 28px;
                color: #2c3e50;
                margin: 0;
            }

            .company-info p {
                margin: 5px 0;
                color: #6c757d;
            }

            .invoice-title {
                text-align: center;
                font-size: 24px;
                font-weight: 700;
                color: #4361ee;
                margin: 20px 0;
            }

            /* Invoice details grid */
            .invoice-details-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin-bottom: 30px;
            }

            .invoice-section {
                border: 1px solid #dee2e6;
                padding: 15px;
                border-radius: 8px;
            }

            .invoice-section h4 {
                font-size: 14px;
                color: #4361ee;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 8px;
                margin-bottom: 12px;
            }

            /* Info rows for print */
            .print-info-row {
                display: flex;
                justify-content: space-between;
                padding: 6px 0;
                border-bottom: 1px dashed #e9ecef;
            }

            .print-info-row:last-child {
                border-bottom: none;
            }

            .print-label {
                font-weight: 600;
                color: #495057;
                font-size: 12px;
            }

            .print-value {
                color: #212529;
                font-size: 12px;
            }

            /* Items table */
            .invoice-items {
                margin-top: 30px;
            }

            .invoice-items h4 {
                font-size: 16px;
                color: #2c3e50;
                margin-bottom: 15px;
            }

            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }

            .items-table th {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                padding: 10px;
                text-align: left;
                font-size: 12px;
                font-weight: 600;
            }

            .items-table td {
                border: 1px solid #dee2e6;
                padding: 10px;
                font-size: 12px;
            }

            /* Total section */
            .invoice-totals {
                margin-top: 20px;
                float: right;
                width: 300px;
            }

            .total-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-top: 1px solid #dee2e6;
            }

            .total-row.grand-total {
                font-size: 16px;
                font-weight: 700;
                color: #28a745;
                border-top: 2px solid #28a745;
                padding-top: 10px;
            }

            /* QR Code styling for print */
            .print-qr-section {
                page-break-inside: avoid;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 2px dashed #dee2e6;
                text-align: center;
            }

            /* Footer */
            .invoice-footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 2px solid #4361ee;
                text-align: center;
                font-size: 11px;
                color: #6c757d;
            }

            /* Page breaks */
            .page-break {
                page-break-after: always;
            }

            /* Cards become simple sections */
            .card {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
            }

            .card-header {
                display: none !important;
            }

            .card-body {
                padding: 0 !important;
            }

            /* Hide the on-screen content when printing */
            .screen-only {
                display: none !important;
            }
        }

        /* Enhanced Status Dropdown Styling */
        .status-select-enhanced {
            min-width: 180px;
            max-width: 250px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px;
            font-weight: 500;
            font-size: 0.9rem;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%234361ee' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .status-select-enhanced:hover {
            border-color: #4361ee;
            box-shadow: 0 2px 8px rgba(67, 97, 238, 0.15);
        }

        .status-select-enhanced:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
            outline: none;
        }

        .status-select-enhanced:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .status-select-enhanced option {
            padding: 10px;
            font-weight: 500;
        }

        /* Select2 Custom Styling */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            z-index: 9999;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }

        /* View-only mode - hide editable elements */
        <?php if ($viewOnly): ?>
        .editable-action,
        .status-select-enhanced,
        #addItemBtn,
        .delete-item-btn,
        .edit-btn {
            display: none !important;
        }
        
        .view-only-stage {
            display: inline-block;
            padding: 8px 16px;
            background: #f0f0f0;
            border-radius: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .view-only-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            margin-left: 10px;
        }
        <?php endif; ?>
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
                    <div class="page-header no-print">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="page-title">
                                    <i class="fas fa-clipboard-list me-2"></i>Job Details - <?php echo htmlspecialchars($service->job_number); ?>
                                    <?php if ($viewOnly): ?>
                                        <span class="view-only-badge">View Only</span>
                                    <?php endif; ?>
                                </h1>
                                <p class="page-subtitle">Service job information and QR code</p>
                            </div>
                            <div class="d-flex gap-2">
                                <!-- Health Check button hidden as per request
                                <a href="health_check.php?job_id=<?php echo $serviceId; ?>" class="btn btn-info text-white">
                                    <i class="fas fa-car-crash me-1"></i> Health Check
                                </a>
                                -->
                                <a href="print_invoice.php?id=<?php echo $serviceId; ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-print"></i> Print Invoice
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Header (Only visible when printing) -->
                    <div class="invoice-header" style="display: none;">
                        <div class="company-info">
                            <h1><?php echo APP_NAME; ?></h1>
                            <p>Professional Vehicle Service & Maintenance</p>
                            <p>Address: Your Address Here | Phone: +94 XX XXX XXXX | Email: info@yourcompany.com</p>
                        </div>
                        <div class="invoice-title">SERVICE INVOICE</div>
                        <div class="invoice-details-grid">
                            <div class="invoice-section">
                                <h4>Invoice Details</h4>
                                <div class="print-info-row">
                                    <span class="print-label">Invoice #:</span>
                                    <span class="print-value"><?php echo htmlspecialchars($service->job_number); ?></span>
                                </div>
                                <div class="print-info-row">
                                    <span class="print-label">Date:</span>
                                    <span class="print-value"><?php echo date('M d, Y', strtotime($service->created_at)); ?></span>
                                </div>
                                <div class="print-info-row">
                                    <span class="print-label">Status:</span>
                                    <span class="print-value"><?php echo ucfirst($service->status); ?></span>
                                </div>
                            </div>
                            <div class="invoice-section">
                                <h4>Customer Details</h4>
                                <div class="print-info-row">
                                    <span class="print-label">Name:</span>
                                    <span class="print-value"><?php echo htmlspecialchars($customer->name); ?></span>
                                </div>
                                <div class="print-info-row">
                                    <span class="print-label">Phone:</span>
                                    <span class="print-value"><?php echo htmlspecialchars($customer->phone); ?></span>
                                </div>
                                <?php if ($customer->email): ?>
                                <div class="print-info-row">
                                    <span class="print-label">Email:</span>
                                    <span class="print-value"><?php echo htmlspecialchars($customer->email); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="invoice-section" style="margin-bottom: 20px;">
                            <h4>Vehicle Information</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr;">
                                <div class="print-info-row">
                                    <span class="print-label">Vehicle:</span>
                                    <span class="print-value"><?php echo htmlspecialchars($vehicle->make . ' ' . $vehicle->model); ?></span>
                                </div>
                                <div class="print-info-row">
                                    <span class="print-label">Registration:</span>
                                    <span class="print-value"><?php echo htmlspecialchars($vehicle->registration_number); ?></span>
                                </div>
                                <div class="print-info-row">
                                    <span class="print-label">Year:</span>
                                    <span class="print-value"><?php echo htmlspecialchars($vehicle->year ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Service Items Table -->
                        <div class="invoice-items">
                            <h4>Service Details</h4>
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Service Package</th>
                                        <th>Description</th>
                                        <th>Duration</th>
                                        <th style="text-align: right;">Amount (LKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo htmlspecialchars($package->package_name); ?></td>
                                        <td><?php echo htmlspecialchars($package->description ?? 'Complete service package'); ?></td>
                                        <td><?php echo $package->estimated_duration; ?> mins</td>
                                        <td style="text-align: right;"><?php echo number_format($service->total_amount, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <!-- Totals -->
                            <div class="invoice-totals">
                                <div class="total-row">
                                    <span>Subtotal:</span>
                                    <span>LKR <?php echo number_format($service->total_amount, 2); ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Tax (0%):</span>
                                    <span>LKR 0.00</span>
                                </div>
                                <div class="total-row grand-total">
                                    <span>TOTAL:</span>
                                    <span>LKR <?php echo number_format($service->total_amount, 2); ?></span>
                                </div>
                                <div class="total-row" style="border-top: none;">
                                    <span>Payment Status:</span>
                                    <span style="color: <?php echo $service->payment_status == 'paid' ? '#28a745' : '#ffc107'; ?>;">
                                        <?php echo ucfirst($service->payment_status); ?>
                                    </span>
                                </div>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                        
                        <?php if ($service->notes): ?>
                        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-left: 3px solid #4361ee;">
                            <strong>Notes:</strong><br>
                            <?php echo nl2br(htmlspecialchars($service->notes)); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- QR Code for Print -->
                        <div class="print-qr-section">
                            <p style="margin-bottom: 10px; font-weight: 600;">Scan to Track Service Progress</p>
                            <div id="qrcode-print"></div>
                            <p style="margin-top: 10px; font-size: 11px; color: #6c757d;">
                                Tracking Code: <?php echo htmlspecialchars($qrCode->qr_code); ?>
                            </p>
                        </div>
                        
                        <!-- Invoice Footer -->
                        <div class="invoice-footer">
                            <p><strong>Thank you for your business!</strong></p>
                            <p>This is a computer-generated invoice. For any queries, please contact us.</p>
                            <p>© <?php echo date('Y'); ?> Codeplay Studio. All rights reserved.</p>
                        </div>
                    </div>

                    <div class="row screen-only">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Job Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="info-label">Job Number:</span>
                                        <span class="info-value"><strong><?php echo htmlspecialchars($service->job_number); ?></strong></span>
                                    </div>
                                    <div class="info-row">
                                        <div class="d-flex justify-content-between w-100">
                                            <span class="info-label">Customer:</span>
                                            <div>
                                                <span class="info-value me-2"><?php echo htmlspecialchars($customer->name); ?></span>
                                                <button class="btn btn-sm btn-link p-0 text-primary editable-action" onclick="editCustomer(<?php echo $customer->id; ?>)">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="d-flex justify-content-between w-100">
                                            <span class="info-label">Phone:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($customer->phone); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="d-flex justify-content-between w-100">
                                            <span class="info-label">Vehicle:</span>
                                            <div>
                                                <span class="info-value me-2"><?php echo htmlspecialchars($vehicle->make . ' ' . $vehicle->model); ?></span>
                                                <button class="btn btn-sm btn-link p-0 text-primary editable-action" onclick="editVehicle(<?php echo $vehicle->id; ?>)">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Registration:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($vehicle->registration_number); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Package:</span>
                                        <span class="info-value">
                                            <?php if ($package->id): ?>
                                                <?php echo htmlspecialchars($package->package_name ?? ''); ?>
                                            <?php else: ?>
                                                <span class="badge bg-info">Multiple Packages</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Total Amount:</span>
                                        <span class="info-value text-success"><strong>LKR <?php echo number_format($service->total_amount, 2); ?></strong></span>
                                    </div>
                                    <div class="info-row align-items-center">
                                        <span class="info-label">Current Stage:</span>
                                        <div class="info-value">
                                            <?php if ($viewOnly): ?>
                                                <?php foreach ($allStages as $stage): ?>
                                                    <?php if ($service->current_stage_id == $stage['id']): ?>
                                                        <span class="view-only-stage"><?php echo htmlspecialchars($stage['stage_name']); ?></span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                            <select class="form-select form-select-sm status-select-enhanced" id="statusSelect" 
                                                    data-job-id="<?php echo $service->id; ?>"
                                                    <?php echo in_array($service->status, ['delivered', 'cancelled']) ? 'disabled' : ''; ?>>
                                                <?php foreach ($allStages as $stage): ?>
                                                    <option value="<?php echo $stage['id']; ?>" 
                                                            <?php echo ($service->current_stage_id == $stage['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($stage['stage_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Progress:</span>
                                        <span class="info-value"><?php echo $service->progress_percentage; ?>%</span>
                                    </div>
                                    <div class="info-row align-items-center">
                                        <span class="info-label">Payment Status:</span>
                                        <div class="info-value">
                                            <?php if ($service->payment_status == 'paid'): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark" id="paymentStatusBadge"><i class="fas fa-clock me-1"></i>Pending</span>
                                                <?php if (!$viewOnly && in_array($service->status, ['completed', 'delivered'])): ?>
                                                    <button type="button" class="btn btn-success btn-sm ms-2 editable-action" id="markAsPaidBtn" data-service-id="<?php echo $service->id; ?>">
                                                        <i class="fas fa-money-bill-wave me-1"></i>Mark as Paid
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Created At:</span>
                                        <span class="info-value"><?php echo date('M d, Y H:i', strtotime($service->created_at)); ?></span>
                                    </div>
                                    <?php if ($service->notes): ?>
                                    <div class="info-row">
                                        <span class="info-label">Notes:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($service->notes); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>


                        </div>
                        <div class="col-md-4">
                            <?php if ($hasHealthCheck): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0"><i class="fas fa-heartbeat me-2"></i>Health Check Report</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Tyre Condition:</span>
                                            <span class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $healthCheck->tyre_condition)); ?></span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <?php $tyreScore = $healthCheck->getConditionScore('tyre_condition'); ?>
                                            <div class="progress-bar bg-<?php echo $healthCheck->getConditionColor('tyre_condition'); ?>" role="progressbar" style="width: <?php echo $tyreScore; ?>%" 
                                                 aria-valuenow="<?php echo $tyreScore; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Brake Condition:</span>
                                            <span class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $healthCheck->brake_condition)); ?></span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <?php $brakeScore = $healthCheck->getConditionScore('brake_condition'); ?>
                                            <div class="progress-bar bg-<?php echo $healthCheck->getConditionColor('brake_condition'); ?>" role="progressbar" style="width: <?php echo $brakeScore; ?>%" 
                                                 aria-valuenow="<?php echo $brakeScore; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Battery Health:</span>
                                            <span class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $healthCheck->battery_health)); ?></span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <?php $batteryScore = $healthCheck->getConditionScore('battery_health'); ?>
                                            <div class="progress-bar bg-<?php echo $healthCheck->getConditionColor('battery_health'); ?>" role="progressbar" style="width: <?php echo $batteryScore; ?>%" 
                                                 aria-valuenow="<?php echo $batteryScore; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <a href="health_check.php?job_id=<?php echo $serviceId; ?>" class="btn btn-outline-info btn-sm w-100">
                                        <i class="fas fa-eye me-1"></i> View Full Report
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="fas fa-qrcode me-2"></i>QR Code</h5>
                                </div>
                                <div class="card-body">
                                    <div class="qr-display">
                                        <div class="qr-code-container">
                                            <div id="qrcode"></div>
                                        </div>
                                        <p class="mt-3 mb-0 text-muted small">Scan to track job</p>
                                        <p class="mt-1 mb-0">
                                            <a href="<?php echo htmlspecialchars($service->getTrackingUrl()); ?>" target="_blank" class="text-decoration-none">
                                                <strong><?php echo htmlspecialchars($qrCode->qr_code); ?></strong>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Items Section (For adding items during service) -->
                    <?php 
                    $currentStageSlug = '';
                    foreach ($allStages as $stage) {
                        if ($stage['id'] == $service->current_stage_id) {
                            $currentStageSlug = strtolower(str_replace(' ', '_', $stage['stage_name']));
                            break;
                        }
                    }

                    $excludedStages = ['registration', 'ready_to_delivery', 'delivered', 'cancelled'];
                    $canAddInventory = !in_array($currentStageSlug, $excludedStages) && !in_array($service->status, $excludedStages) && !$viewOnly;
                    ?>
                    <?php if ($canAddInventory): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-boxes me-2"></i>Add Inventory Items
                                        </h5>
                                        <span class="badge bg-light text-success">During Service</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Add Inventory Item Form -->
                                    <div class="card bg-light mb-3">
                                        <div class="card-body py-3">
                                            <h6 class="mb-3"><i class="fas fa-plus-circle me-2 text-success"></i>Add Inventory Item to Job</h6>
                                            <div class="row g-2 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Select Item</label>
                                                    <input type="hidden" id="inventoryItemId">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="inventoryItemDisplay" placeholder="Click to browse items..." readonly style="cursor: pointer;">
                                                        <button type="button" class="btn btn-primary" id="browseInventoryBtn">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Available Stock</label>
                                                    <input type="text" class="form-control" id="inventoryAvailableStock" readonly placeholder="-">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Unit Price</label>
                                                    <input type="number" class="form-control" id="inventoryUnitPrice" placeholder="0.00" step="0.01">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Quantity</label>
                                                    <input type="number" class="form-control" id="inventoryQty" value="1" min="1" step="1">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-success w-100" id="addInventoryItemBtn">
                                                        <i class="fas fa-plus me-1"></i>Add
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted" id="inventoryItemInfo"></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Current Inventory Items Table -->
                                    <h6 class="mb-3"><i class="fas fa-list me-2"></i>Items Added to This Job</h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm" id="jobInventoryTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Item</th>
                                                    <th class="text-end">Quantity</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">Total</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="jobInventoryBody">
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">Loading items...</td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Total Inventory Cost:</strong></td>
                                                    <td class="text-end"><strong id="inventoryTotalCost">LKR 0.00</strong></td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Invoice Items Section (Only visible for completed/delivered services) -->
                    <?php if (in_array($service->status, ['completed', 'delivered'])): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-file-invoice me-2"></i>Invoice Details
                                        </h5>
                                        <span class="badge bg-info" id="invoiceNumber">Loading...</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Add New Item Row (Hidden as per request - view only) -->
                                    <?php if (false): ?>
                                    <div class="card bg-light mb-3" id="addItemForm">
                                        <div class="card-body py-3">
                                            <h6 class="mb-3"><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Item</h6>
                                            <input type="hidden" id="invoiceId">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-md-2">
                                                    <label class="form-label small">Type</label>
                                                    <select class="form-select form-select-sm" id="newItemType">
                                                        <option value="inventory">Inventory</option>
                                                        <option value="labor">Labor</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3" id="itemSelectCol">
                                                    <label class="form-label small">Item</label>
                                                    <input type="hidden" id="newItemId">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" class="form-control" id="newItemDisplay" placeholder="Click to browse..." readonly style="cursor: pointer;">
                                                        <button type="button" class="btn btn-primary" id="browseItemBtn">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Description</label>
                                                    <input type="text" class="form-control form-control-sm" id="newItemDescription" placeholder="Description">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small">Qty</label>
                                                    <input type="number" class="form-control form-control-sm" id="newItemQty" value="1" min="0.01" step="0.01">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small">Price</label>
                                                    <input type="number" class="form-control form-control-sm" id="newItemPrice" value="0" min="0" step="0.01">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small">Tax %</label>
                                                    <input type="number" class="form-control form-control-sm" id="newItemTax" value="0" min="0" step="0.01">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-success btn-sm w-100" id="addItemBtn">
                                                        <i class="fas fa-plus me-1"></i>Add Item
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted" id="newItemInfo"></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Invoice Items Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="invoiceItemsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Description</th>
                                                    <th>Type</th>
                                                    <th class="text-end">Quantity</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">Tax</th>
                                                    <th class="text-end">Total</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="invoiceItemsBody">
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">Loading invoice items...</td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                                    <td class="text-end"><strong id="invoiceSubtotal">LKR 0.00</strong></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="5" class="text-end"><strong>Tax:</strong></td>
                                                    <td class="text-end"><strong id="invoiceTax">LKR 0.00</strong></td>
                                                    <td></td>
                                                </tr>
                                                <tr class="table-primary">
                                                    <td colspan="5" class="text-end"><strong>Total Amount:</strong></td>
                                                    <td class="text-end"><strong id="invoiceTotal">LKR 0.00</strong></td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Old Add Invoice Item Modal removed - now using inline form -->

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editCustomerForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Customer Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="custId">
                        <input type="hidden" name="action" value="update">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="custName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="custPhone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="custEmail">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="custAddress" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editVehicleForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Vehicle Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="vehId">
                        <input type="hidden" name="customer_id" id="vehCustId">
                        <input type="hidden" name="action" value="update">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Registration No.</label>
                                <input type="text" class="form-control" name="registration_number" id="vehReg" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year</label>
                                <input type="number" class="form-control" name="year" id="vehYear">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Make</label>
                                <input type="text" class="form-control" name="make" id="vehMake" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model" id="vehModel" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color" id="vehColor">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Current Mileage</label>
                                <input type="number" class="form-control" name="current_mileage" id="vehMileage">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- Item Selector Modal -->
    <script src="<?php echo BASE_URL; ?>Ajax/js/item_selector_modal.js"></script>
    <script>
        $(document).ready(function() {
            // Generate QR code with tracking URL
            const trackingUrl = "<?php echo htmlspecialchars($service->getTrackingUrl()); ?>";
            
            // Create QR code for screen display
            const qrCodeScreen = new QRCode(document.getElementById("qrcode"), {
                text: trackingUrl,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Create QR code for print (smaller size)
            const qrCodePrint = new QRCode(document.getElementById("qrcode-print"), {
                text: trackingUrl,
                width: 150,
                height: 150,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Make QR code clickable
            $('#qrcode').css('cursor', 'pointer').on('click', function() {
                window.open(trackingUrl, '_blank');
            });

            // Edit Customer Logic
            window.editCustomer = function(id) {
                $.get('../../Ajax/php/customer.php', { action: 'get', id: id }, function(res) {
                    if(res.status === 'success') {
                        const data = res.data;
                        $('#custId').val(data.id);
                        $('#custName').val(data.name);
                        $('#custPhone').val(data.phone);
                        $('#custEmail').val(data.email);
                        $('#custAddress').val(data.address);
                        const modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed to load customer data'
                        });
                    }
                }, 'json').fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Failed to connect to server'
                    });
                });
            };

            $('#editCustomerForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
                
                $.post('../../Ajax/php/customer.php', $(this).serialize(), function(res) {
                    if(res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Customer updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed to update customer'
                        });
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                }, 'json').fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Failed to connect to server'
                    });
                    submitBtn.prop('disabled', false).html(originalText);
                });
            });

            // Edit Vehicle Logic
            window.editVehicle = function(id) {
                $.get('../../Ajax/php/vehicle.php', { action: 'get', id: id }, function(res) {
                    if(res.status === 'success') {
                        const data = res.data;
                        $('#vehId').val(data.id);
                        $('#vehCustId').val(data.customer_id);
                        $('#vehReg').val(data.registration_number);
                        $('#vehMake').val(data.make);
                        $('#vehModel').val(data.model);
                        $('#vehYear').val(data.year);
                        $('#vehColor').val(data.color);
                        $('#vehMileage').val(data.current_mileage);
                        const modal = new bootstrap.Modal(document.getElementById('editVehicleModal'));
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed to load vehicle data'
                        });
                    }
                }, 'json').fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Failed to connect to server'
                    });
                });
            };

            $('#editVehicleForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
                
                $.post('../../Ajax/php/vehicle.php', $(this).serialize(), function(res) {
                    if(res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Vehicle updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed to update vehicle'
                        });
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                }, 'json').fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Failed to connect to server'
                    });
                    submitBtn.prop('disabled', false).html(originalText);
                });
            });

            // Status Change Handler with improved UX
            $('#statusSelect').on('change', function() {
                const select = $(this);
                const jobId = select.data('job-id');
                const newStageId = select.val();
                const newStageName = select.find('option:selected').text().trim();
                const originalStageId = '<?php echo $service->current_stage_id; ?>';
                
                // Confirm specific status changes with SweetAlert
                if (['completed', 'delivered', 'cancelled', 'ready for delivery'].includes(newStageName.toLowerCase())) {
                    Swal.fire({
                        title: 'Confirm Status Change',
                        html: `Are you sure you want to change the status to <strong>${newStageName}</strong>?<br><small class="text-muted">This might trigger invoice actions.</small>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, update it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateStageStatus(select, jobId, newStageId, originalStageId);
                        } else {
                            select.val(originalStageId);
                        }
                    });
                } else {
                    updateStageStatus(select, jobId, newStageId, originalStageId);
                }
            });

            // Function to update stage status
            function updateStageStatus(select, jobId, newStageId, originalStageId) {
                // Disable and show loading
                select.prop('disabled', true);
                
                $.post('../../Ajax/php/service.php', {
                    action: 'update_status',
                    id: jobId,
                    stage_id: newStageId
                }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Job status updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: res.message || 'Failed to update status'
                        });
                        select.val(originalStageId);
                    }
                }, 'json')
                .fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Failed to connect to server'
                    });
                    select.val(originalStageId);
                })
                .always(function() {
                    select.prop('disabled', false);
                });
            }

            // ============== INVOICE ITEMS MANAGEMENT ==============
            <?php if (in_array($service->status, ['completed', 'delivered'])): ?>
            let currentInvoiceId = null;

            // Load invoice and items on page load
            loadInvoiceData();

            // Load inventory items for dropdown
            loadInventoryItems();

            function loadInvoiceData() {
                $.get('../../Ajax/php/invoice.php', {
                    action: 'get_by_service',
                    service_id: <?php echo $serviceId; ?>
                }, function(res) {
                    if (res.status === 'success') {
                        currentInvoiceId = res.data.id;
                        $('#invoiceId').val(currentInvoiceId);
                        $('#invoiceNumber').text(res.data.invoice_number);
                        displayInvoiceItems(res.data.items);
                        updateInvoiceTotals(res.data);
                    } else {
                        $('#invoiceItemsBody').html('<tr><td colspan="7" class="text-center text-muted">No invoice found</td></tr>');
                    }
                }, 'json').fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load invoice data'
                    });
                });
            }

            function loadInventoryItems() {
                $.get('../../Ajax/php/inventory_item.php', {
                    action: 'list'
                }, function(res) {
                    if (res.status === 'success' && res.data) {
                        const select = $('#inventoryItemSelect');
                        select.empty().append('<option value="">Choose an item...</option>');
                        res.data.forEach(item => {
                            if (item.current_stock > 0) {
                                select.append(`<option value="${item.id}" 
                                    data-name="${item.item_name}" 
                                    data-price="${item.unit_price}" 
                                    data-stock="${item.current_stock}">
                                    ${item.item_name} - Stock: ${item.current_stock} - LKR ${parseFloat(item.unit_price).toFixed(2)}
                                </option>`);
                            }
                        });
                        
                        // Initialize Select2 with search
                        select.select2({
                            theme: 'bootstrap-5',
                            placeholder: 'Search and select an item...',
                            allowClear: true,
                            width: '100%',
                            dropdownParent: $('#addInvoiceItemModal')
                        });
                    }
                }, 'json');
            }

            function displayInvoiceItems(items) {
                const tbody = $('#invoiceItemsBody');
                tbody.empty();

                if (!items || items.length === 0) {
                    tbody.html('<tr><td colspan="7" class="text-center text-muted">No items added yet</td></tr>');
                    return;
                }

                items.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.description}</td>
                            <td><span class="badge bg-secondary">${item.item_type}</span></td>
                            <td class="text-end">${item.quantity}</td>
                            <td class="text-end">LKR ${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td class="text-end">LKR ${parseFloat(item.tax_amount).toFixed(2)}</td>
                            <td class="text-end">LKR ${parseFloat(item.total_price).toFixed(2)}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-danger remove-item-btn" data-item-id="${item.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            function updateInvoiceTotals(invoice) {
                $('#invoiceSubtotal').text('LKR ' + parseFloat(invoice.subtotal || 0).toFixed(2));
                $('#invoiceTax').text('LKR ' + parseFloat(invoice.tax_amount || 0).toFixed(2));
                $('#invoiceTotal').text('LKR ' + parseFloat(invoice.total_amount || 0).toFixed(2));
            }

            // Add Item Button - now using inline form
            // Initialize Item Selector Modal for browsing items
            const itemSelector = new ItemSelectorModal({
                onSelect: function(item) {
                    // Set the hidden item ID
                    $('#newItemId').val(item.id);
                    // Display the selected item
                    $('#newItemDisplay').val(item.item_code + ' - ' + item.item_name);
                    // Show additional info
                    $('#newItemInfo').html(
                        `<span class="text-success"><i class="fas fa-check-circle me-1"></i>Stock: ${item.current_stock}</span> | ` +
                        `<span class="text-primary">Price: LKR ${parseFloat(item.selling_price || item.unit_cost || 0).toFixed(2)}</span>`
                    );
                    // Auto-fill the form fields
                    $('#newItemDescription').val(item.item_name);
                    $('#newItemPrice').val(item.selling_price || item.unit_cost || 0);
                }
            });

            // Browse items button
            $('#browseItemBtn').on('click', function() {
                itemSelector.show();
            });

            // Also open when clicking on the display input
            $('#newItemDisplay').on('click', function() {
                if ($('#newItemType').val() === 'inventory') {
                    itemSelector.show();
                }
            });

            // Item Type Change Handler - show/hide item select column
            $('#newItemType').on('change', function() {
                if ($(this).val() === 'inventory') {
                    $('#itemSelectCol').show();
                    $('#newItemId').val('');
                    $('#newItemDisplay').val('');
                    $('#newItemInfo').text('');
                } else {
                    $('#itemSelectCol').hide();
                    $('#newItemId').val('');
                    $('#newItemDisplay').val('');
                    $('#newItemInfo').text('');
                }
            });

            // Add Item Button Click - submit inline form
            $('#addItemBtn').on('click', function() {
                const type = $('#newItemType').val();
                const itemId = $('#newItemId').val();
                const description = $('#newItemDescription').val();
                const qty = parseFloat($('#newItemQty').val()) || 0;
                const price = parseFloat($('#newItemPrice').val()) || 0;
                const taxRate = parseFloat($('#newItemTax').val()) || 0;

                // Validation
                if (!description) {
                    Swal.fire('Error', 'Please enter a description', 'error');
                    return;
                }
                if (type === 'inventory' && !itemId) {
                    Swal.fire('Error', 'Please select an inventory item', 'error');
                    return;
                }
                if (qty <= 0) {
                    Swal.fire('Error', 'Quantity must be greater than 0', 'error');
                    return;
                }
                if (price <= 0) {
                    Swal.fire('Error', 'Price must be greater than 0', 'error');
                    return;
                }

                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Adding...');

                $.post('../../Ajax/php/invoice.php', {
                    action: 'add_item',
                    invoice_id: currentInvoiceId,
                    item_type: type,
                    item_id: itemId,
                    description: description,
                    quantity: qty,
                    unit_price: price,
                    tax_rate: taxRate
                }, function(res) {
                    if (res.status === 'success') {
                        // Clear the form
                        $('#newItemId').val('');
                        $('#newItemDisplay').val('');
                        $('#newItemDescription').val('');
                        $('#newItemQty').val(1);
                        $('#newItemPrice').val(0);
                        $('#newItemTax').val(0);
                        $('#newItemInfo').html('');
                        
                        // Refresh the items table
                        displayInvoiceItems(res.items);
                        updateInvoiceTotals(res.invoice);
                        
                        // Show success toast
                        Swal.fire({
                            icon: 'success',
                            title: 'Item Added!',
                            text: 'Item has been added to the invoice',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed to add item'
                        });
                    }
                    btn.prop('disabled', false).html(originalText);
                }, 'json').fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Failed to connect to server'
                    });
                    btn.prop('disabled', false).html(originalText);
                });
            });

            // Remove Item
            $(document).on('click', '.remove-item-btn', function() {
                const itemId = $(this).data('item-id');
                
                Swal.fire({
                    title: 'Remove Item?',
                    text: 'Are you sure you want to remove this item from the invoice?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, remove it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('../../Ajax/php/invoice.php', {
                            action: 'remove_item',
                            item_id: itemId,
                            invoice_id: currentInvoiceId
                        }, function(res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Removed!',
                                    text: 'Item has been removed',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                displayInvoiceItems(res.items);
                                updateInvoiceTotals(res.invoice);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: res.message || 'Failed to remove item'
                                });
                            }
                        }, 'json');
                    }
                });
            });

            // Mark as Paid Handler
            $('#markAsPaidBtn').on('click', function() {
                const serviceId = $(this).data('service-id');
                const btn = $(this);
                
                Swal.fire({
                    title: 'Mark as Paid?',
                    text: 'Are you sure you want to mark this service as paid?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check me-1"></i>Yes, Mark as Paid',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const originalHtml = btn.html();
                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                        
                        $.post('../../Ajax/php/service.php', {
                            action: 'update_payment_status',
                            service_id: serviceId,
                            payment_status: 'paid'
                        }, function(res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Paid!',
                                    text: 'Payment status has been updated',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Update the badge and hide the button
                                    $('#paymentStatusBadge').removeClass('bg-warning text-dark').addClass('bg-success')
                                        .html('<i class="fas fa-check-circle me-1"></i>Paid');
                                    btn.hide();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: res.message || 'Failed to update payment status'
                                });
                                btn.prop('disabled', false).html(originalHtml);
                            }
                        }, 'json').fail(function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Network Error',
                                text: 'Failed to connect to server'
                            });
                            btn.prop('disabled', false).html(originalHtml);
                        });
                    }
                });
            });
            <?php endif; ?>
        });
    </script>


    <script>
        $(document).ready(function() {
            const serviceId = <?php echo $serviceId; ?>;

            // ========== INVENTORY ITEMS MANAGEMENT ==========
            <?php if ($canAddInventory): ?>
            
            // Initialize Item Selector Modal
            const inventoryItemSelector = new ItemSelectorModal({
                onSelect: function(item) {
                    $('#inventoryItemId').val(item.id);
                    $('#inventoryItemDisplay').val(item.item_code + ' - ' + item.item_name);
                    // Use correct property names from InventoryItem::all()
                    const stock = item.current_stock !== undefined ? item.current_stock : (item.quantity || 0);
                    const unit = item.unit_of_measure || item.unit || 'pcs';
                    $('#inventoryAvailableStock').val(stock + ' ' + unit);
                    $('#inventoryUnitPrice').val(parseFloat(item.selling_price || item.unit_price).toFixed(2));
                    $('#inventoryItemInfo').text(`${item.item_name} - ${item.description || 'No description'}`);
                }
            });

            // Open Item Selector
            $('#browseInventoryBtn, #inventoryItemDisplay').on('click', function() {
                inventoryItemSelector.show();
            });

            // Handle manual clearing
            $('#inventoryItemDisplay').on('input', function() {
                if ($(this).val() === '') {
                    $('#inventoryItemId').val('');
                    $('#inventoryAvailableStock').val('');
                    $('#inventoryUnitPrice').val('');
                    $('#inventoryItemInfo').text('');
                }
            });
            
            // Load current inventory items for this job
            function loadJobInventoryItems() {
                $.get('../../Ajax/php/service.php', { 
                    action: 'get_service_items', 
                    service_id: serviceId,
                    item_type: 'inventory'
                }, function(res) {
                    const tbody = $('#jobInventoryBody');
                    if (res.status === 'success' && res.data && res.data.length > 0) {
                        let html = '';
                        let total = 0;
                        res.data.forEach(item => {
                            const itemTotal = parseFloat(item.total_price) || 0;
                            total += itemTotal;
                            html += `
                                <tr>
                                    <td>${item.description || 'N/A'}</td>
                                    <td class="text-end">${parseFloat(item.quantity).toFixed(2)}</td>
                                    <td class="text-end">LKR ${parseFloat(item.unit_price).toFixed(2)}</td>
                                    <td class="text-end">LKR ${itemTotal.toFixed(2)}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger remove-inventory-btn" data-item-id="${item.id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                        tbody.html(html);
                        $('#inventoryTotalCost').text('LKR ' + total.toFixed(2));
                    } else {
                        tbody.html('<tr><td colspan="5" class="text-center text-muted">No inventory items added yet</td></tr>');
                        $('#inventoryTotalCost').text('LKR 0.00');
                    }
                }, 'json');
            }
            
            // Add inventory item to job
            $('#addInventoryItemBtn').on('click', function() {
                const itemId = $('#inventoryItemId').val();
                const qty = parseFloat($('#inventoryQty').val()) || 0;
                const unitPrice = parseFloat($('#inventoryUnitPrice').val()) || 0;
                
                if (!itemId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select an Item',
                        text: 'Please select an inventory item to add'
                    });
                    return;
                }
                
                if (qty <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Quantity',
                        text: 'Please enter a valid quantity'
                    });
                    return;
                }
                
                // Check stock (optional frontend check, real check is backend)
                const stockText = $('#inventoryAvailableStock').val();
                if(stockText) {
                     const available = parseFloat(stockText);
                     if (qty > available) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Insufficient Stock',
                            text: `Only ${available} available`
                        });
                        return;
                     }
                }
                
                const btn = $(this);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.post('../../Ajax/php/service.php', {
                    action: 'add_inventory_item',
                    service_id: serviceId,
                    inventory_item_id: itemId,
                    quantity: qty,
                    unit_price: unitPrice
                }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Item Added!',
                            text: 'Inventory item has been added to the job',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                        
                        // Reset form
                        $('#inventoryItemId').val('');
                        $('#inventoryItemDisplay').val('');
                        $('#inventoryAvailableStock').val('');
                        $('#inventoryUnitPrice').val('');
                        $('#inventoryQty').val('1');
                        $('#inventoryItemInfo').text('');
                        
                        // Reload lists
                        loadJobInventoryItems();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed to add item'
                        });
                    }
                    btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i>Add');
                }, 'json').fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Failed to connect to server'
                    });
                    btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i>Add');
                });
            });
            
            // Remove inventory item from job
            $(document).on('click', '.remove-inventory-btn', function() {
                const itemId = $(this).data('item-id');
                
                Swal.fire({
                    title: 'Remove Item?',
                    text: 'This will return the item to inventory stock.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, remove it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('../../Ajax/php/service.php', {
                            action: 'remove_service_item',
                            item_id: itemId
                        }, function(res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Removed!',
                                    text: 'Item has been removed and returned to stock',
                                    timer: 1500,
                                    showConfirmButton: false,
                                    toast: true,
                                    position: 'top-end'
                                });
                                loadJobInventoryItems();
                                // loadInventoryItemsDropdown(); // Removed as using modal
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: res.message || 'Failed to remove item'
                                });
                            }
                        }, 'json');
                    }
                });
            });
            
            // Initialize inventory section
            loadJobInventoryItems();
            
            <?php endif; ?>
        });
    </script>
</body>

</html>