$(document).ready(function () {
    let earningsTable;
    const AJAX_URL = '../../Ajax/php/employee_earnings.php';

    // Format currency
    function formatCurrency(amount) {
        return 'LKR ' + parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Initialize DataTable
    function initTable() {
        const date = $('#earningsDate').val();

        if (earningsTable) {
            earningsTable.destroy();
            $('#earningsTable tbody').empty();
        }

        earningsTable = $('#earningsTable').DataTable({
            ajax: {
                url: AJAX_URL + '?action=get_earnings_summary&date=' + date,
                dataSrc: function (response) {
                    if (response.status === 'success') {
                        updateSummaryCards(response.data);
                        return response.data;
                    }
                    return [];
                }
            },
            columns: [
                {
                    className: 'details-control text-center',
                    orderable: false,
                    data: null,
                    width: '40px',
                    render: function () {
                        return '<button class="btn btn-sm btn-outline-primary btn-expand"><i class="fas fa-plus"></i></button>';
                    }
                },
                {
                    data: null,
                    title: 'Employee',
                    render: function (data) {
                        return `<div><strong>${data.name}</strong><br><small class="text-muted">${data.employee_code}</small></div>`;
                    }
                },
                {
                    data: 'salary_type',
                    title: 'Pay Type',
                    className: 'text-center',
                    render: function (data) {
                        const badges = {
                            'monthly': '<span class="badge bg-primary">Monthly</span>',
                            'daily': '<span class="badge bg-success">Daily</span>',
                            'commission': '<span class="badge bg-warning text-dark">Commission</span>'
                        };
                        return badges[data] || badges['monthly'];
                    }
                },
                {
                    data: null,
                    title: 'Rate/Salary',
                    className: 'text-end',
                    render: function (data) {
                        if (data.salary_type === 'commission') {
                            return `<span class="fw-bold text-warning">${data.salary}%</span>`;
                        }
                        return formatCurrency(data.salary);
                    }
                },
                {
                    data: 'jobs_count',
                    title: 'Jobs',
                    className: 'text-center',
                    render: function (data) {
                        if (data > 0) {
                            return `<span class="badge bg-info fs-6">${data}</span>`;
                        }
                        return '<span class="text-muted">0</span>';
                    }
                },
                {
                    data: 'today_earnings',
                    title: "Today's Earnings",
                    className: 'text-end',
                    render: function (data) {
                        const amount = parseFloat(data);
                        if (amount > 0) {
                            return `<span class="text-primary fw-bold">${formatCurrency(amount)}</span>`;
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: 'pending_balance',
                    title: 'Pending',
                    className: 'text-end',
                    render: function (data) {
                        const amount = parseFloat(data);
                        if (amount > 0) {
                            return `<span class="badge bg-warning text-dark">${formatCurrency(amount)}</span>`;
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: 'total_payable',
                    title: 'Total Payable',
                    className: 'text-end',
                    render: function (data) {
                        const amount = parseFloat(data);
                        if (amount > 0) {
                            return `<span class="fs-6 fw-bold text-success">${formatCurrency(amount)}</span>`;
                        }
                        return '<span class="text-muted">LKR 0.00</span>';
                    }
                },
                {
                    data: 'is_paid',
                    title: 'Status',
                    className: 'text-center',
                    render: function (data) {
                        if (data) {
                            return '<span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i>Paid</span>';
                        }
                        return '<span class="badge bg-danger px-3 py-2"><i class="fas fa-clock me-1"></i>Unpaid</span>';
                    }
                },
                {
                    data: null,
                    title: 'Action',
                    className: 'text-center',
                    orderable: false,
                    render: function (data) {
                        if (data.is_paid) {
                            return '<span class="text-success"><i class="fas fa-check-double"></i></span>';
                        }
                        if (parseFloat(data.total_payable) <= 0) {
                            return '<span class="text-muted">-</span>';
                        }
                        return `<button class="btn btn-success btn-sm pay-btn" data-id="${data.id}">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Pay
                                </button>`;
                    }
                }
            ],
            responsive: true,
            scrollX: false,
            pageLength: 25,
            order: [[1, 'asc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            language: {
                emptyTable: '<div class="text-center py-4"><i class="fas fa-users fa-3x text-muted mb-3"></i><br>No employees found</div>',
                zeroRecords: '<div class="text-center py-4">No matching employees found</div>'
            },
            drawCallback: function () {
                // Style adjustments after draw
                $('#earningsTable thead th').addClass('bg-light');
            }
        });

        // Row expand/collapse handler
        $('#earningsTable tbody').off('click', '.btn-expand').on('click', '.btn-expand', function () {
            const tr = $(this).closest('tr');
            const row = earningsTable.row(tr);
            const btn = $(this);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('table-active');
                btn.html('<i class="fas fa-plus"></i>').removeClass('btn-danger').addClass('btn-outline-primary');
            } else {
                const data = row.data();
                btn.html('<i class="fas fa-spinner fa-spin"></i>');

                loadJobDetails(data.id, function (detailsHtml) {
                    row.child(detailsHtml).show();
                    tr.addClass('table-active');
                    btn.html('<i class="fas fa-minus"></i>').removeClass('btn-outline-primary').addClass('btn-danger');
                });
            }
        });
    }

    // Update summary cards
    function updateSummaryCards(data) {
        let totalEarnings = 0;
        let totalPending = 0;
        let totalPayable = 0;

        data.forEach(emp => {
            totalEarnings += parseFloat(emp.today_earnings || 0);
            totalPending += parseFloat(emp.pending_balance || 0);
            totalPayable += parseFloat(emp.total_payable || 0);
        });

        $('#totalEmployees').text(data.length);
        $('#totalEarnings').text(formatCurrency(totalEarnings));
        $('#totalPending').text(formatCurrency(totalPending));
        $('#totalPayable').text(formatCurrency(totalPayable));
    }

    // Load job details for expandable row
    function loadJobDetails(employeeId, callback) {
        const date = $('#earningsDate').val();

        $.get(AJAX_URL, {
            action: 'get_employee_jobs',
            employee_id: employeeId,
            date: date
        }, function (res) {
            if (res.status === 'success') {
                const data = res.data;
                let html = '<tr class="expanded-row"><td colspan="10"><div class="bg-light p-4 mx-2 my-2 rounded">';

                if (data.jobs.length === 0) {
                    html += `
                        <div class="text-center py-4">
                            <i class="fas fa-briefcase fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No jobs assigned on this date</p>
                            <small class="text-muted">Earnings are based on ${data.employee.salary_type} rate</small>
                        </div>
                    `;
                } else {
                    html += '<h6 class="mb-3 fw-bold"><i class="fas fa-list-check me-2 text-primary"></i>Jobs Worked On</h6>';
                    html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-3">';
                    html += '<thead class="table-secondary"><tr>';
                    html += '<th>Job #</th><th>Customer</th><th>Vehicle</th><th>Service Package</th><th class="text-end">Amount</th>';

                    if (data.employee.salary_type === 'commission') {
                        html += '<th class="text-end">Commission</th>';
                    }
                    html += '<th class="text-center">Status</th></tr></thead><tbody>';

                    const commissionRate = data.employee.salary_type === 'commission'
                        ? parseFloat(data.employee.salary) / 100 : 0;

                    let totalJobAmount = 0;
                    let totalCommission = 0;

                    data.jobs.forEach(job => {
                        const jobAmount = parseFloat(job.total_amount || 0);
                        const commission = jobAmount * commissionRate;
                        totalJobAmount += jobAmount;
                        totalCommission += commission;

                        html += `<tr>
                            <td><a href="job_details.php?id=${job.id}" target="_blank" class="fw-bold text-primary">${job.job_number}</a></td>
                            <td>${job.customer_name || '-'}</td>
                            <td>${job.registration_number || '-'}</td>
                            <td>${job.package_name || '-'}</td>
                            <td class="text-end">${formatCurrency(jobAmount)}</td>`;

                        if (data.employee.salary_type === 'commission') {
                            html += `<td class="text-end text-success fw-bold">${formatCurrency(commission)}</td>`;
                        }

                        const statusClass = (job.status === 'completed' || job.status === 'delivered') ? 'success' : 'info';
                        html += `<td class="text-center"><span class="badge bg-${statusClass}">${job.status}</span></td></tr>`;
                    });

                    // Total row for commission
                    if (data.employee.salary_type === 'commission') {
                        html += `<tr class="table-light fw-bold">
                            <td colspan="4" class="text-end">Total:</td>
                            <td class="text-end">${formatCurrency(totalJobAmount)}</td>
                            <td class="text-end text-success">${formatCurrency(totalCommission)}</td>
                            <td></td>
                        </tr>`;
                    }

                    html += '</tbody></table></div>';
                }

                // Earnings Summary Card
                html += `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-0 bg-white">
                                <div class="card-body py-2">
                                    <small class="text-muted">Payment Configuration</small>
                                    <div class="mt-2">
                                        <span class="badge bg-${data.employee.salary_type === 'commission' ? 'warning text-dark' : (data.employee.salary_type === 'daily' ? 'success' : 'primary')} me-2">
                                            ${data.employee.salary_type.toUpperCase()}
                                        </span>
                                        ${data.employee.salary_type === 'commission'
                        ? `<span class="fw-bold">${data.employee.salary}% per job</span>`
                        : `<span class="fw-bold">${formatCurrency(data.employee.salary)}</span>`
                    }
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-success bg-opacity-10">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">Today's Earnings</small>
                                            <div class="fw-bold">${formatCurrency(data.earnings.today_earnings)}</div>
                                        </div>
                                        ${data.earnings.pending_balance > 0 ? `
                                            <div class="text-center px-3 border-start border-end">
                                                <small class="text-warning">Pending</small>
                                                <div class="fw-bold text-warning">${formatCurrency(data.earnings.pending_balance)}</div>
                                            </div>
                                        ` : ''}
                                        <div class="text-end">
                                            <small class="text-success">Total Payable</small>
                                            <div class="fs-5 fw-bold text-success">${formatCurrency(data.earnings.total_payable)}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                html += '</div></td></tr>';
                callback(html);
            } else {
                callback('<tr><td colspan="10" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i>Error loading details</td></tr>');
            }
        }, 'json').fail(function () {
            callback('<tr><td colspan="10" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load details</td></tr>');
        });
    }

    // Initialize on page load
    initTable();

    // Date change handler
    $('#earningsDate').on('change', function () {
        initTable();
    });

    // Refresh button
    $('#refreshBtn').on('click', function () {
        initTable();
    });

    // Pay Now button click
    $(document).on('click', '.pay-btn', function () {
        const employeeId = $(this).data('id');
        const date = $('#earningsDate').val();

        $.get(AJAX_URL, {
            action: 'get_employee_jobs',
            employee_id: employeeId,
            date: date
        }, function (res) {
            if (res.status === 'success') {
                const data = res.data;

                $('#paymentEmployeeId').val(employeeId);
                $('#paymentEmployeeName').text(data.employee.name);
                $('#paymentDate').text(new Date(date).toLocaleDateString());
                $('#paymentType').text(data.employee.salary_type.charAt(0).toUpperCase() + data.employee.salary_type.slice(1));
                $('#paymentBase').text(formatCurrency(data.earnings.base_amount));
                $('#paymentCommission').text(formatCurrency(data.earnings.commission_amount));
                $('#paymentPending').text(formatCurrency(data.earnings.pending_balance));
                $('#paymentTotal').text(formatCurrency(data.earnings.total_payable));

                $('#paymentModal').modal('show');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    // Confirm payment
    $('#confirmPaymentBtn').on('click', function () {
        const btn = $(this);
        const spinner = btn.find('.spinner-border');
        const employeeId = $('#paymentEmployeeId').val();
        const date = $('#earningsDate').val();

        btn.prop('disabled', true);
        spinner.removeClass('d-none');

        $.post(AJAX_URL, {
            action: 'process_payment',
            employee_id: employeeId,
            date: date
        }, function (res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Processed!',
                    text: 'Total Paid: ' + formatCurrency(res.data.total_paid),
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#paymentModal').modal('hide');
                initTable();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
            btn.prop('disabled', false);
            spinner.addClass('d-none');
        }, 'json').fail(function () {
            Swal.fire('Error', 'Network error occurred', 'error');
            btn.prop('disabled', false);
            spinner.addClass('d-none');
        });
    });
});
