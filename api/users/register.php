<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and user files
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../lib/User.php';

// Instantiate database and user object
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input data
if (!empty($data->username) &&
    !empty($data->email) && filter_var($data->email, FILTER_VALIDATE_EMAIL) &&
    !empty($data->password) && strlen($data->password) >= 6) {
    
    // Set user property values
    $user->username = trim($data->username);
    $user->email = trim($data->email);
    $user->password = $data->password;

    // Attempt to register the user
    $registration_result = $user->register();

    if ($registration_result === true) {
        http_response_code(201);
        echo json_encode(array(
            "message" => "User was registered successfully.", 
            "userId" => $user->id
        ));
    } elseif ($registration_result === false) {
        if ($user->usernameExists() || $user->emailExists()) {
            http_response_code(409);
            echo json_encode(array(
                "message" => "Unable to register user. Username or email already exists."
            ));
        } else {
            http_response_code(503);
            echo json_encode(array(
                "message" => "Unable to register user. Internal error."
            ));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "message" => "Unable to register user. Data is incomplete or invalid."
    ));
}
?>
