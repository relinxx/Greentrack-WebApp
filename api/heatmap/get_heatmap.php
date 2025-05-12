<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Set JSON response as default for all errors
header('Content-Type: application/json; charset=UTF-8');

// Function to handle errors and return JSON response
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(["error" => true, "message" => $message]);
    exit;
}

// Disable PHP error output to prevent mixing HTML with JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Custom error handler to convert PHP errors to JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    handleError("Server error: " . $errstr);
    return true;
});

// Set exception handler
set_exception_handler(function($e) {
    handleError("Exception: " . $e->getMessage());
});

// Wrap everything in try-catch to ensure JSON output
try {
    // Include database connection
    $configPath = __DIR__ . '/../../config/config.php';
    $dbPath = __DIR__ . '/../../includes/db.php';
    
    if (!file_exists($configPath)) {
        handleError("Configuration file not found: config.php");
    }
    
    if (!file_exists($dbPath)) {
        handleError("Database file not found: db.php");
    }
    
    require_once $configPath;
    require_once $dbPath;

    // Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
        handleError("Database connection failed");
    }

    // Initialize empty result array as fallback
    $heatmapPoints = [];
    
    try {
        // Get reports data with error handling
        $reportQuery = "SELECT latitude, longitude FROM reports WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
        $reportStmt = $db->prepare($reportQuery);
        
        if (!$reportStmt->execute()) {
            // Log the error but continue - don't fail completely
            error_log("Failed to execute reports query: " . implode(", ", $reportStmt->errorInfo()));
        } else {
            // Process reports if query was successful
            while ($row = $reportStmt->fetch(PDO::FETCH_ASSOC)) {
                // Validate coordinates
                $lat = floatval($row['latitude']);
                $lng = floatval($row['longitude']);
                
                if (is_numeric($lat) && is_numeric($lng) && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                    $heatmapPoints[] = [$lat, $lng];
                }
            }
        }
    } catch (Exception $e) {
        // Log error but continue to next query
        error_log("Error in reports query: " . $e->getMessage());
    }
    
    try {
        // Get plantings data with error handling
        $plantingQuery = "SELECT latitude, longitude FROM tree_plantings WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
        $plantingStmt = $db->prepare($plantingQuery);
        
        if (!$plantingStmt->execute()) {
            // Log the error but continue - don't fail completely
            error_log("Failed to execute plantings query: " . implode(", ", $plantingStmt->errorInfo()));
        } else {
            // Process plantings if query was successful
            while ($row = $plantingStmt->fetch(PDO::FETCH_ASSOC)) {
                // Validate coordinates
                $lat = floatval($row['latitude']);
                $lng = floatval($row['longitude']);
                
                if (is_numeric($lat) && is_numeric($lng) && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                    $heatmapPoints[] = [$lat, $lng];
                }
            }
        }
    } catch (Exception $e) {
        // Log error but continue
        error_log("Error in plantings query: " . $e->getMessage());
    }
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Return the heatmap data as JSON
    echo json_encode($heatmapPoints);
    
} catch (Exception $e) {
    // Catch-all for any unexpected errors
    handleError("Error retrieving heatmap data: " . $e->getMessage());
}
?>
