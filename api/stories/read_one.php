<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../lib/PlantingStory.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate story object
$story = new PlantingStory($db);

// Get ID from query parameter
$story->id = isset($_GET["id"]) ? $_GET["id"] : die();

// Read the details of story to be fetched
if ($story->readOne()) {
    // Create array
    $story_item = [
        "id" => $story->id,
        "user_id" => $story->user_id,
        "username" => $story->username,
        "title" => $story->title,
        "content" => $story->content,
        "photo_path" => $story->photo_path,
        "category" => $story->category,
        "likes_count" => (int)$story->likes_count,
        "comments_count" => (int)$story->comments_count,
        "created_at" => $story->created_at,
        "updated_at" => $story->updated_at
    ];

    http_response_code(200);
    echo json_encode($story_item);
} else {
    http_response_code(404);
    echo json_encode(["message" => "Story not found."]);
}
?>
