<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - GRN Management</title>
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
            border-left: 4px solid var(--warning-color);
        }

        .page-header-content {
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

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-card .icon.warning { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
        .stat-card .icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-card .icon.info { background: rgba(8, 145, 178, 0.1); color: var(--info-color); }
        .stat-card .icon.primary { background: rgba(37, 99, 235, 0.1); color: var(--primary-color); }

        .stat-card .details h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-dark);
        }

        .stat-card .details p {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* Card Styles */
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

        .data-card-header h5 {
            margin: 0;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .data-card-body {
            padding: 0;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
            font-size: 0.875rem;
        }

        .table thead th {
            background-color: var(--bg-light);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 0.875rem 1rem;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 0.875rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
        }

        .table tbody tr:hover {
            background-color: var(--bg-light);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
        }

        .badge-draft {
            background-color: rgba(100, 116, 139, 0.1);
            color: var(--secondary-color);
        }

        .badge-received {
            background-color: rgba(5, 150, 105, 0.1);
            color: var(--success-color);
        }

        .badge-verified {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .badge-cancelled {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }

        /* Action Buttons */
        .btn {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .btn-sm {
            padding: 0.375rem 0.625rem;
            font-size: 0.8125rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-muted);
            transition: all 0.15s ease;
        }

        .btn-action:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .btn-action.danger:hover {
            border-color: var(--danger-color);
            color: var(--danger-color);
            background: rgba(220, 38, 38, 0.05);
        }

        .btn-action.success:hover {
            border-color: var(--success-color);
            color: var(--success-color);
            background: rgba(5, 150, 105, 0.05);
        }

        /* DataTables Overrides */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.375rem 2rem 0.375rem 0.75rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            margin: 0 2px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: #fff !important;
        }

        /* GRN Number Styling */
        .grn-number {
            font-weight: 600;
            color: var(--warning-color);
        }

        /* Amount Styling */
        .amount {
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border-color);
            margin-bottom: 1rem;
        }

        .empty-state h5 {
            color: var(--text-muted);
            font-weight: 500;
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
                        <div class="page-header-content">
                            <div class="title-section">
                                <h1><i class="fas fa-file-import me-2"></i>Goods Receipt Notes (GRN)</h1>
                                <p>Manage incoming stock from suppliers</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon warning">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="details">
                                <h3 id="totalGrns">0</h3>
                                <p>Total GRNs</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon info">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="details">
                                <h3 id="draftGrns">0</h3>
                                <p>Draft</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="details">
                                <h3 id="receivedGrns">0</h3>
                                <p>Received</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon primary">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                            <div class="details">
                                <h3 id="totalAmount">Rs. 0</h3>
                                <p>Total Value</p>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table Card -->
                    <div class="data-card">
                        <div class="data-card-header">
                            <h5><i class="fas fa-list me-2"></i>GRN List</h5>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create New GRN
                            </a>
                        </div>
                        <div class="data-card-body">
                            <div class="table-responsive">
                                <table id="grnsTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>GRN Number</th>
                                            <th>Supplier</th>
                                            <th>Date</th>
                                            <th>Invoice #</th>
                                            <th>Net Amount</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View GRN Modal -->
    <div class="modal fade" id="viewGrnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--bg-light); border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>GRN Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="grnModalContent">
                    <!-- Content loaded via AJAX -->
                </div>
                <div class="modal-footer" style="background-color: var(--bg-light); border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script>
    $(document).ready(function() {
        // Load Statistics
        function loadGrnStats() {
            $.get('../../Ajax/php/grn.php', { action: 'statistics' }, function(res) {
                if (res.status === 'success') {
                    const stats = res.data;
                    $('#totalGrns').animateNumber({ number: stats.total_grns });
                    $('#draftGrns').animateNumber({ number: stats.draft_count });
                    $('#receivedGrns').animateNumber({ number: stats.received_count });
                    $('#totalAmount').text('Rs. ' + parseFloat(stats.total_value || 0).toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0}));
                }
            });
        }
        
        loadGrnStats();

        // Initialize DataTable
        const table = $('#grnsTable').DataTable({
            ajax: {
                url: '../../Ajax/php/grn.php?action=list',
                dataSrc: function(json) {
                    return json.data || [];
                }
            },
            columns: [
                { data: 'id', className: 'text-muted' },
                { 
                    data: 'grn_number',
                    render: function(data) {
                        return '<span class="grn-number">' + data + '</span>';
                    }
                },
                { data: 'supplier_name' },
                { 
                    data: 'grn_date',
                    render: function(data) {
                        return new Date(data).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    }
                },
                { 
                    data: 'invoice_number',
                    render: function(data) {
                        return data || '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'net_amount',
                    render: function(data) {
                        return '<span class="amount">Rs. ' + parseFloat(data).toLocaleString('en-IN', {minimumFractionDigits: 2}) + '</span>';
                    }
                },
                { 
                    data: 'status',
                    render: function(data) {
                        const badges = {
                            'draft': '<span class="badge badge-draft"><i class="fas fa-clock me-1"></i>Draft</span>',
                            'received': '<span class="badge badge-received"><i class="fas fa-truck me-1"></i>Received</span>',
                            'verified': '<span class="badge badge-verified"><i class="fas fa-check-double me-1"></i>Verified</span>',
                            'cancelled': '<span class="badge badge-cancelled"><i class="fas fa-times me-1"></i>Cancelled</span>'
                        };
                        return badges[data] || data;
                    }
                },
                { 
                    data: null,
                    className: 'text-end',
                    orderable: false,
                    render: function(data, type, row) {
                        let actions = `
                            <button class="btn-action view-grn me-1" data-id="${row.id}" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>`;
                        
                        if (row.status === 'draft') {
                            actions += `
                                <a href="create.php?id=${row.id}" class="btn-action primary me-1" title="Edit GRN">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn-action success mark-received me-1" data-id="${row.id}" title="Mark as Received">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-action danger cancel-grn me-1" data-id="${row.id}" title="Cancel GRN">
                                    <i class="fas fa-ban"></i>
                                </button>`;
                        }

                        if (row.status === 'received') {
                            actions += `
                                <button class="btn-action primary verify-grn me-1" data-id="${row.id}" title="Verify & Add Stock">
                                    <i class="fas fa-clipboard-check"></i>
                                </button>`;
                        }
                        
                        
                        return actions;
                    }
                }
            ],
            order: [[0, 'desc']],
            pageLength: 10,
            language: {
                emptyTable: '<div class="empty-state"><i class="fas fa-inbox"></i><h5>No GRNs found</h5><p class="text-muted">Create your first GRN to get started</p></div>'
            }
        });

        // View GRN
        $(document).on('click', '.view-grn', function() {
            const id = $(this).data('id');
            $.get('../../Ajax/php/grn.php', { action: 'get', id: id }, function(res) {
                if (res.status === 'success') {
                    const grn = res.data;
                    const items = grn.items || []; // Fix: use grn.items
                    
                    // Status Badge Logic
                    const badges = {
                        'draft': '<span class="badge badge-draft">Draft</span>',
                        'received': '<span class="badge badge-received">Received</span>',
                        'verified': '<span class="badge badge-verified">Verified</span>',
                        'cancelled': '<span class="badge badge-cancelled">Cancelled</span>'
                    };

                    let itemsHtml = `
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 35%">Item</th>
                                        <th style="width: 15%">Batch/Exp</th>
                                        <th style="width: 10%" class="text-end">Qty</th>
                                        <th style="width: 15%" class="text-end">Unit Price</th>
                                        <th style="width: 20%" class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    if (items.length > 0) {
                        items.forEach((item, index) => {
                            let meta = '';
                            if(item.batch_number) meta += `<div class="small text-muted">Batch: ${item.batch_number}</div>`;
                            if(item.expiry_date) meta += `<div class="small text-muted">Exp: ${item.expiry_date}</div>`;

                            itemsHtml += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>
                                        <div class="fw-bold">${item.item_name}</div>
                                        <div class="small text-muted">${item.item_code}</div>
                                    </td>
                                    <td>${meta || '-'}</td>
                                    <td class="text-end">${parseFloat(item.quantity).toLocaleString()} ${item.unit_of_measure}</td>
                                    <td class="text-end">Rs. ${parseFloat(item.unit_price).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                    <td class="text-end fw-bold">Rs. ${parseFloat(item.total_price).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                </tr>
                            `;
                        });
                    } else {
                        itemsHtml += '<tr><td colspan="6" class="text-center py-3">No items found</td></tr>';
                    }
                    itemsHtml += '</tbody></table></div>';

                    // Financial Summary
                    const summaryHtml = `
                        <div class="row justify-content-end mt-3">
                            <div class="col-md-5">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-end">Subtotal:</td>
                                        <td class="text-end fw-bold" style="width: 120px;">Rs. ${parseFloat(grn.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-end text-muted">Discount:</td>
                                        <td class="text-end text-danger" style="width: 120px;">- Rs. ${parseFloat(grn.discount_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-end text-muted">Tax:</td>
                                        <td class="text-end" style="width: 120px;">+ Rs. ${parseFloat(grn.tax_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                    </tr>
                                    <tr style="border-top: 2px solid #e2e8f0;">
                                        <td class="text-end pt-2"><strong>Net Amount:</strong></td>
                                        <td class="text-end pt-2"><strong class="text-primary" style="font-size: 1.1rem;">Rs. ${parseFloat(grn.net_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    `;

                    // Modal Content Construction
                    $('#grnModalContent').html(`
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                            <div>
                                <h4 class="mb-1 text-primary">GRN #${grn.grn_number}</h4>
                                <div class="text-muted small"><i class="far fa-calendar-alt me-1"></i> ${new Date(grn.grn_date).toLocaleDateString('en-GB', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})}</div>
                                ${grn.due_date ? `<div class="text-muted small mt-1"><i class="far fa-clock me-1"></i> Due: ${new Date(grn.due_date).toLocaleDateString('en-GB', {year: 'numeric', month: 'short', day: 'numeric'})}</div>` : ''}
                            </div>
                            <div class="text-end">
                                ${badges[grn.status] || grn.status}
                                ${grn.invoice_number ? `<div class="mt-2 text-muted small">Inv Ref: <strong>${grn.invoice_number}</strong></div>` : ''}
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <h6 class="text-uppercase text-muted small mb-2 fw-bold">Supplier Details</h6>
                                    <div class="fw-bold fs-5 mb-1">${grn.supplier_name}</div>
                                    <!-- Add address if available later -->
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <h6 class="text-uppercase text-muted small mb-2 fw-bold">Internal Reference</h6>
                                    <div><strong>Received By:</strong> ${grn.received_by_name || 'Pending'}</div>
                                    <!-- <div><strong>Verified By:</strong> ${grn.verified_by_employee_id || '-'}</div> -->
                                    ${grn.notes ? `<div class="mt-2 text-muted fst-italic border-top pt-2"><small>Note: ${grn.notes}</small></div>` : ''}
                                </div>
                            </div>
                        </div>

                        ${itemsHtml}
                        ${summaryHtml}
                    `);
                    $('#viewGrnModal').modal('show');
                }
            });
        });

        // Mark as Received
        $(document).on('click', '.mark-received', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Mark as Received?',
                text: 'This will change the status to Received',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                confirmButtonText: 'Yes, mark as received'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../../Ajax/php/grn.php', { action: 'mark_received', id: id }, function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Success', res.message, 'success');
                            table.ajax.reload();
                            loadGrnStats();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
                }
            });
        });

        // Verify GRN
        $(document).on('click', '.verify-grn', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Verify GRN & Update Stock?',
                text: 'This will update inventory quantities and record stock movements. This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Yes, verify and update stock'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../../Ajax/php/grn.php', { action: 'verify', id: id }, function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Verified!', res.message, 'success');
                            table.ajax.reload();
                            loadGrnStats();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
                }
            });
        });

        // Cancel GRN
        $(document).on('click', '.cancel-grn', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Cancel GRN?',
                text: 'Are you sure you want to cancel this GRN? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../../Ajax/php/grn.php', { action: 'cancel', id: id }, function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Cancelled!', res.message, 'success');
                            table.ajax.reload();
                            loadGrnStats();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
                }
            });
        });
        // Custom simple animation
        $.fn.animateNumber = function(options) {
            const $this = $(this);
            const end = options.number;
            $({ val: 0 }).animate({ val: end }, {
                duration: 1000,
                step: function() { $this.text(Math.floor(this.val)); },
                complete: function() { $this.text(this.val); }
            });
        };
    });
    </script>
</body>
</html>
