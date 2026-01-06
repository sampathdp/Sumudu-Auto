<?php
class Quotation
{
    public $id;
    public $company_id;
    public $branch_id;
    public $quotation_number;
    public $customer_id;
    public $customer_name;
    public $subtotal;
    public $tax_amount;
    public $discount_amount;
    public $total_amount;
    public $valid_until;
    public $status;
    public $created_at;
    public $updated_at;
    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load quotation by ID.
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM quotations WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
            }
        }
    }

    private function populateFromArray($row)
    {
        $this->id = $row['id'];
        $this->company_id = $row['company_id'];
        $this->branch_id = $row['branch_id'];
        $this->quotation_number = $row['quotation_number'];
        $this->customer_id = $row['customer_id'];
        $this->customer_name = $row['customer_name'];
        $this->subtotal = $row['subtotal'];
        $this->tax_amount = $row['tax_amount'];
        $this->discount_amount = $row['discount_amount'];
        $this->total_amount = $row['total_amount'];
        $this->valid_until = $row['valid_until'];
        $this->status = $row['status'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    /**
     * Generate unique quotation number for company.
     */
    public function generateQuotationNumber($companyId)
    {
        $prefix = 'QT-';
        
        $query = "SELECT quotation_number FROM quotations 
                  WHERE company_id = ? AND quotation_number LIKE ? 
                  ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepareSelect($query, [$companyId, $prefix . '%']);
        $row = $stmt->fetch();
        
        $nextNumber = 1;
        
        if ($row && isset($row['quotation_number'])) {
            $lastNumber = (int) str_replace($prefix, '', $row['quotation_number']);
            if ($lastNumber > 0) {
                $nextNumber = $lastNumber + 1;
            }
        }
        
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new quotation.
     */
    public function create()
    {
        if (empty($this->company_id)) {
            return false;
        }

        if (empty($this->quotation_number)) {
            $this->quotation_number = $this->generateQuotationNumber($this->company_id);
        }

        $query = "INSERT INTO quotations (company_id, branch_id, quotation_number, customer_id, customer_name, subtotal, tax_amount, 
                  discount_amount, total_amount, valid_until, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->branch_id ?? null,
            $this->quotation_number,
            $this->customer_id ?? 0,
            $this->customer_name ?? '',
            $this->subtotal ?? 0,
            $this->tax_amount ?? 0,
            $this->discount_amount ?? 0,
            $this->total_amount ?? 0,
            $this->valid_until ?? null,
            $this->status ?? 'pending'
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update quotation.
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        $query = "UPDATE quotations SET subtotal = ?, tax_amount = ?, discount_amount = ?, 
                  total_amount = ?, valid_until = ?, status = ?, branch_id = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->subtotal,
            $this->tax_amount ?? 0,
            $this->discount_amount ?? 0,
            $this->total_amount,
            $this->valid_until ?? null,
            $this->status,
            $this->branch_id ?? null,
            $this->id
        ]);
    }

    /**
     * Delete quotation.
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        // Items will be deleted manually first since ON DELETE CASCADE might not be relied upon across engines
        $this->db->prepareExecute("DELETE FROM quotation_items WHERE quotation_id = ?", [$this->id]);
        
        $query = "DELETE FROM quotations WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all quotations for a company.
     */
    public function all($companyId, $branchId = null)
    {
        $query = "SELECT q.*, b.branch_name 
                  FROM quotations q
                  LEFT JOIN branches b ON q.branch_id = b.id
                  WHERE q.company_id = ?";
        
        $params = [$companyId];
         
        if ($branchId) {
            $query .= " AND q.branch_id = ?";
            $params[] = $branchId;
        }
        
        $query .= " ORDER BY q.created_at DESC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Get quotation items.
     */
    public function getItems($quotationId)
    {
        // No strict company check here on read, reliant on parent auth. 
        // But good practice generally.
        $query = "SELECT qi.*, i.item_name, i.item_code
                  FROM quotation_items qi
                  LEFT JOIN inventory_items i ON qi.item_id = i.id
                  WHERE qi.quotation_id = ?";
        $stmt = $this->db->prepareSelect($query, [$quotationId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Add item to quotation.
     */
    public function addItem($itemData)
    {
        if (!$this->id || empty($this->company_id)) {
            return false;
        }

        $query = "INSERT INTO quotation_items (company_id, quotation_id, item_type, item_id, description, 
                  quantity, unit_price, total_price, tax_rate, tax_amount) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->db->prepareExecute($query, [
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
    }

    /**
     * Get quotation statistics for a company.
     */
    public static function getStatistics($companyId, $branchId = null)
    {
        $db = new Database();
        $stats = [
            'total' => 0,
            'pending' => 0,
            'accepted' => 0,
            'rejected' => 0
        ];
        
        $params = [$companyId];
        $branchClause = "";
        
        if ($branchId) {
            $branchClause = " AND branch_id = ?";
            $params[] = $branchId;
        }

        // Total
        $query = "SELECT COUNT(*) as count FROM quotations WHERE company_id = ?" . $branchClause;
        $stmt = $db->prepareSelect($query, $params);
        $stats['total'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;

        // Pending
        $query = "SELECT COUNT(*) as count FROM quotations WHERE company_id = ? AND status = 'pending'" . $branchClause;
        $stmt = $db->prepareSelect($query, $params);
        $stats['pending'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;

        // Accepted
        $query = "SELECT COUNT(*) as count FROM quotations WHERE company_id = ? AND status = 'accepted'" . $branchClause;
        $stmt = $db->prepareSelect($query, $params);
        $stats['accepted'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;

        // Rejected
        $query = "SELECT COUNT(*) as count FROM quotations WHERE company_id = ? AND status = 'rejected'" . $branchClause;
        $stmt = $db->prepareSelect($query, $params);
        $stats['rejected'] = $stmt ? ($stmt->fetch()['count'] ?? 0) : 0;
        
        return $stats;
    }

    /**
     * Count quotations for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM quotations WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>
