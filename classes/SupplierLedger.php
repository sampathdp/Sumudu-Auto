<?php
/**
 * SupplierLedger Class
 * Manages supplier balance tracking and ledger entries (Accounts Payable)
 * 
 * Usage:
 * - GRN (Goods Receipt) creates DEBIT (we owe more money)
 * - Supplier Payment creates CREDIT (we pay them)
 * - Positive balance = We owe supplier
 * - Negative balance = Supplier owes us (overpayment/advance)
 */
require_once __DIR__ . '/Database.php';

class SupplierLedger {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Record a GRN entry (Debit - we owe more)
     */
    public function recordGRN($companyId, $supplierId, $grnId, $grnNumber, $amount, $grnDate, $createdBy = null, $notes = null) {
        $prevBalance = self::getBalance($companyId, $supplierId);
        $newBalance = $prevBalance + (float)$amount; // GRN increases what we owe
        
        return $this->db->prepareExecute(
            "INSERT INTO supplier_ledger 
            (company_id, supplier_id, transaction_date, transaction_type, reference_id, reference_number, debit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'grn', ?, ?, ?, ?, ?, ?)",
            [
                $companyId,
                $supplierId,
                $grnDate,
                $grnId,
                $grnNumber,
                $amount,
                $newBalance,
                $notes,
                $createdBy
            ]
        );
    }
    
    /**
     * Record a payment entry (Credit - we pay off debt)
     */
    public function recordPayment($companyId, $supplierId, $paymentId, $paymentNumber, $amount, $paymentDate, $createdBy = null, $notes = null) {
        $prevBalance = self::getBalance($companyId, $supplierId);
        $newBalance = $prevBalance - (float)$amount; // Payment decreases what we owe
        
        return $this->db->prepareExecute(
            "INSERT INTO supplier_ledger 
            (company_id, supplier_id, transaction_date, transaction_type, reference_id, reference_number, credit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'payment', ?, ?, ?, ?, ?, ?)",
            [
                $companyId,
                $supplierId,
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
     * Record an adjustment entry (can be debit or credit)
     */
    public function recordAdjustment($companyId, $supplierId, $debitAmount, $creditAmount, $adjustmentDate, $createdBy = null, $notes = null) {
        $prevBalance = self::getBalance($companyId, $supplierId);
        $newBalance = $prevBalance + (float)$debitAmount - (float)$creditAmount;
        
        return $this->db->prepareExecute(
            "INSERT INTO supplier_ledger 
            (company_id, supplier_id, transaction_date, transaction_type, debit_amount, credit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'adjustment', ?, ?, ?, ?, ?)",
            [
                $companyId,
                $supplierId,
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
    public function recordOpeningBalance($companyId, $supplierId, $balance, $asOfDate, $createdBy = null, $notes = 'Opening Balance') {
        // Opening balance is typically a debit if we owe, credit if they owe us
        $debit = $balance > 0 ? $balance : 0;
        $credit = $balance < 0 ? abs($balance) : 0;
        
        return $this->db->prepareExecute(
            "INSERT INTO supplier_ledger 
            (company_id, supplier_id, transaction_date, transaction_type, debit_amount, credit_amount, balance, notes, created_by)
            VALUES (?, ?, ?, 'opening', ?, ?, ?, ?, ?)",
            [
                $companyId,
                $supplierId,
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
     * Get current balance for a supplier
     */
    public static function getBalance($companyId, $supplierId) {
        $db = new Database();
        $stmt = $db->prepareSelect(
            "SELECT balance FROM supplier_ledger WHERE company_id = ? AND supplier_id = ? ORDER BY id DESC LIMIT 1", 
            [$companyId, $supplierId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            return (float)$row['balance'];
        }
        return 0.00;
    }
    
    /**
     * Get supplier statement (all transactions)
     */
    public static function getStatement($companyId, $supplierId, $startDate = null, $endDate = null) {
        $db = new Database();
        $query = "SELECT * FROM supplier_ledger WHERE company_id = ? AND supplier_id = ?";
        $params = [$companyId, $supplierId];
        
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
     * Get all suppliers with outstanding balances (we owe them)
     */
    public static function getOutstandingBalances($companyId, $minBalance = 0.01) {
        $db = new Database();
        $query = "SELECT sl.supplier_id, s.supplier_name, s.phone, sl.balance 
                  FROM supplier_ledger sl
                  JOIN suppliers s ON sl.supplier_id = s.id
                  WHERE sl.company_id = ? 
                  AND sl.id IN (SELECT MAX(id) FROM supplier_ledger WHERE company_id = ? GROUP BY supplier_id)
                  AND sl.balance >= ?
                  ORDER BY sl.balance DESC";
        
        $stmt = $db->prepareSelect($query, [$companyId, $companyId, $minBalance]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Recalculate all balances for a supplier (use if data gets out of sync)
     */
    public static function recalculateBalances($companyId, $supplierId) {
        $db = new Database();
        
        // Get all transactions ordered by date and id
        $stmt = $db->prepareSelect(
            "SELECT id, debit_amount, credit_amount FROM supplier_ledger 
             WHERE company_id = ? AND supplier_id = ? 
             ORDER BY transaction_date ASC, id ASC",
            [$companyId, $supplierId]
        );
        
        if (!$stmt) return false;
        
        $runningBalance = 0;
        $transactions = $stmt->fetchAll();
        
        foreach ($transactions as $tx) {
            $runningBalance = $runningBalance + (float)$tx['debit_amount'] - (float)$tx['credit_amount'];
            $db->prepareExecute("UPDATE supplier_ledger SET balance = ? WHERE id = ?", [$runningBalance, $tx['id']]);
        }
        
        return $runningBalance;
    }
    
    /**
     * Get summary stats for all suppliers (Accounts Payable)
     */
    public static function getSummaryStats($companyId) {
        $db = new Database();
        
        $stats = [
            'total_payable' => 0,
            'supplier_count' => 0,
            'total_purchased' => 0,
            'total_paid' => 0
        ];
        
        // Total Payable (what we owe)
        $stmt = $db->prepareSelect(
            "SELECT SUM(balance) as total FROM (
                SELECT supplier_id, balance FROM supplier_ledger 
                WHERE company_id = ? 
                AND id IN (SELECT MAX(id) FROM supplier_ledger WHERE company_id = ? GROUP BY supplier_id)
                AND balance > 0
            ) as balances",
            [$companyId, $companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            $stats['total_payable'] = (float)($row['total'] ?? 0);
        }
        
        // Supplier count with balances
        $stmt = $db->prepareSelect(
            "SELECT COUNT(DISTINCT supplier_id) as count FROM supplier_ledger 
             WHERE company_id = ? AND id IN (
                SELECT MAX(id) FROM supplier_ledger WHERE company_id = ? GROUP BY supplier_id
             ) AND balance > 0",
            [$companyId, $companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            $stats['supplier_count'] = (int)($row['count'] ?? 0);
        }
        
        // Total purchased (sum of all debits from GRNs)
        $stmt = $db->prepareSelect(
            "SELECT SUM(debit_amount) as total FROM supplier_ledger WHERE company_id = ? AND transaction_type = 'grn'",
            [$companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            $stats['total_purchased'] = (float)($row['total'] ?? 0);
        }
        
        // Total paid (sum of all credits from payments)
        $stmt = $db->prepareSelect(
            "SELECT SUM(credit_amount) as total FROM supplier_ledger WHERE company_id = ? AND transaction_type = 'payment'",
            [$companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            $stats['total_paid'] = (float)($row['total'] ?? 0);
        }
        
        return $stats;
    }
}
