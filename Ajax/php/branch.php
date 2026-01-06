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
            $branch = new Branch();
            $branch->company_id = (int)($_POST['company_id'] ?? 0);
            $branch->branch_code = strtoupper(trim($_POST['branch_code'] ?? ''));
            $branch->branch_name = trim($_POST['branch_name'] ?? '');
            $branch->address = trim($_POST['address'] ?? '') ?: null;
            $branch->phone = trim($_POST['phone'] ?? '') ?: null;
            $branch->email = trim($_POST['email'] ?? '') ?: null;
            $branch->manager_id = (int)($_POST['manager_id'] ?? 0) ?: null;
            $branch->is_main = (int)($_POST['is_main'] ?? 0);
            $branch->is_active = (int)($_POST['is_active'] ?? 1);

            if (!$branch->company_id) {
                $response['message'] = 'Please select a company';
                break;
            }

            if ($branch->create()) {
                $response = ['status' => 'success', 'message' => 'Branch created successfully', 'id' => $branch->id];
            } else {
                $response['message'] = 'Failed to create branch. Code may exist or company limit reached.';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid branch ID';
                break;
            }
            $branch = new Branch($id);
            if (!$branch->id) {
                $response['message'] = 'Branch not found';
                break;
            }

            $branch->branch_code = strtoupper(trim($_POST['branch_code'] ?? $branch->branch_code));
            $branch->branch_name = trim($_POST['branch_name'] ?? $branch->branch_name);
            $branch->address = trim($_POST['address'] ?? '') ?: null;
            $branch->phone = trim($_POST['phone'] ?? '') ?: null;
            $branch->email = trim($_POST['email'] ?? '') ?: null;
            $branch->manager_id = (int)($_POST['manager_id'] ?? 0) ?: null;
            $branch->is_main = (int)($_POST['is_main'] ?? $branch->is_main);
            $branch->is_active = (int)($_POST['is_active'] ?? $branch->is_active);

            if ($branch->update()) {
                // If set as main, update accordingly
                if ($branch->is_main) {
                    $branch->setAsMain();
                }
                $response = ['status' => 'success', 'message' => 'Branch updated successfully'];
            } else {
                $response['message'] = 'Failed to update branch. Branch code may already exist.';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid branch ID';
                break;
            }
            $branch = new Branch($id);
            if ($branch->id) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $branch->id,
                        'company_id' => $branch->company_id,
                        'branch_code' => $branch->branch_code,
                        'branch_name' => $branch->branch_name,
                        'address' => $branch->address ?? '',
                        'phone' => $branch->phone ?? '',
                        'email' => $branch->email ?? '',
                        'manager_id' => $branch->manager_id,
                        'is_main' => $branch->is_main,
                        'is_active' => $branch->is_active,
                        'user_count' => $branch->getUserCount(),
                        'employee_count' => $branch->getEmployeeCount()
                    ]
                ];
            } else {
                $response['message'] = 'Branch not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid branch ID';
                break;
            }
            $branch = new Branch($id);
            
            if ($branch->is_main) {
                $response['message'] = 'Cannot delete main branch. Set another branch as main first.';
                break;
            }
            
            if ($branch->getUserCount() > 0) {
                $response['message'] = 'Cannot delete branch with users. Reassign users first.';
                break;
            }
            
            if ($branch->delete()) {
                $response = ['status' => 'success', 'message' => 'Branch deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete branch.';
            }
            break;

        case 'set_main':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid branch ID';
                break;
            }
            $branch = new Branch($id);
            if ($branch->setAsMain()) {
                $response = ['status' => 'success', 'message' => 'Branch set as main successfully'];
            } else {
                $response['message'] = 'Failed to set as main branch.';
            }
            break;

        case 'get_by_company':
            $companyId = (int)($_GET['company_id'] ?? 0);
            $branch = new Branch();
            $branches = $branch->getActiveByCompany($companyId);
            $response = ['status' => 'success', 'data' => $branches];
            break;

        case 'get_stats':
            $stats = Branch::getStats($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $stats];
            break;

        case 'list':
        default:
            $branch = new Branch();
            $branches = $branch->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $branches];
            break;
    }
} catch (Exception $e) {
    error_log("Branch AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
