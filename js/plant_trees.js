// JavaScript for the tree planting form (plant_trees.html)

document.addEventListener("DOMContentLoaded", () => {
    const plantingForm = document.getElementById("planting-form");
    let map = null;
    let marker = null;

    // Check if user is logged in
    const userId = localStorage.getItem("userId");
    console.log("User ID from localStorage:", userId);
    
    if (!userId) {
        const formMessageEl = document.querySelector(".form-message");
        if (formMessageEl) {
        showMessage(formMessageEl, "You must be logged in to log tree plantings. Redirecting...", "error");
        }
        setTimeout(() => {
            window.location.href = "/greentrack/public_html/login.html?redirect=plant_trees.html";
        }, 2000);
        return; // Stop further execution
    }

    // Initialize Leaflet map for location selection
    initializeMap();

    // Initialize event listeners
    const useMyLocationBtn = document.getElementById("use-my-location");
    if (useMyLocationBtn) {
        useMyLocationBtn.addEventListener("click", () => {
            getCurrentLocation();
        });
    }

    if (plantingForm) {
        plantingForm.addEventListener("submit", handleFormSubmit);
        
        // Setup photo preview if elements exist
        const photoInput = document.getElementById("photo");
        const photoPreview = document.getElementById("photo-preview");
        const photoPreviewContainer = document.getElementById("photo-preview-container");
        
        if (photoInput && photoPreview && photoPreviewContainer) {
            setupPhotoPreview(photoInput, photoPreview, photoPreviewContainer);
        }
    }

    // Initialize Leaflet map
    function initializeMap(lat = 51.505, lon = -0.09) {
        const mapContainer = document.getElementById("map");
        if (!mapContainer) return;
        
        map = L.map(mapContainer).setView([lat, lon], 13);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors",
        }).addTo(map);

        marker = L.marker([lat, lon], { draggable: true }).addTo(map);
        
        // Add click handler to map
        map.on("click", function(e) {
            const latlng = e.latlng;
            marker.setLatLng(latlng);
        });
        
        // Add dragend handler to marker
        marker.on("dragend", function(e) {
            const latlng = e.target.getLatLng();
        });
        }

    // Get current location
    function getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    if (map && marker) {
                        marker.setLatLng([lat, lon]);
                        map.setView([lat, lon], 16);
                    }
                },
                (error) => {
                    console.error("Geolocation error:", error);
                    const formMessageEl = document.querySelector(".form-message");
                    if (formMessageEl) {
                    showMessage(formMessageEl, `Error getting location: ${error.message}`, "error");
                    }
                },
                { enableHighAccuracy: true }
            );
        } else {
            const formMessageEl = document.querySelector(".form-message");
            if (formMessageEl) {
            showMessage(formMessageEl, "Geolocation is not supported by this browser.", "error");
            }
        }
    }

    // Handle photo preview
    function setupPhotoPreview(inputElement, previewElement, containerElement) {
        if (!inputElement || !previewElement || !containerElement) return;
        
        inputElement.addEventListener("change", function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewElement.src = e.target.result;
                    containerElement.style.display = "block";
                }
                reader.readAsDataURL(file);
            } else {
                previewElement.src = "#";
                containerElement.style.display = "none";
            }
        });
    }

    // Set default planting date to today
    const plantingDateElement = document.getElementById("planting-date");
    if (plantingDateElement) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        plantingDateElement.value = `${year}-${month}-${day}`;
    }

    // Handle form submission
    async function handleFormSubmit(e) {
        e.preventDefault();
        const formMessageEl = document.querySelector(".form-message");
        if (formMessageEl) {
        showMessage(formMessageEl, "Submitting planting log...", "");
        }

        // Get values from form
        const speciesElement = document.getElementById("species");
        const quantityElement = document.getElementById("quantity");
        const plantingDateElement = document.getElementById("planting-date");
        const descriptionElement = document.getElementById("description");

        // Validate all required fields
        if (!marker) {
            if (formMessageEl) {
            showMessage(formMessageEl, "Please select a location on the map or use GPS.", "error");
            }
            return;
        }
        
        const latlng = marker.getLatLng();
        
        if (!speciesElement || !speciesElement.value) {
            if (formMessageEl) {
                showMessage(formMessageEl, "Please enter the tree species name.", "error");
            }
            return;
        }
        
        if (!quantityElement || !quantityElement.value || quantityElement.value < 1) {
            if (formMessageEl) {
                showMessage(formMessageEl, "Please enter a valid quantity (minimum 1).", "error");
            }
            return;
        }
        
        if (!plantingDateElement || !plantingDateElement.value) {
            if (formMessageEl) {
                showMessage(formMessageEl, "Please select the planting date.", "error");
            }
            return;
        }

        const formData = new FormData();
        
        // Add all required fields explicitly
        formData.append("user_id", userId);
        formData.append("species_name_reported", speciesElement.value);
        formData.append("quantity", quantityElement.value);
        formData.append("planting_date", plantingDateElement.value);
        formData.append("latitude", latlng.lat);
        formData.append("longitude", latlng.lng);
        formData.append("description", descriptionElement ? descriptionElement.value : "");

        // Add optional photo fields if they exist
        const photoInput = document.getElementById("photo");
        if (photoInput && photoInput.files[0]) {
            formData.append("photo", photoInput.files[0]);
        }

        try {
            const response = await fetch(`${config.API_BASE_URL}/plantings/create.php`, {
                method: "POST",
                body: formData
            });

            const responseData = await response.json();
            console.log("API Response:", responseData);

            if (!response.ok) {
                throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
            }

            if (formMessageEl) {
                showMessage(formMessageEl, `Planting logged successfully! Redirecting...`, "success");
            }
            
            // Reset form and UI
            if (plantingForm) plantingForm.reset();
            const photoPreviewContainer = document.getElementById("photo-preview-container");
            if (photoPreviewContainer) photoPreviewContainer.style.display = "none";
            
            // Redirect to map
            setTimeout(() => {
                window.location.href = "/greentrack/public_html/map.html";
            }, 2000);

        } catch (error) {
            console.error("Planting log submission failed:", error);
            if (formMessageEl) {
            showMessage(formMessageEl, error.message || "Planting log submission failed. Please try again.", "error");
            }
        }
        }
    });

    // Helper function to display messages
    function showMessage(element, message, type) {
        if (!element) return;
        element.textContent = message;
        element.className = `form-message ${type}-message`;
        element.style.display = message ? "block" : "none";
    }

