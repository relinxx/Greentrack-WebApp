<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database and object files
include_once __DIR__ . '/../../../config/config.php';
include_once __DIR__ . '/../../../includes/db.php';

try {
    // Get required data
    $storyId = $_GET["story_id"] ?? null;
    $userId = $_GET["user_id"] ?? null;

    // Validate input
    if (!$storyId || !$userId) {
        throw new Exception("Missing required parameters.");
    }

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Check if the user has liked the story
    $query = "SELECT COUNT(*) as count FROM story_likes WHERE story_id = :story_id AND user_id = :user_id";
    $stmt = $db->prepare($query);

    // Sanitize and bind values
    $storyId = htmlspecialchars(strip_tags($storyId));
    $userId = htmlspecialchars(strip_tags($userId));

    $stmt->bindParam(":story_id", $storyId);
    $stmt->bindParam(":user_id", $userId);

    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return the result
    http_response_code(200);
    echo json_encode([
        "isLiked" => $row["count"] > 0
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "message" => $e->getMessage()
    ]);
}
?> 