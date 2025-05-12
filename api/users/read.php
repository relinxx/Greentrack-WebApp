<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Query to get all users (excluding sensitive information)
    $query = "SELECT id, username, email, role, created_at, updated_at FROM users";
    
    // Add role filter if specified
    if (isset($_GET['role']) && in_array($_GET['role'], ['user', 'admin'])) {
        $query .= " WHERE role = :role";
    }
    
    // Add order by created_at by default
    $query .= " ORDER BY created_at DESC";
    
    // Add limit if specified
    if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = intval($_GET['limit']);
        $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? intval($_GET['offset']) : 0;
        $query .= " LIMIT :offset, :limit";
    }
    
    // Prepare the query
    $stmt = $db->prepare($query);
    
    // Bind parameters
    if (isset($_GET['role']) && in_array($_GET['role'], ['user', 'admin'])) {
        $role = $_GET['role'];
        $stmt->bindParam(':role', $role);
    }
    
    if (isset($limit)) {
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }
    
    // Execute the query
    $stmt->execute();
    
    // Check if any users found
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        // Users array
        $users_arr = array();
        $users_arr["records"] = array();
        
        // Retrieve and format the results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $user_item = array(
                "id" => $id,
                "username" => $username,
                "email" => $email,
                "role" => $role,
                "created_at" => $created_at,
                "updated_at" => $updated_at
            );
            
            array_push($users_arr["records"], $user_item);
        }
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Send response
        echo json_encode($users_arr);
    } else {
        // No users found
        http_response_code(200);
        echo json_encode(array("records" => array(), "message" => "No users found."));
    }
} catch (Exception $e) {
    // Error retrieving users
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving users. " . $e->getMessage()));
}
?> 