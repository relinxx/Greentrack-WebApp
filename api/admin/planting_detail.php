<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/middleware.php';

// Check admin authentication
checkAdminAuth();

try {
    // Validate input
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid planting ID');
    }

    $planting_id = intval($_GET['id']);

    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare query to get planting details with user info
    $query = "SELECT p.*, u.username 
              FROM tree_plantings p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.id = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $planting_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $planting = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$planting) {
        throw new Exception('Planting not found');
    }

    // Format the response
    $formatted_planting = [
        'id' => $planting['id'],
        'user_id' => $planting['user_id'],
        'username' => $planting['username'],
        'species_name_reported' => $planting['species_name_reported'],
        'quantity' => $planting['quantity'],
        'latitude' => $planting['latitude'],
        'longitude' => $planting['longitude'],
        'photo_before_path' => $planting['photo_before_path'] ? str_replace('uploads/uploads/', 'uploads/', $planting['photo_before_path']) : null,
        'photo_after_path' => $planting['photo_after_path'] ? str_replace('uploads/uploads/', 'uploads/', $planting['photo_after_path']) : null,
        'status' => $planting['status'],
        'created_at' => $planting['created_at']
    ];
    
    echo json_encode(['data' => $formatted_planting]);
} catch(PDOException $e) {
    error_log("Database error in planting_detail.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error occurred", "message" => $e->getMessage()]);
} catch(Exception $e) {
    error_log("General error in planting_detail.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "An error occurred", "message" => $e->getMessage()]);
}
?> 