<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // Include database and object files
    include_once __DIR__ . '/../../config/config.php';
    include_once __DIR__ . '/../../includes/db.php';
    include_once __DIR__ . '/../../lib/Gamification.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Initialize gamification object
    $gamification = new Gamification($db);

    // Get user_id from query string
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if (!$user_id) {
        throw new Exception("User ID is required");
    }

    // Get XP
    $xp_count = $gamification->getXP($user_id);

    // Return response
    http_response_code(200);
    echo json_encode(array(
        "user_id" => (int)$user_id,
        "xp_count" => $xp_count
    ));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "error" => true,
        "message" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ));
}
?> 