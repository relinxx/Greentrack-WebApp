<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/middleware.php';

try {
    // Check admin authentication
    checkAdminAuth();
    
    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all recommended locations
    $stmt = $conn->prepare("
        SELECT * FROM recommended_locations 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['locations' => $locations]);
    
} catch(PDOException $e) {
    error_log("Database error in recommended_locations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'details' => $e->getMessage(),
        'sql_state' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
} catch(Exception $e) {
    error_log("General error in recommended_locations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred',
        'details' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?> 