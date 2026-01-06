<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

$configPath = dirname(dirname(__DIR__)) . '/config/config.php';
if (!file_exists($configPath)) {
    die(json_encode(['status' => 'error', 'message' => 'Configuration not found']));
}
require_once $configPath;

$classesPath = dirname(dirname(__DIR__)) . '/classes/Includes.php';
if (!file_exists($classesPath)) {
    die(json_encode(['status' => 'error', 'message' => 'Classes not found']));
}
require_once $classesPath;

// Initialize database connection
$db = new Database();

// CSRF Protection for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // Parameter class requires DB injected
    $parameter = new Parameter($db);

    // Default LIST (kept empty because method not implemented)
    if ($action === 'list' || $action === '') {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }

    // GET parameter value
    if ($action === 'get') {
        $key = trim($_GET['key'] ?? $_POST['key'] ?? '');
        if (empty($key)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter key is required']);
            exit;
        }

        $value = $parameter->get($key);
        if ($value === null) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter not found']);
        } else {
            echo json_encode(['status' => 'success', 'data' => $value]);
        }
        exit;
    }

    // INCREMENT parameter
    if ($action === 'increment') {
        $key = trim($_POST['key'] ?? '');
        $incrementBy = isset($_POST['increment_by']) ? (int)$_POST['increment_by'] : 1;

        if (empty($key)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter key is required']);
            exit;
        }

        $newValue = $parameter->increment($key, $incrementBy);
        echo json_encode(['status' => 'success', 'data' => $newValue]);
        exit;
    }

    // Invalid action fallback
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}
