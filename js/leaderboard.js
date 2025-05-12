// JavaScript for the leaderboard page (leaderboard.html)

document.addEventListener("DOMContentLoaded", async () => {
    try {
        // Fetch leaderboard data from API
        const leaderboardData = await fetchLeaderboardData();
        
        // Update the leaderboard table with the fetched data
        updateLeaderboardTable(leaderboardData);
        
        // Update auth state (handled by main.js)
        // This happens automatically when main.js is included
    } catch (error) {
        console.error("Error loading leaderboard:", error);
        
        // Display error message
        const leaderboardBody = document.getElementById('leaderboard-body');
        if (leaderboardBody) {
            leaderboardBody.innerHTML = `
                <tr>
                    <td colspan="3" class="error-row">
                        Failed to load leaderboard. Please try again later.
                    </td>
                </tr>
            `;
        }
    }
});

// Function to fetch leaderboard data
async function fetchLeaderboardData() {
    try {
        // Use the config.js API_BASE_URL instead of hardcoding
        const response = await fetch(`${config.API_BASE_URL}/leaderboard/get_leaderboard.php?limit=20`);
        
        if (!response.ok) {
            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching leaderboard data:', error);
        throw error;
    }
}

// Function to update the leaderboard table with data
function updateLeaderboardTable(data) {
    const leaderboardBody = document.getElementById('leaderboard-body');
    if (!leaderboardBody) return;
    
    // Clear the loading message
    leaderboardBody.innerHTML = '';
    
    if (!data || data.length === 0) {
        leaderboardBody.innerHTML = `
            <tr>
                <td colspan="3" class="empty-row">
                    No leaderboard data available yet. Be the first to contribute!
                </td>
            </tr>
        `;
        return;
    }

    // Populate the table with leaderboard data
    data.forEach((user, index) => {
        // Create a row for each user
        const row = document.createElement('tr');
        
        // Add rank class for top 3
        if (index < 3) {
            row.classList.add(`rank-${index + 1}`);
        }
        
        row.innerHTML = `
            <td class="rank-cell">${index + 1}</td>
            <td class="username-cell">
                <span class="username">${user.username}</span>
            </td>
            <td class="xp-cell">${user.xp_count}</td>
        `;
        
        leaderboardBody.appendChild(row);
    });
    }

    // Assuming makeApiCall is available globally (from main.js or a dedicated api.js)
    // If not, include or import it here.

