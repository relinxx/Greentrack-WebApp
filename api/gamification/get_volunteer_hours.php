<?php
// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../lib/Gamification.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize gamification object
$gamification = new Gamification($db);

// Get user_id from query string
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

// Get volunteer hours
$total_hours = $gamification->getVolunteerHours($user_id);

// Return response
http_response_code(200);
echo json_encode(array(
    "total_hours" => $total_hours
));
?>
