<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Check if user is logged in and has permission to view this page
requirePagePermission('View');

// Load service packages
$package = new ServicePackage();
$packages = $package->all();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Book Service Appointment</title>
    <?php include '../../includes/main-css.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Font Awesome is already included in main-css.php -->

    <style>
        :root {
            --primary-color: #4f46e5; /* Indigo 600 */
            --primary-hover: #4338ca; /* Indigo 700 */
            --primary-light: #e0e7ff; /* Indigo 100 */
            --secondary-bg: #f8fafc; /* Slate 50 */
            --text-main: #1e293b; /* Slate 800 */
            --text-muted: #64748b; /* Slate 500 */
            --border-color: #e2e8f0; /* Slate 200 */
            --success-color: #10b981;
            --card-bg: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-lg: 1rem;
            --radius-md: 0.75rem;
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--secondary-bg);
            color: var(--text-main);
            font-family: 'Public Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .content {
            padding: 2rem 1rem;
            max-width: 1400px;
            margin: 0 auto;
            margin-top: 60px; /* Adjust based on navbar height */
        }

        /* --- Page Header --- */
        .page-header-modern {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border: 1px solid var(--border-color);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            line-height: 1.2;
        }

        .page-subtitle {
            color: var(--text-muted);
            margin-top: 0.5rem;
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .btn-back {
            color: var(--text-muted);
            background: transparent;
            border: 1px solid var(--border-color);
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-back:hover {
            background: var(--secondary-bg);
            color: var(--text-main);
            border-color: #cbd5e1;
        }

        /* --- Layout Grid --- */
        .booking-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 992px) {
            .booking-layout {
                grid-template-columns: 1fr;
            }
        }

        /* --- Cards & Sections --- */
        .modern-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .modern-card:hover {
            box-shadow: var(--shadow-md);
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: #fafafa;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.2rem;
            background: var(--primary-light);
            padding: 0.5rem;
            border-radius: 8px;
        }

        .card-body-content {
            padding: 1.5rem;
        }

        /* --- Form Elements --- */
        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 0.6rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #fff;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .input-group-text {
            border-radius: 0.6rem 0 0 0.6rem;
            background: #f1f5f9;
            border-color: var(--border-color);
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .form-floating-custom {
            position: relative;
        }
        
        .form-floating-custom input:focus ~ label,
        .form-floating-custom input:not(:placeholder-shown) ~ label {
             /* Add floating label styles if needed, currently using standard logic */
        }

        /* --- Package Cards --- */
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .package-card {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            cursor: pointer;
            transition: var(--transition);
            background: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .package-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .package-card.selected {
            border-color: var(--primary-color);
            background: #fdfcff;
            box-shadow: 0 0 0 1px var(--primary-color);
        }
        
        .package-card.selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 5 Solid';
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary-color);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .package-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
            display: block;
        }

        .package-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            line-height: 1.5;
            flex-grow: 1;
        }

        .package-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            border-top: 1px dashed var(--border-color);
            padding-top: 0.75rem;
        }

        .package-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .package-duration {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.35rem;
            background: #f1f5f9;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
        }

        /* --- Date & Time --- */
        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.75rem;
        }

        .time-slot {
            padding: 0.75rem 0.5rem;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-main);
        }

        .time-slot:hover:not(.disabled) {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: var(--primary-light);
        }

        .time-slot.selected {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
        }

        .time-slot.disabled {
            background: #f1f5f9;
            color: #cbd5e1;
            cursor: not-allowed;
            border-color: transparent;
        }

        /* --- Sidebar & Summary --- */
        .booking-sidebar {
            position: sticky;
            top: 2rem;
        }

        .summary-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: none;
            overflow: hidden;
        }
        
        .summary-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3730a3 100%);
            padding: 1.5rem;
            color: white;
            text-align: center;
        }
        
        .summary-header h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
        }
        
        .summary-body {
            padding: 1.5rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .summary-label {
            color: var(--text-muted);
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--text-main);
            text-align: right;
            max-width: 60%;
        }
        
        .total-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .total-label {
            font-weight: 600;
            color: var(--text-main);
        }
        
        .total-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .btn-submit {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-submit:hover:not(:disabled) {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #94a3b8;
        }

        /* --- Loading & Success --- */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display:none; /* Toggled by JS */
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
            border-radius: var(--radius-lg);
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e0e7ff;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin { 100% { transform: rotate(360deg); } }
        
        .success-panel {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        
        .success-icon-box {
            width: 80px;
            height: 80px;
            background: #d1fae5;
            color: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
        }

    </style>
</head>

<body>
<div class="wrapper">
    
    <div class="main-panel">
        <div class="content">

            <!-- Booking Form Wrapper -->
            <!-- Form ID preserved for JS -->
            <form id="bookingForm"> 
                <div class="booking-layout">
                    
                    <!-- LEFT COLUMN: Inputs -->
                    <div class="left-column">
                        
                        <!-- Page Header -->
                        <div class="page-header-modern">
                            <div>
                                <h1 class="page-title">Service Booking</h1>
                                <p class="page-subtitle">Schedule your vehicle maintenance in a few simple steps</p>
                            </div> 
                        </div>

                        <!-- 1. Customer Information -->
                        <section class="modern-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-user-circle"></i> Customer Details
                                </h3>
                            </div>
                            <div class="card-body-content">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label" for="customerName">Full Name</label>
                                        <input type="text" class="form-control" id="customerName" required placeholder="Ex: John Doe">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="customerMobile">Mobile Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+94</span>
                                            <input type="tel" class="form-control" id="customerMobile" maxlength="9" placeholder="771234567" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="vehicleReg">Vehicle Registration Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="vehicleReg" placeholder="Ex: CAB-1234 or KV-4567" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="vehicleMake">Vehicle Make <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="vehicleMake" placeholder="Ex: Toyota, Honda" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="vehicleModel">Vehicle Model <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="vehicleModel" placeholder="Ex: Corolla, Civic" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="customerEmail">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                                        <input type="email" class="form-control" id="customerEmail" placeholder="john@example.com">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 2. Package Selection -->
                        <section class="modern-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-box-open"></i> Select Package
                                </h3>
                            </div>
                            <div class="card-body-content">
                                <!-- ID preserved for JS parent container -->
                                <div id="packagesList" class="packages-grid">
                                    <?php if (!empty($packages)): ?>
                                        <?php foreach ($packages as $pkg): ?>
                                            <!-- Data attributes preserved for JS logic -->
                                            <div class="package-card"
                                                 data-package-id="<?= $pkg['id'] ?>"
                                                 data-price="<?= $pkg['base_price'] ?>"
                                                 data-duration="<?= $pkg['estimated_duration'] ?>">
                                                
                                                <div>
                                                    <span class="package-name"><?= htmlspecialchars($pkg['package_name']) ?></span>
                                                    <div class="package-desc"><?= htmlspecialchars($pkg['description']) ?></div>
                                                </div>
                                                
                                                <div class="package-footer">
                                                    <span class="package-duration">
                                                        <i class="fas fa-clock"></i> <?= $pkg['estimated_duration'] ?> mins
                                                    </span>
                                                    <span class="package-price">
                                                        LKR <?= number_format($pkg['base_price'], 2) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info w-100">
                                            No packages available.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>

                        <!-- 3. Date & Time -->
                        <section class="modern-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-calendar-alt"></i> Date & Time
                                </h3>
                            </div>
                            <div class="card-body-content">
                                <div class="row g-4">
                                    <div class="col-md-5">
                                        <label class="form-label">Preferred Date</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="fas fa-calendar"></i></span>
                                            <input type="text" class="form-control" id="bookingDate" placeholder="Select Date" readonly required>
                                        </div>
                                        <div class="mt-2 text-muted small">
                                            <i class="fas fa-info-circle me-1"></i> We are closed on Sundays
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label">Available Slots</label>
                                        <!-- ID preserved -->
                                        <div class="time-grid" id="timeSlots">
                                            <div class="p-3 text-center text-muted w-100 border rounded bg-light" style="grid-column: 1 / -1;">
                                                Select a date to view slots
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 4. Notes -->
                        <section class="modern-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-comment-dots"></i> Additional Notes
                                </h3>
                            </div>
                            <div class="card-body-content">
                                <textarea class="form-control" id="bookingNotes" rows="3" placeholder="Describe any specific issues or requests..."></textarea>
                            </div>
                        </section>

                    </div> 
                    <!-- End Left Column -->

                    <!-- RIGHT COLUMN: Summary (Sticky) -->
                    <div class="booking-sidebar">
                        
                        <!-- Summary Card: Hidden initially via JS display:none logic or CSS class -->
                        <!-- ID preserved: summaryCard -->
                        <div class="summary-card" id="summaryCard" style="display:none;">
                            <div class="summary-header">
                                <h4>Booking Summary</h4>
                            </div>
                            <div class="summary-body">
                                <div class="summary-item">
                                    <span class="summary-label">Package</span>
                                    <!-- ID preserved: sumPackage -->
                                    <span class="summary-value" id="sumPackage">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Date</span>
                                    <!-- ID preserved: sumDateTime -->
                                    <span class="summary-value" id="sumDateTime">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Duration</span>
                                    <!-- ID preserved: sumDuration -->
                                    <span class="summary-value" id="sumDuration">-</span>
                                </div>
                            </div>
                            <div class="total-section">
                                <span class="total-label">Total Estimate</span>
                                <!-- ID preserved: sumTotal -->
                                <span class="total-price" id="sumTotal">LKR 0.00</span>
                            </div>
                            
                            <div class="p-3 bg-white">
                                <!-- ID preserved: submitBtn -->
                                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                                    Confirm Booking <i class="fas fa-arrow-right"></i>
                                </button>
                                <div class="text-center mt-2">
                                    <small class="text-muted" style="font-size: 0.75rem;">No payment required now</small>
                                </div>
                            </div>

                            <!-- Loading Overlay inside card or fullscreen? 
                                 Original was inplace. Let's make it overlay the summary or form. 
                                 Actually original code hid form and showed loading. 
                                 Let's keep separate loading div for simplicity with existing JS structure 
                                 OR adapt structure. 
                                 
                                 The JS does: 
                                 elements.bookingForm.style.display = 'none';
                                 elements.loadingState.style.display = 'block';
                                 
                                 So I must have a loadingState div OUTSIDE the form or handle it.
                            -->
                        </div>

                        <!-- Fallback/Intro State when no selection -->
                        <div class="modern-card p-4 text-center text-muted" id="emptyStateHelp" style="display:none;">
                            <i class="fas fa-calendar-check fa-3x mb-3 text-light"></i>
                            <p>Select a package and date to see your summary.</p>
                        </div>
                    </div>
                    <!-- End Right Column -->

                </div>
            </form>

            <!-- Loading State Container (Hidden by default) -->
            <!-- ID preserved: loadingState -->
            <div id="loadingState" class="text-center py-5" style="display:none;">
                <div class="spinner mx-auto"></div>
                <h3 class="fw-bold text-dark">Processing Booking...</h3>
                <p class="text-muted">Please wait while we confirm your appointment.</p>
            </div>

            <!-- Success Message Container (Hidden by default) -->
            <!-- ID preserved: successMessage -->
            <div id="successMessage" class="modern-card success-panel mx-auto" style="display:none; max-width: 600px;">
                <div class="success-icon-box">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="fw-bold text-dark mb-3">Booking Confirmed!</h2>
                
                <!-- ID preserved: successDetails -->
                <div id="successDetails" class="bg-light p-4 rounded mb-4 text-start">
                    <!-- Content injected by JS -->
                </div>

                <div class="d-flex gap-2 justify-content-center">
                    <a href="<?php echo BASE_URL; ?>views/dashboard/" class="btn btn-outline-primary px-4">
                        Return to Dashboard
                    </a>
                    <button class="btn btn-primary px-4" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Global state
        const state = {
            selectedPackage: null,
            selectedDate: null,
            selectedTime: null,
            availableTimeSlots: []
        };

        // DOM Elements
        const elements = {
            customerName: document.getElementById('customerName'),
            customerMobile: document.getElementById('customerMobile'),
            customerEmail: document.getElementById('customerEmail'),
            vehicleReg: document.getElementById('vehicleReg'),
            vehicleMake: document.getElementById('vehicleMake'),
            vehicleModel: document.getElementById('vehicleModel'),
            bookingDate: document.getElementById('bookingDate'),
            bookingNotes: document.getElementById('bookingNotes'),
            timeSlots: document.getElementById('timeSlots'),
            summaryCard: document.getElementById('summaryCard'),
            submitBtn: document.getElementById('submitBtn'),
            bookingForm: document.getElementById('bookingForm'),
            loadingState: document.getElementById('loadingState'),
            successMessage: document.getElementById('successMessage')
        };

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeDatePicker();
            prefillMobileFromURL();
            setupEventListeners();
        });

        // Pre-fill mobile number from URL parameter
        function prefillMobileFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const mobileParam = urlParams.get('mobile');

            if (mobileParam && mobileParam.startsWith('94') && mobileParam.length === 11) {
                elements.customerMobile.value = mobileParam.substring(2);
                validateForm();
            }
        }

        // Initialize Flatpickr date picker
        function initializeDatePicker() {
            flatpickr(elements.bookingDate, {
                minDate: "today",
                maxDate: new Date().fp_incr(30),
                disable: [date => date.getDay() === 0], // Disable Sundays
                locale: {
                    firstDayOfWeek: 1
                },
                onChange: function(selectedDates, dateStr) {
                    state.selectedDate = dateStr;
                    loadTimeSlots(dateStr);
                    updateSummary();
                    validateForm();
                }
            });
        }

        // Setup all event listeners
        function setupEventListeners() {
            // Package selection
            document.querySelectorAll('.package-card').forEach(card => {
                card.addEventListener('click', function() {
                    selectPackage(this);
                });
            });

            // Form validation on input
            [elements.customerName, elements.customerMobile, elements.vehicleReg].forEach(input => {
                input.addEventListener('input', validateForm);
            });

            // Mobile number validation - only digits
            elements.customerMobile.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 9);
            });

            // Form submission
            elements.bookingForm.addEventListener('submit', handleFormSubmit);
        }

        // Package selection handler
        function selectPackage(card) {
            document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            state.selectedPackage = {
                id: card.dataset.packageId,
                name: card.querySelector('.package-name').textContent,
                price: parseFloat(card.dataset.price),
                duration: parseInt(card.dataset.duration)
            };

            updateSummary();
            validateForm();
        }

        // Load available time slots
        function loadTimeSlots(date) {
            elements.timeSlots.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading slots...</div>';

            // Simulate API call - replace with actual API
            setTimeout(() => {
                const allSlots = [
                    '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM',
                    '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM',
                    '04:00 PM', '05:00 PM'
                ];

                // Simulate some unavailable slots
                const unavailableSlots = ['12:00 PM', '04:00 PM'];

                elements.timeSlots.innerHTML = '';
                allSlots.forEach(slot => {
                    const isUnavailable = unavailableSlots.includes(slot);
                    const slotElement = document.createElement('div');
                    slotElement.className = 'time-slot' + (isUnavailable ? ' disabled' : '');
                    slotElement.textContent = slot;

                    if (!isUnavailable) {
                        slotElement.addEventListener('click', () => selectTimeSlot(slotElement, slot));
                    }

                    elements.timeSlots.appendChild(slotElement);
                });
            }, 500);
        }

        // Time slot selection
        function selectTimeSlot(element, time) {
            document.querySelectorAll('.time-slot:not(.disabled)').forEach(slot => {
                slot.classList.remove('selected');
            });

            element.classList.add('selected');
            state.selectedTime = time;

            updateSummary();
            validateForm();
        }

        // Update booking summary
        function updateSummary() {
            const {
                selectedPackage,
                selectedDate,
                selectedTime
            } = state;

            if (selectedPackage && selectedDate && selectedTime) {
                elements.summaryCard.style.display = 'block';

                document.getElementById('sumPackage').textContent = selectedPackage.name;
                document.getElementById('sumDateTime').textContent = `${selectedDate} at ${selectedTime}`;
                document.getElementById('sumDuration').textContent = `${selectedPackage.duration} minutes`;
                document.getElementById('sumTotal').textContent = `LKR ${selectedPackage.price.toFixed(2)}`;
            } else {
                elements.summaryCard.style.display = 'none';
            }
        }

        // Form validation
        function validateForm() {
            const isValid = elements.customerName.value.trim() &&
                elements.customerMobile.value.trim().length === 9 &&
                elements.vehicleReg.value.trim() &&
                elements.vehicleMake.value.trim() &&
                elements.vehicleModel.value.trim() &&
                state.selectedPackage &&
                state.selectedDate &&
                state.selectedTime;

            elements.submitBtn.disabled = !isValid;
        }

        // Form submission handler
        function handleFormSubmit(e) {
            e.preventDefault();

            if (!state.selectedDate || !state.selectedTime || !state.selectedPackage) {
                alert('Please select a package, date, and time.');
                return;
            }

            const formData = {
                customerName: elements.customerName.value.trim(),
                customerMobile: '94' + elements.customerMobile.value.trim(),
                customerEmail: elements.customerEmail.value.trim(),
                vehicleReg: elements.vehicleReg.value.trim().toUpperCase(),
                vehicleMake: elements.vehicleMake.value.trim(),
                vehicleModel: elements.vehicleModel.value.trim(),
                packageId: state.selectedPackage.id,
                packageName: state.selectedPackage.name,
                bookingDate: state.selectedDate,
                bookingTime: state.selectedTime,
                notes: elements.bookingNotes.value.trim(),
                totalAmount: state.selectedPackage.price,
                duration: state.selectedPackage.duration
            };

            // Show loading state
            elements.bookingForm.style.display = 'none';
            elements.loadingState.style.display = 'block';

            // Send AJAX request
            fetch('../../Ajax/php/booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'create',
                    customer_name: formData.customerName,
                    customer_mobile: formData.customerMobile,
                    customer_email: formData.customerEmail,
                    registration_number: formData.vehicleReg,
                    vehicle_make: formData.vehicleMake,
                    vehicle_model: formData.vehicleModel,
                    service_package_id: formData.packageId,
                    booking_date: formData.bookingDate,
                    booking_time: formData.bookingTime,
                    estimated_duration: formData.duration,
                    notes: formData.notes,
                    total_amount: formData.totalAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Show success message
                    elements.loadingState.style.display = 'none';
                    elements.successMessage.style.display = 'block';

                    document.getElementById('successDetails').innerHTML = `
                        <strong>Booking ID: #${data.booking_number}</strong><br>
                        <span class="text-muted">Date: ${formData.bookingDate} at ${formData.bookingTime}</span><br>
                        <span class="text-muted">Package: ${formData.packageName}</span><br>
                        <span class="text-muted">Vehicle: ${formData.vehicleReg} (${formData.vehicleMake} ${formData.vehicleModel})</span><br><br>
                        <small>We'll send a confirmation to your mobile number shortly.</small>
                    `;
                    
                    // Optional: Send WhatsApp message
                    sendWhatsAppConfirmation(formData, data.booking_number);
                } else {
                    alert('Booking Failed: ' + (data.message || 'Unknown error'));
                    elements.loadingState.style.display = 'none';
                    elements.bookingForm.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
                elements.loadingState.style.display = 'none';
                elements.bookingForm.style.display = 'block';
            });
        }

        // Send WhatsApp confirmation
        function sendWhatsAppConfirmation(formData, bookingNumber) {
            const message = encodeURIComponent(
                `Your service booking #${bookingNumber} is confirmed!\n` +
                `Date: ${formData.bookingDate}\n` +
                `Time: ${formData.bookingTime}\n` +
                `Package: ${formData.packageName}\n` +
                `Amount: LKR ${formData.totalAmount.toFixed(2)}`
            );

            // Uncomment to auto-open WhatsApp
            // window.open(`https://wa.me/${formData.customerMobile}?text=${message}`, '_blank');
        }
    </script>
</body>

</html>