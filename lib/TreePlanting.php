<?php
class TreePlanting {
    private $conn;
    private $table_name = "tree_plantings";

    // Object properties
    public $id;
    public $user_id;
    public $species_id;
    public $species_name_reported;
    public $quantity;
    public $planting_date;
    public $latitude;
    public $longitude;
    public $photo_before_path;
    public $photo_after_path;
    public $status;
    public $verified_by_admin_id;
    public $verification_notes;
    public $verified_at;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create planting record
    public function create() {
        try {
            // Insert query
            $query = "INSERT INTO " . $this->table_name . " 
                    (user_id, species_id, species_name_reported, quantity, planting_date, 
                    latitude, longitude, photo_before_path, photo_after_path, status) 
                    VALUES (:user_id, :species_id, :species_name_reported, :quantity, :planting_date, 
                    :latitude, :longitude, :photo_before_path, :photo_after_path, :status)";

            // Prepare the query
            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->species_name_reported = htmlspecialchars(strip_tags($this->species_name_reported));
            $this->quantity = htmlspecialchars(strip_tags($this->quantity));
            $this->planting_date = htmlspecialchars(strip_tags($this->planting_date));
            $this->latitude = htmlspecialchars(strip_tags($this->latitude));
            $this->longitude = htmlspecialchars(strip_tags($this->longitude));
            
            // Set status to pending
            $this->status = "pending";

            // Bind the values
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":species_id", $this->species_id);
            $stmt->bindParam(":species_name_reported", $this->species_name_reported);
            $stmt->bindParam(":quantity", $this->quantity);
            $stmt->bindParam(":planting_date", $this->planting_date);
            $stmt->bindParam(":latitude", $this->latitude);
            $stmt->bindParam(":longitude", $this->longitude);
            $stmt->bindParam(":photo_before_path", $this->photo_before_path);
            $stmt->bindParam(":photo_after_path", $this->photo_after_path);
            $stmt->bindParam(":status", $this->status);

            // Execute the query
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Add experience points to user
                $this->addXpToUser($this->user_id, 15 * $this->quantity); // Award XP based on trees planted
                
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error creating planting: " . $e->getMessage());
            return false;
        }
    }
    
    // Add XP to user
    private function addXpToUser($userId, $xpAmount) {
        try {
            error_log("Adding $xpAmount XP to user ID: $userId (Tree Planting)");
            
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
                error_log("User $userId now has {$row['xp_count']} XP (after tree planting)");
            } else {
                error_log("Failed to verify XP for user $userId");
            }
        } catch (PDOException $e) {
            error_log("Error adding XP to user: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
        }
    }

    // Read plantings with optional filters
    public function read($userId = null, $status = null, $limit = null, $offset = 0, $order = 'desc', $bbox = null) {
        try {
            $query = "SELECT p.*, u.username, ts.common_name, ts.co2_offset_per_year_kg
                    FROM " . $this->table_name . " p
                    LEFT JOIN users u ON p.user_id = u.id
                    LEFT JOIN tree_species ts ON p.species_id = ts.id
                    WHERE 1=1";
            
            // Add filters
            if ($userId) {
                $query .= " AND p.user_id = :user_id";
            }
            
            if ($status) {
                $query .= " AND p.status = :status";
            }
            
            if ($bbox) {
                // Parse bbox: west,south,east,north
                $bboxParts = explode(',', $bbox);
                if (count($bboxParts) == 4) {
                    $query .= " AND p.longitude >= :west AND p.longitude <= :east 
                              AND p.latitude >= :south AND p.latitude <= :north";
                }
            }
            
            // Add order and limit
            $query .= " ORDER BY p.created_at " . ($order == 'desc' ? 'DESC' : 'ASC');
            
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
            error_log("Error reading plantings: " . $e->getMessage());
            return null;
        }
    }

    // Read a single planting
    public function readOne() {
        try {
            $query = "SELECT p.*, u.username, ts.common_name, ts.scientific_name, ts.co2_offset_per_year_kg
                    FROM " . $this->table_name . " p
                    LEFT JOIN users u ON p.user_id = u.id
                    LEFT JOIN tree_species ts ON p.species_id = ts.id
                    WHERE p.id = :id
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
                $this->species_id = $row['species_id'];
                $this->species_name_reported = $row['species_name_reported'];
                $this->quantity = $row['quantity'];
                $this->planting_date = $row['planting_date'];
                $this->latitude = $row['latitude'];
                $this->longitude = $row['longitude'];
                $this->photo_before_path = $row['photo_before_path'];
                $this->photo_after_path = $row['photo_after_path'];
                $this->status = $row['status'];
                $this->verified_by_admin_id = $row['verified_by_admin_id'];
                $this->verification_notes = $row['verification_notes'];
                $this->verified_at = $row['verified_at'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                $this->username = $row['username'];
                $this->common_name = $row['common_name'];
                $this->scientific_name = $row['scientific_name'];
                $this->co2_offset_per_year_kg = $row['co2_offset_per_year_kg'];
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error reading planting: " . $e->getMessage());
            return false;
        }
    }

    // Update planting status and verification
    public function verify($adminId, $status, $notes = null) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                    SET status = :status, 
                        verified_by_admin_id = :admin_id,
                        verification_notes = :notes,
                        verified_at = NOW()
                    WHERE id = :id";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->id = htmlspecialchars(strip_tags($this->id));
            $status = htmlspecialchars(strip_tags($status));
            $notes = $notes ? htmlspecialchars(strip_tags($notes)) : null;
            
            // Bind values
            $stmt->bindParam(":id", $this->id);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":admin_id", $adminId);
            $stmt->bindParam(":notes", $notes);
            
            // Execute the query
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error verifying planting: " . $e->getMessage());
            return false;
        }
    }

    // Delete planting
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Sanitize ID
            $this->id = htmlspecialchars(strip_tags($this->id));
            
            // Bind parameter
            $stmt->bindParam(":id", $this->id);
            
            // Execute the query
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting planting: " . $e->getMessage());
            return false;
        }
    }
}
?> 