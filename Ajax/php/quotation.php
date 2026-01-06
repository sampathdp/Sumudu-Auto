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
            $quotation = new Quotation();
            $quotation->company_id = $sessionCompanyId;
            // Branch logic: defaulting to session branch, allowing override if needed/authorized
            $quotation->branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $sessionBranchId;
            
            $quotation->quotation_number = $quotation->generateQuotationNumber($sessionCompanyId);
            $quotation->customer_id = (int)($_POST['customer_id'] ?? 0);
            
            // Security: verify customer belongs to company
            if ($quotation->customer_id) {
                $customer = new Customer($quotation->customer_id);
                if (!$customer->id || $customer->company_id != $sessionCompanyId) {
                    $response['message'] = 'Customer not found';
                    break;
                }
                $quotation->customer_name = $customer->name;
            } else {
                $quotation->customer_name = trim($_POST['customer_name'] ?? '');
            }

            $quotation->subtotal = (float)($_POST['subtotal'] ?? 0);
            $quotation->tax_amount = (float)($_POST['tax_amount'] ?? 0);
            $quotation->discount_amount = (float)($_POST['discount_amount'] ?? 0);
            $quotation->total_amount = (float)($_POST['total_amount'] ?? 0);
            $quotation->valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
            $quotation->status = 'pending';

            if ($quotation->create()) {
                // Add items if provided
                if (!empty($_POST['items'])) {
                    $items = json_decode($_POST['items'], true);
                    foreach ($items as $item) {
                        // Check item belonging if item_id is provided
                        if (!empty($item['item_id'])) {
                            $invItem = new InventoryItem($item['item_id']);
                            if (!$invItem->id || $invItem->company_id != $sessionCompanyId) {
                                continue; // Skip invalid company items
                            }
                        }
                        $quotation->addItem($item);
                    }
                }
                $response = [
                    'status' => 'success',
                    'message' => 'Quotation created successfully',
                    'quotation_id' => $quotation->id,
                    'quotation_number' => $quotation->quotation_number
                ];
            } else {
                $response['message'] = 'Failed to create quotation';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid quotation ID';
                break;
            }
            $quotation = new Quotation($id);
            
            // Security: ensure quotation belongs to same company
            if (!$quotation->id || $quotation->company_id != $sessionCompanyId) {
                $response['message'] = 'Quotation not found';
                break;
            }

            $quotation->subtotal = (float)($_POST['subtotal'] ?? $quotation->subtotal);
            $quotation->tax_amount = (float)($_POST['tax_amount'] ?? $quotation->tax_amount);
            $quotation->discount_amount = (float)($_POST['discount_amount'] ?? $quotation->discount_amount);
            $quotation->total_amount = (float)($_POST['total_amount'] ?? $quotation->total_amount);
            $quotation->valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : $quotation->valid_until;
            $quotation->status = $_POST['status'] ?? $quotation->status;

            if ($quotation->update()) {
                $response = ['status' => 'success', 'message' => 'Quotation updated successfully'];
            } else {
                $response['message'] = 'Failed to update quotation';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid quotation ID';
                break;
            }
            $quotation = new Quotation($id);
            
            // Security: ensure quotation belongs to same company
            if ($quotation->id && $quotation->company_id == $sessionCompanyId) {
                $customerMobile = 'N/A';
                if (!empty($quotation->customer_id)) {
                    $customer = new Customer($quotation->customer_id);
                    if ($customer->id) {
                        $customerMobile = $customer->phone;
                    }
                }

                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $quotation->id,
                        'company_id' => $quotation->company_id,
                        'branch_id' => $quotation->branch_id,
                        'quotation_number' => $quotation->quotation_number,
                        'customer_id' => $quotation->customer_id,
                        'customer_name' => $quotation->customer_name,
                        'customer_mobile' => $customerMobile,
                        'subtotal' => $quotation->subtotal,
                        'tax_amount' => $quotation->tax_amount,
                        'discount_amount' => $quotation->discount_amount,
                        'total_amount' => $quotation->total_amount,
                        'valid_until' => $quotation->valid_until ?? '',
                        'created_at' => $quotation->created_at,
                        'status' => $quotation->status,
                        'items' => $quotation->getItems($quotation->id)
                    ]
                ];
            } else {
                $response['message'] = 'Quotation not found';
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid quotation ID';
                break;
            }
            $quotation = new Quotation($id);
            
            // Security: ensure quotation belongs to same company
            if (!$quotation->id || $quotation->company_id != $sessionCompanyId) {
                $response['message'] = 'Quotation not found';
                break;
            }
            
            if ($quotation->delete()) {
                $response = ['status' => 'success', 'message' => 'Quotation deleted successfully'];
            } else {
                $response['message'] = 'Failed to delete quotation';
            }
            break;

        case 'add_item':
            $quotationId = (int)($_POST['quotation_id'] ?? 0);
            if (!$quotationId) {
                $response['message'] = 'Invalid quotation ID';
                break;
            }

            $quotation = new Quotation($quotationId);
            
            // Security: ensure quotation belongs to same company
            if (!$quotation->id || $quotation->company_id != $sessionCompanyId) {
                $response['message'] = 'Quotation not found';
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

            // Security: check item if provided
            if ($itemData['item_id'] && $itemData['item_type'] === 'inventory') {
                $invItem = new InventoryItem();
                // We should ideally check if it exists in DB, but this check is usually lighter.
                // Assuming client sends valid ID. For strictness:
                $invItemCheck = new InventoryItem($itemData['item_id']);
                if (!$invItemCheck->id || $invItemCheck->company_id != $sessionCompanyId) {
                     $response['message'] = 'Invalid inventory item';
                     break; 
                }
            }

            // Calculate totals
            $itemData['total_price'] = $itemData['quantity'] * $itemData['unit_price'];
            $itemData['tax_amount'] = ($itemData['total_price'] * $itemData['tax_rate']) / 100;

            if ($quotation->addItem($itemData)) {
                // Recalculate totals
                $items = $quotation->getItems($quotationId);
                $subtotal = 0;
                $taxTotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['total_price'];
                    $taxTotal += $item['tax_amount'];
                }
                
                $quotation->subtotal = $subtotal;
                $quotation->tax_amount = $taxTotal;
                $quotation->total_amount = $subtotal + $taxTotal - ($quotation->discount_amount ?? 0);
                $quotation->update();

                $response = [
                    'status' => 'success',
                    'message' => 'Item added successfully',
                    'quotation' => [
                        'subtotal' => $quotation->subtotal,
                        'tax_amount' => $quotation->tax_amount,
                        'total_amount' => $quotation->total_amount
                    ],
                    'items' => $items
                ];
            } else {
                $response['message'] = 'Failed to add item.';
            }
            break;

        case 'remove_item':
            $itemId = (int)($_POST['item_id'] ?? 0);
            $quotationId = (int)($_POST['quotation_id'] ?? 0);
            
            if (!$itemId || !$quotationId) {
                $response['message'] = 'Invalid item or quotation ID';
                break;
            }

            $quotation = new Quotation($quotationId);
            // Security: ensure quotation belongs to same company
            if (!$quotation->id || $quotation->company_id != $sessionCompanyId) {
                $response['message'] = 'Quotation not found';
                break;
            }

            // Delete the item - we need to make sure item belongs to quotation which belongs to company
            $query = "DELETE FROM quotation_items WHERE id = ? AND quotation_id = ?";
            $db = new Database();
            // Since we verified quotation belongs to company, and we verify item belongs to quotation here:
            if ($db->prepareExecute($query, [$itemId, $quotationId])) {
                // Recalculate totals
                $items = $quotation->getItems($quotationId);
                $subtotal = 0;
                $taxTotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['total_price'];
                    $taxTotal += $item['tax_amount'];
                }
                
                $quotation->subtotal = $subtotal;
                $quotation->tax_amount = $taxTotal;
                $quotation->total_amount = $subtotal + $taxTotal - ($quotation->discount_amount ?? 0);
                $quotation->update();

                $response = [
                    'status' => 'success',
                    'message' => 'Item removed successfully',
                    'quotation' => [
                        'subtotal' => $quotation->subtotal,
                        'tax_amount' => $quotation->tax_amount,
                        'total_amount' => $quotation->total_amount
                    ],
                    'items' => $items
                ];
            } else {
                $response['message'] = 'Failed to remove item';
            }
            break;

        case 'get_next_number':
            $quotation = new Quotation();
            // Pass session company ID
            $response = [
                'status' => 'success',
                'next_number' => $quotation->generateQuotationNumber($sessionCompanyId)
            ];
            break;

        case 'list':
        default:
            $quotation = new Quotation();
            $filterBranchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $sessionBranchId;
            $quotations = $quotation->all($sessionCompanyId, $filterBranchId);
            $response = ['status' => 'success', 'data' => $quotations];
            break;

        case 'statistics':
            $filterBranchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $sessionBranchId;
            $stats = Quotation::getStatistics($sessionCompanyId, $filterBranchId);
            $response = ['status' => 'success', 'data' => $stats];
            break;
            
        case 'get_branches':
            $branch = new Branch();
            $branches = $branch->getActiveByCompany($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $branches];
            break;
    }
} catch (Exception $e) {
    error_log("Quotation AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
