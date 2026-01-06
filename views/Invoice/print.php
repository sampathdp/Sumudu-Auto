<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in and has permission to view this page
requirePagePermission('Print');

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$invoiceId = (int) $_GET['id'];
$invoice = new Invoice($invoiceId);

if (!$invoice->id) {
    header('Location: index.php');
    exit();
}

// Get Company Profile
$company = new CompanyProfile();
if (!$company->loadActive()) {
    // If no active profile, try to load the first one as fallback
    $company = new CompanyProfile(1);
}

// Ensure company name has a fallback
$companyName = !empty($company->name) ? $company->name : (defined('APP_NAME') ? APP_NAME : 'Company Name');

// Get related data if linked to a service
$service = null;
$customer = null;
$vehicle = null;
$customerName = 'Walk-in Customer';
$customerPhone = 'N/A';
$customerEmail = '';
$vehicleInfo = 'N/A';
$vehicleReg = 'N/A';
$vehicleYear = 'N/A';

if ($invoice->service_id) {
    $service = new Service($invoice->service_id);
    if ($service->id) {
        $customer = new Customer($service->customer_id);
        $vehicle = new Vehicle($service->vehicle_id);
        
        $customerName = $customer->name;
        $customerPhone = $customer->phone;
        $customerEmail = $customer->email;
        
        $vehicleInfo = $vehicle->make . ' ' . $vehicle->model;
        $vehicleReg = $vehicle->registration_number;
        $vehicleYear = $vehicle->year ?? 'N/A';
    }
} else {
    // Use customer details directly from invoice if available
    if (!empty($invoice->customer_name)) {
        $customerName = $invoice->customer_name;
        // If we have a customer_id, we could fetch phone/email too
        if (!empty($invoice->customer_id)) {
            $customerObj = new Customer($invoice->customer_id);
            if ($customerObj->id) {
                 $customerPhone = $customerObj->phone;
                 $customerEmail = $customerObj->email;
            }
        }
    }
}

$invoiceItems = $invoice->getItems($invoice->id);
$subtotal = $invoice->subtotal ?? 0;
$taxTotal = $invoice->tax_amount ?? 0;
$grandTotal = $invoice->total_amount ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo htmlspecialchars($invoice->invoice_number); ?></title>
    
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            padding: 10px;
            background: white;
            color: #333;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }

        /* Company Header */
        .company-header {
            text-align: center;
            border-bottom: 3px solid <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-header h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .company-header p {
            color: #6c757d;
            margin: 5px 0;
            font-size: 14px;
        }

        .invoice-title {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>;
            margin: 20px 0 30px 0;
        }

        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-box {
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
        }

        .detail-box h3 {
            font-size: 14px;
            color: <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 13px;
            border-bottom: 1px dashed #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .detail-value {
            color: #212529;
        }

        /* Vehicle Info */
        .vehicle-box {
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .vehicle-box h3 {
            font-size: 14px;
            color: <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        /* Service Table */
        .service-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .service-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
        }

        .service-table td {
            border: 1px solid #dee2e6;
            padding: 12px;
            font-size: 13px;
        }

        /* Totals */
        .totals-section {
            float: right;
            width: 350px;
            margin-top: 20px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
        }

        .total-line.grand {
            font-size: 18px;
            font-weight: 700;
            color: #28a745;
            border-top: 2px solid #28a745;
            padding-top: 12px;
            margin-top: 5px;
        }

        .total-line.status {
            border-top: none;
            padding-top: 5px;
        }

        .clearfix {
            clear: both;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }

        .invoice-footer p {
            margin: 5px 0;
        }

        /* Print button (hide when printing) */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .print-button:hover {
            opacity: 0.9;
        }

        @media print {
            .print-button {
                display: none;
            }
            
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        🖨️ Print Invoice
    </button>

    <div class="invoice-container">
        <!-- Company Header -->
        <div class="company-header">
            <?php if ($company->image_name && file_exists(__DIR__ . '/../../uploads/company/' . $company->image_name)): ?>
                <img src="../../uploads/company/<?php echo $company->image_name; ?>" alt="Company Logo" style="max-height: 80px; margin-bottom: 10px;">
            <?php else: ?>
               
            <?php endif; ?>
             <h1><?php echo htmlspecialchars($companyName); ?></h1>
            <?php if ($company->address): ?>
                <p><?php echo htmlspecialchars($company->address); ?></p>
            <?php endif; ?>
            
            <p>
                <?php if ($company->mobile_number_1): ?>
                    Phone: <?php echo htmlspecialchars($company->mobile_number_1); ?>
                <?php endif; ?>
                <?php if ($company->email): ?>
                    | Email: <?php echo htmlspecialchars($company->email); ?>
                <?php endif; ?>
            </p>
            <?php if ($company->is_vat && $company->tax_number): ?>
                <p>Tax Reg: <?php echo htmlspecialchars($company->tax_number); ?></p>
            <?php endif; ?>
        </div>

        <?php 
        $billTypeLabel = ($invoice->bill_type === 'credit') ? 'CREDIT INVOICE' : 'CASH INVOICE';
        $billTypeColor = ($invoice->bill_type === 'credit') ? '#dc3545' : '#28a745';
        ?>
        <div class="invoice-title">
            <?php echo $billTypeLabel; ?>
            <div style="font-size: 14px; color: <?php echo $billTypeColor; ?>; font-weight: 500; margin-top: 5px;">
                <?php echo ($invoice->bill_type === 'credit') ? 'Payment Due Later' : 'Paid in Full'; ?>
            </div>
        </div>

        <!-- Invoice and Customer Details -->
        <div class="details-grid">
            <div class="detail-box">
                <h3>Invoice Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Invoice #:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($invoice->invoice_number); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?php echo date('M d, Y', strtotime($invoice->created_at)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value"><?php echo ucfirst($invoice->status); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bill Type:</span>
                    <span class="detail-value" style="color: <?php echo ($invoice->bill_type === 'credit') ? '#dc3545' : '#28a745'; ?>; font-weight: bold;">
                        <?php echo ucfirst($invoice->bill_type ?? 'cash'); ?>
                    </span>
                </div>
            </div>

            <div class="detail-box">
                <h3>Customer Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customerName); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customerPhone); ?></span>
                </div>
                <?php if ($customerEmail): ?>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customerEmail); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vehicle Information (Only if Service ID exists) -->
        <?php if ($invoice->service_id): ?>
        <div class="vehicle-box">
            <h3>Vehicle Information</h3>
            <div class="vehicle-grid">
                <div class="detail-row">
                    <span class="detail-label">Vehicle:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($vehicleInfo); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Registration:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($vehicleReg); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Year:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($vehicleYear); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Invoice Items Table -->
        <h3 style="margin-bottom: 15px; color: <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>;">Invoice Items</h3>
        <table class="service-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Description</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 10%; text-align: center;">Qty</th>
                    <th style="width: 15%; text-align: right;">Unit Price</th>
                    <th style="width: 10%; text-align: right;">Tax</th>
                    <th style="width: 15%; text-align: right;">Total (LKR)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoiceItems)): ?>
                    <?php $rowNum = 1; ?>
                    <?php foreach ($invoiceItems as $item): ?>
                    <tr>
                        <td><?php echo $rowNum++; ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['description']); ?>
                            <?php if ($item['item_type'] === 'inventory' && !empty($item['item_code'])): ?>
                                <br><small style="color: #6c757d;">Code: <?php echo htmlspecialchars($item['item_code']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; 
                                <?php 
                                $bgColor = '#e3f2fd'; $textColor = '#1976d2';
                                switch($item['item_type']) {
                                    case 'inventory': $bgColor = '#e8f5e9'; $textColor = '#388e3c'; break;
                                    case 'labor': $bgColor = '#fff3e0'; $textColor = '#f57c00'; break;
                                    case 'service': $bgColor = '#e3f2fd'; $textColor = '#1976d2'; break;
                                    default: $bgColor = '#f5f5f5'; $textColor = '#616161';
                                }
                                echo "background: $bgColor; color: $textColor;";
                                ?>">
                                <?php echo ucfirst($item['item_type']); ?>
                            </span>
                        </td>
                        <td style="text-align: center;"><?php echo number_format($item['quantity'], 2); ?></td>
                        <td style="text-align: right;"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="text-align: right;"><?php echo number_format($item['tax_amount'] ?? 0, 2); ?></td>
                        <td style="text-align: right; font-weight: 600;"><?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #6c757d;">No items found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-line">
                <span>Subtotal:</span>
                <span>LKR <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($taxTotal > 0): ?>
            <div class="total-line">
                <span>Tax:</span>
                <span>LKR <?php echo number_format($taxTotal, 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if (($invoice->discount_amount ?? 0) > 0): ?>
            <div class="total-line">
                <span>Discount:</span>
                <span>- LKR <?php echo number_format($invoice->discount_amount, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="total-line grand">
                <span>TOTAL:</span>
                <span>LKR <?php echo number_format($grandTotal, 2); ?></span>
            </div>
            <div class="total-line status">
                <span>Payment Status:</span>
                <span style="color: <?php echo $invoice->payment_method ? '#28a745' : '#ffc107'; ?>; font-weight: 600;">
                    <?php echo $invoice->payment_method ? 'Paid' : 'Pending'; ?>
                </span>
            </div>
            <?php if ($invoice->payment_method): ?>
            <div class="total-line status">
                <span>Payment Method:</span>
                <span style="font-weight: 500;">
                    <?php echo ucfirst(str_replace('_', ' ', $invoice->payment_method)); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>

        <!-- Footer -->
        <div class="invoice-footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>This is a computer-generated invoice. For any queries, please contact us.</p>
            <p>© <?php echo date('Y'); ?> Codeplay Studio. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        // Auto-print option 
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
