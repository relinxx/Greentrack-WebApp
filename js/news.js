document.addEventListener('DOMContentLoaded', () => {
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            const activeContent = document.getElementById(tabId);
            if (activeContent) {
                activeContent.classList.add('active');
                // Re-trigger animations
                const newsItems = activeContent.querySelectorAll('.news-item');
                newsItems.forEach((item, index) => {
                    item.classList.remove('animated');
                    setTimeout(() => {
                        item.classList.add('animated');
                    }, index * 100);
                });
            }
        });
    });

    // Fetch news data (placeholder for API integration)
    const fetchNewsData = async () => {
        try {
            const response = await fetch('/greentrack/api/news/news.php');
            const data = await response.json();
            console.log('Received news data:', data);
            // Update news feed dynamically if needed
        } catch (error) {
            console.error('Error fetching news data:', error);
        }
    };

    fetchNewsData();
});