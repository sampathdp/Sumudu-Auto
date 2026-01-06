<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../../classes/Includes.php';

// Clear any output that might have been generated
ob_end_clean();

// Set JSON header before any output
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

try {
    $sessionCompanyId = $_SESSION['company_id'];
    $report = new Report($sessionCompanyId);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_data':
            // Get filters from request
            $filters = [];
            
            if (!empty($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            if (!empty($_GET['customer_id'])) {
                $filters['customer_id'] = (int)$_GET['customer_id'];
            }
            if (!empty($_GET['vehicle_id'])) {
                $filters['vehicle_id'] = (int)$_GET['vehicle_id'];
            }
            if (!empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (!empty($_GET['payment_status'])) {
                $filters['payment_status'] = $_GET['payment_status'];
            }

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            // Get service history and summary
            $serviceHistory = $report->getServiceHistory($filters, $page, $perPage);
            $summary = $report->getServiceHistorySummary($filters);

            echo json_encode([
                'success' => true,
                'data' => $serviceHistory['data'],
                'pagination' => $serviceHistory['pagination'],
                'summary' => $summary
            ]);
            break;

        case 'get_customers':
            $customers = $report->getAllCustomersForFilter();
            echo json_encode([
                'success' => true,
                'data' => $customers
            ]);
            break;

        case 'get_vehicles':
            $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
            
            if ($customerId > 0) {
                $vehicles = $report->getVehiclesByCustomer($customerId);
            } else {
                $vehicles = $report->getAllVehiclesForFilter();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $vehicles
            ]);
            break;

        case 'export_pdf':
            // Return filter data for PDF export (handled client-side)
            $filters = [];
            
            if (!empty($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            if (!empty($_GET['customer_id'])) {
                $filters['customer_id'] = (int)$_GET['customer_id'];
            }
            if (!empty($_GET['vehicle_id'])) {
                $filters['vehicle_id'] = (int)$_GET['vehicle_id'];
            }
            if (!empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }

            // Get all data (no pagination for PDF)
            $serviceHistory = $report->getServiceHistory($filters, 1, 1000);
            $summary = $report->getServiceHistorySummary($filters);

            // Get customer and vehicle names for display
            $customerName = 'All Customers';
            $vehicleName = 'All Vehicles';

            if (!empty($filters['customer_id'])) {
                $customers = $report->getAllCustomersForFilter();
                foreach ($customers as $customer) {
                    if ($customer['id'] == $filters['customer_id']) {
                        $customerName = $customer['name'];
                        break;
                    }
                }
            }

            if (!empty($filters['vehicle_id'])) {
                $vehicles = $report->getAllVehiclesForFilter();
                foreach ($vehicles as $vehicle) {
                    if ($vehicle['id'] == $filters['vehicle_id']) {
                        $vehicleName = $vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model'];
                        break;
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $serviceHistory['data'],
                'summary' => $summary,
                'filters' => [
                    'start_date' => $filters['start_date'] ?? null,
                    'end_date' => $filters['end_date'] ?? null,
                    'customer_name' => $customerName,
                    'vehicle_name' => $vehicleName,
                    'status' => $filters['status'] ?? 'All'
                ]
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
