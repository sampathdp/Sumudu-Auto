<?php

require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in and has permission to view this page
// requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title><?php echo APP_NAME; ?> - User Permissions Management</title>
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
            text-align: center;
        }

        .table td {
            padding: 1rem 0.5rem;
            vertical-align: middle;
            border-color: #f1f5f9;
            color: #475569;
            text-align: center;
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .table tbody tr td input[type="checkbox"]:checked {
            animation: pulse 0.3s ease;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .form-check-input {
            width: 1.25em;
            height: 1.25em;
            margin: 0;
            vertical-align: top;
            background-color: #fff;
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            border: 1px solid #ced4da;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            transition: var(--transition);
        }

        .form-check-input:checked {
            background-color: #4361ee;
            border-color: #4361ee;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
        }

        .form-check-input:focus {
            border-color: #b1b9f3;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
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

        .btn-outline-secondary {
            color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-outline-secondary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: #fff;
        }

        .form-select,
        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            transition: var(--transition);
        }

        .form-select:focus,
        .form-control:focus {
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

            .table th,
            .table td {
                padding: 0.75rem 0.25rem;
                font-size: 0.85rem;
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
                                <h1 class="page-title">User Permissions Management</h1>
                                <p class="page-subtitle mb-0">Select a user to manage their permissions across pages</p>
                            </div>
                        </div>
                    </div>
                    <!-- Alert Container -->
                    <div id="alert-container" class="mb-3"></div>
                    <!-- Main Content -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card animate__animated animate__fadeInUp">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">User Permissions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-2">
                                            <label for="user_type" class="form-label">User Type</label>
                                            <select class="form-select" id="user_type">
                                                <option value="0">All Users</option>
                                                <?php
                                                $role = new Role();
                                                $roles = $role->all();
                                                foreach ($roles as $r) {
                                                    echo '<option value="' . $r['id'] . '">' . htmlspecialchars($r['role_name']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="selected_user" class="form-label">Select User <span class="text-danger">*</span></label>
                                            <select class="form-select" id="selected_user" required>
                                                <option value="">-- Select User --</option>
                                                <?php
                                                $user = new User();
                                                $users = $user->all($_SESSION['company_id'] ?? null);
                                                foreach ($users as $u) {
                                                    $role_id = $u['role_id'] ?? 0; // Assume role_id exists
                                                    echo '<option value="' . $u['id'] . '" data-role="' . $role_id . '">' . htmlspecialchars($u['username']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="search_pages" class="form-label">Search Pages</label>
                                            <input type="text" class="form-control" id="search_pages" placeholder="Search pages or modules...">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <div class="form-check me-3 mb-0">
                                                <input type="checkbox" class="form-check-input" id="select_all">
                                                <label class="form-check-label mb-0" for="select_all">All</label>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary me-2" id="refresh">
                                                <i class="fas fa-sync-alt"></i> Refresh
                                            </button>
                                            <button type="button" class="btn btn-primary" id="savePermissionsBtn" disabled>
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="permissionsMatrix" class="table table-hover table-striped align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="5%">ID</th>
                                                    <th width="20%">Page Module</th>
                                                    <th width="25%">Page</th>
                                                    <th class="text-center" width="8%">All</th>
                                                    <!-- Dynamic permission columns added by JS -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted"> <!-- Dynamic colspan adjusted by JS -->
                                                        <div class="d-flex flex-column align-items-center">
                                                            <i class="fas fa-user-shield fa-3x mb-3 text-muted"></i>
                                                            <p class="mb-0">Please select a user type and user to manage their permissions</p>
                                                        </div>
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
    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/user_permission.js"></script>
</body>

</html>