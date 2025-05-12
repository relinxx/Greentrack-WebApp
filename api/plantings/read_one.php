<?php
// Prevent any output before headers
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set headers
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    
    // Encode and send response
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // Include database and object files
    include_once __DIR__ . '/../../config/config.php';
    include_once __DIR__ . '/../../includes/db.php';
    include_once __DIR__ . '/../../lib/TreePlanting.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Instantiate planting object
    $planting = new TreePlanting($db);

    // Get ID from query parameter
    if (!isset($_GET["id"])) {
        throw new Exception("Planting ID is required");
    }

    $planting->id = $_GET["id"];
    error_log("Fetching planting with ID: " . $planting->id);

    // Read the details of planting to be fetched
    if ($planting->readOne()) {
        error_log("Successfully found planting: " . print_r([
            "id" => $planting->id,
            "user_id" => $planting->user_id,
            "status" => $planting->status,
            "species_name" => $planting->species_name_reported
        ], true));
        
        // Create array
        $planting_item = [
            "id" => $planting->id,
            "user_id" => $planting->user_id,
            "username" => $planting->username,
            "species_id" => $planting->species_id,
            "species_name_reported" => $planting->species_name_reported,
            "species_common_name" => $planting->species_common_name,
            "quantity" => (int)$planting->quantity,
            "planting_date" => $planting->planting_date,
            "latitude" => (float)$planting->latitude,
            "longitude" => (float)$planting->longitude,
            "photo_before_path" => $planting->photo_before_path,
            "photo_after_path" => $planting->photo_after_path,
            "status" => $planting->status,
            "verified_by_admin_id" => $planting->verified_by_admin_id,
            "verifier_username" => $planting->verifier_username,
            "verification_notes" => $planting->verification_notes,
            "verified_at" => $planting->verified_at,
            "created_at" => $planting->created_at,
            "updated_at" => $planting->updated_at
        ];

        sendJsonResponse($planting_item);
    } else {
        error_log("No planting found with ID: " . $planting->id);
        sendJsonResponse(["message" => "Tree planting record not found."], 404);
    }
} catch (Exception $e) {
    error_log("Error in read_one.php: " . $e->getMessage());
    sendJsonResponse([
        "message" => "Error retrieving planting details",
        "error" => $e->getMessage()
    ], 500);
}

// End output buffering
ob_end_flush();
?>
