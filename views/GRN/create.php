<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');

$grnId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pageTitle = $grnId ? "Edit GRN #$grnId" : "Create New GRN";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $pageTitle; ?></title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        /* ... existing styles ... */
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
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
            border-left: 4px solid var(--warning-color);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        
        /* ... rest of existing styles ... */
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

        .grn-number-badge {
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--warning-color);
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
            border-color: var(--primary-color);
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
            color: var(--warning-color);
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

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #047857;
            border-color: #047857;
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
                        <div class="title-section">
                            <h1><i class="fas fa-file-import me-2"></i><?php echo $pageTitle; ?></h1>
                            <p><?php echo $grnId ? 'Update GRN details and items' : 'Record incoming stock from supplier'; ?></p>
                        </div>
                        <div class="grn-number-badge">
                            <i class="fas fa-truck me-1"></i> Goods Received Note
                        </div>
                    </div>

                    <form id="grnForm">
                        <!-- Supplier Details Card -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5><i class="fas fa-truck me-2"></i>Supplier Details</h5>
                            </div>
                            <div class="form-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-3"> 
                                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                        <input type="hidden" id="supplier_id" required>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="supplier_display" placeholder="Type to search..." autocomplete="off" required>
                                            <button class="btn btn-outline-secondary" type="button" id="browseSupplierBtn"><i class="fas fa-search"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">GRN Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="grn_date" required>
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" class="form-control" id="due_date">
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Supplier Invoice #</label>
                                        <input type="text" class="form-control" id="invoice_number" placeholder="Enter supplier invoice">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Card -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5><i class="fas fa-boxes me-2"></i>GRN Items</h5>
                            </div>
                            
                            <!-- Desktop Header -->
                            <div class="items-header">
                                <div style="flex: 0 0 35%;">Item</div>
                                <div style="flex: 0 0 15%;">Quantity</div>
                                <div style="flex: 0 0 18%;">Unit Price</div>
                                <div style="flex: 0 0 18%;">Total</div>
                                <div style="flex: 0 0 10%;">Action</div>
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

                        <!-- Notes & Summary Row -->
                        <div class="row">
                            <!-- Notes -->
                            <div class="col-lg-7 mb-3 mb-lg-0">
                                <div class="form-card h-100">
                                    <div class="form-card-header">
                                        <h5><i class="fas fa-sticky-note me-2"></i>Additional Notes</h5>
                                    </div>
                                    <div class="form-card-body">
                                        <textarea class="form-control" id="notes" rows="4" placeholder="Enter any notes or remarks about this GRN..."></textarea>
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
                                                <label>Net Amount</label>
                                                <span class="value" id="net_amount">Rs. 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary" name="status" value="draft">
                                            <i class="fas fa-save me-1"></i><?php echo $grnId ? 'Update Draft' : 'Save as Draft'; ?>
                                        </button>
                                        <button type="submit" class="btn btn-success" name="status" value="received">
                                            <i class="fas fa-check me-1"></i>Mark as Received
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
    <script src="<?php echo BASE_URL; ?>Ajax/js/autocomplete_helper.js"></script>
    <script src="<?php echo BASE_URL; ?>Ajax/js/item_selector_modal.js"></script>
    <script>
    let itemCounter = 0;
    let currentItemRow = null;
    let availableItems = [];
    const editingGrnId = <?php echo $grnId; ?>;

    $(document).ready(function() {
        // Set today's date only if NOT editing
        if (!editingGrnId) {
            $('#grn_date').val(new Date().toISOString().split('T')[0]);
        }

        let availableSuppliers = [];

        // Load suppliers
        $.get('../../Ajax/php/grn.php', {action: 'suppliers'}, function(res) {
            if(res.status === 'success') {
                availableSuppliers = res.data.filter(s => s.is_active == 1);
                // If editing, trigger data load after suppliers are populated
                if (editingGrnId) {
                    loadGrnData();
                } else {
                     // Init Supplier Autocomplete for Create Mode
                     initSupplierAutocomplete();
                }
            }
        });

        function initSupplierAutocomplete() {
            new Autocomplete({
                inputSelector: '#supplier_display',
                minChars: 0,
                fetchData: function(term, callback) {
                    term = term.toLowerCase();
                    const matches = availableSuppliers.filter(s => 
                        s.supplier_name.toLowerCase().includes(term) || 
                        (s.contact_person && s.contact_person.toLowerCase().includes(term)) ||
                        (s.phone && s.phone.includes(term))
                    );
                    callback(matches);
                },
                renderItem: function(s) {
                    return `
                        <div class="d-flex align-items-center">
                            <div class="icon-circle bg-secondary bg-opacity-10 text-secondary me-2" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-white">${s.supplier_name}</div>
                                <small class="text-muted" style="color:#9ca3af!important">${s.contact_person || ''} ${s.phone ? ' - ' + s.phone : ''}</small>
                            </div>
                        </div>
                    `;
                },
                onSelect: function(s) {
                    $('#supplier_id').val(s.id);
                    $('#supplier_display').val(s.supplier_name);
                }
            });
            
            // Show all on click of browse button
            $('#browseSupplierBtn').click(function() {
                $('#supplier_display').focus().trigger('input');
            });
        }


        // Load items for modal
        $.get('../../Ajax/php/grn.php', {action: 'items'}, function(res) {
            if(res.status === 'success') {
                availableItems = res.data.filter(i => i.is_active == 1);
            }
        });

        function loadGrnData() {
            $.get('../../Ajax/php/grn.php', {action: 'get', id: editingGrnId}, function(res) {
                if (res.status === 'success') {
                    const grn = res.data;
                    
                    // Populate Header
                    $('#supplier_id').val(grn.supplier_id);
                    $('#supplier_display').val(grn.supplier_name || 'Unknown Supplier'); 
                    // Init autocomplete now that we have data
                    initSupplierAutocomplete();
                    $('#grn_date').val(grn.grn_date);
                    $('#due_date').val(grn.due_date);
                    $('#invoice_number').val(grn.invoice_number);
                    $('#notes').val(grn.notes);
                    $('#tax_amount').val(grn.tax_amount);
                    $('#discount_amount').val(grn.discount_amount);
                    
                    // Populate Items
                    if (grn.items && grn.items.length > 0) {
                        grn.items.forEach(item => {
                            addItemRow(item);
                        });
                        calculateTotals();
                    }
                    
                    // Status Badge if needed
                    $('.grn-number-badge').html(`<i class="fas fa-truck me-1"></i> ${grn.grn_number}`);
                }
            });
        }

        function addItemRow(itemData = null) {
            const row = `
                <div class="item-row" data-index="${itemCounter}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-lg" style="flex: 0 0 35%;">
                            <label class="form-label d-lg-none">Item</label>
                            <input type="hidden" class="item-id" required value="${itemData ? itemData.item_id : ''}">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control item-display" placeholder="Type or click..." value="${itemData ? (itemData.item_code + ' - ' + itemData.item_name) : ''}" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary browse-item-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-6 col-lg" style="flex: 0 0 15%;">
                            <label class="form-label d-lg-none">Quantity</label>
                            <input type="number" step="0.01" class="form-control form-control-sm item-qty" placeholder="Qty" value="${itemData ? itemData.quantity : '1'}" required>
                        </div>
                        <div class="col-6 col-lg" style="flex: 0 0 18%;">
                            <label class="form-label d-lg-none">Unit Price</label>
                            <input type="number" step="0.01" class="form-control form-control-sm item-price" placeholder="Price" value="${itemData ? itemData.unit_price : ''}" required>
                        </div>
                        <div class="col-6 col-lg" style="flex: 0 0 18%;">
                            <label class="form-label d-lg-none">Total</label>
                            <input type="text" class="form-control form-control-sm item-total" readonly placeholder="0.00" value="${itemData ? itemData.total_price : ''}">
                        </div>
                        <div class="col-6 col-lg text-end" style="flex: 0 0 10%;">
                            <button type="button" class="btn btn-sm remove-item" title="Remove item">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            const $row = $(row);
            $('#itemsContainer').append($row);
            itemCounter++;
            
            // Init Autocomplete for this row
            new Autocomplete({
                inputSelector: $row.find('.item-display'),
                minChars: 0,
                fetchData: function(term, callback) {
                    term = term.toLowerCase();
                    const matches = availableItems.filter(i => 
                        i.item_code.toLowerCase().includes(term) || 
                        i.item_name.toLowerCase().includes(term)
                    ).slice(0, 15);
                    callback(matches);
                },
                renderItem: function(i) {
                     return `
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <div class="fw-bold text-white">${i.item_code}</div>
                            </div>
                            <div>
                                <div class="text-white">${i.item_name}</div>
                                <small class="text-muted" style="color:#9ca3af!important">In Stock: ${i.current_stock}</small>
                            </div>
                        </div>
                    `;
                },
                onSelect: function(i) {
                   $row.find('.item-id').val(i.id);
                   $row.find('.item-display').val(i.item_code + ' - ' + i.item_name);
                   $row.find('.item-price').val(i.unit_cost || 0).trigger('input');
                }
            });
        }

        // Initialize Item Selector Modal for GRN
        const grnItemSelector = new ItemSelectorModal({
            onSelect: function(item) {
                if (currentItemRow) {
                    currentItemRow.find('.item-id').val(item.id);
                    currentItemRow.find('.item-display').val(item.item_code + ' - ' + item.item_name);
                    currentItemRow.find('.item-price').val(item.unit_cost || 0).trigger('input');
                }
            }
        });

        // Add item row
        $('#addItemBtn').click(function() {
            addItemRow();
        });

        // Open Item Selector (Modal Button Only)
        $(document).on('click', '.browse-item-btn', function() {
            currentItemRow = $(this).closest('.item-row');
            grnItemSelector.show();
        });

        // Calculate row total
        $(document).on('input', '.item-qty, .item-price', function() {
            const row = $(this).closest('.item-row');
            const qty = parseFloat(row.find('.item-qty').val()) || 0;
            const price = parseFloat(row.find('.item-price').val()) || 0;
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
            const net = subtotal + tax - discount;
            
            $('#subtotal').text('Rs. ' + subtotal.toFixed(2));
            $('#net_amount').text('Rs. ' + net.toFixed(2));
        }

        // Submit form
        $('#grnForm').submit(function(e) {
            e.preventDefault();
            const status = $(document.activeElement).val();
            
            const items = [];
            let hasErrors = false;
            
            $('.item-row').each(function() {
                const itemId = $(this).find('.item-id').val();
                if (!itemId) {
                    $(this).find('.item-display').addClass('is-invalid');
                    hasErrors = true;
                } else {
                    $(this).find('.item-display').removeClass('is-invalid');
                    items.push({
                        item_id: itemId,
                        quantity: $(this).find('.item-qty').val(),
                        unit_price: $(this).find('.item-price').val(),
                        total_price: $(this).find('.item-total').val()
                    });
                }
            });

            if(items.length === 0) {
                Swal.fire('Error', 'Please add at least one item', 'error');
                return;
            }

            if(hasErrors) {
                Swal.fire('Error', 'Please select an item for all rows', 'error');
                return;
            }

            const subtotal = parseFloat($('#subtotal').text().replace('Rs. ', ''));
            const net = parseFloat($('#net_amount').text().replace('Rs. ', ''));
            
            // Determine action (create or update)
            const action = editingGrnId ? 'update' : 'create';
            const payload = {
                action: action,
                supplier_id: $('#supplier_id').val(),
                grn_date: $('#grn_date').val(),
                due_date: $('#due_date').val(),
                invoice_number: $('#invoice_number').val(),
                total_amount: subtotal,
                tax_amount: $('#tax_amount').val(),
                discount_amount: $('#discount_amount').val(),
                net_amount: net,
                status: status,
                notes: $('#notes').val(),
                items: JSON.stringify(items)
            };

            if (editingGrnId) {
                payload.id = editingGrnId;
            }

            $.post('../../Ajax/php/grn.php', payload, function(res) {
                if(res.status === 'success') {
                    Swal.fire('Success', res.message, 'success').then(() => {
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
