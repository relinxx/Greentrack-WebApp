<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "Starting database initialization with sample data...\n";

try {
    // Create a test user if none exists
    $checkUsers = $db->query("SELECT COUNT(*) FROM users");
    $usersCount = $checkUsers->fetchColumn();
    
    if ($usersCount == 0) {
        echo "Adding sample users...\n";
        
        // Add sample user (password: password123)
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password_hash, role) VALUES 
                ('johndoe', 'john@example.com', '$password_hash', 'user'),
                ('janedoe', 'jane@example.com', '$password_hash', 'user'),
                ('adminuser', 'admin@example.com', '$password_hash', 'admin')";
        
        $db->exec($sql);
        echo "Sample users created.\n";
    } else {
        echo "Users already exist, skipping sample user creation.\n";
    }
    
    // Get a user ID for sample data
    $getUserQuery = "SELECT id FROM users WHERE username = 'johndoe' LIMIT 1";
    $userStmt = $db->query($getUserQuery);
    $userId = $userStmt->fetchColumn();
    
    if (!$userId) {
        // Use the first user if johndoe doesn't exist
        $userStmt = $db->query("SELECT id FROM users LIMIT 1");
        $userId = $userStmt->fetchColumn();
    }
    
    if ($userId) {
        // Create sample reports if none exist
        $checkReports = $db->query("SELECT COUNT(*) FROM reports");
        $reportsCount = $checkReports->fetchColumn();
        
        if ($reportsCount == 0) {
            echo "Adding sample reports...\n";
            
            $sql = "INSERT INTO reports (user_id, latitude, longitude, description, status) VALUES 
                    ($userId, 51.509865, -0.118092, 'Waste dump near the park entrance. Several plastic bags and bottles.', 'pending'),
                    ($userId, 51.511279, -0.123050, 'Large pile of construction debris left on sidewalk.', 'approved'),
                    ($userId, 51.507351, -0.127758, 'Overflowing trash bin with garbage scattered around.', 'cleaned')";
            
            $db->exec($sql);
            echo "Sample reports created.\n";
        } else {
            echo "Reports already exist, skipping sample report creation.\n";
        }
        
        // Create sample tree plantings if none exist
        $checkPlantings = $db->query("SELECT COUNT(*) FROM tree_plantings");
        $plantingsCount = $checkPlantings->fetchColumn();
        
        if ($plantingsCount == 0) {
            echo "Adding sample tree plantings...\n";
            
            // Add sample tree plantings (assuming tree_species table has data)
            $sql = "INSERT INTO tree_plantings (user_id, species_id, species_name_reported, quantity, planting_date, 
                                             latitude, longitude, status) VALUES 
                    ($userId, 1, 'Oak', 2, '2023-04-15', 51.513871, -0.101390, 'verified'),
                    ($userId, 3, 'Pine', 5, '2023-05-20', 51.517672, -0.107422, 'pending'),
                    ($userId, 2, 'Maple', 1, '2023-06-10', 51.521251, -0.122136, 'verified')";
            
            $db->exec($sql);
            echo "Sample tree plantings created.\n";
        } else {
            echo "Tree plantings already exist, skipping sample plantings creation.\n";
        }
        
        // Create user_xp entries for sample users
        $checkUserXP = $db->query("SELECT COUNT(*) FROM user_xp");
        $userXPCount = $checkUserXP->fetchColumn();
        
        if ($userXPCount == 0) {
            echo "Adding sample user XP data...\n";
            
            // Get all user IDs
            $userIdsStmt = $db->query("SELECT id FROM users");
            $userIds = $userIdsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($userIds as $id) {
                $xp = rand(50, 500); // Random XP between 50 and 500
                $sql = "INSERT INTO user_xp (user_id, xp_count) VALUES ($id, $xp)";
                $db->exec($sql);
            }
            
            echo "Sample user XP data created.\n";
        } else {
            echo "User XP data already exists, skipping sample XP creation.\n";
        }
    } else {
        echo "No users found, skipping sample data creation for reports and plantings.\n";
    }
    
    echo "Sample data initialization complete.\n";
    
} catch (PDOException $e) {
    echo "Error initializing sample data: " . $e->getMessage() . "\n";
}
?> 