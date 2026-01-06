<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in and has permission to view this page
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Permission Management</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --card-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease-in-out;
        }

        body {
            background-color: #f5f7fb;
            color: #3c4858;
            font-family: 'Public Sans', sans-serif;
        }

        .content {
            margin-top: 70px;
            padding: 2rem;
            transition: var(--transition);
        }

        .page-header {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            padding: 1.5rem 2rem;
            border-left: 4px solid var(--primary-color);
        }

        .page-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.25rem;
            font-size: 1.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1.5rem rgba(67, 97, 238, 0.15);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0;
            font-size: 1.1rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            color: #64748b;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .table td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            border-color: #f1f5f9;
            color: #475569;
        }

        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            border-radius: 50px;
        }

        .badge.bg-primary {
            background-color: rgba(67, 97, 238, 0.1) !important;
            color: var(--primary-color) !important;
        }

        .badge.bg-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
            color: var(--secondary-color) !important;
        }

        code {
            font-size: 0.85em;
            color: #6c757d;
        }

        .btn {
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn i {
            font-size: 0.9em;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .permission-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid #f1f5f9;
            padding: 1.25rem 1.5rem;
        }

        .modal-title {
            font-weight: 600;
            color: #2c3e50;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #f1f5f9;
            padding: 1.25rem 1.5rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .content {
                padding: 1rem;
                margin-left: 0;
            }

            .page-header {
                padding: 1.25rem;
            }

            .table-responsive {
                border-radius: 8px;
                border: 1px solid #f1f5f9;
            }
        }

        /* Animation */
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="animate-fade-in">
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="page-header animate__animated animate__fadeIn">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="page-title">Permission Management</h1>
                                <p class="page-subtitle mb-0">Manage application permissions and access controls</p>
                            </div>
                            <div class="ms-auto">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#permissionModal">
                                    <i class="fas fa-plus me-2"></i>Add New Permission
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Main Content -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card animate__animated animate__fadeInUp">
                                <div class="card-header">
                                    <h5 class="card-title">Permissions List</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="permissionsTable" class="table table-hover align-middle" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th width="5%">ID</th>
                                                    <th width="25%">Permission Name</th>
                                                    <th width="20%">Code</th>
                                                    <th width="35%">Description</th>
                                                    <th width="10%">Created At</th>
                                                    <th width="5%" class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        <p class="mt-2 mb-0 text-muted">Loading permissions...</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Permission Modal -->
    <div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <form id="permissionForm" class="needs-validation" novalidate>
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="permissionModalLabel">Add New Permission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" id="permission_id" name="id">
                        <div class="mb-4">
                            <label for="permission_name" class="form-label">Permission Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="text" class="form-control" id="permission_name" name="permission_name" required
                                    placeholder="Enter permission name" autofocus>
                            </div>
                            <div class="invalid-feedback">Please provide a permission name.</div>
                        </div>
                        <div class="mb-4">
                            <label for="permission_code" class="form-label">Permission Code <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-code"></i></span>
                                <input type="text" class="form-control" id="permission_code" name="permission_code" required
                                    placeholder="Enter unique code (e.g., view_users)" pattern="[a-zA-Z_][a-zA-Z0-9_]*">
                            </div>
                            <div class="invalid-feedback">Please provide a unique permission code.</div>
                        </div>
                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <textarea class="form-control" id="description" name="description"
                                    rows="3" placeholder="Enter permission description (optional)"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="savePermissionBtn">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/permission.js"></script>
</body>

</html>