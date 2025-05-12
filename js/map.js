// JavaScript for the interactive map page (map.html)

document.addEventListener("DOMContentLoaded", () => {
    // Initialize map
    let map = L.map('map').setView([config.DEFAULT_MAP_CENTER.lat, config.DEFAULT_MAP_CENTER.lng], config.DEFAULT_MAP_ZOOM);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Initialize marker layers
    let reportMarkers = L.layerGroup().addTo(map);
    let plantingMarkers = L.layerGroup().addTo(map);
    let heatmapLayer = null;
    let heatmapRetryCount = 0;
    const MAX_HEATMAP_RETRIES = 3;

    const heatmapToggle = document.getElementById("heatmap-toggle");

    // Function to create a custom icon
    function createCustomIcon(emoji, className) {
        return L.divIcon({
            html: `<div class="${className}"><div>${emoji}</div></div>`,
            className: '', // Prevent default Leaflet icon class
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -20]
        });
    }
    
    // Add map legend
    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function(map) {
        const div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = `
            <h4 class="legend-title">Legend</h4>
            <div class="legend-title"><span class="tree-marker"><div>🌳</div></span> Tree Planting</div>
            <div class="legend-title"><span class="trash-marker"><div>🗑️</div></span> Waste Report</div>
        `;
        return div;
    };
    legend.addTo(map);
    
    // Function to fetch and display plantings
    async function fetchAndDisplayPlantings() {
        try {
            const bounds = map.getBounds();
            const bbox = `${bounds.getWest()},${bounds.getSouth()},${bounds.getEast()},${bounds.getNorth()}`;
            
            if (config.DEBUG) {
            console.log('Fetching plantings with bbox:', bbox);
            }

            const response = await fetch(`${config.API_BASE_URL}/plantings/read.php?bbox=${bbox}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const responseText = await response.text();
            
            if (config.DEBUG) {
            console.log('Raw response:', responseText);
            }
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Failed to parse response as JSON:', e);
                throw new Error('Invalid response from server');
            }
            
            if (config.DEBUG) {
            console.log('Plantings API response:', data);
            }

            // Clear existing planting markers
            plantingMarkers.clearLayers();

            if (data.records && data.records.length > 0) {
                if (config.DEBUG) {
                console.log(`Found ${data.records.length} plantings`);
                }
                data.records.forEach(planting => {
                    if (!planting.latitude || !planting.longitude) {
                        console.warn('Planting missing coordinates:', planting);
                        return;
                    }

                    if (config.DEBUG) {
                    console.log('Creating marker for planting:', planting);
                    }
                    const marker = L.marker([planting.latitude, planting.longitude], {
                        icon: createCustomIcon('🌳', 'tree-marker')
                    });

                    const photoUrl = planting.photo_after_path || planting.photo_before_path;
                    const popupContent = `
                        <div class="marker-popup">
                            <h3>${planting.species_name_reported || 'Tree Planting'}</h3>
                            <p>Quantity: ${planting.quantity}</p>
                            <p>Planted: ${new Date(planting.planting_date).toLocaleDateString()}</p>
                            <p>By: ${planting.username}</p>
                            <div style="margin-top: 10px;">
                                <a href="/greentrack/public_html/planting_detail.html?id=${planting.id}" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    `;

                    marker.bindPopup(popupContent);
                    plantingMarkers.addLayer(marker);
                });
            } else {
                if (config.DEBUG) {
                console.log('No plantings found in the response');
                }
            }
        } catch (error) {
            console.error('Error fetching plantings:', error);
        }
    }
    

    // Function to fetch and display reports
    async function fetchAndDisplayReports() {
        try {
            const bounds = map.getBounds();
            const bbox = `${bounds.getWest()},${bounds.getSouth()},${bounds.getEast()},${bounds.getNorth()}`;
            
            if (config.DEBUG) {
                console.log('Fetching reports with bbox:', bbox);
            }
    
            const response = await fetch(`${config.API_BASE_URL}/reports/read.php?bbox=${bbox}`);
            const data = await response.json();
    
            if (config.DEBUG) {
                console.log('Reports API response:', data);
            }
    
            // Clear existing report markers
            reportMarkers.clearLayers();
    
            if (data.records && data.records.length > 0) {
                data.records.forEach(report => {
                    const marker = L.marker([report.latitude, report.longitude], {
                        icon: createCustomIcon('🗑️', 'trash-marker')
                    });
    
                    const popupContent = `
                        <div class="marker-popup">
                            <h3>Waste Report</h3>
                            <p>Status: ${report.status}</p>
                            <p>Reported: ${new Date(report.created_at).toLocaleDateString()}</p>
                            <p>By: ${report.username}</p>
                            <div style="margin-top: 10px;">
                                <a href="/greentrack/public_html/report_detail.html?id=${report.id}" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    `;
    
                    marker.bindPopup(popupContent);
                    reportMarkers.addLayer(marker);
                });
            }
        } catch (error) {
            console.error('Error fetching reports:', error);
        }
    }
    

    // Function to fetch and display heatmap
    async function fetchAndDisplayHeatmap() {
        try {
            // Check if Leaflet.heat plugin is available
            if (typeof L.heatLayer !== 'function') {
                console.warn("Leaflet.heat plugin not loaded. Adding it dynamically...");
                
                // Try to load the plugin dynamically as fallback
                return new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js';
                    script.onload = () => {
                        console.log("Leaflet.heat plugin loaded dynamically!");
                        // Continue with heatmap initialization after loading
                        fetchAndDisplayHeatmapData().then(resolve).catch(reject);
                    };
                    script.onerror = () => {
                        console.error("Failed to load Leaflet.heat plugin dynamically");
                        reject(new Error("Could not load heatmap plugin"));
                    };
                    document.head.appendChild(script);
                });
            } else {
                return fetchAndDisplayHeatmapData();
            }
        } catch (error) {
            console.error("Error in heatmap initialization:", error);
        }
    }
    
    // Separate function to handle the actual data fetching and displaying
    async function fetchAndDisplayHeatmapData() {
        try {
            // Add a random timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch(`${config.API_BASE_URL}/heatmap/get_heatmap.php?_=${timestamp}`);
            
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }
            
            // Get response as text first to debug
            const responseText = await response.text();
            
            // Skip processing if the response is empty
            if (!responseText.trim()) {
                console.log("Heatmap API returned empty response");
                return;
            }
            
            try {
                // Parse the response text as JSON
                const data = JSON.parse(responseText);
                
                // Reset retry count on success
                heatmapRetryCount = 0;
                
                // Remove any error messages that might exist
                const errorSpan = document.querySelector('.heatmap-error');
                if (errorSpan) {
                    errorSpan.remove();
                }
                
                // Re-enable the toggle if it was disabled
                if (heatmapToggle) {
                    heatmapToggle.disabled = false;
                }

                if (heatmapLayer) {
                    map.removeLayer(heatmapLayer);
                    heatmapLayer = null;
                }

                if (data && data.length > 0) {
                    if (typeof L.heatLayer === 'function') {
                        // For leaflet.heat, points should be in format: [[lat, lng, intensity], ...] 
                        // Our API returns [[lat, lng], ...] so we need to add intensity
                        const heatPoints = data.map(point => [point[0], point[1], 1.0]); // Increased intensity to 1.0
                        
                        heatmapLayer = L.heatLayer(heatPoints, {
                            radius: 35,
                            blur: 20,
                            maxZoom: 18,
                            minOpacity: 0.4,
                            gradient: {
                                0.4: 'blue',
                                0.6: 'lime',
                                0.8: 'yellow',
                                1.0: 'red'
                            }
                        });
                        
                        if (heatmapToggle && heatmapToggle.checked) {
                            heatmapLayer.addTo(map);
                        }
                        
                        console.log("Heatmap successfully initialized with", data.length, "points");
                    } else {
                        console.warn("Heatmap plugin not loaded. Skipping heatmap display.");
                    }
                } else {
                    console.log("No heatmap data available");
                }
            } catch (error) {
                console.error("Error parsing heatmap JSON:", error);
                console.log("Raw response:", responseText);
                throw new Error("Invalid response format from heatmap API");
            }
        } catch (error) {
            console.error("Error fetching or displaying heatmap:", error);
            
            // Increment retry count
            heatmapRetryCount++;
            
            if (heatmapRetryCount < MAX_HEATMAP_RETRIES) {
                // Try again after a short delay (exponential backoff)
                const retryDelay = Math.pow(2, heatmapRetryCount) * 1000;
                console.log(`Retrying heatmap in ${retryDelay/1000} seconds (attempt ${heatmapRetryCount} of ${MAX_HEATMAP_RETRIES})...`);
                
                setTimeout(() => {
                    if (heatmapToggle && heatmapToggle.checked) {
                        fetchAndDisplayHeatmap();
                    }
                }, retryDelay);
                
                // Show a temporary notification
                const toggleLabel = heatmapToggle ? heatmapToggle.closest('label') : null;
                if (toggleLabel) {
                    let errorSpan = toggleLabel.querySelector('.heatmap-error');
                    if (!errorSpan) {
                        errorSpan = document.createElement('span');
                        errorSpan.className = 'heatmap-error';
                        errorSpan.style.color = '#f44336';
                        errorSpan.style.fontSize = '0.8rem';
                        toggleLabel.appendChild(errorSpan);
                    }
                    errorSpan.textContent = ` (Retrying...)`;
                }
            } else {
                // After max retries, show error but keep toggle enabled
                console.log("Max heatmap retries reached, giving up");
                
                const toggleLabel = heatmapToggle ? heatmapToggle.closest('label') : null;
                if (toggleLabel) {
                    let errorSpan = toggleLabel.querySelector('.heatmap-error');
                    if (!errorSpan) {
                        errorSpan = document.createElement('span');
                        errorSpan.className = 'heatmap-error';
                        errorSpan.style.color = '#f44336';
                        errorSpan.style.fontSize = '0.8rem';
                        toggleLabel.appendChild(errorSpan);
                    }
                    errorSpan.textContent = ` (Not available right now)`;
                }
                
                // Keep the toggle enabled but unchecked
                if (heatmapToggle) {
                    heatmapToggle.checked = false;
                }
            }
        }
    }

    // Initial load of markers and heatmap
    fetchAndDisplayPlantings();
    fetchAndDisplayReports();
    fetchAndDisplayHeatmap();

    // Event listener for heatmap toggle
    if (heatmapToggle) {
        heatmapToggle.addEventListener("change", () => {
            if (heatmapLayer) {
                if (heatmapToggle.checked) {
                    heatmapLayer.addTo(map);
                } else {
                    map.removeLayer(heatmapLayer);
                }
            } else if (heatmapToggle.checked) {
                // Reset retry count when user manually toggles
                heatmapRetryCount = 0;
                
                // If heatmap layer doesn't exist and toggle is checked, try to fetch and create it
                fetchAndDisplayHeatmap();
            }
        });
    }

    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Update markers when map moves
    map.on('moveend', debounce(() => {
        fetchAndDisplayPlantings();
        fetchAndDisplayReports();
    }, 500));

    // Placeholder for viewing report details
    window.viewReportDetails = function(reportId) {
        alert(`Viewing details for report ID: ${reportId}\n(Implementation needed - e.g., open modal or new page)`);
        // Example: window.location.href = `/greentrack/public_html/report_detail.html?id=${reportId}`;
    }

    // Placeholder for viewing planting details
    window.viewPlantingDetails = function(plantingId) {
        window.location.href = `/greentrack/public_html/story.html?mode=detail&planting_id=${plantingId}`;
    }
});
