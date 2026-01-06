<?php
ob_start();
require_once '../../classes/Includes.php';
ob_end_clean();

header('Content-Type: application/json');

// Only allow admin users
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$settings = new CompanyUISettings();
$componentService = new UIComponent(); // Instantiate UIComponent service
$db = new Database();

switch ($action) {
    case 'list_components':
        // For the Components Manager page
        try {
            $data = $componentService->getAll(false); // Include inactive
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'save_component':
        try {
            $id = $_POST['id'] ?? null;
            $data = [
                'component_key' => $_POST['component_key'] ?? '',
                'name' => $_POST['name'] ?? '',
                'category' => $_POST['category'] ?? 'General',
                'icon' => $_POST['icon'] ?? 'fa-cog',
                'description' => $_POST['description'] ?? '',
                'is_active' => $_POST['is_active'] ?? 1
            ];

            if ($id) {
                // Update
                $success = $componentService->update($id, $data);
            } else {
                // Create
                if (empty($data['component_key']) || empty($data['name'])) {
                     throw new Exception("Key and Name are required");
                }
                $existing = $componentService->getByKey($data['component_key']);
                if ($existing) {
                    throw new Exception("Component key already exists");
                }
                $success = $componentService->create($data);
            }

            if ($success) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_component':
        try {
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception("ID required");
            }
            if ($componentService->delete($id)) {
                 echo json_encode(['status' => 'success']);
            } else {
                 echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_companies':
        try {
            // Get list of active companies
            $stmt = $db->prepareSelect("SELECT id, name as company_name FROM companies ORDER BY name");
            $companies = $stmt ? $stmt->fetchAll() : [];
            echo json_encode(['status' => 'success', 'data' => $companies]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'list_settings':
        try {
            $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
            if (!$companyId) {
                echo json_encode(['status' => 'error', 'message' => 'Company ID required']);
                exit;
            }

            // Get merged list of components and rules from Class
            $components = $settings->getAllForCompany($companyId);
            
            // Group by Category
            $grouped = [];
            foreach ($components as $comp) {
                $category = $comp['category'];
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [
                        'category' => $category,
                        'items' => []
                    ];
                }
                $grouped[$category]['items'][] = [
                    'key' => $comp['component_key'],
                    'name' => $comp['name'],
                    'icon' => $comp['icon'],
                    'is_visible' => (bool)$comp['is_visible']
                ];
            }

            // Convert to indexed array for JSON
            $result = array_values($grouped);

            echo json_encode(['status' => 'success', 'data' => $result]);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle':
        try {
            $companyId = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
            $componentKey = $_POST['component_key'] ?? '';
            $isVisible = isset($_POST['is_visible']) ? (int)$_POST['is_visible'] : 1;

            if (!$companyId || !$componentKey) {
                echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
                exit;
            }

            $success = $settings->setVisibility($companyId, $componentKey, (bool)$isVisible);

            if ($success) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
            }

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
