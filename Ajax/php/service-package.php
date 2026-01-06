<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

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
            $package = new ServicePackage();
            $package->company_id = $sessionCompanyId;
            $package->package_name       = trim($_POST['package_name'] ?? '');
            $package->description        = trim($_POST['description'] ?? '') ?: null;
            $package->base_price         = (float)($_POST['base_price'] ?? 0);
            $package->estimated_duration = (int)($_POST['estimated_duration'] ?? 0);
            $package->is_active          = isset($_POST['is_active']) ? 1 : 0;

            if (empty($package->package_name)) {
                $response['message'] = 'Package name is required';
                break;
            }

            if ($package->create()) {
                $response = ['status' => 'success', 'message' => 'Package created successfully', 'id' => $package->id];
            } else {
                $response['message'] = 'Failed to create package. Name may already exist.';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid package ID';
                break;
            }

            $package = new ServicePackage($id);
            
            // Security: ensure package belongs to same company
            if (!$package->id || $package->company_id != $sessionCompanyId) {
                $response['message'] = 'Package not found';
                break;
            }

            $package->package_name       = trim($_POST['package_name'] ?? $package->package_name);
            $package->description        = trim($_POST['description'] ?? '') ?: null;
            $package->base_price         = (float)($_POST['base_price'] ?? $package->base_price);
            $package->estimated_duration = (int)($_POST['estimated_duration'] ?? $package->estimated_duration);
            $package->is_active          = isset($_POST['is_active']) ? 1 : 0;

            if ($package->update()) {
                $response = ['status' => 'success', 'message' => 'Package updated successfully'];
            } else {
                $response['message'] = 'Failed to update package. Name may already exist.';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Package ID required';
                break;
            }

            $package = new ServicePackage($id);
            
            // Security: ensure package belongs to same company
            if ($package->id && $package->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                        'id'                => $package->id,
                        'company_id'        => $package->company_id,
                        'package_name'      => $package->package_name,
                        'description'       => $package->description,
                        'base_price'        => number_format($package->base_price, 2),
                        'estimated_duration'=> $package->estimated_duration,
                        'is_active'         => $package->is_active,
                        'created_at'        => $package->created_at
                    ]
                ];
            } else {
                $response['message'] = 'Package not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid package ID';
                break;
            }

            $package = new ServicePackage($id);
            
            // Security: ensure package belongs to same company
            if (!$package->id || $package->company_id != $sessionCompanyId) {
                $response['message'] = 'Package not found';
                break;
            }
            
            if ($package->delete()) {
                $response = ['status' => 'success', 'message' => 'Package deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete package';
            }
            break;

        case 'get_active':
            $package = new ServicePackage();
            $packages = $package->active($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $packages];
            break;

        case 'list':
        default:
            $package = new ServicePackage();
            $packages = $package->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $packages];
            break;
    }
} catch (Exception $e) {
    error_log("ServicePackage AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please try again.';
}

echo json_encode($response);
exit;