// Combined JavaScript for all story-related functionality

document.addEventListener("DOMContentLoaded", () => {
    // Common elements and variables
    const userId = localStorage.getItem("userId");
    const userRole = localStorage.getItem("userRole") || "user";
    const urlParams = new URLSearchParams(window.location.search);
    const storyId = urlParams.get("id");
    const mode = urlParams.get("mode") || "list"; // list, detail, create, edit

    // Show the correct view based on mode
    document.getElementById("story-list-view").style.display = mode === "list" ? "block" : "none";
    document.getElementById("story-detail-view").style.display = mode === "detail" ? "block" : "none";
    document.getElementById("create-story-view").style.display = mode === "create" ? "block" : "none";
    document.getElementById("edit-story-view").style.display = mode === "edit" ? "block" : "none";

    // Initialize based on mode
    switch (mode) {
        case "list":
            initStoryList();
            break;
        case "detail":
            initStoryDetail();
            break;
        case "create":
            initCreateStory();
            break;
        case "edit":
            initEditStory();
            break;
    }

    // Story List Page Functions
    function initStoryList() {
        const storyListEl = document.getElementById("story-list");
        const storiesMessageEl = document.getElementById("stories-message");
        const createStoryBtn = document.getElementById("create-story-btn");

        // Show create button if logged in
        if (userId) {
            createStoryBtn.style.display = "inline-block";
            createStoryBtn.addEventListener("click", () => {
                window.location.href = "/greentrack/public_html/story.html?mode=create";
            });
        }

        // Fetch and display stories
        async function loadStories() {
            showMessage(storiesMessageEl, "Loading stories...", "");
            storyListEl.innerHTML = "<p>Loading stories...</p>";

            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/read.php`);
                const responseText = await response.text();
                console.log("Raw response:", responseText);

                let data = JSON.parse(responseText);

                if (!response.ok) {
                    throw new Error(data.message || `HTTP error! status: ${response.status}`);
                }

                if (data.records && data.records.length > 0) {
                    renderStories(data.records);
                    showMessage(storiesMessageEl, "", "");
                } else {
                    storyListEl.innerHTML = "<p>No planting stories found yet. Be the first to share yours!</p>";
                    showMessage(storiesMessageEl, "", "");
                }
            } catch (error) {
                console.error("Error loading stories:", error);
                storyListEl.innerHTML = `<p>Error loading stories: ${error.message}</p>`;
                showMessage(storiesMessageEl, error.message, "error");
            }
        }

        function renderStories(stories) {
            storyListEl.innerHTML = "";
            stories.forEach(story => {
                const li = document.createElement("li");
                li.className = "story-item";
                li.innerHTML = `
                    <div class="story-card">
                        <div class="story-header">
                            <h3><a href="/greentrack/public_html/story.html?mode=detail&id=${story.id}">${escapeHTML(story.title)}</a></h3>
                            <span class="story-meta">By ${story.username || 'Anonymous'} on ${new Date(story.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="story-body">
                            <p>${escapeHTML(story.content.substring(0, 200))}... <a href="/greentrack/public_html/story.html?mode=detail&id=${story.id}">Read More</a></p>
                        </div>
                        <div class="story-footer">
                            <a href="/greentrack/public_html/story.html?mode=detail&id=${story.id}#comments">View Comments (${story.comments_count || 0})</a>
                        </div>
                    </div>
                `;
                storyListEl.appendChild(li);
            });
        }

        // Initial load
        loadStories();
    }

    // Story Detail Page Functions
    function initStoryDetail() {
        const storyTitleEl = document.getElementById("story-title");
        const storyMetaEl = document.getElementById("story-meta");
        const storyContentEl = document.getElementById("story-content");
        const storyActionsEl = document.getElementById("story-actions");
        const likeBtn = document.getElementById("like-btn");
        const likeCountEl = document.getElementById("like-count");
        const editStoryBtn = document.getElementById("edit-story-btn");
        const deleteStoryBtn = document.getElementById("delete-story-btn");
        const commentsSection = document.getElementById("comments-section");
        const commentCountEl = document.getElementById("comment-count");
        const commentListEl = document.getElementById("comment-list");
        const commentFormContainer = document.getElementById("comment-form-container");
        const commentForm = document.getElementById("comment-form");
        const commentTextarea = document.getElementById("comment_text");
        const commentFormMessageEl = document.getElementById("comment-form-message");
        const storyDetailMessageEl = document.getElementById("story-detail-message");

        let currentStoryData = null;

        if (!storyId) {
            showMessage(storyDetailMessageEl, "Story ID not found in URL.", "error");
            storyTitleEl.textContent = "Error";
            storyContentEl.textContent = "Could not load story.";
            return;
        }

        // Show comment form if logged in
        if (userId) {
            commentFormContainer.style.display = "block";
        }

        // Fetch Story Details
        async function loadStoryDetails() {
            showMessage(storyDetailMessageEl, "Loading story details...", "");
            // Initialize counts to 0
            likeCountEl.textContent = "0";
            commentCountEl.textContent = "0";
            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/read_one.php?id=${storyId}`);
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                }
                currentStoryData = await response.json();
                renderStoryDetails(currentStoryData);
                showMessage(storyDetailMessageEl, "", "");
                loadComments();
                checkUserLikeStatus();
            } catch (error) {
                console.error("Failed to load story details:", error);
                showMessage(storyDetailMessageEl, error.message || "Failed to load story details.", "error");
                storyTitleEl.textContent = "Error Loading Story";
                storyContentEl.textContent = "Could not load story details. Please try again later.";
            }
        }

        function renderStoryDetails(story) {
            storyTitleEl.textContent = story.title;
            storyMetaEl.innerHTML = `By: ${escapeHTML(story.username)} | Created: ${new Date(story.created_at).toLocaleDateString()}`;
            storyContentEl.innerHTML = nl2br(escapeHTML(story.content));
            likeCountEl.textContent = story.likes_count || 0;
            commentCountEl.textContent = story.comments_count || 0;
            storyActionsEl.style.display = "block";

            if (userId && (userId == story.user_id || userRole === "admin")) {
                deleteStoryBtn.style.display = "inline-block";
                if (userId == story.user_id) {
                    editStoryBtn.style.display = "inline-block";
                }
            }
        }

        // Comments functionality
        async function loadComments() {
            commentListEl.innerHTML = "<p>Loading comments...</p>";
            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/comments/read.php?story_id=${storyId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                const comments = data.records || [];
                renderComments(comments);
                // Update comment count based on actual comments
                commentCountEl.textContent = comments.length;
            } catch (error) {
                console.error("Failed to load comments:", error);
                commentListEl.innerHTML = "<p>Error loading comments.</p>";
                commentCountEl.textContent = "0";
            }
        }

        function renderComments(comments) {
            commentListEl.innerHTML = "";
            if (comments.length === 0) {
                commentListEl.innerHTML = "<p>No comments yet.</p>";
                return;
            }
            comments.forEach(comment => {
                const li = document.createElement("li");
                li.className = "comment-item";
                li.dataset.commentId = comment.id;
                li.dataset.userId = comment.user_id;
                li.innerHTML = `
                    <div class="comment-meta">${escapeHTML(comment.username)} - ${new Date(comment.created_at).toLocaleString()}</div>
                    <p>${nl2br(escapeHTML(comment.comment_text))}</p>
                    <div class="comment-actions">
                        ${userId && (userId == comment.user_id || userRole === "admin") ? 
                            `<button class="delete-comment-btn">Delete</button>` : 
                            ``}
                    </div>
                `;
                commentListEl.appendChild(li);
            });

            commentListEl.querySelectorAll(".delete-comment-btn").forEach(button => {
                button.addEventListener("click", handleDeleteComment);
            });
        }

        // Comment submission
        commentForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (!userId) {
                showMessage(commentFormMessageEl, "You must be logged in to comment.", "error");
                return;
            }

            const commentText = commentTextarea.value.trim();
            if (!commentText) {
                showMessage(commentFormMessageEl, "Comment cannot be empty.", "error");
                return;
            }

            showMessage(commentFormMessageEl, "Posting comment...", "");

            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/comments/create.php`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ 
                        story_id: storyId, 
                        comment_text: commentText,
                        user_id: userId 
                    })
                });

                const responseData = await response.json();

                if (!response.ok) {
                    throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
                }

                showMessage(commentFormMessageEl, "Comment posted successfully!", "success");
                commentTextarea.value = "";
                loadComments();
                commentCountEl.textContent = parseInt(commentCountEl.textContent) + 1;

            } catch (error) {
                console.error("Failed to post comment:", error);
                showMessage(commentFormMessageEl, error.message || "Failed to post comment.", "error");
            }
        });

        // Like functionality
        let isLiked = false;
        async function checkUserLikeStatus() {
            if (!userId) {
                likeBtn.disabled = true;
                likeBtn.textContent = "Login to Like";
                return;
            }

            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/likes/check.php?story_id=${storyId}&user_id=${userId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                isLiked = data.isLiked;
                likeBtn.textContent = isLiked ? "Unlike" : "Like";
                likeBtn.disabled = false;
            } catch (error) {
                console.error("Failed to check like status:", error);
                likeBtn.disabled = false;
            }
        }

        likeBtn.addEventListener("click", async () => {
            if (!userId) {
                showMessage(storyDetailMessageEl, "Please log in to like stories.", "error");
                return;
            }

            likeBtn.disabled = true;
            const method = isLiked ? "DELETE" : "POST";
            const url = isLiked ? 
                `${config.API_BASE_URL}/stories/likes/delete.php?story_id=${storyId}&user_id=${userId}` : 
                `${config.API_BASE_URL}/stories/likes/create.php?user_id=${userId}`;
            const body = isLiked ? null : JSON.stringify({ story_id: storyId });

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { "Content-Type": "application/json" },
                    ...(method === "POST" && { body: body })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                isLiked = !isLiked;
                likeBtn.textContent = isLiked ? "Unlike" : "Like";
                const currentCount = parseInt(likeCountEl.textContent);
                likeCountEl.textContent = isLiked ? currentCount + 1 : Math.max(0, currentCount - 1);

            } catch (error) {
                console.error("Like/Unlike failed:", error);
                showMessage(storyDetailMessageEl, "Failed to update like status. Please try again.", "error");
            } finally {
                likeBtn.disabled = false;
            }
        });

        // Edit and Delete functionality
        editStoryBtn.addEventListener("click", () => {
            if (!userId) {
                showMessage(storyDetailMessageEl, "You must be logged in to edit stories.", "error");
                return;
            }

            if (!currentStoryData || !(userId == currentStoryData.user_id || userRole === "admin")) {
                showMessage(storyDetailMessageEl, "You don't have permission to edit this story.", "error");
                return;
            }

            window.location.href = `/greentrack/public_html/story.html?mode=edit&id=${storyId}`;
        });

        deleteStoryBtn.addEventListener("click", async () => {
            if (!userId) {
                showMessage(storyDetailMessageEl, "You must be logged in to delete stories.", "error");
                return;
            }

            if (!currentStoryData || !(userId == currentStoryData.user_id || userRole === "admin")) {
                showMessage(storyDetailMessageEl, "You don't have permission to delete this story.", "error");
                return;
            }

            if (!confirm("Are you sure you want to permanently delete this story and all its comments/likes?")) {
                return;
            }

            showMessage(storyDetailMessageEl, "Deleting story...", "");

            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/delete.php?id=${storyId}&user_id=${userId}&role=${userRole}`, {
                    method: "DELETE"
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                showMessage(storyDetailMessageEl, "Story deleted successfully. Redirecting...", "success");
                setTimeout(() => {
                    window.location.href = "/greentrack/public_html/story.html?mode=list";
                }, 2000);

            } catch (error) {
                console.error("Failed to delete story:", error);
                showMessage(storyDetailMessageEl, "Failed to delete story. Please try again.", "error");
            }
        });

        // Comment deletion
        async function handleDeleteComment(event) {
            const button = event.target;
            const commentItem = button.closest(".comment-item");
            const commentId = commentItem.dataset.commentId;
            const commentUserId = commentItem.dataset.userId;

            if (!userId) {
                showMessage(storyDetailMessageEl, "You must be logged in to delete comments.", "error");
                return;
            }

            if (userId !== commentUserId && userRole !== "admin") {
                showMessage(storyDetailMessageEl, "You don't have permission to delete this comment.", "error");
                return;
            }

            if (!confirm("Are you sure you want to delete this comment?")) {
                return;
            }

            showMessage(storyDetailMessageEl, "Deleting comment...", "");

            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/comments/delete.php?id=${commentId}&user_id=${userId}&role=${userRole}`, {
                    method: "DELETE"
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                showMessage(storyDetailMessageEl, "Comment deleted successfully.", "success");
                loadComments();
                commentCountEl.textContent = Math.max(0, parseInt(commentCountEl.textContent) - 1);

            } catch (error) {
                console.error("Failed to delete comment:", error);
                showMessage(storyDetailMessageEl, "Failed to delete comment. Please try again.", "error");
            }
        }

        // Initial load
        loadStoryDetails();
    }

    // Create Story Page Functions
    function initCreateStory() {
        const storyForm = document.getElementById("story-form");
        const formMessageEl = document.getElementById("form-message");
        const plantingSelect = document.getElementById("planting_id");

        if (!userId) {
            showMessage(formMessageEl, "You must be logged in to share a story. Redirecting...", "error");
            setTimeout(() => {
                window.location.href = "/greentrack/public_html/login.html?redirect=story.html?mode=create";
            }, 2000);
            storyForm.querySelectorAll("input, textarea, button, select").forEach(el => el.disabled = true);
            return;
        }

        // Load user's plantings
        async function loadUserPlantings() {
            if (!userId) return;

            try {
                const response = await fetch(`${config.API_BASE_URL}/plantings/read.php?user_id=${userId}&status=verified`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const responseText = await response.text();
                console.log('Raw plantings response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse plantings response:', e);
                    throw new Error('Invalid response from server');
                }

                const plantingSelect = document.getElementById('planting_id');
                if (!plantingSelect) {
                    console.error('Planting select element not found');
                    return;
                }

                // Clear existing options except the first one
                while (plantingSelect.options.length > 1) {
                    plantingSelect.remove(1);
                }

                if (data.records && data.records.length > 0) {
                    data.records.forEach(planting => {
                        const option = document.createElement('option');
                        option.value = planting.id;
                        option.textContent = `${planting.species_name_reported} (${new Date(planting.planting_date).toLocaleDateString()}) - ${planting.quantity} trees`;
                        plantingSelect.appendChild(option);
                    });
                    plantingSelect.disabled = false;
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No verified plantings found';
                    plantingSelect.appendChild(option);
                    plantingSelect.disabled = true;
                }
            } catch (error) {
                console.error('Error loading plantings:', error);
                const plantingSelect = document.getElementById('planting_id');
                if (plantingSelect) {
                    plantingSelect.innerHTML = '<option value="">Error loading plantings</option>';
                    plantingSelect.disabled = true;
                }
            }
        }

        // Handle form submission
        storyForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            showMessage(formMessageEl, "Publishing story...", "");

            const title = document.getElementById("title").value;
            const content = document.getElementById("content").value;
            const plantingId = plantingSelect.value || null;

            if (!title || !content) {
                showMessage(formMessageEl, "Title and content are required.", "error");
                return;
            }

            const storyData = {
                title: title,
                content: content,
                planting_id: plantingId,
                user_id: userId
            };

            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/create.php`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(storyData)
                });

                const responseText = await response.text();
                let responseData = JSON.parse(responseText);

                if (!response.ok) {
                    throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
                }

                showMessage(formMessageEl, `Story published successfully! Story ID: ${responseData.storyId}. Redirecting...`, "success");
                storyForm.reset();
                setTimeout(() => {
                    window.location.href = `/greentrack/public_html/story.html?mode=detail&id=${responseData.storyId}`;
                }, 2000);

            } catch (error) {
                console.error("Story submission failed:", error);
                showMessage(formMessageEl, error.message || "Story submission failed. Please try again.", "error");
            }
        });

        // Initial load
        loadUserPlantings();
    }

    // Edit Story Page Functions
    function initEditStory() {
        const storyForm = document.getElementById("edit-story-form");
        const formMessageEl = document.getElementById("edit-form-message");
        const plantingSelect = document.getElementById("edit-planting_id");
        const cancelBtn = document.getElementById("cancel-btn");

        if (!userId) {
            showMessage(formMessageEl, "You must be logged in to edit stories. Redirecting...", "error");
            setTimeout(() => {
                window.location.href = "/greentrack/public_html/login.html?redirect=story.html?mode=edit&id=" + storyId;
            }, 2000);
            storyForm.querySelectorAll("input, textarea, button, select").forEach(el => el.disabled = true);
            return;
        }

        if (!storyId) {
            showMessage(formMessageEl, "No story ID provided.", "error");
            return;
        }

        // Fetch story data
        async function loadStoryData() {
            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/read_one.php?id=${storyId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const storyData = await response.json();
                
                const isOwner = String(storyData.user_id) === String(userId);
                const isAdmin = userRole === "admin";

                if (!isOwner && !isAdmin) {
                    showMessage(formMessageEl, "You don't have permission to edit this story.", "error");
                    storyForm.querySelectorAll("input, textarea, button, select").forEach(el => el.disabled = true);
                    return;
                }

                document.getElementById("edit-title").value = storyData.title;
                document.getElementById("edit-content").value = storyData.content;
                await loadUserPlantings(userId);
            } catch (error) {
                console.error("Failed to load story:", error);
                showMessage(formMessageEl, "Failed to load story. Please try again.", "error");
            }
        }

        // Load user's plantings
        async function loadUserPlantings(userId) {
            try {
                if (config.DEBUG) {
                    console.log('Loading plantings for user:', userId);
                }

                const response = await fetch(`${config.API_BASE_URL}/plantings/read.php?user_id=${userId}&status=verified`);
                const data = await response.json();

                if (config.DEBUG) {
                    console.log('Plantings API response:', data);
                }

                const plantingSelect = document.getElementById('planting-select');
                if (!plantingSelect) {
                    console.error('Planting select element not found');
                    return;
                }

                // Clear existing options
                plantingSelect.innerHTML = '';

                if (data.records && data.records.length > 0) {
                    // Add default option
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Select a planting';
                    plantingSelect.appendChild(defaultOption);

                    // Add planting options
                    data.records.forEach(planting => {
                        const option = document.createElement('option');
                        option.value = planting.id;
                        option.textContent = `${planting.species_name_reported} (${new Date(planting.planting_date).toLocaleDateString()})`;
                        plantingSelect.appendChild(option);
                    });

                    // Enable the select
                    plantingSelect.disabled = false;
                } else {
                    // No plantings found
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No verified plantings found';
                    plantingSelect.appendChild(option);
                    plantingSelect.disabled = true;
                }
            } catch (error) {
                console.error('Error loading plantings:', error);
                const plantingSelect = document.getElementById('planting-select');
                if (plantingSelect) {
                    plantingSelect.innerHTML = '<option value="">Error loading plantings</option>';
                    plantingSelect.disabled = true;
                }
            }
        }

        // Handle form submission
        storyForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            showMessage(formMessageEl, "Updating story...", "");

            const title = document.getElementById("edit-title").value;
            const content = document.getElementById("edit-content").value;
            const plantingId = plantingSelect.value || "";

            if (!title || !content) {
                showMessage(formMessageEl, "Title and content are required.", "error");
                return;
            }

            const requestData = {
                id: storyId,
                title: title,
                content: content,
                planting_id: plantingId,
                userId: userId,
                userRole: userRole
            };

            try {
                const response = await fetch(`${config.API_BASE_URL}/stories/update.php`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(requestData)
                });

                const responseText = await response.text();
                let responseData;
                try {
                    responseData = JSON.parse(responseText);
                } catch (e) {
                    console.error("Failed to parse response:", responseText);
                    throw new Error("Invalid server response");
                }

                if (!response.ok) {
                    throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
                }

                showMessage(formMessageEl, "Story updated successfully! Redirecting...", "success");
                setTimeout(() => {
                    window.location.href = `/greentrack/public_html/story.html?mode=detail&id=${storyId}`;
                }, 2000);

            } catch (error) {
                console.error("Failed to update story:", error);
                showMessage(formMessageEl, error.message || "Failed to update story. Please try again.", "error");
            }
        });

        // Handle Cancel Button
        cancelBtn.addEventListener("click", () => {
            window.location.href = `/greentrack/public_html/story.html?mode=detail&id=${storyId}`;
        });

        // Initial load
        loadStoryData();
    }

    // Helper functions
    function showMessage(element, message, type) {
        if (!element) return;
        element.textContent = message;
        element.className = `form-message ${type}-message`;
        element.style.display = message ? "block" : "none";
    }

    function escapeHTML(str) {
        if (str === null || str === undefined) return "";
        const div = document.createElement("div");
        div.textContent = str;
        return div.innerHTML;
    }

    function nl2br(str) {
        return escapeHTML(str).replace(/\r\n|\r|\n/g, "<br />");
    }
}); 