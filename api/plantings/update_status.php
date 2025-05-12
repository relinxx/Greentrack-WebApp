<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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
include_once __DIR__ . 
'/../../lib/User.php'
; // Include User class for role check
// Removed JWT Handler include

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate objects
$planting = new TreePlanting($db);
$user = new User($db); // Instantiate User object

// Get planting ID from query parameter
$planting->id = isset($_GET["id"]) ? filter_var($_GET["id"], FILTER_VALIDATE_INT) : null;

// Get data from request body
$data = json_decode(file_get_contents("php://input"));

// Check if ID, status, and admin_user_id are provided
if (
    !$planting->id ||
    !isset($data->status) ||
    !isset($data->admin_user_id) || !is_numeric($data->admin_user_id)
) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update status. Planting ID, new status, and admin_user_id are required."]);
    exit;
}

$adminUserId = intval($data->admin_user_id);

// Verify if the provided admin_user_id actually has admin role
if (!$user->isAdmin($adminUserId)) { // Assuming an isAdmin method exists in User class
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access denied. Provided user ID does not have admin privileges."]);
    exit();
}

// Set planting properties for update
$planting->status = $data->status;
$planting->verified_by_admin_id = $adminUserId;
$planting->verification_notes = $data->notes ?? null;

// Validate status value
$allowed_statuses = ["pending", "verified", "rejected"];
if (!in_array($planting->status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid status value. Allowed values: " . implode(", ", $allowed_statuses)]);
    exit;
}

// Update the planting status
if ($planting->updateStatus()) {
    http_response_code(200);
    echo json_encode(["message" => "Tree planting status updated."]);
} else {
    http_response_code(503); // Or 404 if planting not found
    echo json_encode(["message" => "Unable to update planting status. Planting might not exist or internal error occurred."]);
}
?>

