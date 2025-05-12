<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database, report, user, and logger files
require_once __DIR__ . 
'/../../includes/db.php';
require_once __DIR__ . 
'/../../lib/Report.php';
require_once __DIR__ . 
'/../../lib/User.php'; // Needed to potentially check role if not relying on input
require_once __DIR__ . 
'/../../lib/AdminActionLogger.php'; // For logging admin actions

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

// Get report ID from query string or request body
// Prefer query string for ID in RESTful PUT/DELETE
$report->id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

// Get posted data (expecting JSON for the new status and admin user ID)
if (strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
    $data = json_decode(file_get_contents("php://input"));
} else {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid input format. JSON expected."));
    exit();
}

// Validate input: Check for admin_user_id, report_id, and status
if (
    !empty($report->id) &&
    isset($data->admin_user_id) && is_numeric($data->admin_user_id) && // Check for admin user ID
    !empty($data->status) && in_array($data->status, ['pending', 'approved', 'rejected', 'cleaned']) // Validate status value
) {
    $admin_user_id = intval($data->admin_user_id);

    // Optional: Verify if the provided admin_user_id actually has admin role in the database
    if (!$user->isAdmin($admin_user_id)) { // Assuming an isAdmin method exists in User class
        http_response_code(403); // Forbidden
        echo json_encode(array("message" => "Access denied. Provided user ID does not have admin privileges."));
        exit();
    }

    // Set report property values
    $report->status = $data->status;

    // Attempt to update the report status
    if ($report->updateStatus()) {
        // Log the admin action
        $logger->logAction($admin_user_id, 'update_report_status', $report->id, null, json_encode(['new_status' => $report->status]));

        // Set response code - 200 OK
        http_response_code(200);
        // Tell the user
        echo json_encode(array("message" => "Report status was updated."));
    } else {
        // Set response code - 503 service unavailable or 404 if report not found
        http_response_code(503); // Assuming internal error, could refine based on updateStatus return
        echo json_encode(array("message" => "Unable to update report status. Report might not exist or internal error occurred."));
    }
} else {
    // Data is incomplete or invalid
    http_response_code(400);
    echo json_encode(array("message" => "Unable to update report status. Invalid or missing Report ID, Admin User ID, or Status provided."));
}
?>

