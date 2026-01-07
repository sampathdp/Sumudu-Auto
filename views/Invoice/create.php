<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');

$editInvoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $editInvoiceId ? 'Edit' : 'Create'; ?> Invoice</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .content {
            margin-top: 70px;
            padding: 1.5rem;
        }

        @media (min-width: 768px) {
            .content { padding: 2rem; }
        }

        /* Page Header */
        .page-header {
            background: #fff;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.5rem;
            border-left: 4px solid var(--primary-color);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .page-header .title-section h1 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .page-header .title-section p {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin: 0.25rem 0 0;
        }

        .invoice-number-badge {
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Card Styles */
        .form-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
        }

        .form-card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--bg-light);
        }

        .form-card-header h5 {
            margin: 0;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-card-body {
            padding: 1.5rem;
        }

        /* Form Controls */
        .form-label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.375rem;
        }

        .form-control, .form-select {
            font-size: 0.875rem;
            border-color: var(--border-color);
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Items Table */
        .items-header {
            display: none;
        }

        @media (min-width: 992px) {
            .items-header {
                display: flex;
                padding: 0.75rem 1rem;
                background-color: var(--bg-light);
                border-bottom: 1px solid var(--border-color);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                color: var(--text-muted);
                letter-spacing: 0.05em;
            }
        }

        .item-row {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            background: #fff;
            transition: background-color 0.15s ease;
        }

        .item-row:hover {
            background-color: var(--bg-light);
        }

        .item-row:last-child {
            border-bottom: none;
        }

        @media (max-width: 991px) {
            .item-row .form-label {
                display: block;
                margin-top: 0.5rem;
            }
        }

        .item-row .remove-item {
            color: var(--danger-color);
            background: transparent;
            border: 1px solid var(--danger-color);
        }

        .item-row .remove-item:hover {
            background: var(--danger-color);
            color: #fff;
        }

        /* Summary Section */
        .summary-section {
            background: var(--bg-light);
            border-radius: 8px;
            padding: 1.25rem;
            border: 1px solid var(--border-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.875rem;
        }

        .summary-row.total {
            border-top: 2px solid var(--border-color);
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .summary-row label {
            color: var(--text-muted);
        }

        .summary-row .value {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Action Buttons */
        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: flex-end;
            padding: 1.25rem 1.5rem;
            background-color: var(--bg-light);
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 8px 8px;
        }

        .btn {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-outline-secondary {
            color: var(--secondary-color);
            border-color: var(--border-color);
        }

        .btn-outline-secondary:hover {
            background-color: var(--bg-light);
            border-color: var(--secondary-color);
        }

        .btn-add-item {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px dashed var(--primary-color);
            width: 100%;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .btn-add-item:hover {
            background-color: rgba(37, 99, 235, 0.05);
            border-style: solid;
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
                    <!-- Page Header -->
                    <div class="page-header">
                            <h1><i class="fas fa-file-invoice-dollar me-2"></i><?php echo $editInvoiceId ? 'Edit' : 'Create'; ?> Invoice</h1>
                            <p><?php echo $editInvoiceId ? 'Update existing invoice details' : 'Generate invoice for service or direct sale'; ?></p>
                        <div class="invoice-number-badge" id="invoice_number_display">
                            <i class="fas fa-hashtag me-1"></i> Loading...
                        </div>
                    </div>

                    <form id="invoiceForm">
                        <!-- Customer Details Card -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5><i class="fas fa-user me-2"></i>Customer Details</h5>
                            </div>
                            <div class="form-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-4">
                                        <label class="form-label">Select Vehicle</label>
                                        <div class="input-group">
                                            <input type="hidden" id="vehicle_id" value="">
                                            <input type="text" class="form-control" id="vehicle_display" placeholder="Type reg no or browse..." style="cursor: text;">
                                            <button class="btn btn-outline-secondary" type="button" id="browseVehicleBtn" title="Browse vehicles">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label class="form-label">Customer Name</label>
                                        <div class="input-group">
                                            <input type="hidden" id="customer_id" value="0">
                                            <input type="text" class="form-control" id="customer_name" placeholder="Type name or browse..." style="cursor: text;">
                                            <button class="btn btn-outline-secondary" type="button" id="browseCustomerBtn" title="Browse customers">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <button class="btn btn-outline-success" type="button" id="quickAddBtn" title="Quick Add Customer & Vehicle">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label class="form-label">Mobile Number</label>
                                        <input type="text" class="form-control" id="customer_mobile" placeholder="Enter mobile number">
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label class="form-label">Invoice Date</label>
                                        <input type="date" class="form-control" id="invoice_date">
                                    </div>
                                    <div class="col-md-6 col-lg-8" id="lastServiceInfoCol" style="display:none;">
                                        <label class="form-label"><i class="fas fa-history text-info me-1"></i>Last Service Record</label>
                                        <div class="d-flex flex-wrap gap-2" id="lastServiceInfo">
                                            <small class="text-muted">Select a vehicle to see history</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Service History Card (shown when vehicle selected) -->
                        <div class="form-card" id="serviceHistoryCard" style="display:none;">
                            <div class="form-card-header bg-info bg-opacity-10">
                                <h5 class="text-dark"><i class="fas fa-history me-2"></i>Service Record</h5>
                            </div>
                            <div class="form-card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Current Mileage</label>
                                        <input type="number" class="form-control" id="current_mileage" placeholder="e.g., 85000">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Next Service Mileage</label>
                                        <input type="number" class="form-control" id="next_service_mileage" placeholder="e.g., 90000">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Next Service Date</label>
                                        <input type="date" class="form-control" id="next_service_date">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Service Notes</label>
                                        <input type="text" class="form-control" id="service_notes" placeholder="Optional notes">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Card -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5><i class="fas fa-list-ul me-2"></i>Invoice Items</h5>
                            </div>
                            
                            <!-- Desktop Header -->
                            <div class="items-header">
                                <div style="flex: 0 0 15%;">Type</div>
                                <div style="flex: 0 0 30%;">Description / Item</div>
                                <div style="flex: 0 0 12%;">Qty</div>
                                <div style="flex: 0 0 15%;">Unit Price</div>
                                <div style="flex: 0 0 15%;">Total</div>
                                <div style="flex: 0 0 8%;">Action</div>
                            </div>

                            <div id="itemsContainer">
                                <!-- Dynamic items go here -->
                            </div>

                            <div class="p-3">
                                <button type="button" class="btn btn-add-item" id="addItemBtn">
                                    <i class="fas fa-plus me-2"></i>Add New Item
                                </button>
                            </div>
                        </div>

                        <!-- Payment & Summary Row -->
                        <div class="row">
                            <!-- Payment Details -->
                            <div class="col-lg-7 mb-3 mb-lg-0">
                                <div class="form-card h-100">
                                    <div class="form-card-header">
                                        <h5><i class="fas fa-credit-card me-2"></i>Payment Details</h5>
                                    </div>
                                    <div class="form-card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label">Bill Type <span class="text-danger">*</span></label>
                                                <select class="form-select" id="bill_type" required>
                                                    <option value="cash" selected>Cash Bill</option>
                                                    <option value="credit">Credit Bill</option>
                                                </select>
                                                <small class="text-muted">Cash = Pay now, Credit = Pay later</small>
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label">Payment Method</label>
                                                <select class="form-select" id="payment_method">
                                                    <option value="">-- Not Paid Yet --</option>
                                                    <option value="cash" selected>Cash</option>
                                                    <option value="card">Card</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label">Payment Date</label>
                                                <input type="date" class="form-control" id="payment_date">
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label">Deposit To (Account)</label>
                                                <select class="form-select" id="account_id">
                                                    <option value="">-- Select Account --</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="col-lg-5">
                                <div class="form-card">
                                    <div class="form-card-header">
                                        <h5><i class="fas fa-calculator me-2"></i>Summary</h5>
                                    </div>
                                    <div class="form-card-body p-0">
                                        <div class="summary-section">
                                            <div class="summary-row">
                                                <label>Subtotal</label>
                                                <span class="value" id="subtotal">Rs. 0.00</span>
                                            </div>
                                            <div class="summary-row align-items-center d-none">
                                                <label>Tax</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm" style="width: 120px;" id="tax_amount" value="0">
                                            </div>
                                            <div class="summary-row align-items-center">
                                                <label>Discount</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm" style="width: 120px;" id="discount_amount" value="0">
                                            </div>
                                            <div class="summary-row total">
                                                <label>Total Amount</label>
                                                <span class="value" id="total_amount">Rs. 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary" id="saveInvoiceBtn">
                                            <i class="fas fa-save me-1"></i><?php echo $editInvoiceId ? 'Update' : 'Create'; ?> Invoice
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="<?php echo BASE_URL; ?>Ajax/js/item_selector_modal.js"></script>
    <script src="<?php echo BASE_URL; ?>Ajax/js/customer_selector_modal.js"></script>
    <script src="<?php echo BASE_URL; ?>Ajax/js/vehicle_selector_modal.js"></script>
    <script src="<?php echo BASE_URL; ?>Ajax/js/autocomplete_helper.js"></script>
    <script>
    let itemCounter = 0;
    let currentItemRow = null;
    let activeServicePackages = [];

    const editInvoiceId = <?php echo $editInvoiceId; ?>;

    $(document).ready(function() {
        if (editInvoiceId) {
            loadInvoiceForEdit(editInvoiceId);
        } else {
            // Set today's date for new invoice
            const today = new Date().toISOString().split('T')[0];
            $('#invoice_date').val(today);
            $('#payment_date').val(today);

            // Fetch Next Invoice Number
            $.get('../../Ajax/php/invoice.php?action=get_next_number', function(res) {
                if (res.status === 'success') {
                    $('#invoice_number_display').html('<i class="fas fa-hashtag me-1"></i> ' + res.next_number);
                }
            });
        }

        // Fetch Active Service Packages
        $.get('../../Ajax/php/service-package.php?action=get_active', function(res) {
            if (res.status === 'success') {
                activeServicePackages = res.data;
            }
        });

        // Load Financial Accounts
        $.get('../../Ajax/php/financial.php?action=get_accounts', function(res) {
            if (res.status === 'success') {
                const select = $('#account_id');
                res.data.forEach(acc => {
                    select.append(`<option value="${acc.id}">${acc.account_name} (${acc.account_type})</option>`);
                    if (!editInvoiceId && acc.is_default == 1) {
                        select.val(acc.id);
                    }
                });
            }
        });

        // Load Vehicles for Autocomplete
        let vehiclesData = [];
        $.get('../../Ajax/php/vehicle.php?action=list_with_customer', function(res) {
            if (res.status === 'success') {
                vehiclesData = res.data;
            }
        });



        // Initialize Vehicle Selector Modal
        const vehicleSelector = new VehicleSelectorModal({
            onSelect: function(vehicle) {
                $('#vehicle_id').val(vehicle.id);
                $('#vehicle_display').val(vehicle.registration_number + ' - ' + vehicle.make + ' ' + vehicle.model);
                
                // Auto-fill customer details
                $('#customer_id').val(vehicle.customer_id);
                $('#customer_name').val(vehicle.customer_name);
                $('#customer_mobile').val(vehicle.customer_phone || '');
                showServiceHistoryCard(vehicle.id);
            }
        });

        $('#browseVehicleBtn').click(function() {
            vehicleSelector.show();
        });

        // Autocomplete for Vehicle
        new Autocomplete({
            inputSelector: '#vehicle_display',
            minChars: 1,
            fetchData: function(term, callback) {
                // Filter locally from loaded vehiclesData
                term = term.toLowerCase();
                const matches = vehiclesData.filter(v => 
                    v.registration_number.toLowerCase().includes(term) || 
                    v.make.toLowerCase().includes(term) || 
                    v.model.toLowerCase().includes(term) ||
                    (v.customer_name && v.customer_name.toLowerCase().includes(term))
                ).slice(0, 10); // Limit results
                callback(matches);
            },
            renderItem: function(v) {
                return `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-search text-secondary me-3"></i>
                        <div>
                            <div class="fw-bold text-white">${v.registration_number} <span class="text-secondary small ms-1">${v.make} ${v.model}</span></div>
                            <small class="text-muted" style="color: #9ca3af !important;">Owner: ${v.customer_name}</small>
                        </div>
                    </div>
                `;
            },
            onSelect: function(vehicle) {
                $('#vehicle_id').val(vehicle.id);
                $('#vehicle_display').val(vehicle.registration_number + ' - ' + vehicle.make + ' ' + vehicle.model);
                $('#customer_id').val(vehicle.customer_id);
                $('#customer_name').val(vehicle.customer_name);
                $('#customer_mobile').val(vehicle.customer_phone || '');
                showServiceHistoryCard(vehicle.id);
            }
        });

        // Helper to fetch and select vehicle for a customer
        function fetchAndSelectCustomerVehicle(customerId) {
            $.get('../../Ajax/php/vehicle.php', { action: 'get_by_customer', customer_id: customerId }, function(res) {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    // Select the first vehicle (most recently created/updated usually)
                    const v = res.data[0];
                    $('#vehicle_id').val(v.id);
                    $('#vehicle_display').val(v.registration_number + ' - ' + v.make + ' ' + v.model);
                    showServiceHistoryCard(v.id);
                    
                    if (res.data.length > 1) {
                        // Optional: Notify user if multiple vehicles exist
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'Customer has multiple vehicles. Selected newest: ' + v.registration_number,
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                } else {
                    // Clear vehicle if none found (safety)
                    $('#vehicle_id').val('');
                    $('#vehicle_display').val('');
                    $('#serviceHistoryCard').slideUp();
                }
            });
        }

        // Helper to show service history card and load latest data
        function showServiceHistoryCard(vehicleId) {
            if (!vehicleId) {
                $('#serviceHistoryCard').slideUp();
                return;
            }
            
            $('#serviceHistoryCard').slideDown();
            $('#lastServiceInfoCol').show();
            
            // Clear previous values
            $('#current_mileage').val('');
            $('#next_service_mileage').val('');
            $('#next_service_date').val('');
            $('#service_notes').val('');
            $('#lastServiceInfo').html('<small class="text-muted">Loading...</small>');
            
            // Load latest service to pre-fill (optional - gives context)
            $.get('../../Ajax/php/vehicle.php', { action: 'get_latest_service', vehicle_id: vehicleId }, function(res) {
                if (res.status === 'success' && res.data) {
                    const d = res.data;
                    let html = '';
                    
                    if (d.service_date) {
                        html += `<div class="d-inline-flex align-items-center px-3 py-2 rounded-pill me-2 mb-1" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-size: 0.85rem;">
                            <i class="fas fa-calendar-check me-2"></i>
                            <span><strong>Last:</strong> ${d.service_date}</span>
                        </div>`;
                    }
                    if (d.current_mileage) {
                        html += `<div class="d-inline-flex align-items-center px-3 py-2 rounded-pill me-2 mb-1" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; font-size: 0.85rem;">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            <span><strong>${Number(d.current_mileage).toLocaleString()}</strong> km</span>
                        </div>`;
                    }
                    if (d.next_service_mileage) {
                        html += `<div class="d-inline-flex align-items-center px-3 py-2 rounded-pill me-2 mb-1" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #1f2937; font-size: 0.85rem;">
                            <i class="fas fa-arrow-right me-2"></i>
                            <span><strong>Next:</strong> ${Number(d.next_service_mileage).toLocaleString()} km</span>
                        </div>`;
                        $('#current_mileage').attr('placeholder', 'Last next: ' + d.next_service_mileage);
                    }
                    if (d.next_service_date) {
                        html += `<div class="d-inline-flex align-items-center px-3 py-2 rounded-pill mb-1" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; font-size: 0.85rem;">
                            <i class="fas fa-calendar me-2"></i>
                            <span><strong>Next:</strong> ${d.next_service_date}</span>
                        </div>`;
                    }
                    
                    $('#lastServiceInfo').html(html || '<span class="text-muted small">No data available</span>');
                } else {
                    $('#lastServiceInfo').html('<span class="text-muted small"><i class="fas fa-info-circle me-1"></i>No previous service records</span>');
                }
            });
        }

        // Autocomplete for Customer
        new Autocomplete({
            inputSelector: '#customer_name',
            minChars: 1,
            fetchData: function(term, callback) {
                // Use absolute path for reliability
                $.get('<?php echo BASE_URL; ?>Ajax/php/customer.php', { action: 'search', term: term }, function(res) {
                    if (res.status === 'success') {
                        callback(res.data);
                    } else {
                        callback([]);
                    }
                }).fail(function() {
                    console.error("Customer search failed");
                    callback([]);
                });
            },
            renderItem: function(c) {
                return `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-search text-secondary me-3"></i>
                        <div>
                            <div class="fw-bold text-white">${c.name}</div>
                            <small class="text-muted" style="color: #9ca3af !important;">${c.phone}</small>
                        </div>
                    </div>
                `;
            },
            onSelect: function(customer) {
                $('#customer_id').val(customer.id);
                $('#customer_name').val(customer.name);
                $('#customer_mobile').val(customer.phone || '');
                fetchAndSelectCustomerVehicle(customer.id);
            }
        });

        // Initialize Customer Selector Modal
        const customerSelector = new CustomerSelectorModal({
            onSelect: function(customer) {
                $('#customer_id').val(customer.id);
                $('#customer_name').val(customer.name);
                $('#customer_mobile').val(customer.phone || '');
                fetchAndSelectCustomerVehicle(customer.id);
            }
        });

        $('#browseCustomerBtn').click(function() {
            customerSelector.show();
        });

        // Initialize Item Selector Modal
        const invoiceItemSelector = new ItemSelectorModal({
            onSelect: function(item) {
                if (currentItemRow) {
                    currentItemRow.find('.item-id').val(item.id);
                    currentItemRow.find('.item-available-stock').val(item.current_stock || 0);
                    currentItemRow.find('.item-display').val(item.item_code + ' - ' + item.item_name);
                    currentItemRow.find('.item-description').val(item.item_name);
                    currentItemRow.find('.item-price').val(item.unit_price || item.selling_price || item.unit_cost || 0).trigger('input');
                    
                    // Show available stock
                    const stockDisplay = currentItemRow.find('.stock-info');
                    stockDisplay.html(`<small class="text-muted">Available: ${item.current_stock}</small>`);
                    
                    // Trigger qty validation
                    currentItemRow.find('.item-qty').trigger('input');
                }
            }
        });

        // Quick Add Button Handler
        $('#quickAddBtn').click(function() {
            $('#addBothModal').modal('show');
        });

        // Quick Add Form Submission
        $('#addBothForm').on('submit', function(e) {
            e.preventDefault();
            if (!this.checkValidity()) {
                $(this).addClass('was-validated');
                return;
            }

            const btn = $('#saveBothBtn');
            const originalBtnText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            // 1. Create Customer
            const customerData = {
                action: 'create',
                name: $('#both_name').val(),
                phone: $('#both_phone').val(),
                email: $('#both_email').val(),
                address: '' // Optional
            };

            $.post('<?php echo BASE_URL; ?>Ajax/php/customer.php', customerData, function(cRes) {
                if (cRes.status === 'success') {
                    const customerId = cRes.id;
                    const customerName = customerData.name;
                    const customerPhone = customerData.phone;

                    // 2. Create Vehicle
                    const vehicleData = {
                        action: 'create',
                        customer_id: customerId,
                        registration_number: $('#both_registration').val(),
                        make: $('#both_make').val(),
                        model: $('#both_model').val(),
                        year: $('#both_year').val(),
                        current_mileage: $('#both_mileage').val(),
                        color: '' // Optional
                    };

                    $.post('<?php echo BASE_URL; ?>Ajax/php/vehicle.php', vehicleData, function(vRes) {
                        if (vRes.status === 'success') {
                            const vehicleId = vRes.id;
                            const regNo = vehicleData.registration_number;
                            const make = vehicleData.make;
                            const model = vehicleData.model;

                            // Success! Fill the invoice inputs
                            $('#customer_id').val(customerId);
                            $('#customer_name').val(customerName);
                            $('#customer_mobile').val(customerPhone);
                            
                            $('#vehicle_id').val(vehicleId);
                            $('#vehicle_display').val(regNo + ' - ' + make + ' ' + model);

                            // Close modal and reset
                            $('#addBothModal').modal('hide');
                            $('#addBothForm')[0].reset();
                            $('#addBothForm').removeClass('was-validated');
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Customer and Vehicle added successfully!',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Refresh local vehicle list for autocomplete
                             $.get('../../Ajax/php/vehicle.php?action=list_with_customer', function(res) {
                                if (res.status === 'success') {
                                    vehiclesData = res.data;
                                }
                            });

                        } else {
                            Swal.fire('Error', vRes.message || 'Failed to create vehicle', 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'Failed to save vehicle details', 'error');
                    }).always(function() {
                        btn.prop('disabled', false).html(originalBtnText);
                    });

                } else {
                    Swal.fire('Error', cRes.message || 'Failed to create customer', 'error');
                    btn.prop('disabled', false).html(originalBtnText);
                }
            }).fail(function() {
                Swal.fire('Error', 'Failed to save customer details', 'error');
                btn.prop('disabled', false).html(originalBtnText);
            });
        });

        // Add item row function
        function addItemRow(data = null) {
            let serviceOptions = '<option value="">-- Select Service --</option>';
            activeServicePackages.forEach(pkg => {
                const selected = data && data.item_type === 'service' && data.item_id == pkg.id ? 'selected' : '';
                serviceOptions += `<option value="${pkg.id}" data-price="${pkg.base_price}" data-name="${pkg.package_name}" ${selected}>${pkg.package_name}</option>`;
            });

            const row = `
                <div class="item-row" data-index="${itemCounter}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-lg" style="flex: 0 0 15%;">
                            <label class="form-label d-lg-none">Type</label>
                            <select class="form-select form-select-sm item-type" required>
                                <option value="">-- Select --</option>
                                <option value="service" ${data && data.item_type === 'service' ? 'selected' : ''}>Service</option>
                                <option value="inventory" ${(!data || data.item_type === 'inventory') ? 'selected' : ''}>Inventory</option>
                                <option value="labor" ${data && data.item_type === 'labor' ? 'selected' : ''}>Labor</option>
                                <option value="other" ${data && data.item_type === 'other' ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg" style="flex: 0 0 30%;">
                            <label class="form-label d-lg-none">Description</label>
                            <input type="hidden" class="item-id" value="${data ? (data.item_id || '') : ''}">
                            
                            <div class="input-group input-group-sm item-browse-group" style="display: ${(!data || data.item_type === 'inventory') ? 'flex' : 'none'};">
                                <input type="text" class="form-control item-display" placeholder="Type to search or browse..." value="${data ? (data.item_name || data.description || '') : ''}">
                                <button type="button" class="btn btn-outline-secondary browse-item-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <select class="form-select form-select-sm item-service-package" style="display: ${data && data.item_type === 'service' ? 'block' : 'none'};" ${data && data.item_type === 'service' ? 'required' : ''}>
                                ${serviceOptions}
                            </select>

                            <input type="text" class="form-control form-control-sm item-description" placeholder="Enter description" style="display: ${data && (data.item_type === 'labor' || data.item_type === 'other') ? 'block' : 'none'};" ${data && (data.item_type === 'labor' || data.item_type === 'other') ? 'required' : ''} value="${data ? (data.description || '') : ''}">
                        </div>
                        <div class="col-6 col-lg" style="flex: 0 0 12%;">
                            <label class="form-label d-lg-none">Qty</label>
                            <input type="hidden" class="item-available-stock" value="${data ? (data.current_stock || 9999) : 0}">
                            <input type="number" step="0.01" class="form-control form-control-sm item-qty" placeholder="Qty" value="${data ? data.quantity : 1}" required>
                            <div class="stock-info mt-1" style="font-size: 0.7rem; min-height: 15px;">
                                ${data && data.item_type === 'inventory' ? `<small class="text-muted">Available: ${data.current_stock || 0}</small>` : ''}
                            </div>
                        </div>
                        <div class="col-6 col-lg" style="flex: 0 0 15%;">
                            <label class="form-label d-lg-none">Unit Price</label>
                            <input type="number" step="0.01" class="form-control form-control-sm item-price" placeholder="Price" required value="${data ? data.unit_price : ''}">
                        </div>
                        <div class="col-6 col-lg" style="flex: 0 0 15%;">
                            <label class="form-label d-lg-none">Total</label>
                            <input type="text" class="form-control form-control-sm item-total" readonly placeholder="0.00" value="${data ? (data.quantity * data.unit_price).toFixed(2) : ''}">
                        </div>
                        <div class="col-6 col-lg text-end" style="flex: 0 0 8%;">
                            <button type="button" class="btn btn-sm remove-item" title="Remove item">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>`;

            const $row = $(row);
            $('#itemsContainer').append($row);
            
            // Initialize Autocomplete for this row
            if (!data || data.item_type === 'inventory' || !data.item_type) {
                initializeItemAutocomplete($row);
            }
            
            itemCounter++;
            calculateTotals();
        }

        // Helper to initialize autocomplete on item row
        function initializeItemAutocomplete(row) {
            const input = row.find('.item-display');
            
            new Autocomplete({
                inputSelector: input,
                minChars: 1,
                fetchData: function(term, callback) {
                    // Only search if type is inventory
                    if (row.find('.item-type').val() !== 'inventory') {
                        callback([]);
                        return;
                    }

                    $.get('../../Ajax/php/inventory_item.php', { 
                        action: 'search', 
                        term: term 
                    }, function(res) {
                        if (res.status === 'success') {
                            callback(res.data);
                        } else {
                            callback([]);
                        }
                    });
                },
                renderItem: function(item) {
                    return `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${item.item_name}</strong> <span class="text-secondary small">(${item.item_code})</span>
                                <br>
                                <span class="text-white-50 small">Stock: ${item.current_stock} | Price: ${item.unit_price}</span>
                            </div>
                        </div>
                    `;
                },
                onSelect: function(item) {
                    row.find('.item-id').val(item.id);
                    row.find('.item-display').val(item.item_name);
                    row.find('.item-price').val(item.unit_price);
                    row.find('.item-available-stock').val(item.current_stock);
                    
                    // Update stock info display
                    row.find('.stock-info').html(`<small class="text-muted">Available: ${item.current_stock}</small>`);
                    
                    // Recalculate totals
                    const qty = parseFloat(row.find('.item-qty').val()) || 1;
                    const total = qty * parseFloat(item.unit_price);
                    row.find('.item-total').val(total.toFixed(2));
                    calculateTotals();
                    
                    // Validate stock
                    if (qty > item.current_stock) {
                         row.find('.item-qty').addClass('is-invalid');
                    } else {
                         row.find('.item-qty').removeClass('is-invalid');
                    }
                }
            });
        }

        $('#addItemBtn').click(function() {
            addItemRow();
        });

        function loadInvoiceForEdit(id) {
            $.get('../../Ajax/php/invoice.php', { action: 'get', id: id }, function(res) {
                if (res.status === 'success') {
                    const invoice = res.data;
                    $('#invoice_number_display').html('<i class="fas fa-hashtag me-1"></i> ' + invoice.invoice_number);
                    $('#customer_id').val(invoice.customer_id || 0);
                    $('#customer_name').val(invoice.customer_name || '');
                    $('#customer_mobile').val(invoice.customer_mobile || '');
                    $('#invoice_date').val(invoice.created_at.split(' ')[0]);
                    $('#payment_method').val(invoice.payment_method || '');
                    $('#payment_date').val(invoice.payment_date || '');
                    $('#account_id').val(invoice.account_id || ''); // Added
                    $('#bill_type').val(invoice.bill_type || 'cash'); // Added
                    $('#tax_amount').val(invoice.tax_amount || 0);
                    $('#discount_amount').val(invoice.discount_amount || 0);
                    
                    // Load items
                    if (invoice.items && invoice.items.length > 0) {
                        invoice.items.forEach(item => {
                            addItemRow(item);
                        });
                    }
                } else {
                    Swal.fire('Error', 'Failed to load invoice data', 'error');
                }
            });
        }

        // Handle item type change
        $(document).on('change', '.item-type', function() {
            const row = $(this).closest('.item-row');
            const type = $(this).val();
            
            row.find('.item-browse-group').hide();
            row.find('.item-service-package').hide().prop('required', false);
            row.find('.item-description').hide().prop('required', false);
            
            if(type === 'inventory') {
                row.find('.item-browse-group').show();
                row.find('.item-id').val('');
                row.find('.item-display').val('');
            } else if (type === 'service') {
                row.find('.item-service-package').show().prop('required', true);
                row.find('.item-description').val('');
            } else {
                row.find('.item-description').show().prop('required', true);
            }
        });

        // Handle Service Package Selection
        $(document).on('change', '.item-service-package', function() {
            const row = $(this).closest('.item-row');
            const selectedOption = $(this).find('option:selected');
            const price = selectedOption.data('price') || 0;
            row.find('.item-price').val(price).trigger('input');
        });

        // Open item selector
        $(document).on('click', '.browse-item-btn', function() {
            currentItemRow = $(this).closest('.item-row');
            invoiceItemSelector.show();
        });

        // Calculate row total and check stock
        $(document).on('input', '.item-qty, .item-price', function() {
            const row = $(this).closest('.item-row');
            const type = row.find('.item-type').val();
            const qty = parseFloat(row.find('.item-qty').val()) || 0;
            const price = parseFloat(row.find('.item-price').val()) || 0;
            
            // Stock check for inventory items
            if (type === 'inventory') {
                const available = parseFloat(row.find('.item-available-stock').val()) || 0;
                const qtyInput = row.find('.item-qty');
                const stockInfo = row.find('.stock-info');
                
                if (qty > available) {
                    qtyInput.addClass('is-invalid');
                    stockInfo.find('small').addClass('text-danger fw-bold').removeClass('text-muted');
                } else {
                    qtyInput.removeClass('is-invalid');
                    stockInfo.find('small').removeClass('text-danger fw-bold').addClass('text-muted');
                }
            }

            row.find('.item-total').val((qty * price).toFixed(2));
            calculateTotals();
        });

        // Remove item
        $(document).on('click', '.remove-item', function() {
            $(this).closest('.item-row').remove();
            calculateTotals();
        });

        // Calculate totals
        $(document).on('input', '#tax_amount, #discount_amount', calculateTotals);

        function calculateTotals() {
            let subtotal = 0;
            $('.item-total').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });
            const tax = parseFloat($('#tax_amount').val()) || 0;
            const discount = parseFloat($('#discount_amount').val()) || 0;
            const total = subtotal + tax - discount;
            
            $('#subtotal').text('Rs. ' + subtotal.toFixed(2));
            $('#total_amount').text('Rs. ' + total.toFixed(2));
        }

        // Submit form
        $('#invoiceForm').submit(function(e) {
            e.preventDefault();
            
            const items = [];
            let hasErrors = false;
            
            $('.item-row').each(function() {
                const type = $(this).find('.item-type').val();
                const itemId = $(this).find('.item-id').val();
                let description = $(this).find('.item-description').val();
                let selectedPackageId = null;

                if (!type) {
                    $(this).find('.item-type').addClass('is-invalid');
                    hasErrors = true;
                    return;
                }
                
                if (type === 'inventory' && !itemId) {
                    $(this).find('.item-display').addClass('is-invalid');
                    hasErrors = true;
                    return;
                }

                if (type === 'service') {
                    const pkgSelect = $(this).find('.item-service-package');
                    if (!pkgSelect.val()) {
                        pkgSelect.addClass('is-invalid');
                        hasErrors = true;
                        return;
                    }
                    pkgSelect.removeClass('is-invalid');
                    description = pkgSelect.find('option:selected').text();
                    selectedPackageId = pkgSelect.val();
                } else if (type !== 'inventory') {
                    if (!description) {
                        $(this).find('.item-description').addClass('is-invalid');
                        hasErrors = true;
                        return;
                    }
                    $(this).find('.item-description').removeClass('is-invalid');
                }
                
                $(this).find('.item-type').removeClass('is-invalid');
                $(this).find('.item-display').removeClass('is-invalid');
                
                items.push({
                    item_type: type,
                    item_id: type === 'inventory' ? itemId : (type === 'service' ? selectedPackageId : null),
                    description: description,
                    quantity: $(this).find('.item-qty').val(),
                    unit_price: $(this).find('.item-price').val(),
                    total_price: $(this).find('.item-total').val()
                });
            });

            if(items.length === 0) {
                Swal.fire('Error', 'Please add at least one item', 'error');
                return;
            }
            
            if(hasErrors) {
                Swal.fire('Error', 'Please fill in all required fields accurately', 'error');
                return;
            }

            // Final stock check before submission
            let stockError = false;
            $('.item-row').each(function() {
                const type = $(this).find('.item-type').val();
                if (type === 'inventory') {
                    const qty = parseFloat($(this).find('.item-qty').val()) || 0;
                    const available = parseFloat($(this).find('.item-available-stock').val()) || 0;
                    if (qty > available) {
                        stockError = true;
                        $(this).find('.item-qty').addClass('is-invalid');
                    }
                }
            });

            if (stockError) {
                Swal.fire('Insufficient Stock', 'One or more items exceed available stock levels.', 'error');
                return;
            }

            const subtotal = parseFloat($('#subtotal').text().replace('Rs. ', '').replace(',', ''));
            const total = parseFloat($('#total_amount').text().replace('Rs. ', '').replace(',', ''));

            $('#saveInvoiceBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');

            $.post('../../Ajax/php/invoice.php', {
                action: editInvoiceId ? 'update' : 'create',
                id: editInvoiceId,
                service_id: null,
                customer_id: $('#customer_id').val(),
                customer_name: $('#customer_name').val(),
                customer_mobile: $('#customer_mobile').val(),
                subtotal: subtotal,
                tax_amount: $('#tax_amount').val(),
                discount_amount: $('#discount_amount').val(),
                total_amount: total,
                payment_method: $('#payment_method').val(),
                payment_date: $('#payment_date').val(),
                account_id: $('#account_id').val(), // Added
                bill_type: $('#bill_type').val(), // Added
                items: JSON.stringify(items)
            }, function(res) {
                $('#saveInvoiceBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>' + (editInvoiceId ? 'Update' : 'Create') + ' Invoice');

                if(res.status === 'success') {
                    
                    // Save Service History if vehicle is selected
                    const vehicleId = $('#vehicle_id').val();
                    const currentMileage = $('#current_mileage').val();
                    const nextServiceMileage = $('#next_service_mileage').val();
                    const nextServiceDate = $('#next_service_date').val();
                    const serviceNotes = $('#service_notes').val();
                    
                    if (vehicleId && (currentMileage || nextServiceMileage || nextServiceDate)) {
                        $.post('../../Ajax/php/vehicle.php', {
                            action: 'save_service_history',
                            vehicle_id: vehicleId,
                            invoice_id: res.invoice_id || editInvoiceId,
                            service_date: $('#invoice_date').val(),
                            current_mileage: currentMileage,
                            next_service_mileage: nextServiceMileage,
                            next_service_date: nextServiceDate,
                            notes: serviceNotes
                        });
                    }

                    // Create Service Record if vehicle is selected (for new invoices)
                    if (!editInvoiceId && vehicleId) {
                        $.post('../../Ajax/php/service.php', {
                            action: 'create_from_invoice',
                            vehicle_id: vehicleId,
                            customer_id: $('#customer_id').val(),
                            invoice_id: res.invoice_id,
                            total_amount: total,
                            notes: 'Invoice #' + (res.invoice_number || res.invoice_id)
                        });
                    }

                    if (!editInvoiceId && res.invoice_id) {
                        window.open('print.php?id=' + res.invoice_id, '_blank');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        });
    });
    </script>
    <!-- Quick Add Customer & Vehicle Modal -->
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
                                <h6 class="section-title customer text-success border-bottom pb-2 mb-3"><i class="fas fa-user me-2"></i>Customer Details</h6>
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
                                <h6 class="section-title vehicle text-primary border-bottom pb-2 mb-3"><i class="fas fa-car me-2"></i>Vehicle Details</h6>
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
</body>
</html>
