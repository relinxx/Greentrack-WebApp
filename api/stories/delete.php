<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../lib/PlantingStory.php';

// Get story ID and user info from request
$storyId = $_GET["id"] ?? null;
$userId = $_GET["user_id"] ?? null;
$userRole = $_GET["role"] ?? null;

if (!$storyId || !$userId || !$userRole) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required parameters: story ID, user_id, and role."]);
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate story object
$story = new PlantingStory($db);
$story->id = $storyId;

// --- Authorization Check ---
if (!$story->readOne()) {
    http_response_code(404);
    echo json_encode(["message" => "Story not found."]);
    exit;
}

$isOwner = ($story->user_id == $userId);
$isAdmin = ($userRole === 'admin');

// Only owner or admin can delete
if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. You can only delete your own stories or if you are an admin."]);
    exit;
}

// Delete the story
if ($story->delete()) {
    http_response_code(200);
    echo json_encode(["message" => "Planting story deleted."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to delete story."]);
}
?>

