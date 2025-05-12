document.addEventListener("DOMContentLoaded", () => {
    const reportContent = document.getElementById("report-content");
    const reportMessage = document.getElementById("report-message");
    let map = null;
    let marker = null;

    // Helper to normalize image path
    function getImageUrl(photoPath) {
        if (!photoPath) return '';
        let path = photoPath;
        if (path.startsWith('/')) path = path.slice(1);
        if (!path.startsWith('uploads/')) path = 'uploads/' + path;
        return '/greentrack/' + path;
    }

    // Get report ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const reportId = urlParams.get("id");

    if (!reportId) {
        showMessage(reportMessage, "No report ID provided.", "error");
        reportContent.innerHTML = "<p>Error: No report ID provided.</p>";
        return;
    }

    // Initialize map
    function initMap(lat, lng) {
        if (map) return; // Already initialized

        map = L.map("report-map").setView([lat, lng], 15);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors"
        }).addTo(map);

        marker = L.marker([lat, lng]).addTo(map);
    }

    // Show message
    function showMessage(element, message, type) {
        element.textContent = message;
        element.className = `form-message ${type}`;
        element.style.display = "block";
    }

    // Fetch and display report details
    async function loadReportDetails() {
        try {
            const response = await fetch(`${config.API_BASE_URL}/reports/read_one.php?id=${reportId}`);
            
            // Debug: Log response details
            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));
            
            // Get the raw response text
            const rawResponse = await response.text();
            console.log('Raw API Response:', rawResponse);
            
            // Try to parse the response as JSON
            let report;
            try {
                report = JSON.parse(rawResponse);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                throw new Error('Invalid response from server');
            }
            
            if (!response.ok) {
                throw new Error(report.message || `HTTP error! status: ${response.status}`);
            }
            
            if (!report) {
                throw new Error("Report not found");
            }

            // Initialize map with report coordinates
            initMap(report.latitude, report.longitude);

            // Display report details
            reportContent.innerHTML = `
                <div class="report-details">
                    <h3>Waste Report #${report.id}</h3>
                    <div class="report-info">
                        <div class="info-item">
                            <label>Status</label>
                            <span>${report.status || 'Pending'}</span>
                        </div>
                        <div class="info-item">
                            <label>Reported By</label>
                            <span>${report.username || 'Anonymous'}</span>
                        </div>
                        <div class="info-item">
                            <label>Date</label>
                            <span>${new Date(report.created_at).toLocaleDateString()}</span>
                        </div>
                    </div>
                    ${report.description ? `
                        <div class="description">
                            <h4>Description</h4>
                            <p>${report.description}</p>
                        </div>
                    ` : ""}
                    ${report.photo_path ? `
                        <div id="report-photo" class="report-photo">
                            <h4>Photo</h4>
                            <img src="${getImageUrl(report.photo_path)}" alt="Report photo" onerror="this.parentElement.style.display='none'">
                        </div>
                    ` : ""}
                    <div class="buttons-container">
                        <a href="/greentrack/public_html/map.html" class="btn btn-secondary">Back to Map</a>
                    </div>
                </div>
            `;

        } catch (error) {
            console.error("Error loading report details:", error);
            showMessage(reportMessage, error.message || "Error loading report details. Please try again.", "error");
            reportContent.innerHTML = `
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
    loadReportDetails();
}); 