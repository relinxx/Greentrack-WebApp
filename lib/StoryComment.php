<?php
class StoryComment {
    // Database connection and table name
    private $conn;
    private $table_name = "story_comments";

    // Object properties
    public $id;
    public $story_id;
    public $user_id;
    public $comment_text;
    public $created_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create comment
    public function create() {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Insert comment record
            $query = "INSERT INTO " . $this->table_name . "
                    (story_id, user_id, comment_text)
                    VALUES
                    (:story_id, :user_id, :comment_text)";

            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->story_id = htmlspecialchars(strip_tags($this->story_id));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->comment_text = htmlspecialchars(strip_tags($this->comment_text));

            // Bind values
            $stmt->bindParam(":story_id", $this->story_id);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":comment_text", $this->comment_text);

            // Execute query
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();

                // Update comments count in stories table
                $updateQuery = "UPDATE stories 
                              SET comments_count = comments_count + 1 
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
            error_log("Error creating comment: " . $e->getMessage());
            return false;
        }
    }

    // Delete comment
    public function delete() {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Get story_id before deleting
            $getStoryQuery = "SELECT story_id FROM " . $this->table_name . " WHERE id = :id";
            $getStoryStmt = $this->conn->prepare($getStoryQuery);
            $getStoryStmt->bindParam(":id", $this->id);
            $getStoryStmt->execute();
            $storyId = $getStoryStmt->fetchColumn();

            // Delete comment record
            $query = "DELETE FROM " . $this->table_name . "
                    WHERE id = :id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->id = htmlspecialchars(strip_tags($this->id));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));

            // Bind values
            $stmt->bindParam(":id", $this->id);
            $stmt->bindParam(":user_id", $this->user_id);

            // Execute query
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                // Update comments count in stories table
                $updateQuery = "UPDATE stories 
                              SET comments_count = GREATEST(comments_count - 1, 0) 
                              WHERE id = :story_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(":story_id", $storyId);
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
            error_log("Error deleting comment: " . $e->getMessage());
            return false;
        }
    }
}
?> 