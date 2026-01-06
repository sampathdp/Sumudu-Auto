<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get session company_id and branch_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;
$sessionBranchId = $_SESSION['branch_id'] ?? null;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $employee = new Employee();
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] == '1';
            $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
            $employees = $employee->all($sessionCompanyId, $branchId, $activeOnly);
            echo json_encode(['status' => 'success', 'data' => $employees]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Employee ID required']);
                exit;
            }
            
            $employee = new Employee($id);
            
            // Security: ensure employee belongs to same company
            if ($employee->id && $employee->company_id == $sessionCompanyId) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'branch_id' => $employee->branch_id,
                        'employee_code' => $employee->employee_code,
                        'first_name' => $employee->first_name,
                        'last_name' => $employee->last_name,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'position' => $employee->position,
                        'department' => $employee->department,
                        'hire_date' => $employee->hire_date,
                        'salary' => $employee->salary,
                        'salary_type' => $employee->salary_type,
                        'address' => $employee->address,
                        'emergency_contact' => $employee->emergency_contact,
                        'emergency_phone' => $employee->emergency_phone,
                        'is_active' => $employee->is_active,
                        'user_id' => $employee->user_id,
                        'notes' => $employee->notes
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
            }
            break;

        case 'create':
            if (empty($_POST['first_name']) || empty($_POST['last_name'])) {
                echo json_encode(['status' => 'error', 'message' => 'First name and last name are required']);
                exit;
            }

            $employee = new Employee();
            $employee->company_id = $sessionCompanyId;
            $employee->branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : null;
            $employee->first_name = trim($_POST['first_name']);
            $employee->last_name = trim($_POST['last_name']);
            $employee->email = trim($_POST['email'] ?? '') ?: null;
            $employee->phone = trim($_POST['phone'] ?? '') ?: null;
            $employee->position = trim($_POST['position'] ?? '') ?: null;
            $employee->department = trim($_POST['department'] ?? '') ?: null;
            $employee->hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;
            $employee->salary = (float)($_POST['salary'] ?? 0);
            $employee->salary_type = trim($_POST['salary_type'] ?? 'monthly');
            $employee->address = trim($_POST['address'] ?? '') ?: null;
            $employee->emergency_contact = trim($_POST['emergency_contact'] ?? '') ?: null;
            $employee->emergency_phone = trim($_POST['emergency_phone'] ?? '') ?: null;
            $employee->is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $employee->user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
            $employee->notes = trim($_POST['notes'] ?? '') ?: null;

            if ($employee->create()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Employee created successfully',
                    'data' => ['id' => $employee->id, 'employee_code' => $employee->employee_code]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create employee. Email may already exist.']);
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Employee ID required']);
                exit;
            }

            $employee = new Employee($id);
            
            // Security: ensure employee belongs to same company
            if (!$employee->id || $employee->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
                exit;
            }

            $employee->branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : null;
            $employee->first_name = trim($_POST['first_name']);
            $employee->last_name = trim($_POST['last_name']);
            $employee->email = trim($_POST['email'] ?? '') ?: null;
            $employee->phone = trim($_POST['phone'] ?? '') ?: null;
            $employee->position = trim($_POST['position'] ?? '') ?: null;
            $employee->department = trim($_POST['department'] ?? '') ?: null;
            $employee->hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;
            $employee->salary = (float)($_POST['salary'] ?? 0);
            $employee->salary_type = trim($_POST['salary_type'] ?? 'monthly');
            $employee->address = trim($_POST['address'] ?? '') ?: null;
            $employee->emergency_contact = trim($_POST['emergency_contact'] ?? '') ?: null;
            $employee->emergency_phone = trim($_POST['emergency_phone'] ?? '') ?: null;
            $employee->is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $employee->user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
            $employee->notes = trim($_POST['notes'] ?? '') ?: null;

            if ($employee->update()) {
                echo json_encode(['status' => 'success', 'message' => 'Employee updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update employee']);
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Employee ID required']);
                exit;
            }

            $employee = new Employee($id);
            
            // Security: ensure employee belongs to same company
            if (!$employee->id || $employee->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
                exit;
            }
            
            if ($employee->delete()) {
                echo json_encode(['status' => 'success', 'message' => 'Employee deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete employee']);
            }
            break;

        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Employee ID required']);
                exit;
            }

            $employee = new Employee($id);
            
            // Security: ensure employee belongs to same company
            if (!$employee->id || $employee->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
                exit;
            }
            
            if ($employee->toggleStatus()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Status updated successfully',
                    'data' => ['is_active' => $employee->is_active]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
            }
            break;

        case 'get_branches':
            $branch = new Branch();
            $branches = $branch->getActiveByCompany($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $branches]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Employee AJAX Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
}
?>
