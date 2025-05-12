<?php
class AdminActionLogger {
    private $conn;
    private $table_name = "admin_actions";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function logAction($admin_user_id, $action_type, $target_report_id = null, $target_user_id = null, $details = null) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (admin_user_id, action_type, target_report_id, target_user_id, details) 
                     VALUES 
                     (:admin_user_id, :action_type, :target_report_id, :target_user_id, :details)";

            $stmt = $this->conn->prepare($query);

            // Bind values
            $stmt->bindParam(":admin_user_id", $admin_user_id);
            $stmt->bindParam(":action_type", $action_type);
            $stmt->bindParam(":target_report_id", $target_report_id);
            $stmt->bindParam(":target_user_id", $target_user_id);
            $stmt->bindParam(":details", $details);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error logging admin action: " . $e->getMessage());
            return false;
        }
    }
}
?> 