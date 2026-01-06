<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Authentication
if (!isset($_SESSION['id'])) {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit();
}

$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if (!$jobId) {
    header('Location: index.php');
    exit();
}

// Load Job & Related Data
$service = new Service($jobId);
if (!$service->id) {
    die('<div class="alert alert-danger">Job not found.</div>');
}

$customer = new Customer($service->customer_id);
$vehicle  = new Vehicle($service->vehicle_id);
$package  = new ServicePackage($service->package_id);

// Load existing health check report
$report = new HealthCheckReport();
$reportExists = $report->loadByJobId($jobId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Health Check Report #<?= $service->job_number ?></title>
    <?php include '../../includes/main-css.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        body { background-color: #f5f7fb; font-family: 'Public Sans', sans-serif; }
        .content { margin-top: 80px; padding: 2rem; }

        .page-header {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.05);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            border-left: 5px solid var(--primary);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), #3a56d4);
            color: white;
            border-bottom: none;
            padding: 1.25rem 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
        }

        .form-select, .form-control {
            border-radius: 8px;
            padding: 0.65rem 1rem;
            border: 1px solid #dee2e6;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67,97,238,.25);
        }

        .btn-print {
            background: var(--success);
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
        }

        .btn-print:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .condition-badge {
            font-size: 0.85rem;
            padding: 0.4em 0.8em;
            border-radius: 50px;
        }

        @media print {
            body { background: white; }
            .no-print, .card-header .btn { display: none !important; }
            .card { box-shadow: none; border: 1px solid #ddd; }
            .page-header { border-left: none; margin-bottom: 0; page-break-after: always; }
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
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h1 class="page-title mb-1">
                                <i class="fas fa-notes-medical me-2"></i> Vehicle Health Check Report
                            </h1>
                            <p class="page-subtitle mb-0">Job #<?= htmlspecialchars($service->job_number) ?> • <?= date('F j, Y') ?></p>
                        </div>
                        <div class="no-print">
                            <a href="job_details.php?id=<?= $jobId ?>" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <button id="printReport" class="btn btn-print text-white">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0 text-white">
                                    <i class="fas fa-car me-2"></i>
                                    <?= htmlspecialchars($vehicle->make . ' ' . $vehicle->model) ?> 
                                    <small class="opacity-75">• <?= htmlspecialchars($vehicle->registration_number) ?></small>
                                </h4>
                            </div>
                            <div class="card-body p-4" id="reportContent">

                                <!-- Customer & Vehicle Summary -->
                                <div class="row mb-5">
                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2">Customer</h5>
                                        <p class="mb-1"><strong><?= htmlspecialchars($customer->name) ?></strong></p>
                                        <p class="mb-1 text-muted">Phone: <?= htmlspecialchars($customer->phone) ?></p>
                                        <p class="mb-0 text-muted">Email: <?= htmlspecialchars($customer->email ?? 'N/A') ?></p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <h5 class="border-bottom pb-2">Vehicle Details</h5>
                                        <p class="mb-1"><strong><?= htmlspecialchars($vehicle->make . ' ' . $vehicle->model) ?></strong></p>
                                        <p class="mb-1 text-muted">Reg: <?= htmlspecialchars($vehicle->registration_number) ?></p>
                                        <p class="mb-0 text-muted">Year: <?= $vehicle->year ?? 'N/A' ?> • Mileage: <?= number_format($service->mileage ?? 0) ?> km</p>
                                    </div>
                                </div>

                                <form id="healthCheckForm" data-report-exists="<?= $reportExists ? 'true' : 'false' ?>">
                                    <input type="hidden" name="job_id" value="<?= $jobId ?>">

                                    <h5 class="border-bottom pb-3 mb-4">Vehicle Health Inspection</h5>

                                    <div class="row g-4">
                                        <!-- Tyre Condition -->
                                        <div class="col-md-6">
                                            <label class="form-label">Tyre Condition *</label>
                                            <select class="form-select" name="tyre_condition" required>
                                                <option value="">Choose condition...</option>
                                                <option value="excellent" <?= $reportExists && $report->tyre_condition==='excellent'?'selected':'' ?>>Excellent</option>
                                                <option value="good" <?= $reportExists && $report->tyre_condition==='good'?'selected':'' ?>>Good</option>
                                                <option value="fair" <?= $reportExists && $report->tyre_condition==='fair'?'selected':'' ?>>Fair</option>
                                                <option value="poor" <?= $reportExists && $report->tyre_condition==='poor'?'selected':'' ?>>Poor – Replace Soon</option>
                                                <option value="bad" <?= $reportExists && $report->tyre_condition==='bad'?'selected':'' ?>>Bad – Replace Now</option>
                                            </select>
                                        </div>

                                        <!-- Brake Condition -->
                                        <div class="col-md-6">
                                            <label class="form-label">Brake Condition *</label>
                                            <select class="form-select" name="brake_condition" required>
                                                <option value="">Choose condition...</option>
                                                <option value="excellent" <?= $reportExists && $report->brake_condition==='excellent'?'selected':'' ?>>Excellent</option>
                                                <option value="good" <?= $reportExists && $report->brake_condition==='good'?'selected':'' ?>>Good</option>
                                                <option value="fair" <?= $reportExists && $report->brake_condition==='fair'?'selected':'' ?>>Fair</option>
                                                <option value="poor" <?= $reportExists && $report->brake_condition==='poor'?'selected':'' ?>>Poor – Needs Attention</option>
                                            </select>
                                        </div>

                                        <!-- Oil Level -->
                                        <div class="col-md-6">
                                            <label class="form-label">Oil Level *</label>
                                            <select class="form-select" name="oil_level" required>
                                                <option value="">Choose level...</option>
                                                <option value="full" <?= $reportExists && $report->oil_level==='full'?'selected':'' ?>>Full</option>
                                                <option value="low" <?= $reportExists && $report->oil_level==='low'?'selected':'' ?>>Low</option>
                                                <option value="very_low" <?= $reportExists && $report->oil_level==='very_low'?'selected':'' ?>>Very Low</option>
                                                <option value="overfilled" <?= $reportExists && $report->oil_level==='overfilled'?'selected':'' ?>>Overfilled</option>
                                            </select>
                                        </div>

                                        <!-- Air Filter -->
                                        <div class="col-md-6">
                                            <label class="form-label">Air Filter Status *</label>
                                            <select class="form-select" name="filter_status" required>
                                                <option value="">Choose status...</option>
                                                <option value="clean" <?= $reportExists && $report->filter_status==='clean'?'selected':'' ?>>Clean</option>
                                                <option value="slightly_dirty" <?= $reportExists && $report->filter_status==='slightly_dirty'?'selected':'' ?>>Slightly Dirty</option>
                                                <option value="dirty" <?= $reportExists && $report->filter_status==='dirty'?'selected':'' ?>>Dirty – Replace</option>
                                                <option value="very_dirty" <?= $reportExists && $report->filter_status==='very_dirty'?'selected':'' ?>>Very Dirty – Urgent</option>
                                            </select>
                                        </div>

                                        <!-- Battery Health -->
                                        <div class="col-md-6">
                                            <label class="form-label">Battery Health *</label>
                                            <select class="form-select" name="battery_health" required>
                                                <option value="">Choose health...</option>
                                                <option value="excellent" <?= $reportExists && $report->battery_health==='excellent'?'selected':'' ?>>Excellent</option>
                                                <option value="good" <?= $reportExists && $report->battery_health==='good'?'selected':'' ?>>Good</option>
                                                <option value="fair" <?= $reportExists && $report->battery_health==='fair'?'selected':'' ?>>Fair</option>
                                                <option value="poor" <?= $reportExists && $report->battery_health==='poor'?'selected':'' ?>>Poor – Replace Soon</option>
                                            </select>
                                        </div>

                                        <!-- Additional Notes -->
                                        <div class="col-12">
                                            <label class="form-label">Additional Notes & Recommendations</label>
                                            <textarea class="form-control" name="additional_notes" rows="4" placeholder="Any observations, recommendations, or customer instructions..."><?= $reportExists ? htmlspecialchars($report->additional_notes) : '' ?></textarea>
                                        </div>
                                    </div>

                                    <div class="text-end mt-4 no-print">
                                        <button type="submit" class="btn btn-primary px-5">
                                            <i class="fas fa-save me-2"></i>
                                            <?= $reportExists ? 'Update' : 'Save' ?> Report
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/main-js.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="../../Ajax/js/health_check_report.js"></script>


</body>
</html>