<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

// Auth check
if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Get session company_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$response = ['status' => 'error', 'message' => 'Invalid request'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'create':
            $stage = new ServiceStage();
            $stage->company_id = $sessionCompanyId;
            $stage->stage_name        = trim($_POST['stage_name'] ?? '');
            $stage->stage_order       = (int)($_POST['stage_order'] ?? ServiceStage::getNextOrder($sessionCompanyId));
            $stage->icon              = trim($_POST['icon'] ?? '') ?: null;
            $stage->estimated_duration = (int)($_POST['estimated_duration'] ?? 0) ?: null;

            if (empty($stage->stage_name)) {
                $response['message'] = 'Stage name is required';
                break;
            }

            if ($stage->create()) {
                $response = [
                    'status'  => 'success',
                    'message' => 'Service stage created successfully',
                    'data'    => [
                        'id' => $stage->id
                    ]
                ];
            } else {
                $response['message'] = 'Failed to create stage. Name may already exist.';
            }
            break;


        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid stage ID';
                break;
            }

            $stage = new ServiceStage($id);
            
            // Security: ensure stage belongs to same company
            if (!$stage->id || $stage->company_id != $sessionCompanyId) {
                $response['message'] = 'Stage not found';
                break;
            }

            $stage->stage_name        = trim($_POST['stage_name'] ?? $stage->stage_name);
            $stage->stage_order       = (int)($_POST['stage_order'] ?? $stage->stage_order);
            $stage->icon              = trim($_POST['icon'] ?? '') ?: null;
            $stage->estimated_duration = (int)($_POST['estimated_duration'] ?? 0) ?: null;

            if (empty($stage->stage_name)) {
                $response['message'] = 'Stage name is required';
                break;
            }

            if ($stage->update()) {
                $response = ['status' => 'success', 'message' => 'Stage updated successfully'];
            } else {
                $response['message'] = 'Failed to update stage. Name may already exist.';
            }
            break;


        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Stage ID required';
                break;
            }

            $stage = new ServiceStage($id);
            
            // Security: ensure stage belongs to same company
            if ($stage->id && $stage->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                        'id'                => $stage->id,
                        'company_id'        => $stage->company_id,
                        'stage_name'        => $stage->stage_name,
                        'stage_order'       => $stage->stage_order,
                        'icon'              => $stage->icon,
                        'estimated_duration'=> $stage->estimated_duration,
                        'created_at'        => $stage->created_at
                    ]
                ];
            } else {
                $response['message'] = 'Stage not found';
            }
            break;


        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid stage ID';
                break;
            }

            $stage = new ServiceStage($id);
            
            // Security: ensure stage belongs to same company
            if (!$stage->id || $stage->company_id != $sessionCompanyId) {
                $response['message'] = 'Stage not found';
                break;
            }
            
            if ($stage->delete()) {
                $response = ['status' => 'success', 'message' => 'Stage deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete stage';
            }
            break;

        case 'get_next_order':
            $nextOrder = ServiceStage::getNextOrder($sessionCompanyId);
            $response = ['status' => 'success', 'data' => ['next_order' => $nextOrder]];
            break;

        case 'list':
        default:
            $stage = new ServiceStage();
            $stages = $stage->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $stages];
            break;
    }

} catch (Exception $e) {
    error_log("ServiceStage AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred';
}

echo json_encode($response);
exit;