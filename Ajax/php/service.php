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
    $service = new Service();

    switch ($action) {
        case 'create':
            if (empty($_POST['customer_id']) || empty($_POST['vehicle_id']) || empty($_POST['package_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Customer, Vehicle, and Package are required']);
                exit;
            }

            $service->company_id = $sessionCompanyId;
            $service->branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : $sessionBranchId;
            $service->customer_id        = (int)$_POST['customer_id'];
            $service->vehicle_id         = (int)$_POST['vehicle_id'];
            $service->package_id         = (int)$_POST['package_id'];
            $service->notes              = trim($_POST['notes'] ?? '');
            $service->assigned_employee_id  = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
            $service->total_amount       = (float)($_POST['total_amount'] ?? 0);

            $result = $service->create();

            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Service created successfully',
                    'data' => $result
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create service']);
            }
            break;

        case 'create_from_invoice':
            // Create service record from invoice - simplified version without package requirement
            if (empty($_POST['vehicle_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Vehicle ID is required']);
                exit;
            }

            $db = new Database();
            
            // Generate job number
            $prefix = "JOB";
            $date = date('Ymd');
            $query = "SELECT job_number FROM services WHERE company_id = ? AND job_number LIKE ? ORDER BY id DESC LIMIT 1";
            $stmt = $db->prepareSelect($query, [$sessionCompanyId, $prefix . $date . '%']);
            $sequence = 1;
            if ($stmt) {
                $row = $stmt->fetch();
                if ($row) {
                    $sequence = (int)substr($row['job_number'], strlen($prefix . $date)) + 1;
                }
            }
            $jobNumber = $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Insert service record
            $insertQuery = "INSERT INTO services (
                company_id, branch_id, job_number, customer_id, vehicle_id, 
                status, progress_percentage, total_amount, payment_status, notes, start_time
            ) VALUES (?, ?, ?, ?, ?, 'completed', 100, ?, 'paid', ?, NOW())";

            $success = $db->prepareExecute($insertQuery, [
                $sessionCompanyId,
                $sessionBranchId,
                $jobNumber,
                !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
                (int)$_POST['vehicle_id'],
                (float)($_POST['total_amount'] ?? 0),
                $_POST['notes'] ?? 'Created from Invoice #' . ($_POST['invoice_id'] ?? '')
            ]);

            if ($success) {
                $serviceId = $db->getLastInsertId();
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Service record created',
                    'service_id' => $serviceId,
                    'job_number' => $jobNumber
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create service record']);
            }
            break;

        case 'create_multi':
            // Multi-package job creation
            if (empty($_POST['customer_id']) || empty($_POST['vehicle_id']) || empty($_POST['package_ids'])) {
                echo json_encode(['status' => 'error', 'message' => 'Customer, Vehicle, and at least one Package are required']);
                exit;
            }

            $packageIds = json_decode($_POST['package_ids'], true);
            if (!is_array($packageIds) || empty($packageIds)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid package selection']);
                exit;
            }

            $service->company_id = $sessionCompanyId;
            $service->branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : $sessionBranchId;
            $service->customer_id = (int)$_POST['customer_id'];
            $service->vehicle_id = (int)$_POST['vehicle_id'];
            $service->notes = trim($_POST['notes'] ?? '');
            $service->assigned_employee_id = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;

            $result = $service->createWithPackages($packageIds);

            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Service created successfully with ' . count($packageIds) . ' package(s)',
                    'data' => $result
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create multi-package service']);
            }
            break;

        case 'update':
            if (empty($_POST['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Service ID required']);
                exit;
            }

            $service = new Service((int)$_POST['id']);
            
            // Security: ensure service belongs to same company
            if (!$service->id || $service->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Service not found']);
                exit;
            }

            $service->branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : $service->branch_id;
            $service->customer_id       = (int)$_POST['customer_id'];
            $service->vehicle_id        = (int)$_POST['vehicle_id'];
            $service->package_id        = (int)$_POST['package_id'];
            $service->status            = $_POST['status'] ?? $service->status;
            $service->progress_percentage = (int)($_POST['progress_percentage'] ?? $service->progress_percentage);
            $service->total_amount      = (float)($_POST['total_amount'] ?? $service->total_amount);
            $service->payment_status    = $_POST['payment_status'] ?? $service->payment_status;
            $service->notes             = trim($_POST['notes'] ?? '');

            if ($service->update()) {
                echo json_encode(['status' => 'success', 'message' => 'Service updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update service']);
            }
            break;

        case 'get':
            if (empty($_GET['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Service ID required']);
                exit;
            }

            $service = new Service((int)$_GET['id']);
            
            // Security: ensure service belongs to same company
            if ($service->id && $service->company_id == $sessionCompanyId) {
                $customer = new Customer($service->customer_id);
                $vehicle  = new Vehicle($service->vehicle_id);
                $package  = new ServicePackage($service->package_id);
                $qrCode   = new QRCode($service->qr_id);

                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'id' => $service->id,
                        'company_id' => $service->company_id,
                        'branch_id' => $service->branch_id,
                        'job_number' => $service->job_number,
                        'customer' => [
                            'id' => $customer->id,
                            'name' => $customer->name,
                            'phone' => $customer->phone,
                            'email' => $customer->email
                        ],
                        'vehicle' => [
                            'id' => $vehicle->id,
                            'make' => $vehicle->make,
                            'model' => $vehicle->model,
                            'registration_number' => $vehicle->registration_number,
                            'year' => $vehicle->year
                        ],
                        'package' => [
                            'id' => $package->id,
                            'package_name' => $package->package_name,
                            'base_price' => $package->base_price,
                            'estimated_duration' => $package->estimated_duration
                        ],
                        'qr_code' => $qrCode->qr_code,
                        'status' => $service->status,
                        'progress_percentage' => $service->progress_percentage,
                        'total_amount' => $service->total_amount,
                        'payment_status' => $service->payment_status,
                        'notes' => $service->notes,
                        'created_at' => $service->created_at,
                        'updated_at' => $service->updated_at
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Service not found']);
            }
            break;

        case 'delete':
            if (empty($_POST['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Service ID required']);
                exit;
            }

            $service = new Service((int)$_POST['id']);
            
            // Security: ensure service belongs to same company
            if (!$service->id || $service->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Service not found']);
                exit;
            }
            
            if ($service->delete()) {
                echo json_encode(['status' => 'success', 'message' => 'Service deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete service']);
            }
            break;

        case 'list':
            $dateFilter = $_GET['date_filter'] ?? null;
            $date       = $_GET['date'] ?? null;
            $startDate  = $_GET['start_date'] ?? null;
            $endDate    = $_GET['end_date'] ?? null;
            $status     = $_GET['status'] ?? null;
            $branchId   = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;

            if ($dateFilter === 'range' && $startDate && $endDate) {
                $services = $service->getByDateRange($sessionCompanyId, $startDate, $endDate, $branchId);
            } else {
                $services = $service->all($sessionCompanyId, $branchId, $dateFilter, $date, $status);
            }

            $statusProgress = [
                'waiting'       => 10,
                'in_progress'   => 40,
                'quality_check' => 70,
                'completed'     => 90,
                'delivered'     => 100,
                'cancelled'     => 0
            ];

            foreach ($services as &$srv) {
                if (is_null($srv['progress_percentage']) || $srv['progress_percentage'] == 0) {
                    $srv['progress_percentage'] = $statusProgress[$srv['status']] ?? 0;
                }
                $srv['status_normalized'] = str_replace('-', '_', $srv['status']);
                
                // Get packages for this service from service_items table
                $db = new Database();
                $pkgQuery = "SELECT si.description as item_name, sp.package_name 
                             FROM service_items si 
                             LEFT JOIN service_packages sp ON si.related_id = sp.id AND si.item_type = 'package'
                             WHERE si.service_id = ? AND si.item_type = 'package'
                             ORDER BY si.id";
                $pkgStmt = $db->prepareSelect($pkgQuery, [$srv['id']]);
                $packages = $pkgStmt ? $pkgStmt->fetchAll() : [];
                
                // Create comma-separated list of package names
                $packageNames = [];
                foreach ($packages as $pkg) {
                    $packageNames[] = $pkg['package_name'] ?: $pkg['item_name'];
                }
                $srv['packages'] = $packageNames;
                $srv['packages_text'] = implode(', ', $packageNames);
            }
            unset($srv);

            echo json_encode([
                'status' => 'success',
                'data'   => $services,
                'meta'   => [
                    'total' => count($services),
                    'filters' => [
                        'date_filter' => $dateFilter,
                        'status'      => $status,
                        'date'        => $date,
                        'start_date'  => $startDate,
                        'end_date'    => $endDate,
                        'branch_id'   => $branchId
                    ]
                ]
            ]);
            break;

        case 'update_status':
            if (empty($_POST['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Service ID required']);
                exit;
            }

            $service = new Service((int)$_POST['id']);
            
            // Security: ensure service belongs to same company
            if (!$service->id || $service->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Job not found']);
                exit;
            }

            // Handle stage-based update (Preferred)
            if (!empty($_POST['stage_id'])) {
                if ($service->updateStage((int)$_POST['stage_id'])) {
                    if ($service->qr_id) {
                        $qr = new QRCode($service->qr_id);
                        $color = ($service->progress_percentage >= 100) ? 'green' : ($service->progress_percentage >= 70 ? 'yellow' : 'red');
                        $qr->updateColorCode($color);
                    }

                    echo json_encode([
                        'status'       => 'success',
                        'message'      => 'Status updated successfully',
                        'new_status'   => $service->status,
                        'new_stage_id' => $service->current_stage_id,
                        'progress'     => $service->progress_percentage
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update stage']);
                }
                exit;
            }

            // Legacy status-based update (Fallback)
            if (!isset($_POST['status'])) {
                 echo json_encode(['status' => 'error', 'message' => 'Status or Stage ID required']);
                 exit;
            }

            $statusMap = [
                'waiting'       => 'waiting',
                'in_progress'   => 'in_progress',
                'quality_check' => 'quality_check',
                'completed'     => 'completed',
                'delivered'     => 'delivered',
                'cancelled'     => 'cancelled'
            ];

            $newStatus = $_POST['status'];
            if (!isset($statusMap[$newStatus])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
                exit;
            }

            $service->status = $statusMap[$newStatus];

            $progressMap = [
                'waiting'       => 10,
                'in_progress'   => 40,
                'quality_check' => 70,
                'completed'     => 90,
                'delivered'     => 100,
                'cancelled'     => 0
            ];
            $service->progress_percentage = $progressMap[$newStatus];

            if ($service->update()) {
                if ($service->qr_id) {
                    $qr = new QRCode($service->qr_id);
                    $color = ($service->progress_percentage >= 100) ? 'green' : ($service->progress_percentage >= 70 ? 'yellow' : 'red');
                    $qr->updateColorCode($color);
                }

                echo json_encode([
                    'status'       => 'success',
                    'message'      => 'Status updated',
                    'new_status'   => $service->status,
                    'progress'     => $service->progress_percentage
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
            }
            break;

        case 'get_by_qr':
            if (empty($_GET['qr_code'])) {
                echo json_encode(['status' => 'error', 'message' => 'QR code required']);
                exit;
            }

            // Note: QR lookup may be without company filter for public tracking pages
            if ($service->getByQRCode($_GET['qr_code'])) {
                $qr = new QRCode($service->qr_id);
                $qr->incrementScanCount();

                $customer = new Customer($service->customer_id);
                $vehicle  = new Vehicle($service->vehicle_id);
                $package  = new ServicePackage($service->package_id);

                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'id'               => $service->id,
                        'job_number'       => $service->job_number,
                        'customer_name'    => $customer->name,
                        'vehicle'          => $vehicle->make . ' ' . $vehicle->model,
                        'registration'     => $vehicle->registration_number,
                        'package_name'     => $package->package_name,
                        'status'           => $service->status,
                        'progress_percentage' => $service->progress_percentage,
                        'total_amount'     => $service->total_amount,
                        'payment_status'   => $service->payment_status
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Service not found']);
            }
            break;

        case 'get_by_job_number':
            if (empty($_GET['job_number'])) {
                echo json_encode(['status' => 'error', 'message' => 'Job number required']);
                exit;
            }

            if ($service->getByJobNumber($_GET['job_number'], $sessionCompanyId)) {
                $customer = new Customer($service->customer_id);
                $vehicle  = new Vehicle($service->vehicle_id);
                $package  = new ServicePackage($service->package_id);

                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'id'               => $service->id,
                        'job_number'       => $service->job_number,
                        'customer_name'    => $customer->name,
                        'vehicle'          => $vehicle->make . ' ' . $vehicle->model,
                        'status'           => $service->status,
                        'total_amount'     => $service->total_amount
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Service not found']);
            }
            break;

        case 'poll_status':
            if (empty($_GET['qr_code']) && empty($_GET['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'QR code or Service ID required']);
                exit;
            }

            $found = false;
            if (!empty($_GET['qr_code'])) {
                $found = $service->getByQRCode($_GET['qr_code']);
            } elseif (!empty($_GET['id'])) {
                $service = new Service((int)$_GET['id']);
                $found = (bool)$service->id;
            }

            if ($found) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'id'                  => $service->id,
                        'status'              => $service->status,
                        'progress_percentage' => $service->progress_percentage,
                        'payment_status'      => $service->payment_status,
                        'updated_at'          => $service->updated_at
                    ],
                    'timestamp' => time()
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Service not found']);
            }
            break;

        case 'get_by_qr_full':
            if (empty($_GET['qr_code'])) {
                echo json_encode(['status' => 'error', 'message' => 'QR code required']);
                exit;
            }

            if ($service->getByQRCode($_GET['qr_code'])) {
                $qr = new QRCode($service->qr_id);
                $qr->incrementScanCount();

                $customer = new Customer($service->customer_id);
                $vehicle  = new Vehicle($service->vehicle_id);
                $package  = new ServicePackage($service->package_id);

                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'id'                     => $service->id,
                        'job_number'             => $service->job_number,
                        'customer_name'          => $customer->name,
                        'customer_phone'         => $customer->phone,
                        'vehicle'                => $vehicle->make . ' ' . $vehicle->model,
                        'registration'           => $vehicle->registration_number,
                        'vehicle_year'           => $vehicle->year,
                        'package_name'           => $package->package_name,
                        'package_description'    => $package->description,
                        'status'                 => $service->status,
                        'progress_percentage'    => $service->progress_percentage,
                        'total_amount'           => $service->total_amount,
                        'payment_status'         => $service->payment_status,
                        'start_time'             => $service->start_time,
                        'expected_completion_time' => $service->expected_completion_time,
                        'actual_completion_time' => $service->actual_completion_time,
                        'notes'                  => $service->notes,
                        'created_at'             => $service->created_at,
                        'updated_at'             => $service->updated_at
                    ],
                    'timestamp' => time()
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Service not found']);
            }
            break;

        case 'update_payment_status':
            if (empty($_POST['service_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Service ID required']);
                exit;
            }

            $service = new Service((int)$_POST['service_id']);
            
            // Security: ensure service belongs to same company
            if (!$service->id || $service->company_id != $sessionCompanyId) {
                echo json_encode(['status' => 'error', 'message' => 'Service not found']);
                exit;
            }

            $paymentStatus = $_POST['payment_status'] ?? 'paid';
            if (!in_array($paymentStatus, ['pending', 'paid', 'partial'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid payment status']);
                exit;
            }

            $service->payment_status = $paymentStatus;
            
            if ($service->update()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Payment status updated successfully',
                    'new_payment_status' => $service->payment_status
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update payment status']);
            }
            break;

        case 'statistics':
            $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
            $stats = $service->getStatistics($sessionCompanyId, $branchId);
            if ($stats) {
                echo json_encode(['status' => 'success', 'data' => $stats]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to get statistics']);
            }
            break;

        case 'get_branches':
            $branch = new Branch();
            $branches = $branch->getActiveByCompany($sessionCompanyId);
            echo json_encode(['status' => 'success', 'data' => $branches]);
            break;

        case 'get_service_items':
            // Get service items filtered by type
            $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
            $itemType = $_GET['item_type'] ?? null;
            
            if (!$serviceId) {
                echo json_encode(['status' => 'error', 'message' => 'Service ID required']);
                break;
            }
            
            $db = new Database();
            $query = "SELECT * FROM service_items WHERE service_id = ?";
            $params = [$serviceId];
            
            if ($itemType) {
                $query .= " AND item_type = ?";
                $params[] = $itemType;
            }
            $query .= " ORDER BY created_at ASC";
            
            $stmt = $db->prepareSelect($query, $params);
            $items = $stmt ? $stmt->fetchAll() : [];
            
            echo json_encode(['status' => 'success', 'data' => $items]);
            break;

        case 'add_inventory_item':
            // Add inventory item to a service
            $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
            $inventoryItemId = isset($_POST['inventory_item_id']) ? (int)$_POST['inventory_item_id'] : 0;
            $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0;
            $unitPrice = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : 0;
            
            if (!$serviceId || !$inventoryItemId || $quantity <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
                break;
            }
            
            // Get the inventory item
            $db = new Database();
            $invStmt = $db->prepareSelect("SELECT * FROM inventory_items WHERE id = ?", [$inventoryItemId]);
            $invItem = $invStmt ? $invStmt->fetch() : null;
            
            if (!$invItem) {
                echo json_encode(['status' => 'error', 'message' => 'Inventory item not found']);
                break;
            }
            
            // Check stock
            if ($quantity > (float)$invItem['current_stock']) {
                echo json_encode(['status' => 'error', 'message' => 'Insufficient stock']);
                break;
            }
            
            // Deduct from inventory
            $newQty = (float)$invItem['current_stock'] - $quantity;
            $db->prepareExecute("UPDATE inventory_items SET current_stock = ? WHERE id = ?", [$newQty, $inventoryItemId]);
            
            // Create service item
            $totalPrice = $quantity * $unitPrice;
            $serviceItem = new ServiceItem();
            $serviceItem->company_id = $sessionCompanyId;
            $serviceItem->service_id = $serviceId;
            $serviceItem->item_type = 'inventory';
            $serviceItem->related_id = $inventoryItemId;
            $serviceItem->item_name = $invItem['item_name'];
            $serviceItem->description = $invItem['description'] ?? $invItem['item_name'];
            $serviceItem->quantity = $quantity;
            $serviceItem->unit_price = $unitPrice;
            $serviceItem->tax_amount = 0;
            $serviceItem->total_price = $totalPrice;
            
            if ($serviceItem->create()) {
                // Update service total
                $service = new Service($serviceId);
                $service->recalculateTotal();
                
                echo json_encode(['status' => 'success', 'message' => 'Item added successfully']);
            } else {
                // Revert stock if failed
                $db->prepareExecute("UPDATE inventory_items SET current_stock = current_stock + ? WHERE id = ?", [$quantity, $inventoryItemId]);
                echo json_encode(['status' => 'error', 'message' => 'Failed to add item']);
            }
            break;

        case 'remove_service_item':
            // Remove a service item and restore inventory if applicable
            $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
            
            if (!$itemId) {
                echo json_encode(['status' => 'error', 'message' => 'Item ID required']);
                break;
            }
            
            $db = new Database();
            
            // Get the service item first
            $stmt = $db->prepareSelect("SELECT * FROM service_items WHERE id = ?", [$itemId]);
            $srvItem = $stmt ? $stmt->fetch() : null;
            
            if (!$srvItem) {
                echo json_encode(['status' => 'error', 'message' => 'Item not found']);
                break;
            }
            
            // If it's an inventory type, restore the stock
            if ($srvItem['item_type'] === 'inventory' && $srvItem['related_id']) {
                $db->prepareExecute(
                    "UPDATE inventory_items SET current_stock = current_stock + ? WHERE id = ?", 
                    [$srvItem['quantity'], $srvItem['related_id']]
                );
            }
            
            // Delete the service item
            if ($db->prepareExecute("DELETE FROM service_items WHERE id = ?", [$itemId])) {
                // Recalculate service total
                $service = new Service($srvItem['service_id']);
                $service->recalculateTotal();
                
                echo json_encode(['status' => 'success', 'message' => 'Item removed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to remove item']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Service AJAX Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
}
?>