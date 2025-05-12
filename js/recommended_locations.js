// JavaScript for the recommended locations page
document.addEventListener("DOMContentLoaded", async () => {
    // Initialize map
    const map = initMap('recommended-map');
    
    // Load and display recommended locations
    await loadRecommendedLocations(map);
    
    // Update auth state (from main.js)
    updateAuthState();
});

// Initialize Leaflet map
function initMap(containerId) {
    // Center on a default location (can be adjusted based on user's location)
    const map = L.map(containerId).setView([51.5074, -0.1278], 12);
    
    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    return map;
}

// Load recommended locations from API
async function loadRecommendedLocations(map) {
    const locationsListEl = document.getElementById('locations-list');
    locationsListEl.innerHTML = '<div class="loading">Loading recommended locations...</div>';
    
    try {
        // Fetch locations from the API
        const locations = await makeApiCall("GET", "/api/recommended_locations.php");
        
        if (!locations || !locations.locations || locations.locations.length === 0) {
            locationsListEl.innerHTML = '<div class="no-data">No recommended locations found.</div>';
            return;
        }
        
        // Clear loading message
        locationsListEl.innerHTML = '';
        
        // Add markers to the map and create location cards
        locations.locations.forEach(location => {
            // Add marker to map
            const marker = L.marker([location.latitude, location.longitude])
                .addTo(map)
                .bindPopup(`<strong>${location.name}</strong><br>${location.description}`);
            
            // Create location card
            const locationCard = createLocationCard(location);
            locationsListEl.appendChild(locationCard);
            
            // Add hover interaction between card and marker
            locationCard.addEventListener('mouseenter', () => {
                marker.openPopup();
            });
        });
        
        // Adjust map view to show all markers
        if (locations.locations.length > 0) {
            const bounds = locations.locations.map(loc => [loc.latitude, loc.longitude]);
            map.fitBounds(bounds);
        }
        
    } catch (error) {
        console.error("Error loading recommended locations:", error);
        locationsListEl.innerHTML = `<div class="error">Error loading recommended locations: ${error.message}</div>`;
    }
}

// Create a card for a location
function createLocationCard(location) {
    const card = document.createElement('div');
    card.className = 'card location-card';
    
    // Check if location has priority attribute, default to medium if not
    const priority = location.priority !== undefined ? location.priority : 5;
    
    // Calculate priority class (high, medium, low)
    let priorityClass = 'low-priority';
    if (priority >= 8) {
        priorityClass = 'high-priority';
    } else if (priority >= 4) {
        priorityClass = 'medium-priority';
    }
    
    // Priority badge text - show only if priority exists
    const priorityBadge = location.priority !== undefined ? 
        `<span class="priority-badge">Priority: ${priority}/10</span>` : '';
    
    card.innerHTML = `
        <div class="card-header ${priorityClass}">
            <h3>${location.name}</h3>
            ${priorityBadge}
        </div>
        <div class="card-body">
            <p>${location.description}</p>
            <div class="location-coords">
                <small>Coordinates: ${location.latitude.toFixed(4)}, ${location.longitude.toFixed(4)}</small>
            </div>
        </div>
        <div class="card-footer">
            <div>
                <h4>${location.name}</h4>
                <p>${location.description}</p>
                <div class="location-actions">
                    <a href="/greentrack/public_html/plant_trees.html?lat=${location.latitude}&lng=${location.longitude}" class="btn btn-primary">Plant Trees Here</a>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="showOnMap(${location.latitude}, ${location.longitude})">Show on Map</button>
        </div>
    `;
    
    return card;
}

// Function to center map on specific coordinates
function showOnMap(lat, lng) {
    const map = document.getElementById('recommended-map')._leaflet_map;
    map.setView([lat, lng], 15);
} 