<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in and has permission to view this page
requirePagePermission('View');

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$serviceId = (int) $_GET['id'];
$service = new Service($serviceId);

if (!$service->id) {
    header('Location: index.php');
    exit();
}

// Get related data
$customer = new Customer($service->customer_id);
$vehicle = new Vehicle($service->vehicle_id);
$package = new ServicePackage($service->package_id);
$qrCode = new QRCode($service->qr_id);

// Load service items (multi-package support)
$serviceItems = ServiceItem::getByServiceId($serviceId);

// Get invoice and invoice items
$invoice = new Invoice();
$hasInvoice = $invoice->getByServiceId($serviceId);
$invoiceItemsData = [];

// Get existing invoice items if invoice exists
if ($hasInvoice) {
    $invoiceItemsData = $invoice->getItems($invoice->id);
}

// PREPARE DISPLAY ITEMS
// We want to show:
// 1. All current Service Items (Packages) from the service_items table
// 2. Any additional items (Inventory/Labor) from the invoice_items table (if exists)
// This ensures that if multiple packages are added, they are all shown, even if the invoice record is stale.

$displayItems = [];
$subtotal = 0;
$taxTotal = 0;
$grandTotal = 0;

// 1. Add Service Items (Packages/Custom)
if (!empty($serviceItems)) {
    foreach ($serviceItems as $si) {
        $amount = (float)$si['total_price'];
        $displayItems[] = [
            'description' => $si['item_name'],
            'description_sub' => $si['description'], // Helper for subtitle
            'item_type' => $si['item_type'] ?? 'package',
            'quantity' => (float)$si['quantity'],
            'unit_price' => (float)$si['unit_price'],
            'tax_amount' => 0, // Assuming base packages don't have tax separate or included? 
            'total_price' => $amount,
            'is_inventory' => false
        ];
        $subtotal += $amount;
    }
} elseif ($package->id) {
    // Fallback if no service items but package exists
    $amount = (float)$service->total_amount;
    $displayItems[] = [
        'description' => $package->package_name,
        'description_sub' => $package->description,
        'item_type' => 'package',
        'quantity' => 1,
        'unit_price' => $amount,
        'tax_amount' => 0,
        'total_price' => $amount,
        'is_inventory' => false
    ];
    $subtotal += $amount;
}

// 2. Add Non-Service Invoice Items (Inventory, Labor, etc.)
if ($hasInvoice && !empty($invoiceItemsData)) {
    foreach ($invoiceItemsData as $ii) {
        // Skip items that look like the main service package to avoid duplication
        // We assume 'package' and 'service' types in invoice_items are the ones we already covered above
        if (in_array($ii['item_type'], ['package', 'service'])) {
            continue;
        }
        
        $amount = (float)$ii['total_price'];
        $tax = (float)($ii['tax_amount'] ?? 0);
        
        $displayItems[] = [
            'description' => $ii['description'],
            'description_sub' => isset($ii['item_code']) ? 'Code: ' . $ii['item_code'] : '',
            'item_type' => $ii['item_type'],
            'quantity' => (float)$ii['quantity'],
            'unit_price' => (float)$ii['unit_price'],
            'tax_amount' => $tax,
            'total_price' => $amount,
            'is_inventory' => ($ii['item_type'] == 'inventory')
        ];
        
        $subtotal += ($amount - $tax); // Invoice item total usually includes tax? Let's check calculation.
        // Usually total_price = (qty * unit_price) + tax.
        // So subtotal should be just (qty * unit_price).
        // Let's use pure calculation:
        $lineSubtotal = (float)$ii['quantity'] * (float)$ii['unit_price'];
        // Adjust subtotal accumulation:
        // formatting above was simplistic. Let's reset subtotal based on line items properly.
    }
}

// Recalculate exact totals from display items to be consistent
$subtotal = 0;
$taxTotal = 0;

foreach ($displayItems as $item) {
    $lineSub = $item['quantity'] * $item['unit_price'];
    $lineTax = $item['tax_amount'];
    $subtotal += $lineSub;
    $taxTotal += $lineTax;
}

$grandTotal = $subtotal + $taxTotal;

// Apply discount if invoice exists
if ($hasInvoice && ($invoice->discount_amount ?? 0) > 0) {
    $grandTotal -= $invoice->discount_amount;
}

// Get company profile
$companyProfile = new CompanyProfile();
$companyProfile->loadByCompanyId($service->company_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo htmlspecialchars($service->job_number); ?></title>
    
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
            padding-bottom: 50px;
        }

        /* Company Header */
        .company-header {
            text-align: center;
            border-bottom: 3px solid #4361ee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-header img {
            max-height: 80px;
            margin-bottom: 10px;
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
            color: #4361ee;
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
            color: #4361ee;
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
            color: #4361ee;
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

        /* Notes */
        .notes-box {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #4361ee;
            font-size: 13px;
        }

        .notes-box strong {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        /* QR Section */
        .qr-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px dashed #dee2e6;
            text-align: center;
        }

        .qr-section h4 {
            font-size: 14px;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #4361ee;
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
            background: #4361ee;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .print-button:hover {
            background: #3651d4;
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
            <?php if ($companyProfile->image_name): ?>
                <img src="<?php echo BASE_URL . 'uploads/company/' . htmlspecialchars($companyProfile->image_name); ?>" alt="Company Logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($companyProfile->name ?: APP_NAME); ?></h1>
            <p><?php echo htmlspecialchars($companyProfile->address ?: 'Professional Vehicle Service & Maintenance'); ?></p>
            <p>
                <?php if ($companyProfile->mobile_number_1): ?>
                    Phone: <?php echo htmlspecialchars($companyProfile->mobile_number_1); ?> 
                    <?php echo $companyProfile->mobile_number_2 ? ' / ' . htmlspecialchars($companyProfile->mobile_number_2) : ''; ?>
                <?php endif; ?>
                <?php if ($companyProfile->email): ?>
                    | Email: <?php echo htmlspecialchars($companyProfile->email); ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="invoice-title">SERVICE INVOICE</div>

        <!-- Invoice and Customer Details -->
        <div class="details-grid">
            <div class="detail-box">
                <h3>Invoice Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Invoice #:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($service->job_number); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?php echo date('M d, Y', strtotime($service->created_at)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value"><?php echo ucfirst($service->status); ?></span>
                </div>
            </div>

            <div class="detail-box">
                <h3>Customer Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customer->name); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customer->phone); ?></span>
                </div>
                <?php if ($customer->email): ?>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customer->email); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vehicle Information -->
        <div class="vehicle-box">
            <h3>Vehicle Information</h3>
            <div class="vehicle-grid">
                <div class="detail-row">
                    <span class="detail-label">Vehicle:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($vehicle->make . ' ' . $vehicle->model); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Registration:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($vehicle->registration_number); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Year:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($vehicle->year ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Service/Invoice Details Table -->
        <h3 style="margin-bottom: 15px; color: #2c3e50;">Invoice Details</h3>
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
                <?php if (!empty($displayItems)): ?>
                    <?php $rowNum = 1; ?>
                    <?php foreach ($displayItems as $item): ?>
                    <tr>
                        <td><?php echo $rowNum++; ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['description']); ?>
                            <?php if (!empty($item['description_sub'])): ?>
                                <br><small style="color: #6c757d;"><?php echo htmlspecialchars($item['description_sub']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; 
                                <?php 
                                $bgColor = '#f5f5f5'; $textColor = '#616161';
                                switch($item['item_type']) {
                                    case 'inventory': $bgColor = '#e8f5e9'; $textColor = '#388e3c'; break;
                                    case 'labor': $bgColor = '#fff3e0'; $textColor = '#f57c00'; break;
                                    case 'service': 
                                    case 'package': $bgColor = '#e3f2fd'; $textColor = '#1976d2'; break;
                                }
                                echo "background: $bgColor; color: $textColor;";
                                ?>">
                                <?php echo ucfirst($item['item_type']); ?>
                            </span>
                        </td>
                        <td style="text-align: center;"><?php echo number_format($item['quantity'], 2); ?></td>
                        <td style="text-align: right;"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="text-align: right;"><?php echo number_format($item['tax_amount'], 2); ?></td>
                        <td style="text-align: right; font-weight: 600;"><?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #6c757d;">No items to display</td>
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
            <?php if ($hasInvoice && ($invoice->discount_amount ?? 0) > 0): ?>
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
                <span style="color: <?php echo $service->payment_status == 'paid' ? '#28a745' : '#ffc107'; ?>; font-weight: 600;">
                    <?php echo ucfirst($service->payment_status); ?>
                </span>
            </div>
            <?php if ($hasInvoice && $invoice->payment_method): ?>
            <div class="total-line status">
                <span>Payment Method:</span>
                <span style="font-weight: 500;">
                    <?php echo ucfirst(str_replace('_', ' ', $invoice->payment_method)); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>

        <!-- Notes -->
        <?php if ($service->notes): ?>
        <div class="notes-box">
            <strong>Notes:</strong>
            <?php echo nl2br(htmlspecialchars($service->notes)); ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="invoice-footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>This is a computer-generated invoice. For any queries, please contact us.</p>
            <p>© <?php echo date('Y'); ?> Codeplay Studio. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Auto-print option (uncomment if you want auto-print)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
