<?php
require_once __DIR__ . '/FinancialAccount.php'; // Add dependency

class Expense
{
    public $id;
    public $company_id;
    public $branch_id;
    public $category_id;
    public $account_id; // Added
    public $expense_date;
    public $amount;
    public $reference_number;
    public $description;
    public $paid_to;
    public $payment_method;
    public $status;
    public $approved_by;
    public $created_by;
    // public $attachment; // Removed
    public $created_at;
    public $updated_at;
    
    // Joined fields
    public $category_name;
    public $created_by_name;
    public $account_name; // Added
    
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    private function loadById($id)
    {
        $query = "SELECT e.*, c.category_name, fa.account_name, CONCAT(u.first_name, ' ', u.last_name) as created_by_name 
                  FROM expenses e 
                  LEFT JOIN expense_categories c ON e.category_id = c.id
                  LEFT JOIN financial_accounts fa ON e.account_id = fa.id
                  LEFT JOIN employees u ON e.created_by = u.id
                  WHERE e.id = ?";
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

    public function create()
    {
        if (empty($this->company_id) || empty($this->category_id) || empty($this->amount) || empty($this->expense_date)) {
            return false;
        }

        $query = "INSERT INTO expenses (company_id, branch_id, category_id, account_id, expense_date, amount, reference_number, description, paid_to, payment_method, status, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $this->company_id,
            $this->branch_id,
            (int)$this->category_id,
            !empty($this->account_id) ? (int)$this->account_id : null,
            $this->expense_date,
            (float)$this->amount,
            $this->reference_number,
            $this->description,
            $this->paid_to,
            $this->payment_method,
            $this->status ?? 'pending',
            $this->created_by
        ];

        $success = $this->db->prepareExecute($query, $params);
        if ($success) {
            $this->id = $this->db->getLastInsertId();
            
            // If created as approved immediately, record transaction
            if ($this->status === 'approved' && $this->account_id) {
                $this->recordFinancialTransaction($this->created_by);
            }
        }
        return $success;
    }

    public function update()
    {
        if (!$this->id) return false;

        $query = "UPDATE expenses SET category_id = ?, account_id = ?, expense_date = ?, amount = ?, reference_number = ?, description = ?, paid_to = ?, payment_method = ? WHERE id = ?";
        $params = [
            (int)$this->category_id,
            !empty($this->account_id) ? (int)$this->account_id : null,
            $this->expense_date,
            (float)$this->amount,
            $this->reference_number,
            $this->description,
            $this->paid_to,
            $this->payment_method,
            $this->id
        ];
        return $this->db->prepareExecute($query, $params);
    }

    public function delete()
    {
        if (!$this->id) return false;
        // Optionally delete attachment file here
        $query = "DELETE FROM expenses WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    public function approve($approverId)
    {
        if (!$this->id) return false;
        
        $this->db->beginTransaction();
        
        try {
            $query = "UPDATE expenses SET status = 'approved', approved_by = ? WHERE id = ?";
            $this->db->prepareExecute($query, [$approverId, $this->id]);
            
            // Record Transaction
            if ($this->account_id) {
                $res = $this->recordFinancialTransaction($approverId);
                if ($res !== true) {
                    throw new Exception("Financial Account Error");
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    private function recordFinancialTransaction($userId) {
        require_once __DIR__ . '/FinancialTransaction.php';
        
        $financialTx = new FinancialTransaction();
        $description = "Expense: " . $this->description . " (" . $this->category_name . ")";
        
        $result = $financialTx->recordExpense(
            $this->company_id,
            $this->account_id,
            $this->amount,
            $description,
            'expense',
            $this->id,
            $this->expense_date,
            $userId,
            'Expense',
            $this->branch_id
        );
        
        return $result['success'] ? true : $result;
    }
    
    public function reject($approverId)
    {
        if (!$this->id) return false;
        $query = "UPDATE expenses SET status = 'rejected', approved_by = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$approverId, $this->id]);
    }

    public function all($companyId)
    {
        $query = "SELECT e.*, c.category_name, fa.account_name,
                         CASE WHEN emp.id IS NOT NULL THEN CONCAT(emp.first_name, ' ', emp.last_name) 
                              ELSE 'System' END as created_by_name 
                  FROM expenses e 
                  LEFT JOIN expense_categories c ON e.category_id = c.id
                  LEFT JOIN financial_accounts fa ON e.account_id = fa.id
                  LEFT JOIN employees emp ON e.created_by = emp.user_id 
                  WHERE e.company_id = ? 
                  ORDER BY e.expense_date DESC, e.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public static function getStats($companyId)
    {
        $db = new Database();
        $stats = [];
        
        // Total Expenses (All time)
        $stmt = $db->prepareSelect("SELECT SUM(amount) as total FROM expenses WHERE company_id = ? AND status = 'approved'", [$companyId]);
        $stats['total'] = $stmt ? ($stmt->fetch()['total'] ?? 0) : 0;
        
        // This Month
        $stmt = $db->prepareSelect("SELECT SUM(amount) as total FROM expenses WHERE company_id = ? AND status = 'approved' AND expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$companyId]);
        $stats['this_month'] = $stmt ? ($stmt->fetch()['total'] ?? 0) : 0;
        
        // Pending Approval Count
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM expenses WHERE company_id = ? AND status = 'pending'", [$companyId]);
        $stats['pending_count'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        return $stats;
    }
}
