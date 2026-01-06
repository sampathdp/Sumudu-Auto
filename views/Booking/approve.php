<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Redirect if not logged in
if (!isset($_SESSION['id'])) {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit();
}

$companyId = $_SESSION['company_id'] ?? 1;
$branchId = $_SESSION['branch_id'] ?? null;

$booking = new Booking();
$pendingBookings = $booking->getPendingBookings($companyId, $branchId);
$todayBookings   = $booking->getTodayBookings($companyId, $branchId);
$stats           = $booking->getStatistics($companyId, $branchId);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Booking Approvals</title>
    <?php include '../../includes/main-css.php'; ?>

    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --success-color: #28a745;
            --warning-color: #f59e0b;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --light-bg: #f8f9fa;
            --card-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7fb;
            color: #3c4858;
        }

        .content {
            margin-top: 70px;
            padding: 2rem;
        }

        .page-header {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            padding: 1.75rem 2rem;
            border-left: 5px solid var(--primary-color);
        }

        .page-title {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.8rem;
            margin: 0;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 1rem 2rem rgba(67, 97, 238, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 1rem;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0.5rem 0;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .section-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-top: 0.35rem;
        }

        .booking-item {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f5f9;
            transition: var(--transition);
        }

        .booking-item:hover {
            background: #f8fafc;
        }

        .booking-item:last-child {
            border-bottom: none;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .booking-info h6 {
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            font-size: 1.1rem;
        }

        .booking-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            color: #64748b;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }

        .status-badge {
            padding: 0.4rem 0.9rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: rgba(245,158,11,0.15); color: var(--warning-color); }
        .status-approved { background: rgba(40,167,69,0.15); color: var(--success-color); }
        .status-rejected { background: rgba(220,53,69,0.15); color: var(--danger-color); }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 10px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-weight: 600;
            color: #2c3e50;
            margin-top: 0.25rem;
        }

        .booking-notes {
            background: #fff8e6;
            padding: 1rem;
            border-radius: 8px;
            font-style: italic;
            color: #856404;
            margin: 1rem 0;
            border-left: 4px solid #ffc107;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-action {
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .btn-approve {
            background: var(--success-color);
            color: white;
        }

        .btn-reject {
            background: var(--danger-color);
            color: white;
        }

        .btn-view {
            background: var(--info-color);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.4;
        }

        @media (max-width: 768px) {
            .content { padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .booking-header { flex-direction: column; gap: 1rem; }
            .action-buttons { justify-content: center; }
        }
    </style>
</head>

<body class="animate-fade-in">
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>

            <div class="content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h1 class="page-title">
                                    <i class="fas fa-calendar-check me-3"></i>Booking Approvals
                                </h1>
                                <p class="page-subtitle">Review and manage customer service booking requests</p>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small">Last updated: <?php echo date('M j, Y g:i A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(67,97,238,0.1); color: var(--primary-color);">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['total_bookings'] ?? 0); ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: var(--warning-color);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                            <div class="stat-label">Pending Approval</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(40,167,69,0.1); color: var(--success-color);">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['approved'] ?? 0); ?></div>
                            <div class="stat-label">Approved</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(220,53,69,0.1); color: var(--danger-color);">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['rejected'] ?? 0); ?></div>
                            <div class="stat-label">Rejected</div>
                        </div>
                    </div>

                    <!-- Pending Bookings -->
                    <div class="section-card">
                        <div class="section-header">
                            <div>
                                <h3 class="section-title">
                                    <i class="fas fa-hourglass-half"></i>
                                    Pending Approvals
                                </h3>
                                <p class="section-subtitle">Customer requests awaiting your review</p>
                            </div>
                        </div>

                        <div class="p-3">
                            <?php if (!empty($pendingBookings)): ?>
                                <?php foreach ($pendingBookings as $b): ?>
                                    <div class="booking-item">
                                        <div class="booking-header">
                                            <div class="booking-info">
                                                <h6>#<?php echo htmlspecialchars($b['booking_number']); ?></h6>
                                                <div class="booking-meta">
                                                    <span><i class="fas fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($b['booking_date'])); ?></span>
                                                    <span><i class="fas fa-clock me-1"></i><?php echo $b['booking_time']; ?></span>
                                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($b['customer_name']); ?></span>
                                                </div>
                                            </div>
                                            <span class="status-badge status-pending">Pending</span>
                                        </div>

                                        <div class="booking-details">
                                            <div>
                                                <div class="detail-label">Service Package</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($b['package_name']); ?></div>
                                            </div>
                                            <div>
                                                <div class="detail-label">Duration</div>
                                                <div class="detail-value"><?php echo $b['estimated_duration']; ?> mins</div>
                                            </div>
                                            <div>
                                                <div class="detail-label">Mobile</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($b['customer_mobile']); ?></div>
                                            </div>
                                            <div>
                                                <div class="detail-label">Amount</div>
                                                <div class="detail-value">LKR <?php echo number_format($b['total_amount'], 2); ?></div>
                                            </div>
                                        </div>

                                        <?php if (!empty($b['notes'])): ?>
                                            <div class="booking-notes">
                                                <strong>Customer Notes:</strong> <?php echo nl2br(htmlspecialchars($b['notes'])); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="action-buttons">
                                            <button class="btn-action btn-approve" onclick="approveBooking(<?php echo $b['id']; ?>)">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn-action btn-reject" onclick="showRejectModal(<?php echo $b['id']; ?>)">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                            <button class="btn-action btn-view" onclick="viewDetails(<?php echo $b['id']; ?>)">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-double"></i>
                                    <h4>No Pending Requests</h4>
                                    <p>All bookings have been processed</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Today's Bookings -->
                    <div class="section-card">
                        <div class="section-header">
                            <div>
                                <h3 class="section-title">
                                    <i class="fas fa-calendar-day"></i>
                                    Today's Schedule
                                </h3>
                                <p class="section-subtitle">Bookings confirmed for today</p>
                            </div>
                        </div>

                        <div class="p-3">
                            <?php if (!empty($todayBookings)): ?>
                                <?php foreach ($todayBookings as $b): ?>
                                    <div class="booking-item">
                                        <div class="booking-header">
                                            <div class="booking-info">
                                                <h6>#<?php echo htmlspecialchars($b['booking_number']); ?></h6>
                                                <div class="booking-meta">
                                                    <span><i class="fas fa-clock me-1"></i><?php echo $b['booking_time']; ?></span>
                                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($b['customer_name']); ?></span>
                                                    <span><i class="fas fa-phone me-1"></i><?php echo $b['customer_mobile']; ?></span>
                                                </div>
                                            </div>
                                            <span class="status-badge status-<?php echo $b['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $b['status'])); ?>
                                            </span>
                                        </div>

                                        <div class="booking-details">
                                            <div>
                                                <div class="detail-label">Package</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($b['package_name']); ?></div>
                                            </div>
                                            <div>
                                                <div class="detail-label">Duration</div>
                                                <div class="detail-value"><?php echo $b['estimated_duration']; ?> mins</div>
                                            </div>
                                            <div>
                                                <div class="detail-label">Amount</div>
                                                <div class="detail-value">LKR <?php echo number_format($b['total_amount'], 2); ?></div>
                                            </div>
                                            <?php if ($b['approver_name']): ?>
                                            <div>
                                                <div class="detail-label">Approved By</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($b['approver_name']); ?></div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h4>No Bookings Today</h4>
                                    <p>No confirmed bookings scheduled for today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form id="rejectForm">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">Reject Booking Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="rejectBookingId">
                        <div class="mb-3">
                            <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="rejectionReason" rows="4" required 
                                      placeholder="Please explain why this booking cannot be accepted..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Reject Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>

    <script>
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));

        function showRejectModal(bookingId) {
            document.getElementById('rejectBookingId').value = bookingId;
            rejectModal.show();
        }

        function approveBooking(id) {
            Swal.fire({
                title: 'Approve Booking?',
                text: "The customer will be notified immediately.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performApproval(id);
                }
            });
        }

        function performApproval(id) {
            Swal.fire({
                title: 'Processing...',
                text: 'Creating service record and notifying customer',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../../Ajax/php/booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'approve',
                    booking_id: id
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Approved!',
                        text: 'Booking approved successfully.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Approval Failed',
                        text: data.message || 'Unknown error'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Please check your connection and try again.'
                });
            });
        }

        document.getElementById('rejectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('rejectBookingId').value;
            const reason = document.getElementById('rejectionReason').value.trim();

            if (!reason) {
                showToast('warning', 'Please provide a rejection reason');
                return;
            }

            Swal.fire({
                title: 'Rejecting...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../../Ajax/php/booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'reject',
                    booking_id: id,
                    reason: reason
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    rejectModal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected',
                        text: 'Booking has been rejected.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Rejection Failed',
                        text: data.message || 'Unknown error'
                    });
                }
            });
        });

        function viewDetails(id) {
            Swal.fire({
                title: 'Booking Details',
                text: 'Detailed view coming soon for Booking ID: ' + id,
                icon: 'info'
            });
        }

        function showToast(type, msg) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            Toast.fire({
                icon: type,
                title: msg
            });
        }
    </script>
</body>
</html>