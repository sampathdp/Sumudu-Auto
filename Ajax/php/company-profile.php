<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

// Get current user's company_id from session (default to 1 for backward compatibility)
$sessionCompanyId = $_SESSION['company_id'] ?? 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid company profile ID';
                break;
            }

            $profile = new CompanyProfile($id);
            // Security check: ensure profile belongs to user's company
            if ($profile->id && $profile->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $profile->id,
                        'company_id' => $profile->company_id,
                        'name' => $profile->name,
                        'address' => $profile->address,
                        'mobile_number_1' => $profile->mobile_number_1,
                        'mobile_number_2' => $profile->mobile_number_2,
                        'email' => $profile->email,
                        'image_name' => $profile->image_name,
                        'is_active' => $profile->is_active,
                        'is_vat' => $profile->is_vat,
                        'tax_number' => $profile->tax_number,
                        'tax_percentage' => $profile->tax_percentage,
                        'customer_id' => $profile->customer_id,
                        'company_code' => $profile->company_code,
                        'theme' => $profile->theme,
                        'favicon' => $profile->favicon,
                        'cashbook_opening_balance' => $profile->cashbook_opening_balance
                    ]
                ];
            } else {
                $response['message'] = 'Company profile not found';
            }
            break;

        case 'get_active':
            $profile = new CompanyProfile();
            if ($profile->loadActive($sessionCompanyId)) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $profile->id,
                        'company_id' => $profile->company_id,
                        'name' => $profile->name,
                        'address' => $profile->address,
                        'mobile_number_1' => $profile->mobile_number_1,
                        'mobile_number_2' => $profile->mobile_number_2,
                        'email' => $profile->email,
                        'image_name' => $profile->image_name,
                        'is_active' => $profile->is_active,
                        'is_vat' => $profile->is_vat,
                        'tax_number' => $profile->tax_number,
                        'tax_percentage' => $profile->tax_percentage,
                        'customer_id' => $profile->customer_id,
                        'company_code' => $profile->company_code,
                        'theme' => $profile->theme,
                        'favicon' => $profile->favicon,
                        'cashbook_opening_balance' => $profile->cashbook_opening_balance
                    ]
                ];
            } else {
                $response = ['status' => 'error', 'message' => 'No active company profile found'];
            }
            break;

        case 'get_by_company':
            $profile = new CompanyProfile();
            if ($profile->loadByCompanyId($sessionCompanyId)) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $profile->id,
                        'company_id' => $profile->company_id,
                        'name' => $profile->name,
                        'address' => $profile->address,
                        'mobile_number_1' => $profile->mobile_number_1,
                        'mobile_number_2' => $profile->mobile_number_2,
                        'email' => $profile->email,
                        'image_name' => $profile->image_name,
                        'is_active' => $profile->is_active,
                        'is_vat' => $profile->is_vat,
                        'tax_number' => $profile->tax_number,
                        'tax_percentage' => $profile->tax_percentage,
                        'customer_id' => $profile->customer_id,
                        'company_code' => $profile->company_code,
                        'theme' => $profile->theme,
                        'favicon' => $profile->favicon,
                        'cashbook_opening_balance' => $profile->cashbook_opening_balance
                    ]
                ];
            } else {
                $response = ['status' => 'error', 'message' => 'No company profile found for this company'];
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            
            // If no ID provided, check if profile exists for this company
            if (!$id) {
                $existingProfile = new CompanyProfile();
                if ($existingProfile->loadByCompanyId($sessionCompanyId)) {
                    $id = $existingProfile->id;
                }
            }

            $profile = new CompanyProfile($id);
            $isNew = false;

            if ($id) {
                // Security check: ensure profile belongs to user's company
                if (!$profile->id || $profile->company_id != $sessionCompanyId) {
                    $response['message'] = 'Company profile not found or access denied';
                    break;
                }
            } else {
                // No existing profile found, create new one
                $isNew = true;
                $profile->company_id = $sessionCompanyId;
            }

            // Update fields
            $profile->name = $_POST['name'] ?? $profile->name;
            $profile->address = $_POST['address'] ?? $profile->address;
            $profile->mobile_number_1 = $_POST['mobile_number_1'] ?? $profile->mobile_number_1;
            $profile->mobile_number_2 = $_POST['mobile_number_2'] ?? $profile->mobile_number_2;
            $profile->email = $_POST['email'] ?? $profile->email;
            $profile->is_vat = isset($_POST['is_vat']) ? (int)$_POST['is_vat'] : $profile->is_vat;
            $profile->tax_number = $_POST['tax_number'] ?? $profile->tax_number;
            $profile->tax_percentage = isset($_POST['tax_percentage']) ? (int)$_POST['tax_percentage'] : $profile->tax_percentage;
            $profile->company_code = $_POST['company_code'] ?? $profile->company_code;
            $profile->theme = $_POST['theme'] ?? $profile->theme;
            $profile->cashbook_opening_balance = isset($_POST['cashbook_opening_balance']) ? (float)$_POST['cashbook_opening_balance'] : $profile->cashbook_opening_balance;
            $profile->is_active = 1; // Default to active on create/update

            if ($isNew) {
                if ($profile->create()) {
                     $response = ['status' => 'success', 'message' => 'Company profile created successfully', 'id' => $profile->id];
                } else {
                     $response['message'] = 'Failed to create company profile';
                }
            } else {
                if ($profile->update()) {
                    $response = ['status' => 'success', 'message' => 'Company profile updated successfully'];
                } else {
                    $response['message'] = 'Failed to update company profile';
                }
            }
            break;

        case 'upload_logo':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid company profile ID';
                break;
            }

            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = 'No file uploaded or upload error occurred';
                break;
            }

            $profile = new CompanyProfile($id);
            // Security check
            if (!$profile->id || $profile->company_id != $sessionCompanyId) {
                $response['message'] = 'Company profile not found or access denied';
                break;
            }

            $result = $profile->uploadLogo($_FILES['logo']);
            $response = [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'filename' => $result['filename'] ?? null
            ];
            break;

        case 'upload_favicon':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid company profile ID';
                break;
            }

            if (!isset($_FILES['favicon']) || $_FILES['favicon']['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = 'No file uploaded or upload error occurred';
                break;
            }

            $profile = new CompanyProfile($id);
            // Security check
            if (!$profile->id || $profile->company_id != $sessionCompanyId) {
                $response['message'] = 'Company profile not found or access denied';
                break;
            }

            $result = $profile->uploadFavicon($_FILES['favicon']);
            $response = [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'filename' => $result['filename'] ?? null
            ];
            break;

        case 'create':
            // Check if profile already exists for this company
            if (CompanyProfile::existsForCompany($sessionCompanyId)) {
                $response['message'] = 'Company profile already exists. Use update instead.';
                break;
            }

            $profile = new CompanyProfile();
            $profile->company_id = $sessionCompanyId;
            $profile->name = $_POST['name'] ?? '';
            $profile->address = $_POST['address'] ?? '';
            $profile->mobile_number_1 = $_POST['mobile_number_1'] ?? '';
            $profile->mobile_number_2 = $_POST['mobile_number_2'] ?? '';
            $profile->email = $_POST['email'] ?? '';
            $profile->is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $profile->is_vat = isset($_POST['is_vat']) ? (int)$_POST['is_vat'] : 0;
            $profile->tax_number = $_POST['tax_number'] ?? '';
            $profile->tax_percentage = isset($_POST['tax_percentage']) ? (int)$_POST['tax_percentage'] : 0;
            $profile->company_code = $_POST['company_code'] ?? '';
            $profile->theme = $_POST['theme'] ?? 'dark';
            $profile->cashbook_opening_balance = isset($_POST['cashbook_opening_balance']) ? (float)$_POST['cashbook_opening_balance'] : 0;

            if ($profile->create()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Company profile created successfully',
                    'id' => $profile->id
                ];
            } else {
                $response['message'] = 'Failed to create company profile';
            }
            break;

        case 'list':
        default:
            $profile = new CompanyProfile();
            // Filter by company_id for regular users
            $profiles = $profile->all($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $profiles];
            break;
    }
} catch (Exception $e) {
    error_log("Company Profile AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
