<?php
require_once __DIR__ . '/../config/config.php';

try {
    // Connect to the database
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully.\n";

    // Read and execute the SQL script
    $sql = file_get_contents(__DIR__ . '/fix_story_tables.sql');
    $conn->exec($sql);
    echo "Story tables updated successfully.\n";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?> 