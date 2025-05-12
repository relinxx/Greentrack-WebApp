document.addEventListener("DOMContentLoaded", () => {
    const plantingContent = document.getElementById("planting-content");
    const plantingMessage = document.getElementById("planting-message");
    let map = null;
    let marker = null;

    // Get planting ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const plantingId = urlParams.get("id");

    if (!plantingId) {
        showMessage(plantingMessage, "No planting ID provided.", "error");
        plantingContent.innerHTML = `
            <div class="card-header">
                <h3>Error</h3>
            </div>
            <div class="card-body">
                <p>No planting ID was provided. Please return to the map and select a planting.</p>
                <div class="back-button-container">
                    <a href="/greentrack/public_html/map.html" class="btn btn-primary">Return to Map</a>
                </div>
            </div>
        `;
        return;
    }

    // Normalize image path to always start with /greentrack/uploads/ and never duplicate uploads/
    function getImageUrl(photoPath) {
        if (!photoPath) return '';
        let path = photoPath;
        if (path.startsWith('/')) path = path.slice(1); // removes leading slash
        if (!path.startsWith('uploads/')) path = 'uploads/' + path;
        return '/greentrack/' + path;
    }

    // Initialize map
    function initMap(lat, lng) {
        if (map) return; // Already initialized
        
        const mapContainer = document.getElementById("map");
        if (!mapContainer) return;
        
        map = L.map(mapContainer).setView([lat, lng], 15);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors"
        }).addTo(map);
        marker = L.marker([lat, lng]).addTo(map);
    }

    // Show message
    function showMessage(element, message, type) {
        if (!element) return;
        element.textContent = message;
        element.className = `form-message ${type}-message`;
        element.style.display = "block";
    }

    // Fetch and display planting details
    async function loadPlantingDetails() {
        try {
            showMessage(plantingMessage, "Loading planting details...", "info");
            
            // Use the makeApiCall helper function
            const response = await fetch(`${config.API_BASE_URL}/plantings/read_one.php?id=${plantingId}`);
            
            // Debug: Log response details
            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));
            
            // Get the raw response text
            const responseText = await response.text();
            console.log('Raw API Response:', responseText);
            
            // Try to parse the response as JSON
            let planting;
            try {
                planting = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                throw new Error('Invalid response from server');
            }
            
            if (!response.ok) {
                throw new Error(planting.message || `HTTP error! status: ${response.status}`);
            }
            
            if (!planting || Object.keys(planting).length === 0) {
                throw new Error("Planting not found");
            }
            
            // Initialize map with planting coordinates
            initMap(planting.latitude, planting.longitude);
            
            // Display planting details in the card format
            plantingContent.innerHTML = `
                <div class="card-header">
                    <h3>Tree Planting #${planting.id}</h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Species</label>
                            <span>${planting.species_name_reported || "Not specified"}</span>
                        </div>
                        <div class="info-item">
                            <label>Quantity</label>
                            <span>${planting.quantity}</span>
                        </div>
                        <div class="info-item">
                            <label>Planted By</label>
                            <span>${planting.username || "Anonymous"}</span>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <span>${planting.status || "Pending"}</span>
                        </div>
                        <div class="info-item">
                            <label>Date</label>
                            <span>${new Date(planting.planting_date).toLocaleDateString()}</span>
                        </div>
                    </div>
                    
                    ${planting.description ? `
                        <div class="description-section">
                            <h4>Description</h4>
                            <p>${planting.description}</p>
                        </div>
                    ` : ""}
                    
                    <div class="photos-container">
                    ${planting.photo_before_path ? `
                            <div class="photo-item">
                            <h4>Before Planting</h4>
                            <img src="${getImageUrl(planting.photo_before_path)}" alt="Before planting" onerror="this.parentElement.style.display='none'">
                        </div>
                    ` : ""}
                        
                    ${planting.photo_after_path ? `
                            <div class="photo-item">
                            <h4>After Planting</h4>
                            <img src="${getImageUrl(planting.photo_after_path)}" alt="After planting" onerror="this.parentElement.style.display='none'">
                        </div>
                    ` : ""}
                    </div>
                    
                    <div class="buttons-container">
                        <a href="/greentrack/public_html/map.html" class="btn btn-secondary">Back to Map</a>
                    </div>
                </div>
            `;
            
            // Hide the message once loaded
            plantingMessage.style.display = "none";
            
        } catch (error) {
            console.error("Error loading planting details:", error);
            showMessage(plantingMessage, error.message || "Error loading planting details. Please try again.", "error");
            
            plantingContent.innerHTML = `
                <div class="card-header">
                    <h3>Error</h3>
                </div>
                <div class="card-body">
                    <p>Could not load planting details. Please try again later.</p>
                    <div class="buttons mt-3">
                        <a href="/greentrack/public_html/map.html" class="btn btn-primary">Return to Map</a>
                    </div>
                </div>
            `;
        }
    }
    
    // Initial load
    loadPlantingDetails();
}); 