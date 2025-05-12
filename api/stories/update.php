<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../lib/PlantingStory.php';

// Log request method and raw input
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
$rawInput = file_get_contents("php://input");
error_log("Raw input: " . $rawInput);

// Get JSON input
$data = json_decode($rawInput);

// Log decoded data
error_log("Decoded data: " . print_r($data, true));

// Validate required parameters
if (!isset($data->id) || !isset($data->userId) || !isset($data->userRole)) {
    error_log("Missing required parameters. Data received: " . print_r($data, true));
    http_response_code(400);
    echo json_encode([
        "message" => "Missing required parameters.",
        "debug" => [
            "received_data" => $data,
            "missing_fields" => [
                "id" => !isset($data->id),
                "userId" => !isset($data->userId),
                "userRole" => !isset($data->userRole)
            ]
        ]
    ]);
    exit;
}

$storyId = $data->id;
$userId = $data->userId;
$userRole = strtolower($data->userRole); // Convert to lowercase for case-insensitive comparison

// Debug logging
error_log("Story ID: " . $storyId);
error_log("User ID: " . $userId);
error_log("User Role: " . $userRole);

try {
    $db = new Database();
    $conn = $db->getConnection();

    // First, get the existing story to check ownership
    $stmt = $conn->prepare("SELECT * FROM planting_stories WHERE id = ?");
    $stmt->execute([$storyId]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$story) {
        error_log("Story not found with ID: " . $storyId);
        http_response_code(404);
        echo json_encode([
            "message" => "Story not found.",
            "debug" => ["storyId" => $storyId]
        ]);
        exit();
    }

    // Log the story data
    error_log("Story data: " . json_encode($story));

    // Check if user is authorized to edit
    $isOwner = (string)$story['user_id'] === (string)$userId;
    $isAdmin = $userRole === 'admin';

    error_log("Is owner: " . ($isOwner ? 'true' : 'false'));
    error_log("Is admin: " . ($isAdmin ? 'true' : 'false'));

    if (!$isOwner && !$isAdmin) {
        error_log("Access denied. User ID: " . $userId . ", Role: " . $userRole . ", Story Owner: " . $story['user_id']);
        http_response_code(403);
        echo json_encode([
            "message" => "Access denied",
            "debug" => [
                "userId" => $userId,
                "userRole" => $userRole,
                "storyOwner" => $story['user_id'],
                "isOwner" => $isOwner,
                "isAdmin" => $isAdmin
            ]
        ]);
        exit();
    }

    // If user is admin but not owner, only allow updating status
    if ($isAdmin && !$isOwner) {
        if (isset($data->title) || isset($data->content)) {
            error_log("Admin attempted to modify content/title of another user's story");
            http_response_code(403);
            echo json_encode(["message" => "Admins can only update status of others' stories"]);
            exit();
        }
    }

    // Build update query based on provided fields
    $updateFields = [];
    $params = [];

    if (isset($data->title)) {
        $updateFields[] = "title = ?";
        $params[] = $data->title;
    }
    if (isset($data->content)) {
        $updateFields[] = "content = ?";
        $params[] = $data->content;
    }
    // Handle planting_id separately since it can be null
    $updateFields[] = "planting_id = ?";
    $params[] = $data->planting_id === "" ? null : $data->planting_id;

    if (empty($updateFields)) {
        error_log("No fields to update provided");
        http_response_code(400);
        echo json_encode(["message" => "No fields to update provided"]);
        exit();
    }

    // Add story ID to params
    $params[] = $storyId;

    // Update the story
    $sql = "UPDATE planting_stories SET " . implode(", ", $updateFields) . " WHERE id = ?";
    error_log("Update SQL: " . $sql);
    error_log("Update params: " . json_encode($params));

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        error_log("Story updated successfully");
        echo json_encode(["message" => "Story updated successfully"]);
    } else {
        error_log("Failed to update story: " . json_encode($stmt->errorInfo()));
        http_response_code(500);
        echo json_encode(["message" => "Failed to update story"]);
    }

} catch (Exception $e) {
    error_log("Exception in update.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Server error: " . $e->getMessage()]);
}
?>

