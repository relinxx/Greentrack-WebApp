<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/db.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Check if tree_species table is empty, if so add sample data
    $checkEmpty = $db->query("SELECT COUNT(*) FROM tree_species");
    $count = $checkEmpty->fetchColumn();
    
    if ($count == 0) {
        // Insert sample tree species data
        $sampleData = "INSERT INTO `tree_species` 
            (`common_name`, `scientific_name`, `description`, `co2_offset_per_year_kg`, `growth_rate`, `lifespan`, `native_regions`) VALUES
            ('Oak', 'Quercus robur', 'The oak is a common symbol of strength and endurance.', 22.00, 'Slow', '500-1000 years', 'Europe, North America, Asia'),
            ('Maple', 'Acer saccharum', 'Known for its vibrant autumn colors and syrup production.', 18.50, 'Medium', '300-400 years', 'North America'),
            ('Pine', 'Pinus sylvestris', 'Evergreen coniferous tree with needle-like leaves.', 16.75, 'Medium', '100-200 years', 'Europe, Asia'),
            ('Willow', 'Salix babylonica', 'Fast-growing tree often found near water sources.', 14.30, 'Fast', '30-50 years', 'Asia, Europe'),
            ('Cedar', 'Cedrus deodara', 'Tall, evergreen conifer with aromatic wood.', 20.10, 'Slow', '150-300 years', 'Himalayas, Mediterranean'),
            ('Birch', 'Betula pendula', 'Distinctive white bark and delicate leaves.', 15.80, 'Fast', '40-100 years', 'Northern Europe, Asia, North America'),
            ('Ash', 'Fraxinus excelsior', 'Deciduous tree valued for its tough, elastic wood.', 17.20, 'Fast', '200-300 years', 'Europe, Asia Minor'),
            ('Beech', 'Fagus sylvatica', 'Deciduous tree with smooth gray bark.', 19.40, 'Slow', '150-300 years', 'Europe'),
            ('Cherry', 'Prunus avium', 'Known for beautiful spring blossoms and fruit production.', 12.60, 'Medium', '50-80 years', 'Europe, Western Asia'),
            ('Redwood', 'Sequoia sempervirens', 'One of the tallest tree species in the world.', 25.30, 'Medium', '1000-2000 years', 'Western North America')";
            
        $db->exec($sampleData);
        
        echo "Sample tree species data inserted.";
    }
    
    // Query to get all tree species
    $query = "SELECT * FROM tree_species";
    
    // Add search filter if provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $query .= " WHERE common_name LIKE :search 
                  OR scientific_name LIKE :search 
                  OR description LIKE :search";
    }
    
    // Add order by
    $query .= " ORDER BY common_name ASC";
    
    // Add limit if specified
    if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = intval($_GET['limit']);
        $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? intval($_GET['offset']) : 0;
        $query .= " LIMIT :offset, :limit";
    }
    
    // Prepare the query
    $stmt = $db->prepare($query);
    
    // Bind parameters
    if (isset($search)) {
        $searchParam = "%{$search}%";
        $stmt->bindParam(':search', $searchParam);
    }
    
    if (isset($limit)) {
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }
    
    // Execute the query
    $stmt->execute();
    
    // Check if any species found
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        // Species array
        $species_arr = array();
        $species_arr["records"] = array();
        
        // Retrieve results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $species_item = array(
                "id" => $id,
                "common_name" => $common_name,
                "scientific_name" => $scientific_name,
                "description" => $description,
                "co2_offset_per_year_kg" => $co2_offset_per_year_kg,
                "growth_rate" => $growth_rate,
                "lifespan" => $lifespan,
                "native_regions" => $native_regions
            );
            
            array_push($species_arr["records"], $species_item);
        }
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Send response
        echo json_encode($species_arr);
    } else {
        // No species found
        http_response_code(200);
        echo json_encode(array("records" => array(), "message" => "No tree species found."));
    }
} catch (Exception $e) {
    // Error retrieving species
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving tree species. " . $e->getMessage()));
}
?> 