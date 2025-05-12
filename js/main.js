// --- Dark Mode Toggle Logic ---
document.addEventListener('DOMContentLoaded', function() {
const darkModeToggle = document.getElementById("dark-mode-toggle");

    // Function to apply dark mode
    function applyDarkMode(isDark) {
        document.body.classList.toggle('dark-mode', isDark);
        localStorage.setItem('darkMode', isDark ? 'true' : 'false');
        
        // Update map if it exists and is properly initialized
        if (window.map && typeof window.map.addLayer === 'function') {
            try {
                if (isDark) {
                    // Remove default layer if it exists
                    if (window.defaultMapLayer) {
                        window.map.removeLayer(window.defaultMapLayer);
                    }
                    // Add dark layer
                    window.defaultMapLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                        attribution: '©OpenStreetMap, ©CartoDB'
                    });
                    window.map.addLayer(window.defaultMapLayer);
                } else {
                    // Remove dark layer if it exists
                    if (window.defaultMapLayer) {
                        window.map.removeLayer(window.defaultMapLayer);
                    }
                    // Add default layer
                    window.defaultMapLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    });
                    window.map.addLayer(window.defaultMapLayer);
                }
            } catch (error) {
                console.warn('Error updating map theme:', error);
            }
        }
    }

    // Check for saved theme preference or use dark mode as default
    const savedTheme = localStorage.getItem('theme');
    
    // Apply dark mode by default, unless specifically set to light
    if (savedTheme !== 'light') {
        // Delay dark mode application to ensure map is initialized
        setTimeout(() => {
            applyDarkMode(true);
        }, 100);
    }
    
    // Toggle dark mode on button click
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            const isDarkMode = document.body.classList.contains('dark-mode');
            applyDarkMode(!isDarkMode);
        });
    }
    
    // Update auth state based on local storage
    const authLink = document.getElementById("auth-link") || document.getElementById("login-link");
    const profileLink = document.getElementById("profile-link");
    const logoutButton = document.getElementById("logout-button");

    const userId = localStorage.getItem("userId");
    const username = localStorage.getItem("username");

    if (userId) {
        if (authLink) authLink.style.display = "none";
        if (profileLink) {
            profileLink.style.display = "inline-block";
            profileLink.textContent = username || "Profile";
        }
    } else {
        if (authLink) authLink.style.display = "inline-block";
        if (profileLink) profileLink.style.display = "none";
    }

    if (logoutButton) {
        logoutButton.addEventListener("click", () => {
            localStorage.removeItem("userId");
            localStorage.removeItem("username");
            localStorage.removeItem("userRole");
            window.location.href = "/greentrack/public_html/login.html";
        });
    }

    // Load content based on page elements
    // Only call fetchHomepageStats when we're on the homepage (check for index.html or root path)
    const isHomepage = 
        window.location.pathname.endsWith('index.html') || 
        window.location.pathname.endsWith('/') ||
        window.location.pathname.endsWith('/public_html/');
    
    if (isHomepage) {
        fetchHomepageStats();
    }
    
    // Load recent reports preview if the container exists
    if (document.getElementById("reports-preview-container")) {
        fetchRecentReportsPreview();
    }
    
    // Initialize tabs if they exist
    if (document.querySelector('.tab-btn')) {
        initTabs();
    }
    
    // Load recommended locations if container exists
    if (document.getElementById('recommendedLocationsList')) {
       // Check if it's the active tab initially or load if needed
       if(document.getElementById('recommended')?.classList.contains('active')){
           loadRecommendedLocations();
       }
    }
});

// --- Existing functions below ---

// Function to fetch basic stats for the homepage
async function fetchHomepageStats() {
    // Get elements with null checks
    const totalReportsEl = document.getElementById("total-reports");
    const cleanedReportsEl = document.getElementById("cleaned-reports");
    const activeUsersEl = document.getElementById("active-users");

    // If none of these elements exist, we're not on the homepage, so exit early
    if (!totalReportsEl && !cleanedReportsEl && !activeUsersEl) {
        console.log("Homepage stats elements not found on this page");
        return;
    }

    try {
        // Fetch reports for total and cleaned counts
        const reportsData = await makeApiCall('GET', '/api/reports/read.php');
        
        if (reportsData.records) {
            // Update total reports element if it exists
            if (totalReportsEl) {
                totalReportsEl.textContent = reportsData.records.length;
            }
            
            // Update cleaned reports element if it exists
            if (cleanedReportsEl) {
                const cleanedCount = reportsData.records.filter(r => r.status === "cleaned").length;
                cleanedReportsEl.textContent = cleanedCount;
            }
        } else {
            // Update with zeros if elements exist
            if (totalReportsEl) totalReportsEl.textContent = 0;
            if (cleanedReportsEl) cleanedReportsEl.textContent = 0;
        }

        // Fetch users for active users count
        if (activeUsersEl) {
            const usersData = await makeApiCall('GET', '/api/users/read.php'); 
            if (usersData.records) {
                activeUsersEl.textContent = usersData.records.length; 
            } else {
                activeUsersEl.textContent = 0;
            }
        }
    } catch (error) {
        console.error("Error fetching homepage stats:", error);
        // Update with error if elements exist
        if (totalReportsEl) totalReportsEl.textContent = "Error";
        if (cleanedReportsEl) cleanedReportsEl.textContent = "Error";
        if (activeUsersEl) activeUsersEl.textContent = "Error";
    }
}

// Function to fetch a few recent reports for the homepage preview
async function fetchRecentReportsPreview() {
    const container = document.getElementById("reports-preview-container");
    if (!container) return;

    try {
        const data = await makeApiCall('GET', '/api/reports/read.php?limit=3&order=desc'); 
        container.innerHTML = ""; // Clear loading message

        if (data.records && data.records.length > 0) {
            data.records.forEach(report => {
                const reportDiv = document.createElement("div");
                reportDiv.className = "report-preview card"; // Add card class for styling
                reportDiv.innerHTML = `
                    <h4>Report #${report.id}</h4>
                    <p><strong>Location:</strong> (${parseFloat(report.latitude).toFixed(4)}, ${parseFloat(report.longitude).toFixed(4)})</p>
                    <p><strong>Status:</strong> <span class="status-${report.status}">${report.status}</span></p>
                    <p><strong>Reported:</strong> ${new Date(report.created_at).toLocaleDateString()}</p>
                    ${report.description ? `<p><strong>Description:</strong> ${report.description.substring(0, 100)}${report.description.length > 100 ? '...' : ''}</p>` : ""}
                    <a href="/greentrack/public_html/report_detail.html?id=${report.id}" class="button secondary">Details</a>
                `;
                container.appendChild(reportDiv);
            });
        } else {
            container.innerHTML = "<p>No recent reports found.</p>";
        }
    } catch (error) {
        console.error("Error fetching recent reports preview:", error);
        container.innerHTML = "<p>Error loading recent reports.</p>";
    }
}

// Helper function for API calls
async function makeApiCall(method = "GET", endpoint, body = null) {
    const headers = {
        "Content-Type": "application/json",
    };
    // Add JWT token if available
    const token = localStorage.getItem('authToken');
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const options = {
        method: method,
        headers: headers,
    };

    if (body && (method === "POST" || method === "PUT" || method === "DELETE")) {
        options.body = JSON.stringify(body);
    }

    try {
        // Add /greentrack prefix to all API calls
        let apiUrl = endpoint;
        if (!endpoint.startsWith('/greentrack')) {
            apiUrl = `/greentrack${endpoint}`;
        }
        
        console.log(`Making API call to: ${apiUrl}`);
        const response = await fetch(apiUrl, options);
        
        const responseText = await response.text();
        let responseData;
        try {
            responseData = responseText ? JSON.parse(responseText) : {};
        } catch (e) {
            console.log('Raw response: ', responseText);
            // If JSON parsing fails but response is ok, maybe it's not JSON (e.g., logout)
            if (response.ok) return { success: true, message: responseText }; 
            throw new Error(responseText || `Request failed with status ${response.status}`);
        }

        if (!response.ok) {
            throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
        }
        
        return responseData;
    } catch (error) {
        console.error(`API call failed for ${method} ${endpoint}:`, error);
        throw error; 
    }
}

// Load recommended locations
async function loadRecommendedLocations() {
    const locationsContainer = document.getElementById('recommendedLocationsList');
    if (!locationsContainer) return;
    locationsContainer.innerHTML = '<p>Loading recommended locations...</p>'; // Show loading state
    try {
        const data = await makeApiCall('GET', '/api/admin/recommended_locations.php');
        
        if (data.locations && data.locations.length > 0) {
             locationsContainer.innerHTML = data.locations.map(location => `
                <div class="location-card card" onclick="selectLocation(${location.id}, ${location.latitude}, ${location.longitude})">
                    <h3>${location.name}</h3>
                    <p>${location.description}</p>
                    <p class="location-coords">Coordinates: ${parseFloat(location.latitude).toFixed(4)}, ${parseFloat(location.longitude).toFixed(4)}</p>
                </div>
            `).join('');
        } else {
             locationsContainer.innerHTML = '<p>No recommended locations found.</p>';
        }
    } catch (error) {
        console.error('Error loading recommended locations:', error);
        locationsContainer.innerHTML = '<p>Error loading recommended locations.</p>';
    }
}

// Handle location selection (assuming map object and form exist on the page where this is called)
function selectLocation(locationId, latitude, longitude) {
    // Check if map object exists
    if (typeof map !== 'undefined' && map) {
        // Switch to map tab if tabs exist
        const mapTabBtn = document.querySelector('[data-tab="map"]');
        if (mapTabBtn) mapTabBtn.click();
        
        // Center map on selected location
        map.setView([latitude, longitude], 15);
        
        // Optionally open a popup or highlight the location
        L.popup()
         .setLatLng([latitude, longitude])
         .setContent(`Selected Location: ${latitude.toFixed(4)}, ${longitude.toFixed(4)}`) 
         .openOn(map);

    } else {
        console.warn("Map object not found for selectLocation");
    }
    
    // If a planting form exists, pre-fill coordinates
    const plantingLatInput = document.getElementById('plantingLatitude');
    const plantingLonInput = document.getElementById('plantingLongitude');
    if (plantingLatInput && plantingLonInput) {
        plantingLatInput.value = latitude;
        plantingLonInput.value = longitude;
        // Optionally show the form if hidden
        const plantationForm = document.getElementById('plantationForm');
        if (plantationForm) plantationForm.style.display = 'block';
    } else {
        console.warn("Planting form inputs not found for selectLocation");
    }
}

// Initialize tabs
function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            btn.classList.add('active');
            const tabId = btn.getAttribute('data-tab');
            const activePane = document.getElementById(tabId);
            if (activePane) activePane.classList.add('active');
            
            // Load content specifically when tab becomes active
            if (tabId === 'recommended') {
                loadRecommendedLocations();
            }
            // Load news preview when news tab is activated
            if (tabId === 'news') {
                loadNewsPreview();
            }
            // Add similar logic for other tabs if needed, e.g., loadStories()
            // if (tabId === 'stories') {
            //     loadStories(); 
            // }
        });
    });
    
    // Activate the first tab by default if none are marked active
    if (document.querySelector('.tab-btn.active') === null && tabBtns.length > 0) {
        tabBtns[0].click();
    }
}

// Function to load news preview on the home page
async function loadNewsPreview() {
    const newsContainer = document.getElementById('news');
    if (!newsContainer) return;
    
    // Only update content if there's no existing content (other than the view all link)
    if (newsContainer.querySelectorAll('.news-item').length === 0) {
        const contentArea = document.createElement('div');
        contentArea.className = 'cards-grid';
        contentArea.innerHTML = '<p>Loading environmental news...</p>';
        
        // Insert before the view-all-link
        const viewAllLink = newsContainer.querySelector('.view-all-link');
        if (viewAllLink) {
            newsContainer.insertBefore(contentArea, viewAllLink);
        } else {
            newsContainer.appendChild(contentArea);
        }
        
        try {
            // Fetch news data from the API
            const data = await makeApiCall('GET', '/api/news/news.php');
            
            if (data.recent_reports && data.recent_reports.length > 0) {
                // Create news items based on recent reports
                contentArea.innerHTML = '';
                data.recent_reports.forEach(report => {
                    const newsDiv = document.createElement('div');
                    newsDiv.className = 'news-item card';
                    newsDiv.innerHTML = `
                        <h4>Environmental Update</h4>
                        <p>${report.description ? report.description.substring(0, 120) + (report.description.length > 120 ? '...' : '') : 'No description available'}</p>
                        <p class="news-meta">Location: (${parseFloat(report.latitude).toFixed(4)}, ${parseFloat(report.longitude).toFixed(4)})</p>
                        <p class="news-meta">Status: <span class="status-${report.status}">${report.status}</span></p>
                        <p class="news-date">${new Date(report.created_at).toLocaleDateString()}</p>
                    `;
                    contentArea.appendChild(newsDiv);
                });
            } else {
                contentArea.innerHTML = '<p>No recent environmental news available.</p>';
            }
        } catch (error) {
            console.error('Error loading news preview:', error);
            contentArea.innerHTML = '<p>Error loading environmental news.</p>';
        }
    }
}

// Placeholder for loadStories function if needed
// async function loadStories() { 
//    console.log("Loading stories..."); 
//    // Fetch and display stories
// }

// Placeholder for initMap function if needed
// function initMap() {
//    console.log("Initializing map...");
//    // Initialize Leaflet map
// }

// Function to update authentication state in the UI
function updateAuthState() {
    const loginLink = document.getElementById("login-link");
    const profileLink = document.getElementById("profile-link");
    const logoutButton = document.getElementById("logout-button");

    const userId = localStorage.getItem("userId");
    const username = localStorage.getItem("username");

    if (userId) {
        // User is logged in
        if (loginLink) loginLink.style.display = "none";
        if (profileLink) {
            profileLink.style.display = "inline-block";
            profileLink.textContent = username || "Profile";
        }
    } else {
        // User is not logged in
        if (loginLink) loginLink.style.display = "inline-block";
        if (profileLink) profileLink.style.display = "none";
        
        // Redirect to login page if on a protected page
        const protectedPages = ['profile.html', 'plant_trees.html', 'report.html'];
        const currentPage = window.location.pathname.split('/').pop();
        
        if (protectedPages.includes(currentPage)) {
            window.location.href = `/greentrack/public_html/login.html?redirect=${currentPage}`;
        }
    }

    // Setup logout button if it exists
    if (logoutButton) {
        logoutButton.addEventListener("click", () => {
            localStorage.removeItem("userId");
            localStorage.removeItem("username");
            localStorage.removeItem("userRole");
            window.location.href = "/greentrack/public_html/index.html";
        });
    }
}


