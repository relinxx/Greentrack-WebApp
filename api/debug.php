<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: text/html; charset=UTF-8");

echo "<h1>Debugging Report Creation</h1>";

// Test database connection
echo "<h2>Testing Database Connection</h2>";
try {
    require_once __DIR__ . '/../includes/db.php';
    
    echo "<p>Database file included successfully</p>";
    
    $database = new Database();
    echo "<p>Database object created</p>";
    
    $db = $database->getConnection();
    echo "<p>Connection method called</p>";
    
    if ($db) {
        echo "<p style='color:green;'>Database connection successful!</p>";
    } else {
        echo "<p style='color:red;'>Database connection failed!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . ", Line: " . $e->getLine() . "</p>";
}

// Test report class
echo "<h2>Testing Report Class</h2>";
try {
    require_once __DIR__ . '/../lib/Report.php';
    
    echo "<p>Report file included successfully</p>";
    
    if (isset($db)) {
        $report = new Report($db);
        echo "<p>Report object created successfully</p>";
        
        // Get report table structure
        $query = "DESCRIBE reports";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        echo "<h3>Reports Table Structure:</h3>";
        echo "<ul>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>" . $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>Skipping Report tests due to database connection failure</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . ", Line: " . $e->getLine() . "</p>";
}

// Test uploads directory
echo "<h2>Testing Uploads Directory</h2>";
try {
    $upload_dir = __DIR__ . '/../uploads/';
    echo "<p>Upload directory path: " . $upload_dir . "</p>";
    
    if (file_exists($upload_dir)) {
        echo "<p>Upload directory exists.</p>";
        
        if (is_writable($upload_dir)) {
            echo "<p style='color:green;'>Upload directory is writable!</p>";
        } else {
            echo "<p style='color:red;'>Upload directory is not writable!</p>";
            echo "<p>Attempting to set permissions...</p>";
            
            if (chmod($upload_dir, 0777)) {
                echo "<p style='color:green;'>Permissions updated successfully.</p>";
            } else {
                echo "<p style='color:red;'>Failed to update permissions.</p>";
            }
        }
    } else {
        echo "<p>Upload directory does not exist. Attempting to create it...</p>";
        
        if (mkdir($upload_dir, 0777, true)) {
            echo "<p style='color:green;'>Upload directory created successfully!</p>";
        } else {
            echo "<p style='color:red;'>Failed to create upload directory!</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . ", Line: " . $e->getLine() . "</p>";
}

// Show PHP Info for debugging
echo "<h2>Environment Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";

// Show loaded extensions
echo "<h3>Loaded Extensions:</h3>";
echo "<ul>";
$extensions = get_loaded_extensions();
foreach ($extensions as $extension) {
    if ($extension === 'pdo_mysql' || $extension === 'PDO' || $extension === 'mysqli') {
        echo "<li style='color:green;'>" . $extension . "</li>";
    } else {
        echo "<li>" . $extension . "</li>";
    }
}
echo "</ul>";
?> 