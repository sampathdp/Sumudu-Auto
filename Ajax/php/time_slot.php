<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

// Get session company_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $timeSlot = new TimeSlot();
            $timeSlot->company_id = $sessionCompanyId;
            $timeSlot->slot_start   = trim($_POST['slot_start'] ?? '');
            $timeSlot->slot_end     = trim($_POST['slot_end'] ?? '');
            $timeSlot->max_bookings = (int)($_POST['max_bookings'] ?? 3);
            $timeSlot->is_active    = (int)($_POST['is_active'] ?? 1);

            if (strtotime($timeSlot->slot_start) >= strtotime($timeSlot->slot_end)) {
                $response['message'] = 'Start time must be earlier than end time.';
                break;
            }

            if ($timeSlot->create()) {
                $response = ['status' => 'success', 'message' => 'Time slot created successfully', 'id' => $timeSlot->id];
            } else {
                $response['message'] = 'Failed to create time slot. A slot with these times may already exist.';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid time slot ID';
                break;
            }
            $timeSlot = new TimeSlot($id);
            
            // Security: ensure time slot belongs to same company
            if (!$timeSlot->id || $timeSlot->company_id != $sessionCompanyId) {
                $response['message'] = 'Time slot not found';
                break;
            }

            $timeSlot->slot_start   = trim($_POST['slot_start'] ?? $timeSlot->slot_start);
            $timeSlot->slot_end     = trim($_POST['slot_end'] ?? $timeSlot->slot_end);
            $timeSlot->max_bookings = (int)($_POST['max_bookings'] ?? $timeSlot->max_bookings);
            $timeSlot->is_active    = (int)($_POST['is_active'] ?? $timeSlot->is_active);

            if (strtotime($timeSlot->slot_start) >= strtotime($timeSlot->slot_end)) {
                $response['message'] = 'Start time must be earlier than end time.';
                break;
            }

            if ($timeSlot->update()) {
                $response = ['status' => 'success', 'message' => 'Time slot updated successfully'];
            } else {
                $response['message'] = 'Failed to update time slot. A slot with these times may already exist.';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid time slot ID';
                break;
            }
            $timeSlot = new TimeSlot($id);
            
            // Security: ensure time slot belongs to same company
            if ($timeSlot->id && $timeSlot->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                        'id'           => $timeSlot->id,
                        'company_id'   => $timeSlot->company_id,
                        'slot_start'   => $timeSlot->slot_start,
                        'slot_end'     => $timeSlot->slot_end,
                        'max_bookings' => $timeSlot->max_bookings,
                        'is_active'    => $timeSlot->is_active
                    ]
                ];
            } else {
                $response['message'] = 'Time slot not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid time slot ID';
                break;
            }
            $timeSlot = new TimeSlot($id);
            
            // Security: ensure time slot belongs to same company
            if (!$timeSlot->id || $timeSlot->company_id != $sessionCompanyId) {
                $response['message'] = 'Time slot not found';
                break;
            }
            
            if ($timeSlot->delete()) {
                $response = ['status' => 'success', 'message' => 'Time slot deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete time slot.';
            }
            break;

        case 'get_active':
            $timeSlot = new TimeSlot();
            $slots = $timeSlot->active($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $slots];
            break;

        case 'list':
        default:
            $timeSlot = new TimeSlot();
            $slots = $timeSlot->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $slots];
            break;
    }
} catch (Exception $e) {
    error_log("TimeSlot AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
