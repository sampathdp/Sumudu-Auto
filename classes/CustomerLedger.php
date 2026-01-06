<?php
/**
 * CustomerLedger Class
 * Manages customer balance tracking and ledger entries
 * 
 * Usage:
 * - Invoice (Credit Bill) creates DEBIT (customer owes money)
 * - Customer Payment creates CREDIT (customer pays money)
 * - Positive balance = Customer owes us
 * - Negative balance = We owe customer (overpayment/advance)
 */
require_once __DIR__ . '/Database.php';

class CustomerLedger {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Record an invoice entry (Debit - customer owes more)
     */
    public function recordInvoice($companyId, $customerId, $invoiceId, $invoiceNumber, $amount, $invoiceDate, $createdBy = null, $notes = null) {
        $prevBalance = self::getBalance($companyId, $customerId);
        $newBalance = $prevBalance + (float)$amount; // Invoice increases what customer owes
        
        return $this->db->prepareExecute(
            "INSERT INTO customer_ledger 
            (company_id, customer_id, transaction_date, transaction_type, reference_id, reference_number, debit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'invoice', ?, ?, ?, ?, ?, ?)",
            [
                $companyId,
                $customerId,
                $invoiceDate,
                $invoiceId,
                $invoiceNumber,
                $amount,
                $newBalance,
                $notes,
                $createdBy
            ]
        );
    }
    
    /**
     * Record a payment entry (Credit - customer pays off)
     */
    public function recordPayment($companyId, $customerId, $paymentId, $paymentNumber, $amount, $paymentDate, $createdBy = null, $notes = null) {
        $prevBalance = self::getBalance($companyId, $customerId);
        $newBalance = $prevBalance - (float)$amount; // Payment decreases what customer owes
        
        return $this->db->prepareExecute(
            "INSERT INTO customer_ledger 
            (company_id, customer_id, transaction_date, transaction_type, reference_id, reference_number, credit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'payment', ?, ?, ?, ?, ?, ?)",
            [
                $companyId,
                $customerId,
                $paymentDate,
                $paymentId,
                $paymentNumber,
                $amount,
                $newBalance,
                $notes,
                $createdBy
            ]
        );
    }
    
    /**
     * Record a refund entry (Debit - we owe customer more, or reverse a payment)
     */
    public function recordRefund($companyId, $customerId, $referenceId, $referenceNumber, $amount, $refundDate, $createdBy = null, $notes = null) {
        $prevBalance = self::getBalance($companyId, $customerId);
        $newBalance = $prevBalance - (float)$amount; // Refund = we give back money
        
        return $this->db->prepareExecute(
            "INSERT INTO customer_ledger 
            (company_id, customer_id, transaction_date, transaction_type, reference_id, reference_number, credit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'refund', ?, ?, ?, ?, ?, ?)",
            [
                $companyId,
                $customerId,
                $refundDate,
                $referenceId,
                $referenceNumber,
                $amount,
                $newBalance,
                $notes,
                $createdBy
            ]
        );
    }
    
    /**
     * Record an adjustment entry (can be debit or credit)
     */
    public function recordAdjustment($companyId, $customerId, $debitAmount, $creditAmount, $adjustmentDate, $createdBy = null, $notes = null) {
        $prevBalance = self::getBalance($companyId, $customerId);
        $newBalance = $prevBalance + (float)$debitAmount - (float)$creditAmount;
        
        return $this->db->prepareExecute(
            "INSERT INTO customer_ledger 
            (company_id, customer_id, transaction_date, transaction_type, debit_amount, credit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'adjustment', ?, ?, ?, ?, ?)",
            [
                $companyId,
                $customerId,
                $adjustmentDate,
                $debitAmount,
                $creditAmount,
                $newBalance,
                $notes,
                $createdBy
            ]
        );
    }
    
    /**
     * Record opening balance
     */
    public function recordOpeningBalance($companyId, $customerId, $balance, $asOfDate, $createdBy = null, $notes = 'Opening Balance') {
        // Opening balance is typically a debit if customer owes, credit if we owe
        $debit = $balance > 0 ? $balance : 0;
        $credit = $balance < 0 ? abs($balance) : 0;
        
        return $this->db->prepareExecute(
            "INSERT INTO customer_ledger 
            (company_id, customer_id, transaction_date, transaction_type, debit_amount, credit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'opening', ?, ?, ?, ?, ?)",
            [
                $companyId,
                $customerId,
                $asOfDate,
                $debit,
                $credit,
                $balance,
                $notes,
                $createdBy
            ]
        );
    }
    
    /**
     * Get current balance for a customer
     */
    public static function getBalance($companyId, $customerId) {
        $db = new Database();
        $stmt = $db->prepareSelect(
            "SELECT balance FROM customer_ledger WHERE company_id = ? AND customer_id = ? ORDER BY id DESC LIMIT 1", 
            [$companyId, $customerId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            return (float)$row['balance'];
        }
        return 0.00;
    }
    
    /**
     * Get customer statement (all transactions)
     */
    public static function getStatement($companyId, $customerId, $startDate = null, $endDate = null) {
        $db = new Database();
        $query = "SELECT * FROM customer_ledger WHERE company_id = ? AND customer_id = ?";
        $params = [$companyId, $customerId];
        
        if ($startDate) {
            $query .= " AND transaction_date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $query .= " AND transaction_date <= ?";
            $params[] = $endDate;
        }
        
        $query .= " ORDER BY id ASC";
        $stmt = $db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Get all customers with outstanding balances
     */
    public static function getOutstandingBalances($companyId, $minBalance = 0.01) {
        $db = new Database();
        $query = "SELECT cl.customer_id, c.name as customer_name, c.phone, cl.balance 
                  FROM customer_ledger cl
                  JOIN customers c ON cl.customer_id = c.id
                  WHERE cl.company_id = ? 
                  AND cl.id IN (SELECT MAX(id) FROM customer_ledger WHERE company_id = ? GROUP BY customer_id)
                  AND cl.balance >= ?
                  ORDER BY cl.balance DESC";
        
        $stmt = $db->prepareSelect($query, [$companyId, $companyId, $minBalance]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Recalculate all balances for a customer (use if data gets out of sync)
     */
    public static function recalculateBalances($companyId, $customerId) {
        $db = new Database();
        
        // Get all transactions ordered by date and id
        $stmt = $db->prepareSelect(
            "SELECT id, debit_amount, credit_amount FROM customer_ledger 
             WHERE company_id = ? AND customer_id = ? 
             ORDER BY transaction_date ASC, id ASC",
            [$companyId, $customerId]
        );
        
        if (!$stmt) return false;
        
        $runningBalance = 0;
        $transactions = $stmt->fetchAll();
        
        foreach ($transactions as $tx) {
            $runningBalance = $runningBalance + (float)$tx['debit_amount'] - (float)$tx['credit_amount'];
            $db->prepareExecute("UPDATE customer_ledger SET balance = ? WHERE id = ?", [$runningBalance, $tx['id']]);
        }
        
        return $runningBalance;
    }
    
    /**
     * Get summary stats for all customers
     */
    public static function getSummaryStats($companyId) {
        $db = new Database();
        
        $stats = [
            'total_outstanding' => 0,
            'customer_count' => 0,
            'total_invoiced' => 0,
            'total_received' => 0
        ];
        
        // Total Outstanding
        $stmt = $db->prepareSelect(
            "SELECT SUM(balance) as total FROM (
                SELECT customer_id, balance FROM customer_ledger 
                WHERE company_id = ? 
                AND id IN (SELECT MAX(id) FROM customer_ledger WHERE company_id = ? GROUP BY customer_id)
                AND balance > 0
            ) as balances",
            [$companyId, $companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            $stats['total_outstanding'] = (float)($row['total'] ?? 0);
        }
        
        // Customer count with balances
        $stmt = $db->prepareSelect(
            "SELECT COUNT(DISTINCT customer_id) as count FROM customer_ledger 
             WHERE company_id = ? AND id IN (
                SELECT MAX(id) FROM customer_ledger WHERE company_id = ? GROUP BY customer_id
             ) AND balance > 0",
            [$companyId, $companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            $stats['customer_count'] = (int)($row['count'] ?? 0);
        }
        
        // Total invoiced (sum of all debits)
        $stmt = $db->prepareSelect(
            "SELECT SUM(debit_amount) as total FROM customer_ledger WHERE company_id = ? AND transaction_type = 'invoice'",
            [$companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            $stats['total_invoiced'] = (float)($row['total'] ?? 0);
        }
        
        // Total received (sum of all credits from payments)
        $stmt = $db->prepareSelect(
            "SELECT SUM(credit_amount) as total FROM customer_ledger WHERE company_id = ? AND transaction_type = 'payment'",
            [$companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            $stats['total_received'] = (float)($row['total'] ?? 0);
        }
        
        return $stats;
    }
}
