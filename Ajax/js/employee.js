$(document).ready(function () {
    let employeesTable;
    const AJAX_URL = '../../Ajax/php/employee.php';

    // Initialize DataTable
    function initTable() {
        if (employeesTable) {
            employeesTable.destroy();
        }

        const activeOnly = $('#showActiveOnly').is(':checked') ? '1' : '0';

        employeesTable = $('#employeesTable').DataTable({
            ajax: {
                url: AJAX_URL + '?action=list&active_only=' + activeOnly,
                dataSrc: function (response) {
                    return response.status === 'success' ? response.data : [];
                }
            },
            columns: [
                {
                    data: null,
                    render: function (data) {
                        const initials = (data.first_name.charAt(0) + data.last_name.charAt(0)).toUpperCase();
                        return `
                            <div class="d-flex align-items-center">
                                <div class="employee-avatar me-2">${initials}</div>
                                <div>
                                    <strong>${data.first_name} ${data.last_name}</strong>
                                    ${data.email ? `<br><small class="text-muted">${data.email}</small>` : ''}
                                </div>
                            </div>
                        `;
                    }
                },
                { data: 'employee_code' },
                {
                    data: 'position',
                    render: function (data) {
                        return data || '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: 'department',
                    render: function (data) {
                        return data ? `<span class="badge bg-info">${data}</span>` : '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: 'phone',
                    render: function (data) {
                        return data || '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: 'hire_date',
                    render: function (data) {
                        return data ? new Date(data).toLocaleDateString() : '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: 'is_active',
                    render: function (data) {
                        return data == 1
                            ? '<span class="badge badge-active">Active</span>'
                            : '<span class="badge badge-inactive">Inactive</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center btn-actions',
                    render: function (data) {
                        return `
                            <button class="btn btn-sm btn-outline-primary edit-btn" data-id="${data.id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-${data.is_active == 1 ? 'warning' : 'success'} toggle-btn" data-id="${data.id}" title="Toggle Status">
                                <i class="fas fa-${data.is_active == 1 ? 'ban' : 'check'}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ],
            responsive: true,
            pageLength: 10,
            order: [[1, 'asc']],
            language: {
                emptyTable: 'No employees found',
                zeroRecords: 'No matching employees found'
            }
        });
    }

    initTable();

    // Filter change
    $('#showActiveOnly').on('change', function () {
        initTable();
    });

    // Add Employee
    $('#addEmployeeBtn').on('click', function () {
        $('#employeeForm')[0].reset();
        $('#employeeId').val('');
        $('#modalTitle').html('<i class="fas fa-user-plus me-2"></i>Add Employee');
        $('#isActive').prop('checked', true);
        $('#salaryType').val('monthly').trigger('change');
        $('#employeeModal').modal('show');
    });

    // Salary type change handler
    $('#salaryType').on('change', function () {
        const type = $(this).val();
        const labels = {
            'monthly': { label: 'Monthly Salary (LKR)', hint: 'Base monthly salary amount' },
            'daily': { label: 'Daily Rate (LKR)', hint: 'Daily wage rate' },
            'commission': { label: 'Commission Rate (%)', hint: 'Percentage earned per job' }
        };
        const config = labels[type] || labels['monthly'];
        $('#salaryLabel').text(config.label);
        $('#salaryHint').text(config.hint);
    });

    // Edit Employee
    $(document).on('click', '.edit-btn', function () {
        const id = $(this).data('id');

        $.get(AJAX_URL, { action: 'get', id: id }, function (res) {
            if (res.status === 'success') {
                const emp = res.data;
                $('#employeeId').val(emp.id);
                $('#firstName').val(emp.first_name);
                $('#lastName').val(emp.last_name);
                $('#email').val(emp.email);
                $('#phone').val(emp.phone);
                $('#position').val(emp.position);
                $('#department').val(emp.department);
                $('#hireDate').val(emp.hire_date);
                $('#salary').val(emp.salary);
                $('#salaryType').val(emp.salary_type || 'monthly').trigger('change');
                $('#address').val(emp.address);
                $('#emergencyContact').val(emp.emergency_contact);
                $('#emergencyPhone').val(emp.emergency_phone);
                $('#isActive').prop('checked', emp.is_active == 1);
                $('#notes').val(emp.notes);

                $('#modalTitle').html('<i class="fas fa-user-edit me-2"></i>Edit Employee');
                $('#employeeModal').modal('show');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    // Save Employee
    $('#employeeForm').on('submit', function (e) {
        e.preventDefault();

        const $btn = $(this).find('button[type="submit"]');
        const $spinner = $btn.find('.spinner-border');
        const $text = $btn.find('.btn-text');
        const id = $('#employeeId').val();
        const action = id ? 'update' : 'create';

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $text.text('Saving...');

        let formData = $(this).serialize() + '&action=' + action;
        formData += '&is_active=' + ($('#isActive').is(':checked') ? 1 : 0);

        $.post(AJAX_URL, formData, function (res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                $('#employeeModal').modal('hide');
                employeesTable.ajax.reload();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
            $btn.prop('disabled', false);
            $spinner.addClass('d-none');
            $text.text('Save Employee');
        }, 'json').fail(function () {
            Swal.fire('Error', 'Network error', 'error');
            $btn.prop('disabled', false);
            $spinner.addClass('d-none');
            $text.text('Save Employee');
        });
    });

    // Toggle Status
    $(document).on('click', '.toggle-btn', function () {
        const id = $(this).data('id');

        $.post(AJAX_URL, { action: 'toggle_status', id: id }, function (res) {
            if (res.status === 'success') {
                employeesTable.ajax.reload();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    // Delete Employee
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete Employee?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(AJAX_URL, { action: 'delete', id: id }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        employeesTable.ajax.reload();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });
});
