<?php
require_once __DIR__ . '/../../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo APP_NAME; ?> - Customer Sales Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <?php include '../../../includes/main-css.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .filter-panel { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .summary-card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); height: 100%; border-left: 4px solid transparent; }
        .summary-card.primary { border-left-color: #4361ee; }
        .summary-card.success { border-left-color: #10b981; }
        .summary-card.warning { border-left-color: #f59e0b; }
        .summary-card.info { border-left-color: #3b82f6; }
        .summary-label { color: #6c757d; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-value { font-size: 1.8rem; font-weight: 700; color: #333; margin-top: 5px; }
        .table-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .badge-pill { padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; }
        
        /* Date Buttons */
        .quick-date-btns { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .quick-date-btn { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; }
        .quick-date-btn:hover { border-color: #6366f1; background: rgba(99, 102, 241, 0.05); }
        .quick-date-btn.active { background: #6366f1; color: white; border-color: #6366f1; }
        
        /* Table enhancements */
        tr.clickable-row { cursor: pointer; transition: background-color 0.15s; }
        tr.clickable-row:hover { background-color: #f8fafc !important; }
        tr.detail-row td { background-color: #f8fafc !important; box-shadow: inset 0 3px 6px rgba(0,0,0,0.02); }
        .detail-table { margin: 0; font-size: 0.9em; }
        .detail-table th { background: transparent; border-bottom: 2px solid #e2e8f0; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.8em; }
        .detail-table td { border-bottom: 1px solid #e2e8f0; background: white; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../../../includes/sidebar.php'; ?>
        <div class="main-panel">
            <?php include '../../../includes/header.php'; ?>
            <div class="container-fluid">
                <div class="page-inner">
                    <!-- Header -->
                    <div class="page-header d-flex justify-content-between align-items-center mb-4" style="margin-top: 70px;">
                        <div>
                            <h1 class="page-title"><i class="fas fa-chart-pie me-2"></i>Customer Sales Report</h1>
                            <p class="text-muted mb-0">Sales breakdown by customer and category</p>
                        </div>
                        <div>
                            <button class="btn btn-dark" id="printReport">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="filter-panel">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Date Range</label>
                                <div class="quick-date-btns">
                                    <button class="quick-date-btn" data-range="today">Today</button>
                                    <button class="quick-date-btn" data-range="yesterday">Yesterday</button>
                                    <button class="quick-date-btn" data-range="this_week">This Week</button>
                                    <button class="quick-date-btn active" data-range="this_month">This Month</button>
                                    <button class="quick-date-btn" data-range="last_month">Last Month</button>
                                    <button class="quick-date-btn" data-range="custom">Custom Range</button>
                                </div>
                                <div class="row" id="customDateRange" style="display: none;">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" id="startDate" placeholder="Start Date">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" id="endDate" placeholder="End Date">
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-primary w-100" id="applyFilters">Apply Filter</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <div class="summary-card primary">
                                <div class="summary-label">Total Revenue</div>
                                <div class="summary-value" id="val_total">LKR 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-card success">
                                <div class="summary-label">Service Revenue</div>
                                <div class="summary-value" id="val_service">LKR 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-card warning">
                                <div class="summary-label">Inventory Revenue</div>
                                <div class="summary-value" id="val_inventory">LKR 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-card info">
                                <div class="summary-label">Labor Revenue</div>
                                <div class="summary-value" id="val_labor">LKR 0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="reportTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-center">Invoices</th>
                                        <th class="text-end">Service (LKR)</th>
                                        <th class="text-end">Inventory (LKR)</th>
                                        <th class="text-end">Labor (LKR)</th>
                                        <th class="text-end">Total (LKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="7" class="text-center py-5">Loading data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <?php include '../../../includes/main-js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // Helper: Format Date YYYY-MM-DD
            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Init Variables
            const today = new Date();
            let startDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 1)); // Start of month
            let endDate = formatDate(new Date(today.getFullYear(), today.getMonth() + 1, 0)); // End of month

            // Init Date Pickers
            const fpStart = flatpickr("#startDate", {
                dateFormat: "Y-m-d",
                defaultDate: startDate,
                onChange: function(selectedDates, dateStr) {
                    startDate = dateStr;
                }
            });
            
            const fpEnd = flatpickr("#endDate", {
                dateFormat: "Y-m-d",
                defaultDate: endDate,
                onChange: function(selectedDates, dateStr) {
                    endDate = dateStr;
                }
            });

            // Quick Date Buttons Logic
            $('.quick-date-btn').click(function() {
                $('.quick-date-btn').removeClass('active');
                $(this).addClass('active');
                
                const range = $(this).data('range');
                
                if(range === 'custom') {
                    $('#customDateRange').slideDown();
                } else {
                    $('#customDateRange').slideUp();
                    const dates = getDateRange(range);
                    startDate = dates.start;
                    endDate = dates.end;
                    
                    // Update pickers
                    fpStart.setDate(startDate);
                    fpEnd.setDate(endDate);
                    
                    loadReport();
                }
            });

            function getDateRange(range) {
                const today = new Date();
                let start, end;
                
                switch(range) {
                    case 'today':
                        start = new Date(today);
                        end = new Date(today);
                        break;
                    case 'yesterday':
                        start = new Date(today);
                        start.setDate(today.getDate() - 1);
                        end = new Date(start);
                        break;
                    case 'this_week':
                        start = new Date(today);
                        const day = today.getDay() || 7; // Get current day number, converting Sun(0) to 7
                        if (day !== 1) start.setHours(-24 * (day - 1)); // Go back to Monday
                        end = new Date(today);
                        break;
                    case 'this_month':
                        start = new Date(today.getFullYear(), today.getMonth(), 1);
                        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        break;
                    case 'last_month':
                        start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        end = new Date(today.getFullYear(), today.getMonth(), 0);
                        break;
                    default:
                        start = new Date(today.getFullYear(), today.getMonth(), 1);
                        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                }
                
                return {
                    start: formatDate(start),
                    end: formatDate(end)
                };
            }

            function loadReport() {
                const tbody = $('#reportTable tbody');
                tbody.html('<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><br>Loading Report...</td></tr>');
                
                $.ajax({
                    url: '../../../Ajax/php/customer-sales-report.php',
                    type: 'GET',
                    data: { action: 'list', start_date: startDate, end_date: endDate },
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            renderTable(response.data);
                            updateSummary(response.summary);
                        } else {
                            tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Error: ' + response.message + '</td></tr>');
                        }
                    },
                    error: function() {
                        tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load data. Please try again.</td></tr>');
                    }
                });
            }

            function renderTable(data) {
                const tbody = $('#reportTable tbody');
                if(data.length === 0) {
                    tbody.html('<tr><td colspan="6" class="text-center py-4 text-muted">No sales records found for this period.</td></tr>');
                    return;
                }

                let html = '';
                data.forEach(row => {
                    html += `
                        <tr class="clickable-row" onclick="toggleDetails(this, ${row.customer_id})">
                            <td>
                                <div class="fw-bold text-dark">${row.customer_name}</div>
                                <small class="text-muted">${row.customer_phone || ''}</small>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark text-center" style="min-width: 30px;">${row.invoice_count}</span></td>
                            <td class="text-end text-muted">${formatMoney(row.service_revenue)}</td>
                            <td class="text-end text-muted">${formatMoney(row.inventory_revenue)}</td>
                            <td class="text-end text-muted">${formatMoney(row.labor_revenue)}</td>
                            <td class="text-end fw-bold text-dark">${formatMoney(row.total_revenue)}</td>
                        </tr>
                    `;
                });
                tbody.html(html);
            }

            function updateSummary(summary) {
                $('#val_total').text(formatMoney(summary.total_revenue));
                $('#val_service').text(formatMoney(summary.service_revenue));
                $('#val_inventory').text(formatMoney(summary.inventory_revenue));
                $('#val_labor').text(formatMoney(summary.labor_revenue));
            }

            function formatMoney(amount) {
                return 'LKR ' + parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            // Events
            $('#applyFilters').click(loadReport);
            
            $('#printReport').click(function() {
                 const params = new URLSearchParams({
                    start_date: startDate,
                    end_date: endDate
                });
                window.open('print.php?' + params.toString(), '_blank');
            });

            // Initial Load
            loadReport();
        });

        // Toggle Details Function
        window.toggleDetails = function(row, customerId) {
            const $row = $(row);
            const $nextRow = $row.next('tr');
            
            // If already open, toggle it
            if($nextRow.hasClass('detail-row')) {
                $nextRow.toggle();
                return;
            }
            
            // Create new detail row
            const newRow = $(`<tr class="detail-row"><td colspan="6" class="p-4 text-center"><div class="spinner-border spinner-border-sm text-primary"></div> Loading details...</td></tr>`);
            $row.after(newRow);
            
            // Get dates from flatpickr instances
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            $.ajax({
                url: '../../../Ajax/php/customer-sales-report.php',
                type: 'GET',
                data: { action: 'get_customer_invoices', customer_id: customerId, start_date: startDate, end_date: endDate },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        let detailHtml = `
                            <td colspan="6" class="p-4">
                                <div class="card border-0 shadow-sm mb-0">
                                    <div class="card-body p-3">
                                        <h6 class="mb-3 text-muted fw-bold text-uppercase fs-12"><i class="fas fa-list me-2"></i>Invoice Breakdown</h6>
                                        <table class="table table-sm detail-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 15%">Date</th>
                                                    <th style="width: 20%">Invoice #</th>
                                                    <th style="width: 15%" class="text-end">Total</th>
                                                    <th style="width: 15%" class="text-end">Paid</th>
                                                    <th style="width: 15%" class="text-center">Balance</th>
                                                    <th style="width: 20%" class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;
                                    
                        if(response.data.length > 0) {
                            response.data.forEach(inv => {
                                // Format Date
                                const d = new Date(inv.created_at);
                                const dateStr = d.toLocaleDateString();
                                
                                // Calc Unpaid
                                const total = parseFloat(inv.total_amount);
                                const paid = parseFloat(inv.paid_amount);
                                const balance = total - paid;
                                
                                // Status
                                let statusBadge;
                                if(balance <= 0.01) statusBadge = '<span class="badge badge-success">Paid</span>';
                                else if(paid > 0) statusBadge = '<span class="badge badge-warning">Partial</span>';
                                else statusBadge = '<span class="badge badge-danger">Unpaid</span>';
                                
                                if(inv.status === 'cancelled') statusBadge = '<span class="badge badge-secondary">Cancelled</span>';

                                detailHtml += `
                                    <tr>
                                        <td>${dateStr}</td>
                                        <td><a href="../../Invoice/print.php?id=${inv.id}" target="_blank" class="fw-bold text-primary text-decoration-none">${inv.invoice_number}</a></td>
                                        <td class="text-end fw-bold">${parseFloat(inv.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                        <td class="text-end text-success">${parseFloat(inv.paid_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                        <td class="text-end text-danger">${Math.max(0, balance).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                        <td class="text-center">${statusBadge}</td>
                                    </tr>`;
                            });
                        } else {
                            detailHtml += `<tr><td colspan="6" class="text-center text-muted">No invoices found.</td></tr>`;
                        }
                        
                        detailHtml += `</tbody></table></div></div></td>`;
                        newRow.html(detailHtml);
                    } else {
                        newRow.html(`<td colspan="6" class="text-center text-danger py-3">Error: ${response.message}</td>`);
                    }
                },
                error: function() {
                    newRow.html(`<td colspan="6" class="text-center text-danger py-3">Failed to load invoice details.</td>`);
                }
            });
        };
    </script>
</body>
</html>
