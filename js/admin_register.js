document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('adminRegisterForm');
    const errorMessage = document.getElementById('errorMessage');

    if (!registerForm) {
        console.error('Admin registration form not found');
        return;
    }

    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username')?.value;
        const email = document.getElementById('email')?.value;
        const password = document.getElementById('password')?.value;
        const confirmPassword = document.getElementById('confirmPassword')?.value;
        const adminKey = document.getElementById('adminKey')?.value;
        
        // Validate all fields are present
        if (!username || !email || !password || !confirmPassword || !adminKey) {
            errorMessage.textContent = 'All fields are required.';
            errorMessage.style.display = 'block';
            return;
        }

        // Check if passwords match
        if (password !== confirmPassword) {
            errorMessage.textContent = 'Passwords do not match.';
            errorMessage.style.display = 'block';
            return;
        }

        try {
            const response = await fetch('/greentrack/api/admin/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    email: email,
                    password: password,
                    admin_key: adminKey
                })
            });

            const data = await response.json();

            if (response.ok) {
                // Registration successful, redirect to login
                window.location.href = '/greentrack/public_html/admin_login.html?registered=true';
            } else {
                errorMessage.textContent = data.message || 'Registration failed. Please try again.';
                errorMessage.style.display = 'block';
            }
        } catch (error) {
            errorMessage.textContent = 'An error occurred. Please try again later.';
            errorMessage.style.display = 'block';
            console.error('Registration error:', error);
        }
    });
}); 