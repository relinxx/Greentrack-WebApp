<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once 'middleware.php';
require_once __DIR__ . '/../../lib/AdminActionLogger.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

try {
    // Check admin authentication
    checkAdminAuth($conn);
    
    // Get admin ID from session
    $admin_id = $_SESSION['admin_id'];
    
    // Get posted data
    $input = file_get_contents("php://input");
    if (!$input) {
        sendJsonResponse(['error' => 'No input data received'], 400);
    }

    $data = json_decode($input);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['error' => 'Invalid JSON data: ' . json_last_error_msg()], 400);
    }

    // Validate input
    if (!isset($data->name) || !isset($data->latitude) || !isset($data->longitude) || !isset($data->description)) {
        sendJsonResponse(['error' => 'All fields are required'], 400);
    }

    // Start transaction
    $conn->beginTransaction();
    
    // Insert new location
    $query = "INSERT INTO recommended_locations 
              (name, latitude, longitude, description, created_by_admin_id) 
              VALUES 
              (:name, :latitude, :longitude, :description, :admin_id)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":name", $data->name);
    $stmt->bindParam(":latitude", $data->latitude);
    $stmt->bindParam(":longitude", $data->longitude);
    $stmt->bindParam(":description", $data->description);
    $stmt->bindParam(":admin_id", $admin_id);
    
    // Log the values being inserted
    error_log("Attempting to insert location with values: " . 
              "name=" . $data->name . 
              ", lat=" . $data->latitude . 
              ", lng=" . $data->longitude . 
              ", desc=" . $data->description . 
              ", admin_id=" . $admin_id);
    
    if (!$stmt->execute()) {
        $error = $stmt->errorInfo();
        error_log("Database error: " . print_r($error, true));
        throw new Exception("Failed to add location: " . $error[2]);
    }
    
    $location_id = $conn->lastInsertId();
    
    // Log admin action
    $actionLogger = new AdminActionLogger($conn);
    $actionLogger->logAction(
        $admin_id,
        'add_recommended_location',
        null,
        null,
        "Added recommended location: {$data->name}"
    );
    
    // Commit transaction
    $conn->commit();
    
    sendJsonResponse([
        "message" => "Location added successfully",
        "location_id" => $location_id
    ]);

} catch(Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Error in add_location.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    sendJsonResponse([
        "error" => "Error: " . $e->getMessage()
    ], 500);
}
?> 