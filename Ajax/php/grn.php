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
            $grn = new GRN();
            $grn->company_id = $sessionCompanyId;
            // Branch logic: defaulting to session branch, but allowing override if company supports it
            $grn->branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $sessionBranchId;
            
            $grn->grn_number = $grn->generateGRNNumber($sessionCompanyId);
            $grn->supplier_id = (int)($_POST['supplier_id'] ?? 0);
            
            // Security: verify supplier belongs to company
            $supplier = new Supplier($grn->supplier_id);
            if (!$supplier->id || $supplier->company_id != $sessionCompanyId) {
                $response['message'] = 'Supplier not found';
                break;
            }
            
            $grn->grn_date = $_POST['grn_date'] ?? date('Y-m-d');
            $grn->due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $grn->invoice_number = trim($_POST['invoice_number'] ?? '') ?: null;
            $grn->total_amount = (float)($_POST['total_amount'] ?? 0);
            $grn->tax_amount = (float)($_POST['tax_amount'] ?? 0);
            $grn->discount_amount = (float)($_POST['discount_amount'] ?? 0);
            $grn->net_amount = (float)($_POST['net_amount'] ?? 0);
            $grn->status = $_POST['status'] ?? 'draft';
            $employee = new Employee();
            $currentEmployee = $employee->getByUserId($_SESSION['id']);
            $grn->received_by_employee_id = $currentEmployee ? $currentEmployee->id : null;
            $grn->notes = trim($_POST['notes'] ?? '') ?: null;

            if ($grn->create()) {
                // Add items if provided
                if (!empty($_POST['items'])) {
                    $items = json_decode($_POST['items'], true);
                    foreach ($items as $item) {
                        // Security check for each item could be added here, 
                        // ensuring item belongs to company.
                        $invItem = new InventoryItem($item['item_id']);
                        if ($invItem->id && $invItem->company_id == $sessionCompanyId) {
                            $grn->addItem($item);
                        }
                    }
                }
                $response = [
                    'status' => 'success', 
                    'message' => 'GRN created successfully',
                    'grn_id' => $grn->id,
                    'grn_number' => $grn->grn_number
                ];
            } else {
                $response['message'] = 'Failed to create GRN';
            }
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid GRN ID';
                break;
            }
            $grn = new GRN($id);
            
            // Security: ensure GRN belongs to same company
            if (!$grn->id || $grn->company_id != $sessionCompanyId) {
                $response['message'] = 'GRN not found';
                break;
            }

            // Only allow editing if status is draft
            if ($grn->status !== 'draft') {
                $response['message'] = 'Only draft GRNs can be edited';
                break;
            }

            $grn->supplier_id = (int)($_POST['supplier_id'] ?? $grn->supplier_id);
            
            // Security verify new supplier
            if ($grn->supplier_id != $_POST['supplier_id']) { // If changed
                $supplier = new Supplier($grn->supplier_id);
                if (!$supplier->id || $supplier->company_id != $sessionCompanyId) {
                   $response['message'] = 'Invalid Supplier';
                   break;
                }
            }
            
            $grn->branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $grn->branch_id;
            
            $grn->grn_date = $_POST['grn_date'] ?? $grn->grn_date;
            $grn->due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : $grn->due_date;
            $grn->invoice_number = trim($_POST['invoice_number'] ?? '') ?: null;
            $grn->total_amount = (float)($_POST['total_amount'] ?? $grn->total_amount);
            $grn->tax_amount = (float)($_POST['tax_amount'] ?? $grn->tax_amount);
            $grn->discount_amount = (float)($_POST['discount_amount'] ?? $grn->discount_amount);
            $grn->net_amount = (float)($_POST['net_amount'] ?? $grn->net_amount);
            
            // Allow status update (e.g., save as draft or mark as received)
            // But if marking as received, we must handle it separately or let update() handle status change 
            // and then triggering receive() logic might be needed if moved from draft -> received.
            // For now, let's keep it simple: Editing stays in draft or upgrades to received? 
            // The create logic allows saving as 'received'. 
            // If user changes status to 'received' here while updating, we should trigger receive() logic?
            // Current flow seems to separation creation and "Mark Received". 
            // Let's assume edit keeps it separate or we use the POSTed status.
            $newStatus = $_POST['status'] ?? $grn->status;
            $grn->status = $newStatus;
            
            $grn->notes = trim($_POST['notes'] ?? '') ?: null;

            if ($grn->update()) {
                // Update items
                if (!empty($_POST['items'])) {
                    $items = json_decode($_POST['items'], true);
                    $grn->replaceItems($items);
                }
                
                // If status changed to received during update, we should trigger receive logic?
                // For safety, let's recommend using the explicit "Mark Received" action instead of doing it implicitly here,
                // OR duplicate the receive logic. 
                // The frontend 'Save as Draft' sends status='draft'. 
                // The frontend 'Mark as Received' sends status='received'.
                // If status is 'received', we should probably trigger the receive method logic (setting received_by).
                if ($newStatus === 'received' && $grn->receive($currentEmployee ? $currentEmployee->id : null)) {
                     $response = ['status' => 'success', 'message' => 'GRN updated and marked as received'];
                } else {
                     $response = ['status' => 'success', 'message' => 'GRN updated successfully'];
                }
            } else {
                $response['message'] = 'Failed to update GRN';
            }
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid GRN ID';
                break;
            }
            $grn = new GRN($id);
            
            // Security: ensure GRN belongs to same company
            if ($grn->id && $grn->company_id == $sessionCompanyId) {
                $response = [
                    'status' => 'success',
                    'data'   => [
                'id' => $grn->id,
                        'company_id' => $grn->company_id,
                        'branch_id' => $grn->branch_id,
                        'grn_number' => $grn->grn_number,
                        'supplier_id' => $grn->supplier_id,
                        'supplier_name' => (new Supplier($grn->supplier_id))->supplier_name ?? 'N/A', // Helper to get name
                        'grn_date' => $grn->grn_date,
                        'due_date' => $grn->due_date ?? '',
                        'invoice_number' => $grn->invoice_number ?? '',
                        'total_amount' => $grn->total_amount,
                        'tax_amount' => $grn->tax_amount,
                        'discount_amount' => $grn->discount_amount,
                        'net_amount' => $grn->net_amount,
                        'status' => $grn->status,
                        'notes' => $grn->notes ?? '',
                        'received_by_employee_id' => $grn->received_by_employee_id,
                        'received_by_name' => $grn->received_by_employee_id ? ((new Employee($grn->received_by_employee_id))->first_name . ' ' . (new Employee($grn->received_by_employee_id))->last_name) : 'N/A',
                        'verified_by_employee_id' => $grn->verified_by_employee_id,
                        'items' => $grn->getItems($grn->id)
                    ]
                ];
            } else {
                $response['message'] = 'GRN not found';
            }
            break;

        case 'cancel':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid GRN ID';
                break;
            }
            $grn = new GRN($id);
            
            // Security: ensure GRN belongs to same company
            if (!$grn->id || $grn->company_id != $sessionCompanyId) {
                $response['message'] = 'GRN not found';
                break;
            }
            
            $employee = new Employee();
            $currentEmployee = $employee->getByUserId($_SESSION['id']);
            
            if ($grn->cancel($currentEmployee ? $currentEmployee->id : null)) {
                $response = ['status' => 'success', 'message' => 'GRN cancelled successfully'];
            } else {
                $response['message'] = 'Failed to cancel GRN (Only Draft/Received GRNs can be cancelled)';
            }
            break;

        case 'mark_received':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid GRN ID';
                break;
            }
            $grn = new GRN($id);
            
            // Security: ensure GRN belongs to same company
            if (!$grn->id || $grn->company_id != $sessionCompanyId) {
                $response['message'] = 'GRN not found';
                break;
            }
            
            $employee = new Employee();
            $currentEmployee = $employee->getByUserId($_SESSION['id']);
            
            if ($grn->receive($currentEmployee ? $currentEmployee->id : null)) {
                $response = ['status' => 'success', 'message' => 'GRN marked as received'];
            } else {
                $response['message'] = 'Failed to mark GRN as received';
            }
            break;

        case 'verify':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $response['message'] = 'Invalid GRN ID';
                break;
            }
            $grn = new GRN($id);
            
            // Security: ensure GRN belongs to same company
            if (!$grn->id || $grn->company_id != $sessionCompanyId) {
                $response['message'] = 'GRN not found';
                break;
            }
            
            $employee = new Employee();
            $currentEmployee = $employee->getByUserId($_SESSION['id']);
            
            if ($grn->verify($currentEmployee ? $currentEmployee->id : null)) {
                $response = ['status' => 'success', 'message' => 'GRN verified and stock updated'];
            } else {
                $response['message'] = 'Failed to verify GRN';
            }
            break;

        case 'suppliers':
            $supplier = new Supplier();
            $suppliers = $supplier->getActive($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $suppliers];
            break;

        case 'items':
            $item = new InventoryItem();
            $items = $item->all($sessionCompanyId); // Get all items for selection
            $response = ['status' => 'success', 'data' => $items];
            break;
            
        case 'branches':
            $branch = new Branch();
            $branches = $branch->getActiveByCompany($sessionCompanyId);
            $response = ['status' => 'success', 'data' => $branches];
            break;

        case 'statistics':
            $grn = new GRN();
            $filterBranchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $sessionBranchId;
            $stats = $grn->getStatistics($sessionCompanyId, $filterBranchId);
            if ($stats) {
                $response = ['status' => 'success', 'data' => $stats];
            } else {
                $response['message'] = 'Failed to get statistics';
            }
            break;

        case 'list':
        default:
            $grn = new GRN();
            $filterBranchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $sessionBranchId;
            $grns = $grn->all($sessionCompanyId, $filterBranchId);
            $response = ['status' => 'success', 'data' => $grns];
            break;
    }
} catch (Exception $e) {
    error_log("GRN AJAX Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
