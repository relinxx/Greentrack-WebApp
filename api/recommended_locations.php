<?php
header('Content-Type: application/json');
require_once 'config/database.php';

try {
    // Prepare query to get recommended locations
    $query = "SELECT rl.*, u.username as admin_name 
              FROM recommended_locations rl
              JOIN users u ON rl.created_by_admin_id = u.id
              ORDER BY rl.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $formatted_locations = array_map(function($location) {
        return [
            'id' => $location['id'],
            'name' => $location['name'],
            'latitude' => (float)$location['latitude'],
            'longitude' => (float)$location['longitude'],
            'description' => $location['description'],
            'admin_name' => $location['admin_name'],
            'created_at' => $location['created_at']
        ];
    }, $locations);
    
    echo json_encode(["locations" => $formatted_locations]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?> 