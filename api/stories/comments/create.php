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
include_once __DIR__ . '/../../../lib/StoryComment.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (
    !empty($data->user_id) &&
    !empty($data->story_id) &&
    !empty($data->comment_text)
) {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Instantiate comment object
    $comment = new StoryComment($db);

    // Set properties
    $comment->user_id = $data->user_id;
    $comment->story_id = $data->story_id;
    $comment->comment_text = $data->comment_text;

    // Create the comment
    if ($comment->create()) {
        http_response_code(201);
        echo json_encode(["message" => "Comment created.", "commentId" => $comment->id]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to create comment."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "user_id, story_id, and comment_text are required."]);
}
?>

