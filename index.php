

<?php
// Set session configuration before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);

require_once 'config/config.php';

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (isset($_SESSION['user_id'])) {
    header('Location: views/Dashboard/index.php');
} else {
    header('Location: views/auth/login.php');
}
exit();
?>
