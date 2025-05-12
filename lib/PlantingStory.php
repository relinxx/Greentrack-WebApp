<?php
class PlantingStory {
    // Database connection and table name
    private $conn;
    private $table_name = "stories";

    // Object properties
    public $id;
    public $user_id;
    public $username;
    public $title;
    public $content;
    public $photo_path;
    public $category;
    public $likes_count;
    public $comments_count;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read one story
    public function readOne() {
        // Query to read single record
        $query = "SELECT s.*, u.username
                 FROM " . $this->table_name . " s
                 LEFT JOIN users u ON s.user_id = u.id
                 WHERE s.id = ?
                 LIMIT 0,1";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind ID of story to be read
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Set values to object properties
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->username = $row['username'];
            $this->title = $row['title'];
            $this->content = $row['content'];
            $this->photo_path = $row['photo_path'];
            $this->category = $row['category'];
            $this->likes_count = $row['likes_count'];
            $this->comments_count = $row['comments_count'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Create story
    public function create() {
        try {
            // Query to insert record
            $query = "INSERT INTO " . $this->table_name . "
                    (user_id, title, content, category)
                    VALUES
                    (:user_id, :title, :content, :category)";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->content = htmlspecialchars(strip_tags($this->content));
            $this->category = htmlspecialchars(strip_tags($this->category));

            // Bind values
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":content", $this->content);
            $stmt->bindParam(":category", $this->category);

            // Execute query
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error creating story: " . $e->getMessage());
            return false;
        }
    }

    // Update story
    public function update() {
        try {
            // Query to update record
            $query = "UPDATE " . $this->table_name . "
                    SET title = :title,
                        content = :content,
                        category = :category,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->content = htmlspecialchars(strip_tags($this->content));
            $this->category = htmlspecialchars(strip_tags($this->category));
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind values
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":content", $this->content);
            $stmt->bindParam(":category", $this->category);
            $stmt->bindParam(":id", $this->id);

            // Execute query
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating story: " . $e->getMessage());
            return false;
        }
    }

    // Delete story
    public function delete() {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Delete related records first
            $stmt = $this->conn->prepare("DELETE FROM story_likes WHERE story_id = ?");
            $stmt->execute([$this->id]);

            $stmt = $this->conn->prepare("DELETE FROM story_comments WHERE story_id = ?");
            $stmt->execute([$this->id]);

            // Delete the story
            $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id = ?");
            $stmt->execute([$this->id]);

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            error_log("Error deleting story: " . $e->getMessage());
            return false;
        }
    }
}
?> 