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
include_once __DIR__ . '/../../../lib/StoryComment.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate comment object
$comment = new StoryComment($db);

// Get comment ID from query parameter
$comment->id = isset($_GET["id"]) ? $_GET["id"] : null;

// Check if ID is provided
if (!$comment->id) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to delete comment. Comment ID is required."]);
    exit;
}

// --- Authorization Check ---
// Get the original comment details to check ownership
$checkQuery = "SELECT user_id FROM story_comments WHERE id = :id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(":id", $comment->id);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(["message" => "Comment not found."]);
    exit;
}

$commentUserId = $checkStmt->fetchColumn();

// Check ownership of the comment
$isOwner = ($commentUserId == $_GET['user_id']); // Assuming user_id is passed in the query parameters or via another method
$isAdmin = ($_GET['role'] === 'admin'); // Assuming role is passed in the query parameters

// Only owner or admin can delete
if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. You can only delete your own comments or if you are an admin."]);
    exit;
}

// Set user_id for the comment object
$comment->user_id = $_GET['user_id'];

// Delete the comment
if ($comment->delete()) {
    http_response_code(200);
    echo json_encode(["message" => "Comment deleted."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to delete comment."]);
}
?>

