<?php
class User {
    private $conn;
    private $table_name = "users";

    // Object properties
    public $id;
    public $username;
    public $email;
    public $password;
    public $role;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Register user
    public function register() {
        try {
            // First check if username or email already exists
            if ($this->usernameExists() || $this->emailExists()) {
                return false;
            }

            // Insert query
            $query = "INSERT INTO " . $this->table_name . " 
                    (username, email, password_hash, role) 
                    VALUES (:username, :email, :password_hash, :role)";

            // Prepare the query
            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->email = htmlspecialchars(strip_tags($this->email));
            
            // Hash the password
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
            
            // Set default role
            $role = "user";

            // Bind the values
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password_hash", $password_hash);
            $stmt->bindParam(":role", $role);

            // Execute the query, also check if query was successful
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Create initial XP entry
                $this->initializeXP();
                
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error in user registration: " . $e->getMessage());
            return false;
        }
    }

    // Initialize user XP 
    private function initializeXP() {
        try {
            $query = "INSERT INTO user_xp (user_id, xp_count) VALUES (:user_id, 0)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error initializing user XP: " . $e->getMessage());
            return false;
        }
    }

    // Check if username exists
    public function usernameExists() {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $this->username);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking username: " . $e->getMessage());
            return false;
        }
    }

    // Check if email exists
    public function emailExists() {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking email: " . $e->getMessage());
            return false;
        }
    }

    // Login user
    public function login() {
        try {
            $query = "SELECT id, username, email, password_hash, role 
                    FROM " . $this->table_name . " 
                    WHERE email = :email LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($this->password, $row['password_hash'])) {
                    $this->id = $row['id'];
                    $this->username = $row['username'];
                    $this->role = $row['role'];
                    return true;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error in user login: " . $e->getMessage());
            return false;
        }
    }

    // Get user details by ID
    public function getById($id) {
        try {
            $query = "SELECT id, username, email, role, created_at 
                    FROM " . $this->table_name . " 
                    WHERE id = :id LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->created_at = $row['created_at'];
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error fetching user details: " . $e->getMessage());
            return false;
        }
    }

    // Read a single user record by ID
    public function readOne() {
        try {
            // Query to read a single user
            $query = "SELECT id, username, email, role, created_at, updated_at 
                    FROM " . $this->table_name . " 
                    WHERE id = :id 
                    LIMIT 1";

            // Prepare query statement
            $stmt = $this->conn->prepare($query);

            // Bind ID value
            $stmt->bindParam(":id", $this->id);

            // Execute query
            $stmt->execute();

            // Get retrieved row
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // If a record is found
            if ($row) {
                // Set properties from retrieved row
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error reading user: " . $e->getMessage());
            return false;
        }
    }
}
?> 