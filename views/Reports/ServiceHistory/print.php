<?php
require_once __DIR__ . '/../../../classes/Includes.php';

// Check permissions
requirePagePermission('View');

// Get Company Profile
$companyId = $_SESSION['company_id'] ?? 1;
$company = new CompanyProfile();
if (!$company->loadByCompanyId($companyId)) {
    $company = new CompanyProfile(1);
}

// Ensure company name has a fallback
$companyName = !empty($company->name) ? $company->name : (defined('APP_NAME') ? APP_NAME : 'Company Name');

// Get filter parameters
$filters = [];
if (!empty($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
if (!empty($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
if (!empty($_GET['customer_id'])) $filters['customer_id'] = (int)$_GET['customer_id'];
if (!empty($_GET['vehicle_id'])) $filters['vehicle_id'] = (int)$_GET['vehicle_id'];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['payment_status'])) $filters['payment_status'] = $_GET['payment_status'];

// Fetch data
$companyId = $_SESSION['company_id'] ?? 1;
$report = new Report($companyId);
$serviceHistory = $report->getServiceHistory($filters, 1, 10000); // Get all records
$summary = $report->getServiceHistorySummary($filters);

$items = $serviceHistory['data'];

// Helper for status colors
function getStatusColor($status) {
    switch ($status) {
        case 'completed': return '#10b981'; // Success
        case 'delivered': return '#059669'; // Darker Success
        case 'cancelled': return '#ef4444'; // Danger
        case 'in_progress': return '#3b82f6'; // Info
        case 'waiting': return '#f59e0b'; // Warning
        default: return '#6b7280'; // Gray
    }
}

function formatStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service History Report - <?php echo date('Y-m-d'); ?></title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; padding: 10px; background: white; color: #333; }
        .invoice-container { max-width: 100%; margin: 0 auto; background: white; }
        
        /* Company Header */
        .company-header { text-align: center; border-bottom: 3px solid <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>; padding-bottom: 20px; margin-bottom: 30px; }
        .company-header h1 { font-size: 32px; color: #2c3e50; margin-bottom: 5px; }
        .company-header p { color: #6c757d; margin: 5px 0; font-size: 14px; }
        
        .report-title { text-align: center; font-size: 24px; font-weight: 700; color: <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>; margin: 20px 0 10px 0; }
        .report-meta { text-align: center; color: #6c757d; font-size: 12px; margin-bottom: 30px; }

        /* Attributes Grid */
        .details-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .detail-box { border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; text-align: center; }
        .detail-label { display: block; font-size: 11px; text-transform: uppercase; color: #6c757d; margin-bottom: 5px; }
        .detail-value { font-weight: 700; color: #2c3e50; font-size: 14px; }

        /* Table */
        .stock-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .stock-table th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .stock-table td { border: 1px solid #dee2e6; padding: 8px; font-size: 11px; vertical-align: middle; }
        
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        
        /* Footer */
        .invoice-footer { margin-top: 50px; padding-top: 20px; border-top: 2px solid <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>; text-align: center; font-size: 10px; color: #6c757d; }
        
        /* Print Button */
        .print-button { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .print-button:hover { opacity: 0.9; }
        
        @media print {
            .print-button { display: none; }
            body { padding: 0; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Print Report</button>

    <div class="invoice-container">
        <!-- Company Header -->
        <div class="company-header">
            <?php if ($company->image_name && file_exists(__DIR__ . '/../../../uploads/company/' . $company->image_name)): ?>
                <img src="<?php echo BASE_URL; ?>uploads/company/<?php echo $company->image_name; ?>" alt="Company Logo" style="max-height: 60px; margin-bottom: 10px;">
            <?php endif; ?>
             <h1><?php echo htmlspecialchars($companyName); ?></h1>
            <?php if ($company->address): ?>
                <p><?php echo htmlspecialchars($company->address); ?></p>
            <?php endif; ?>
            <p>
                <?php if ($company->mobile_number_1): ?>Phone: <?php echo htmlspecialchars($company->mobile_number_1); ?><?php endif; ?>
                <?php if ($company->email): ?> | Email: <?php echo htmlspecialchars($company->email); ?><?php endif; ?>
            </p>
        </div>

        <div class="report-title">SERVICE HISTORY REPORT</div>
        <div class="report-meta">Generated on: <?php echo date('F d, Y h:i A'); ?></div>

        <div class="details-grid">
            <div class="detail-box">
                <span class="detail-label">Total Services</span>
                <span class="detail-value"><?php echo number_format($summary['total_services']); ?></span>
            </div>
            <div class="detail-box">
                <span class="detail-label">Completed</span>
                <span class="detail-value"><?php echo number_format($summary['completed_services']); ?></span>
            </div>
            <div class="detail-box">
                <span class="detail-label">Total Revenue</span>
                <span class="detail-value">LKR <?php echo number_format($summary['total_revenue'], 2); ?></span>
            </div>
            <div class="detail-box">
                <span class="detail-label">Pending Payment</span>
                <span class="detail-value">LKR <?php echo number_format($summary['pending_amount'], 2); ?></span>
            </div>
        </div>

        <table class="stock-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Job #</th>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 15%;">Customer</th>
                    <th style="width: 15%;">Vehicle</th>
                    <th style="width: 12%;">Package</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;" class="text-center">Payment</th>
                    <th style="width: 10%;" class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                        <?php 
                            $statusColor = getStatusColor($item['status']);
                            $amount = $item['invoice_amount'] ?? $item['service_amount'] ?? 0;
                            $paymentStatus = $item['payment_date'] ? 'Paid' : ($item['invoice_id'] ? 'Pending' : 'No Invoice');
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['job_number']); ?></strong></td>
                            <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($item['customer_name']); ?></div>
                                <small style="color: #666;"><?php echo htmlspecialchars($item['customer_phone']); ?></small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($item['registration_number']); ?></div>
                                <small style="color: #666;"><?php echo htmlspecialchars($item['make'] . ' ' . $item['model']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($item['package_name']); ?></td>
                            <td>
                                <span style="color: <?php echo $statusColor; ?>; font-weight: 600;">
                                    <?php echo formatStatus($item['status']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php echo $paymentStatus; ?>
                            </td>
                            <td class="text-end font-weight-bold">
                                <?php echo number_format($amount, 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px;">No service records found matching the criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Footer -->
        <div class="invoice-footer">
            <p>Generated by <?php echo APP_NAME; ?> System</p>
            <p>© <?php echo date('Y'); ?> Codeplay Studio. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
