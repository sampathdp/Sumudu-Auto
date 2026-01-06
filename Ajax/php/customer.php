<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

// Get session company_id (default to 1 for backward compatibility)
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $customer = new Customer();
            $customer->company_id = $sessionCompanyId;
            $customer->name    = trim($_POST['name'] ?? '');
            $customer->phone   = trim($_POST['phone'] ?? '');
            $customer->email   = trim($_POST['email'] ?? '') ?: null;
            $customer->address = trim($_POST['address'] ?? '') ?: null;

            if ($customer->create()) {
                $response = ['status' => 'success', 'message' => 'Customer created successfully', 'id' => $customer->id];
            } else {
                $response['message'] = 'Failed to create customer. Phone number may already exist.';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid customer ID';
                break;
            }
            $customer = new Customer($id);
            
            // Security: ensure customer belongs to same company
            if (!$customer->id || $customer->company_id != $sessionCompanyId) {
                $response['message'] = 'Customer not found';
                break;
            }

            $customer->name    = trim($_POST['name'] ?? $customer->name);
            $customer->phone   = trim($_POST['phone'] ?? $customer->phone);
            $customer->email   = trim($_POST['email'] ?? '') ?: null;
            $customer->address = trim($_POST['address'] ?? '') ?: null;

            if ($customer->update()) {
                $response = ['status' => 'success', 'message' => 'Customer updated successfully'];
            } else {
                $response['message'] = 'Failed to update customer. Phone number may already exist.';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid customer ID';
                break;
            }
            $customer = new Customer($id);
            
            // Security: ensure customer belongs to same company
            if ($customer->id && $customer->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                        'id'         => $customer->id,
                        'company_id' => $customer->company_id,
                        'name'       => $customer->name,
                        'phone'      => $customer->phone,
                        'email'      => $customer->email ?? '',
                        'address'    => $customer->address ?? '',
                        'total_visits' => $customer->total_visits,
                        'last_visit_date' => $customer->last_visit_date
                    ]
                ];
            } else {
                $response['message'] = 'Customer not found';
            }
            break;

        case 'get_by_phone':
            $phone = trim($_GET['phone'] ?? '');
            if (empty($phone)) {
                $response['message'] = 'Phone number required';
                break;
            }
            $customer = new Customer();
            $data = $customer->getByPhone($phone, $sessionCompanyId);
            if ($data) {
                $response = ['status' => 'success', 'data' => $data];
            } else {
                $response['message'] = 'Customer not found';
            }
            break;

        case 'search':
            $term = trim($_GET['term'] ?? '');
            if (empty($term)) {
                $response = ['status' => 'success', 'data' => []];
                break;
            }
            $customer = new Customer();
            $results = $customer->search($term, $sessionCompanyId);
            $response = ['status' => 'success', 'data' => $results];
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid customer ID';
                break;
            }
            $customer = new Customer($id);
            
            // Security: ensure customer belongs to same company
            if (!$customer->id || $customer->company_id != $sessionCompanyId) {
                $response['message'] = 'Customer not found';
                break;
            }
            
            if ($customer->delete()) {
                $response = ['status' => 'success', 'message' => 'Customer deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete customer.';
            }
            break;

        case 'list':
        default:
            $customer = new Customer();
            $customers = $customer->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $customers];
            break;
    }
} catch (Exception $e) {
    error_log("Customer AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please try again.';
}

echo json_encode($response);
exit;