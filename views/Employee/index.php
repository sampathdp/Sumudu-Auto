<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Employee Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        body { background-color: #f5f7fb; }
        .content { margin-top: 70px; padding: 2rem; }
        .page-header {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            padding: 1.5rem 2rem;
            border-left: 4px solid #4361ee;
        }
        .card { border: none; border-radius: 12px; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05); }
        .card-header { background: #fff; border-bottom: 1px solid #eee; padding: 1.25rem 1.5rem; }
        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4361ee, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .badge-active { background-color: #28a745; }
        .badge-inactive { background-color: #dc3545; }
        .btn-actions { white-space: nowrap; }
        .btn-actions .btn { 
            padding: 0.25rem 0.5rem; 
            margin: 0 2px;
            display: inline-block;
        }
        #employeesTable { width: 100% !important; }
        #employeesTable th, #employeesTable td { vertical-align: middle; }
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
                            <h1 class="page-title"><i class="fas fa-users me-2"></i>Employee Management</h1>
                            <p class="page-subtitle mb-0">Manage your team members</p>
                        </div>
                        <button class="btn btn-primary" id="addEmployeeBtn">
                            <i class="fas fa-plus me-1"></i>Add Employee
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">All Employees</h5>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="showActiveOnly">
                                <label class="form-check-label" for="showActiveOnly">Active Only</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="employeesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee</th>
                                            <th>Code</th>
                                            <th>Position</th>
                                            <th>Department</th>
                                            <th>Contact</th>
                                            <th>Hire Date</th>
                                            <th>Status</th>
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

    <!-- Employee Modal -->
    <div class="modal fade" id="employeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="employeeForm">
                    <input type="hidden" id="employeeId" name="id">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTitle"><i class="fas fa-user-plus me-2"></i>Add Employee</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <!-- Basic Information -->
                        <h6 class="text-primary border-bottom pb-2 mb-3"><i class="fas fa-user me-2"></i>Basic Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" id="firstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" id="lastName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" id="phone">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                        </div>

                        <!-- Work Information -->
                        <h6 class="text-primary border-bottom pb-2 mb-3 mt-4"><i class="fas fa-briefcase me-2"></i>Work Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-control" name="position" id="position" placeholder="e.g. Mechanic, Manager">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" name="department" id="department" placeholder="e.g. Service, Sales">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hire Date</label>
                                <input type="date" class="form-control" name="hire_date" id="hireDate">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary Type</label>
                                <select class="form-select" name="salary_type" id="salaryType">
                                    <option value="monthly">Monthly Salary</option>
                                    <option value="daily">Daily Rate</option>
                                    <option value="commission">Commission (Job-based)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" id="salaryLabel">Monthly Salary (LKR)</label>
                                <input type="number" class="form-control" name="salary" id="salary" min="0" step="0.01">
                                <small class="text-muted" id="salaryHint">Base monthly salary amount</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <h6 class="text-primary border-bottom pb-2 mb-3 mt-4"><i class="fas fa-phone-alt me-2"></i>Emergency Contact</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Name</label>
                                <input type="text" class="form-control" name="emergency_contact" id="emergencyContact">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" name="emergency_phone" id="emergencyPhone">
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="isActive" name="is_active" value="1" checked>
                            <label class="form-check-label" for="isActive">Active Employee</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none me-1"></span>
                            <span class="btn-text">Save Employee</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/employee.js"></script>
</body>
</html>

