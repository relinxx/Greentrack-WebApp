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
    
    // Get all reports with user details
    $stmt = $conn->prepare("
        SELECT r.*, u.username 
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $formattedReports = array_map(function($report) {
        return [
            'id' => $report['id'],
            'username' => $report['username'],
            'description' => $report['description'],
            'latitude' => $report['latitude'],
            'longitude' => $report['longitude'],
            'status' => $report['status'],
            'photo_path' => $report['photo_path'],
            'created_at' => $report['created_at']
        ];
    }, $reports);
    
    echo json_encode(['reports' => $formattedReports]);
    
} catch(PDOException $e) {
    error_log("Database error in reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'details' => $e->getMessage(),
        'sql_state' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
} catch(Exception $e) {
    error_log("General error in reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred',
        'details' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?> 