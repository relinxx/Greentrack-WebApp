<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and user files
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../lib/User.php';

// Instantiate database and user object
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

// Validate input data
if (
    !empty($data->email) && filter_var($data->email, FILTER_VALIDATE_EMAIL) &&
    !empty($data->password)
) {
    // Set user property values
    $user->email = trim($data->email);
    $user->password = $data->password;

    // Attempt to login
    if ($user->login()) {
        // Start session and set session variables
        setUserSession([
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'email' => $user->email
        ]);

        // Set response code - 200 OK
        http_response_code(200);
        // Return user details
        echo json_encode(
            array(
                "message" => "Login successful.",
                "userId" => $user->id,
                "username" => $user->username,
                "role" => $user->role
            )
        );
    } else {
        // Login failed
        // Set response code - 401 Unauthorized
        http_response_code(401);
        // Tell the user
        echo json_encode(array("message" => "Login failed. Invalid email or password."));
    }
} else {
    // Data is incomplete or invalid
    // Set response code - 400 bad request
    http_response_code(400);
    // Tell the user
    echo json_encode(array("message" => "Unable to login. Email and password are required."));
}
?>

