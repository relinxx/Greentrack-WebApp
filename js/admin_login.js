document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('adminLoginForm');
    const errorMessage = document.getElementById('errorMessage');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        const apiUrl = '/greentrack/api/admin/login.php';
        console.log('Attempting to login with URL:', apiUrl);

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            const responseText = await response.text();
            console.log('Raw response:', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('Invalid JSON response from server');
            }

            if (response.ok) {
                // Redirect to admin dashboard (session is now active)
                window.location.href = '/greentrack/public_html/admin_dashboard.html';
            } else {
                errorMessage.textContent = data.message || 'Login failed. Please check your credentials.';
                errorMessage.style.display = 'block';
            }
        } catch (error) {
            errorMessage.textContent = 'An error occurred. Please try again later.';
            errorMessage.style.display = 'block';
            console.error('Login error:', error);
        }
    });
}); 