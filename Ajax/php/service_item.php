<?php
/**
 * Service Items AJAX Handler
 * Manages service item operations (add, remove, update, list)
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');

$classesPath = dirname(__DIR__, 2) . '/classes/Includes.php';
if (!file_exists($classesPath)) {
    die(json_encode(['status' => 'error', 'message' => 'Classes not found']));
}
require_once $classesPath;

// Authentication check
if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$sessionCompanyId = $_SESSION['company_id'] ?? null;
if (!$sessionCompanyId) {
    echo json_encode(['status' => 'error', 'message' => 'Company context required']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'list':
            // Get all items for a service
            $serviceId = (int)($_GET['service_id'] ?? 0);
            if (!$serviceId) {
                $response['message'] = 'Service ID required';
                break;
            }
            
            $items = ServiceItem::getByServiceId($serviceId);
            $total = ServiceItem::calculateServiceTotal($serviceId);
            
            $response = [
                'status' => 'success',
                'data' => $items,
                'total' => $total
            ];
            break;

        case 'add_package':
            // Add a package to a service
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $packageId = (int)($_POST['package_id'] ?? 0);
            $quantity = (float)($_POST['quantity'] ?? 1);
            
            if (!$serviceId || !$packageId) {
                $response['message'] = 'Service ID and Package ID required';
                break;
            }
            
            // Verify service belongs to company
            $service = new Service($serviceId);
            if (!$service->id || $service->company_id != $sessionCompanyId) {
                $response['message'] = 'Service not found or access denied';
                break;
            }
            
            $package = new ServicePackage($packageId);
            if (!$package->id) {
                $response['message'] = 'Package not found';
                break;
            }
            
            $item = new ServiceItem();
            $item->company_id = $sessionCompanyId;
            $item->service_id = $serviceId;
            $item->item_type = 'package';
            $item->related_id = $packageId;
            $item->item_name = $package->package_name;
            $item->description = $package->description;
            $item->unit_price = $package->base_price;
            $item->quantity = $quantity;
            $item->discount_amount = 0;
            
            if ($item->create()) {
                // Recalculate service total
                $service->recalculateTotal();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Package added successfully',
                    'item_id' => $item->id,
                    'new_total' => $service->total_amount
                ];
            } else {
                $response['message'] = 'Failed to add package';
            }
            break;

        case 'add_custom':
            // Add a custom item (labor, misc, etc.)
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $itemName = trim($_POST['item_name'] ?? '');
            $unitPrice = (float)($_POST['unit_price'] ?? 0);
            $quantity = (float)($_POST['quantity'] ?? 1);
            
            if (!$serviceId || empty($itemName)) {
                $response['message'] = 'Service ID and Item Name required';
                break;
            }
            
            // Verify service belongs to company
            $service = new Service($serviceId);
            if (!$service->id || $service->company_id != $sessionCompanyId) {
                $response['message'] = 'Service not found or access denied';
                break;
            }
            
            $item = new ServiceItem();
            $item->company_id = $sessionCompanyId;
            $item->service_id = $serviceId;
            $item->item_type = 'custom';
            $item->related_id = null;
            $item->item_name = $itemName;
            $item->description = $_POST['description'] ?? '';
            $item->unit_price = $unitPrice;
            $item->quantity = $quantity;
            $item->discount_amount = (float)($_POST['discount'] ?? 0);
            
            if ($item->create()) {
                $service->recalculateTotal();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Item added successfully',
                    'item_id' => $item->id,
                    'new_total' => $service->total_amount
                ];
            } else {
                $response['message'] = 'Failed to add item';
            }
            break;

        case 'update':
            // Update an existing item
            $itemId = (int)($_POST['item_id'] ?? 0);
            if (!$itemId) {
                $response['message'] = 'Item ID required';
                break;
            }
            
            $item = new ServiceItem($itemId);
            if (!$item->id || $item->company_id != $sessionCompanyId) {
                $response['message'] = 'Item not found or access denied';
                break;
            }
            
            // Update allowed fields
            if (isset($_POST['unit_price'])) $item->unit_price = (float)$_POST['unit_price'];
            if (isset($_POST['quantity'])) $item->quantity = (float)$_POST['quantity'];
            if (isset($_POST['discount'])) $item->discount_amount = (float)$_POST['discount'];
            if (isset($_POST['item_name'])) $item->item_name = trim($_POST['item_name']);
            
            if ($item->update()) {
                $service = new Service($item->service_id);
                $service->recalculateTotal();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Item updated successfully',
                    'new_total' => $service->total_amount
                ];
            } else {
                $response['message'] = 'Failed to update item';
            }
            break;

        case 'delete':
            // Remove an item
            $itemId = (int)($_POST['item_id'] ?? 0);
            if (!$itemId) {
                $response['message'] = 'Item ID required';
                break;
            }
            
            $item = new ServiceItem($itemId);
            if (!$item->id || $item->company_id != $sessionCompanyId) {
                $response['message'] = 'Item not found or access denied';
                break;
            }
            
            $serviceId = $item->service_id;
            
            if ($item->delete()) {
                $service = new Service($serviceId);
                $service->recalculateTotal();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Item removed successfully',
                    'new_total' => $service->total_amount
                ];
            } else {
                $response['message'] = 'Failed to remove item';
            }
            break;

        case 'get_packages':
            // Get available packages for selection
            $package = new ServicePackage();
            $packages = $package->all($sessionCompanyId);
            
            $response = [
                'status' => 'success',
                'data' => $packages
            ];
            break;

        default:
            $response['message'] = 'Unknown action: ' . $action;
    }
} catch (Exception $e) {
    error_log("Service Items AJAX Error: " . $e->getMessage());
    $response['message'] = 'Server error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
