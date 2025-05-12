<?php
// Prevent any output before headers
ob_start();

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
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
    // Include required files
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../lib/Report.php';

    // Clear any output that might have been generated
    ob_clean();

    // Instantiate database and report object
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        sendJsonResponse(array("message" => "Unable to connect to database."), 503);
    }

    $report = new Report($db);

    // Get and validate ID
    if (!isset($_GET['id'])) {
        sendJsonResponse(array("message" => "Report ID not specified."), 400);
    }

    $report->id = $_GET['id'];

    if (!filter_var($report->id, FILTER_VALIDATE_INT)) {
        sendJsonResponse(array("message" => "Invalid Report ID."), 400);
    }

    // Read the report
    if ($report->readOne()) {
        $report_item = array(
            "id" => $report->id,
            "user_id" => $report->user_id,
            "username" => $report->username,
            "latitude" => $report->latitude,
            "longitude" => $report->longitude,
            "description" => $report->description,
            "photo_path" => $report->photo_path,
            "status" => $report->status,
            "created_at" => $report->created_at,
            "updated_at" => $report->updated_at
        );

        sendJsonResponse($report_item);
    } else {
        sendJsonResponse(array("message" => "Report not found."), 404);
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in read_one.php: " . $e->getMessage());
    sendJsonResponse(array(
        "message" => "An error occurred while processing your request.",
        "error" => $e->getMessage()
    ), 500);
}

// End output buffering
ob_end_flush();
?>
