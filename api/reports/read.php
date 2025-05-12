<?php
// Headers
header("Access-Control-Allow-Origin: *"); // Allow public access to reports
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../lib/Report.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("message" => "Unable to connect to database."));
    exit();
}

// Initialize report object
$report = new Report($db);

// Get query parameters
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'desc' : 'asc';
$bbox = isset($_GET['bbox']) ? $_GET['bbox'] : null;

// Read reports
$stmt = $report->read($user_id, $status, $limit, $offset, $order, $bbox);

// Check if any reports found
if ($stmt) {
    $num = $stmt->rowCount();

if ($num > 0) {
    // Reports array
    $reports_arr = array();
    $reports_arr["records"] = array();

        // Retrieve and format the results
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $report_item = array(
            "id" => $id,
            "user_id" => $user_id,
                "username" => $username,
            "latitude" => $latitude,
            "longitude" => $longitude,
            "description" => $description,
            "photo_path" => $photo_path,
            "status" => $status,
                "created_at" => $created_at,
                "updated_at" => $updated_at
        );

        array_push($reports_arr["records"], $report_item);
    }

    http_response_code(200);
    echo json_encode($reports_arr);
} else {
        // No reports found
    http_response_code(200);
    echo json_encode(array("records" => array(), "message" => "No reports found."));
    }
} else {
    // Error reading reports
    http_response_code(500);
    echo json_encode(array("message" => "Error reading reports."));
}
?>

