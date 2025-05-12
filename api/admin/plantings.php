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
    
    // Get all plantings with user details
    $stmt = $conn->prepare("
        SELECT p.*, u.username 
        FROM tree_plantings p 
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $plantings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $formattedPlantings = array_map(function($planting) {
        return [
            'id' => $planting['id'],
            'username' => $planting['username'],
            'species_name_reported' => $planting['species_name_reported'],
            'quantity' => $planting['quantity'],
            'latitude' => $planting['latitude'],
            'longitude' => $planting['longitude'],
            'status' => $planting['status'],
            'photo_before_path' => $planting['photo_before_path'],
            'photo_after_path' => $planting['photo_after_path'],
            'created_at' => $planting['created_at']
        ];
    }, $plantings);
    
    echo json_encode(['plantings' => $formattedPlantings]);
    
} catch(PDOException $e) {
    error_log("Database error in plantings.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'details' => $e->getMessage(),
        'sql_state' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
} catch(Exception $e) {
    error_log("General error in plantings.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred',
        'details' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?> 