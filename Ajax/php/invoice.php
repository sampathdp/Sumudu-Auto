<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$response = ['status' => 'error', 'message' => 'Unauthorized access'];

if (!isset($_SESSION['id'])) {
    echo json_encode($response);
    exit;
}

// Get session company_id and branch_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;
$sessionBranchId = $_SESSION['branch_id'] ?? null;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $invoice = new Invoice();
            $invoice->company_id = $sessionCompanyId;
            // Branch logic: default to session branch, allow override if needed
            $invoice->branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $sessionBranchId;
            
            $invoice->invoice_number = $invoice->generateInvoiceNumber($sessionCompanyId);
            $invoice->service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
            
            // Security: If linked to service, ensure service belongs to company
            if ($invoice->service_id) {
                // Verify service
                $serviceQuery = "SELECT id, company_id FROM services WHERE id = ?";
                $db = new Database();
                $stmt = $db->prepareSelect($serviceQuery, [$invoice->service_id]);
                $svc = $stmt->fetch();
                if (!$svc || $svc['company_id'] != $sessionCompanyId) {
                    $response['message'] = 'Service not found or access denied';
                    break;
                }
            }

            $invoice->customer_id = (int)($_POST['customer_id'] ?? 0);
            
            // Security: Verify customer
            if ($invoice->customer_id) {
                $customer = new Customer($invoice->customer_id);
                if (!$customer->id || $customer->company_id != $sessionCompanyId) {
                    $response['message'] = 'Customer not found';
                    break;
                }
                $invoice->customer_name = $customer->name;
            } else {
                $invoice->customer_name = trim($_POST['customer_name'] ?? ''); 
            }

            $invoice->subtotal = (float)($_POST['subtotal'] ?? 0);
            $invoice->tax_amount = (float)($_POST['tax_amount'] ?? 0);
            $invoice->discount_amount = (float)($_POST['discount_amount'] ?? 0);
            $invoice->total_amount = (float)($_POST['total_amount'] ?? 0);
            $invoice->payment_method = $_POST['payment_method'] ?? null;
            $invoice->payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
            $invoice->account_id = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null; // Added
            $invoice->bill_type = $_POST['bill_type'] ?? 'cash'; // Added

            if ($invoice->create()) {
                // Add items if provided
                if (!empty($_POST['items'])) {
                    $items = json_decode($_POST['items'], true);
                    foreach ($items as $item) {
                        // Check inventory item security
                         if (!empty($item['item_id']) && $item['item_type'] == 'inventory') {
                            $invCheck = new InventoryItem($item['item_id']);
                            if (!$invCheck->id || $invCheck->company_id != $sessionCompanyId) {
                                continue; // Skip invalid
                            }
                        }
                        $invoice->addItem($item);
                    }
                }
                $response = [
                    'status' => 'success',
                    'message' => 'Invoice created successfully',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number
                ];
            } else {
                $response['message'] = 'Failed to create invoice';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid invoice ID';
                break;
            }
            $invoice = new Invoice($id);
            
            // Security: ensure invoice belongs to company
            if (!$invoice->id || $invoice->company_id != $sessionCompanyId) {
                $response['message'] = 'Invoice not found';
                break;
            }

            $invoice->subtotal = (float)($_POST['subtotal'] ?? $invoice->subtotal);
            $invoice->tax_amount = (float)($_POST['tax_amount'] ?? $invoice->tax_amount);
            $invoice->discount_amount = (float)($_POST['discount_amount'] ?? $invoice->discount_amount);
            $invoice->total_amount = (float)($_POST['total_amount'] ?? $invoice->total_amount);
            $invoice->payment_method = $_POST['payment_method'] ?? $invoice->payment_method;
            $invoice->payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : $invoice->payment_date;
            $invoice->account_id = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : $invoice->account_id; // Added
            $invoice->bill_type = $_POST['bill_type'] ?? $invoice->bill_type; // Added

            if ($invoice->update()) {
                // If items are provided, sync them
                if (!empty($_POST['items'])) {
                    $newItems = json_decode($_POST['items'], true);
                    
                    // 1. Remove all existing items (restores stock)
                    $existingItems = $invoice->getItems($invoice->id);
                    foreach ($existingItems as $oldItem) {
                        $invoice->removeItem($oldItem['id']);
                    }
                    
                    // 2. Add new items (deducts stock)
                    foreach ($newItems as $newItem) {
                        // Security check for inventory item
                        if (!empty($newItem['item_id']) && $newItem['item_type'] == 'inventory') {
                            $invCheck = new InventoryItem($newItem['item_id']);
                            if (!$invCheck->id || $invCheck->company_id != $sessionCompanyId) {
                                continue; 
                            }
                        }
                        $invoice->addItem($newItem);
                    }
                }

                $response = ['status' => 'success', 'message' => 'Invoice updated successfully'];
            } else {
                $response['message'] = 'Failed to update invoice';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid invoice ID';
                break;
            }
            $invoice = new Invoice($id);
            
            if ($invoice->id && $invoice->company_id == $sessionCompanyId) {
                $customerMobile = 'N/A';
                if (!empty($invoice->customer_id)) {
                    $customer = new Customer($invoice->customer_id);
                    if ($customer->id) {
                        $customerMobile = $customer->phone;
                    }
                }

                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $invoice->id,
                        'company_id' => $invoice->company_id,
                        'branch_id' => $invoice->branch_id,
                        'service_id' => $invoice->service_id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_id' => $invoice->customer_id,
                        'customer_name' => $invoice->customer_name,
                        'customer_mobile' => $customerMobile,
                        'subtotal' => $invoice->subtotal,
                        'tax_amount' => $invoice->tax_amount,
                        'discount_amount' => $invoice->discount_amount,
                        'total_amount' => $invoice->total_amount,
                        'payment_method' => $invoice->payment_method ?? '',
                        'payment_date' => $invoice->payment_date ?? '',
                        'account_id' => $invoice->account_id, // Added
                        'bill_type' => $invoice->bill_type, // Added
                        'created_at' => $invoice->created_at,
                        'status' => $invoice->status,
                        'items' => $invoice->getItems($invoice->id)
                    ]
                ];
            } else {
                $response['message'] = 'Invoice not found';
            }
            break;

        case 'delete':
            // Deletion is no longer allowed - use cancel instead
            $response['message'] = 'Invoice deletion is not allowed. Please use Cancel option instead.';
            break;
            
        case 'cancel':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid invoice ID';
                break;
            }
            $invoice = new Invoice($id);
            
            // Security: ensure invoice belongs to company
            if (!$invoice->id || $invoice->company_id != $sessionCompanyId) {
                $response['message'] = 'Invoice not found';
                break;
            }

            if ($invoice->cancel()) {
                 $response = ['status' => 'success', 'message' => 'Invoice cancelled successfully'];
            } else {
                 $response['message'] = 'Failed to cancel invoice';
            }
            break;

        case 'add_item':
            $invoiceId = (int)($_POST['invoice_id'] ?? 0);
            if (!$invoiceId) {
                $response['message'] = 'Invalid invoice ID';
                break;
            }

            $invoice = new Invoice($invoiceId);
            if (!$invoice->id || $invoice->company_id != $sessionCompanyId) {
                $response['message'] = 'Invoice not found';
                break;
            }

            $itemData = [
                'item_type' => $_POST['item_type'] ?? 'inventory',
                'item_id' => !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null,
                'description' => $_POST['description'] ?? '',
                'quantity' => (int)($_POST['quantity'] ?? 1),
                'unit_price' => (float)($_POST['unit_price'] ?? 0),
                'tax_rate' => (float)($_POST['tax_rate'] ?? 0)
            ];

            // Security check for inventory item
            if ($itemData['item_type'] == 'inventory' && $itemData['item_id']) {
                 $invCheck = new InventoryItem($itemData['item_id']);
                 if (!$invCheck->id || $invCheck->company_id != $sessionCompanyId) {
                      $response['message'] = 'Invalid inventory item for this company';
                      break;
                 }
            }

            // Calculate totals
            $itemData['total_price'] = $itemData['quantity'] * $itemData['unit_price'];
            $itemData['tax_amount'] = ($itemData['total_price'] * $itemData['tax_rate']) / 100;

            if ($invoice->addItem($itemData)) {
                // Recalculate invoice totals
                $items = $invoice->getItems($invoiceId);
                $subtotal = 0;
                $taxTotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['total_price'];
                    $taxTotal += $item['tax_amount'];
                }
                
                $invoice->subtotal = $subtotal;
                $invoice->tax_amount = $taxTotal;
                $invoice->total_amount = $subtotal + $taxTotal - ($invoice->discount_amount ?? 0);
                $invoice->update();

                $response = [
                    'status' => 'success',
                    'message' => 'Item added successfully',
                    'invoice' => [
                        'subtotal' => $invoice->subtotal,
                        'tax_amount' => $invoice->tax_amount,
                        'total_amount' => $invoice->total_amount
                    ],
                    'items' => $items
                ];
            } else {
                $response['message'] = 'Failed to add item. Check stock availability.';
            }
            break;

        case 'remove_item':
            $itemId = (int)($_POST['item_id'] ?? 0);
            $invoiceId = (int)($_POST['invoice_id'] ?? 0);
            
            if (!$itemId || !$invoiceId) {
                $response['message'] = 'Invalid item or invoice ID';
                break;
            }

            $invoice = new Invoice($invoiceId);
            if (!$invoice->id || $invoice->company_id != $sessionCompanyId) {
                $response['message'] = 'Invoice not found';
                break;
            }

            // Security check: item must belong to invoice (implicit via WHERE clause below)
            // But we should verify item deletion logic matches business rules (e.g. restoring stock if needed)
            // Current Invoice::addItem deducts stock. Invoice::delete doesn't restore items automatically individually.
            // Ideally remove_item should restore stock if it was inventory.
            // Let's rely on standard logic for now or todo: enhance remove_item.
            // Enhancing to restore stock:
            
            $db = new Database();
            $itemQuery = "SELECT * FROM invoice_items WHERE id = ? AND invoice_id = ?";
            $stmt = $db->prepareSelect($itemQuery, [$itemId, $invoiceId]);
            $itemToDelete = $stmt->fetch();
            
            if ($itemToDelete) {
                if ($invoice->removeItem($itemId)) {
                    // Recalc totals
                    $items = $invoice->getItems($invoiceId);
                    $subtotal = 0;
                    $taxTotal = 0;
                    foreach ($items as $item) {
                        $subtotal += $item['total_price'];
                        $taxTotal += $item['tax_amount'];
                    }
                    
                    $invoice->subtotal = $subtotal;
                    $invoice->tax_amount = $taxTotal;
                    $invoice->total_amount = $subtotal + $taxTotal - ($invoice->discount_amount ?? 0);
                    $invoice->update();
    
                    $response = [
                        'status' => 'success',
                        'message' => 'Item removed successfully',
                        'invoice' => [
                            'subtotal' => $invoice->subtotal,
                            'tax_amount' => $invoice->tax_amount,
                            'total_amount' => $invoice->total_amount
                        ],
                        'items' => $items
                    ];
                } else {
                    $response['message'] = 'Failed to remove item record';
                }
            } else {
                $response['message'] = 'Item not found in invoice';
            }
            break;

        case 'get_by_service':
            $serviceId = (int)($_GET['service_id'] ?? 0);
            if (!$serviceId) {
                $response['message'] = 'Invalid service ID';
                break;
            }

            $invoice = new Invoice();
            // Check invoice via simple query including company check
            // getByServiceId in Invoice class verifies existence but maybe not company directly in query unless updated.
            // Let's blindly check via Invoice method then verify company
            
            // Actually let's assume getByServiceId is updated or we check result
            // The Invoice class getByServiceId SELECTs * FROM invoices WHERE service_id = ?. 
            // Better to filter by company in query or check object after.
            
            // Manual check for now in AJAX for safety or trust getByServiceId
            $q = "SELECT id FROM invoices WHERE service_id = ? AND company_id = ?";
            $db = new Database();
            $stmt = $db->prepareSelect($q, [$serviceId, $sessionCompanyId]);
            $row = $stmt->fetch();
            
            if ($row) {
                $invoice = new Invoice($row['id']);
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $invoice->id,
                        'service_id' => $invoice->service_id,
                        'invoice_number' => $invoice->invoice_number,
                        'subtotal' => $invoice->subtotal,
                        'tax_amount' => $invoice->tax_amount,
                        'discount_amount' => $invoice->discount_amount,
                        'total_amount' => $invoice->total_amount,
                        'payment_method' => $invoice->payment_method ?? '',
                        'payment_date' => $invoice->payment_date ?? '',
                        'status' => $invoice->status,
                        'items' => $invoice->getItems($invoice->id)
                    ]
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'No invoice found for this service'
                ];
            }
            break;
            
        case 'create_from_service':
             $serviceId = (int)($_POST['service_id'] ?? 0);
             if (!$serviceId) {
                $response['message'] = 'Invalid service ID';
                break;
             }
             
             // Verify service/company relationship
             $db = new Database();
             $svc = $db->prepareSelect("SELECT company_id, branch_id FROM services WHERE id = ?", [$serviceId])->fetch();
             if (!$svc || $svc['company_id'] != $sessionCompanyId) {
                 $response['message'] = 'Service not found or access denied';
                 break;
             }
             
             $invoice = new Invoice();
             $result = $invoice->createFromService($serviceId, $sessionCompanyId, $svc['branch_id']);
             if ($result['success']) {
                 $response = ['status' => 'success', 'message' => $result['message'], 'invoice_number' => $result['invoice_number']];
             } else {
                 $response['message'] = $result['message'];
             }
             break;

        case 'get_next_number':
            $invoice = new Invoice();
            $response = [
                'status' => 'success',
                'next_number' => $invoice->generateInvoiceNumber($sessionCompanyId)
            ];
            break;

        case 'list':
        default:
            $invoice = new Invoice();
            $filterBranchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $sessionBranchId;
            $invoices = $invoice->all($sessionCompanyId, $filterBranchId);
            $response = ['status' => 'success', 'data' => $invoices];
            break;
            
        case 'statistics':
            $invoice = new Invoice();
            $filterBranchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $sessionBranchId;
            $stats = $invoice->getStatistics($sessionCompanyId, $filterBranchId);
            if ($stats) {
                $response = ['status' => 'success', 'data' => $stats];
            } else {
                $response['message'] = 'Failed to get statistics';
            }
            break;

        case 'get_branches':
            $branch = new Branch();
            $branches = $branch->getActiveByCompany($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $branches];
            break;
    }
} catch (Exception $e) {
    error_log("Invoice AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
