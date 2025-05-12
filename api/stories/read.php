<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database files
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Check if stories table exists, if not create it with sample data
    $checkTableExists = $db->query("SHOW TABLES LIKE 'stories'");
    
    if ($checkTableExists->rowCount() == 0) {
        // Create stories table
        $createTable = "CREATE TABLE IF NOT EXISTS `stories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `content` TEXT NOT NULL,
            `photo_path` VARCHAR(255) NULL,
            `category` ENUM('planting', 'cleanup', 'general') NOT NULL DEFAULT 'general',
            `likes_count` INT UNSIGNED DEFAULT 0,
            `comments_count` INT UNSIGNED DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($createTable);
        
        // Create story_comments table
        $createCommentsTable = "CREATE TABLE IF NOT EXISTS `story_comments` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `story_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `content` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($createCommentsTable);
        
        // Create story_likes table
        $createLikesTable = "CREATE TABLE IF NOT EXISTS `story_likes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `story_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `story_user_unique` (`story_id`, `user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($createLikesTable);
        
        // Insert sample stories - first get a user ID
        $getUserQuery = "SELECT id FROM users LIMIT 1";
        $userIdStmt = $db->query($getUserQuery);
        $userId = $userIdStmt->fetchColumn();
        
        if ($userId) {
            // Insert sample stories
            $sampleData = "INSERT INTO `stories` 
                (`user_id`, `title`, `content`, `category`, `likes_count`, `comments_count`) VALUES
                ($userId, 'My Oak Tree Planting Adventure', 'Last weekend, I planted three oak trees in my backyard. It was hard work digging the holes, but it felt rewarding to know I\'m helping the environment. I learned that oak trees can absorb about 22kg of CO2 per year! I\'m looking forward to watching them grow over the years.', 'planting', 5, 2),
                ($userId, 'Community Garden Project', 'Our neighborhood came together to plant a variety of trees in the local park. We planted maples, cherries, and birches. The local nursery provided guidance on proper planting techniques and care. It\'s amazing to see what a community can accomplish when working together for a good cause!', 'planting', 8, 3),
                ($userId, 'Beach Cleanup Success', 'Joined the annual beach cleanup this morning with 50 other volunteers. We collected over 200 pounds of trash! I was shocked by the amount of plastic we found. This experience has really motivated me to reduce my plastic consumption and encourage others to do the same.', 'cleanup', 12, 4)";
                
            $db->exec($sampleData);
        }
    }

// Get query parameters
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Base query to get stories with username
    $query = "SELECT s.*, u.username 
              FROM stories s
              LEFT JOIN users u ON s.user_id = u.id
              WHERE 1=1";
    
    // Add filters
    if ($category && in_array($category, ['planting', 'cleanup', 'general'])) {
        $query .= " AND s.category = :category";
    }
    
    if ($user_id) {
        $query .= " AND s.user_id = :user_id";
    }
    
    // Order by creation date, newest first
    $query .= " ORDER BY s.created_at DESC";
    
    // Add pagination
    $query .= " LIMIT :offset, :limit";
    
    // Prepare the query
    $stmt = $db->prepare($query);
    
    // Bind parameters
    if ($category && in_array($category, ['planting', 'cleanup', 'general'])) {
        $stmt->bindParam(':category', $category);
    }
    
    if ($user_id) {
        $stmt->bindParam(':user_id', $user_id);
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    
    // Execute the query
    $stmt->execute();
    
    // Check if any stories found
    $num = $stmt->rowCount();

    if ($num > 0) {
        // Stories array
        $stories_arr = array();
        $stories_arr["records"] = array();

        // Retrieve results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $story_item = array(
                "id" => $id,
                "user_id" => $user_id,
                "username" => $username,
                "title" => $title,
                "content" => $content,
                "photo_path" => $photo_path,
                "category" => $category,
                "likes_count" => $likes_count,
                "comments_count" => $comments_count,
                "created_at" => $created_at,
                "updated_at" => $updated_at
            );

            array_push($stories_arr["records"], $story_item);
        }

        // Set response code - 200 OK
        http_response_code(200);
        
        // Send response
        echo json_encode($stories_arr);
    } else {
        // No stories found
        http_response_code(200);
        echo json_encode(array("records" => array(), "message" => "No stories found."));
    }
} catch (Exception $e) {
    // Error retrieving stories
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving stories. " . $e->getMessage()));
}
?>
