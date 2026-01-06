<?php
// Initialize sidebar module visibility
$moduleVisibility = new ModuleVisibility();
$companyId = $_SESSION['company_id'] ?? 1;
$sidebarTree = $moduleVisibility->getSidebarTree($companyId);
$currentRoute = UserPermission::getCurrentPageRoute();

// Helper to check if any page in a category is active to keep menu open
function isCategoryActive($pages, $currentRoute) {
    foreach ($pages as $page) {
        if ($page['route'] == $currentRoute) return true;
    }
    return false;
}
?>
<style>
    /* ... (Existing CSS variables) ... */
    :root {
        --sidebar-bg: #ffffff;
        --sidebar-hover: #f8f9fa;
        --sidebar-active: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --sidebar-text: #2d3748;
        --sidebar-text-muted: #718096;
        --sidebar-divider: #e2e8f0;
        --sidebar-section-text: #a0aec0;
        --sidebar-active-shadow: rgba(102, 126, 234, 0.2);
        /* Icon Colors */
        --icon-dashboard: #667eea;
        --icon-customer: #48bb78;
        --icon-vehicle: #ed8936;
        --icon-service: #4299e1;
        --icon-service-list: #38b2ac;
        --icon-service-package: #9f7aea;
        --icon-service-stage: #ed64a6;
        --icon-booking: #10b981;
        --icon-system: #805ad5;
        --icon-users: #667eea;
        --icon-roles: #ed8936;
        --icon-pages: #4299e1;
        --icon-permissions: #48bb78;
        --icon-time-slot: #ff9f43;
        --icon-inventory: #e74c3c;
        --icon-supplier: #3498db;
        --icon-category: #9b59b6;
        --icon-item: #27ae60;
        --icon-grn: #e67e22;
        --icon-invoice: #8e44ad;
        --icon-expense: #ef4444;
        --icon-reports: #f59e0b;
        --icon-finance: #2ecc71;
    }

    /* ... (Keep other CSS styles 45-305) ... */
    .sidebar {
        background: var(--sidebar-bg) !important;
        color: var(--sidebar-text);
        transition: all 0.3s ease;
        box-shadow: 2px 0 20px rgba(0, 0, 0, 0.08);
        border-right: 1px solid var(--sidebar-divider);
        top: 60px !important;
        height: calc(100vh - 60px) !important;
        z-index: 1000;
    }

    .sidebar .nav-section {
        margin: 1.5rem 0 0.5rem;
        padding: 0 20px;
    }

    .sidebar .nav-section .text-section {
        color: var(--sidebar-section-text);
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.5rem;
    }

    .sidebar .nav-item>a {
        color: var(--sidebar-text);
        padding: 0.75rem 1.25rem;
        margin: 0.2rem 0.75rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        position: relative;
    }

    .sidebar .nav-item>a:hover {
        background: var(--sidebar-hover);
        transform: translateX(5px);
    }

    .sidebar .nav-item>a i {
        margin-right: 12px;
        width: 24px;
        height: 24px;
        text-align: center;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        padding: 4px;
    }

    /* Icon Color Classes */
    /* Dashboard */
    .sidebar .nav-item>a[href*="Dashboard"] i { color: var(--icon-dashboard); }
    /* Service */
    .sidebar .nav-collapse a[href*="Service/"] i { color: var(--icon-service); }
    .sidebar .nav-collapse a[href*="service_list"] i { color: var(--icon-service-list); }
    .sidebar .nav-collapse a[href*="Package"] i { color: var(--icon-service-package); }
    .sidebar .nav-collapse a[href*="Stage"] i { color: var(--icon-service-stage); }
    .sidebar .nav-collapse a[href*="Booking"] i { color: var(--icon-booking); }
    /* Customer */
    .sidebar .nav-collapse a[href*="Customer"] i { color: var(--icon-customer); }
    .sidebar .nav-collapse a[href*="Vehicle"] i { color: var(--icon-vehicle); }
    /* Inventory */
    .sidebar .nav-collapse a[href*="Supplier"] i { color: var(--icon-supplier); }
    .sidebar .nav-collapse a[href*="Category"] i { color: var(--icon-category); }
    .sidebar .nav-collapse a[href*="Item"] i { color: var(--icon-item); }
    .sidebar .nav-collapse a[href*="GRN"] i { color: var(--icon-grn); }
    /* Billing */
    .sidebar .nav-collapse a[href*="Invoice"] i { color: var(--icon-invoice); }
    .sidebar .nav-collapse a[href*="Expense"] i { color: var(--icon-expense); }
    /* Admin */
    .sidebar .nav-collapse a[href*="users"] i { color: var(--icon-users); }
    .sidebar .nav-collapse a[href*="Role"] i { color: var(--icon-roles); }
    .sidebar .nav-collapse a[href*="Page"] i { color: var(--icon-pages); }
    .sidebar .nav-collapse a[href*="Permission"] i { color: var(--icon-permissions); }
    /* Reports */
    .sidebar .nav-collapse a[href*="Reports"] i { color: var(--icon-reports); }
    /* Finance */
    .sidebar .nav-collapse a[href*="Finance"] i { color: var(--icon-finance); }

    .sidebar .nav-item.active>a {
        background: var(--sidebar-active);
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 15px var(--sidebar-active-shadow);
    }
    
    .sidebar .nav-item.active>a i {
        color: white !important;
        background: rgba(255, 255, 255, 0.2);
    }

    .sidebar .nav-collapse .sub-item {
        font-size: 0.9em;
        color: var(--sidebar-text-muted);
    }
    
    .sidebar .nav-collapse .nav-item a { padding: 0.6rem 1.25rem 0.6rem 3rem; }
    .sidebar .nav-collapse .nav-item a:hover .sub-item { color: var(--sidebar-text); }
    
    .sidebar .logo-header { padding: 1.5rem 1rem; border-bottom: 1px solid var(--sidebar-divider); background: #ffffff; }
    .sidebar .logo { display: flex; align-items: center; }
    .sidebar .logo img { max-height: 28px; width: auto; }
    .sidebar .nav-item>a p { margin: 0; font-size: 0.95rem; font-weight: 500; }
    .sidebar .caret { margin-left: auto; }
    .sidebar .sidebar-logo { display: none; }

    @media (max-width: 991px) {
        .sidebar { position: fixed !important; left: 0; transform: translateX(-100%); transition: transform 0.3s ease; z-index: 1050; }
        .sidebar.show { transform: translateX(0) !important; }
        body.sidebar-open::before { content: ''; position: fixed; top: 60px; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1040; }
    }
</style>

<div class="sidebar sidebar-style-2" data-background-color="light">
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary" style="padding-top: 1rem;">
                <?php foreach ($sidebarTree as $category => $catData): ?>
                    <?php 
                    // Verify actual page permission and visibility
                    // Only show pages that:
                    // 1. Are marked visible in sidebar_modules (handled by getSidebarTree)
                    // 2. The user has 'View' permission for (UserPermission check)
                    $visiblePages = [];
                    foreach ($catData['pages'] as $page) {
                        if ($page['is_visible']) { 
                             // Show the page in sidebar regardless of permissions
                             // Access control is handled at the page level (redirects to 403)
                             $visiblePages[] = $page;
                        }
                    }
                    
                    if (empty($visiblePages)) continue;

                    // Special Render for Dashboard (flat item)
                    if ($category === 'Dashboard' && count($visiblePages) > 0): 
                        $p = $visiblePages[0];
                        $isActive = ($currentRoute == $p['route']);
                    ?>
                        <li class="nav-item <?php echo $isActive ? 'active' : ''; ?>">
                            <a href="<?php echo BASE_URL . ltrim($p['route'], '/'); ?>">
                                <i class="<?php echo $p['icon']; ?>"></i>
                                <p><?php echo $p['name']; ?></p>
                            </a>
                        </li>
                    <?php else: ?>
                        <?php 
                        $catId = preg_replace('/[^a-zA-Z0-9]/', '', $category); 
                        $isOpen = isCategoryActive($visiblePages, $currentRoute);
                        ?>
                        <li class="nav-item <?php echo $isOpen ? 'active submenu' : ''; ?>">
                            <a data-bs-toggle="collapse" href="#<?php echo $catId; ?>" aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>">
                                <i class="<?php echo $catData['icon']; ?>"></i>
                                <p><?php echo $category; ?></p>
                                <span class="caret"></span>
                            </a>
                            <div class="collapse <?php echo $isOpen ? 'show' : ''; ?>" id="<?php echo $catId; ?>">
                                <ul class="nav nav-collapse">
                                    <?php foreach ($visiblePages as $page): ?>
                                        <li class="<?php echo ($currentRoute == $page['route']) ? 'active' : ''; ?>">
                                            <a href="<?php echo BASE_URL . ltrim($page['route'], '/'); ?>">
                                                <i class="<?php echo $page['icon']; ?> me-2"></i>
                                                <span class="sub-item"><?php echo $page['name']; ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
