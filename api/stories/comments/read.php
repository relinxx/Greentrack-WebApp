<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// Include database and object files
include_once __DIR__ . '/../../../config/config.php';
include_once __DIR__ . '/../../../includes/db.php';
include_once __DIR__ . '/../../../lib/StoryComment.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Get story ID from query parameter
    $storyId = isset($_GET["story_id"]) ? $_GET["story_id"] : die();

    // Query to get comments with username
    $query = "SELECT c.*, u.username 
              FROM story_comments c
              LEFT JOIN users u ON c.user_id = u.id
              WHERE c.story_id = :story_id
              ORDER BY c.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":story_id", $storyId);
    $stmt->execute();

    $comments_arr = [];
    $comments_arr["records"] = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $comment_item = [
            "id" => $row['id'],
            "story_id" => $row['story_id'],
            "user_id" => $row['user_id'],
            "username" => $row['username'],
            "comment_text" => $row['comment_text'],
            "created_at" => $row['created_at']
        ];

        array_push($comments_arr["records"], $comment_item);
    }

    http_response_code(200);
    echo json_encode($comments_arr);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Error retrieving comments",
        "error" => $e->getMessage()
    ]);
}
?>
