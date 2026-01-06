<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

// Get session company_id (default to 1 for backward compatibility)
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_customers':
            $customer = new Customer();
            $customers = $customer->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $customers];
            break;

        case 'create':
            $vehicle = new Vehicle();
            $vehicle->company_id = $sessionCompanyId;
            $vehicle->customer_id = (int) ($_POST['customer_id'] ?? 0);
            $vehicle->registration_number = strtoupper(trim($_POST['registration_number'] ?? ''));
            $vehicle->make = trim($_POST['make'] ?? '');
            $vehicle->model = trim($_POST['model'] ?? '');
            $vehicle->year = (int) ($_POST['year'] ?? 0) ?: null;
            $vehicle->color = trim($_POST['color'] ?? '') ?: null;
            $vehicle->current_mileage = (int) ($_POST['current_mileage'] ?? 0) ?: null;
            $vehicle->last_service_date = trim($_POST['last_service_date'] ?? '') ?: null;
            $vehicle->last_oil_change_date = trim($_POST['last_oil_change_date'] ?? '') ?: null;

            if ($vehicle->create()) {
                $response = ['status' => 'success', 'message' => 'Vehicle created successfully', 'id' => $vehicle->id];
            } else {
                $response['message'] = 'Failed to create vehicle. Registration number may already exist.';
            }
            break;

        case 'update':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid vehicle ID';
                break;
            }
            
            $vehicle = new Vehicle($id);
            
            // Security: ensure vehicle belongs to same company
            if (!$vehicle->id || $vehicle->company_id != $sessionCompanyId) {
                $response['message'] = 'Vehicle not found';
                break;
            }
            
            $vehicle->customer_id = (int) ($_POST['customer_id'] ?? $vehicle->customer_id);
            $vehicle->registration_number = strtoupper(trim($_POST['registration_number'] ?? $vehicle->registration_number));
            $vehicle->make = trim($_POST['make'] ?? $vehicle->make);
            $vehicle->model = trim($_POST['model'] ?? $vehicle->model);
            $vehicle->year = (int) ($_POST['year'] ?? $vehicle->year) ?: null;
            $vehicle->color = trim($_POST['color'] ?? '') ?: null;
            $vehicle->current_mileage = (int) ($_POST['current_mileage'] ?? 0) ?: null;
            $vehicle->last_service_date = trim($_POST['last_service_date'] ?? '') ?: null;
            $vehicle->last_oil_change_date = trim($_POST['last_oil_change_date'] ?? '') ?: null;

            if ($vehicle->update()) {
                $response = ['status' => 'success', 'message' => 'Vehicle updated successfully'];
            } else {
                $response['message'] = 'Failed to update vehicle. Registration number may already exist.';
            }
            break;

        case 'get':
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid vehicle ID';
                break;
            }
            
            $vehicle = new Vehicle($id);
            
            // Security: ensure vehicle belongs to same company
            if ($vehicle->id && $vehicle->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $vehicle->id,
                        'company_id' => $vehicle->company_id,
                        'customer_id' => $vehicle->customer_id,
                        'registration_number' => $vehicle->registration_number,
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                        'year' => $vehicle->year,
                        'color' => $vehicle->color,
                        'current_mileage' => $vehicle->current_mileage,
                        'last_service_date' => $vehicle->last_service_date,
                        'last_oil_change_date' => $vehicle->last_oil_change_date,
                        'customer_name' => $vehicle->customer_name,
                        'created_at' => $vehicle->created_at
                    ]
                ];
            } else {
                $response['message'] = 'Vehicle not found';
            }
            break;

        case 'get_by_registration':
            $regNumber = trim($_GET['registration_number'] ?? '');
            if (empty($regNumber)) {
                $response['message'] = 'Registration number required';
                break;
            }
            $vehicle = new Vehicle();
            $data = $vehicle->getByRegistration($regNumber, $sessionCompanyId);
            if ($data) {
                $response = ['status' => 'success', 'data' => $data];
            } else {
                $response['message'] = 'Vehicle not found';
            }
            break;

        case 'get_by_customer':
            $customerId = (int) ($_GET['customer_id'] ?? 0);
            if (!$customerId) {
                $response['message'] = 'Customer ID required';
                break;
            }
            $vehicle = new Vehicle();
            $vehicles = $vehicle->getByCustomer($customerId, $sessionCompanyId);
            $response = ['status' => 'success', 'data' => $vehicles];
            break;

        case 'delete':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid vehicle ID';
                break;
            }
            
            $vehicle = new Vehicle($id);
            
            // Security: ensure vehicle belongs to same company
            if (!$vehicle->id || $vehicle->company_id != $sessionCompanyId) {
                $response['message'] = 'Vehicle not found';
                break;
            }
            
            if ($vehicle->delete()) {
                $response = ['status' => 'success', 'message' => 'Vehicle deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete vehicle.';
            }
            break;

        case 'list':
        default:
            $vehicle = new Vehicle();
            $vehicles = $vehicle->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $vehicles];
            break;
    }
} catch (Exception $e) {
    error_log("Vehicle AJAX Error: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
