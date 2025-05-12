<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../lib/TreePlanting.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize tree planting object
$planting = new TreePlanting($db);

// Get query parameters
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'desc' : 'asc';
$bbox = isset($_GET['bbox']) ? $_GET['bbox'] : null;

error_log("Reading plantings with params: user_id=" . $user_id . ", status=" . $status);

// Read plantings
$stmt = $planting->read($user_id, $status, $limit, $offset, $order, $bbox);

// Check if any plantings found
if ($stmt) {
    $num = $stmt->rowCount();

    if ($num > 0) {
        // Plantings array
        $plantings_arr = array();
        $plantings_arr["records"] = array();

        // Retrieve and format the results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $planting_item = array(
                "id" => $id,
                "user_id" => $user_id,
                "username" => $username,
                "species_id" => $species_id,
                "species_name_reported" => $species_name_reported,
                "common_name" => $common_name,
                "co2_offset_per_year_kg" => $co2_offset_per_year_kg,
                "quantity" => $quantity,
                "planting_date" => $planting_date,
                "latitude" => $latitude,
                "longitude" => $longitude,
                "photo_before_path" => $photo_before_path,
                "photo_after_path" => $photo_after_path,
                "status" => $status,
                "verified_by_admin_id" => $verified_by_admin_id,
                "verification_notes" => $verification_notes,
                "verified_at" => $verified_at,
                "created_at" => $created_at,
                "updated_at" => $updated_at
            );

            array_push($plantings_arr["records"], $planting_item);
        }

        error_log("Found " . count($plantings_arr["records"]) . " plantings");
        http_response_code(200);
        echo json_encode($plantings_arr);
    } else {
        // No plantings found
        error_log("No plantings found");
        http_response_code(200);
        echo json_encode(array("records" => array(), "message" => "No plantings found."));
    }
} else {
    // Error reading plantings
    error_log("Error reading plantings");
    http_response_code(500);
    echo json_encode(array("message" => "Error reading plantings."));
}
?>
