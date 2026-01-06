<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AuditLog.php';
require_once __DIR__ . '/FinancialAccount.php';
require_once __DIR__ . '/CustomerLedger.php';

class CustomerPayment {
    public $id;
    public $company_id;
    public $branch_id;
    public $payment_number;
    public $payment_date;
    public $customer_id;
    public $total_amount;
    public $allocated_amount;
    public $unallocated_amount;
    public $status;
    public $notes;
    public $created_by;
    public $approved_by;
    public $approved_at;
    public $created_at;
    public $updated_at;

    // Joined fields
    public $customer_name;

    private $db;

    public function __construct($id = null) {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    private function loadById($id) {
        $query = "SELECT cp.*, c.name as customer_name 
                  FROM customer_payments cp
                  JOIN customers c ON cp.customer_id = c.id
                  WHERE cp.id = ?";
        
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt && $row = $stmt->fetch()) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Create a new customer payment
     * @param array $data Payment header data
     * @param array $methods Payment methods w/ account_id [ ['payment_method'=>'cash', 'amount'=>100, 'account_id'=>1], ... ]
     * @param array $allocations Allocations to Invoices [ ['invoice_id'=>1, 'allocated_amount'=>50], ... ]
     */
    public function create($data, $methods, $allocations) {
        try {
            $this->db->beginTransaction();

            // 1. Generate Payment Number
            $paymentNumber = 'CPAY-' . date('Ymd') . '-' . rand(1000, 9999);

            // 2. Insert Header
            $query = "INSERT INTO customer_payments 
                      (company_id, branch_id, payment_number, payment_date, customer_id, total_amount, allocated_amount, unallocated_amount, status, notes, created_by)
                      VALUES (?, ?, ?, ?, ?, 0, 0, 0, 'draft', ?, ?)";
            
            $this->db->prepareExecute($query, [
                $data['company_id'],
                $data['branch_id'] ?? null,
                $paymentNumber,
                $data['payment_date'],
                $data['customer_id'],
                $data['notes'] ?? null,
                $data['created_by']
            ]);
            
            $paymentId = $this->db->getLastInsertId();
            $this->id = $paymentId;
            $this->payment_number = $paymentNumber;

            // 3. Process Methods
            $totalAmount = 0;
            $methodQuery = "INSERT INTO customer_payment_methods (company_id, payment_id, payment_method, amount, account_id, reference_number, bank_name, cheque_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            foreach ($methods as $method) {
                $amount = (float)($method['amount'] ?? 0);
                if ($amount <= 0) continue;
                
                $totalAmount += $amount;
                $this->db->prepareExecute($methodQuery, [
                    $data['company_id'],
                    $paymentId,
                    $method['payment_method'] ?? 'cash',
                    $amount,
                    !empty($method['account_id']) ? (int)$method['account_id'] : null,
                    !empty($method['reference_number']) ? $method['reference_number'] : null,
                    !empty($method['bank_name']) ? $method['bank_name'] : null,
                    !empty($method['cheque_date']) ? $method['cheque_date'] : null,
                    !empty($method['notes']) ? $method['notes'] : null
                ]);
            }

            // 4. Process Allocations
            $allocatedTotal = 0;
            $allocQuery = "INSERT INTO customer_payment_allocations (company_id, payment_id, invoice_id, allocated_amount) VALUES (?, ?, ?, ?)";

            foreach ($allocations as $alloc) {
                $amount = (float)($alloc['allocated_amount'] ?? $alloc['amount'] ?? 0);
                if ($amount <= 0) continue;
                
                $allocatedTotal += $amount;
                $this->db->prepareExecute($allocQuery, [
                    $data['company_id'],
                    $paymentId,
                    $alloc['invoice_id'],
                    $amount
                ]);
            }

            // 5. Update Header Totals
            $unallocated = $totalAmount - $allocatedTotal;
            $updateHeader = "UPDATE customer_payments SET total_amount = ?, allocated_amount = ?, unallocated_amount = ? WHERE id = ?";
            $this->db->prepareExecute($updateHeader, [$totalAmount, $allocatedTotal, $unallocated, $paymentId]);

            // Log
            AuditLog::logCreate($data['company_id'], 'customer_payments', $paymentId, "Created customer payment $paymentNumber");

            $this->db->commit();
            return $paymentId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Customer Payment Create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Confirm payment - Add to Cashbook, Update Invoices, Update Ledger
     */
    public function confirm($userId) {
        if (!$this->id) return false;

        try {
            $this->db->beginTransaction();

            // 1. Get Payment Details with Methods
            $payment = $this->db->prepareSelect("SELECT * FROM customer_payments WHERE id = ?", [$this->id])->fetch();
            if ($payment['status'] !== 'draft') {
                throw new Exception("Payment already processed");
            }

            $methods = $this->db->prepareSelect("SELECT * FROM customer_payment_methods WHERE payment_id = ?", [$this->id])->fetchAll();
            $allocations = $this->db->prepareSelect("SELECT * FROM customer_payment_allocations WHERE payment_id = ?", [$this->id])->fetchAll();

            require_once __DIR__ . '/FinancialTransaction.php';
            $financialTx = new FinancialTransaction();

            // 2. Add to Financial Accounts (Income - Money In)
            foreach ($methods as $method) {
                if (!empty($method['account_id'])) {
                    $description = "Customer Payment: " . $payment['payment_number'] . " from " . $this->customer_name;
                    
                    $result = $financialTx->recordIncome(
                        $payment['company_id'],
                        $method['account_id'],
                        $method['amount'],
                        $description,
                        'customer_payment',
                        $this->id,
                        $payment['payment_date'],
                        $userId,
                        'Customer Payment',
                        $payment['branch_id']
                    );
                    
                    if (!$result['success']) {
                        throw new Exception("Financial Account Error: " . ($result['error'] ?? 'Unknown'));
                    }
                }
            }

            // 3. Update Invoice payment status (Apply allocations)
            // We need to track paid amounts on invoices - add amount_paid column or use separate tracking
            // For simplicity, let's add bill_type context: Credit invoices need payment tracking
            $invoiceUpdate = "UPDATE invoices SET 
                              payment_method = COALESCE(payment_method, 'cash'),
                              payment_date = COALESCE(payment_date, NOW())
                              WHERE id = ? AND bill_type = 'credit'";
            
            foreach ($allocations as $alloc) {
                // For credit invoices, mark as paid when payment is received
                // This is simplified - in a full system we'd track partial payments
                $this->db->prepareExecute($invoiceUpdate, [$alloc['invoice_id']]);
            }

            // 4. Update Customer Ledger using CustomerLedger class
            $customerLedger = new CustomerLedger();
            $customerLedger->recordPayment(
                $payment['company_id'],
                $payment['customer_id'],
                $this->id,
                $payment['payment_number'],
                $payment['total_amount'],
                $payment['payment_date'],
                $userId
            );

            // 5. Update Status
            $this->db->prepareExecute("UPDATE customer_payments SET status = 'confirmed', approved_by = ?, approved_at = NOW() WHERE id = ?", [$userId, $this->id]);
            
            AuditLog::logUpdate($payment['company_id'], 'customer_payments', $this->id, 'status', 'draft', 'confirmed', "Customer Payment Confirmed");

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Customer Payment Confirm Error: " . $e->getMessage());
            return false;
        }
    }

    public function cancel($reason) {
        if ($this->status === 'draft') {
             $this->db->prepareExecute("UPDATE customer_payments SET status = 'cancelled', notes = CONCAT(IFNULL(notes, ''), ' | Cancelled: ', ?) WHERE id = ?", [$reason, $this->id]);
             return true;
        }
        return false;
    }

    public function getMethods($paymentId) {
        $stmt = $this->db->prepareSelect("SELECT pm.*, fa.account_name 
                                          FROM customer_payment_methods pm
                                          LEFT JOIN financial_accounts fa ON pm.account_id = fa.id
                                          WHERE pm.payment_id = ?", [$paymentId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function getAllocations($paymentId) {
        $stmt = $this->db->prepareSelect("SELECT cpa.*, i.invoice_number, i.created_at as invoice_date, i.total_amount as invoice_total 
                                          FROM customer_payment_allocations cpa
                                          JOIN invoices i ON cpa.invoice_id = i.id
                                          WHERE cpa.payment_id = ?", [$paymentId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public static function getOutstandingInvoices($companyId, $customerId) {
        $db = new Database();
        // Get credit invoices that don't have a payment method set (unpaid)
        $query = "SELECT id, invoice_number, created_at as invoice_date, total_amount, 
                         IFNULL((SELECT SUM(allocated_amount) FROM customer_payment_allocations WHERE invoice_id = i.id), 0) as amount_paid,
                         (total_amount - IFNULL((SELECT SUM(allocated_amount) FROM customer_payment_allocations WHERE invoice_id = i.id), 0)) as balance
                  FROM invoices i
                  WHERE company_id = ? AND customer_id = ? AND bill_type = 'credit' AND status = 'active'
                  HAVING balance > 0
                  ORDER BY created_at ASC";
        
        $stmt = $db->prepareSelect($query, [$companyId, $customerId]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public static function getCustomerBalance($companyId, $customerId) {
        return CustomerLedger::getBalance($companyId, $customerId);
    }

    public static function getCustomerStatement($companyId, $customerId, $startDate = null, $endDate = null) {
        return CustomerLedger::getStatement($companyId, $customerId, $startDate, $endDate);
    }

    public static function all($companyId, $status = null) {
        $db = new Database();
        $query = "SELECT cp.*, c.name as customer_name 
                  FROM customer_payments cp
                  JOIN customers c ON cp.customer_id = c.id
                  WHERE cp.company_id = ?";
        $params = [$companyId];
        
        if ($status) {
            $query .= " AND cp.status = ?";
            $params[] = $status; 
        }
        
        $query .= " ORDER BY cp.payment_date DESC";
        $stmt = $db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public static function getByCustomer($companyId, $customerId) {
        $db = new Database();
        $query = "SELECT * FROM customer_payments WHERE company_id = ? AND customer_id = ? ORDER BY payment_date DESC";
        $stmt = $db->prepareSelect($query, [$companyId, $customerId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public static function getStats($companyId) {
        $db = new Database();
        $stats = [];
        
        // Total Received
        $stmt = $db->prepareSelect("SELECT SUM(total_amount) as total FROM customer_payments WHERE company_id = ? AND status = 'confirmed'", [$companyId]);
        $stats['total_received'] = $stmt ? ($stmt->fetch()['total'] ?? 0) : 0;
        
        // This Month
        $stmt = $db->prepareSelect("SELECT SUM(total_amount) as total FROM customer_payments WHERE company_id = ? AND status = 'confirmed' AND payment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$companyId]);
        $stats['this_month'] = $stmt ? ($stmt->fetch()['total'] ?? 0) : 0;

        // Pending (Draft)
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count, SUM(total_amount) as total FROM customer_payments WHERE company_id = ? AND status = 'draft'", [$companyId]);
        $row = $stmt ? $stmt->fetch() : null;
        $stats['pending_count'] = $row ? ($row['count'] ?? 0) : 0;
        $stats['pending_amount'] = $row ? ($row['total'] ?? 0) : 0;
        
        return $stats;
    }
}
