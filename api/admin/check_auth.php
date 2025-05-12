<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

session_start();

try {
    // Check if admin is logged in
    if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        // Verify admin still exists in database
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = :id AND role = 'admin'");
        $stmt->bindParam(':id', $_SESSION['admin_id']);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            echo json_encode(['isLoggedIn' => true]);
            exit;
        }
    }
    
    // If we get here, admin is not logged in
    echo json_encode(['isLoggedIn' => false]);
    
} catch(PDOException $e) {
    error_log("Database error in check_auth.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'isLoggedIn' => false,
        'error' => 'Database error occurred'
    ]);
} catch(Exception $e) {
    error_log("General error in check_auth.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'isLoggedIn' => false,
        'error' => 'An error occurred'
    ]);
}
?> 