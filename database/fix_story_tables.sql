-- Drop tables in correct order to avoid foreign key constraints
DROP TABLE IF EXISTS `story_likes`;
DROP TABLE IF EXISTS `story_comments`;
DROP TABLE IF EXISTS `planting_stories`;

-- Create new stories table
CREATE TABLE IF NOT EXISTS `stories` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create new story_likes table
CREATE TABLE IF NOT EXISTS `story_likes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `story_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `story_user_like_unique` (`story_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create new story_comments table
CREATE TABLE IF NOT EXISTS `story_comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `story_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `comment_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 