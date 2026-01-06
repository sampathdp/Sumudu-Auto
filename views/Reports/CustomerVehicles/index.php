<?php
require_once __DIR__ . '/../../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo APP_NAME; ?> - Customer Vehicles Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <?php include '../../../includes/main-css.php'; ?>
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
        
        .vehicle-card { background: #fff; border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; border: 1px solid #e2e8f0; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .vehicle-card:hover { border-color: #6366f1; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15); }
        .vehicle-card .vehicle-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .vehicle-card .vehicle-reg { font-size: 1.25rem; font-weight: 700; color: #1e293b; }
        .vehicle-card .vehicle-make { color: #64748b; font-size: 0.875rem; }
        .vehicle-card .vehicle-info { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; font-size: 0.875rem; }
        .vehicle-card .info-item { display: flex; justify-content: space-between; padding: 0.25rem 0; }
        .vehicle-card .info-label { color: #94a3b8; }
        .vehicle-card .info-value { color: #334155; font-weight: 500; }
        
        .customer-info-box { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 12px; padding: 1.5rem; color: white; margin-bottom: 1.5rem; }
        .customer-info-box h4 { margin: 0 0 0.5rem 0; font-weight: 700; }
        .customer-info-box p { margin: 0; opacity: 0.9; }
        
        .detail-section { background: #f8fafc; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .detail-section h6 { color: #475569; font-weight: 600; margin-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        
        .service-history-item { background: #fff; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 0.5rem; border-left: 3px solid #10b981; }
        .service-history-item.pending { border-left-color: #f59e0b; }
        .service-history-item.cancelled { border-left-color: #ef4444; opacity: 0.7; }
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
                            <h1 class="page-title"><i class="fas fa-car-alt me-2"></i>Customer Vehicles Report</h1>
                            <p class="text-muted mb-0">View all vehicles by customer with full details</p>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="filter-panel">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-user me-1"></i>Select Customer</label>
                                <select class="form-select form-select-lg" id="customerSelect">
                                    <option value="">-- All Customers --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><i class="fas fa-search me-1"></i>Search Vehicle</label>
                                <input type="text" class="form-control form-control-lg" id="vehicleSearch" placeholder="Registration, Make, Model...">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary btn-lg w-100" id="applyFilter">
                                    <i class="fas fa-filter me-2"></i>Apply Filter
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="summary-card primary">
                                <div class="summary-label">Total Customers</div>
                                <div class="summary-value" id="totalCustomers">0</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-card success">
                                <div class="summary-label">Total Vehicles</div>
                                <div class="summary-value" id="totalVehicles">0</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-card warning">
                                <div class="summary-label">Active Services</div>
                                <div class="summary-value" id="activeServices">0</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-card info">
                                <div class="summary-label">Filtered Results</div>
                                <div class="summary-value" id="filteredCount">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info (shown when customer selected) -->
                    <div id="customerInfoBox" class="customer-info-box" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4><i class="fas fa-user-circle me-2"></i><span id="selectedCustomerName"></span></h4>
                                <p><i class="fas fa-phone me-1"></i><span id="selectedCustomerPhone"></span> | <i class="fas fa-envelope me-1"></i><span id="selectedCustomerEmail"></span></p>
                            </div>
                            <div>
                                <span class="badge bg-light text-dark fs-6"><span id="customerVehicleCount">0</span> Vehicles</span>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicles Table -->
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover" id="vehiclesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Registration</th>
                                        <th>Make / Model</th>
                                        <th>Year</th>
                                        <th>Mileage</th>
                                        <th>Owner</th>
                                        <th>Last Service</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Details Modal -->
    <div class="modal fade" id="vehicleDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-car me-2"></i>Vehicle Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Vehicle Header -->
                    <div class="text-center mb-4">
                        <h3 id="modal_reg" class="mb-1"></h3>
                        <p class="text-muted mb-0" id="modal_make_model"></p>
                    </div>

                    <div class="row">
                        <!-- Vehicle Info -->
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6><i class="fas fa-info-circle me-2"></i>Vehicle Information</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Year:</span>
                                    <span class="fw-bold" id="modal_year"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Color:</span>
                                    <span class="fw-bold" id="modal_color"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Current Mileage:</span>
                                    <span class="fw-bold" id="modal_mileage"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Registration Date:</span>
                                    <span class="fw-bold" id="modal_created"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Owner Info -->
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6><i class="fas fa-user me-2"></i>Owner Information</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Name:</span>
                                    <span class="fw-bold" id="modal_owner_name"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Phone:</span>
                                    <span class="fw-bold" id="modal_owner_phone"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Last Service:</span>
                                    <span class="fw-bold" id="modal_last_service"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Last Oil Change:</span>
                                    <span class="fw-bold" id="modal_last_oil"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Service History -->
                    <div class="detail-section">
                        <h6><i class="fas fa-history me-2"></i>Service History</h6>
                        <div id="serviceHistoryContainer">
                            <p class="text-muted text-center">Loading service history...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../../includes/main-js.php'; ?>
    <script>
    $(document).ready(function() {
        let vehiclesTable;
        
        // Initialize DataTable
        vehiclesTable = $('#vehiclesTable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[0, 'asc']],
            responsive: true,
            language: {
                emptyTable: "No vehicles found. Select a customer or apply filters.",
                zeroRecords: "No matching vehicles found"
            },
            columns: [
                { data: 'registration_number', render: function(data) {
                    return `<code class="fw-bold fs-6">${data}</code>`;
                }},
                { data: null, render: function(data, type, row) {
                    return `<strong>${row.make}</strong> ${row.model}`;
                }},
                { data: 'year', render: function(data) {
                    return data || '<span class="text-muted">N/A</span>';
                }},
                { data: 'current_mileage', render: function(data) {
                    return data ? data.toLocaleString() + ' km' : '<span class="text-muted">N/A</span>';
                }},
                { data: 'customer_name', render: function(data) {
                    return data || '<span class="text-muted">Unknown</span>';
                }},
                { data: 'last_service_date', render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : '<span class="badge bg-warning">Never</span>';
                }},
                { data: null, orderable: false, className: 'text-center', render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-primary view-vehicle-details" data-id="${row.id}">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>`;
                }}
            ]
        });

        // Load customers
        loadCustomers();
        loadSummary();

        function loadCustomers() {
            $.get('../../../Ajax/php/customer.php', { action: 'list' }, function(res) {
                if (res.status === 'success') {
                    const select = $('#customerSelect');
                    select.empty().append('<option value="">-- All Customers --</option>');
                    res.data.forEach(c => {
                        select.append(`<option value="${c.id}" data-customer='${JSON.stringify(c)}'>${c.name} - ${c.phone}</option>`);
                    });
                    $('#totalCustomers').text(res.data.length);
                }
            });
        }

        function loadSummary() {
            $.get('../../../Ajax/php/vehicle.php', { action: 'list' }, function(res) {
                if (res.status === 'success') {
                    $('#totalVehicles').text(res.data.length);
                    // Load all vehicles into table initially
                    vehiclesTable.clear().rows.add(res.data).draw();
                    $('#filteredCount').text(res.data.length);
                }
            });
        }

        // Apply filter
        $('#applyFilter').on('click', function() {
            loadVehicles();
        });

        $('#customerSelect').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            if ($(this).val()) {
                const customer = JSON.parse(selectedOption.attr('data-customer'));
                $('#selectedCustomerName').text(customer.name);
                $('#selectedCustomerPhone').text(customer.phone || 'N/A');
                $('#selectedCustomerEmail').text(customer.email || 'N/A');
                $('#customerInfoBox').slideDown();
            } else {
                $('#customerInfoBox').slideUp();
            }
            loadVehicles();
        });

        function loadVehicles() {
            const customerId = $('#customerSelect').val();
            
            let url = '../../../Ajax/php/vehicle.php?action=list';
            if (customerId) {
                url = '../../../Ajax/php/vehicle.php?action=list&customer_id=' + customerId;
            }
            
            $.get(url, function(res) {
                if (res.status === 'success') {
                    let vehicles = res.data;
                    
                    // Apply search filter from manual search box
                    const searchTerm = $('#vehicleSearch').val().toLowerCase();
                    if (searchTerm) {
                        vehicles = vehicles.filter(v => 
                            (v.registration_number && v.registration_number.toLowerCase().includes(searchTerm)) ||
                            (v.make && v.make.toLowerCase().includes(searchTerm)) ||
                            (v.model && v.model.toLowerCase().includes(searchTerm))
                        );
                    }
                    
                    vehiclesTable.clear().rows.add(vehicles).draw();
                    $('#filteredCount').text(vehicles.length);
                    $('#customerVehicleCount').text(vehicles.length);
                }
            });
        }

        // View vehicle details
        $(document).on('click', '.view-vehicle-details', function() {
            const vehicleId = $(this).data('id');
            
            // Fetch vehicle details
            $.get('../../../Ajax/php/vehicle.php', { action: 'get', id: vehicleId }, function(res) {
                if (res.status === 'success') {
                    const v = res.data;
                    $('#modal_reg').text(v.registration_number);
                    $('#modal_make_model').text(`${v.make} ${v.model}`);
                    $('#modal_year').text(v.year || 'N/A');
                    $('#modal_color').text(v.color || 'N/A');
                    $('#modal_mileage').text(v.current_mileage ? v.current_mileage.toLocaleString() + ' km' : 'N/A');
                    $('#modal_created').text(v.created_at ? new Date(v.created_at).toLocaleDateString() : 'N/A');
                    $('#modal_owner_name').text(v.customer_name || 'N/A');
                    $('#modal_owner_phone').text(v.customer_phone || 'N/A');
                    $('#modal_last_service').text(v.last_service_date ? new Date(v.last_service_date).toLocaleDateString() : 'Never');
                    $('#modal_last_oil').text(v.last_oil_change_date ? new Date(v.last_oil_change_date).toLocaleDateString() : 'Never');
                    
                    // Load service history
                    loadServiceHistory(vehicleId);
                    
                    new bootstrap.Modal(document.getElementById('vehicleDetailsModal')).show();
                }
            });
        });

        function loadServiceHistory(vehicleId) {
            $.get('../../../Ajax/php/service.php', { action: 'get_by_vehicle', vehicle_id: vehicleId }, function(res) {
                const container = $('#serviceHistoryContainer');
                
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(s => {
                        const statusClass = s.status === 'completed' ? '' : (s.status === 'cancelled' ? 'cancelled' : 'pending');
                        html += `
                            <div class="service-history-item ${statusClass}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${s.job_number}</strong>
                                        <span class="badge bg-${getStatusColor(s.status)} ms-2">${s.status.toUpperCase()}</span>
                                    </div>
                                    <span class="text-muted">${new Date(s.created_at).toLocaleDateString()}</span>
                                </div>
                                <div class="mt-1 text-muted small">
                                    ${s.package_name || 'General Service'} - LKR ${parseFloat(s.total_amount || 0).toFixed(2)}
                                </div>
                            </div>
                        `;
                    });
                    container.html(html);
                } else {
                    container.html('<p class="text-muted text-center mb-0">No service history found</p>');
                }
            }).fail(function() {
                $('#serviceHistoryContainer').html('<p class="text-muted text-center mb-0">Could not load service history</p>');
            });
        }

        function getStatusColor(status) {
            const colors = {
                'pending': 'warning',
                'in_progress': 'info',
                'completed': 'success',
                'cancelled': 'danger'
            };
            return colors[status] || 'secondary';
        }

        // Search on enter
        $('#vehicleSearch').on('keypress', function(e) {
            if (e.which === 13) {
                loadVehicles();
            }
        });
    });
    </script>
</body>
</html>
