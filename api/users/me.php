<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../lib/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize user object
$user = new User($db);

// Get user ID from query string
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    
    try {
        // Set the user ID property of the user object
        $user->id = $user_id;
        
        // Get user details
        if ($user->readOne()) {
            // Create array
            $user_arr = array(
            "id" => $user->id,
            "username" => $user->username,
            "email" => $user->email,
            "role" => $user->role,
                "created_at" => $user->created_at,
                "updated_at" => $user->updated_at
        );

        // Set response code - 200 OK
        http_response_code(200);
            
            // Make it json format
            echo json_encode($user_arr);
    } else {
            // No user found
        http_response_code(404);
        echo json_encode(array("message" => "User not found."));
        }
    } catch (Exception $e) {
        // Error retrieving user
        http_response_code(500);
        echo json_encode(array("message" => "Error retrieving user. " . $e->getMessage()));
    }
} else {
    // Missing or invalid user_id
    http_response_code(400);
    echo json_encode(array("message" => "Missing or invalid user_id parameter."));
}
?>

