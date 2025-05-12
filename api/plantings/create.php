<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . 
'/../../config/config.php'
;
include_once __DIR__ . 
'/../../includes/db.php'
;
include_once __DIR__ . 
'/../../lib/TreePlanting.php'
;
// Removed JWT Handler include

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate planting object
$planting = new TreePlanting($db);

// Get posted data
// Use $_POST for form-data, which is needed for file uploads
$data = $_POST;

// Check if required data is present (including user_id)
if (
    !empty($data["user_id"]) && is_numeric($data["user_id"]) && // Added user_id check
    !empty($data["species_name_reported"]) &&
    !empty($data["quantity"]) && is_numeric($data["quantity"]) &&
    !empty($data["planting_date"]) &&
    !empty($data["latitude"]) && is_numeric($data["latitude"]) &&
    !empty($data["longitude"]) && is_numeric($data["longitude"])
) {
    // Set planting property values
    $planting->user_id = $data["user_id"]; // Get user_id from POST data
    $planting->species_name_reported = $data["species_name_reported"];
    $planting->quantity = $data["quantity"];
    $planting->planting_date = $data["planting_date"];
    $planting->latitude = $data["latitude"];
    $planting->longitude = $data["longitude"];
    $planting->species_id = $data["species_id"] ?? null; // Optional

    // --- Handle Photo Uploads (Basic Placeholder) ---
    // This needs a robust implementation: validation, unique naming, moving to a secure directory.
    $upload_dir = __DIR__ . 
'/../../uploads/plantings/'
;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES["photo_before"]) && $_FILES["photo_before"]["error"] == 0) {
        $filename_before = uniqid("before_", true) . "." . pathinfo($_FILES["photo_before"]["name"], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES["photo_before"]["tmp_name"], $upload_dir . $filename_before)) {
            $planting->photo_before_path = "/uploads/plantings/" . $filename_before; // Relative path for web access
        }
    }
    if (isset($_FILES["photo_after"]) && $_FILES["photo_after"]["error"] == 0) {
        $filename_after = uniqid("after_", true) . "." . pathinfo($_FILES["photo_after"]["name"], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES["photo_after"]["tmp_name"], $upload_dir . $filename_after)) {
            $planting->photo_after_path = "/uploads/plantings/" . $filename_after; // Relative path for web access
        }
    }
    // --- End Photo Upload Handling ---

    // Create the planting
    if ($planting->create()) {
        http_response_code(201);
        echo json_encode(["message" => "Tree planting record created.", "plantingId" => $planting->id]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to create planting record."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create planting record. Incomplete or invalid data. Ensure user_id, species_name_reported, quantity, planting_date, latitude, and longitude are provided."]); // Updated error message
}
?>

