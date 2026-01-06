<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Get QR code from URL parameter
$qrCode = $_GET['qr'] ?? '';

if (empty($qrCode)) {
    header('Location: ' . BASE_URL);
    exit();
}
// Fetch initial job data
$service = new Service();
$jobData = null;

// Get all service stages
$allStages = (new ServiceStage())->all();

if ($service->getByQRCode($qrCode)) {
    // Increment scan count
    $qrCodeObj = new QRCode($service->qr_id);
    $qrCodeObj->incrementScanCount();

    $customer = new Customer($service->customer_id);
    $vehicle = new Vehicle($service->vehicle_id);
    $package = new ServicePackage($service->package_id);
    $currentStage = new ServiceStage($service->current_stage_id);

    $jobData = [
        'id' => $service->id,
        'job_number' => $service->job_number,
        'customer_name' => $customer->name,
        'vehicle' => $vehicle->make . ' ' . $vehicle->model,
        'registration' => $vehicle->registration_number,
        'package_name' => $package->package_name,
        'status' => $service->status,
        'current_stage_id' => $service->current_stage_id,
        'current_stage_name' => $currentStage->stage_name ?? 'Unknown',
        'progress_percentage' => $service->progress_percentage,
        'total_amount' => $service->total_amount,
        'payment_status' => $service->payment_status,
        'qr_code' => $qrCode
    ];
}

if (!$jobData) {
    header('Location: ' . BASE_URL);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Service - <?php echo htmlspecialchars($jobData['job_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
            color: #1a1a1a;
        }

        .container-custom {
            max-width: 800px;
            margin: 0 auto;
        }

        .header-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .job-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #666;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .status-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .status-title {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .status-waiting { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #dbeafe; color: #1e40af; }
        .status-quality-check { background: #e0e7ff; color: #4338ca; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .progress-section {
            margin-bottom: 2.5rem;
        }

        .progress-bar-custom {
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: #1a1a1a;
            border-radius: 4px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-percentage {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .timeline {
            position: relative;
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 19px;
            top: 40px;
            width: 2px;
            height: calc(100% - 10px);
            background: #e5e7eb;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.125rem;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .timeline-icon.active {
            background: #1a1a1a;
            color: white;
        }

        .timeline-icon.pending {
            background: #f0f0f0;
            color: #9ca3af;
        }

        .timeline-content {
            flex: 1;
            padding-top: 8px;
        }

        .timeline-title {
            font-weight: 600;
            font-size: 1rem;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .timeline-item.inactive .timeline-title {
            color: #9ca3af;
        }

        .timeline-desc {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .info-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px solid #f0f0f0;
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a1a;
            word-break: break-word;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #1a1a1a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.2s ease;
        }

        .download-btn:hover {
            background: #333;
            color: white;
        }

        .footer-text {
            text-align: center;
            color: #9ca3af;
            font-size: 0.875rem;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .refresh-icon {
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            min-width: 320px;
            border-left: 4px solid #1a1a1a;
            display: none;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toast-icon {
            font-size: 1.25rem;
        }

        .toast-message {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1a1a1a;
        }

        @media (max-width: 640px) {
            body {
                padding: 12px;
            }

            .header-card,
            .status-card,
            .info-card {
                padding: 1.5rem;
            }

            .job-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .job-number {
                font-size: 1.5rem;
            }

            .progress-percentage {
                font-size: 1.75rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .notification-toast {
                right: 12px;
                left: 12px;
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <!-- Header Card -->
        <div class="header-card">
            <div class="job-header">
                <h1 class="job-number"><?php echo htmlspecialchars($jobData['job_number']); ?></h1>
                <div class="live-badge">
                    <span class="live-dot"></span>
                    <span id="lastUpdated">Live tracking</span>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="status-card">
            <div class="status-header">
                <span class="status-title">Current Stage</span>
                <span class="status-badge" id="statusBadge">
                    <i class="fas fa-circle"></i>
                    <span id="statusText"><?php echo htmlspecialchars($jobData['current_stage_name']); ?></span>
                </span>
            </div>

            <!-- Progress -->
            <div class="progress-section">
                <div class="progress-bar-custom">
                    <div class="progress-fill" id="progressBar" 
                         style="width: <?php echo $jobData['progress_percentage']; ?>%"></div>
                </div>
                <div class="progress-percentage">
                    <span id="progressText"><?php echo $jobData['progress_percentage']; ?>%</span>
                    <span style="font-size: 1rem; color: #6b7280; font-weight: 500;"> complete</span>
                </div>
            </div>

            <!-- Timeline -->
            <div class="timeline">
                <?php 
                $currentStageOrder = 0;
                foreach ($allStages as $stage):
                    if ($stage['id'] == $jobData['current_stage_id']) {
                        $currentStageOrder = $stage['stage_order'];
                    }
                endforeach;
                
                foreach ($allStages as $index => $stage): 
                    $isActive = $stage['stage_order'] <= $currentStageOrder;
                    $isLast = ($index === count($allStages) - 1);
                ?>
                <div class="timeline-item <?php echo $isActive ? '' : 'inactive'; ?>" data-stage-id="<?php echo $stage['id']; ?>">
                    <div class="timeline-icon <?php echo $isActive ? 'active' : 'pending'; ?>">
                        <i class="<?php echo htmlspecialchars($stage['icon'] ?? 'fas fa-circle'); ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title"><?php echo htmlspecialchars($stage['stage_name']); ?></div>
                        <div class="timeline-desc">
                            <?php 
                            if ($stage['estimated_duration']) {
                                echo 'Est. ' . $stage['estimated_duration'] . ' mins';
                            } else {
                                echo 'Processing';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Info Card -->
        <div class="info-card">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-user"></i>
                        Customer
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($jobData['customer_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-car"></i>
                        Vehicle
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($jobData['vehicle']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-hashtag"></i>
                        Registration
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($jobData['registration']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-box"></i>
                        Package
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($jobData['package_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-dollar-sign"></i>
                        Total Amount
                    </div>
                    <div class="info-value">$<?php echo number_format($jobData['total_amount'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-credit-card"></i>
                        Payment
                    </div>
                    <div class="info-value" id="paymentStatus"><?php echo ucfirst($jobData['payment_status']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-file-pdf"></i>
                        Bill
                    </div>
                    <div class="info-value">
                        <a href="#" onclick="generatePDF(); return false;" class="download-btn">
                            <i class="fas fa-download"></i>
                            Download PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-text">
            <i class="fas fa-sync-alt refresh-icon"></i>
            Auto-updating every 5 seconds
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="notification-toast" id="notificationToast">
        <div class="toast-content">
            <i class="fas fa-bell toast-icon"></i>
            <span class="toast-message" id="toastMessage"></span>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const jobData = <?php echo json_encode($jobData); ?>;
        const allStages = <?php echo json_encode($allStages); ?>;
        const qrCode = '<?php echo htmlspecialchars($qrCode); ?>';
        let lastUpdateTime = Date.now();
        let isPageVisible = true;

        const statusConfig = {
            'waiting': { class: 'status-waiting' },
            'in_progress': { class: 'status-in-progress' },
            'quality_check': { class: 'status-quality-check' },
            'completed': { class: 'status-completed' },
            'delivered': { class: 'status-delivered' },
            'cancelled': { class: 'status-cancelled' }
        };

        document.addEventListener('visibilitychange', function() {
            isPageVisible = !document.hidden;
            if (isPageVisible) updateJobStatus();
        });

        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const pageWidth = doc.internal.pageSize.getWidth();
            const margin = 20;
            let yPos = margin;

            // Company Header
            doc.setFontSize(24);
            doc.setFont('helvetica', 'bold');
            doc.text('Auto Service Center', pageWidth / 2, yPos, { align: 'center' });
            yPos += 10;
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('Professional Vehicle Maintenance & Repair', pageWidth / 2, yPos, { align: 'center' });
            yPos += 15;

            // Horizontal line
            doc.setLineWidth(0.5);
            doc.setDrawColor(0, 0, 0);
            doc.line(margin, yPos, pageWidth - margin, yPos);
            yPos += 10;

            // Invoice Details
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('INVOICE', margin, yPos);
            yPos += 8;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text(`Invoice Number: ${jobData.job_number}`, margin, yPos);
            yPos += 6;
            doc.text(`Date: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}`, margin, yPos);
            yPos += 15;

            // Bill To Section
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('Bill To:', margin, yPos);
            yPos += 8;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text(`Customer: ${jobData.customer_name}`, margin, yPos);
            yPos += 6;
            doc.text(`Vehicle: ${jobData.vehicle}`, margin, yPos);
            yPos += 6;
            doc.text(`Registration: ${jobData.registration}`, margin, yPos);
            yPos += 15;

            // Service Details Section
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('Service Details:', margin, yPos);
            yPos += 8;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text(`Package: ${jobData.package_name}`, margin, yPos);
            yPos += 6;
            doc.text(`Status: ${jobData.status.replace(/_/g, ' ').toUpperCase()}`, margin, yPos);
            yPos += 6;
            doc.text(`Progress: ${jobData.progress_percentage}% Complete`, margin, yPos);
            yPos += 15;

            // Billing Table
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('Billing Summary:', margin, yPos);
            yPos += 8;

            // Simple table headers
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10);
            doc.text('Description', margin, yPos);
            doc.text('Amount', pageWidth - margin - 30, yPos, { align: 'right' });
            yPos += 6;

            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(0.2);
            doc.line(margin, yPos, pageWidth - margin, yPos);
            yPos += 5;

            doc.setFont('helvetica', 'normal');
            doc.text(`${jobData.package_name} Service`, margin, yPos);
            doc.text(`$${jobData.total_amount.toFixed(2)}`, pageWidth - margin - 30, yPos, { align: 'right' });
            yPos += 8;

            doc.line(margin, yPos, pageWidth - margin, yPos);
            yPos += 5;

            // Total
            doc.setFont('helvetica', 'bold');
            doc.text('Total Amount:', margin, yPos);
            doc.setFontSize(12);
            doc.text(`$${jobData.total_amount.toFixed(2)}`, pageWidth - margin - 30, yPos, { align: 'right' });
            yPos += 10;

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(`Payment Status: ${jobData.payment_status.toUpperCase()}`, margin, yPos);
            yPos += 20;

            // Footer
            doc.setLineWidth(0.5);
            doc.line(margin, yPos, pageWidth - margin, yPos);
            yPos += 10;

            doc.setFontSize(8);
            doc.text('Thank you for your business!', pageWidth / 2, yPos, { align: 'center' });
            yPos += 5;
            doc.text('For questions, contact us at support@autoservice.com | (123) 456-7890', pageWidth / 2, yPos, { align: 'center' });
            yPos += 5;
            doc.text(`Generated on: ${new Date().toLocaleString()}`, pageWidth / 2, yPos, { align: 'center' });

            // Save the PDF
            doc.save(`invoice_${jobData.job_number}.pdf`);
        }

        function updateJobStatus() {
            if (!isPageVisible) return;

            $.ajax({
                url: '../../Ajax/php/service.php',
                type: 'GET',
                data: { action: 'get_by_qr', qr_code: qrCode },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        updateUI(response.data);
                        lastUpdateTime = Date.now();
                    }
                },
                error: function() {
                    console.error('Failed to fetch job status');
                }
            });
        }

        function updateUI(data) {
            const oldProgress = parseInt($('#progressBar').css('width')) / $('#progressBar').parent().width() * 100 || 0;
            const newProgress = parseInt(data.progress_percentage);
            const oldStageId = jobData.current_stage_id;
            const newStageId = data.current_stage_id;

            // Update status badge with stage name
            const statusClass = statusConfig[data.status]?.class || 'status-waiting';
            const currentStage = allStages.find(s => s.id == newStageId);
            const stageName = currentStage ? currentStage.stage_name : data.status.replace(/_/g, ' ');
            
            $('#statusBadge').removeClass().addClass('status-badge ' + statusClass);
            $('#statusText').text(stageName);

            // Update progress
            $('#progressBar').css('width', newProgress + '%');
            $('#progressText').text(newProgress);

            // Update timeline based on stage
            updateTimelineByStage(newStageId);

            // Update payment status
            $('#paymentStatus').text(data.payment_status.charAt(0).toUpperCase() + data.payment_status.slice(1));

            // Show notifications on stage change
            if (newStageId !== oldStageId && currentStage) {
                showNotification(`📍 Stage updated: ${currentStage.stage_name}`);
                jobData.current_stage_id = newStageId;
            }
        }

        function updateTimelineByStage(currentStageId) {
            const currentStage = allStages.find(s => s.id == currentStageId);
            if (!currentStage) return;
            
            const currentOrder = currentStage.stage_order;

            $('.timeline-item').each(function() {
                const $item = $(this);
                const stageId = $item.data('stage-id');
                const stage = allStages.find(s => s.id == stageId);
                
                if (stage) {
                    const $icon = $item.find('.timeline-icon');
                    
                    if (stage.stage_order <= currentOrder) {
                        $item.removeClass('inactive');
                        $icon.removeClass('pending').addClass('active');
                    } else {
                        $item.addClass('inactive');
                        $icon.removeClass('active').addClass('pending');
                    }
                }
            });
        }

        function showNotification(message) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('Service Update', { body: message });
            }

            $('#toastMessage').text(message);
            $('#notificationToast').fadeIn(300);
            
            setTimeout(() => {
                $('#notificationToast').fadeOut(300);
            }, 4000);
        }

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        setInterval(updateJobStatus, 5000);
        $(document).ready(function() {
            updateJobStatus();
        });
    </script>
</body>
</html>