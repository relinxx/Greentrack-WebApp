<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/middleware.php';

try {
    // Check admin authentication
    checkAdminAuth();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['planting_id']) || !isset($input['action'])) {
        throw new Exception('Missing required fields');
    }
    
    if (!in_array($input['action'], ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }
    
    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Update planting status
        $stmt = $conn->prepare("
            UPDATE tree_plantings 
            SET status = :status 
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $input['action'] === 'approve' ? 'verified' : 'rejected',
            ':id' => $input['planting_id']
        ]);
        
        // If planting is rejected, deduct 15 XP from the user
        if ($input['action'] === 'reject') {
            // Get the user_id for this planting
            $stmt = $conn->prepare("SELECT user_id FROM tree_plantings WHERE id = :id");
            $stmt->execute([':id' => $input['planting_id']]);
            $planting = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($planting) {
                // Deduct 15 XP
                $stmt = $conn->prepare("
                    UPDATE user_xp 
                    SET xp_count = GREATEST(0, xp_count - 15) 
                    WHERE user_id = :user_id
                ");
                $stmt->execute([':user_id' => $planting['user_id']]);
            }
        }
        
        // Log the action
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_user_id, action_type, target_planting_id, details)
            VALUES (:admin_user_id, :action_type, :target_planting_id, :details)
        ");
        $stmt->execute([
            ':admin_user_id' => $_SESSION['admin_id'],
            ':action_type' => $input['action'] . '_planting',
            ':target_planting_id' => $input['planting_id'],
            ':details' => json_encode(['status' => $input['action'] === 'approve' ? 'verified' : 'rejected'])
        ]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['message' => 'Planting ' . $input['action'] . 'd successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch(PDOException $e) {
    error_log("Database error in handle_planting.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("General error in handle_planting.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred',
        'details' => $e->getMessage()
    ]);
}
?> 