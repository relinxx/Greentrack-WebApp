-- Add type column to reports table
ALTER TABLE `reports`
ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'trash' AFTER `photo_path`; 