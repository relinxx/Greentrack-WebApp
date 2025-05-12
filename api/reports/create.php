<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Start output buffering to prevent any unwanted output before JSON response
ob_start();

try {
    // Include database and report files
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../lib/Report.php';

    // Instantiate database and objects
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Could not connect to database");
    }

    $report = new Report($db);

    // Get posted data
    $data = $_POST;

    // If no POST data, try to get JSON input
    if (empty($data)) {
        $data = json_decode(file_get_contents("php://input"), true);
        }

    // Check if required data is present
    if (
        !empty($data["user_id"]) && is_numeric($data["user_id"]) &&
        !empty($data["latitude"]) && is_numeric($data["latitude"]) &&
        !empty($data["longitude"]) && is_numeric($data["longitude"])
    ) {
        // Set report property values
        $report->user_id = $data["user_id"];
        $report->latitude = $data["latitude"];
        $report->longitude = $data["longitude"];
        $report->description = $data["description"] ?? '';
        
        // Handle photo upload if present
        $upload_dir = __DIR__ . '/../../uploads/reports/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
            $filename = uniqid("report_", true) . "." . pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_dir . $filename)) {
                $report->photo_path = "/uploads/reports/" . $filename; // Relative path for web access
            }
        } else if (!empty($data["photo_data"])) {
            // Handle base64 encoded image data if provided (from mobile apps etc.)
            $photo_data = $data["photo_data"];
            $filename = uniqid("report_", true) . ".jpg"; // Assuming JPEG format
            
            // Decode and save the image
            $binary_data = base64_decode($photo_data);
            if (file_put_contents($upload_dir . $filename, $binary_data)) {
                $report->photo_path = "/uploads/reports/" . $filename;
            }
        }

        // Create the report
        if ($report->create()) {
            // Clear any output that might have been generated
            ob_end_clean();
            // Set response code - 201 created
            http_response_code(201);
            // Tell the user
            echo json_encode(["message" => "Report created successfully.", "report_id" => $report->id]);
        } else {
            throw new Exception("Failed to create report");
        }
    } else {
        throw new Exception("Missing required data: user_id, latitude, and longitude are required");
    }
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_end_clean();
    // Set response code
    http_response_code(500);
    // Return error message
    echo json_encode(array(
        "message" => "Error: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ));
}
?>

