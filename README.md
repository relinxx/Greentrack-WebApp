# GreenTrack - Environmental Cleanup & Tree Planting Tracker

GreenTrack is a web application that allows users to report waste/garbage dumps, log tree plantings, share stories, and track their environmental impact.

## Features

- User registration and authentication
- Report waste dumps with location and photos
- Log tree plantings with species information and photos
- Interactive map showing all reports and plantings
- User profiles with badges, XP, and activity tracking
- Planting stories with comments and likes
- Admin dashboard for managing reports and content
- Recommended planting locations

## Setup Instructions

### Prerequisites

- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.2+
- Web server (Apache/Nginx)
- Composer (optional, for future dependencies)

### Installation

1. **Clone the repository**
   ```
   git clone https://github.com/yourusername/greentrack.git
   cd greentrack
   ```

2. **Database Setup**
   - Create a MySQL database named `greentrack`
   - Run the database setup script:
     ```
     php database/setup_mysql.php
     ```
   - (Optional) Add sample data for testing:
     ```
     php database/init_sample_data.php
     ```

3. **Configuration**
   - Modify `config/config.php` with your database credentials:
     ```php
     define("DB_HOST", "localhost");
     define("DB_NAME", "greentrack");
     define("DB_USER", "your_mysql_username");
     define("DB_PASS", "your_mysql_password");
     define("DB_PORT", 3306);
     ```

4. **Create necessary directories**
   - Ensure upload directories exist:
     ```
     mkdir -p uploads/reports uploads/plantings
     chmod 777 uploads/reports uploads/plantings
     ```

5. **Web Server Setup**
   - Point your web server to the project root directory
   - For Apache, ensure mod_rewrite is enabled
   - For XAMPP: Move/copy the project folder to `htdocs` directory

## Testing Accounts

After running the sample data initialization, you can use these test accounts:

- Regular User:
  - Username: `johndoe`
  - Email: `john@example.com`
  - Password: `password123`

- Admin User:
  - Username: `adminuser`
  - Email: `admin@example.com`
  - Password: `password123`

## File Structure

- `/api` - API endpoints for frontend communication
- `/config` - Configuration files
- `/css` - Stylesheets
- `/database` - Database scripts and migrations
- `/includes` - Core PHP includes
- `/js` - JavaScript files
- `/lib` - Core application classes
- `/uploads` - File upload directory

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Ensure MySQL server is running
   - Verify credentials in `config/config.php`
   - Check database existence

2. **Permission Issues**
   - Ensure upload directories have write permissions
   - Check PHP error logs for permission-related errors

3. **API Endpoint 404 Errors**
   - Make sure .htaccess is properly configured
   - Check JavaScript console for AJAX request details
   - Ensure the project is accessed via proper URL (e.g., `http://localhost/greentrack/`)

4. **File Upload Issues**
   - Check PHP's `post_max_size` and `upload_max_filesize` in php.ini
   - Ensure upload directories exist with proper permissions

### Still having issues?

1. Check the PHP error logs
2. Try runing the databasnitilizatin scripts agai
3. Clear browr cache and ty again

## License

This project is licensed under the MIT License - see the LICENSE file for details. 