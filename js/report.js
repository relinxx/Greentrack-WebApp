// JavaScript for the report submission form (report.html)

document.addEventListener("DOMContentLoaded", () => {
    const reportForm = document.getElementById("report-form");
    const latitudeInput = document.getElementById("latitude");
    const longitudeInput = document.getElementById("longitude");
    const useGpsBtn = document.getElementById("use-gps-btn");
    const formMessageEl = document.getElementById("form-message");
    const photoInput = document.getElementById("photo");
    const photoPreview = document.getElementById("photo-preview");
    const photoPreviewContainer = document.getElementById("photo-preview-container");

    let map = null;
    let marker = null;

    // Check if user is logged in
    const userId = localStorage.getItem("userId");
    if (!userId) {
        // Redirect to login if not authenticated
        showMessage(formMessageEl, "You must be logged in to submit a report. Redirecting...", "error");
        setTimeout(() => {
            window.location.href = "/greentrack/public_html/login.html?redirect=report.html";
        }, 2000);
        // Disable form
        reportForm.querySelectorAll("input, textarea, button").forEach(el => el.disabled = true);
        return; // Stop further execution
    }

    // Initialize map
    try {
        map = L.map('report-map').setView([config.DEFAULT_MAP_CENTER.lat, config.DEFAULT_MAP_CENTER.lng], config.DEFAULT_MAP_ZOOM);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add click handler to map
        map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            latitudeInput.value = lat.toFixed(6);
            longitudeInput.value = lng.toFixed(6);

            // Update marker
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
        });

        if (config.DEBUG) {
        console.log('Map initialized successfully');
        }
    } catch (error) {
        console.error('Error initializing map:', error);
        showMessage(formMessageEl, "Error initializing map. Please refresh the page.", "error");
    }

    // Handle GPS button click
    if (useGpsBtn) {
    useGpsBtn.addEventListener('click', () => {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    latitudeInput.value = latitude.toFixed(6);
                    longitudeInput.value = longitude.toFixed(6);

                    // Update map view and marker
                    map.setView([latitude, longitude], 15);
                    if (marker) {
                        marker.setLatLng([latitude, longitude]);
                    } else {
                        marker = L.marker([latitude, longitude]).addTo(map);
                    }
                },
                (error) => {
                    showMessage(formMessageEl, "Error getting location: " + error.message, "error");
                }
            );
        } else {
            showMessage(formMessageEl, "Geolocation is not supported by your browser.", "error");
        }
    });
    }

    // Handle photo preview
    if (photoInput && photoPreview && photoPreviewContainer) {
    photoInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                photoPreview.src = e.target.result;
                    photoPreviewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
            } else {
                photoPreview.src = '';
                photoPreviewContainer.style.display = 'none';
        }
    });
    }

    // Handle form submission
    if (reportForm) {
    reportForm.addEventListener("submit", async (e) => {
        e.preventDefault();
            if (formMessageEl) {
                showMessage(formMessageEl, "Submitting report...", "info");
            }

        // Validate required fields
        if (!latitudeInput.value || !longitudeInput.value) {
            showMessage(formMessageEl, "Please select a location on the map or use GPS.", "error");
            return;
        }

        // Create FormData object for file upload
        const formData = new FormData(reportForm);
        formData.append("user_id", userId); // Add user ID to form data

        try {
            const response = await fetch(`${config.API_BASE_URL}/reports/create.php`, {
                method: "POST",
                body: formData
            });

            // First get the raw response text
            const responseText = await response.text();
                if (config.DEBUG) {
            console.log('Raw response:', responseText);
                }
            
            // Try to parse as JSON
            let responseData;
            try {
                responseData = JSON.parse(responseText);
            } catch (e) {
                console.error("Failed to parse response as JSON:", responseText);
                throw new Error(`Server returned invalid JSON: ${responseText.substring(0, 100)}...`);
            }

            if (!response.ok) {
                throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
            }

                showMessage(formMessageEl, `Report submitted successfully! Redirecting to map...`, "success");
            reportForm.reset(); // Clear the form
                
                if (photoPreviewContainer) {
                    photoPreviewContainer.style.display = "none"; // Hide preview
                }

            // Redirect to map after a delay
            setTimeout(() => {
                window.location.href = `/greentrack/public_html/map.html?report_id=${responseData.reportId}`;
            }, 2000);
        } catch (error) {
            console.error("Error submitting report:", error);
            showMessage(formMessageEl, `Error submitting report: ${error.message}`, "error");
        }
    });
    }
});

// Helper function to show messages
function showMessage(element, message, type) {
    if (!element) return;
    
    element.textContent = message;
    element.className = `form-message ${type}-message`;
    element.style.display = message ? 'block' : 'none';
}

