<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AuditLog.php';
require_once __DIR__ . '/FinancialTransaction.php';

class FinancialAccount {
    private $db;
    private $company_id;

    public function __construct($company_id) {
        $this->db = new Database();
        $this->company_id = $company_id;
    }

    // ============================================================
    // ACCOUNT MANAGEMENT
    // ============================================================

    public function createAccount($data) {
        $query = "INSERT INTO financial_accounts 
                  (company_id, branch_id, account_type, account_name, bank_name, account_number, currency, opening_balance, current_balance, is_active, is_default) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $this->company_id,
            $data['branch_id'] ?? null,
            $data['account_type'],
            $data['account_name'],
            $data['bank_name'] ?? null,
            $data['account_number'] ?? null,
            $data['currency'] ?? 'LKR',
            $data['opening_balance'] ?? 0.00,
            $data['opening_balance'] ?? 0.00, // Current balance starts same as opening
            $data['is_active'] ?? 1,
            $data['is_default'] ?? 0
        ];

        if ($this->db->prepareExecute($query, $params)) {
            $id = $this->db->getLastInsertId();
            AuditLog::logCreate($this->company_id, 'financial_accounts', $id, "Created account: " . $data['account_name']);
            return $id;
        }
        return false;
    }

    public function updateAccount($id, $data) {
        // Get old data for audit
        $old = $this->getAccountById($id);
        
        $query = "UPDATE financial_accounts SET 
                  account_name = ?, bank_name = ?, account_number = ?, is_active = ? 
                  WHERE id = ? AND company_id = ?";
        
        $success = $this->db->prepareExecute($query, [
            $data['account_name'],
            $data['bank_name'] ?? null,
            $data['account_number'] ?? null,
            $data['is_active'],
            $id,
            $this->company_id
        ]);
        
        if ($success) {
            AuditLog::logUpdate($this->company_id, 'financial_accounts', $id, 'details', $old, $data, "Updated account details");
        }
        return $success;
    }

    public function getAccounts($type = null, $isActiveOnly = true) {
        $query = "SELECT * FROM financial_accounts WHERE company_id = ?";
        $params = [$this->company_id];

        if ($type) {
            $query .= " AND account_type = ?";
            $params[] = $type;
        }

        if ($isActiveOnly) {
            $query .= " AND is_active = 1";
        }

        $query .= " ORDER BY is_default DESC, account_name ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    public function getAccountById($id) {
        $query = "SELECT * FROM financial_accounts WHERE id = ? AND company_id = ?";
        $stmt = $this->db->prepareSelect($query, [$id, $this->company_id]);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }

    // ============================================================
    // TRANSACTION MANAGEMENT
    // ============================================================

    /**
     * Record a financial transaction and update account balance
     * Uses FinancialTransaction class for consistency
     */
    public function recordTransaction($accountId, $type, $amount, $description, $meta = []) {
        $financialTx = new FinancialTransaction();
        
        $transactionDate = $meta['date'] ?? date('Y-m-d');
        $referenceType = $meta['reference_type'] ?? 'manual';
        $referenceId = $meta['reference_id'] ?? null;
        $categoryType = $meta['category_type'] ?? null;
        $createdBy = $meta['created_by'] ?? ($_SESSION['id'] ?? 0);
        $branchId = $meta['branch_id'] ?? null;
        
        // Route to appropriate method based on type
        if (in_array($type, ['income', 'transfer_in'])) {
            $result = $financialTx->recordIncome(
                $this->company_id,
                $accountId,
                $amount,
                $description,
                $referenceType,
                $referenceId,
                $transactionDate,
                $createdBy,
                $categoryType,
                $branchId
            );
        } elseif (in_array($type, ['expense', 'transfer_out'])) {
            $result = $financialTx->recordExpense(
                $this->company_id,
                $accountId,
                $amount,
                $description,
                $referenceType,
                $referenceId,
                $transactionDate,
                $createdBy,
                $categoryType,
                $branchId
            );
        } else {
            return ['error' => 'Invalid transaction type'];
        }
        
        if ($result['success']) {
            return true;
        }
        return ['error' => $result['error'] ?? 'Unknown error'];
    }

    /**
     * Get Daily Cashbook / Ledger
     */
    public function getLedger($filters = []) {
        return FinancialTransaction::getLedger(
            $this->company_id,
            $filters['account_id'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $filters['reference_type'] ?? null
        );
    }
    
    // ============================================================
    // TRANSFER LOGIC
    // ============================================================

    public function transferFunds($fromAccountId, $toAccountId, $amount, $description, $date, $userId) {
        $financialTx = new FinancialTransaction();
        
        try {
            $fromAccount = $this->getAccountById($fromAccountId);
            $toAccount = $this->getAccountById($toAccountId);

            if (!$fromAccount || !$toAccount) {
                return ['error' => 'Invalid accounts'];
            }
            if ($amount <= 0) {
                return ['error' => 'Invalid amount'];
            }

            // Record transfer out
            $resultOut = $financialTx->recordTransferOut(
                $this->company_id,
                $fromAccountId,
                $amount,
                $description . " (To: " . $toAccount['account_name'] . ")",
                $date,
                $userId
            );
            
            if (!$resultOut['success']) {
                return ['error' => $resultOut['error'] ?? 'Transfer out failed'];
            }
            
            // Record transfer in
            $resultIn = $financialTx->recordTransferIn(
                $this->company_id,
                $toAccountId,
                $amount,
                $description . " (From: " . $fromAccount['account_name'] . ")",
                $date,
                $userId
            );
            
            if (!$resultIn['success']) {
                // Note: In a real scenario, we'd need to handle partial failure
                return ['error' => $resultIn['error'] ?? 'Transfer in failed'];
            }
            
            AuditLog::logCreate($this->company_id, 'financial_transactions', 0, 
                "Transfer $amount from " . $fromAccount['account_name'] . " to " . $toAccount['account_name']);

            return true;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get Financial Statistics
     */
    public static function getStats($companyId) {
        $db = new Database();
        $stats = [
            'total_cash' => 0,
            'total_bank' => 0,
            'total_liquidity' => 0,
            'accounts_count' => 0
        ];
        
        $query = "SELECT account_type, SUM(current_balance) as total, COUNT(*) as count 
                  FROM financial_accounts 
                  WHERE company_id = ? AND is_active = 1 
                  GROUP BY account_type";
                  
        $res = $db->prepareSelect($query, [$companyId]);
        
        if($res) {
            while($row = $res->fetch(PDO::FETCH_ASSOC)) {
                if($row['account_type'] == 'cash') {
                    $stats['total_cash'] = (float)$row['total'];
                } else if($row['account_type'] == 'bank') {
                    $stats['total_bank'] = (float)$row['total'];
                }
                $stats['accounts_count'] += $row['count'];
            }
        }
        
        $stats['total_liquidity'] = $stats['total_cash'] + $stats['total_bank'];
        
        return $stats;
    }
}
