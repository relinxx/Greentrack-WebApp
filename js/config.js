// Configuration for the application
const config = {
    // API base URL - adjust based on your server setup
    API_BASE_URL: '/greentrack/api',
    
    // Default map center coordinates (can be overridden)
    DEFAULT_MAP_CENTER: {
        lat: 20,
        lng: 0
    },
    
    // Default map zoom level
    DEFAULT_MAP_ZOOM: 2,
    
    // Maximum file size for uploads (in bytes)
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    
    // Allowed file types for uploads
    ALLOWED_FILE_TYPES: ['image/jpeg', 'image/png', 'image/gif'],
    
    // Session timeout (in milliseconds)
    SESSION_TIMEOUT: 24 * 60 * 60 * 1000, // 24 hours
    
    // Debug mode
    DEBUG: true
};

