<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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

// Get planting ID and admin user ID from query parameters
$planting->id = isset($_GET["id"]) ? filter_var($_GET["id"], FILTER_VALIDATE_INT) : null;
$adminUserId = isset($_GET["admin_user_id"]) ? filter_var($_GET["admin_user_id"], FILTER_VALIDATE_INT) : null;

// Check if IDs are provided and admin user has privileges
if (!$planting->id || !$adminUserId) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to delete planting. Planting ID and Admin User ID are required."]);
    exit;
}

// Verify if the provided admin_user_id actually has admin role
if (!$user->isAdmin($adminUserId)) { // Assuming an isAdmin method exists in User class
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access denied. Provided user ID does not have admin privileges."]);
    exit();
}

// Delete the planting
if ($planting->delete()) {
    http_response_code(200);
    echo json_encode(["message" => "Tree planting record deleted."]);
    // Log admin action (consider adding to AdminActionLogger class)
    // include_once __DIR__ . '/../../lib/AdminActionLogger.php';
    // $logger = new AdminActionLogger($db);
    // $logger->logAction($adminUserId, 'delete_planting', $planting->id, null, null);
} else {
    http_response_code(503); // Or 404 if not found
    echo json_encode(["message" => "Unable to delete planting record. Planting might not exist or internal error occurred."]);
}
?>

