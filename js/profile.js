// JavaScript for the user profile page (profile.html)

document.addEventListener("DOMContentLoaded", async () => {
    const profileLoadingEl = document.getElementById("profile-loading");
    const profileErrorEl = document.getElementById("profile-error");
    const profileDetailsEl = document.getElementById("profile-details");
    const userStatsEl = document.getElementById("user-stats");
    const userBadgesEl = document.getElementById("user-badges");
    const userTagsEl = document.getElementById("user-tags");
    const userReportsEl = document.getElementById("user-reports");

    const userId = localStorage.getItem("userId");

    // Check if user is logged in
    if (!userId) {
        profileLoadingEl.style.display = "none";
        showError("You must be logged in to view your profile. Redirecting...");
        // Clear any partial auth data
        localStorage.removeItem("userId");
        localStorage.removeItem("username");
        localStorage.removeItem("userRole");
        setTimeout(() => {
            window.location.href = "/greentrack/public_html/login.html?redirect=profile.html";
        }, 2000);
        return;
    }

    try {
        // Fetch all profile data concurrently
        let profileData, xpData, badgesData, tagsData, volunteerHoursData, reportsData, plantingsData;
        
        try {
            // Try to get basic profile data - this should succeed
            profileData = await makeApiCall('GET', `/api/users/me.php?user_id=${userId}`);
        } catch (error) {
            throw new Error("Failed to load basic profile data.");
        }
        
        // Get user stats - these can have partial failures
        try {
            xpData = await makeApiCall('GET', `/api/gamification/get_xp.php?user_id=${userId}`);
        } catch (error) {
            console.warn("Failed to load XP data:", error);
            xpData = { xp_count: 0 };
        }
        
        try {
            badgesData = await makeApiCall('GET', `/api/gamification/get_badges.php?user_id=${userId}`);
        } catch (error) {
            console.warn("Failed to load badges data:", error);
            badgesData = [];
        }
        
        try {
            tagsData = await makeApiCall('GET', `/api/gamification/get_tags.php?user_id=${userId}`);
        } catch (error) {
            console.warn("Failed to load tags data:", error);
            tagsData = [];
        }
        
        try {
            volunteerHoursData = await makeApiCall('GET', `/api/gamification/get_volunteer_hours.php?user_id=${userId}`);
        } catch (error) {
            console.warn("Failed to load volunteer hours data:", error);
            volunteerHoursData = { total_hours: 0 };
        }
        
        try {
            reportsData = await makeApiCall('GET', `/api/reports/read.php?user_id=${userId}`);
        } catch (error) {
            console.warn("Failed to load trash reports:", error);
            reportsData = { records: [] };
        }

        try {
            console.log('Fetching plantings for user:', userId);
            const plantingsResponse = await makeApiCall('GET', `/api/plantings/read.php?user_id=${userId}`);
            console.log('Plantings response:', plantingsResponse);
            plantingsData = plantingsResponse;
        } catch (error) {
            console.error("Failed to load planting reports:", error);
            plantingsData = { records: [] };
        }

        // --- Populate Profile Details ---
        if (profileData) {
            document.getElementById("profile-username").textContent = profileData.username;
            document.getElementById("profile-email").textContent = profileData.email;
            document.getElementById("profile-created-at").textContent = new Date(profileData.created_at).toLocaleDateString();
            profileDetailsEl.style.display = "block";
        } else {
            throw new Error("Failed to load basic profile data.");
        }

        // --- Populate Stats ---
        const xpValue = xpData?.xp_count ?? 0;
        const volunteerHoursValue = volunteerHoursData?.total_hours ?? 0;
        const reportCount = reportsData?.records?.length ?? 0;

        document.getElementById("profile-xp").textContent = xpValue;
        document.getElementById("profile-volunteer-hours").textContent = volunteerHoursValue;
        document.getElementById("profile-reports-count").textContent = reportCount;

        // Update the XP bar in the header with the latest XP value
        updateGlobalXPBar(xpValue);

        const statsFallback = document.getElementById("stats-fallback");
        if (xpValue === 0 && volunteerHoursValue === 0 && reportCount === 0) {
            statsFallback.style.display = "block";
        } else {
            statsFallback.style.display = "none";
        }
        userStatsEl.style.display = "block";

        // --- Populate Badges ---
        const badgesContainer = document.getElementById("badges-container");
        badgesContainer.innerHTML = ""; // Clear
        if (badgesData && badgesData.length > 0) {
            badgesData.forEach(badge => {
                const badgeDiv = document.createElement("div");
                badgeDiv.className = "badge-item";
                badgeDiv.title = `${badge.description} (Earned: ${new Date(badge.earned_at).toLocaleDateString()})`;
                badgeDiv.innerHTML = `
                    ${badge.icon_path ? `<img src="${badge.icon_path}" alt="${badge.name}">` : ""}
                    <span>${badge.name}</span>
                `;
                badgesContainer.appendChild(badgeDiv);
            });
            userBadgesEl.style.display = "block";
        } else {
            badgesContainer.innerHTML = "<p>No badges earned yet.</p>";
            userBadgesEl.style.display = "block";
        }

        // --- Populate Tags ---
        const tagsContainer = document.getElementById("tags-container");
        tagsContainer.innerHTML = ""; // Clear
        if (tagsData && tagsData.length > 0) {
            tagsData.forEach(tag => {
                const tagDiv = document.createElement("div");
                tagDiv.className = "tag-item";
                tagDiv.title = tag.description || "";
                tagDiv.textContent = tag.name;
                tagsContainer.appendChild(tagDiv);
            });
            userTagsEl.style.display = "block";
        } else {
            tagsContainer.innerHTML = "<p>No tags assigned yet.</p>";
            userTagsEl.style.display = "block";
        }

        // --- Populate Recent Reports ---
        const myReportsContainer = document.getElementById("my-reports-container");
        myReportsContainer.innerHTML = ""; // Clear

        // Combine and sort reports by date
        const allReports = [
            ...(reportsData?.records || []).map(report => ({
                ...report,
                type: 'trash',
                date: new Date(report.created_at)
            })),
            ...(plantingsData?.records || []).map(planting => ({
                ...planting,
                type: 'planting',
                date: new Date(planting.planting_date)
            }))
        ].sort((a, b) => b.date - a.date).slice(0, 5);

        if (allReports.length > 0) {
            allReports.forEach(report => {
                const reportDiv = document.createElement("div");
                reportDiv.className = "report-item";
                
                if (report.type === 'trash') {
                    reportDiv.innerHTML = `
                        <div class="report-type trash">Trash Report</div>
                        <p><strong>Date:</strong> ${report.date.toLocaleString()}</p>
                        <p><strong>Location:</strong> (${report.latitude}, ${report.longitude})</p>
                        <p><strong>Status:</strong> ${report.status}</p>
                        ${report.description ? `<p><strong>Description:</strong> ${report.description.substring(0,100)}...</p>` : ""}
                        <div class="action-buttons">
                            <a href="/greentrack/public_html/map.html?report_id=${report.id}">View on Map</a>
                        </div>
                    `;
                } else {
                    reportDiv.innerHTML = `
                        <div class="report-type planting">Planting Report</div>
                        <p><strong>Date:</strong> ${report.date.toLocaleString()}</p>
                        <p><strong>Location:</strong> (${report.latitude}, ${report.longitude})</p>
                        <p><strong>Species:</strong> ${report.species_name_reported}</p>
                        <p><strong>Quantity:</strong> ${report.quantity} trees</p>
                        <p><strong>Status:</strong> ${report.status}</p>
                        <div class="action-buttons">
                            <a href="/greentrack/public_html/map.html?planting_id=${report.id}">View on Map</a>
                        </div>
                    `;
                }
                myReportsContainer.appendChild(reportDiv);
            });
            userReportsEl.style.display = "block";
        } else {
            myReportsContainer.innerHTML = "<p>You haven't submitted any reports yet.</p>";
            userReportsEl.style.display = "block";
        }

        profileLoadingEl.style.display = "none"; // Hide loading indicator

    } catch (error) {
        console.error("Error loading profile data:", error);
        profileLoadingEl.style.display = "none";
        showError(error.message || "Failed to load profile data. Please try logging in again.");
        
        // If there's an authentication error, clear auth data and redirect
        if (error.message.includes("session") || error.message.includes("login")) {
            localStorage.removeItem("userId");
            localStorage.removeItem("username");
            localStorage.removeItem("userRole");
            setTimeout(() => {
                window.location.href = "/greentrack/public_html/login.html?redirect=profile.html";
            }, 2000);
        }
    }

    function showError(message) {
        profileErrorEl.textContent = message;
        profileErrorEl.style.display = "block";
    }
    
    // Function to update the global XP bar in the header
    function updateGlobalXPBar(xpPoints) {
        // Try to update the global XP bar if it exists on the page
        try {
            console.log("Updating global XP bar with:", xpPoints);
            
            // Check if the global updateXpBar function exists
            if (typeof window.updateXpBar === 'function') {
                window.updateXpBar(xpPoints);
            } else {
                // If the function doesn't exist, we'll implement it here
                const MAX_XP = 500; // Maximum XP for full bar
                
                // Ensure xpPoints is a number
                xpPoints = parseInt(xpPoints) || 0;
                
                // Calculate percentage (capped at 100%)
                const xpPercentage = Math.min((xpPoints / MAX_XP) * 100, 100);
                
                // Update XP bar fill with smooth animation
                const xpBarFill = document.getElementById("xp-bar-fill");
                if (xpBarFill) {
                    xpBarFill.style.width = xpPercentage + "%";
                    
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
                
                // Format the XP display with current and max values
                const xpPointsElement = document.getElementById("xp-points");
                if (xpPointsElement) {
                    if (xpPoints > 0) {
                        xpPointsElement.innerHTML = `<strong>${xpPoints}</strong>/${MAX_XP} XP`;
                    } else {
                        xpPointsElement.textContent = `0/${MAX_XP} XP`;
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
            }
            
            // Also update localStorage to ensure XP is consistent across pages
            localStorage.setItem("userXP", xpPoints);
        } catch (error) {
            console.error("Error updating global XP bar:", error);
        }
    }
});

