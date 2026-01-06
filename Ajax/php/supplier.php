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
            $supplier = new Supplier();
            $supplier->company_id = $sessionCompanyId;
            $supplier->supplier_name   = trim($_POST['supplier_name'] ?? '');
            $supplier->contact_person  = trim($_POST['contact_person'] ?? '') ?: null;
            $supplier->phone          = trim($_POST['phone'] ?? '') ?: null;
            $supplier->email          = trim($_POST['email'] ?? '') ?: null;
            $supplier->address        = trim($_POST['address'] ?? '') ?: null;
            $supplier->tax_id         = trim($_POST['tax_id'] ?? '') ?: null;
            $supplier->payment_terms  = trim($_POST['payment_terms'] ?? '') ?: null;
            $supplier->is_active      = (int)($_POST['is_active'] ?? 1);

            if ($supplier->create()) {
                $response = ['status' => 'success', 'message' => 'Supplier created successfully'];
            } else {
                $response['message'] = 'Failed to create supplier. Supplier name may already exist.';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid supplier ID';
                break;
            }
            $supplier = new Supplier($id);
            
            // Security: ensure supplier belongs to same company
            if (!$supplier->id || $supplier->company_id != $sessionCompanyId) {
                $response['message'] = 'Supplier not found';
                break;
            }

            $supplier->supplier_name   = trim($_POST['supplier_name'] ?? $supplier->supplier_name);
            $supplier->contact_person  = trim($_POST['contact_person'] ?? '') ?: null;
            $supplier->phone          = trim($_POST['phone'] ?? '') ?: null;
            $supplier->email          = trim($_POST['email'] ?? '') ?: null;
            $supplier->address        = trim($_POST['address'] ?? '') ?: null;
            $supplier->tax_id         = trim($_POST['tax_id'] ?? '') ?: null;
            $supplier->payment_terms  = trim($_POST['payment_terms'] ?? '') ?: null;
            $supplier->is_active      = (int)($_POST['is_active'] ?? $supplier->is_active);

            if ($supplier->update()) {
                $response = ['status' => 'success', 'message' => 'Supplier updated successfully'];
            } else {
                $response['message'] = 'Failed to update supplier. Supplier name may already exist.';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid supplier ID';
                break;
            }
            $supplier = new Supplier($id);
            
            // Security: ensure supplier belongs to same company
            if ($supplier->id && $supplier->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                        'id'              => $supplier->id,
                        'company_id'      => $supplier->company_id,
                        'supplier_name'   => $supplier->supplier_name,
                        'contact_person'  => $supplier->contact_person ?? '',
                        'phone'           => $supplier->phone ?? '',
                        'email'           => $supplier->email ?? '',
                        'address'         => $supplier->address ?? '',
                        'tax_id'          => $supplier->tax_id ?? '',
                        'payment_terms'   => $supplier->payment_terms ?? '',
                        'is_active'       => $supplier->is_active
                    ]
                ];
            } else {
                $response['message'] = 'Supplier not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid supplier ID';
                break;
            }
            $supplier = new Supplier($id);
            
            // Security: ensure supplier belongs to same company
            if (!$supplier->id || $supplier->company_id != $sessionCompanyId) {
                $response['message'] = 'Supplier not found';
                break;
            }
            
            if ($supplier->delete()) {
                $response = ['status' => 'success', 'message' => 'Supplier deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete supplier.';
            }
            break;

        case 'list':
        default:
            $supplier = new Supplier();
            $suppliers = $supplier->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $suppliers];
            break;
            
        case 'get_active':
            $supplier = new Supplier();
            $suppliers = $supplier->getActive($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $suppliers];
            break;
            
        case 'get_stats':
            $stats = Supplier::getStats($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $stats];
            break;
    }
} catch (Exception $e) {
    error_log("Supplier AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
