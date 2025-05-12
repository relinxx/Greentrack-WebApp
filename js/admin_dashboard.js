// Initialize map
let map;
let markers = [];
let currentMarker = null;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Check if admin is logged in
    checkAdminLogin();
    
    // Initialize map
    initMap();
    
    // Load initial data
    loadReports();
    loadPlantings();
    loadRecommendedLocations();
    
    // Set up tab switching
    setupTabs();
    
    // Initialize modal
    initModal();
    
    // Set up event listeners
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
    document.getElementById('addLocationForm').addEventListener('submit', handleAddLocation);
});

// Set up tab switching
function setupTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
            
            // If switching to locations tab, invalidate map size
            if (tabId === 'locations') {
                setTimeout(() => {
                    map.invalidateSize();
                }, 100);
            }
        });
    });
}

// Initialize map
function initMap() {
    map = L.map('locationsMap').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add click event to map
    map.on('click', function(e) {
        const { lat, lng } = e.latlng;
        
        // Update form fields
        document.getElementById('latitude').value = lat.toFixed(6);
        document.getElementById('longitude').value = lng.toFixed(6);
        
        // Update marker
        if (currentMarker) {
            map.removeLayer(currentMarker);
        }
        currentMarker = L.marker([lat, lng]).addTo(map);
    });
}

// Load reports
async function loadReports() {
    try {
        const response = await fetch('/greentrack/api/admin/reports.php', { credentials: 'include' });
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        const reportsList = document.getElementById('trashReports');
        reportsList.innerHTML = data.reports.map(report => `
            <div class="report-item">
                <h4>Report #${report.id}</h4>
                <p>Location: ${report.latitude}, ${report.longitude}</p>
                <p>Status: ${report.status}</p>
                <div class="report-actions">
                    <button onclick="viewReport(${report.id})" class="btn-action">View</button>
                    ${report.status === 'pending' ? `
                        <button onclick="handleReport(${report.id}, 'approve')" class="btn-action">Approve</button>
                        <button onclick="handleReport(${report.id}, 'reject')" class="btn-action">Reject</button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading reports:', error);
        alert('Failed to load reports. Please try again.');
    }
}

// Load plantings
async function loadPlantings() {
    try {
        const response = await fetch('/greentrack/api/admin/plantings.php', { credentials: 'include' });
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        const plantingsList = document.getElementById('plantingReports');
        plantingsList.innerHTML = data.plantings.map(planting => `
            <div class="report-item">
                <h4>Planting #${planting.id}</h4>
                <p>Species: ${planting.species_name_reported}</p>
                <p>Quantity: ${planting.quantity}</p>
                <p>Status: ${planting.status}</p>
                <div class="report-actions">
                    <button onclick="viewPlanting(${planting.id})" class="btn-action">View</button>
                    ${planting.status === 'pending' ? `
                        <button onclick="handlePlanting(${planting.id}, 'approve')" class="btn-action">Approve</button>
                        <button onclick="handlePlanting(${planting.id}, 'reject')" class="btn-action">Reject</button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading plantings:', error);
        alert('Failed to load plantings. Please try again.');
    }
}

// Load recommended locations
async function loadRecommendedLocations() {
    try {
        const response = await fetch('/greentrack/api/admin/recommended_locations.php', { credentials: 'include' });
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Clear existing markers
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];
        
        // Add markers to map
        data.locations.forEach(location => {
            const marker = L.marker([location.latitude, location.longitude])
                .bindPopup(`
                    <div class="location-popup">
                        <h3>${location.name}</h3>
                        <p>${location.description}</p>
                        <button onclick="deleteLocation(${location.id})" class="btn-action">Delete</button>
                    </div>
                `);
            marker.addTo(map);
            markers.push(marker);
        });
        
        // Update locations list
        const locationsList = document.getElementById('recommendedLocations');
        locationsList.innerHTML = data.locations.map(location => `
            <div class="location-item">
                <h4>${location.name}</h4>
                <p>Coordinates: ${location.latitude}, ${location.longitude}</p>
                <p>${location.description}</p>
                <button onclick="deleteLocation(${location.id})" class="btn-action">Delete</button>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading locations:', error);
        alert('Failed to load locations. Please try again.');
    }
}

// Handle adding new location
async function handleAddLocation(event) {
    event.preventDefault();
    
    const formData = {
        name: document.getElementById('locationName').value,
        latitude: document.getElementById('latitude').value,
        longitude: document.getElementById('longitude').value,
        description: document.getElementById('description').value
    };
    
    try {
        const response = await fetch('/greentrack/api/admin/add_location.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(formData)
        });
        
        // Log the raw response for debugging
        const responseText = await response.text();
        console.log('Raw server response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Failed to parse server response:', parseError);
            throw new Error('Invalid server response format');
        }
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Clear form
        event.target.reset();
        if (currentMarker) {
            map.removeLayer(currentMarker);
            currentMarker = null;
        }
        
        // Reload locations
        loadRecommendedLocations();
    } catch (error) {
        console.error('Error adding location:', error);
        alert('Failed to add location: ' + error.message);
    }
}

// Handle report actions
async function handleReport(reportId, action) {
    try {
        const response = await fetch('/greentrack/api/admin/handle_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ report_id: reportId, action })
        });
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        loadReports();
    } catch (error) {
        console.error('Error handling report:', error);
        alert('Failed to process report. Please try again.');
    }
}

// Handle planting actions
async function handlePlanting(plantingId, action) {
    try {
        const response = await fetch('/greentrack/api/admin/handle_planting.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ planting_id: plantingId, action })
        });
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        loadPlantings();
    } catch (error) {
        console.error('Error handling planting:', error);
        alert('Failed to process planting. Please try again.');
    }
}

// Handle location deletion
async function deleteLocation(locationId) {
    if (!confirm('Are you sure you want to delete this location?')) {
        return;
    }
    
    try {
        const response = await fetch(`/greentrack/api/admin/recommended_locations.php?id=${locationId}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        loadRecommendedLocations();
    } catch (error) {
        console.error('Error deleting location:', error);
        alert('Failed to delete location. Please try again.');
    }
}

// Check admin login status
async function checkAdminLogin() {
    try {
        const response = await fetch('/greentrack/api/admin/check_auth.php', { credentials: 'include' });
        const data = await response.json();
        
        if (!data.isLoggedIn) {
            window.location.href = '/greentrack/public_html/admin_login.html';
        }
    } catch (error) {
        console.error('Error checking login status:', error);
        window.location.href = '/greentrack/public_html/admin_login.html';
    }
}

// Handle logout
async function handleLogout(event) {
    event.preventDefault();
    
    try {
        const response = await fetch('/greentrack/api/admin/logout.php', {
            method: 'POST',
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        window.location.href = '/greentrack/public_html/admin_login.html';
    } catch (error) {
        console.error('Error logging out:', error);
        alert('Failed to log out. Please try again.');
    }
}

// View report details
window.viewReport = async function(reportId) {
    try {
        const response = await fetch(`/greentrack/api/admin/report_detail.php?id=${reportId}`, { credentials: 'include' });
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Failed to load report details');
        }

        const report = result.data;
        const modal = document.getElementById('reportModal');
        const modalContent = document.getElementById('modalContent');
        
        modalContent.innerHTML = `
            <h2>Report #${report.id}</h2>
            <p><strong>Status:</strong> ${report.status}</p>
            <p><strong>Description:</strong> ${report.description}</p>
            <p><strong>Location:</strong> ${report.latitude}, ${report.longitude}</p>
            <p><strong>Submitted by:</strong> ${report.username}</p>
            <p><strong>Date:</strong> ${new Date(report.created_at).toLocaleString()}</p>
            ${report.photo_path ? `<img src="/greentrack/${report.photo_path}" alt="Report photo" style="max-width: 100%;">` : ''}
        `;
        
        modal.style.display = "block";
    } catch (error) {
        console.error('Error viewing report:', error);
        alert('Failed to load report details: ' + error.message);
    }
};

// View planting details
window.viewPlanting = async function(plantingId) {
    try {
        const response = await fetch(`/greentrack/api/admin/planting_detail.php?id=${plantingId}`, { credentials: 'include' });
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Failed to load planting details');
        }

        const planting = result.data;
        const modal = document.getElementById('reportModal');
        const modalContent = document.getElementById('modalContent');
        
        modalContent.innerHTML = `
            <h2>Planting #${planting.id}</h2>
            <p><strong>Status:</strong> ${planting.status}</p>
            <p><strong>Species:</strong> ${planting.species_name_reported}</p>
            <p><strong>Quantity:</strong> ${planting.quantity}</p>
            <p><strong>Location:</strong> ${planting.latitude}, ${planting.longitude}</p>
            <p><strong>Submitted by:</strong> ${planting.username}</p>
            <p><strong>Date:</strong> ${new Date(planting.created_at).toLocaleString()}</p>
            ${planting.photo_before_path ? `<img src="/greentrack/${planting.photo_before_path}" alt="Before photo" style="max-width: 100%;">` : ''}
            ${planting.photo_after_path ? `<img src="/greentrack/${planting.photo_after_path}" alt="After photo" style="max-width: 100%;">` : ''}
        `;
        
        modal.style.display = "block";
    } catch (error) {
        console.error('Error viewing planting:', error);
        alert('Failed to load planting details: ' + error.message);
    }
};

// Initialize modal
function initModal() {
    const modal = document.getElementById('reportModal');
    const closeBtn = document.getElementById("closeModalBtn"); // Use ID selector
    
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
} 