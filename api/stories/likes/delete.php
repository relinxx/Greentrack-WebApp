<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../../../config/config.php';
include_once __DIR__ . '/../../../includes/db.php';
include_once __DIR__ . '/../../../lib/StoryLike.php';

// Get user_id and story_id from query params
$userId = $_GET["user_id"] ?? null;
$storyId = $_GET["story_id"] ?? null;

// Validate input
if (!$userId || !$storyId) {
    http_response_code(400);
    echo json_encode(["message" => "user_id and story_id are required."]);
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate like object
$like = new StoryLike($db);
$like->user_id = $userId;
$like->story_id = $storyId;

// Attempt to delete the like
if ($like->delete()) {
    http_response_code(200);
    echo json_encode(["message" => "Story unliked."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to unlike story (or it wasn't liked)."]);
}
?>

