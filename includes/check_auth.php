<?php
require_once __DIR__ . '/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // If it's an AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized access']);
        exit();
    }
    
    // For regular requests, redirect to login page
    header('Location: /greentrack/login.html');
    exit();
}
?> 