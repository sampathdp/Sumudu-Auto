<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get session company_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$response = ['status' => 'error', 'message' => 'Invalid request'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
        case 'update':
            $required = ['job_id', 'tyre_condition', 'brake_condition', 'oil_level', 'filter_status', 'battery_health'];
            foreach ($required as $field) {
                if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    echo json_encode(['status' => 'error', 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                    exit;
                }
            }

            $jobId = (int)$_POST['job_id'];
            
            // Security: verify job belongs to this company
            $service = new Service($jobId);
            if (!$service->id || $service->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Job not found']);
                exit;
            }

            $report = new HealthCheckReport();
            $report->company_id = $sessionCompanyId;
            $report->job_id           = $jobId;
            $report->tyre_condition   = $_POST['tyre_condition'];
            $report->brake_condition  = $_POST['brake_condition'];
            $report->oil_level        = $_POST['oil_level'];
            $report->filter_status    = $_POST['filter_status'];
            $report->battery_health   = $_POST['battery_health'];
            $report->additional_notes = trim($_POST['additional_notes'] ?? '') ?: null;

            // Check if report already exists for this job_id
            $existingReport = new HealthCheckReport();
            if ($existingReport->loadByJobId($report->job_id, $sessionCompanyId)) {
                // Reuse existing ID for update
                $report->id = $existingReport->id;
            }

            if ($report->save()) {
                $response = [
                    'status'  => 'success',
                    'message' => 'Health check report ' . ($report->id && $existingReport->id ? 'updated' : 'created') . ' successfully',
                    'data'    => ['id' => $report->id]
                ];
            } else {
                $response['message'] = 'Failed to save report';
            }
            break;

        case 'get':
            if (empty($_GET['job_id'])) {
                $response['message'] = 'Job ID is required';
                break;
            }

            $jobId = (int)$_GET['job_id'];
            
            // Security: verify job belongs to this company
            $service = new Service($jobId);
            if (!$service->id || $service->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Job not found']);
                exit;
            }

            $report = new HealthCheckReport();
            if ($report->loadByJobId($jobId, $sessionCompanyId)) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                        'id'               => $report->id,
                        'company_id'       => $report->company_id,
                        'job_id'           => $report->job_id,
                        'tyre_condition'   => $report->tyre_condition,
                        'brake_condition'  => $report->brake_condition,
                        'oil_level'        => $report->oil_level,
                        'filter_status'    => $report->filter_status,
                        'battery_health'   => $report->battery_health,
                        'additional_notes' => $report->additional_notes,
                        'created_at'       => $report->created_at,
                        'updated_at'       => $report->updated_at
                    ]
                ];
            } else {
                $response = ['status' => 'success', 'data' => null]; // No report yet
            }
            break;

        case 'list':
            $report = new HealthCheckReport();
            $reports = $report->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $reports];
            break;

        case 'delete':
            if (empty($_POST['id'])) {
                $response['message'] = 'Report ID is required';
                break;
            }

            $report = new HealthCheckReport((int)$_POST['id']);
            
            // Security: ensure report belongs to same company
            if (!$report->id || $report->company_id != $sessionCompanyId) {
                $response['message'] = 'Report not found';
                break;
            }
            
            if ($report->delete()) {
                $response = ['status' => 'success', 'message' => 'Report deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete report';
            }
            break;

        default:
            $response['message'] = 'Unknown action';
            break;
    }
} catch (Exception $e) {
    error_log('HealthCheckReport AJAX Error: ' . $e->getMessage());
    $response['message'] = 'An unexpected error occurred';
}

echo json_encode($response);
exit;