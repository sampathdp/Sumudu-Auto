<?php
/**
 * FinancialTransaction Class
 * Manages all financial transactions (Cashbook/Ledger entries)
 * 
 * Usage:
 * - Invoice (Cash Sale) → recordIncome()
 * - Customer Payment → recordIncome()
 * - Supplier Payment → recordExpense()
 * - Expense → recordExpense()
 * - Transfer → recordTransfer() (both in and out)
 * 
 * All transactions update account balances automatically.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AuditLog.php';

class FinancialTransaction {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Record an income transaction (Money IN)
     * Use for: Invoice payments, Customer payments, Sales, etc.
     */
    public function recordIncome($companyId, $accountId, $amount, $description, $referenceType, $referenceId, $transactionDate, $createdBy, $categoryType = null, $branchId = null) {
        return $this->record([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'account_id' => $accountId,
            'transaction_date' => $transactionDate,
            'transaction_type' => 'income',
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'description' => $description,
            'category_type' => $categoryType ?? 'Sales',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy
        ]);
    }
    
    /**
     * Record an expense transaction (Money OUT)
     * Use for: Supplier payments, General expenses, Salaries, etc.
     */
    public function recordExpense($companyId, $accountId, $amount, $description, $referenceType, $referenceId, $transactionDate, $createdBy, $categoryType = null, $branchId = null) {
        return $this->record([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'account_id' => $accountId,
            'transaction_date' => $transactionDate,
            'transaction_type' => 'expense',
            'debit_amount' => 0,
            'credit_amount' => $amount,
            'description' => $description,
            'category_type' => $categoryType ?? 'Expense',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy
        ]);
    }
    
    /**
     * Record a transfer out transaction (Money leaving an account)
     */
    public function recordTransferOut($companyId, $accountId, $amount, $description, $transactionDate, $createdBy, $branchId = null) {
        return $this->record([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'account_id' => $accountId,
            'transaction_date' => $transactionDate,
            'transaction_type' => 'transfer_out',
            'debit_amount' => 0,
            'credit_amount' => $amount,
            'description' => $description,
            'category_type' => 'Transfer',
            'reference_type' => 'transfer',
            'reference_id' => null,
            'created_by' => $createdBy
        ]);
    }
    
    /**
     * Record a transfer in transaction (Money entering an account)
     */
    public function recordTransferIn($companyId, $accountId, $amount, $description, $transactionDate, $createdBy, $branchId = null) {
        return $this->record([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'account_id' => $accountId,
            'transaction_date' => $transactionDate,
            'transaction_type' => 'transfer_in',
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'description' => $description,
            'category_type' => 'Transfer',
            'reference_type' => 'transfer',
            'reference_id' => null,
            'created_by' => $createdBy
        ]);
    }
    
    /**
     * Record a manual adjustment
     */
    public function recordAdjustment($companyId, $accountId, $debitAmount, $creditAmount, $description, $transactionDate, $createdBy, $branchId = null) {
        return $this->record([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'account_id' => $accountId,
            'transaction_date' => $transactionDate,
            'transaction_type' => 'adjustment',
            'debit_amount' => $debitAmount,
            'credit_amount' => $creditAmount,
            'description' => $description,
            'category_type' => 'Adjustment',
            'reference_type' => 'manual',
            'reference_id' => null,
            'created_by' => $createdBy
        ]);
    }
    
    /**
     * Core record function - handles balance update and transaction insert
     */
    private function record($data) {
        try {
            $this->db->beginTransaction();
            
            // 1. Get current account balance
            $account = $this->db->prepareSelect(
                "SELECT current_balance FROM financial_accounts WHERE id = ? AND company_id = ?",
                [$data['account_id'], $data['company_id']]
            );
            
            if (!$account || !($row = $account->fetch())) {
                throw new Exception("Invalid financial account ID: " . $data['account_id']);
            }
            
            $currentBalance = (float)$row['current_balance'];
            
            // 2. Calculate new balance
            // Debit = Money IN, Credit = Money OUT
            $balanceChange = (float)$data['debit_amount'] - (float)$data['credit_amount'];
            $newBalance = $currentBalance + $balanceChange;
            
            // 3. Update account balance
            $this->db->prepareExecute(
                "UPDATE financial_accounts SET current_balance = ? WHERE id = ?",
                [$newBalance, $data['account_id']]
            );
            
            // 4. Insert transaction record
            $query = "INSERT INTO financial_transactions 
                      (company_id, branch_id, account_id, transaction_date, transaction_type, 
                       debit_amount, credit_amount, balance_after, description, category_type,
                       reference_type, reference_id, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->prepareExecute($query, [
                $data['company_id'],
                $data['branch_id'] ?? null,
                $data['account_id'],
                $data['transaction_date'],
                $data['transaction_type'],
                $data['debit_amount'],
                $data['credit_amount'],
                $newBalance,
                $data['description'],
                $data['category_type'] ?? null,
                $data['reference_type'] ?? 'manual',
                $data['reference_id'] ?? null,
                $data['created_by']
            ]);
            
            $transactionId = $this->db->getLastInsertId();
            
            // 5. Audit log for manual entries
            if (($data['reference_type'] ?? 'manual') === 'manual') {
                AuditLog::logCreate($data['company_id'], 'financial_transactions', $transactionId, "Manual: " . $data['description']);
            }
            
            $this->db->commit();
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'new_balance' => $newBalance
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("FinancialTransaction Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get account balance
     */
    public static function getAccountBalance($companyId, $accountId) {
        $db = new Database();
        $stmt = $db->prepareSelect(
            "SELECT current_balance FROM financial_accounts WHERE id = ? AND company_id = ?",
            [$accountId, $companyId]
        );
        if ($stmt && $row = $stmt->fetch()) {
            return (float)$row['current_balance'];
        }
        return 0.00;
    }
    
    /**
     * Get transactions for an account (ledger view)
     */
    public static function getLedger($companyId, $accountId = null, $startDate = null, $endDate = null, $referenceType = null) {
        $db = new Database();
        $query = "SELECT t.*, a.account_name, a.account_type 
                  FROM financial_transactions t 
                  JOIN financial_accounts a ON t.account_id = a.id
                  WHERE t.company_id = ?";
        $params = [$companyId];
        
        if ($accountId) {
            $query .= " AND t.account_id = ?";
            $params[] = $accountId;
        }
        
        if ($startDate) {
            $query .= " AND t.transaction_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $query .= " AND t.transaction_date <= ?";
            $params[] = $endDate;
        }
        
        if ($referenceType) {
            $query .= " AND t.reference_type = ?";
            $params[] = $referenceType;
        }
        
        $query .= " ORDER BY t.transaction_date DESC, t.id DESC";
        
        $stmt = $db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Get daily summary (for cashbook)
     */
    public static function getDailySummary($companyId, $date, $accountId = null) {
        $db = new Database();
        $query = "SELECT 
                    SUM(debit_amount) as total_income,
                    SUM(credit_amount) as total_expense,
                    COUNT(*) as transaction_count
                  FROM financial_transactions 
                  WHERE company_id = ? AND transaction_date = ?";
        $params = [$companyId, $date];
        
        if ($accountId) {
            $query .= " AND account_id = ?";
            $params[] = $accountId;
        }
        
        $stmt = $db->prepareSelect($query, $params);
        if ($stmt && $row = $stmt->fetch()) {
            return [
                'income' => (float)($row['total_income'] ?? 0),
                'expense' => (float)($row['total_expense'] ?? 0),
                'net' => (float)($row['total_income'] ?? 0) - (float)($row['total_expense'] ?? 0),
                'count' => (int)($row['transaction_count'] ?? 0)
            ];
        }
        return ['income' => 0, 'expense' => 0, 'net' => 0, 'count' => 0];
    }
    
    /**
     * Get summary stats for a period
     */
    public static function getSummaryStats($companyId, $startDate = null, $endDate = null) {
        $db = new Database();
        
        $query = "SELECT 
                    SUM(CASE WHEN transaction_type = 'income' THEN debit_amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN transaction_type = 'expense' THEN credit_amount ELSE 0 END) as total_expense,
                    SUM(CASE WHEN reference_type = 'invoice' THEN debit_amount ELSE 0 END) as invoice_income,
                    SUM(CASE WHEN reference_type = 'customer_payment' THEN debit_amount ELSE 0 END) as customer_payment_income,
                    SUM(CASE WHEN reference_type = 'supplier_payment' THEN credit_amount ELSE 0 END) as supplier_payment_expense,
                    SUM(CASE WHEN reference_type = 'expense' THEN credit_amount ELSE 0 END) as general_expense,
                    COUNT(*) as transaction_count
                  FROM financial_transactions 
                  WHERE company_id = ?";
        $params = [$companyId];
        
        if ($startDate) {
            $query .= " AND transaction_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $query .= " AND transaction_date <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $db->prepareSelect($query, $params);
        if ($stmt && $row = $stmt->fetch()) {
            return [
                'total_income' => (float)($row['total_income'] ?? 0),
                'total_expense' => (float)($row['total_expense'] ?? 0),
                'net_income' => (float)($row['total_income'] ?? 0) - (float)($row['total_expense'] ?? 0),
                'invoice_income' => (float)($row['invoice_income'] ?? 0),
                'customer_payment_income' => (float)($row['customer_payment_income'] ?? 0),
                'supplier_payment_expense' => (float)($row['supplier_payment_expense'] ?? 0),
                'general_expense' => (float)($row['general_expense'] ?? 0),
                'transaction_count' => (int)($row['transaction_count'] ?? 0)
            ];
        }
        return [];
    }
    
    /**
     * Get transactions by reference (to view/audit a specific source)
     */
    public static function getByReference($companyId, $referenceType, $referenceId) {
        $db = new Database();
        $query = "SELECT t.*, a.account_name 
                  FROM financial_transactions t 
                  JOIN financial_accounts a ON t.account_id = a.id
                  WHERE t.company_id = ? AND t.reference_type = ? AND t.reference_id = ?";
        
        $stmt = $db->prepareSelect($query, [$companyId, $referenceType, $referenceId]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Reverse a transaction (for cancellations)
     */
    public function reverseTransaction($companyId, $referenceType, $referenceId, $reason, $userId) {
        // Get original transactions
        $originals = self::getByReference($companyId, $referenceType, $referenceId);
        
        if (empty($originals)) {
            return ['success' => false, 'error' => 'No transactions found to reverse'];
        }
        
        $results = [];
        foreach ($originals as $orig) {
            // Create opposite transaction
            $result = $this->record([
                'company_id' => $companyId,
                'branch_id' => $orig['branch_id'],
                'account_id' => $orig['account_id'],
                'transaction_date' => date('Y-m-d'),
                'transaction_type' => 'adjustment',
                'debit_amount' => $orig['credit_amount'], // Swap
                'credit_amount' => $orig['debit_amount'], // Swap
                'description' => "REVERSAL: " . $orig['description'] . " - " . $reason,
                'category_type' => 'Reversal',
                'reference_type' => 'manual',
                'reference_id' => null,
                'created_by' => $userId
            ]);
            $results[] = $result;
        }
        
        return ['success' => true, 'reversals' => $results];
    }
}
