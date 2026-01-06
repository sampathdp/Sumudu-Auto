<?php
// Load required files
require_once __DIR__ . '/../classes/Includes.php';

// Initialize user if logged in
$user = null;
if (isLoggedIn()) {
    try {
        $user = new User($_SESSION['id']);
    } catch (Exception $e) {
        error_log('User initialization error: ' . $e->getMessage());
    }
}

// Load company profile for header
$companyProfile = new CompanyProfile();
$companyProfile->loadActive($_SESSION['company_id'] ?? 1);
$companyLogo = $companyProfile->image_name ? BASE_URL . 'uploads/company/' . $companyProfile->image_name : null;
$companyName = $companyProfile->name ?: (APP_NAME ?? 'GaragePulse');

// Theme gradient mapping
$themeGradients = [
    'default' => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
    'purple' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'blue' => 'linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%)',
    'green' => 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
    'red' => 'linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%)',
    'orange' => 'linear-gradient(135deg, #f46b45 0%, #eea849 100%)',
    'dark' => 'linear-gradient(135deg, #232526 0%, #414345 100%)',
    'teal' => 'linear-gradient(135deg, #0f2027 0%, #2c5364 100%)',
];
$headerGradient = $themeGradients[$companyProfile->theme] ?? $themeGradients['default'];
?>

<style>
.top-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: <?php echo $headerGradient; ?>;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1rem;
    z-index: 1030;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.header-brand {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: white;
    gap: 0.75rem;
}

.header-brand:hover {
    color: #fff;
    text-decoration: none;
}

.header-logo {
    height: 36px;
    max-width: 120px;
    object-fit: contain;
}

.header-title {
    font-weight: 700;
    font-size: 1.1rem;
    color: #fff;
    white-space: nowrap;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.header-btn:hover {
    background: rgba(255,255,255,0.2);
}

.header-btn .badge {
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 0.65rem;
    padding: 2px 5px;
}

.user-dropdown {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.75rem;
    border-radius: 25px;
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.user-dropdown:hover {
    background: rgba(255,255,255,0.2);
    color: #fff;
    text-decoration: none;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

.user-name {
    font-weight: 500;
    font-size: 0.9rem;
}

/* Mobile sidebar toggle */
.sidebar-toggle {
    display: none;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
    cursor: pointer;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    border-radius: 12px;
    padding: 0.5rem;
}

.dropdown-item {
    border-radius: 8px;
    padding: 0.6rem 1rem;
}

@media (max-width: 768px) {
    .sidebar-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
    }
    
    .header-title {
        display: none;
    }
    
    .user-name {
        display: none;
    }
    
    .user-dropdown {
        padding: 0.25rem;
        background: transparent;
    }
}

/* Dark Mode Variables */
:root {
    --bg-light: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
}

body.dark-mode {
    /* Global Colors */
    --bg-light: #0f172a;
    --bg-card: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --border-color: #334155;
    
    /* Variable Overrides for Page Scripts */
    --primary: #818cf8; /* Lighten primary for dark mode */
    --bg-main: var(--bg-light);
    
    /* Sidebar Overrides */
    --sidebar-bg: #1e293b !important;
    --sidebar-hover: #334155 !important;
    --sidebar-text: #e2e8f0 !important;
    --sidebar-text-muted: #94a3b8 !important;
    --sidebar-divider: #334155 !important;
    --sidebar-section-text: #cbd5e1 !important;
    
    background-color: var(--bg-light) !important;
    color: var(--text-primary) !important;
}

/* Sidebar Dark Mode Fixes */
body.dark-mode .sidebar {
    background: var(--sidebar-bg) !important;
    border-right-color: var(--sidebar-divider) !important;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.2) !important;
}
body.dark-mode .sidebar .logo-header {
    background: var(--sidebar-bg) !important;
    border-bottom-color: var(--sidebar-divider) !important;
}
body.dark-mode .sidebar .nav-collapse .nav-item a:hover {
    background: var(--sidebar-hover) !important;
}

/* Card & Container Overrides */
body.dark-mode .card, 
body.dark-mode .company-card, 
body.dark-mode .module-section,
body.dark-mode .page-header,
body.dark-mode .selector-card,
body.dark-mode .stat-card, 
body.dark-mode .chart-card, 
body.dark-mode .table-card,
body.dark-mode .qr-card,
body.dark-mode .quick-nav-card,
body.dark-mode .quick-actions-card,
body.dark-mode .data-card {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
    color: var(--text-primary) !important;
    box-shadow: none !important;
}

body.dark-mode .data-card-header {
    background-color: var(--bg-light) !important;
    border-bottom-color: var(--border-color) !important;
}

body.dark-mode .data-card-header h5 {
    color: var(--text-primary) !important;
}

/* Titles & Headings */
body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, 
body.dark-mode h4, body.dark-mode h5, body.dark-mode h6,
body.dark-mode .card-title, 
body.dark-mode .page-title,
body.dark-mode .header-title {
    color: #f8fafc !important;
}

/* Text Visibility Fixes */
body.dark-mode label,
body.dark-mode .form-label,
body.dark-mode .modal-title,
body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, 
body.dark-mode h4, body.dark-mode h5, body.dark-mode h6,
body.dark-mode th {
    color: #ffffff !important;
}

body.dark-mode .text-muted,
body.dark-mode .text-secondary,
body.dark-mode .form-text,
body.dark-mode .card-category,
body.dark-mode p {
    color: #cbd5e1 !important; /* Lighter gray for secondary text */
}

body.dark-mode .form-control,
body.dark-mode .form-select {
    color: #ffffff !important;
    background-color: #1e293b !important; /* Slightly lighter than bg-main */
}

body.dark-mode .form-control::placeholder {
    color: #94a3b8 !important;
    opacity: 1;
}

/* Fix input borders to be visible */
body.dark-mode .form-control,
body.dark-mode .form-select,
body.dark-mode .input-group-text {
    border-color: #475569 !important;
}

/* Modals */

/* Tables */
body.dark-mode .table {
    color: var(--text-primary) !important;
    background-color: var(--bg-card) !important;
}
body.dark-mode .table thead th {
    background-color: #0f172a !important; /* Darker header */
    color: #e2e8f0 !important;
    border-bottom-color: var(--border-color) !important;
}
body.dark-mode .table tbody td,
body.dark-mode .table th {
    border-color: var(--border-color) !important;
}
body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(255, 255, 255, 0.02) !important;
}
body.dark-mode .table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05) !important;
    color: #fff !important;
}

/* Forms */
body.dark-mode .form-control,
body.dark-mode .form-select,
body.dark-mode .input-group-text {
    background-color: #0f172a !important;
    border-color: var(--border-color) !important;
    color: var(--text-primary) !important;
}
body.dark-mode .form-control:focus,
body.dark-mode .form-select:focus {
    background-color: #0f172a !important;
    border-color: var(--primary) !important;
    color: #fff !important;
}
body.dark-mode .form-control::placeholder {
    color: #64748b !important;
}

/* Modals */
body.dark-mode .modal-content {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
}
body.dark-mode .modal-header,
body.dark-mode .modal-footer {
    border-color: var(--border-color) !important;
}
body.dark-mode .close, 
body.dark-mode .modal-title {
    color: var(--text-primary) !important;
}

/* Dropdowns */
body.dark-mode .dropdown-menu {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
}
body.dark-mode .dropdown-item {
    color: var(--text-primary) !important;
}
body.dark-mode .dropdown-item:hover,
body.dark-mode .dropdown-item:focus {
    background-color: rgba(255, 255, 255, 0.1) !important;
    color: #fff !important;
}

/* Badges & Buttons */
body.dark-mode .btn-outline-secondary {
    border-color: var(--text-secondary) !important;
    color: var(--text-secondary) !important;
}
body.dark-mode .btn-outline-secondary:hover {
    background-color: var(--text-secondary) !important;
    color: #fff !important;
}

/* SweetAlert overrides */
body.dark-mode .swal2-popup {
    background: var(--bg-card) !important;
    color: var(--text-primary) !important;
}
body.dark-mode .swal2-title,
body.dark-mode .swal2-content {
    color: var(--text-primary) !important;
}

/* Sidebar Specific Text Overrides */
body.dark-mode .sidebar .nav-item a {
    color: #ffffff !important; /* User requested white */
    transition: all 0.3s ease;
}

body.dark-mode .sidebar .nav-item a:hover {
    color: #ffffff !important;
    background: rgba(99, 102, 241, 0.15) !important; /* Match active primary color */
}

body.dark-mode .sidebar .nav-item a:hover i {
    color: #ffffff !important;
}

body.dark-mode .sidebar .nav-item a p,
body.dark-mode .sidebar .nav-collapse .sub-item,
body.dark-mode .sidebar .caret {
    color: inherit !important;
}

/* Active State & Expanded State */
body.dark-mode .sidebar .nav-item.active > a,
body.dark-mode .sidebar .nav-item a[aria-expanded="true"] {
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.2), rgba(99, 102, 241, 0.05)) !important;
    color: #ffffff !important;
    border-left: 4px solid var(--primary);
    border-radius: 4px; /* Slight rounding for modern look */
}

body.dark-mode .sidebar .nav-item.active > a p,
body.dark-mode .sidebar .nav-item.active > a i,
body.dark-mode .sidebar .nav-item.active > a .caret,
body.dark-mode .sidebar .nav-item a[aria-expanded="true"] p,
body.dark-mode .sidebar .nav-item a[aria-expanded="true"] i,
body.dark-mode .sidebar .nav-item a[aria-expanded="true"] .caret {
    color: #ffffff !important;
}
 

body.dark-mode .sidebar .nav-item.active > a i,
body.dark-mode .sidebar .nav-item a[aria-expanded="true"] i {
    color: #ffffff !important; /* White icons on active */
}

/* Submenu Styling */
body.dark-mode .sidebar .nav-collapse {
    background-color: #0f172a !important; /* Darker bg for nested items */
    margin-top: 5px;
    border-radius: 8px;
}

body.dark-mode .sidebar .nav-collapse li a {
    background: transparent !important;
}

body.dark-mode .sidebar .nav-collapse li.active > a {
    color: #ffffff !important;
    font-weight: 600;
}

body.dark-mode .sidebar .nav-section .text-section {
    color: #475569 !important;
    font-weight: 700;
}
body.dark-mode .sidebar .scrollbar-inner {
    background: var(--sidebar-bg) !important;
}

/* Card Body Fix */

/* Card Body Fix */
body.dark-mode .card-body {
    background-color: transparent !important;
    color: var(--text-primary) !important;
}

/* DataTables & Tables Fix */
body.dark-mode .dataTables_wrapper .dataTables_length,
body.dark-mode .dataTables_wrapper .dataTables_filter,
body.dark-mode .dataTables_wrapper .dataTables_info,
body.dark-mode .dataTables_wrapper .dataTables_processing,
body.dark-mode .dataTables_wrapper .dataTables_paginate {
    color: var(--text-primary) !important;
}

body.dark-mode .dataTables_wrapper .dataTables_length select,
body.dark-mode .dataTables_wrapper .dataTables_filter input {
    background-color: var(--bg-light) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-primary) !important;
}

body.dark-mode .table > :not(caption) > * > * {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
    border-color: var(--border-color) !important;
}

body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * {
    background-color: rgba(255, 255, 255, 0.03) !important;
    color: var(--text-primary) !important;
}

body.dark-mode .table-hover > tbody > tr:hover > * {
    background-color: rgba(255, 255, 255, 0.05) !important;
    color: #ffffff !important;
}

body.dark-mode .page-item.disabled .page-link {
    background-color: var(--bg-light) !important;
    border-color: var(--border-color) !important;
    color: var(--text-secondary) !important;
}
body.dark-mode .page-item .page-link {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
    color: var(--text-primary) !important;
}
body.dark-mode .page-item.active .page-link {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #ffffff !important;
}

/* Quick Nav Specifics */
body.dark-mode .quick-nav-card {
    background: var(--bg-card) !important;
}
body.dark-mode .quick-nav-title {
    color: #fff !important;
}
body.dark-mode .quick-nav-desc {
    color: var(--text-secondary) !important;
}
</style>

<header class="top-header">
    <!-- Left: Sidebar Toggle + Brand -->
    <div class="d-flex align-items-center">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="<?php echo BASE_URL; ?>" class="header-brand">
            <?php if ($companyLogo): ?>
                <img src="<?php echo $companyLogo; ?>" alt="<?php echo htmlspecialchars($companyName); ?>" class="header-logo">
            <?php else: ?>
                <div class="header-logo d-flex align-items-center justify-content-center" style="width: 36px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px;">
                    <i class="fas fa-car text-white"></i>
                </div>
            <?php endif; ?>
            <span class="header-title"><?php echo htmlspecialchars($companyName); ?></span>
        </a>
    </div>

    <!-- Right: Actions -->
    <div class="header-actions">
        <!-- Dark Mode Toggle -->
        <button class="header-btn" id="darkModeToggle" title="Toggle Dark Mode">
            <i class="fas fa-moon"></i>
        </button>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="header-btn" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span class="badge bg-danger">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2 text-muted text-center">
                    <i class="fas fa-bell-slash me-2"></i>No new notifications
                </li>
            </ul>
        </div>

        <?php if ($user): ?>
        <!-- User Menu -->
        <div class="dropdown">
            <a href="#" class="user-dropdown" data-bs-toggle="dropdown">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span class="user-name"><?php echo htmlspecialchars($user->username); ?></span>
                <i class="fas fa-chevron-down ms-1" style="font-size: 0.7rem;"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <div class="px-3 py-2 border-bottom mb-2">
                        <div class="fw-bold"><?php echo htmlspecialchars($user->username); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($user->email ?? ''); ?></small>
                    </div>
                </li>
                <li>
                    <form action="<?php echo BASE_URL; ?>Ajax/php/user.php" method="post">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const darkModeToggle = document.getElementById('darkModeToggle');
    const icon = darkModeToggle.querySelector('i');
    
    // Check local storage
    const isDarkMode = localStorage.getItem('theme') === 'dark';
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }

    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            
            // Toggle icon
            if (isDark) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                localStorage.setItem('theme', 'dark');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                localStorage.setItem('theme', 'light');
            }

            // Optional: Save to DB via AJAX if needed
            /*
            fetch('<?php echo BASE_URL; ?>Ajax/php/settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=update_theme&theme=' + (isDark ? 'dark' : 'light')
            });
            */
        });
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.toggle('show');
            document.body.classList.toggle('sidebar-open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            }
        });
    }
});
</script>