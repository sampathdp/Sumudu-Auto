<?php
// Get the absolute path to classes
$classesPath = dirname(dirname(__DIR__)) . '/classes/Includes.php';

if (!file_exists($classesPath)) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Configuration file not found. Path: ' . $classesPath
    ]));
}

require_once $classesPath;

header('Content-Type: application/json; charset=UTF-8');

// Get session company_id (default to 1 for backward compatibility)
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

// Handle logout
if ((isset($_POST['action']) && $_POST['action'] === 'logout') || (isset($_GET['action']) && $_GET['action'] === 'logout')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    // Check if it's an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode([
            'status' => 'success',
            'message' => 'You have been logged out successfully.',
            'redirect' => BASE_URL . 'views/auth/login.php'
        ]);
    } else {
        header('Location: ' . BASE_URL . 'views/auth/login.php');
    }
    exit();
}

// Handle AUTH login
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // Authenticate
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // $companyId = isset($_POST['company_id']) ? (int)$_POST['company_id'] : null; // Old Logic
    $companyCode = trim($_POST['company_code'] ?? '');

    if (empty($companyCode)) {
        echo json_encode(['status' => 'error', 'message' => 'Company Code is required']);
        exit();
    }

    // Resolve Company Code to ID
    $db = new Database(); // Or use Company class if preferred, but direct DB is faster here
    $stmt = $db->prepareSelect("SELECT id FROM companies WHERE company_code = ? AND status IN ('active', 'trial') LIMIT 1", [$companyCode]);
    $company = $stmt ? $stmt->fetch() : false;

    if (!$company) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Company Code']);
        exit();
    }
    $companyId = $company['id'];

    if (empty($username) || empty($password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username and password are required'
        ]);
        exit();
    }

    try {
        $USER = new User();
        $login_result = $USER->login($username, $password, $companyId);

        if ($login_result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'redirect' => BASE_URL . 'views/Dashboard/index.php'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid username or password'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred. Please try again.'
        ]);
    }
    exit();
}

// Handle profile update
if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if (!$user_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid user ID'
        ]);
        exit();
    }

    try {
        $USER = new User($user_id);
        
        // Security: ensure user belongs to same company
        if ($USER->company_id != $sessionCompanyId) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit();
        }
        
        $USER->username = trim($_POST['username'] ?? $USER->username);
        $result = $USER->update();

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'username' => $USER->username,
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update profile. Username may already exist.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred while updating profile.'
        ]);
    }
    exit();
}

// Handle password change
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (!$user_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid user ID'
        ]);
        exit();
    }

    if (empty($current_password) || empty($new_password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Current password and new password are required'
        ]);
        exit();
    }

    try {
        $USER = new User($user_id);
        
        // Security: ensure user belongs to same company
        if ($USER->company_id != $sessionCompanyId) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit();
        }

        if (!$USER->checkOldPassword($user_id, $current_password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ]);
            exit();
        }

        $result = $USER->changePassword($user_id, $new_password);

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Password updated successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update password. Please try again.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred while changing password.'
        ]);
    }
    exit();
}

// Handle user management actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        // Get all users for current company
        if ($action === 'getUsers') {
            $USER = new User();
            $users = $USER->all($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $users]);
            exit();
        }

        // Get all roles for dropdown
        if ($action === 'getRoles') {
            $ROLE = new Role();
            $roles = $ROLE->all();
            echo json_encode(['status' => 'success', 'data' => $roles]);
            exit();
        }

        // Get branches for dropdown
        if ($action === 'getBranches') {
            $BRANCH = new Branch();
            $branches = $BRANCH->getActiveByCompany($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $branches]);
            exit();
        }

        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : null;
            $branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : null;

            if (empty($username) || empty($password) || strlen($password) < 8) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid input: Username and password (min 8 chars) required'
                ]);
                exit();
            }

            $USER = new User();
            $USER->company_id = $sessionCompanyId;
            $USER->branch_id = $branch_id;
            $USER->username = $username;
            $USER->password = $password;
            $USER->is_active = $is_active;
            $USER->role_id = $role_id;
            $res = $USER->create();
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'message' => $res ? 'User created successfully' : 'Failed to create user (username may exist)'
            ]);
            exit();
        } elseif ($action === 'update') {
            $id = (int)($_POST['user_id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
                exit();
            }

            $USER = new User($id);
            
            // Security: ensure user belongs to same company
            if ($USER->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                exit();
            }
            
            $USER->branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : null;
            $USER->username = trim($_POST['username'] ?? $USER->username);
            $USER->is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : $USER->is_active;
            $USER->role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : $USER->role_id;
            $res = $USER->update();
            if (isset($_POST['password']) && !empty($_POST['password'])) {
                $USER->changePassword($id, $_POST['password']);
            }
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'message' => $res ? 'User updated successfully' : 'Failed to update user (username may exist)'
            ]);
            exit();
        } elseif ($action === 'get') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
                exit();
            }
            $USER = new User($id);
            
            // Security: ensure user belongs to same company
            if ($USER->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                exit();
            }
            
            $data = [
                'id' => $USER->id,
                'company_id' => $USER->company_id,
                'branch_id' => $USER->branch_id,
                'username' => $USER->username,
                'is_active' => $USER->is_active,
                'role_id' => $USER->role_id
            ];
            echo json_encode([
                'status' => 'success',
                'data' => $data
            ]);
            exit();
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
                exit();
            }
            
            $USER = new User($id);
            
            // Security: ensure user belongs to same company
            if ($USER->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                exit();
            }
            
            // Prevent self-deletion
            if ($USER->id == ($_SESSION['id'] ?? 0)) {
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete your own account']);
                exit();
            }
            
            $res = $USER->delete();
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'message' => $res ? 'User deleted successfully' : 'Failed to delete user'
            ]);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ]);
        exit();
    }
}

// Handle invalid requests
echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request'
]);
exit();
