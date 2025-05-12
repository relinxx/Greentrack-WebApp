<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once 'middleware.php';
require_once '../lib/AdminActionLogger.php';

// Verify admin session
$admin = verifyAdminSession();

// Get location ID from URL
$location_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$location_id) {
    http_response_code(400);
    echo json_encode(["message" => "Location ID is required"]);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Get location details for logging
    $query = "SELECT name FROM recommended_locations WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $location_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception("Location not found");
    }
    
    $location = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete location
    $query = "DELETE FROM recommended_locations WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $location_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete location");
    }
    
    // Log admin action
    $actionLogger = new AdminActionLogger($conn);
    $actionLogger->logAction(
        $admin->user_id,
        'delete_recommended_location',
        null,
        null,
        "Deleted recommended location: {$location['name']}"
    );
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(["message" => "Location deleted successfully"]);
} catch(Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?> 