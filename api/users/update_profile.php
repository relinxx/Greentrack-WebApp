<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT"); // Changed to PUT
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and user files
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../lib/User.php';

// Instantiate database and objects
$database = new Database();
$db = $database->getConnection();

$user = new User($db);

// Get posted data (expecting JSON)
if (strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
    $data = json_decode(file_get_contents("php://input"));
} else {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid input format. JSON expected."));
    exit();
}

// Get user ID from request data - REPLACED JWT VALIDATION
if (isset($data->user_id) && is_numeric($data->user_id)) {
    $user->id = intval($data->user_id);

    // Validate input data (Example: updating email)
    // Add more fields to update as needed (e.g., username, password - requires current password verification)
    if (!empty($data->email) && filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        // Set user property values to update
        $user->email = trim($data->email);
        // Add other fields here, e.g., $user->username = trim($data->username);

        // Attempt to update the profile
        if ($user->updateProfile()) {
            // Set response code - 200 OK
            http_response_code(200);
            // Tell the user
            echo json_encode(array("message" => "User profile was updated."));
        } else {
            // Check if update failed due to email conflict
            // (The updateProfile method handles the check, returns false if email exists for another user)
            // You might want more specific error handling based on the cause of failure
            http_response_code(409); // Conflict (e.g., email already taken)
            echo json_encode(array("message" => "Unable to update profile. Email might already be in use by another account or user not found.")); // Updated error message
        }
    } else {
        // Data is incomplete or invalid for update
        http_response_code(400);
        echo json_encode(array("message" => "Unable to update profile. Data is incomplete or invalid (e.g., invalid email format)."));
    }

} else {
    // Invalid or missing user ID
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Access denied. Missing or invalid user ID.")); // UPDATED ERROR MESSAGE
}
?>

