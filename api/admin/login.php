<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['password'])) {
        throw new Exception('Missing required fields');
    }

    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get admin by username
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = :username AND role = 'admin'");
    $stmt->bindParam(':username', $input['username']);
    $stmt->execute();
    
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin || !password_verify($input['password'], $admin['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        exit;
    }

    // Set session variables
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_logged_in'] = true;
    
    echo json_encode(['message' => 'Login successful']);
} catch(PDOException $e) {
    error_log("Database error in login.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("General error in login.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred',
        'details' => $e->getMessage()
    ]);
}
?> 