<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../lib/PlantingStory.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if required data is present
if (
    !empty($data->title) &&
    !empty($data->content) &&
    !empty($data->user_id)
) {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Instantiate story object
    $story = new PlantingStory($db);

    // Set story property values
    $story->user_id = intval($data->user_id);
    $story->title = $data->title;
    $story->content = $data->content;
    $story->planting_id = $data->planting_id ?? null; // Optional link

    // Create the story
    if ($story->create()) {
        http_response_code(201);
        echo json_encode(["message" => "Planting story created.", "storyId" => $story->id]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to create story."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create story. Title, content, and user_id are required."]);
}
?>

