<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid request'];

try {
    $job = new ScheduleJob();

    switch ($action) {

        case 'create':
            $job->qr_code             = trim($_POST['qr_code'] ?? '');
            $job->qr_link             = trim($_POST['qr_link'] ?? '');
            $job->whatsapp_number     = trim($_POST['whatsapp_number'] ?? '');
            $job->status              = trim($_POST['status'] ?? 'pending');
            $job->scheduled_date_time = $_POST['scheduled_date_time'] ?? null;
            $job->customer_id         = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
            $job->customer_note       = trim($_POST['customer_note'] ?? '');

            if (empty($job->qr_code) || empty($job->qr_link)) {
                $response['message'] = 'QR Code and Link are required';
                break;
            }

            if ($job->create()) {
                $response = ['status' => 'success', 'message' => 'Job created successfully', 'id' => $job->id];
            } else {
                $response['message'] = 'Failed to create job. QR code may already exist.';
            }
            break;


        case 'update':
            $job->id = (int)($_POST['id'] ?? 0);
            if (!$job->id) {
                $response['message'] = 'Invalid job ID';
                break;
            }

            // Reload current data first
            $job->loadById($job->id);

            $job->qr_code             = trim($_POST['qr_code'] ?? $job->qr_code);
            $job->qr_link             = trim($_POST['qr_link'] ?? $job->qr_link);
            $job->whatsapp_number     = trim($_POST['whatsapp_number'] ?? $job->whatsapp_number);
            $job->status              = trim($_POST['status'] ?? $job->status);
            $job->scheduled_date_time = $_POST['scheduled_date_time'] ?? $job->scheduled_date_time;
            $job->customer_id         = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : $job->customer_id;
            $job->customer_note       = trim($_POST['customer_note'] ?? $job->customer_note);

            if ($job->update()) {
                $response = ['status' => 'success', 'message' => 'Job updated successfully'];
            } else {
                $response['message'] = 'Failed to update job';
            }
            break;


        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Job ID required';
                break;
            }

            $job = new ScheduleJob($id);
            if ($job->id) {
                $response = ['status' => 'success', 'data' => (array)$job];
            } else {
                $response['message'] = 'Job not found';
            }
            break;


        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid job ID';
                break;
            }

            $job = new ScheduleJob($id);
            $response = $job->delete()
                ? ['status' => 'success', 'message' => 'Job deleted successfully']
                : ['status' => 'error', 'message' => 'Failed to delete job'];
            break;


        case 'find_by_qr':
            $qr = trim($_GET['qr_code'] ?? '');
            if (empty($qr)) {
                $response['message'] = 'QR code is required';
                break;
            }

            if ($job->findByQR($qr)) {
                $response = ['status' => 'success', 'data' => (array)$job];
            } else {
                $response = ['status' => 'error', 'message' => 'Invalid or expired QR code'];
            }
            break;


        case 'update_status':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? '');

            if (!$id || empty($status)) {
                $response['message'] = 'Invalid data';
                break;
            }

            $job = new ScheduleJob($id);
            if ($job->updateStatus($status)) {
                $response = ['status' => 'success', 'message' => 'Status updated successfully'];
            } else {
                $response['message'] = 'Failed to update status';
            }
            break;


        case 'list':
        default:
            $jobs = $job->all();
            $response = ['status' => 'success', 'data' => $jobs];
            break;
    }

} catch (Exception $e) {
    error_log("ScheduleJob AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred';
}

echo json_encode($response);
exit;