<?php
require_once __DIR__ . '/../config/config.php';

class Database {
    private $dbPath = DB_PATH;
    private $conn;
    private $useMySQL = true; // Set to true to use MySQL, false to use SQLite

    // Get the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            if ($this->useMySQL) {
                // Create a PDO instance for MySQL
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT;
                $this->conn = new PDO($dsn, DB_USER, DB_PASS);

             // Set error mode to exception
             $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

             // Set default fetch mode to associative array
             $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Set charset to utf8mb4
                $this->conn->exec("SET NAMES utf8mb4");
            } else {
                // Legacy SQLite connection
                $this->conn = new PDO("sqlite:" . $this->dbPath);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
             $this->conn->exec('PRAGMA foreign_keys = ON;');
            }
            } catch(PDOException $exception) {
                // Log the error
                error_log("Connection error: " . $exception->getMessage());
                
                // Return a proper JSON response
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'message' => 'Database connection failed',
                    'error' => $exception->getMessage()
                ]);
                exit();
            }

        return $this->conn;
    }
}
?>

