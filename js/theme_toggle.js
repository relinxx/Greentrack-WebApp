document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;
    const sunIconClass = 'fa-sun'; // Font Awesome class for sun icon
    const moonIconClass = 'fa-moon'; // Font Awesome class for moon icon

    // Apply the saved theme on initial load
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if (themeToggleBtn) {
            themeToggleBtn.innerHTML = `<i class="fas ${moonIconClass}"></i>`;
        }
    } else {
        // Default to light mode (or explicitly set light mode if needed)
        document.body.classList.remove('dark-mode');
         if (themeToggleBtn) {
            themeToggleBtn.innerHTML = `<i class="fas ${sunIconClass}"></i>`;
        }
    }

    // Add event listener for the toggle button
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            let theme = 'light';
            // Toggle the .dark-mode class on the body
            document.body.classList.toggle('dark-mode');

            // Check if dark mode is now active and update storage/icon
            if (document.body.classList.contains('dark-mode')) {
                theme = 'dark';
                themeToggleBtn.innerHTML = `<i class="fas ${moonIconClass}"></i>`;
            } else {
                themeToggleBtn.innerHTML = `<i class="fas ${sunIconClass}"></i>`;
            }
            // Save the current theme preference to localStorage
            localStorage.setItem('theme', theme);
        });
    }
});

