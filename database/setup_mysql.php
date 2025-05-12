<?php
require_once __DIR__ . '/../config/config.php';

// Connect to MySQL server without database selected
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to MySQL server successfully.\n";
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Create database if it doesn't exist
try {
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Database created successfully or already exists.\n";
} catch(PDOException $e) {
    die("Error creating database: " . $e->getMessage() . "\n");
}

// Connect to the database
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully.\n";
} catch(PDOException $e) {
    die("Connection to database failed: " . $e->getMessage() . "\n");
}

// Create tables
try {
    // Users Table
    $sql = "CREATE TABLE IF NOT EXISTS `users` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `email` VARCHAR(100) NOT NULL UNIQUE,
      `password_hash` VARCHAR(255) NOT NULL,
      `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Users table created successfully.\n";

    // Reports Table
    $sql = "CREATE TABLE IF NOT EXISTS `reports` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `latitude` DECIMAL(10, 8) NOT NULL,
      `longitude` DECIMAL(11, 8) NOT NULL,
      `description` TEXT,
      `photo_path` VARCHAR(255) NULL,
      `status` ENUM('pending', 'approved', 'rejected', 'cleaned') NOT NULL DEFAULT 'pending',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Reports table created successfully.\n";

    // Admin Actions Log Table
    $sql = "CREATE TABLE IF NOT EXISTS `admin_actions` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `admin_user_id` INT UNSIGNED NOT NULL,
      `action_type` VARCHAR(50) NOT NULL,
      `target_report_id` INT UNSIGNED NULL,
      `target_user_id` INT UNSIGNED NULL,
      `details` TEXT NULL,
      `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`admin_user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
      FOREIGN KEY (`target_report_id`) REFERENCES `reports`(`id`) ON DELETE SET NULL,
      FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Admin Actions Log table created successfully.\n";

    // Badges Table
    $sql = "CREATE TABLE IF NOT EXISTS `badges` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(100) NOT NULL UNIQUE,
      `description` TEXT NOT NULL,
      `criteria_type` VARCHAR(50) NULL COMMENT 'e.g., reports_submitted, xp_reached, trees_planted_total, co2_offset_total',
      `criteria_value` VARCHAR(50) NULL COMMENT 'The value associated with the criteria type',
      `unlock_criteria` TEXT NOT NULL,
      `icon_path` VARCHAR(255) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Badges table created successfully.\n";

    // User Badges Table
    $sql = "CREATE TABLE IF NOT EXISTS `user_badges` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `badge_id` INT UNSIGNED NOT NULL,
      `earned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`badge_id`) REFERENCES `badges`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `user_badge_unique` (`user_id`, `badge_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "User Badges table created successfully.\n";

    // Tags Table
    $sql = "CREATE TABLE IF NOT EXISTS `tags` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(50) NOT NULL UNIQUE,
      `criteria_type` VARCHAR(50) NULL COMMENT 'e.g., user_role, reports_submitted_min, reports_submitted_max, trees_planted_min',
      `criteria_value` VARCHAR(50) NULL COMMENT 'The value associated with the criteria type',
      `description` TEXT NULL,
      `purpose` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Tags table created successfully.\n";

    // User Tags Table
    $sql = "CREATE TABLE IF NOT EXISTS `user_tags` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `tag_id` INT UNSIGNED NOT NULL,
      `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `user_tag_unique` (`user_id`, `tag_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "User Tags table created successfully.\n";

    // User XP Table
    $sql = "CREATE TABLE IF NOT EXISTS `user_xp` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `xp_count` INT UNSIGNED NOT NULL DEFAULT 0,
      `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `user_xp_unique` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "User XP table created successfully.\n";

    // Volunteer Hours Table
    $sql = "CREATE TABLE IF NOT EXISTS `volunteer_hours` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `hours` DECIMAL(5, 2) NOT NULL,
      `activity_description` TEXT NULL,
      `logged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Volunteer Hours table created successfully.\n";

    // Tree Species Table
    $sql = "CREATE TABLE IF NOT EXISTS `tree_species` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `common_name` VARCHAR(100) NOT NULL,
      `scientific_name` VARCHAR(100) NOT NULL,
      `description` TEXT,
      `co2_offset_per_year_kg` DECIMAL(10,2) DEFAULT 0.00,
      `growth_rate` VARCHAR(50),
      `lifespan` VARCHAR(100),
      `native_regions` TEXT,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Tree species table created successfully.\n";

    // Tree Plantings Table
    $sql = "CREATE TABLE IF NOT EXISTS `tree_plantings` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `species_id` INT UNSIGNED,
      `species_name_reported` VARCHAR(100) NOT NULL,
      `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
      `planting_date` DATE NOT NULL,
      `latitude` DECIMAL(10, 8) NOT NULL,
      `longitude` DECIMAL(11, 8) NOT NULL,
      `photo_before_path` VARCHAR(255) NULL,
      `photo_after_path` VARCHAR(255) NULL,
      `status` ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
      `verified_by_admin_id` INT UNSIGNED NULL,
      `verification_notes` TEXT NULL,
      `verified_at` TIMESTAMP NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`species_id`) REFERENCES `tree_species`(`id`) ON DELETE SET NULL,
      FOREIGN KEY (`verified_by_admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Tree plantings table created successfully.\n";

    // Stories Table
    $sql = "CREATE TABLE IF NOT EXISTS `stories` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `title` VARCHAR(255) NOT NULL,
      `content` TEXT NOT NULL,
      `photo_path` VARCHAR(255) NULL,
      `category` VARCHAR(50) NULL,
      `likes_count` INT UNSIGNED NOT NULL DEFAULT 0,
      `comments_count` INT UNSIGNED NOT NULL DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Stories table created successfully.\n";

    // Story Likes Table
    $sql = "CREATE TABLE IF NOT EXISTS `story_likes` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `story_id` INT UNSIGNED NOT NULL,
      `user_id` INT UNSIGNED NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `story_user_like_unique` (`story_id`, `user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Story Likes table created successfully.\n";

    // Story Comments Table
    $sql = "CREATE TABLE IF NOT EXISTS `story_comments` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `story_id` INT UNSIGNED NOT NULL,
      `user_id` INT UNSIGNED NOT NULL,
      `comment_text` TEXT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Story Comments table created successfully.\n";

    // Recommended Locations Table
    $sql = "CREATE TABLE IF NOT EXISTS `recommended_locations` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(255) NOT NULL,
      `latitude` DECIMAL(10, 8) NOT NULL,
      `longitude` DECIMAL(11, 8) NOT NULL,
      `description` TEXT,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `created_by` INT UNSIGNED NULL,
      FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Recommended Locations table created successfully.\n";

    // Seed data for badges
    $sql = "INSERT IGNORE INTO `badges` (`name`, `description`, `criteria_type`, `criteria_value`, `unlock_criteria`, `icon_path`) VALUES
    ('Tree Planter Lv.1', 'Planted 5 trees.', 'trees_planted_total', '5', 'Plant 5 trees', '/images/badges/tree_planter_1.png'),
    ('Tree Planter Lv.2', 'Planted 10 trees.', 'trees_planted_total', '10', 'Plant 10 trees', '/images/badges/tree_planter_2.png'),
    ('Tree Planter Lv.3', 'Planted 25 trees.', 'trees_planted_total', '25', 'Plant 25 trees', '/images/badges/tree_planter_3.png'),
    ('Tree Planter Lv.4', 'Planted 50 trees.', 'trees_planted_total', '50', 'Plant 50 trees', '/images/badges/tree_planter_4.png'),
    ('Tree Planter Lv.5', 'Planted 100 trees.', 'trees_planted_total', '100', 'Plant 100 trees', '/images/badges/tree_planter_5.png'),
    ('CO2 Buster', 'Offset 1 ton (1000 kg) of CO2 through verified tree planting.', 'co2_offset_total_kg', '1000', 'Offset 1 ton of CO2', '/images/badges/co2_buster.png'),
    ('Green Warrior', 'An active contributor to environmental cleanup.', 'reports_submitted', '10', 'Submit 10 waste reports', '/images/badges/green_warrior.png'),
    ('Early Bird', 'One of the first users to join the platform.', 'join_date', '2024-01-01', 'Register before 2024', '/images/badges/early_bird.png')";
    $conn->exec($sql);
    echo "Seeded badges data successfully.\n";

    // Create default admin user with password 'admin123'
    $sql = "INSERT IGNORE INTO `users` (username, email, password_hash, role)
            VALUES ('admin', 'admin@greentrack.org', :password_hash, 'admin')";
    $stmt = $conn->prepare($sql);
    $password_hash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->execute();
    
    // Get the admin ID
    $adminId = $conn->lastInsertId();
    if ($adminId) {
        // Initialize admin XP
        $sql = "INSERT IGNORE INTO `user_xp` (user_id, xp_count) VALUES (:admin_id, 1000)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':admin_id', $adminId);
        $stmt->execute();
        
        echo "Created default admin user. Username: admin, Password: admin123\n";
    } else {
        echo "Admin user already exists.\n";
    }

    echo "All database tables and initial data created successfully!\n";
} catch(PDOException $e) {
    die("Error creating tables: " . $e->getMessage() . "\n");
}
?> 