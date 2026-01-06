<?php
require_once 'Database.php';

class Booking {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Create a new booking
     */
    public function create($data, $companyId, $branchId = null) {
        try {
            // Generate unique booking number per company
            $bookingNumber = 'BK' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO bookings (
                company_id,
                branch_id,
                booking_number, 
                customer_name, 
                customer_mobile, 
                customer_email, 
                registration_number,
                vehicle_make,
                vehicle_model,
                service_package_id, 
                booking_date, 
                booking_time, 
                estimated_duration, 
                notes, 
                total_amount,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval')";
            
            $params = [
                $companyId,
                $branchId,
                $bookingNumber,
                $data['customer_name'],
                $data['customer_mobile'],
                $data['customer_email'] ?? null,
                $data['registration_number'],
                $data['vehicle_make'],
                $data['vehicle_model'],
                $data['service_package_id'],
                $data['booking_date'],
                $data['booking_time'],
                $data['estimated_duration'],
                $data['notes'] ?? null,
                $data['total_amount']
            ];
            
            $result = $this->db->prepareExecute($sql, $params);
            
            if ($result) {
                $bookingId = $this->db->getLastInsertId();
                
                // Create notification
                $this->createNotification(
                    $bookingId, 
                    'booking_received', 
                    $data['customer_mobile'], 
                    $data['customer_email'] ?? null,
                    $companyId
                );
                
                return [
                    'success' => true,
                    'booking_id' => $bookingId,
                    'booking_number' => $bookingNumber
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create booking'];
            
        } catch (Exception $e) {
            error_log('Booking creation error: ' . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Failed to create booking: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all bookings with optional filtering
     */
    public function all($companyId, $status = null, $date = null, $branchId = null) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "SELECT b.*, 
                    sp.package_name, 
                    sp.description as package_description,
                    CONCAT(e.first_name, ' ', e.last_name) as approver_name,
                    br.branch_name
                    FROM bookings b
                    LEFT JOIN service_packages sp ON b.service_package_id = sp.id
                    LEFT JOIN employees e ON b.approved_by_employee_id = e.id
                    LEFT JOIN branches br ON b.branch_id = br.id
                    WHERE b.company_id = ?";
            
            $params = [$companyId];
            
            if ($branchId) {
                $sql .= " AND b.branch_id = ?";
                $params[] = $branchId;
            }
            
            if ($status) {
                $sql .= " AND b.status = ?";
                $params[] = $status;
            }
            
            if ($date) {
                $sql .= " AND b.booking_date = ?";
                $params[] = $date;
            }
            
            $sql .= " ORDER BY b.created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Booking fetch error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get booking by ID
     */
    public function getById($id, $companyId = null) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "SELECT b.*, 
                    sp.package_name, 
                    sp.description as package_description,
                    CONCAT(e.first_name, ' ', e.last_name) as approver_name,
                    br.branch_name
                    FROM bookings b
                    LEFT JOIN service_packages sp ON b.service_package_id = sp.id
                    LEFT JOIN employees e ON b.approved_by_employee_id = e.id
                    LEFT JOIN branches br ON b.branch_id = br.id
                    WHERE b.id = ?";
            
            $params = [$id];
            
            if ($companyId) {
                $sql .= " AND b.company_id = ?";
                $params[] = $companyId;
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Booking fetch by ID error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update booking status (approve/reject)
     */
    public function updateStatus($bookingId, $status, $employeeId = null, $companyId = null, $rejectionReason = null) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "UPDATE bookings SET 
                    status = ?, 
                    approved_by_employee_id = ?, 
                    approved_at = NOW(),
                    rejection_reason = ?
                    WHERE id = ?";
            
            $params = [$status, $employeeId, $rejectionReason, $bookingId];
            
            if ($companyId) {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Get booking details for notification
                $booking = $this->getById($bookingId, $companyId);
                
                if ($booking) {
                    // Create notification
                    $notificationType = ($status === 'approved') ? 'booking_approved' : 'booking_rejected';
                    $this->createNotification(
                        $bookingId, 
                        $notificationType, 
                        $booking['customer_mobile'], 
                        $booking['customer_email'] ?? null,
                        $booking['company_id']
                    );
                }
                
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Failed to update booking status'];
            
        } catch (Exception $e) {
            error_log('Booking status update error: ' . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Failed to update status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get pending bookings for approval
     */
    public function getPendingBookings($companyId, $branchId = null) {
        return $this->all($companyId, 'pending_approval', null, $branchId);
    }
    
    /**
     * Get today's bookings
     */
    public function getTodayBookings($companyId, $branchId = null) {
        return $this->all($companyId, null, date('Y-m-d'), $branchId);
    }
    
    /**
     * Check time slot availability
     */
    public function checkTimeSlotAvailability($date, $time, $companyId) {
        try {
            $conn = $this->db->getConnection();
            
            // Count existing bookings for this date/time within company
            $sql = "SELECT COUNT(*) as booked_count 
                    FROM bookings 
                    WHERE company_id = ?
                    AND booking_date = ? 
                    AND booking_time = ? 
                    AND status IN ('pending_approval', 'approved')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$companyId, $date, $time]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $bookedCount = intval($result['booked_count']);
            
            // Get max bookings from time_slots table for this company
            $timeSql = "SELECT max_bookings 
                        FROM time_slots 
                        WHERE company_id = ?
                        AND slot_start = ? 
                        AND is_active = 1
                        LIMIT 1";
            
            $timeStmt = $conn->prepare($timeSql);
            $timeStmt->execute([$companyId, $time]);
            $timeResult = $timeStmt->fetch(PDO::FETCH_ASSOC);
            
            $maxBookings = $timeResult ? intval($timeResult['max_bookings']) : 3;
            
            return [
                'available' => $bookedCount < $maxBookings,
                'booked_count' => $bookedCount,
                'max_bookings' => $maxBookings
            ];
            
        } catch (Exception $e) {
            error_log('Availability check error: ' . $e->getMessage());
            return [
                'available' => false, 
                'message' => 'Error checking availability: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get available time slots for a date within company
     */
    public function getAvailableTimeSlots($date, $companyId) {
        try {
            $conn = $this->db->getConnection();
            
            // Get all active time slots for this company
            $sql = "SELECT 
                    ts.slot_start, 
                    ts.slot_end, 
                    ts.max_bookings,
                    (SELECT COUNT(*) 
                     FROM bookings b 
                     WHERE b.company_id = ?
                     AND b.booking_date = ? 
                     AND b.booking_time = ts.slot_start 
                     AND b.status IN ('pending_approval', 'approved')
                    ) as booked_count
                    FROM time_slots ts
                    WHERE ts.company_id = ? AND ts.is_active = 1
                    ORDER BY ts.slot_start";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$companyId, $date, $companyId]);
            $timeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $availableSlots = [];
            
            foreach ($timeSlots as $slot) {
                $bookedCount = intval($slot['booked_count']);
                $maxBookings = intval($slot['max_bookings']);
                $availableCount = $maxBookings - $bookedCount;
                
                if ($availableCount > 0) {
                    $availableSlots[] = [
                        'time' => date('h:i A', strtotime($slot['slot_start'])),
                        'time_24h' => $slot['slot_start'],
                        'available_slots' => $availableCount
                    ];
                }
            }
            
            return $availableSlots;
            
        } catch (Exception $e) {
            error_log('Time slots fetch error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create booking notification
     */
    private function createNotification($bookingId, $type, $mobile, $email = null, $companyId = null) {
        try {
            $conn = $this->db->getConnection();
            
            $messages = [
                'booking_received' => 'Your booking request has been received and is pending approval.',
                'booking_approved' => 'Your booking has been approved! We look forward to serving you.',
                'booking_rejected' => 'Your booking request could not be accommodated. Please contact us for alternative options.',
                'booking_reminder' => 'Reminder: You have a service scheduled for tomorrow.',
                'booking_cancelled' => 'Your booking has been cancelled as requested.'
            ];
            
            $message = $messages[$type] ?? 'Booking notification';
            
            $sql = "INSERT INTO booking_notifications (
                booking_id, 
                notification_type, 
                recipient_mobile, 
                recipient_email, 
                message
            ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([$bookingId, $type, $mobile, $email, $message]);
            
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get booking statistics for a company
     */
    public function getStatistics($companyId, $branchId = null) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status IN ('approved', 'completed') THEN total_amount ELSE 0 END) as total_revenue
                    FROM bookings
                    WHERE company_id = ?";
            
            $params = [$companyId];
            
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Statistics fetch error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get recent bookings (last 7 days) for a company
     */
    public function getRecentBookings($companyId, $limit = 10, $branchId = null) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "SELECT b.*, sp.package_name, br.branch_name
                    FROM bookings b
                    LEFT JOIN service_packages sp ON b.service_package_id = sp.id
                    LEFT JOIN branches br ON b.branch_id = br.id
                    WHERE b.company_id = ? AND b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            
            $params = [$companyId];
            
            if ($branchId) {
                $sql .= " AND b.branch_id = ?";
                $params[] = $branchId;
            }
            
            $sql .= " ORDER BY b.created_at DESC LIMIT " . intval($limit);
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Recent bookings fetch error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count bookings for a company
     */
    public static function countByCompany($companyId) {
        $db = new Database();
        $stmt = $db->prepareSelect("SELECT COUNT(*) as count FROM bookings WHERE company_id = ?", [$companyId]);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row['count'] ?? 0;
        }
        return 0;
    }
}
?>