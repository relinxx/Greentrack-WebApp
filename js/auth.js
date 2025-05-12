// JavaScript for handling login and registration forms (login.html)

document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("register-form");
    const loginMessageEl = document.getElementById("login-message");
    const registerMessageEl = document.getElementById("register-message");
    const showLoginBtn = document.getElementById("show-login-btn");
    const showRegisterBtn = document.getElementById("show-register-btn");

    // Base API URL with the correct path
    const API_BASE_URL = "/greentrack/api";

    // Toggle between login and register forms
    if (showLoginBtn && showRegisterBtn) {
        showLoginBtn.addEventListener("click", () => {
            loginForm.style.display = "block";
            registerForm.style.display = "none";
            showLoginBtn.classList.add("active");
            showRegisterBtn.classList.remove("active");
        });

        showRegisterBtn.addEventListener("click", () => {
            loginForm.style.display = "none";
            registerForm.style.display = "block";
            showLoginBtn.classList.remove("active");
            showRegisterBtn.classList.add("active");
        });
    }

    // Handle Login Form Submission
    if (loginForm) {
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            showMessage(loginMessageEl, "Logging in...", "info");

            const email = document.getElementById("login-email").value;
            const password = document.getElementById("login-password").value;

            if (!email || !password) {
                showMessage(loginMessageEl, "Please enter both email and password.", "error");
                return;
            }

            try {
                const response = await fetch(`${API_BASE_URL}/users/login.php`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || "Login failed. Please check your credentials.");
                }

                // Store user info in localStorage (for frontend reference only)
                localStorage.setItem("userId", data.userId);
                localStorage.setItem("username", data.username);
                localStorage.setItem("userRole", data.role);

                showMessage(loginMessageEl, "Login successful! Redirecting...", "success");

                // Handle the redirect parameter
                setTimeout(() => {
                    const redirect = new URLSearchParams(window.location.search).get("redirect");
                    if (redirect) {
                        window.location.href = redirect.startsWith('/greentrack/') ? 
                            redirect : `/greentrack/public_html/${redirect}`;
                    } else {
                        window.location.href = "/greentrack/public_html/profile.html";
                    }
                }, 1000);

            } catch (error) {
                console.error("Login error:", error);
                showMessage(loginMessageEl, error.message || "Login failed. Please check your credentials.", "error");
            }
        });
    }

    // Handle Registration Form Submission
    if (registerForm) {
        registerForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            showMessage(registerMessageEl, "Creating account...", "info");

            const username = document.getElementById("register-username").value;
            const email = document.getElementById("register-email").value;
            const password = document.getElementById("register-password").value;

            if (!username || !email || !password) {
                showMessage(registerMessageEl, "Please fill in all fields.", "error");
                return;
            }
            if (password.length < 6) {
                showMessage(registerMessageEl, "Password must be at least 6 characters long.", "error");
                return;
            }

            try {
                const response = await fetch(`${API_BASE_URL}/users/register.php`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ username, email, password })
                });

                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || "Registration failed. Please try again.");
                }

                showMessage(registerMessageEl, "Registration successful! Please log in.", "success");

                // Switch to login form after successful registration
                setTimeout(() => {
                    if (showLoginBtn) {
                        showLoginBtn.click();
                    } else {
                        registerForm.style.display = "none";
                        loginForm.style.display = "block";
                    }
                    
                    // Pre-fill login email
                    document.getElementById("login-email").value = email;
                    document.getElementById("login-password").focus();
                }, 1500);

            } catch (error) {
                console.error("Registration error:", error);
                showMessage(registerMessageEl, error.message || "Registration failed. Please try again.", "error");
            }
        });
    }

    // Add logout functionality
    function logout() {
        fetch(`${API_BASE_URL}/users/logout.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Clear localStorage
            localStorage.clear();
            // Redirect to login page
            window.location.href = '/greentrack/login.html';
        })
        .catch(error => {
            console.error('Logout error:', error);
            // Still redirect to login page even if there's an error
            window.location.href = '/greentrack/login.html';
        });
    }

    // Helper function to display messages
    function showMessage(element, message, type) {
        if (!element) return;
        element.textContent = message;
        element.className = `auth-message ${type}-message`; // Add type class for styling
        element.style.display = message ? "block" : "none";
    }
});

