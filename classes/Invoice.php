<?php
require_once __DIR__ . '/FinancialAccount.php';
require_once __DIR__ . '/CustomerLedger.php';

class Invoice
{
    public $id;
    public $company_id;
    public $branch_id;
    public $service_id;
    public $invoice_number;
    public $customer_id;
    public $customer_name;
    public $subtotal;
    public $tax_amount;
    public $discount_amount;
    public $total_amount;
    public $payment_method;
    public $payment_date;
    public $account_id; // Added: Deposit to Account
    public $bill_type; // Added: cash or credit
    public $status;
    public $created_at;
    public $error; // Added to capture errors
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load invoice by ID
     */
    private function loadById($id) {
        $query = "SELECT * FROM invoices WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                foreach ($row as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
            }
        }
    }

    /**
     * Create a new invoice.
     */
    public function create()
    {
        if (empty($this->company_id)) {
            $this->error = "Company ID is missing";
            error_log("Invoice Creation Failed: Company ID missing");
            return false;
        }

        if (empty($this->invoice_number)) {
            $this->invoice_number = $this->generateInvoiceNumber($this->company_id);
        }

        $dataToLog = [
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'service_id' => $this->service_id,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->customer_id,
            'total_amount' => $this->total_amount
        ];
        error_log("Attempting to create invoice: " . json_encode($dataToLog));

        // Add account_id to insert
        $query = "INSERT INTO invoices (company_id, branch_id, service_id, invoice_number, customer_id, customer_name, subtotal, tax_amount, 
                  discount_amount, total_amount, payment_method, payment_date, account_id, bill_type, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->branch_id ?? null,
            $this->service_id ?? null,
            $this->invoice_number,
            $this->customer_id ?? 0,
            $this->customer_name ?? '',
            $this->subtotal ?? 0,
            $this->tax_amount ?? 0,
            $this->discount_amount ?? 0,
            $this->total_amount ?? 0,
            $this->payment_method ?? null,
            $this->payment_date ?? null,
            !empty($this->account_id) ? (int)$this->account_id : null, 
            $this->bill_type ?? 'cash', // Added
            $this->status ?? 'active'
        ]);

        if (!$success) {
            $this->error = $this->db->lastErrorMessage ?? "Database error";
            error_log("Invoice Creation DB Error: " . $this->error);
            return false;
        }

        if ($success) {
            $this->id = $this->db->getLastInsertId();
            error_log("Invoice Created Successfully. ID: " . $this->id);
            
            // Determine bill type - default to cash if not set
            $billType = $this->bill_type ?? 'cash';
            
            if ($billType === 'cash' && $this->total_amount > 0) {
                // Cash Sale - Record Financial Transaction
                // If no account_id provided, try to get default cash account
                if (empty($this->account_id)) {
                    $this->account_id = $this->getDefaultCashAccount();
                }
                
                if (!empty($this->account_id)) {
                    $this->recordFinancialTransaction();
                } else {
                    error_log("Invoice Created but no default cash account found for transaction. Company: " . $this->company_id);
                }
            } elseif ($billType === 'credit' && !empty($this->customer_id) && $this->total_amount > 0) {
                // Credit Sale - Record Customer Ledger entry (customer owes money)
                $customerLedger = new CustomerLedger();
                $customerLedger->recordInvoice(
                    $this->company_id,
                    $this->customer_id,
                    $this->id,
                    $this->invoice_number,
                    $this->total_amount,
                    date('Y-m-d'),
                    $_SESSION['id'] ?? null,
                    null
                );
            }
        }
        return $success;
    }
    
    /**
     * Generate a unique invoice number for the company
     */
    public function generateInvoiceNumber($companyId) {
        $prefix = "INV";
        $date = date('Ymd');
        
        // Get last invoice number for today
        $query = "SELECT invoice_number FROM invoices WHERE company_id = ? AND invoice_number LIKE ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepareSelect($query, [$companyId, $prefix . $date . '%']);
        
        $sequence = 1;
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                // Extract sequence number from last invoice
                $lastNumber = $row['invoice_number'];
                $sequence = (int)substr($lastNumber, strlen($prefix . $date)) + 1;
            }
        }
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get default cash account for the company
     */
    private function getDefaultCashAccount() {
        $stmt = $this->db->prepareSelect(
            "SELECT id FROM financial_accounts 
             WHERE company_id = ? AND account_type = 'cash' AND is_active = 1 
             ORDER BY is_default DESC, id ASC LIMIT 1",
            [$this->company_id]
        );
        if ($stmt) {
            $row = $stmt->fetch();
            return $row ? $row['id'] : null;
        }
        return null;
    }
    
    private function recordFinancialTransaction() {
        if (!$this->account_id || !$this->total_amount) return;
        
        require_once __DIR__ . '/FinancialTransaction.php';
        
        $financialTx = new FinancialTransaction();
        $description = "Invoice Payment: " . $this->invoice_number . " - " . $this->customer_name;
        
        $financialTx->recordIncome(
            $this->company_id,
            $this->account_id,
            $this->total_amount,
            $description,
            'invoice',
            $this->id,
            $this->payment_date ?? date('Y-m-d'),
            $_SESSION['id'] ?? 0,
            'Sales',
            $this->branch_id
        );
    }

    /**
     * Update invoice.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        $query = "UPDATE invoices SET subtotal = ?, tax_amount = ?, discount_amount = ?, 
                  total_amount = ?, payment_method = ?, payment_date = ?, account_id = ?, branch_id = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->subtotal,
            $this->tax_amount ?? 0,
            $this->discount_amount ?? 0,
            $this->total_amount,
            $this->payment_method ?? null,
            $this->payment_date ?? null,
            !empty($this->account_id) ? (int)$this->account_id : null,
            $this->branch_id ?? null,
            $this->id
        ]);
        
        // Note: Updating invoice usually shouldn't re-trigger financial transaction automatically to avoid dups.
        // Complex logic needed if payment details change. For now, assume update is corrections.
    }

    /**
     * Delete invoice.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        // Manual cleanup of invoice items first
        $this->db->prepareExecute("DELETE FROM invoice_items WHERE invoice_id = ?", [$this->id]);
        
        $query = "DELETE FROM invoices WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Cancel invoice.
     */
    public function cancel()
    {
        if (!$this->id) {
            return false;
        }
        $query = "UPDATE invoices SET status = 'cancelled' WHERE id = ?";
        $success = $this->db->prepareExecute($query, [$this->id]);
        if ($success) {
            // Revert stock if any inventory items were used?
            // Usually cancellation reverses stock. Let's implement that for robustness.
            $items = $this->getItems($this->id);
            foreach ($items as $item) {
                if ($item['item_type'] === 'inventory' && !empty($item['item_id'])) {
                    // Update stock: ADD back
                    $updateStock = "UPDATE inventory_items SET current_stock = current_stock + ? WHERE id = ?";
                    $this->db->prepareExecute($updateStock, [$item['quantity'], $item['item_id']]);
                    
                    // Add stock movement record for return/cancellation
                    // We need current balance
                    $balanceQuery = "SELECT current_stock, unit_cost FROM inventory_items WHERE id = ?";
                    $stmt = $this->db->prepareSelect($balanceQuery, [$item['item_id']]);
                    $row = $stmt->fetch();
                    
                    $movement = "INSERT INTO stock_movements (company_id, item_id, movement_type, reference_type, reference_id, 
                                quantity_change, balance_after, unit_cost, notes) 
                                VALUES (?, ?, 'return', 'invoice', ?, ?, ?, ?, ?)";
                    $this->db->prepareExecute($movement, [
                        $this->company_id,
                        $item['item_id'],
                        $this->id,
                        $item['quantity'],
                        $row['current_stock'],
                        $item['unit_price'], // or row['unit_cost']
                        "Invoice #{$this->invoice_number} cancelled"
                    ]);
                }
            }
            
            $this->status = 'cancelled';
        }
        return $success;
    }

    /**
     * Get all invoices for a company.
     */
    public function all($companyId, $branchId = null)
    {
        $query = "SELECT i.*, s.job_number, 
                  CASE WHEN i.customer_name IS NOT NULL AND i.customer_name != '' THEN i.customer_name ELSE c.name END as customer_name,
                  v.registration_number, b.branch_name
                  FROM invoices i
                  LEFT JOIN services s ON i.service_id = s.id
                  LEFT JOIN customers c ON i.customer_id = c.id
                  LEFT JOIN vehicles v ON s.vehicle_id = v.id
                  LEFT JOIN branches b ON i.branch_id = b.id
                  WHERE i.company_id = ?";
        
        $params = [$companyId];
        
        if ($branchId) {
            $query .= " AND i.branch_id = ?";
            $params[] = $branchId;
        }

        $query .= " ORDER BY i.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get invoice items.
     */
    public function getItems($invoiceId)
    {
        $query = "SELECT ii.*, i.item_name, i.item_code
                  FROM invoice_items ii
                  LEFT JOIN inventory_items i ON ii.item_id = i.id
                  WHERE ii.invoice_id = ?";
        $stmt = $this->db->prepareSelect($query, [$invoiceId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Add item to invoice.
     */
    public function addItem($itemData)
    {
        if (!$this->id || empty($this->company_id)) {
            return false;
        }

        // If item type is inventory, check stock availability first
        if ($itemData['item_type'] === 'inventory' && !empty($itemData['item_id'])) {
            $stockCheck = "SELECT current_stock, unit_cost FROM inventory_items WHERE id = ? AND company_id = ?";
            $stmt = $this->db->prepareSelect($stockCheck, [$itemData['item_id'], $this->company_id]);
            $stockRow = $stmt->fetch();
            
            if (!$stockRow || $stockRow['current_stock'] < $itemData['quantity']) {
                // Insufficient stock or item not found in company
                return false;
            }
            $unitCost = $stockRow['unit_cost'];
        }

        $query = "INSERT INTO invoice_items (company_id, invoice_id, item_type, item_id, description, 
                  quantity, unit_price, total_price, tax_rate, tax_amount) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->id,
            $itemData['item_type'],
            $itemData['item_id'] ?? null,
            $itemData['description'],
            $itemData['quantity'],
            $itemData['unit_price'],
            $itemData['total_price'],
            $itemData['tax_rate'] ?? 0,
            $itemData['tax_amount'] ?? 0
        ]);

        // If item type is inventory, deduct stock and record movement
        if ($success && $itemData['item_type'] === 'inventory' && !empty($itemData['item_id'])) {
            // Deduct from inventory stock
            $updateStock = "UPDATE inventory_items SET current_stock = current_stock - ? WHERE id = ? AND company_id = ?";
            $this->db->prepareExecute($updateStock, [$itemData['quantity'], $itemData['item_id'], $this->company_id]);

            // Get current balance after deduction
            $balanceQuery = "SELECT current_stock FROM inventory_items WHERE id = ?";
            $stmt = $this->db->prepareSelect($balanceQuery, [$itemData['item_id']]);
            $row = $stmt->fetch();

            // Record stock movement (negative quantity for usage)
            // Record stock movement (negative quantity for usage)
            $movement = "INSERT INTO stock_movements (company_id, item_id, movement_type, reference_type, reference_id, 
                        quantity_change, balance_after, unit_cost, created_by_employee_id, notes) 
                        VALUES (?, ?, 'usage', 'invoice', ?, ?, ?, ?, ?, ?)";
            
            $employeeId = null;
            if (isset($_SESSION['id'])) {
                $employee = new Employee();
                $curEmp = $employee->getByUserId($_SESSION['id']);
                $employeeId = $curEmp ? $curEmp->id : null;
            }

            $this->db->prepareExecute($movement, [
                $this->company_id,
                $itemData['item_id'],
                $this->id,
                -$itemData['quantity'], // Negative for stock OUT
                $row['current_stock'],
                $unitCost ?? 0,
                $employeeId,
                'Used in invoice #' . $this->invoice_number
            ]);
        }

        return $success;
    }

    /**
     * Remove item from invoice and restore stock.
     */
    public function removeItem($itemId)
    {
        if (!$this->id) {
            return false;
        }

        // Get item details first
        $query = "SELECT * FROM invoice_items WHERE id = ? AND invoice_id = ?";
        $stmt = $this->db->prepareSelect($query, [$itemId, $this->id]);
        $item = $stmt->fetch();

        if (!$item) {
            return false;
        }

        // Restore stock if inventory
        if ($item['item_type'] === 'inventory' && !empty($item['item_id'])) {
            $updateStock = "UPDATE inventory_items SET current_stock = current_stock + ? WHERE id = ? AND company_id = ?";
            $this->db->prepareExecute($updateStock, [$item['quantity'], $item['item_id'], $this->company_id]);

            // Record movement
            $balanceQuery = "SELECT current_stock FROM inventory_items WHERE id = ?";
            $balStmt = $this->db->prepareSelect($balanceQuery, [$item['item_id']]);
            $balRow = $balStmt->fetch();

            $movement = "INSERT INTO stock_movements (company_id, item_id, movement_type, reference_type, reference_id, 
                        quantity_change, balance_after, unit_cost, notes) 
                        VALUES (?, ?, 'return', 'invoice_edit', ?, ?, ?, ?, ?)";
            
            $this->db->prepareExecute($movement, [
                $this->company_id,
                $item['item_id'],
                $this->id,
                $item['quantity'],
                $balRow['current_stock'] ?? 0,
                $item['unit_price'],
                "Removed from invoice #{$this->invoice_number}"
            ]);
        }

        // Delete the item
        $delQuery = "DELETE FROM invoice_items WHERE id = ?";
        return $this->db->prepareExecute($delQuery, [$itemId]);
    }

    /**
     * Create invoice from completed service
     */
    public function createFromService($serviceId, $companyId, $branchId)
    {
        try {
            // Check if invoice already exists for this service
            $existing = "SELECT id FROM invoices WHERE service_id = ? AND company_id = ?";
            $stmt = $this->db->prepareSelect($existing, [$serviceId, $companyId]);
            if ($stmt && $stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Invoice already exists for this service'
                ];
            }

            // Get service details
            $serviceQuery = "SELECT s.*, sp.package_name, sp.base_price, c.name as customer_name 
                           FROM services s
                           LEFT JOIN service_packages sp ON s.package_id = sp.id
                           LEFT JOIN customers c ON s.customer_id = c.id
                           WHERE s.id = ? AND s.company_id = ?";
            $stmt = $this->db->prepareSelect($serviceQuery, [$serviceId, $companyId]);
            $service = $stmt ? $stmt->fetch() : null;

            if (!$service) {
                return [
                    'success' => false,
                    'message' => 'Service not found'
                ];
            }

            // Init invoice
            $this->company_id = $companyId;
            $this->branch_id = $branchId;
            $this->invoice_number = $this->generateInvoiceNumber($companyId);
            $this->service_id = $serviceId;
            $this->customer_id = $service['customer_id'];
            $this->customer_name = $service['customer_name'] ?? '';
            $this->subtotal = $service['total_amount'];
            $this->tax_amount = 0; 
            $this->discount_amount = 0;
            $this->total_amount = $service['total_amount'];
            $this->status = 'active';
            
            // NOTE: When creating from service, payment might not be done yet. 
            // So we don't set payment_method or account_id here usually, allowing later payment.
            // But if user wants to fast-track... for now leave as is.

            if (!$this->create()) {
                error_log("createFromService: Invoice create() failed. Error: " . ($this->error ?? 'Unknown'));
                return [
                    'success' => false,
                    'message' => 'Failed to create invoice: ' . ($this->error ?? 'Unknown error')
                ];
            }

            // Add service package as invoice item
            $itemData = [
                'item_type' => 'service',
                'item_id' => null,
                'description' => $service['package_name'] ?? 'Service Package',
                'quantity' => 1,
                'unit_price' => $service['total_amount'],
                'total_price' => $service['total_amount'],
                'tax_rate' => 0,
                'tax_amount' => 0
            ];

            if (!$this->addItem($itemData)) {
                error_log("createFromService: Failed to add item. Error: " . ($this->db->lastErrorMessage ?? 'Unknown'));
                $this->delete();
                return [
                    'success' => false,
                    'message' => 'Failed to add invoice item'
                ];
            }

            return [
                'success' => true,
                'message' => 'Invoice created successfully',
                'invoice_id' => $this->id,
                'invoice_number' => $this->invoice_number
            ];

        } catch (Exception $e) {
            error_log("Exception in createFromService: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error creating invoice: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get invoice statistics for a company
     */
    public function getStatistics($companyId, $branchId = null)
    {
        $query = "SELECT 
            COUNT(*) as total_invoices,
            SUM(CASE WHEN status != 'cancelled' AND payment_date IS NOT NULL THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status != 'cancelled' AND payment_date IS NULL THEN 1 ELSE 0 END) as unpaid_count,
            SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as total_revenue
            FROM invoices WHERE company_id = ?";
        
        $params = [$companyId];
        
        if ($branchId) {
            $query .= " AND branch_id = ?";
            $params[] = $branchId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Count invoices for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM invoices WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
