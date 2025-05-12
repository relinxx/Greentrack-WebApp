// Display username and XP
document.addEventListener('DOMContentLoaded', function() {
    const userId = localStorage.getItem("userId");
    const username = localStorage.getItem("username");
    
    if (userId && username) {
        const usernameDisplay = document.getElementById("username-display");
        if (usernameDisplay) {
            usernameDisplay.textContent = username;
        }
        
        // Check if we have cached XP value in localStorage
        const cachedXP = localStorage.getItem("userXP");
        if (cachedXP) {
            // Use the cached XP value immediately to show something
            updateXpBar(parseInt(cachedXP));
        }
        
        // Initial fetch of user XP
        fetchAndUpdateUserXP(userId);
        
        // Periodically check for XP updates every 30 seconds
        // This will ensure the XP bar updates when the user gains new XP
        setInterval(() => {
            fetchAndUpdateUserXP(userId);
        }, 30000);
    } else {
        // Default display for non-logged in users - just show empty bar
        updateXpBar(0);
    }
    
    // Initialize tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked button and corresponding pane
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Smooth scroll for "Start Your Journey" button
    const ctaBtn = document.querySelector('.cta-btn');
    if (ctaBtn) {
        ctaBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const howItWorksSection = document.getElementById('how-it-works');
            if (howItWorksSection) {
                howItWorksSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
});

// Function to fetch and update user XP from the database
function fetchAndUpdateUserXP(userId) {
    console.log("Fetching XP for user ID:", userId);
    
    // Make API call to get user XP
    fetch('../api/users/get_user_xp.php?user_id=' + userId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log("XP data received:", data);
            
            // Update the XP bar with the xp_count from the response
            const xpPoints = data.xp_count !== undefined ? data.xp_count : 0;
            console.log("Updating XP bar with:", xpPoints);
            
            // Save to localStorage for persistence across pages
            localStorage.setItem("userXP", xpPoints.toString());
            
            // Force a direct update to the XP bar elements
            const xpBarFill = document.getElementById("xp-bar-fill");
            const xpPointsElement = document.getElementById("xp-points");
            
            if (xpBarFill || xpPointsElement) {
                console.log("Found XP elements, updating directly");
                updateXpBar(xpPoints);
            } else {
                console.warn("XP bar elements not found, cannot update visually");
            }
        })
        .catch(error => {
            // Log detailed error
            console.error("Error fetching XP:", error);
            
            // Try to use cached XP from localStorage if available
            const cachedXP = localStorage.getItem("userXP");
            if (cachedXP) {
                updateXpBar(parseInt(cachedXP));
            } else {
                updateXpBar(0);
            }
        });
}

// Make updateXpBar available globally
window.updateXpBar = updateXpBar;

// Enhanced function to update XP Bar with animation and visual feedback
function updateXpBar(xpPoints) {
    const MAX_XP = 500; // Maximum XP for full bar
    
    // Ensure xpPoints is a number
    xpPoints = parseInt(xpPoints) || 0;
    console.log("Processing XP update:", xpPoints);
    
    // Calculate percentage (capped at 100%)
    const xpPercentage = Math.min((xpPoints / MAX_XP) * 100, 100);
    
    // Update each element separately to handle cases where some might be missing
    updateXpBarFill(xpPoints, xpPercentage);
    updateXpPointsText(xpPoints, xpPercentage);
}

// Function to update just the XP bar fill
function updateXpBarFill(xpPoints, xpPercentage) {
    // Update XP bar fill with smooth animation
    const xpBarFill = document.getElementById("xp-bar-fill");
    
    // Ensure XP bar exists before updating
    if (!xpBarFill) {
        console.warn("XP bar fill element not found on this page");
        return;
    }
    
    console.log("Updating XP bar fill to", xpPercentage + "%");
    
    // Set initial width to 0 if it wasn't set before
    if (xpBarFill.style.width === "" && xpPoints === 0) {
        xpBarFill.style.width = "0%";
    } else {
        xpBarFill.style.width = xpPercentage + "%";
    }
    
    // Use different gradients based on progress
    if (xpPercentage >= 80) {
        xpBarFill.style.backgroundImage = 'linear-gradient(to right, #2B7A78, #4CAF50)';
    } else if (xpPercentage >= 40) {
        xpBarFill.style.backgroundImage = 'linear-gradient(to right, #3AAFA9, #2B7A78)';
    } else {
        xpBarFill.style.backgroundImage = 'linear-gradient(to right, #FFC107, #FF9800)';
    }

    // Add a pulse animation when XP increases
    xpBarFill.classList.remove("pulse-animation");
    void xpBarFill.offsetWidth; // Trigger reflow
    xpBarFill.classList.add("pulse-animation");
}

// Function to update just the XP text
function updateXpPointsText(xpPoints, xpPercentage) {
    // Format the XP display with current and max values
    const xpPointsElement = document.getElementById("xp-points");
    
    // Ensure XP points element exists before updating
    if (!xpPointsElement) {
        console.warn("XP points element not found on this page");
        return;
    }
    
    console.log("Updating XP points text for", xpPoints, "points");
    
    // Make current XP stand out if it's greater than 0
    if (xpPoints > 0) {
        xpPointsElement.innerHTML = `<strong>${xpPoints}</strong>/500 XP`;
    } else {
        xpPointsElement.textContent = `0/500 XP`;
    }
    
    // Update color based on progress
    if (xpPercentage >= 80) {
        xpPointsElement.style.color = '#4CAF50';
    } else if (xpPercentage >= 40) {
        xpPointsElement.style.color = '#2B7A78';
    } else {
        xpPointsElement.style.color = '#FF9800';
    }
}