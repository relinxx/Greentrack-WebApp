<?php
class StoryLike {
    // Database connection and table name
    private $conn;
    private $table_name = "story_likes";

    // Object properties
    public $id;
    public $story_id;
    public $user_id;
    public $created_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create like
    public function create() {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Check if like already exists
            $checkQuery = "SELECT id FROM " . $this->table_name . "
                          WHERE story_id = :story_id AND user_id = :user_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":story_id", $this->story_id);
            $checkStmt->bindParam(":user_id", $this->user_id);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                // Like already exists
                $this->conn->rollBack();
                return false;
            }

            // Insert like record
            $query = "INSERT INTO " . $this->table_name . "
                    (story_id, user_id)
                    VALUES
                    (:story_id, :user_id)";

            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->story_id = htmlspecialchars(strip_tags($this->story_id));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));

            // Bind values
            $stmt->bindParam(":story_id", $this->story_id);
            $stmt->bindParam(":user_id", $this->user_id);

            // Execute query
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();

                // Update likes count in stories table
                $updateQuery = "UPDATE stories 
                              SET likes_count = likes_count + 1 
                              WHERE id = :story_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(":story_id", $this->story_id);
                $updateStmt->execute();

                // Commit transaction
                $this->conn->commit();
                return true;
            }

            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            error_log("Error creating like: " . $e->getMessage());
            return false;
        }
    }

    // Delete like
    public function delete() {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Delete like record
            $query = "DELETE FROM " . $this->table_name . "
                    WHERE story_id = :story_id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->story_id = htmlspecialchars(strip_tags($this->story_id));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));

            // Bind values
            $stmt->bindParam(":story_id", $this->story_id);
            $stmt->bindParam(":user_id", $this->user_id);

            // Execute query
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                // Update likes count in stories table
                $updateQuery = "UPDATE stories 
                              SET likes_count = GREATEST(likes_count - 1, 0) 
                              WHERE id = :story_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(":story_id", $this->story_id);
                $updateStmt->execute();

                // Commit transaction
                $this->conn->commit();
                return true;
            }

            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            error_log("Error deleting like: " . $e->getMessage());
            return false;
        }
    }
}
?> 