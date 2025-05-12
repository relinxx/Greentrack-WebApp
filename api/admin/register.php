<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Validate input
    if (!isset($data->username) || !isset($data->email) || !isset($data->password) || !isset($data->admin_key)) {
        http_response_code(400);
        echo json_encode(["message" => "All fields are required"]);
        exit;
    }

    // Verify admin key
    if ($data->admin_key !== ADMIN_REGISTRATION_KEY) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid admin registration key"]);
        exit;
    }

    // Check if username or email already exists
    $query = "SELECT id FROM users WHERE username = :username OR email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":username", $data->username);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["message" => "Username or email already exists"]);
        exit;
    }

    // Hash password
    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);

    // Insert new admin user
    $query = "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, 'admin')";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(":username", $data->username);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password_hash", $password_hash);
    
    if ($stmt->execute()) {
        echo json_encode(["message" => "Admin registration successful"]);
    } else {
        throw new Exception("Failed to register admin user");
    }
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Database error occurred"]);
} catch(Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "An error occurred"]);
}
?> 