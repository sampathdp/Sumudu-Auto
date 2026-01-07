<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Financial Accounts</title>
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
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; border-radius: 8px; padding: 1.25rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .stat-card .icon.primary { background: rgba(8, 145, 178, 0.1); color: var(--primary-color); }
        .stat-card .icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-card .icon.info { background: rgba(59, 130, 246, 0.1); color: var(--info-color); }
        .stat-card .details h3 { font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-dark); }
        .stat-card .details p { font-size: 0.8125rem; color: var(--text-muted); margin: 0; }
        .data-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; height: 100%; }
        .data-card-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); background-color: var(--bg-light); display: flex; align-items: center; justify-content: space-between; }
        .data-card-header h5 { margin: 0; font-size: 0.9375rem; font-weight: 600; color: var(--text-dark); }
        .table thead th { background-color: var(--bg-light); border-bottom: 2px solid var(--border-color); font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
        .balance-positive { color: var(--success-color); font-weight: 600; }
        .balance-negative { color: var(--danger-color); font-weight: 600; }
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
                            <h1><i class="fas fa-wallet me-2"></i>Financial Accounts</h1>
                            <p class="text-muted small mb-0">Manage Cash and Bank Account Details</p>
                        </div>
                        <button class="btn btn-primary" onclick="openAccountModal()">
                            <i class="fas fa-plus me-1"></i> Add Account
                        </button>
                    </div>

                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="icon success"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="details"><h3 id="stat_cash">0.00</h3><p>Total Cash</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon primary"><i class="fas fa-university"></i></div>
                            <div class="details"><h3 id="stat_bank">0.00</h3><p>Total Bank</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon info"><i class="fas fa-chart-line"></i></div>
                            <div class="details"><h3 id="stat_total">0.00</h3><p>Total Liquidity</p></div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Cash Accounts -->
                        <div class="col-md-6">
                            <div class="data-card">
                                <div class="data-card-header">
                                    <h5><i class="fas fa-wallet me-2 text-success"></i>Cash Accounts</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="cashAccountsTable">
                                        <thead>
                                            <tr>
                                                <th>Account Name</th>
                                                <th class="text-end">Balance (LKR)</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Accounts -->
                        <div class="col-md-6">
                            <div class="data-card">
                                <div class="data-card-header">
                                    <h5><i class="fas fa-university me-2 text-primary"></i>Bank Accounts</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="bankAccountsTable">
                                        <thead>
                                            <tr>
                                                <th>Bank Details</th>
                                                <th class="text-end">Balance (LKR)</th>
                                                <th class="text-end">Action</th>
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
    </div>

    <!-- Account Modal -->
    <div class="modal fade" id="accountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="accountForm">
                    <div class="modal-body">
                        <input type="hidden" id="account_id" name="id">
                        
                        <div class="mb-3">
                            <label class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_type" name="account_type" required onchange="toggleBankFields()">
                                <option value="cash">Cash Account</option>
                                <option value="bank">Bank Account</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_name" name="account_name" required placeholder="e.g. Petty Cash">
                        </div>

                        <div id="bankFields" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name" placeholder="e.g. BOC">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-control" id="account_number" name="account_number">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Opening Balance (LKR)</label>
                            <input type="number" step="0.01" class="form-control" id="opening_balance" name="opening_balance" value="0.00">
                            <small class="text-muted">Only editable for new accounts.</small>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active Account</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                            <label class="form-check-label" for="is_default">
                                Set as Default <small class="text-muted">(for this account type)</small>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script>
    function toggleBankFields() {
        const type = $('#account_type').val();
        if (type === 'bank') {
            $('#bankFields').slideDown();
        } else {
            $('#bankFields').slideUp();
        }
    }

    function formatCurrency(amount) {
        return parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    function loadStats() {
        $.get('../../Ajax/php/financial.php', { action: 'get_stats' }, function(res) {
            if(res.status === 'success') {
                $('#stat_cash').text(formatCurrency(res.data.total_cash));
                $('#stat_bank').text(formatCurrency(res.data.total_bank));
                $('#stat_total').text(formatCurrency(res.data.total_liquidity));
            }
        });
    }

    function loadAccounts() {
        loadStats();
        $.get('../../Ajax/php/financial.php', { action: 'get_accounts' }, function(res) {
            if (res.status === 'success') {
                const cashRows = [];
                const bankRows = [];
                
                res.data.forEach(acc => {
                    const balanceClass = parseFloat(acc.current_balance) < 0 ? 'balance-negative' : 'balance-positive';
                    const row = `
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">${acc.account_name}</div>
                                ${acc.bank_name ? `<small class="text-muted">${acc.bank_name} - ${acc.account_number}</small>` : ''}
                                ${acc.is_default == 1 ? '<span class="badge bg-secondary ms-1">Default</span>' : ''}
                            </td>
                            <td class="text-end ${balanceClass}">
                                ${formatCurrency(acc.current_balance)}
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" onclick="editAccount(${acc.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    if (acc.account_type === 'cash') cashRows.push(row);
                    else bankRows.push(row);
                });

                $('#cashAccountsTable tbody').html(cashRows.join('') || '<tr><td colspan="3" class="text-center text-muted py-3">No cash accounts found</td></tr>');
                $('#bankAccountsTable tbody').html(bankRows.join('') || '<tr><td colspan="3" class="text-center text-muted py-3">No bank accounts found</td></tr>');
            }
        });
    }

    function openAccountModal() {
        $('#accountForm')[0].reset();
        $('#account_id').val('');
        $('#opening_balance').prop('disabled', false);
        $('#is_default').prop('checked', false);
        $('#accountModal').modal('show');
        toggleBankFields();
    }

    function editAccount(id) {
        $.get('../../Ajax/php/financial.php', { action: 'get_account', id: id }, function(res) {
            if (res.status === 'success') {
                const acc = res.data;
                $('#account_id').val(acc.id);
                $('#account_type').val(acc.account_type);
                $('#account_name').val(acc.account_name);
                $('#bank_name').val(acc.bank_name);
                $('#account_number').val(acc.account_number);
                $('#opening_balance').val(acc.opening_balance).prop('disabled', true);
                $('#is_active').prop('checked', acc.is_active == 1);
                $('#is_default').prop('checked', acc.is_default == 1);
                
                toggleBankFields();
                $('#accountModal').modal('show');
            }
        });
    }

    $('#accountForm').submit(function(e) {
        e.preventDefault();
        const data = $(this).serialize() + '&action=save_account';
        
        $.post('../../Ajax/php/financial.php', data, function(res) {
            if (res.status === 'success') {
                Swal.fire('Success', res.message, 'success');
                $('#accountModal').modal('hide');
                loadAccounts();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
    });

    $(document).ready(function() {
        loadAccounts();
    });
    </script>
</body>
</html>
