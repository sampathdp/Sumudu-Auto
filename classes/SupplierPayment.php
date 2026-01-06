<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AuditLog.php';
require_once __DIR__ . '/FinancialAccount.php';
require_once __DIR__ . '/SupplierLedger.php';

class SupplierPayment {
    public $id;
    public $company_id;
    public $branch_id;
    public $payment_number;
    public $payment_date;
    public $supplier_id;
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
    public $supplier_name;

    private $db;

    public function __construct($id = null) {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    private function loadById($id) {
        $query = "SELECT sp.*, s.supplier_name 
                  FROM supplier_payments sp
                  JOIN suppliers s ON sp.supplier_id = s.id
                  WHERE sp.id = ?";
        
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
     * Create a new supplier payment
     * @param array $data Payment header data
     * @param array $methods Payment methods w/ account_id [ ['method'=>'cash', 'amount'=>100, 'account_id'=>1], ... ]
     * @param array $allocations Allocations to GRNs [ ['grn_id'=>1, 'amount'=>50], ... ]
     */
    public function create($data, $methods, $allocations) {
        try {
            $this->db->beginTransaction();

            // 1. Generate Payment Number
            $paymentNumber = 'PAY-' . date('Ymd') . '-' . rand(1000, 9999); // Simple generation for now

            // 2. Insert Header
            $query = "INSERT INTO supplier_payments 
                      (company_id, branch_id, payment_number, payment_date, supplier_id, total_amount, allocated_amount, unallocated_amount, status, notes, created_by)
                      VALUES (?, ?, ?, ?, ?, 0, 0, 0, 'draft', ?, ?)";
            
            $this->db->prepareExecute($query, [
                $data['company_id'],
                $data['branch_id'],
                $paymentNumber,
                $data['payment_date'],
                $data['supplier_id'],
                $data['notes'],
                $data['created_by']
            ]);
            
            $paymentId = $this->db->getLastInsertId();
            $this->id = $paymentId;
            $this->payment_number = $paymentNumber;

            // 3. Process Methods
            $totalAmount = 0;
            $methodQuery = "INSERT INTO supplier_payment_methods (company_id, payment_id, payment_method, amount, account_id, reference_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            foreach ($methods as $method) {
                $amount = (float)$method['amount'];
                if ($amount <= 0) continue;
                
                $totalAmount += $amount;
                $this->db->prepareExecute($methodQuery, [
                    $data['company_id'],
                    $paymentId,
                    $method['payment_method'] ?? 'cash', // Fixed: was 'method'
                    $amount,
                    $method['account_id'] ?? null, // Important: Link to Financial Account
                    $method['reference_number'] ?? null, // Fixed: was 'reference'
                    $method['notes'] ?? null
                ]);
            }

            // 4. Process Allocations
            $allocatedTotal = 0;
            $allocQuery = "INSERT INTO supplier_payment_allocations (company_id, payment_id, grn_id, allocated_amount) VALUES (?, ?, ?, ?)";
            $grnUpdateQuery = "UPDATE grn SET amount_paid = amount_paid + ?, payment_status = CASE 
                                WHEN (amount_paid) >= net_amount THEN 'paid' 
                                WHEN (amount_paid) > 0 THEN 'partial' 
                                ELSE 'unpaid' END 
                               WHERE id = ?";

            // Note: In 'draft' status, we usually DON'T update GRN paid status yet, only on confirm.
            // But if we want to lock allocations, we save them.
            // Let's save allocations but NOT update GRN until confirm to verify funds.
            
            foreach ($allocations as $alloc) {
                $amount = (float)($alloc['allocated_amount'] ?? $alloc['amount'] ?? 0);
                if ($amount <= 0) continue;
                
                $allocatedTotal += $amount;
                $this->db->prepareExecute($allocQuery, [
                    $data['company_id'],
                    $paymentId,
                    $alloc['grn_id'],
                    $amount
                ]);
            }

            // 5. Update Header Totals
            $unallocated = $totalAmount - $allocatedTotal;
            $updateHeader = "UPDATE supplier_payments SET total_amount = ?, allocated_amount = ?, unallocated_amount = ? WHERE id = ?";
            $this->db->prepareExecute($updateHeader, [$totalAmount, $allocatedTotal, $unallocated, $paymentId]);

            // Log
            AuditLog::logCreate($data['company_id'], 'supplier_payments', $paymentId, "Created payment $paymentNumber");

            $this->db->commit();
            return $paymentId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Payment Create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Confirm payment - Deduct from Cashbook, Update GRNs, Update Ledger
     */
    public function confirm($userId) {
        if (!$this->id) return false;

        try {
            $this->db->beginTransaction();

            // 1. Get Payment Details with Methods
            $payment = $this->db->prepareSelect("SELECT * FROM supplier_payments WHERE id = ?", [$this->id])->fetch();
            if ($payment['status'] !== 'draft') {
                throw new Exception("Payment already processed");
            }

            $methods = $this->db->prepareSelect("SELECT * FROM supplier_payment_methods WHERE payment_id = ?", [$this->id])->fetchAll();
            $allocations = $this->db->prepareSelect("SELECT * FROM supplier_payment_allocations WHERE payment_id = ?", [$this->id])->fetchAll();

            require_once __DIR__ . '/FinancialTransaction.php';
            $financialTx = new FinancialTransaction();

            // 2. Deduct from Financial Accounts
            foreach ($methods as $method) {
                if (!empty($method['account_id'])) {
                    $description = "Supplier Payment: " . $payment['payment_number'] . " to " . $this->supplier_name;
                    
                    $result = $financialTx->recordExpense(
                        $payment['company_id'],
                        $method['account_id'],
                        $method['amount'],
                        $description,
                        'supplier_payment',
                        $this->id,
                        $payment['payment_date'],
                        $userId,
                        'Supplier Payment',
                        $payment['branch_id']
                    );
                    
                    if (!$result['success']) {
                        throw new Exception("Financial Account Error: " . ($result['error'] ?? 'Unknown'));
                    }
                }
            }

            // 3. Update GRN status (Apply allocations)
            $grnUpdate = "UPDATE grn SET amount_paid = amount_paid + ?, payment_status = CASE 
                          WHEN amount_paid >= net_amount THEN 'paid' 
                          WHEN amount_paid > 0 THEN 'partial' 
                          ELSE payment_status END 
                          WHERE id = ?";
            
            foreach ($allocations as $alloc) {
                // Check if GRN exists/valid first? Assuming yes for now.
                $this->db->prepareExecute($grnUpdate, [$alloc['allocated_amount'], $alloc['grn_id']]);
            }

            // 4. Update Supplier Ledger using SupplierLedger class
            $supplierLedger = new SupplierLedger();
            $supplierLedger->recordPayment(
                $payment['company_id'],
                $payment['supplier_id'],
                $this->id,
                $payment['payment_number'],
                $payment['total_amount'],
                $payment['payment_date'],
                $userId
            );

            // 5. Update Status
            $this->db->prepareExecute("UPDATE supplier_payments SET status = 'confirmed', approved_by = ?, approved_at = NOW() WHERE id = ?", [$userId, $this->id]);
            
            AuditLog::logUpdate($payment['company_id'], 'supplier_payments', $this->id, 'status', 'draft', 'confirmed', "Payment Confirmed");

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Payment Confirm Error: " . $e->getMessage());
            return false;
        }
    }

    public function cancel($reason) {
        // Reverse everything done in confirm... complex.
        // For now, assume we can only cancel 'draft' or implement reversal later.
        if ($this->status === 'draft') {
             $this->db->prepareExecute("UPDATE supplier_payments SET status = 'cancelled', notes = CONCAT(notes, ' | Cancelled: ', ?) WHERE id = ?", [$reason, $this->id]);
             return true;
        }
        return false;
    }

    public function getMethods($paymentId) {
        $stmt = $this->db->prepareSelect("SELECT pm.*, fa.account_name 
                                          FROM supplier_payment_methods pm
                                          LEFT JOIN financial_accounts fa ON pm.account_id = fa.id
                                          WHERE pm.payment_id = ?", [$paymentId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function getAllocations($paymentId) {
        $stmt = $this->db->prepareSelect("SELECT spa.*, g.grn_number, g.grn_date, g.net_amount 
                                          FROM supplier_payment_allocations spa
                                          JOIN grn g ON spa.grn_id = g.id
                                          WHERE spa.payment_id = ?", [$paymentId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public static function getOutstandingGRNs($companyId, $supplierId) {
        $db = new Database();
        $query = "SELECT id, grn_number, grn_date, due_date, net_amount, amount_paid, (net_amount - amount_paid) as balance 
                  FROM grn 
                  WHERE company_id = ? AND supplier_id = ? AND payment_status != 'paid' AND status = 'verified'
                  ORDER BY due_date ASC";
        
        $stmt = $db->prepareSelect($query, [$companyId, $supplierId]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public static function getSupplierBalance($companyId, $supplierId) {
        return SupplierLedger::getBalance($companyId, $supplierId);
    }

    public static function getSupplierStatement($companyId, $supplierId, $startDate = null, $endDate = null) {
        return SupplierLedger::getStatement($companyId, $supplierId, $startDate, $endDate);
    }

    public static function all($companyId, $status = null) {
        $db = new Database();
        $query = "SELECT sp.*, s.supplier_name 
                  FROM supplier_payments sp
                  JOIN suppliers s ON sp.supplier_id = s.id
                  WHERE sp.company_id = ?";
        $params = [$companyId];
        
        if ($status) {
            $query .= " AND sp.status = ?";
            $params[] = $status; 
        }
        
        $query .= " ORDER BY sp.payment_date DESC";
        $stmt = $db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public static function getBySupplier($companyId, $supplierId) {
        $db = new Database();
        $query = "SELECT * FROM supplier_payments WHERE company_id = ? AND supplier_id = ? ORDER BY payment_date DESC";
        $stmt = $db->prepareSelect($query, [$companyId, $supplierId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public static function getStats($companyId) {
        $db = new Database();
        $stats = [];
        // Total Paid
        $stmt = $db->prepareSelect("SELECT SUM(total_amount) as total FROM supplier_payments WHERE company_id = ? AND status = 'confirmed'", [$companyId]);
        $stats['total_paid'] = $stmt ? ($stmt->fetch()['total'] ?? 0) : 0;
        
        // This Month
        $stmt = $db->prepareSelect("SELECT SUM(total_amount) as total FROM supplier_payments WHERE company_id = ? AND status = 'confirmed' AND payment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$companyId]);
        $stats['this_month'] = $stmt ? ($stmt->fetch()['total'] ?? 0) : 0;
        
        return $stats;
    }
}
