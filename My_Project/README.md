# Task Tracking System

A comprehensive task and time tracking system for development teams built with PHP, MySQL, and Bootstrap.

## Features

### User Management
- **3 User Roles**: Admin, Manager, Developer
- **User Registration & Login**: Secure authentication system
- **Profile Management**: Edit personal information and change password
- **Password Reset**: Email-based password recovery
- **Email Verification**: Account verification via email

### Project Management
- **Create & Manage Projects**: Full CRUD operations for projects
- **Project Status Tracking**: Active, Completed, On Hold, Cancelled
- **Project Files**: Upload and manage project documents
- **Project Statistics**: Task counts and completion rates

### Task Management
- **Task Creation & Assignment**: Create tasks and assign to developers
- **Task Status**: Todo, In Progress, Done, Cancelled
- **Priority Levels**: Low, Medium, High
- **Time Tracking**: Built-in timer for tracking work hours
- **Task Comments**: Add comments and updates
- **File Attachments**: Attach files to tasks
- **Deadlines**: Set and track task deadlines

### Reporting & Analytics
- **Dashboard**: Overview of tasks, projects, and time tracking
- **Charts & Graphs**: Visual representation of data
- **CSV Export**: Export reports to CSV format
- **Performance Metrics**: Track team and individual performance
- **Time Reports**: Detailed time tracking reports

### Notifications
- **Real-time Notifications**: Get notified of new tasks and updates
- **Email Notifications**: Email alerts for important events
- **Notification Center**: Centralized notification management

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO, PDO_MySQL, OpenSSL extensions

### Setup Instructions

1. **Clone or Download** the project files to your web root directory

2. **Create Database**
   ```sql
   CREATE DATABASE task_tracking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Database Schema**
   ```bash
   mysql -u username -p task_tracking_system < database/schema.sql
   ```

4. **Configure Database Connection**
   Edit `config/database.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'task_tracking_system');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

5. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 644 .htaccess
   ```

6. **Access the Application**
   Open your browser and navigate to your domain

### Default Login
- **Username**: admin
- **Email**: admin@example.com
- **Password**: password

## File Structure

```
├── assets/                 # CSS, JS, and images
├── auth/                  # Authentication system
├── config/                # Configuration files
├── dashboard/             # Dashboard pages
├── projects/              # Project management
├── tasks/                 # Task management
├── reports/               # Reports and analytics
├── profile/               # User profile management
├── includes/              # Shared components
├── api/                   # API endpoints
├── database/              # Database schema
├── uploads/               # File uploads
└── index.php             # Main entry point
```

## Security Features

- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Token-based protection
- **Password Security**: Bcrypt hashing
- **Session Management**: Secure session handling
- **File Upload Security**: Type and size validation

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Charts**: Chart.js
- **Fonts**: Google Fonts (Sarabun)

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support and questions, please refer to the documentation or create an issue in the repository.

## Changelog

### Version 1.0.0
- Initial release
- Complete user management system
- Project and task management
- Time tracking functionality
- Reporting and analytics
- Notification system
- Responsive design
"# My_Project" 
