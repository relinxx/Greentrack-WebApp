<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
include_once '../config/database.php';

// This is a file to provide data for the GreenTrack news dashboard
// In a real application, this would connect to a database or other data sources

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get stats from reports table
    $total_reports_query = "SELECT COUNT(*) as total FROM reports";
    $cleaned_reports_query = "SELECT COUNT(*) as cleaned FROM reports WHERE status = 'cleaned'";
    $users_query = "SELECT COUNT(*) as active FROM users";
    
    $total_stmt = $db->prepare($total_reports_query);
    $total_stmt->execute();
    $total_row = $total_stmt->fetch(PDO::FETCH_ASSOC);
    
    $cleaned_stmt = $db->prepare($cleaned_reports_query);
    $cleaned_stmt->execute();
    $cleaned_row = $cleaned_stmt->fetch(PDO::FETCH_ASSOC);
    
    $users_stmt = $db->prepare($users_query);
    $users_stmt->execute();
    $users_row = $users_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Dummy data for stats
    $stats = [
        "total_reports" => $total_row['total'] ?: 0,
        "cleaned_reports" => $cleaned_row['cleaned'] ?: 0,
        "active_volunteers" => $users_row['active'] ?: 0
    ];
    
    // Get recent reports from database
    $recent_query = "SELECT id, latitude, longitude, status, created_at, description 
                    FROM reports 
                    ORDER BY created_at DESC 
                    LIMIT 3";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->execute();
    
    $recent_reports = [];
    while ($row = $recent_stmt->fetch(PDO::FETCH_ASSOC)) {
        $recent_reports[] = [
            "id" => $row['id'],
            "latitude" => $row['latitude'],
            "longitude" => $row['longitude'],
            "status" => $row['status'],
            "created_at" => $row['created_at'],
            "description" => $row['description']
        ];
    }
    
    // Dummy data for chart data (example - could be structured based on chart needs)
    // In a real application, this would be dynamic data from your database
    $chart_data = [
        "chart1_labels" => ["Jan", "Feb", "Mar", "Apr", "May"],
        "chart1_values" => [12, 19, 3, 5, 2],
        "chart2_labels" => ["Category A", "Category B", "Category C"],
        "chart2_values" => [300, 50, 100]
    ];
    
    // Combine data into a single response object
    $response = [
        "stats" => $stats,
        "recent_reports" => $recent_reports,
        "chart_data" => $chart_data,
        "message" => "Data loaded successfully."
    ];
    
    // Output the data as JSON
    echo json_encode($response);
    
} catch(Exception $e) {
    // Return error message
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?>

