<?php
// Database connection file for the GreenTrack application
// This file should be included by API endpoints that need database access

// Database configuration is defined in config.php
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
    // Make sure we have the config loaded
    require_once __DIR__ . '/../../config/config.php';
}

// Establish database connection
try {
    // Create a PDO instance for MySQL connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT;
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    
    // Set PDO to throw exceptions on error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set character set to UTF-8
    $conn->exec("SET NAMES 'utf8'");
    
} catch(PDOException $e) {
    // Log the error and return a JSON error message
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Only output error details if headers haven't been sent yet
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(["message" => "Database connection failed", "error" => $e->getMessage()]);
    }
    
    // Stop script execution
    exit;
}
?> 