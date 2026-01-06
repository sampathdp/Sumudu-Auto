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
$level = $_GET['level'] ?? 'all';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'item_name';

// Fetch stock data
$report = new Report($companyId);
$reportData = $report->getStockReport([
    'level' => $level,
    'category' => $category,
    'search' => $search,
    'sort' => $sort
]);

$items = $reportData['data'];
$totalValue = $reportData['total_value'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report - <?php echo date('Y-m-d'); ?></title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; padding: 10px; background: white; color: #333; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: white; }
        
        /* Company Header */
        .company-header { text-align: center; border-bottom: 3px solid <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>; padding-bottom: 20px; margin-bottom: 30px; }
        .company-header h1 { font-size: 32px; color: #2c3e50; margin-bottom: 5px; }
        .company-header p { color: #6c757d; margin: 5px 0; font-size: 14px; }
        
        .report-title { text-align: center; font-size: 24px; font-weight: 700; color: <?php echo $company->theme == 'dark' ? '#2c3e50' : '#4361ee'; ?>; margin: 20px 0 10px 0; }
        .report-meta { text-align: center; color: #6c757d; font-size: 12px; margin-bottom: 30px; }

        /* Attributes Grid */
        .details-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .detail-box { border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; text-align: center; }
        .detail-label { display: block; font-size: 11px; text-transform: uppercase; color: #6c757d; margin-bottom: 5px; }
        .detail-value { font-weight: 700; color: #2c3e50; font-size: 14px; }

        /* Table */
        .stock-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .stock-table th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .stock-table td { border: 1px solid #dee2e6; padding: 8px; font-size: 11px; vertical-align: middle; }
        
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        
        /* Status Colors */
        .status-ok { color: #10b981; font-weight: 600; }
        .status-low { color: #f59e0b; font-weight: 600; }
        .status-out { color: #ef4444; font-weight: 600; }

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

        <div class="report-title">LIVE STOCK REPORT</div>
        <div class="report-meta">Generated on: <?php echo date('F d, Y h:i A'); ?></div>

        <div class="details-grid">
            <div class="detail-box">
                <span class="detail-label">Total Items</span>
                <span class="detail-value"><?php echo number_format(count($items)); ?></span>
            </div>
            <div class="detail-box">
                <span class="detail-label">Total Stock Value</span>
                <span class="detail-value">LKR <?php echo number_format($totalValue, 2); ?></span>
            </div>
            <div class="detail-box">
                <span class="detail-label">Filter Applied</span>
                <span class="detail-value"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $level))); ?></span>
            </div>
        </div>

        <table class="stock-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Code</th>
                    <th style="width: 25%;">Item Name</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 10%;" class="text-center">Stock</th>
                    <th style="width: 10%;" class="text-center">Reorder</th>
                    <th style="width: 15%;" class="text-end">Unit Cost</th>
                    <th style="width: 15%;" class="text-end">Value (LKR)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                        <?php 
                            $statusClass = 'status-ok';
                            if ($item['current_stock'] == 0) $statusClass = 'status-out';
                            elseif ($item['current_stock'] <= $item['reorder_level']) $statusClass = 'status-low';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?></td>
                            <td class="text-center <?php echo $statusClass; ?>">
                                <?php echo number_format($item['current_stock']); ?>
                            </td>
                            <td class="text-center" style="color: #6c757d;">
                                <?php echo number_format($item['reorder_level']); ?>
                            </td>
                            <td class="text-end"><?php echo number_format($item['unit_cost'], 2); ?></td>
                            <td class="text-end font-weight-bold">
                                <?php echo number_format($item['fifo_value'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 20px;">No items found matching the criteria.</td>
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
