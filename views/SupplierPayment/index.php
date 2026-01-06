<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo APP_NAME; ?> - Supplier Payments</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <?php include '../../includes/main-css.php'; ?>
    <style>
        .filter-card { background: #fff; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .stat-card { background: #fff; padding: 1.25rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid; margin-bottom: 1rem; }
        .stat-card.primary { border-left-color: #6366f1; }
        .stat-card.success { border-left-color: #10b981; }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.danger { border-left-color: #ef4444; }
        .stat-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-top: 0.25rem; }
        
        .grn-row { cursor: pointer; }
        .grn-row:hover { background: #f1f5f9 !important; }
        .grn-row.selected { background: #e0e7ff !important; }
        
        .payment-method-row { background: #f8fafc; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; }
        .method-header { display: flex; justify-content: space-between; align-items: center; }
        
        .allocation-input { max-width: 150px; text-align: right; font-weight: 600; }
        
        .badge-status { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-draft { background: #fef3c7; color: #92400e; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        
        .step-indicator { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
        .step { flex: 1; text-align: center; padding: 0.75rem; background: #f1f5f9; border-radius: 8px; color: #64748b; font-weight: 500; }
        .step.active { background: #6366f1; color: white; }
        .step.completed { background: #10b981; color: white; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>
            <div class="container-fluid">
                <div class="page-inner">
                    <!-- Header -->
                    <div class="page-header d-flex justify-content-between align-items-center mb-4" style="margin-top: 70px;">
                        <div>
                            <h1 class="page-title"><i class="fas fa-money-check-alt me-2"></i>Supplier Payments</h1>
                            <p class="text-muted mb-0">Manage payments to suppliers and settle GRNs</p>
                        </div>
                        <a href="create.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>New Payment
                        </a>
                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <div class="stat-label">Total Payments (This Month)</div>
                                <div class="stat-value" id="statTotalPayments">LKR 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <div class="stat-label">Confirmed Payments</div>
                                <div class="stat-value" id="statConfirmed">0</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <div class="stat-label">Draft Payments</div>
                                <div class="stat-value" id="statDraft">0</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card danger">
                                <div class="stat-label">Pending Cheques</div>
                                <div class="stat-value" id="statPendingCheques">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="paymentsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Payment #</th>
                                            <th>Date</th>
                                            <th>Supplier</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Allocated</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Actions</th>
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

    <!-- View Payment Modal -->
    <div class="modal fade" id="viewPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewPaymentContent">
                    <!-- Loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script>
    $(document).ready(function() {
        // Load statistics
        loadStats();

        function loadStats() {
            $.get('../../Ajax/php/supplier-payment.php', { action: 'get_stats' }, function(res) {
                if (res.status === 'success') {
                    const s = res.data;
                    $('#statTotalPayments').text('LKR ' + parseFloat(s.total_payments_month).toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#statConfirmed').text(s.confirmed_count);
                    $('#statDraft').text(s.draft_count);
                    $('#statPendingCheques').text(s.pending_cheques);
                }
            });
        }
        // Initialize DataTable
        const paymentsTable = $('#paymentsTable').DataTable({
            order: [[1, 'desc']],
            pageLength: 25,
            ajax: {
                url: '../../Ajax/php/supplier-payment.php?action=list',
                dataSrc: 'data'
            },
            columns: [
                { data: 'payment_number', render: d => `<code class="fw-bold">${d}</code>` },
                { data: 'payment_date', render: d => new Date(d).toLocaleDateString() },
                { data: 'supplier_name' },
                { data: 'total_amount', className: 'text-end', render: d => `LKR ${parseFloat(d).toLocaleString('en-US', {minimumFractionDigits: 2})}` },
                { data: 'allocated_amount', className: 'text-end', render: d => `LKR ${parseFloat(d).toLocaleString('en-US', {minimumFractionDigits: 2})}` },
                { data: 'status', className: 'text-center', render: function(d) {
                    const badges = {
                        'draft': 'badge-draft',
                        'confirmed': 'badge-confirmed',
                        'cancelled': 'badge-cancelled'
                    };
                    return `<span class="badge-status ${badges[d] || ''}">${d.toUpperCase()}</span>`;
                }},
                { data: null, className: 'text-center', orderable: false, render: function(data, type, row) {
                    let actions = `<button class="btn btn-sm btn-info view-payment" data-id="${row.id}"><i class="fas fa-eye"></i></button>`;
                    if (row.status === 'draft') {
                        actions += ` <button class="btn btn-sm btn-success confirm-payment" data-id="${row.id}"><i class="fas fa-check"></i></button>`;
                        actions += ` <button class="btn btn-sm btn-danger cancel-payment" data-id="${row.id}"><i class="fas fa-times"></i></button>`;
                    }
                    return actions;
                }}
            ]
        });

        // View payment
        $(document).on('click', '.view-payment', function() {
            const id = $(this).data('id');
            $.get('../../Ajax/php/supplier-payment.php', { action: 'get', id: id }, function(res) {
                if (res.status === 'success') {
                    const p = res.data;
                    let methodsHtml = p.methods.map(m => `<li>${m.payment_method.toUpperCase()}: LKR ${parseFloat(m.amount).toLocaleString('en-US', {minimumFractionDigits: 2})} ${m.reference_number ? '(' + m.reference_number + ')' : ''}</li>`).join('');
                    let allocsHtml = p.allocations.map(a => `<li>${a.grn_number}: LKR ${parseFloat(a.allocated_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</li>`).join('');
                    
                    $('#viewPaymentContent').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Payment #:</strong> ${p.payment_number}</p>
                                <p><strong>Date:</strong> ${p.payment_date}</p>
                                <p><strong>Supplier:</strong> ${p.supplier_name}</p>
                                <p><strong>Status:</strong> <span class="badge-status badge-${p.status}">${p.status.toUpperCase()}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Amount:</strong> LKR ${parseFloat(p.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                                <p><strong>Allocated:</strong> LKR ${parseFloat(p.allocated_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                                <p><strong>Unallocated:</strong> LKR ${parseFloat(p.unallocated_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                            </div>
                        </div>
                        <hr>
                        <h6>Payment Methods</h6>
                        <ul>${methodsHtml || '<li class="text-muted">None</li>'}</ul>
                        <h6>GRN Allocations</h6>
                        <ul>${allocsHtml || '<li class="text-muted">None (Advance Payment)</li>'}</ul>
                        ${p.notes ? `<h6>Notes</h6><p>${p.notes}</p>` : ''}
                    `);
                    new bootstrap.Modal(document.getElementById('viewPaymentModal')).show();
                }
            });
        });

        // Confirm payment
        $(document).on('click', '.confirm-payment', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Confirm Payment?',
                text: 'This will finalize the payment.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Yes, confirm it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../../Ajax/php/supplier-payment.php', { action: 'confirm', id: id }, function(res) {
                        if (res.status === 'success') {
                            showAlert('success', 'Payment confirmed!');
                            paymentsTable.ajax.reload();
                            loadStats();
                        } else {
                            showAlert('error', res.message);
                        }
                    });
                }
            });
        });

        // Cancel payment
        $(document).on('click', '.cancel-payment', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Cancel Payment?',
                text: 'This will reverse all allocations.',
                icon: 'warning',
                input: 'text',
                inputLabel: 'Reason for cancellation',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../../Ajax/php/supplier-payment.php', { action: 'cancel', id: id, reason: result.value }, function(res) {
                        if (res.status === 'success') {
                            showAlert('success', 'Payment cancelled!');
                            paymentsTable.ajax.reload();
                            loadStats();
                        } else {
                            showAlert('error', res.message);
                        }
                    });
                }
            });
        });

        function showAlert(type, message) {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Success' : 'Error',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
    </script>
</body>
</html>
