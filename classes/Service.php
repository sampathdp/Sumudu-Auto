<?php
class Service
{
    public $id;
    public $company_id;
    public $branch_id;
    public $job_number;
    public $customer_id;
    public $vehicle_id;
    public $package_id;
    public $assigned_employee_id;
    public $qr_id;
    public $status;
    public $current_stage_id;
    public $progress_percentage;
    public $start_time;
    public $expected_completion_time;
    public $actual_completion_time;
    public $total_amount;
    public $payment_status;
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

    private function loadById($id)
    {
        $query = "SELECT * FROM services WHERE id = ?";
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
        $this->id                    = $row['id'];
        $this->company_id            = $row['company_id'];
        $this->branch_id             = $row['branch_id'];
        $this->job_number            = $row['job_number'];
        $this->customer_id           = $row['customer_id'];
        $this->vehicle_id            = $row['vehicle_id'];
        $this->package_id            = $row['package_id'];
        $this->assigned_employee_id  = $row['assigned_employee_id'];
        $this->qr_id                 = $row['qr_id'];
        $this->status                = $row['status'];
        $this->current_stage_id      = $row['current_stage_id'];
        $this->progress_percentage   = (int)$row['progress_percentage'];
        $this->start_time            = $row['start_time'];
        $this->expected_completion_time = $row['expected_completion_time'];
        $this->actual_completion_time = $row['actual_completion_time'];
        $this->total_amount          = (float)$row['total_amount'];
        $this->payment_status        = $row['payment_status'];
        $this->notes                 = $row['notes'];
        $this->created_at            = $row['created_at'];
        $this->updated_at            = $row['updated_at'];
    }

    private function generateJobNumber($companyId)
    {
        $prefix = "JOB";
        $date   = date('Ymd');

        $query = "SELECT job_number FROM services WHERE company_id = ? AND job_number LIKE ? ORDER BY id DESC LIMIT 1";
        $stmt  = $this->db->prepareSelect($query, [$companyId, $prefix . $date . '%']);

        $sequence = 1;
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $sequence = (int)substr($row['job_number'], strlen($prefix . $date)) + 1;
            }
        }

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    private function generateUniqueQRCode($companyId)
    {
        do {
            $qrCode = 'QR' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $query  = "SELECT id FROM qr_codes WHERE company_id = ? AND qr_code = ?";
            $stmt   = $this->db->prepareSelect($query, [$companyId, $qrCode]);
        } while ($stmt && $stmt->rowCount() > 0);

        return $qrCode;
    }

    public function create()
    {
        if (empty($this->company_id) || empty($this->customer_id) || empty($this->vehicle_id) || empty($this->package_id)) {
            return false;
        }

        $this->job_number = $this->generateJobNumber($this->company_id);

        // Create QR code for this company
        $qrCode = new QRCode();
        $qrCode->company_id = $this->company_id;
        $qrCode->qr_code   = $this->generateUniqueQRCode($this->company_id);
        $qrCode->status    = 'active';
        $qrCode->color_code = 'red';

        if (!$qrCode->create()) {
            return false;
        }

        $this->qr_id = $qrCode->id;

        // Get package price and duration
        $package = new ServicePackage($this->package_id);
        $this->total_amount = $this->total_amount ?: $package->base_price;

        $this->status                 = 'waiting';
        $this->current_stage_id       = 1;
        $this->progress_percentage    = 0;
        $this->payment_status         = 'pending';
        $this->start_time             = date('Y-m-d H:i:s');
        $this->expected_completion_time = date('Y-m-d H:i:s', strtotime($this->start_time) + ($package->estimated_duration * 60));

        $query = "INSERT INTO services (
            company_id, branch_id, job_number, customer_id, vehicle_id, package_id, assigned_employee_id,
            qr_id, status, current_stage_id, progress_percentage, start_time,
            expected_completion_time, total_amount, payment_status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->branch_id,
            $this->job_number,
            $this->customer_id,
            $this->vehicle_id,
            $this->package_id,
            $this->assigned_employee_id,
            $this->qr_id,
            $this->status,
            $this->current_stage_id,
            $this->progress_percentage,
            $this->start_time,
            $this->expected_completion_time,
            $this->total_amount,
            $this->payment_status,
            $this->notes
        ]);

        if (!$success) {
            return false;
        }

        $this->id = $this->db->getLastInsertId();

        // Link QR to service
        $qrCode->service_id = $this->id;
        $qrCode->update();

        return [
            'id'           => $this->id,
            'job_number'   => $this->job_number,
            'qr_code'      => $this->getTrackingUrl(),
            'total_amount' => $this->total_amount,
            'status'       => $this->status
        ];
    }

    /**
     * Create a service job with multiple packages
     * @param array $packageIds Array of package IDs to add to this service
     * @return array|false Result array on success, false on failure
     */
    public function createWithPackages($packageData = [])
    {
        if (empty($this->company_id) || empty($this->customer_id) || empty($this->vehicle_id)) {
            return false;
        }

        if (empty($packageData) || !is_array($packageData)) {
            return false;
        }

        $this->job_number = $this->generateJobNumber($this->company_id);

        // Create QR code for this company
        $qrCode = new QRCode();
        $qrCode->company_id = $this->company_id;
        $qrCode->qr_code   = $this->generateUniqueQRCode($this->company_id);
        $qrCode->status    = 'active';
        $qrCode->color_code = 'red';

        if (!$qrCode->create()) {
            return false;
        }

        $this->qr_id = $qrCode->id;

        // Calculate total from packages and estimate duration
        $totalAmount = 0;
        $totalDuration = 0;
        
        foreach ($packageData as $data) {
            $pkgId = is_array($data) ? ($data['id'] ?? null) : $data;
            $customPrice = is_array($data) && isset($data['price']) ? $data['price'] : null;
            
            if (!$pkgId) continue;
            
            $pkg = new ServicePackage($pkgId);
            if ($pkg->id) {
                $totalAmount += ($customPrice !== null) ? (float)$customPrice : $pkg->base_price;
                $totalDuration += $pkg->estimated_duration;
            }
        }

        $this->total_amount = $totalAmount;
        $this->package_id = null; // Multi-package: no single package_id
        $this->status = 'waiting';
        $this->current_stage_id = 1;
        $this->progress_percentage = 0;
        $this->payment_status = 'pending';
        $this->start_time = date('Y-m-d H:i:s');
        $this->expected_completion_time = date('Y-m-d H:i:s', strtotime($this->start_time) + ($totalDuration * 60));

        $query = "INSERT INTO services (
            company_id, branch_id, job_number, customer_id, vehicle_id, package_id, assigned_employee_id,
            qr_id, status, current_stage_id, progress_percentage, start_time,
            expected_completion_time, total_amount, payment_status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->branch_id,
            $this->job_number,
            $this->customer_id,
            $this->vehicle_id,
            $this->package_id,
            $this->assigned_employee_id,
            $this->qr_id,
            $this->status,
            $this->current_stage_id,
            $this->progress_percentage,
            $this->start_time,
            $this->expected_completion_time,
            $this->total_amount,
            $this->payment_status,
            $this->notes
        ]);

        if (!$success) {
            return false;
        }

        $this->id = $this->db->getLastInsertId();

        // Link QR to service
        $qrCode->service_id = $this->id;
        $qrCode->update();

        // Add service items for each package
        foreach ($packageData as $data) {
            $pkgId = is_array($data) ? ($data['id'] ?? null) : $data;
            $customPrice = is_array($data) && isset($data['price']) ? $data['price'] : null;
            
            if ($pkgId) {
                ServiceItem::addPackageToService($this->id, $pkgId, $this->company_id, 1, $customPrice);
            }
        }

        return [
            'id'           => $this->id,
            'job_number'   => $this->job_number,
            'qr_code'      => $this->getTrackingUrl(),
            'total_amount' => $this->total_amount,
            'status'       => $this->status,
            'items_count'  => count($packageData)
        ];
    }

    /**
     * Get all service items for this service
     */
    public function getServiceItems()
    {
        if (!$this->id) {
            return [];
        }
        return ServiceItem::getByServiceId($this->id);
    }

    /**
     * Recalculate and update total_amount from service_items
     */
    public function recalculateTotal()
    {
        if (!$this->id) {
            return false;
        }
        $this->total_amount = ServiceItem::calculateServiceTotal($this->id);
        $query = "UPDATE services SET total_amount = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->total_amount, $this->id]);
    }

    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Get old status before update
        $oldStatusQuery = "SELECT status FROM services WHERE id = ?";
        $oldStmt = $this->db->prepareSelect($oldStatusQuery, [$this->id]);
        $oldStatus = $oldStmt ? $oldStmt->fetch()['status'] : null;

        $query = "UPDATE services SET 
            branch_id = ?, customer_id = ?, vehicle_id = ?, package_id = ?, assigned_employee_id = ?,
            status = ?, current_stage_id = ?, progress_percentage = ?, total_amount = ?,
            payment_status = ?, notes = ?
            WHERE id = ?";

        $success = $this->db->prepareExecute($query, [
            $this->branch_id,
            $this->customer_id,
            $this->vehicle_id,
            $this->package_id,
            $this->assigned_employee_id,
            $this->status,
            $this->current_stage_id,
            $this->progress_percentage,
            $this->total_amount,
            $this->payment_status,
            $this->notes,
            $this->id
        ]);

        // Handle invoice creation and payment based on status changes
        if ($success) {
            if ($oldStatus !== 'completed' && $this->status === 'completed') {
                $invoice = new Invoice();
                $result = $invoice->createFromService($this->id, $this->company_id, $this->branch_id);
                if ($result['success']) {
                    error_log("Auto-created invoice {$result['invoice_number']} for service {$this->job_number}");
                }
            }
            
            if ($oldStatus !== 'delivered' && $this->status === 'delivered') {
                $invoice = new Invoice();
                if (!$invoice->getByServiceId($this->id)) {
                    $invoice->createFromService($this->id, $this->company_id, $this->branch_id);
                }
                $invoice->payment_date = date('Y-m-d H:i:s');
                $invoice->payment_method = $invoice->payment_method ?? 'cash';
                $invoice->update();
            }

            if ($oldStatus !== 'cancelled' && $this->status === 'cancelled') {
                $invoice = new Invoice();
                if ($invoice->getByServiceId($this->id)) {
                    $invoice->cancel();
                }
            }
        }

        return $success;
    }

    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM services WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    public function updateStage($stageId)
    {
        $stage = new ServiceStage($stageId);
        if (!$stage->id) {
            return false;
        }

        $this->current_stage_id = $stageId;
        $stageName = strtolower($stage->stage_name);
        
        if ($stageName === 'delivered') {
            $this->status = 'delivered';
            $this->progress_percentage = 100;
        } elseif ($stageName === 'cancelled') {
            $this->status = 'cancelled';
            $this->progress_percentage = 0;
        } elseif ($stageName === 'ready for delivery') {
            $this->status = 'completed';
        } elseif (in_array($stageName, ['registration', 'pending'])) {
            $this->status = 'waiting';
        } else {
            if ($stageName === 'quality check') {
                $this->status = 'quality_check';
            } else {
                $this->status = 'in_progress';
            }
        }

        if ($stageName !== 'cancelled' && $stageName !== 'delivered') {
            $query = "SELECT MAX(stage_order) as max_order FROM service_stages WHERE company_id = ?";
            $stmt = $this->db->prepareSelect($query, [$this->company_id]);
            $maxOrder = $stmt ? ($stmt->fetch()['max_order'] ?? 10) : 10;
            
            $percentage = ($stage->stage_order / $maxOrder) * 100;
            $this->progress_percentage = min(95, round($percentage));
        }

        return $this->update();
    }

    public function getByJobNumber($jobNumber, $companyId = null)
    {
        $query = "SELECT * FROM services WHERE job_number = ?";
        $params = [$jobNumber];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
                return true;
            }
        }
        return false;
    }

    public function getByQRCode($qrCode, $companyId = null)
    {
        $query = "SELECT s.* FROM services s
                  INNER JOIN qr_codes qr ON s.qr_id = qr.id
                  WHERE qr.qr_code = ?";
        $params = [$qrCode];
        
        if ($companyId) {
            $query .= " AND s.company_id = ?";
            $params[] = $companyId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
                return true;
            }
        }
        return false;
    }

    public function getTrackingUrl()
    {
        if (empty($this->qr_id)) {
            return '';
        }
        $qr = new QRCode($this->qr_id);
        if (!$qr->id) {
            return '';
        }
        return rtrim(BASE_URL, '/') . "/views/Service/track_service.php?qr=" . $qr->qr_code;
    }

    public function all($companyId = null, $branchId = null, $dateFilter = null, $date = null, $status = null)
    {
        $query = "SELECT 
            s.*,
            c.name as customer_name,
            c.phone as customer_phone,
            v.make, v.model, v.registration_number,
            sp.package_name,
            qr.qr_code,
            br.branch_name
        FROM services s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.id
        LEFT JOIN service_packages sp ON s.package_id = sp.id
        LEFT JOIN qr_codes qr ON s.qr_id = qr.id
        LEFT JOIN branches br ON s.branch_id = br.id
        WHERE 1=1";

        $params = [];

        if ($companyId) {
            $query .= " AND s.company_id = ?";
            $params[] = $companyId;
        }

        if ($branchId) {
            $query .= " AND s.branch_id = ?";
            $params[] = $branchId;
        }

        if ($dateFilter === 'today') {
            $query .= " AND DATE(s.created_at) = CURDATE()";
        } elseif ($date) {
            $query .= " AND DATE(s.created_at) = ?";
            $params[] = $date;
        }

        if ($status) {
            $query .= " AND s.status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY s.created_at DESC";

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function getByDateRange($companyId, $startDate, $endDate, $branchId = null)
    {
        $query = "SELECT 
            s.*,
            c.name as customer_name,
            c.phone as customer_phone,
            v.make, v.model, v.registration_number,
            sp.package_name,
            qr.qr_code,
            br.branch_name
        FROM services s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.id
        LEFT JOIN service_packages sp ON s.package_id = sp.id
        LEFT JOIN qr_codes qr ON s.qr_id = qr.id
        LEFT JOIN branches br ON s.branch_id = br.id
        WHERE s.company_id = ? AND DATE(s.created_at) BETWEEN ? AND ?";
        
        $params = [$companyId, $startDate, $endDate];
        
        if ($branchId) {
            $query .= " AND s.branch_id = ?";
            $params[] = $branchId;
        }

        $query .= " ORDER BY s.created_at DESC";

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function createFromBooking($bookingData, $employeeId, $companyId, $branchId = null)
    {
        try {
            // 1. Handle Customer
            $customer = new Customer();
            $customerData = $customer->getByPhone($bookingData['customer_mobile'], $companyId);
            
            if (!$customerData) {
                $customer->company_id = $companyId;
                $customer->name = $bookingData['customer_name'];
                $customer->phone = $bookingData['customer_mobile'];
                $customer->email = $bookingData['customer_email'];
                $customer->address = 'Created from Booking #' . $bookingData['booking_number'];
                
                if (!$customer->create()) {
                    throw new Exception("Failed to create customer record.");
                }
                $customerId = $customer->id;
            } else {
                $customerId = $customerData['id'];
            }
            
            // 2. Handle Vehicle
            $vehicle = new Vehicle();
            $regNumber = $bookingData['registration_number'] ?? ('TMP-' . $bookingData['booking_number']);
            
            $existingVehicleQuery = "SELECT id, customer_id FROM vehicles WHERE company_id = ? AND registration_number = ?";
            $stmt = $this->db->prepareSelect($existingVehicleQuery, [$companyId, $regNumber]);
            
            if ($stmt && $row = $stmt->fetch()) {
                $vehicleId = $row['id'];
            } else {
                $vehicle->company_id = $companyId;
                $vehicle->customer_id = $customerId;
                $vehicle->registration_number = $regNumber;
                $vehicle->make = $bookingData['vehicle_make'] ?? 'Unknown'; 
                $vehicle->model = $bookingData['vehicle_model'] ?? 'Unknown'; 
                $vehicle->year = date('Y');
                $vehicle->color = 'Unknown';
                $vehicle->current_mileage = 0;
                
                if (!$vehicle->create()) {
                    throw new Exception("Failed to create vehicle record for " . $regNumber);
                }
                $vehicleId = $vehicle->id;
            }
            
            // 3. Create Service
            $this->company_id = $companyId;
            $this->branch_id = $branchId ?? $bookingData['branch_id'] ?? null;
            $this->customer_id = $customerId;
            $this->vehicle_id = $vehicleId;
            $this->package_id = $bookingData['service_package_id'];
            $this->assigned_employee_id = $employeeId;
            $this->notes = "Booking Ref: #" . $bookingData['booking_number'] . "\n" . ($bookingData['notes'] ?? '');
            
            $result = $this->create();
            
            if ($result) {
                return ['success' => true, 'service_id' => $result['id'], 'job_number' => $result['job_number']];
            }
            
            return ['success' => false, 'message' => 'Failed to create service record'];
            
        } catch (Exception $e) {
            error_log("Service creation from booking failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function countByCompany($companyId)
    {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM services WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }

    public function getStatistics($companyId, $branchId = null)
    {
        $query = "SELECT 
            COUNT(*) as total_services,
            SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue
            FROM services WHERE company_id = ?";
        
        $params = [$companyId];
        
        if ($branchId) {
            $query .= " AND branch_id = ?";
            $params[] = $branchId;
        }
        
        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetch() : null;
    }
}
?>