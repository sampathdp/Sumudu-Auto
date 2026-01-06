<?php
// Simple test endpoint to verify AJAX is working
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'AJAX endpoint is working',
    'timestamp' => date('Y-m-d H:i:s'),
    'get_params' => $_GET
]);
?>
