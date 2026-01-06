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
                                        <label class="form-label">Customer Name</label>
                                        <div class="input-group">
                                            <input type="hidden" id="customer_id" value="0">
                                            <input type="text" class="form-control" id="customer_name" placeholder="Enter or select customer">
                                            <button class="btn btn-outline-secondary" type="button" id="browseCustomerBtn" title="Browse customers">
                                                <i class="fas fa-search"></i>
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
                                            <div class="summary-row align-items-center">
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
                });
            }
        });



        // Initialize Customer Selector Modal
        const customerSelector = new CustomerSelectorModal({
            onSelect: function(customer) {
                $('#customer_id').val(customer.id);
                $('#customer_name').val(customer.name);
                $('#customer_mobile').val(customer.phone || '');
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
                                <input type="text" class="form-control item-display" placeholder="Click to browse..." readonly style="cursor: pointer;" value="${data ? (data.item_name || data.description || '') : ''}">
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
            $('#itemsContainer').append(row);
            itemCounter++;
            calculateTotals();
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
        $(document).on('click', '.browse-item-btn, .item-display', function() {
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
</body>
</html>
