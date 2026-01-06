<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../classes/Includes.php';

$booking = new Booking();
$response = ['success' => false, 'status' => 'error', 'message' => 'Invalid request'];

// Get session company_id and branch_id
$sessionCompanyId = $_SESSION['company_id'] ?? 1;
$sessionBranchId = $_SESSION['branch_id'] ?? null;

try {
    // Parse JSON input if Content-Type is application/json
    $input = null;
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
    }
    
    // Get action from GET, POST, or JSON body
    $action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? '');
    
    // Get current user/employee ID if logged in
    $userId = $_SESSION['id'] ?? null;
    
    switch ($action) {
        case 'create':
            // Use already parsed JSON input or POST data
            $postData = $input ?: $_POST;
            
            $data = [
                'customer_name' => trim($postData['customer_name'] ?? ''),
                'customer_mobile' => trim($postData['customer_mobile'] ?? ''),
                'customer_email' => trim($postData['customer_email'] ?? ''),
                'registration_number' => trim($postData['registration_number'] ?? ''),
                'vehicle_make' => trim($postData['vehicle_make'] ?? ''),
                'vehicle_model' => trim($postData['vehicle_model'] ?? ''),
                'service_package_id' => intval($postData['service_package_id'] ?? 0),
                'booking_date' => $postData['booking_date'] ?? '',
                'booking_time' => date('H:i:s', strtotime($postData['booking_time'] ?? 'now')),
                'estimated_duration' => intval($postData['estimated_duration'] ?? 0),
                'notes' => trim($postData['notes'] ?? ''),
                'total_amount' => floatval($postData['total_amount'] ?? 0)
            ];
            
            // Branch can be specified or use session default
            $branchId = isset($postData['branch_id']) && $postData['branch_id'] !== '' ? intval($postData['branch_id']) : $sessionBranchId;
            
            // Sanitize inputs
            foreach ($data as $key => $val) {
                if (is_string($val) && strtolower(trim($val)) === 'null') {
                    $data[$key] = '';
                }
            }
            
            // Validate required fields
            $missingFields = [];
            if (empty($data['customer_name'])) $missingFields[] = 'customer_name';
            if (empty($data['customer_mobile'])) $missingFields[] = 'customer_mobile';
            if (empty($data['registration_number'])) $missingFields[] = 'registration_number';
            if (empty($data['vehicle_make'])) $missingFields[] = 'vehicle_make';
            if (empty($data['vehicle_model'])) $missingFields[] = 'vehicle_model';
            if ($data['service_package_id'] <= 0) $missingFields[] = 'service_package_id';
            if (empty($data['booking_date'])) $missingFields[] = 'booking_date';
            if (empty($data['booking_time'])) $missingFields[] = 'booking_time';
            if ($data['estimated_duration'] <= 0) $missingFields[] = 'estimated_duration';
            if ($data['total_amount'] <= 0) $missingFields[] = 'total_amount';
            
            if (!empty($missingFields)) {
                $response['message'] = 'Missing or invalid required fields: ' . implode(', ', $missingFields);
                break;
            }
            
            // Validate mobile format (Sri Lankan)
            if (!preg_match('/^94\d{9}$/', $data['customer_mobile'])) {
                $response['message'] = 'Invalid mobile number format. Use: 94XXXXXXXXX';
                break;
            }
            
            // Check time slot availability for this company
            $availability = $booking->checkTimeSlotAvailability($data['booking_date'], $data['booking_time'], $sessionCompanyId);
            if (!$availability['available']) {
                $response['message'] = 'Selected time slot is not available';
                break;
            }
            
            $result = $booking->create($data, $sessionCompanyId, $branchId);
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Booking created successfully',
                    'booking_id' => $result['booking_id'],
                    'booking_number' => $result['booking_number']
                ];
            } else {
                $response['message'] = $result['message'] ?? 'Failed to create booking';
            }
            break;
            
        case 'approve':
            if (!$userId) {
                $response['message'] = 'Authentication required';
                http_response_code(401);
                break;
            }
            
            $bookingId = intval($_POST['booking_id'] ?? 0);
            
            if (empty($bookingId)) {
                $response['message'] = 'Missing booking ID';
                break;
            }
            
            // Security: verify booking belongs to company
            $bookingCheck = $booking->getById($bookingId, $sessionCompanyId);
            if (!$bookingCheck) {
                $response['message'] = 'Booking not found';
                break;
            }
            
            $employee = new Employee();
            $curEmp = $employee->getByUserId($userId);
            $employeeId = $curEmp ? $curEmp->id : null;

            $result = $booking->updateStatus($bookingId, 'approved', $employeeId, $sessionCompanyId);
            if ($result['success']) {
                // Feature: Auto-create Service Record
                $bookingData = $booking->getById($bookingId, $sessionCompanyId);
                if ($bookingData) {
                    $service = new Service();
                    $serviceResult = $service->createFromBooking($bookingData, $employeeId, $sessionCompanyId);
                    
                    if (!$serviceResult['success']) {
                        error_log('Failed to auto-create service for booking #' . $bookingId . ': ' . ($serviceResult['message'] ?? 'Unknown error'));
                    }
                }

                $response = [
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Booking approved successfully'
                ];
            } else {
                $response['message'] = $result['message'] ?? 'Failed to approve booking';
            }
            break;
            
        case 'reject':
            if (!$userId) {
                $response['message'] = 'Authentication required';
                http_response_code(401);
                break;
            }
            
            $bookingId = intval($_POST['booking_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            
            if (empty($bookingId) || empty($reason)) {
                $response['message'] = 'Missing booking ID or rejection reason';
                break;
            }
            
            // Security: verify booking belongs to company
            $bookingCheck = $booking->getById($bookingId, $sessionCompanyId);
            if (!$bookingCheck) {
                $response['message'] = 'Booking not found';
                break;
            }
            
            $employee = new Employee();
            $curEmp = $employee->getByUserId($userId);
            $employeeId = $curEmp ? $curEmp->id : null;

            $result = $booking->updateStatus($bookingId, 'rejected', $employeeId, $sessionCompanyId, $reason);
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Booking rejected successfully'
                ];
            } else {
                $response['message'] = $result['message'] ?? 'Failed to reject booking';
            }
            break;
            
        case 'list':
            $status = $_GET['status'] ?? null;
            $date = $_GET['date'] ?? null;
            $branchId = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            
            $bookings = $booking->all($sessionCompanyId, $status, $date, $branchId);
            $response = [
                'success' => true,
                'status' => 'success',
                'data' => $bookings,
                'count' => count($bookings)
            ];
            break;
            
        case 'get':
            $bookingId = intval($_GET['id'] ?? 0);
            
            if (empty($bookingId)) {
                $response['message'] = 'Missing booking ID';
                break;
            }
            
            // Security: filter by company
            $bookingData = $booking->getById($bookingId, $sessionCompanyId);
            if ($bookingData) {
                $response = [
                    'success' => true,
                    'status' => 'success',
                    'data' => $bookingData
                ];
            } else {
                $response['message'] = 'Booking not found';
            }
            break;
            
        case 'check_availability':
            $date = $_GET['date'] ?? '';
            $time = $_GET['time'] ?? '';
            
            if (empty($date) || empty($time)) {
                $response['message'] = 'Missing date or time';
                break;
            }
            
            $availability = $booking->checkTimeSlotAvailability($date, $time, $sessionCompanyId);
            $response = [
                'success' => true,
                'status' => 'success',
                'data' => $availability
            ];
            break;
            
        case 'get_time_slots':
            $date = $_GET['date'] ?? '';
            
            if (empty($date)) {
                $response['message'] = 'Missing date';
                break;
            }
            
            $timeSlots = $booking->getAvailableTimeSlots($date, $sessionCompanyId);
            $response = [
                'success' => true,
                'status' => 'success',
                'data' => $timeSlots,
                'count' => count($timeSlots)
            ];
            break;
            
        case 'statistics':
            $branchId = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            $stats = $booking->getStatistics($sessionCompanyId, $branchId);
            if ($stats) {
                $response = [
                    'success' => true,
                    'status' => 'success',
                    'data' => $stats
                ];
            } else {
                $response['message'] = 'Failed to get statistics';
            }
            break;
            
        case 'recent':
            $limit = intval($_GET['limit'] ?? 10);
            $limit = max(1, min($limit, 100));
            $branchId = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            
            $bookings = $booking->getRecentBookings($sessionCompanyId, $limit, $branchId);
            $response = [
                'success' => true,
                'status' => 'success',
                'data' => $bookings,
                'count' => count($bookings)
            ];
            break;
            
        case 'pending':
            $branchId = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            $bookings = $booking->getPendingBookings($sessionCompanyId, $branchId);
            $response = [
                'success' => true,
                'status' => 'success',
                'data' => $bookings,
                'count' => count($bookings)
            ];
            break;
            
        case 'today':
            $branchId = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            $bookings = $booking->getTodayBookings($sessionCompanyId, $branchId);
            $response = [
                'success' => true,
                'status' => 'success',
                'data' => $bookings,
                'count' => count($bookings)
            ];
            break;

        case 'get_branches':
            $branch = new Branch();
            $branches = $branch->getActiveByCompany($sessionCompanyId);
            $response = ['success' => true, 'status' => 'success', 'data' => $branches];
            break;
            
        default:
            $response['message'] = 'Unknown action: ' . htmlspecialchars($action);
            http_response_code(400);
            break;
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'status' => 'error',
        'message' => 'Server error occurred'
    ];
    error_log('Booking API Error: ' . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response);
?>