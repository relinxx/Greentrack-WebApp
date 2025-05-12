<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include session management
require_once __DIR__ . '/../../includes/session.php';

// Clear the session
clearUserSession();

// Return success response
http_response_code(200);
echo json_encode(array("message" => "Successfully logged out."));
?> 