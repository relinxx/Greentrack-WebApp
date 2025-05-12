<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/middleware.php';

// Check admin authentication
checkAdminAuth();

try {
    // Validate input
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid report ID');
    }

    $report_id = intval($_GET['id']);

    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare query to get report details with user info
    $query = "SELECT r.*, u.username 
              FROM reports r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.id = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $report_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        throw new Exception('Report not found');
    }

    // Format the response
    $formatted_report = [
        'id' => $report['id'],
        'user_id' => $report['user_id'],
        'username' => $report['username'],
        'latitude' => $report['latitude'],
        'longitude' => $report['longitude'],
        'description' => $report['description'],
        'photo_path' => $report['photo_path'] ? str_replace('uploads/uploads/', 'uploads/', $report['photo_path']) : null,
        'status' => $report['status'],
        'created_at' => $report['created_at']
    ];
    
    echo json_encode(['data' => $formatted_report]);
} catch(PDOException $e) {
    error_log("Database error in report_detail.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error occurred", "message" => $e->getMessage()]);
} catch(Exception $e) {
    error_log("General error in report_detail.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "An error occurred", "message" => $e->getMessage()]);
}
?> 