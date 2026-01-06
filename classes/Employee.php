<?php
/**
 * Employee Class
 * Handles all employee-related database operations with multi-tenant support
 */
class Employee
{
    public $id;
    public $company_id;
    public $branch_id;
    public $employee_code;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $position;
    public $department;
    public $hire_date;
    public $salary;
    public $salary_type; // 'monthly', 'daily', 'commission'
    public $address;
    public $emergency_contact;
    public $emergency_phone;
    public $is_active;
    public $user_id;
    public $notes;
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
     * Load employee by ID
     */
    private function loadById($id)
    {
        $query = "SELECT * FROM employees WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromRow($row);
            }
        }
    }

    /**
     * Populate object properties from database row
     */
    private function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->company_id = $row['company_id'];
        $this->branch_id = $row['branch_id'];
        $this->employee_code = $row['employee_code'];
        $this->first_name = $row['first_name'];
        $this->last_name = $row['last_name'];
        $this->email = $row['email'];
        $this->phone = $row['phone'];
        $this->position = $row['position'];
        $this->department = $row['department'];
        $this->hire_date = $row['hire_date'];
        $this->salary = $row['salary'];
        $this->salary_type = $row['salary_type'] ?? 'monthly';
        $this->address = $row['address'];
        $this->emergency_contact = $row['emergency_contact'];
        $this->emergency_phone = $row['emergency_phone'];
        $this->is_active = $row['is_active'];
        $this->user_id = $row['user_id'];
        $this->notes = $row['notes'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    /**
     * Generate unique employee code within company
     */
    private function generateEmployeeCode($companyId)
    {
        $query = "SELECT MAX(CAST(SUBSTRING(employee_code, 5) AS UNSIGNED)) as max_num 
                  FROM employees WHERE company_id = ? AND employee_code LIKE 'EMP-%'";
        $stmt = $this->db->prepareSelect($query, [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            $nextNum = ($row['max_num'] ?? 0) + 1;
            return 'EMP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        }
        return 'EMP-0001';
    }

    /**
     * Create new employee
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->first_name) || empty($this->last_name)) {
            return false;
        }

        // Check email uniqueness within company
        if ($this->email && $this->checkEmailExists($this->company_id, $this->email)) {
            return false;
        }

        // Generate employee code for this company
        $this->employee_code = $this->generateEmployeeCode($this->company_id);

        $query = "INSERT INTO employees (company_id, branch_id, employee_code, first_name, last_name, email, phone, 
                  position, department, hire_date, salary, salary_type, address, emergency_contact, 
                  emergency_phone, is_active, user_id, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->branch_id ?? null,
            $this->employee_code,
            $this->first_name,
            $this->last_name,
            !empty($this->email) ? $this->email : null,
            !empty($this->phone) ? $this->phone : null,
            $this->position ?? null,
            $this->department ?? null,
            $this->hire_date ?? null,
            $this->salary ?? 0,
            $this->salary_type ?? 'monthly',
            $this->address ?? null,
            $this->emergency_contact ?? null,
            $this->emergency_phone ?? null,
            $this->is_active ?? 1,
            $this->user_id ?? null,
            $this->notes ?? null
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update employee
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Check email uniqueness within company (excluding current)
        if ($this->email && $this->checkEmailExists($this->company_id, $this->email, $this->id)) {
            return false;
        }

        $query = "UPDATE employees SET 
                  branch_id = ?, first_name = ?, last_name = ?, email = ?, phone = ?, 
                  position = ?, department = ?, hire_date = ?, salary = ?, salary_type = ?,
                  address = ?, emergency_contact = ?, emergency_phone = ?, 
                  is_active = ?, user_id = ?, notes = ?
                  WHERE id = ?";
        
        return $this->db->prepareExecute($query, [
            $this->branch_id ?? null,
            $this->first_name,
            $this->last_name,
            !empty($this->email) ? $this->email : null,
            !empty($this->phone) ? $this->phone : null,
            $this->position ?? null,
            $this->department ?? null,
            $this->hire_date ?? null,
            $this->salary ?? 0,
            $this->salary_type ?? 'monthly',
            $this->address ?? null,
            $this->emergency_contact ?? null,
            $this->emergency_phone ?? null,
            $this->is_active ?? 1,
            $this->user_id ?? null,
            $this->notes ?? null,
            $this->id
        ]);
    }

    /**
     * Delete employee
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM employees WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all employees (optionally filtered by company/branch)
     */
    public function all($companyId = null, $branchId = null, $activeOnly = false)
    {
        $query = "SELECT e.*, b.branch_name 
                  FROM employees e
                  LEFT JOIN branches b ON e.branch_id = b.id";
        $params = [];
        $conditions = [];
        
        if ($companyId) {
            $conditions[] = "e.company_id = ?";
            $params[] = $companyId;
        }
        
        if ($branchId) {
            $conditions[] = "e.branch_id = ?";
            $params[] = $branchId;
        }
        
        if ($activeOnly) {
            $conditions[] = "e.is_active = 1";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $query .= " ORDER BY e.first_name, e.last_name";
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Check if email exists within company
     */
    private function checkEmailExists($companyId, $email, $excludeId = null)
    {
        $query = "SELECT id FROM employees WHERE company_id = ? AND email = ?";
        $params = [$companyId, $email];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get full name
     */
    public function getFullName()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus()
    {
        if (!$this->id) {
            return false;
        }
        $this->is_active = $this->is_active ? 0 : 1;
        $query = "UPDATE employees SET is_active = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->is_active, $this->id]);
    }

    /**
     * Get jobs worked on by this employee for a specific date
     */
    public function getJobsWorkedOn($date, $companyId = null)
    {
        if (!$this->id) {
            return [];
        }
        
        $query = "SELECT s.id, s.job_number, s.total_amount, s.status, s.created_at,
                         c.name as customer_name, v.registration_number,
                         sp.package_name
                  FROM services s
                  LEFT JOIN customers c ON s.customer_id = c.id
                  LEFT JOIN vehicles v ON s.vehicle_id = v.id
                  LEFT JOIN service_packages sp ON s.package_id = sp.id
                  WHERE s.assigned_employee_id = ? 
                  AND DATE(s.created_at) = ?
                  AND s.status != 'cancelled'";
        
        $params = [$this->id, $date];
        
        if ($companyId) {
            $query .= " AND s.company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY s.created_at ASC";
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Calculate earnings for a specific date
     */
    public function calculateEarnings($date, $companyId = null)
    {
        if (!$this->id) {
            return null;
        }

        $jobs = $this->getJobsWorkedOn($date, $companyId);
        $jobsCount = count($jobs);
        $baseAmount = 0;
        $commissionAmount = 0;

        if ($this->salary_type === 'daily') {
            $baseAmount = $jobsCount > 0 ? floatval($this->salary) : 0;
        } elseif ($this->salary_type === 'commission') {
            $commissionRate = floatval($this->salary) / 100;
            foreach ($jobs as $job) {
                $commissionAmount += floatval($job['total_amount']) * $commissionRate;
            }
        } else {
            $dailyRate = floatval($this->salary) / 26;
            $baseAmount = $dailyRate;
        }

        return [
            'jobs' => $jobs,
            'jobs_count' => $jobsCount,
            'base_amount' => round($baseAmount, 2),
            'commission_amount' => round($commissionAmount, 2),
            'today_earnings' => round($baseAmount + $commissionAmount, 2)
        ];
    }

    /**
     * Get pending balance (unpaid earnings from previous days)
     */
    public function getPendingBalance($companyId = null)
    {
        if (!$this->id) {
            return 0;
        }

        $query = "SELECT SUM(total_amount) as pending 
                  FROM employee_payments 
                  WHERE employee_id = ? AND status = 'unpaid'";
        $params = [$this->id];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            return floatval($row['pending'] ?? 0);
        }
        return 0;
    }

    /**
     * Get or create payment record for a date
     */
    public function getPaymentRecord($date, $companyId = null)
    {
        if (!$this->id) {
            return null;
        }

        $query = "SELECT * FROM employee_payments WHERE employee_id = ? AND payment_date = ?";
        $params = [$this->id, $date];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        
        if ($stmt && $stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return null;
    }

    /**
     * Save or update payment record
     */
    public function savePaymentRecord($date, $earnings, $companyId, $pendingAmount = 0, $status = 'unpaid')
    {
        if (!$this->id) {
            return false;
        }

        $totalAmount = $earnings['today_earnings'] + $pendingAmount;
        
        $existing = $this->getPaymentRecord($date, $companyId);
        
        if ($existing) {
            $query = "UPDATE employee_payments SET 
                      base_amount = ?, commission_amount = ?, pending_amount = ?,
                      total_amount = ?, jobs_count = ?, status = ?, updated_at = NOW()
                      WHERE id = ?";
            return $this->db->prepareExecute($query, [
                $earnings['base_amount'],
                $earnings['commission_amount'],
                $pendingAmount,
                $totalAmount,
                $earnings['jobs_count'],
                $status,
                $existing['id']
            ]);
        } else {
            $query = "INSERT INTO employee_payments 
                      (company_id, employee_id, payment_date, salary_type, base_amount, commission_amount, 
                       pending_amount, total_amount, jobs_count, status)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            return $this->db->prepareExecute($query, [
                $companyId,
                $this->id,
                $date,
                $this->salary_type,
                $earnings['base_amount'],
                $earnings['commission_amount'],
                $pendingAmount,
                $totalAmount,
                $earnings['jobs_count'],
                $status
            ]);
        }
    }

    /**
     * Process payment for employee
     */
    public function processPayment($date, $paidBy, $companyId = null)
    {
        if (!$this->id) {
            return false;
        }

        $query = "UPDATE employee_payments SET 
                  status = 'paid', paid_at = NOW(), paid_by = ?
                  WHERE employee_id = ? AND status = 'unpaid' AND payment_date <= ?";
        $params = [$paidBy, $this->id, $date];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        return $this->db->prepareExecute($query, $params);
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory($companyId = null, $limit = 30)
    {
        if (!$this->id) {
            return [];
        }

        $query = "SELECT * FROM employee_payments WHERE employee_id = ?";
        $params = [$this->id];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " ORDER BY payment_date DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Count employees for a company
     */
    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM employees WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }

    /**
     * Get employee by User ID
     */
    public function getByUserId($userId)
    {
        $query = "SELECT * FROM employees WHERE user_id = ?";
        $stmt = $this->db->prepareSelect($query, [$userId]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromRow($row);
                return $this;
            }
        }
        return null;
    }

    /**
     * Get Salary Report Data
     * 
     * @param array $filters Filter parameters (start_date, end_date, employee_id, salary_type, status)
     * @param int $companyId Company ID
     * @return array Array containing data and summary
     */
    public static function getSalaryReport($filters, $companyId)
    {
        $db = new Database();
        
        // Extract filters
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $employeeId = $filters['employee_id'] ?? '';
        $salaryType = $filters['salary_type'] ?? '';
        $status = $filters['status'] ?? '';
        
        // Build query
        $where = "WHERE ep.company_id = ? AND ep.payment_date BETWEEN ? AND ?";
        $params = [$companyId, $startDate, $endDate];
        
        if ($employeeId) {
            $where .= " AND ep.employee_id = ?";
            $params[] = $employeeId;
        }
        if ($salaryType) {
            $where .= " AND ep.salary_type = ?";
            $params[] = $salaryType;
        }
        if ($status) {
            $where .= " AND ep.status = ?";
            $params[] = $status;
        }
        
        // Fetch Data
        $query = "SELECT ep.*, 
                  COALESCE(CONCAT(e.first_name, ' ', e.last_name), 'Unknown Employee') as employee_name,
                  COALESCE(e.employee_code, '-') as employee_code
                  FROM employee_payments ep
                  LEFT JOIN employees e ON ep.employee_id = e.id
                  $where
                  ORDER BY ep.payment_date DESC, e.first_name";
        
        $items = $db->prepareSelect($query, $params)->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate Totals
        $totalEmployees = 0;
        $totalEarnings = 0;
        $totalPaid = 0;
        $totalPending = 0;
        $employeesMap = [];
        
        foreach ($items as $item) {
            $employeesMap[$item['employee_id']] = true;
            $totalEarnings += $item['total_amount'];
            if ($item['status'] == 'paid') $totalPaid += $item['total_amount'];
            else $totalPending += $item['total_amount'];
        }
        $totalEmployees = count($employeesMap);
        
        return [
            'data' => $items,
            'summary' => [
                'total_employees' => $totalEmployees,
                'total_earnings' => $totalEarnings,
                'total_paid' => $totalPaid,
                'total_pending' => $totalPending
            ]
        ];
    }
}
?>
