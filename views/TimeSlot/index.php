<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in
requirePagePermission('View');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Time Slot Management</title>
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

        .table th {
            background-color: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
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

        .btn {
            font-weight: 500;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
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
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#timeSlotModal">
                                    <i class="fas fa-plus-circle"></i>Add New Time Slot
                                </button>
                                <div>
                                    <h1 class="page-title"><i class="fas fa-clock me-2"></i>Time Slot Management</h1>
                                    <p class="page-subtitle">Configure available booking slots</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card animate__animated animate__fadeInUp">
                                <div class="card-header">
                                    <h5 class="card-title">Available Time Slots</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="timeSlotsTable" class="table table-hover align-middle" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th width="5%">ID</th>
                                                    <th width="20%">Start Time</th>
                                                    <th width="20%">End Time</th>
                                                    <th width="20%">Max Bookings</th>
                                                    <th width="15%">Status</th>
                                                    <th width="20%" class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Loaded via AJAX -->
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

    <!-- Time Slot Modal -->
    <div class="modal fade" id="timeSlotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <form id="timeSlotForm" class="needs-validation" novalidate>
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="timeSlotModalLabel">Add New Time Slot</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" id="slot_id" name="id">
                        
                        <div class="row mb-4">
                            <div class="col-6">
                                <label for="slot_start" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="slot_start" name="slot_start" required>
                                <div class="invalid-feedback">Required.</div>
                            </div>
                            <div class="col-6">
                                <label for="slot_end" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="slot_end" name="slot_end" required>
                                <div class="invalid-feedback">Required.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="max_bookings" class="form-label">Max Bookings per Slot</label>
                            <input type="number" class="form-control" id="max_bookings" name="max_bookings" 
                                   value="3" min="1" required>
                            <div class="form-text">Limit concurrent bookings for this time period.</div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Active Status</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveSlotBtn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="../../Ajax/js/time_slot.js"></script>
</body>
</html>
