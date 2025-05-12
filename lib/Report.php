<?php
class Report {
    private $conn;
    private $table_name = "reports";

    // Object properties
    public $id;
    public $user_id;
    public $latitude;
    public $longitude;
    public $description;
    public $photo_path;
    public $status;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create report
    public function create() {
        try {
            // Insert query
            $query = "INSERT INTO " . $this->table_name . " 
                    (user_id, latitude, longitude, description, photo_path, status) 
                    VALUES (:user_id, :latitude, :longitude, :description, :photo_path, :status)";

            // Prepare the query
            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->latitude = htmlspecialchars(strip_tags($this->latitude));
            $this->longitude = htmlspecialchars(strip_tags($this->longitude));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->photo_path = $this->photo_path ? htmlspecialchars(strip_tags($this->photo_path)) : null;
            $this->status = "pending";

            // Bind the values
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":latitude", $this->latitude);
            $stmt->bindParam(":longitude", $this->longitude);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":photo_path", $this->photo_path);
            $stmt->bindParam(":status", $this->status);

            // Execute the query
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Add experience points to user
                $this->addXpToUser($this->user_id, 10); // Award 10 XP for submitting a report
                
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error creating report: " . $e->getMessage());
            return false;
        }
    }
    
    // Add XP to user
    private function addXpToUser($userId, $xpAmount) {
        try {
            error_log("Adding $xpAmount XP to user ID: $userId");
            
            $query = "UPDATE user_xp SET xp_count = xp_count + :xp WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":xp", $xpAmount);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $rowsAffected = $stmt->rowCount();
            error_log("UPDATE query affected $rowsAffected rows");
            
            // If no user_xp entry exists, create one
            if ($rowsAffected == 0) {
                error_log("No existing XP record found, creating new entry");
                $query = "INSERT INTO user_xp (user_id, xp_count) VALUES (:user_id, :xp)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":user_id", $userId);
                $stmt->bindParam(":xp", $xpAmount);
                $result = $stmt->execute();
                error_log("INSERT query result: " . ($result ? "success" : "failed"));
            }
            
            // Verify the current XP for the user after update
            $verifyQuery = "SELECT xp_count FROM user_xp WHERE user_id = :user_id";
            $verifyStmt = $this->conn->prepare($verifyQuery);
            $verifyStmt->bindParam(":user_id", $userId);
            $verifyStmt->execute();
            $row = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                error_log("User $userId now has {$row['xp_count']} XP");
            } else {
                error_log("Failed to verify XP for user $userId");
            }
        } catch (PDOException $e) {
            error_log("Error adding XP to user: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
        }
    }

    // Read reports with optional filters
    public function read($userId = null, $status = null, $limit = null, $offset = 0, $order = 'asc', $bbox = null) {
        try {
            $query = "SELECT r.*, u.username 
                    FROM " . $this->table_name . " r
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE 1=1";
            
            // Add filters
            if ($userId) {
                $query .= " AND r.user_id = :user_id";
            }
            
            if ($status) {
                $query .= " AND r.status = :status";
            }
            
            if ($bbox) {
                // Parse bbox: west,south,east,north
                $bboxParts = explode(',', $bbox);
                if (count($bboxParts) == 4) {
                    $query .= " AND r.longitude >= :west AND r.longitude <= :east 
                              AND r.latitude >= :south AND r.latitude <= :north";
                }
            }
            
            // Add order and limit
            $query .= " ORDER BY r.created_at " . ($order == 'desc' ? 'DESC' : 'ASC');
            
            if ($limit) {
                $query .= " LIMIT :offset, :limit";
            }
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            if ($userId) {
                $stmt->bindParam(":user_id", $userId);
            }
            
            if ($status) {
                $stmt->bindParam(":status", $status);
            }
            
            if ($bbox && count($bboxParts) == 4) {
                $stmt->bindParam(":west", $bboxParts[0]);
                $stmt->bindParam(":south", $bboxParts[1]);
                $stmt->bindParam(":east", $bboxParts[2]);
                $stmt->bindParam(":north", $bboxParts[3]);
            }
            
            if ($limit) {
                $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
                $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            }
            
            // Execute the query
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error reading reports: " . $e->getMessage());
            return null;
        }
    }

    // Read a single report
    public function readOne() {
        try {
            $query = "SELECT r.*, u.username 
                    FROM " . $this->table_name . " r
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.id = :id
                    LIMIT 1";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind ID
            $stmt->bindParam(":id", $this->id);
            
            // Execute the query
            $stmt->execute();
            
            // Fetch the row
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                // Set properties
                $this->id = $row['id'];
                $this->user_id = $row['user_id'];
                $this->latitude = $row['latitude'];
                $this->longitude = $row['longitude'];
                $this->description = $row['description'];
                $this->photo_path = $row['photo_path'];
                $this->status = $row['status'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                $this->username = $row['username'];
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error reading report: " . $e->getMessage());
            return false;
        }
    }

    // Update report status
    public function updateStatus($status) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                    SET status = :status, updated_at = NOW()
                    WHERE id = :id";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Sanitize and bind values
            $this->id = htmlspecialchars(strip_tags($this->id));
            $status = htmlspecialchars(strip_tags($status));
            
            $stmt->bindParam(":id", $this->id);
            $stmt->bindParam(":status", $status);
            
            // Execute the query
            if ($stmt->execute()) {
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error updating report status: " . $e->getMessage());
            return false;
        }
    }

    // Delete report
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Sanitize and bind value
            $this->id = htmlspecialchars(strip_tags($this->id));
            $stmt->bindParam(":id", $this->id);
            
            // Execute the query
            if ($stmt->execute()) {
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error deleting report: " . $e->getMessage());
            return false;
        }
    }
}
?> 