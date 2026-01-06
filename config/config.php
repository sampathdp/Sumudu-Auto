<?php
/**
 * Main Configuration File
 * Centralized configuration for the entire application
 */
// Prevent direct access
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
// ============================================
// PATH CONFIGURATION
// ============================================
define('BASE_PATH', dirname(__DIR__));
// Auto-detect base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
// For local: http://localhost/cims/
// For live: https://yourdomain.com/
if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
    define('BASE_URL', $protocol . $host . '/VSC/');
} else {
    // For live server - adjust if your app is in a subdirectory
    define('BASE_URL', $protocol . $host . '/');
}
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
// ============================================
// APPLICATION SETTINGS
// ============================================
define('APP_NAME', 'Vehicle Service Management System');
define('APP_VERSION', '1.0.0');
// ============================================
// DATABASE CONFIGURATION
// ============================================
// Auto-detect environment
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1']);
if ($isLocal) {
    // Local Development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'vehicle_service');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    // Development settings
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production Server
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'cqzydpdcez_vehicle_service');
    define('DB_USER', 'cqzydpdcez_vehicle_service');
    define('DB_PASS', 'w&W=LX[P{3)K');
    define('DB_CHARSET', 'utf8mb4');
    // Production settings
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}
// ============================================
// SECURITY SETTINGS
// ============================================
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
// ============================================
// FILE UPLOAD SETTINGS
// ============================================
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', 'pdf,doc,docx,jpg,jpeg,png');
// ============================================
// USER ROLES
// ============================================
define('ROLE_ADMIN', 'Admin');
define('ROLE_LAWYER', 'Lawyer');
define('ROLE_PARALEGAL', 'Paralegal');
define('ROLE_RECEPTIONIST', 'Receptionist');
// ============================================
// STATUS CONSTANTS
// ============================================
define('STATUS_OPEN', 'Open');
define('STATUS_PENDING', 'Pending');
define('STATUS_CLOSED', 'Closed');
define('STATUS_IN_PROGRESS', 'In Progress');
define('STATUS_COMPLETED', 'Completed');
// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Asia/Colombo');
// ============================================
// SESSION CONFIGURATION
// ============================================
$isSecure = strpos($protocol, 'https') === 0;
if (session_status() === PHP_SESSION_NONE) {
    // Only set cookie params if session hasn't started yet
    if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
        // For PHP 7.3.0 and above
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $host,
            'secure' => !$isLocal && $isSecure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    } else {
        // For PHP versions below 7.3.0
        session_set_cookie_params(
            0, // lifetime
            '/', // path
            $host, // domain
            !$isLocal && $isSecure, // secure
            true // httponly
            // Note: samesite is not supported in older PHP versions
        );
    }
    session_start();
}
// Check session timeout FIRST
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Generate CSRF token if not exists (AFTER timeout check)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// ============================================
// HELPER FUNCTIONS
// ============================================
function dd($data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    exit();
}
function redirect($url)
{
    header("Location: $url");
    exit();
}
function asset($path)
{
    return BASE_URL . 'assets/' . ltrim($path, '/');
}
function isLoggedIn()
{
    return isset($_SESSION['id']) && !empty($_SESSION['id']);
}
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'views/auth/login.php');
    }
}
function hasRole($roles)
{
    if (!isLoggedIn()) return false;
    $userRole = $_SESSION['user_role'] ?? '';
    return is_array($roles) ? in_array($userRole, $roles) : $userRole === $roles;
}
function requireRole($roles)
{
    requireLogin();
    if (!hasRole($roles)) {
        http_response_code(403);
        die('<h1>403 Forbidden</h1><p>Access denied.</p>');
    }
}

/**
 * Check if user has permission to view current page
 * @param string $permissionName Permission to check (default: "View")
 * @return bool
 */
function hasPagePermission($permissionName = 'View')
{
    if (!isLoggedIn()) {
        return false;
    }
    
    // UserPermission class will be loaded via Includes.php
    return UserPermission::checkPagePermission($_SESSION['id'], $permissionName, $_SESSION['company_id'] ?? null);
}

/**
 * Require page permission - redirect to access denied if not authorized
 * @param string $permissionName Permission to check (default: "View")
 */
function requirePagePermission($permissionName = 'View')
{
    requireLogin();
    
    if (!hasPagePermission($permissionName)) {
        redirect(BASE_URL . 'views/auth/access_denied.php');
    }
}

/**
 * Check if a specific UI part should be visible
 * @param string $componentKey Unique key for the UI component
 * @param int|null $pageId Optional page ID context
 * @return bool
 */
function isUIVisible($componentKey, $pageId = null)
{
    if (!isLoggedIn()) {
        return false;
    }

    // Admins (Role ID 1) generally see everything, OR strictly follow rules?
    // User requirement: "Only users with role = 1 (Super Admin) can manage visibility permissions."
    // "Super Admin can assign permissions per company."
    // This implies Super Admin *manages* it, but *viewing* is subject to rules?
    // Usually Super Admin bypasses. But for testing "as a company", they might want to see what's hidden.
    // However, usually Super Admin is NOT bound by company restrictions if they are "Super".
    // But if logged in as a specific company admin?
    // Let's assume strict Rule adherence for now. If a rule hides it, it's hidden.

    return CompanyUISettings::isVisible($_SESSION['company_id'], $componentKey, $pageId);
}