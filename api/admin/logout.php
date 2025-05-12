<?php
session_start();
header('Content-Type: application/json');

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Send success response
echo json_encode(["message" => "Logged out successfully"]);
?> 