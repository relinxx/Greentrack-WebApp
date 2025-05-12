<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/middleware.php';

try {
    // Check admin authentication
    checkAdminAuth();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['report_id']) || !isset($input['action'])) {
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
        // Update report status
        $stmt = $conn->prepare("
            UPDATE reports 
            SET status = :status 
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $input['action'] === 'approve' ? 'approved' : 'rejected',
            ':id' => $input['report_id']
        ]);
        
        // If report is rejected, deduct 10 XP from the user
        if ($input['action'] === 'reject') {
            // Get the user_id for this report
            $stmt = $conn->prepare("SELECT user_id FROM reports WHERE id = :id");
            $stmt->execute([':id' => $input['report_id']]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report) {
                // Deduct 10 XP
                $stmt = $conn->prepare("
                    UPDATE user_xp 
                    SET xp_count = GREATEST(0, xp_count - 10) 
                    WHERE user_id = :user_id
                ");
                $stmt->execute([':user_id' => $report['user_id']]);
            }
        }
        
        // Log the action
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_user_id, action_type, target_report_id, details)
            VALUES (:admin_user_id, :action_type, :target_report_id, :details)
        ");
        $stmt->execute([
            ':admin_user_id' => $_SESSION['admin_id'],
            ':action_type' => $input['action'] . '_report',
            ':target_report_id' => $input['report_id'],
            ':details' => json_encode(['status' => $input['action'] === 'approve' ? 'approved' : 'rejected'])
        ]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['message' => 'Report ' . $input['action'] . 'd successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch(PDOException $e) {
    error_log("Database error in handle_report.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("General error in handle_report.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred',
        'details' => $e->getMessage()
    ]);
}
?> 