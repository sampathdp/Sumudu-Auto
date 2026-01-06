<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('Create');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Create Supplier Payment</title>
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

        /* Payment Specific Styles */
        .grn-row { cursor: pointer; }
        .grn-row:hover { background: #f8fafc; }
        .grn-row.selected { background: #e0e7ff !important; }
        .payment-method-item { background: #f8fafc; border-radius: 8px; padding: 1.5rem; border: 1px solid #e2e8f0; position: relative; margin-bottom: 1rem; }
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
                                <h1 class="page-title"><i class="fas fa-plus-circle me-2"></i>Create Payment</h1>
                                <p class="page-subtitle">Process supplier payments and settle GRNs</p>
                            </div>
                            <a href="../SupplierPayment/" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Select Supplier</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="step-label">Select GRNs</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="step-label">Payment Methods</div>
                        </div>
                        <div class="step" data-step="4">
                            <div class="step-circle">4</div>
                            <div class="step-label">Confirmation</div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form id="paymentForm">
                        <div class="row">
                            <!-- Left Column - Form Steps -->
                            <div class="col-lg-8">
                                <!-- Step 1: Supplier Selection -->
                                <div class="card form-step" id="step1">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="fas fa-user-tag me-2"></i>Supplier Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-7 mb-3">
                                                <label class="form-label">Select Supplier <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-lg" id="supplierSelect" required>
                                                    <option value="">-- Choose Supplier --</option>
                                                </select>
                                            </div>
                                            <div class="col-md-5 mb-3">
                                                <label class="form-label">Payment Date</label>
                                                <input type="date" class="form-control form-control-lg" id="paymentDate" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        
                                        <div id="supplierInfoBox" class="mt-3" style="display: none;">
                                            <div class="alert alert-info border-0 shadow-sm">
                                                <div class="d-flex align-items-center">
                                                    <div class="display-4 me-3 text-primary"><i class="fas fa-wallet"></i></div>
                                                    <div>
                                                        <h6 class="text-uppercase text-muted fw-bold small mb-1">Current Balance Due</h6>
                                                        <h3 class="mb-0 fw-bold text-dark" id="supplierBalance">LKR 0.00</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end mt-4">
                                            <button type="button" class="btn btn-primary next-step" id="step1Next">
                                                Next <i class="fas fa-arrow-right ms-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2: GRN Selection -->
                                <div class="card form-step" id="step2" style="display: none;">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title"><i class="fas fa-file-invoice me-2"></i>Outstanding GRNs</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                            <table class="table table-hover mb-0" id="outstandingGrnsTable">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th class="ps-4" width="40"><input type="checkbox" id="selectAllGrns" class="form-check-input"></th>
                                                        <th>GRN Info</th>
                                                        <th class="text-end">Balance Due</th>
                                                        <th class="text-end pe-4" width="180">Pay Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr><td colspan="4" class="text-center py-5 text-muted">Select a supplier first</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="p-3 border-top d-flex justify-content-between align-items-center bg-light rounded-bottom">
                                            <button type="button" class="btn btn-outline-secondary prev-step">
                                                <i class="fas fa-arrow-left me-1"></i> Previous
                                            </button>
                                            <button type="button" class="btn btn-primary next-step">
                                                Next <i class="fas fa-arrow-right ms-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 3: Payment Methods -->
                                <div class="card form-step" id="step3" style="display: none;">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title"><i class="fas fa-credit-card me-2"></i>Payment Methods</h5>
                                        <button type="button" class="btn btn-sm btn-primary" id="addMethodBtn">
                                            <i class="fas fa-plus me-1"></i> Add Method
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="paymentMethodsContainer">
                                            <!-- Methods added dynamically -->
                                        </div>

                                        <div class="d-flex justify-content-between mt-4">
                                            <button type="button" class="btn btn-outline-secondary prev-step">
                                                <i class="fas fa-arrow-left me-1"></i> Previous
                                            </button>
                                            <button type="button" class="btn btn-primary next-step" id="step3Next">
                                                Next <i class="fas fa-arrow-right ms-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 4: Confirmation -->
                                <div class="card form-step" id="step4" style="display: none;">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="fas fa-check-circle me-2"></i>Review & Confirm</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-primary mb-4">
                                            <div class="d-flex">
                                                <i class="fas fa-info-circle fa-2x me-3 mt-1"></i>
                                                <div>
                                                    <h5 class="alert-heading fw-bold">Ready to finalize?</h5>
                                                    <p class="mb-0">Please review the payment details below. Once confirmed, the supplier ledger and GRN statuses will be updated immediately.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label">Internal Notes (Optional)</label>
                                            <textarea class="form-control" id="paymentNotes" rows="3" placeholder="Enter any specific notes for this transaction..."></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card bg-light border-0 h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-muted mb-3">Settlement Allocation</h6>
                                                        <ul class="list-unstyled mb-0" id="confirmAllocationsList"></ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card bg-light border-0 h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-muted mb-3">Payment Sources</h6>
                                                        <ul class="list-unstyled mb-0" id="confirmMethodsList"></ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between mt-4">
                                            <button type="button" class="btn btn-outline-secondary prev-step">
                                                <i class="fas fa-arrow-left me-1"></i> Previous
                                            </button>
                                            <button type="button" class="btn btn-success" id="submitPaymentBtn">
                                                <i class="fas fa-check-circle me-2"></i> Confirm & Create Payment
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Summary -->
                            <div class="col-lg-4">
                                <div class="card position-sticky" style="top: 90px; z-index: 100;">
                                    <div class="card-header bg-dark text-white">
                                        <h5 class="card-title text-white mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Payment Summary</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="p-3 border-bottom">
                                            <small class="text-uppercase text-muted fw-bold">Supplier</small>
                                            <div class="fw-bold fs-5 text-dark text-truncate" id="summarySupplier">---</div>
                                        </div>
                                        
                                        <div class="p-3 bg-light">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-secondary">GRN Allocation:</span>
                                                <span class="fw-bold text-dark" id="summaryAllocate">LKR 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="text-secondary fw-bold">Total Payment:</span>
                                                <span class="fw-bold text-primary fs-5" id="summaryPayment">LKR 0.00</span>
                                            </div>
                                            
                                            <div class="alert alert-warning mb-0 p-2 small">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold">Unallocated:</span>
                                                    <span class="fw-bold" id="summaryUnallocated">LKR 0.00</span>
                                                </div>
                                            </div>
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

    <?php include '../../includes/main-js.php'; ?>
    <script>
    $(document).ready(function() {
        let currentStep = 1;
        let selectedGrns = [];
        let paymentMethods = [];
        let financialAccounts = [];

        // Load Suppliers
        $.get('../../Ajax/php/supplier.php', { action: 'list' }, function(res) {
            if (res.status === 'success') {
                const select = $('#supplierSelect');
                res.data.forEach(s => {
                    select.append(`<option value="${s.id}">${s.supplier_name}</option>`);
                });
            }
        });

        // Load Financial Accounts
        $.get('../../Ajax/php/financial.php', { action: 'get_accounts' }, function(res) {
            if (res.status === 'success') {
                financialAccounts = res.data;
            }
        });

        // Supplier Selection Check
        $('#supplierSelect').on('change', function() {
            const supplierId = $(this).val();
            const supplierName = $(this).find('option:selected').text();
            
            $('#summarySupplier').text(supplierId ? supplierName : '---');
            
            if (!supplierId) {
                $('#supplierBalance').text('LKR 0.00');
                $('#supplierInfoBox').slideUp();
                $('#outstandingGrnsTable tbody').html('<tr><td colspan="4" class="text-center py-5 text-muted">Select a supplier first</td></tr>');
                return;
            }

            // Get Balance
            $.get('../../Ajax/php/supplier-payment.php', { action: 'get_supplier_balance', supplier_id: supplierId }, function(res) {
                if (res.status === 'success') {
                    $('#supplierBalance').text('LKR ' + parseFloat(res.data.balance).toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#supplierInfoBox').slideDown();
                }
            });

            // Load GRNs
            loadOutstandingGRNs(supplierId);
        });

        function loadOutstandingGRNs(supplierId) {
            $.get('../../Ajax/php/supplier-payment.php', { action: 'get_outstanding_grns', supplier_id: supplierId }, function(res) {
                const tbody = $('#outstandingGrnsTable tbody');
                tbody.empty();

                if (res.status === 'success' && res.data.length > 0) {
                    res.data.forEach(grn => {
                        const balance = parseFloat(grn.balance_due);
                        const dueDate = grn.due_date ? new Date(grn.due_date).toLocaleDateString() : 'N/A';
                        const isOverdue = grn.days_outstanding > 0;
                        
                        tbody.append(`
                            <tr class="grn-row" data-grn-id="${grn.id}" data-balance="${balance}">
                                <td class="ps-4 text-center"><input type="checkbox" class="form-check-input grn-checkbox" data-grn-id="${grn.id}"></td>
                                <td>
                                    <div><span class="badge bg-light text-dark border">${grn.grn_number}</span> <span class="text-muted small ms-2">${new Date(grn.grn_date).toLocaleDateString()}</span></div>
                                    <div class="small mt-1">Due: <span class="${isOverdue ? 'text-danger fw-bold' : ''}">${dueDate}</span> ${isOverdue ? '<i class="fas fa-exclamation-circle text-danger ms-1" title="Overdue"></i>' : ''}</div>
                                    <div class="small text-muted">Net: LKR ${parseFloat(grn.net_amount).toLocaleString('en-US')}</div>
                                </td>
                                <td class="text-end fw-bold text-dark">LKR ${balance.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                <td class="text-end pe-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0">LKR</span>
                                        <input type="number" class="form-control text-end fw-bold allocation-input" 
                                           data-grn-id="${grn.id}" value="${balance}" min="0" max="${balance}" step="0.01">
                                    </div>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append('<tr><td colspan="4" class="text-center py-5 text-muted"><i class="fas fa-check-circle text-success me-2"></i>No outstanding GRNs found</td></tr>');
                }
            });
        }

        // Checkbox & Allocation Logic
        $(document).on('change', '#selectAllGrns', function() {
            $('.grn-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });

        $(document).on('change', '.grn-checkbox', function() {
            const row = $(this).closest('tr');
            if ($(this).is(':checked')) {
                row.addClass('selected');
            } else {
                row.removeClass('selected');
            }
            updateCalculations();
        });

        $(document).on('input', '.allocation-input', function() {
            const row = $(this).closest('tr');
            const checkbox = row.find('.grn-checkbox');
            const val = parseFloat($(this).val()) || 0;

            if (val > 0 && !checkbox.is(':checked')) {
                checkbox.prop('checked', true).trigger('change');
            } else if (val === 0 && checkbox.is(':checked')) {
                checkbox.prop('checked', false).trigger('change');
            }
            updateCalculations();
        });

        // Add Method
        $('#addMethodBtn').on('click', function() {
            addMethodRow();
        });

        function addMethodRow(data = {}) {
            const id = 'method_' + Date.now();
            let accountOptions = '<option value="">Select Account...</option>';
            financialAccounts.forEach(acc => {
                accountOptions += `<option value="${acc.id}">${acc.account_name} (${acc.account_type})</option>`;
            });

            const html = `
                <div class="payment-method-item animate-fade-in" id="${id}">
                   <div class="d-flex justify-content-between mb-3">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payment Entry</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-method"><i class="fas fa-trash"></i></button>
                   </div>
                   <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label small">Pay From (Account) <span class="text-danger">*</span></label>
                            <select class="form-select method-account">
                                ${accountOptions}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Type</label>
                            <select class="form-select method-type">
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="online">Online</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control method-amount fw-bold" placeholder="0.00" min="0.01" step="0.01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Reference</label>
                            <input type="text" class="form-control method-reference" placeholder="Ref / Cheque #">
                        </div>
                        <div class="col-md-2 cheque-fields" style="display:none;"> <!-- Adjusted col width -->
                            <label class="form-label small">Cheque Date</label>
                            <input type="date" class="form-control method-cheque-date">
                        </div>
                   </div>
                   <!-- Extra row for bank details if needed -->
                   <div class="row g-3 mt-1 cheque-fields" style="display:none;">
                        <div class="col-md-6">
                             <label class="form-label small">Bank Name</label>
                             <input type="text" class="form-control method-bank" placeholder="Bank Name">
                        </div>
                   </div>
                </div>
            `;
            $('#paymentMethodsContainer').append(html);
        }

        $(document).on('click', '.remove-method', function() {
            $(this).closest('.payment-method-item').remove();
            updateCalculations();
        });

        $(document).on('change', '.method-type', function() {
            const row = $(this).closest('.payment-method-item');
            if ($(this).val() === 'cheque') {
                row.find('.cheque-fields').show();
            } else {
                row.find('.cheque-fields').hide();
            }
        });

        $(document).on('input', '.method-amount', function() {
            updateCalculations();
        });

        function updateCalculations() {
            // Allocations
            let allocTotal = 0;
            selectedGrns = [];
            $('.grn-checkbox:checked').each(function() {
                const grnId = $(this).data('grn-id');
                const row = $(this).closest('tr');
                const amount = parseFloat(row.find('.allocation-input').val()) || 0;
                allocTotal += amount;
                
                const grnNum = row.find('td:eq(1) span.badge').text();
                selectedGrns.push({ grn_id: grnId, amount: amount, grn_number: grnNum });
            });

            // Payments
            let payTotal = 0;
            paymentMethods = [];
            $('.payment-method-item').each(function() {
                const amount = parseFloat($(this).find('.method-amount').val()) || 0;
                payTotal += amount;
                
                paymentMethods.push({
                    account_id: $(this).find('.method-account').val(),
                    payment_method: $(this).find('.method-type').val(),
                    amount: amount,
                    reference_number: $(this).find('.method-reference').val(),
                    cheque_date: $(this).find('.method-cheque-date').val(),
                    bank_name: $(this).find('.method-bank').val()
                });
            });

            const unallocated = payTotal - allocTotal;

            $('#summaryAllocate').text('LKR ' + allocTotal.toLocaleString('en-US', {minimumFractionDigits: 2}));
            $('#summaryPayment').text('LKR ' + payTotal.toLocaleString('en-US', {minimumFractionDigits: 2}));
            $('#summaryUnallocated').text('LKR ' + unallocated.toLocaleString('en-US', {minimumFractionDigits: 2}))
                .removeClass('text-success text-danger')
                .addClass(unallocated >= 0 ? 'text-success' : 'text-danger');
            
            // Update Confirm View
            const allocList = selectedGrns.map(g => `<li class="d-flex justify-content-between mb-1"><span>${g.grn_number}</span> <span class="fw-bold">LKR ${g.amount.toLocaleString('en-US')}</span></li>`).join('');
            $('#confirmAllocationsList').html(allocList || '<li class="text-muted fst-italic">No allocations selected.</li>');

            const methodList = paymentMethods.map(m => `<li class="d-flex justify-content-between mb-1"><span>${m.payment_method.toUpperCase()}</span> <span class="fw-bold">LKR ${m.amount.toLocaleString('en-US')}</span></li>`).join('');
            $('#confirmMethodsList').html(methodList || '<li class="text-muted fst-italic">No payment methods added.</li>');
        }

        // Navigation
        $('.next-step').click(function() {
            if (validateStep(currentStep)) goToStep(currentStep + 1);
        });

        $('.prev-step').click(function() {
            goToStep(currentStep - 1);
        });

        function validateStep(step) {
            if (step === 1 && !$('#supplierSelect').val()) {
                Swal.fire('Error', 'Please select a supplier', 'error');
                return false;
            }
            if (step === 3) {
                 if ($('.payment-method-item').length === 0) {
                     Swal.fire('Error', 'Please add a payment method', 'error');
                     return false;
                 }
                 let valid = false;
                 $('.method-amount').each(function() { if(parseFloat($(this).val()) > 0) valid = true; });
                 if(!valid) {
                     Swal.fire('Error', 'Please enter a valid payment amount', 'error');
                     return false;
                 }
                 
                 // Validate Accounts are selected
                 let accountsValid = true;
                 $('.method-account').each(function() { if(!$(this).val()) accountsValid = false; });
                 if(!accountsValid) {
                     Swal.fire('Error', 'Please select a paying account for all entries', 'error');
                     return false;
                 }
            }
            return true;
        }

        function goToStep(step) {
            // Update Indicators
            $('.step').removeClass('active completed');
            for(let i=1; i<step; i++) $(`.step[data-step="${i}"]`).addClass('completed');
            $(`.step[data-step="${step}"]`).addClass('active');

            // Hide/Show Content
            $('.form-step').hide();
            $(`#step${step}`).fadeIn(300);
            
            currentStep = step;
            
            // Auto add method on step 3
            if(step === 3 && $('.payment-method-item').length === 0) addMethodRow();
        }

        // Submit
        $('#submitPaymentBtn').click(function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');

            $.post('../../Ajax/php/supplier-payment.php', {
                action: 'create',
                supplier_id: $('#supplierSelect').val(),
                payment_date: $('#paymentDate').val(),
                notes: $('#paymentNotes').val(),
                methods: JSON.stringify(paymentMethods),
                allocations: JSON.stringify(selectedGrns)
            }, function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: `Payment ${res.data.payment_number} created successfully`,
                        icon: 'success'
                    }).then(() => window.location.href = '../SupplierPayment/');
                } else {
                    Swal.fire('Error', res.message || 'Failed', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Confirm & Create Payment');
                }
            });
        });
    });
    </script>
</body>
</html>
