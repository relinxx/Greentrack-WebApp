<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Get query parameters
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Query to get users ordered by XP
    $query = "SELECT u.id, u.username, ux.xp_count 
              FROM users u
              JOIN user_xp ux ON u.id = ux.user_id
              ORDER BY ux.xp_count DESC
              LIMIT :offset, :limit";
    
    // Prepare statement
    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    
    // Execute query
    $stmt->execute();
    
    // Check if records found
    if ($stmt->rowCount() > 0) {
        // Leaderboard array
        $leaderboard_arr = array();
        
        // Retrieve and format the results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $leaderboard_arr[] = array(
                "id" => $row['id'],
                "username" => $row['username'],
                "xp_count" => $row['xp_count']
            );
        }
        
        // Set response code - 200 OK
    http_response_code(200);
        
        // Send response
        echo json_encode($leaderboard_arr);
} else {
        // No users found
        http_response_code(200);
        echo json_encode(array());
    }
} catch (Exception $e) {
    // Error retrieving leaderboard
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving leaderboard. " . $e->getMessage()));
}
?>
