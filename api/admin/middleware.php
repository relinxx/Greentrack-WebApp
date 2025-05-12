<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

function checkAdminAuth($conn = null) {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    try {
        // Use existing connection or create new one
        if ($conn === null) {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        // Verify admin still exists in database
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = :id AND role = 'admin'");
        $stmt->bindParam(':id', $_SESSION['admin_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // Admin no longer exists, clear session
            session_destroy();
            http_response_code(401);
            echo json_encode(['error' => 'Admin account not found']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Error in middleware.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error occurred',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

// Set headers for all admin API responses
header('Content-Type: application/json');
?> 