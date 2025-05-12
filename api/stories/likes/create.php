<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../../../config/config.php';
include_once __DIR__ . '/../../../includes/db.php';
include_once __DIR__ . '/../../../lib/StoryLike.php';

// Get user_id from query param
$userId = $_GET["user_id"] ?? null;

// Check for user_id
if (!$userId) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required user_id parameter."]);
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate like object
$like = new StoryLike($db);

// Get posted data (expecting story_id in the body)
$rawData = file_get_contents("php://input");
error_log("Raw request body: " . $rawData);
$data = json_decode($rawData);
error_log("Decoded data: " . print_r($data, true));

// Check if required data is present
if (!empty($data->story_id)) {
    $like->story_id = $data->story_id;
    $like->user_id = $userId;

    // Create the like
    if ($like->create()) {
        http_response_code(201);
        echo json_encode(["message" => "Story liked."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to like story."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Story ID is required."]);
}
?>

