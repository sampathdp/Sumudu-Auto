<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Employee Earnings</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary: #4361ee;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --card-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #f5f7fb;
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
            overflow: hidden;
        }

        /* Employee Avatar */
        .employee-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4361ee 0%, #7c3aed 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        /* Table Styling */
        #earningsTable {
            margin-bottom: 0 !important;
            font-size: 0.8rem;
        }

        #earningsTable thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #6c757d;
            padding: 0.6rem 0.5rem;
            white-space: nowrap;
        }

        #earningsTable tbody td {
            padding: 0.5rem;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            font-size: 0.8rem;
        }

        #earningsTable tbody tr:hover {
            background-color: #f8f9ff;
        }

        #earningsTable tbody tr.table-active {
            background-color: #e8f4ff !important;
        }

        /* Expand button */
        .btn-expand {
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Expanded row styling */
        .expanded-row td {
            padding: 0 !important;
            background-color: transparent !important;
        }

        .expanded-row .bg-light {
            border-top: 2px solid #4361ee;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .content {
                padding: 1rem;
                margin-left: 0;
            }
        }

        /* Summary cards enhanced */
        .card h3 {
            font-size: 1.5rem;
        }

        /* DataTables customization */
        .dataTables_wrapper .dataTables_length select {
            padding: 0.25rem 2rem 0.25rem 0.5rem;
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
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h1 class="page-title"><i class="fas fa-money-bill-wave me-2"></i>Employee Earnings</h1>
                                <p class="text-muted mb-0">Track daily earnings, jobs, and process payments</p>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <label class="me-2 fw-bold">Date:</label>
                                <input type="date" id="earningsDate" class="form-control" style="width: 180px;" value="<?php echo date('Y-m-d'); ?>">
                                <button type="button" class="btn btn-outline-primary" id="refreshBtn">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Total Employees</h6>
                                    <h3 class="mb-0" id="totalEmployees">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Today's Earnings</h6>
                                    <h3 class="mb-0 text-primary" id="totalEarnings">LKR 0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Pending Balance</h6>
                                    <h3 class="mb-0 text-warning" id="totalPending">LKR 0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Total Payable</h6>
                                    <h3 class="mb-0 text-success" id="totalPayable">LKR 0.00</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Earnings Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="earningsTable" class="table table-hover" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Employee</th>
                                            <th>Payment Type</th>
                                            <th>Rate</th>
                                            <th>Jobs</th>
                                            <th>Today's Earnings</th>
                                            <th>Pending</th>
                                            <th>Total Payable</th>
                                            <th>Status</th>
                                            <th>Action</th>
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

    <!-- Payment Confirmation Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-money-check-alt me-2"></i>Confirm Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="text-muted">Employee</label>
                        <h5 id="paymentEmployeeName">-</h5>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="text-muted">Payment Date</label>
                            <h6 id="paymentDate">-</h6>
                        </div>
                        <div class="col-6">
                            <label class="text-muted">Payment Type</label>
                            <h6 id="paymentType">-</h6>
                        </div>
                    </div>
                    <hr>
                    <h6 class="mb-3">Earnings Breakdown</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Base/Daily Amount:</span>
                        <strong id="paymentBase">LKR 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Commission:</span>
                        <strong id="paymentCommission">LKR 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-warning">
                        <span>Pending Balance:</span>
                        <strong id="paymentPending">LKR 0.00</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-0">
                        <h5>Total Payable:</h5>
                        <h4 class="text-success" id="paymentTotal">LKR 0.00</h4>
                    </div>
                    <input type="hidden" id="paymentEmployeeId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmPaymentBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1"></span>
                        <i class="fas fa-check me-1"></i>Confirm Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/employee_earnings.js"></script>
</body>

</html>
