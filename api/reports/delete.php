<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database, report, user, and logger files
require_once __DIR__ . 
'/../../includes/db.php
';
require_once __DIR__ . 
'/../../lib/Report.php
';
require_once __DIR__ . 
'/../../lib/User.php
'; // Needed to check role
require_once __DIR__ . 
'/../../lib/AdminActionLogger.php
';

// Instantiate database and objects
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("message" => "Unable to connect to database."));
    exit();
}

$report = new Report($db);
$user = new User($db); // Instantiate User object
$logger = new AdminActionLogger($db);

// Get report ID and admin user ID from query string
$report->id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$admin_user_id = isset($_GET['admin_user_id']) ? filter_var($_GET['admin_user_id'], FILTER_VALIDATE_INT) : null; // Get admin user ID

// Validate IDs and admin role
if (!empty($report->id) && !empty($admin_user_id)) {

    // Verify if the provided admin_user_id actually has admin role in the database
    if (!$user->isAdmin($admin_user_id)) { // Assuming an isAdmin method exists in User class
        http_response_code(403); // Forbidden
        echo json_encode(array("message" => "Access denied. Provided user ID does not have admin privileges."));
        exit();
    }

    // Before deleting, maybe check if report exists (optional, delete operation is idempotent)
    // if (!$report->readOne()) { ... return 404 ... }

    // Attempt to delete the report
    if ($report->delete()) {
        // Log the admin action
        $logger->logAction($admin_user_id, 'delete_report', $report->id);

        // Set response code - 200 OK (or 204 No Content)
        http_response_code(200);
        // Tell the user
        echo json_encode(array("message" => "Report was deleted."));
    } else {
        // Set response code - 503 service unavailable or 404 if not found
        http_response_code(503); // Assuming internal error, could refine based on delete return
        echo json_encode(array("message" => "Unable to delete report. Report might not exist or internal error occurred."));
    }
} else {
    // Invalid or missing ID provided
    http_response_code(400);
    echo json_encode(array("message" => "Unable to delete report. Invalid or missing Report ID or Admin User ID provided."));
}
?>

