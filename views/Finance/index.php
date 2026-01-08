<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Daily Cashbook</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #0891b2; --primary-hover: #0e7490;
            --secondary-color: #64748b; --success-color: #059669; --danger-color: #dc2626;
            --warning-color: #d97706; --info-color: #3b82f6;
            --border-color: #e2e8f0; --bg-light: #f8fafc;
            --text-dark: #1e293b; --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; }
        .content { margin-top: 70px; padding: 1.5rem; }
        .page-header { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; padding: 1.25rem 1.5rem; border-left: 4px solid var(--primary-color); }
        .page-header h1 { font-size: 1.25rem; font-weight: 600; color: var(--text-dark); margin: 0; }
        
        .filter-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; }
        .filter-card-body { padding: 1.25rem; }
        
        .data-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .data-card-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); background-color: var(--bg-light); display: flex; align-items: center; justify-content: space-between; }
        .data-card-header h5 { margin: 0; font-size: 0.9375rem; font-weight: 600; color: var(--text-dark); }
        
        .table thead th { background-color: var(--bg-light); border-bottom: 2px solid var(--border-color); font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
        
        .balance-positive { color: var(--success-color); font-weight: 600; }
        .balance-negative { color: var(--danger-color); font-weight: 600; }
        
        .btn-action-group .btn { margin-left: 0.5rem; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="page-header d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-book me-2"></i>Daily Cashbook</h1>
                            <p class="text-muted small mb-0">Track financial movements and balances</p>
                        </div>
                        <div class="btn-action-group">
                            <button class="btn btn-success" onclick="openTransactionModal('income')">
                                <i class="fas fa-plus me-1"></i> Income
                            </button>
                            <button class="btn btn-info text-white" onclick="openTransferModal()">
                                <i class="fas fa-exchange-alt me-1"></i> Transfer
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filter-card">
                        <div class="filter-card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Select Account</label>
                                    <select class="form-select form-select-sm" id="filter_account" onchange="loadLedger()">
                                        <!-- Populated via JS -->
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Start Date</label>
                                    <input type="date" class="form-control form-control-sm" id="filter_start_date" onchange="loadLedger()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">End Date</label>
                                    <input type="date" class="form-control form-control-sm" id="filter_end_date" onchange="loadLedger()">
                                </div>
                                <div class="col-md-3 text-end">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
                                        <i class="fas fa-undo me-1"></i> Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction List -->
                    <div class="data-card">
                        <div class="data-card-header">
                            <h5 class="text-primary"><i class="fas fa-list me-2"></i>Transactions</h5>
                            <span class="badge bg-white text-dark border shadow-sm px-3 py-2" style="font-size: 0.9rem;" id="current_balance_badge">
                                Current Balance: LKR 0.00
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="ledgerTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Ref</th>
                                        <th class="text-end text-success">Money In</th>
                                        <th class="text-end text-danger">Money Out</th>
                                        <th class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Loading transactions...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transactionTitle">Record Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="transactionForm">
                    <div class="modal-body">
                        <input type="hidden" id="trans_type" name="type">
                        
                        <div class="mb-3">
                            <label class="form-label">Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="trans_account" name="account_id" required>
                                <!-- JS Populated -->
                            </select>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Category</label>
                                <input type="text" class="form-control" name="category" list="categories" placeholder="Overview">
                                <datalist id="categories">
                                    <option value="General">
                                    <option value="Sales">
                                    <option value="Expenses">
                                    <option value="Investment">
                                    <option value="Withdrawal">
                                </datalist>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount (LKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" required placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="2" required placeholder="Enter details..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Transfer Funds</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="transferForm">
                    <div class="modal-body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">From Account <span class="text-danger">*</span></label>
                                <select class="form-select" id="transfer_from" name="from_account_id" required></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">To Account <span class="text-danger">*</span></label>
                                <select class="form-select" id="transfer_to" name="to_account_id" required></select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount (LKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" required placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description / Note</label>
                            <input type="text" class="form-control" name="description" placeholder="Fund Transfer">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info text-white">Process Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script>
    let allAccounts = [];

    // Init
    $(document).ready(function() {
        // Set dates (This Month)
        const date = new Date();
        $('#filter_start_date').val(new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0]);
        $('#filter_end_date').val(new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0]);

        loadAccounts();
    });

    function formatCurrency(amount) {
        return parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    function loadAccounts() {
        $.get('../../Ajax/php/financial.php', { action: 'get_accounts' }, function(res) {
            if (res.status === 'success') {
                allAccounts = res.data;
                const options = res.data.map(acc => `<option value="${acc.id}">${acc.account_name}</option>`);
                
                // Populate Filters
                $('#filter_account').html(options.join(''));
                
                // Populate Modals
                $('#trans_account').html(options.join(''));
                $('#transfer_from').html(options.join(''));
                $('#transfer_to').html(options.join(''));
                
                // Auto load ledger for first account
                loadLedger();
            }
        });
    }

    function loadLedger() {
        const accountId = $('#filter_account').val();
        const start = $('#filter_start_date').val();
        const end = $('#filter_end_date').val();

        if(!accountId) return;

        // Update current balance badge
        const acc = allAccounts.find(a => a.id == accountId);
        if(acc) {
            $('#current_balance_badge').html(`Current Balance: <span class="fw-bold text-success">LKR ${formatCurrency(acc.current_balance)}</span>`);
        }

        $.get('../../Ajax/php/financial.php', { 
            action: 'get_ledger', 
            account_id: accountId,
            start_date: start,
            end_date: end
        }, function(res) {
            if (res.status === 'success') {
                const rows = res.data.map(t => {
                    const debit = parseFloat(t.debit_amount || 0);
                    const credit = parseFloat(t.credit_amount || 0);
                    const balance = parseFloat(t.balance_after || 0);
                    const balanceClass = balance < 0 ? 'text-danger' : 'text-dark';

                    return `
                        <tr>
                            <td>${t.transaction_date}</td>
                            <td>
                                <div class="fw-bold text-dark">${t.description}</div>
                                <span class="badge bg-light text-muted border">${t.category_type || 'General'}</span>
                            </td>
                            <td><small class="text-muted">${t.reference_type}</small></td>
                            <td class="text-end text-success fw-bold">${debit > 0 ? formatCurrency(debit) : '-'}</td>
                            <td class="text-end text-danger fw-bold">${credit > 0 ? formatCurrency(credit) : '-'}</td>
                            <td class="text-end fw-bold ${balanceClass}">${formatCurrency(balance)}</td>
                        </tr>
                    `;
                });

                if(rows.length === 0) {
                    $('#ledgerTable tbody').html('<tr><td colspan="6" class="text-center py-4 text-muted">No transactions found for this period</td></tr>');
                } else {
                    $('#ledgerTable tbody').html(rows.join(''));
                }
            }
        });
    }

    // Modal Helpers
    function openTransactionModal(type) {
        $('#transactionForm')[0].reset();
        $('#trans_type').val(type);
        $('#transactionTitle').text(type === 'income' ? 'Record Income (Money In)' : 'Record Expense (Money Out)');
        $('#transactionModal').modal('show');
    }

    function openTransferModal() {
        $('#transferForm')[0].reset();
        $('#transferModal').modal('show');
    }

    // Form Submits
    $('#transactionForm').submit(function(e) {
        e.preventDefault();
        const data = $(this).serialize() + '&action=add_transaction';
        $.post('../../Ajax/php/financial.php', data, function(res) {
            if(res.status === 'success') {
                Swal.fire('Saved', res.message, 'success');
                $('#transactionModal').modal('hide');
                loadLedger();
                // Refresh accounts to get new balances
                loadAccounts(); 
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
    });

    $('#transferForm').submit(function(e) {
        e.preventDefault();
        const data = $(this).serialize() + '&action=transfer';
        $.post('../../Ajax/php/financial.php', data, function(res) {
            if(res.status === 'success') {
                Swal.fire('Success', res.message, 'success');
                $('#transferModal').modal('hide');
                loadLedger();
                loadAccounts();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
    });

    function resetFilters() {
        const date = new Date();
        $('#filter_start_date').val(new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0]);
        $('#filter_end_date').val(new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0]);
        loadLedger();
    }
    </script>
</body>
</html>
