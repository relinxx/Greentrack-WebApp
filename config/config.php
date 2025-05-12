<?php
// Database Configuration (Using MySQL)
define("DB_HOST", "127.0.0.1");  // Use IP instead of localhost
define("DB_NAME", "greentrack");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_PORT", 3306);

// Keep SQLite path for backward compatibility
define("DB_PATH", __DIR__ . "/../database/greentrack.sqlite"); // Path to the SQLite database file

// Base URL (Adjust if needed)
define("BASE_URL", "http://localhost/greentrack"); // Updated to match your local setup

// API Settings
define("API_BASE_PATH", "/api");

// Other configurations can be added here

// Error Reporting (Development vs Production)
// For development:
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/../logs/php-error.log");

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . "/../logs")) {
    mkdir(__DIR__ . "/../logs", 0777, true);
}

// Set default timezone
date_default_timezone_set("UTC");

// Add the new constant
define('ADMIN_REGISTRATION_KEY', 'admin');



