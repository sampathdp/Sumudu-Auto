<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $company = new Company();
            $company->company_code = strtolower(trim($_POST['company_code'] ?? ''));
            $company->name = trim($_POST['name'] ?? '');
            $company->package_type = trim($_POST['package_type'] ?? 'starter');
            $company->status = trim($_POST['status'] ?? 'trial');
            $company->max_users = (int)($_POST['max_users'] ?? 5);
            $company->max_employees = (int)($_POST['max_employees'] ?? 10);
            $company->max_branches = (int)($_POST['max_branches'] ?? 1);
            
            // Validate admin credentials
            $adminUsername = trim($_POST['admin_username'] ?? '');
            $adminPassword = $_POST['admin_password'] ?? '';
            
            if (empty($adminUsername) || empty($adminPassword) || strlen($adminPassword) < 8) {
                $response['message'] = 'Admin username and password (min 8 chars) are required';
                break;
            }

            if ($company->create()) {
                // Create initial admin user
                try {
                    $user = new User();
                    $user->company_id = $company->id;
                    $user->username = $adminUsername;
                    $user->password = $adminPassword;
                    $user->role_id = 2; // Admin role
                    $user->is_active = 1;
                    $user->branch_id = null; // Company admin has access to all branches

                    if ($user->create()) {
                        // Initialize default sidebar modules for new company
                        $sidebarModule = new SidebarModule();
                        $sidebarModule->initializeForCompany($company->id);

                        // Initialize default service stages
                        $serviceStage = new ServiceStage();
                        $serviceStage->initializeDefaults($company->id);

                        $response = ['status' => 'success', 'message' => 'Company and Admin User created successfully', 'id' => $company->id];
                    } else {
                        // Rollback: Delete company if user creation fails
                        $company->delete();
                        $response['message'] = 'Failed to create Admin User. Username may already exist. Company creation rolled back.';
                    }
                } catch (Exception $e) {
                    $company->delete();
                    $response['message'] = 'Error creating Admin User: ' . $e->getMessage();
                }
            } else {
                $response['message'] = 'Failed to create company. Company code may already exist.';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid company ID';
                break;
            }
            $company = new Company($id);
            if (!$company->id) {
                $response['message'] = 'Company not found';
                break;
            }

            $company->company_code = strtolower(trim($_POST['company_code'] ?? $company->company_code));
            $company->name = trim($_POST['name'] ?? $company->name);
            $company->package_type = trim($_POST['package_type'] ?? $company->package_type);
            $company->status = trim($_POST['status'] ?? $company->status);
            $company->max_users = (int)($_POST['max_users'] ?? $company->max_users);
            $company->max_employees = (int)($_POST['max_employees'] ?? $company->max_employees);
            $company->max_branches = (int)($_POST['max_branches'] ?? $company->max_branches);

            if ($company->update()) {
                $response = ['status' => 'success', 'message' => 'Company updated successfully'];
            } else {
                $response['message'] = 'Failed to update company. Company code may already exist.';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid company ID';
                break;
            }
            $company = new Company($id);
            if ($company->id) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $company->id,
                        'company_code' => $company->company_code,
                        'name' => $company->name,
                        'package_type' => $company->package_type,
                        'settings_json' => $company->settings_json,
                        'status' => $company->status,
                        'trial_ends_at' => $company->trial_ends_at,
                        'max_users' => $company->max_users,
                        'max_employees' => $company->max_employees,
                        'max_branches' => $company->max_branches,
                        'user_count' => $company->getUserCount(),
                        'branch_count' => $company->getBranchCount()
                    ]
                ];
            } else {
                $response['message'] = 'Company not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid company ID';
                break;
            }
            $company = new Company($id);
            
            // Check if has users
            if ($company->getUserCount() > 0) {
                $response['message'] = 'Cannot delete company with existing users. Remove all users first.';
                break;
            }
            
            if ($company->delete()) {
                $response = ['status' => 'success', 'message' => 'Company deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete company.';
            }
            break;

        case 'update_status':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            
            if (!$id || !in_array($status, ['active', 'suspended', 'cancelled', 'trial'])) {
                $response['message'] = 'Invalid parameters';
                break;
            }
            
            $company = new Company($id);
            if (!$company->id) {
                $response['message'] = 'Company not found';
                break;
            }
            
            $company->status = $status;
            if ($company->update()) {
                $response = ['status' => 'success', 'message' => 'Company status updated successfully'];
            } else {
                $response['message'] = 'Failed to update status.';
            }
            break;

        case 'get_stats':
            $stats = Company::getStats();
            $response = ['status' => 'success', 'data' => $stats];
            break;

        case 'get_package_limits':
            $packageType = trim($_GET['package_type'] ?? 'starter');
            $limits = Company::getPackageLimits($packageType);
            $response = ['status' => 'success', 'data' => $limits];
            break;

        case 'list':
        default:
            $company = new Company();
            $companies = $company->all();
            $response = ['status' => 'success', 'data' => $companies];
            break;
    }
} catch (Exception $e) {
    error_log("Company AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
