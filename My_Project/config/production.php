<?php
/**
 * Production Environment Configuration
 * This file contains production-specific settings for hosting on thsv25.hostatom.com
 */

// Production environment flag
define('ENVIRONMENT', 'production');

// Error reporting (disable in production)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session security for production
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Production database settings (these should be set as environment variables)
// Example environment variables that should be set on your hosting:
// DB_HOST=localhost (or your database server)
// DB_NAME=your_database_name
// DB_USER=your_database_user
// DB_PASS=your_database_password

// Production email settings (these should be set as environment variables)
// Example environment variables:
// SMTP_HOST=your-smtp-server.com
// SMTP_PORT=587
// SMTP_USERNAME=your-email@nc-it-projects.com
// SMTP_PASSWORD=your-email-password
// FROM_EMAIL=noreply@nc-it-projects.com

// File upload settings for production
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB max file size
define('UPLOAD_ALLOWED_TYPES', [
    'image/jpeg',
    'image/png', 
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
]);

// Security settings for production
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('PASSWORD_RESET_LIFETIME', 1800); // 30 minutes
define('SESSION_LIFETIME', 28800); // 8 hours

// Rate limiting (requests per minute)
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 60); // 60 requests per minute
define('RATE_LIMIT_WINDOW', 60); // 1 minute window

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour

// Logging settings
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', __DIR__ . '/../logs/application.log');

// Backup settings
define('BACKUP_ENABLED', true);
define('BACKUP_FREQUENCY', 'daily'); // daily, weekly, monthly
define('BACKUP_RETENTION_DAYS', 30);

// Monitoring settings
define('MONITORING_ENABLED', true);
define('PERFORMANCE_MONITORING', true);

// SSL/TLS settings
define('FORCE_HTTPS', true);
define('HSTS_ENABLED', true);
define('HSTS_MAX_AGE', 31536000); // 1 year

// Content Security Policy
define('CSP_ENABLED', true);
define('CSP_REPORT_URI', '/api/csp-report');

// Database optimization
define('DB_PERSISTENT_CONNECTION', false);
define('DB_CONNECTION_TIMEOUT', 30);
define('DB_QUERY_TIMEOUT', 10);

// Memory and execution limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 30);
ini_set('max_input_time', 30);

// File permissions
define('DIRECTORY_PERMISSIONS', 0755);
define('FILE_PERMISSIONS', 0644);

// Maintenance mode
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'System is currently under maintenance. Please try again later.');

// Feature flags
define('FEATURE_REGISTRATION', true);
define('FEATURE_PASSWORD_RESET', true);
define('FEATURE_EMAIL_VERIFICATION', true);
define('FEATURE_FILE_UPLOAD', true);
define('FEATURE_NOTIFICATIONS', true);
define('FEATURE_REPORTS', true);

// API settings
define('API_RATE_LIMIT', 1000); // requests per hour
define('API_VERSION', '1.0');

// Third-party integrations
define('GOOGLE_ANALYTICS_ID', ''); // Add your GA tracking ID
define('RECAPTCHA_SITE_KEY', ''); // Add your reCAPTCHA site key
define('RECAPTCHA_SECRET_KEY', ''); // Add your reCAPTCHA secret key

// Email templates
define('EMAIL_TEMPLATE_PATH', __DIR__ . '/../templates/email/');

// Queue settings (if using background jobs)
define('QUEUE_ENABLED', false);
define('QUEUE_DRIVER', 'database'); // database, redis, etc.

// Cache driver
define('CACHE_DRIVER', 'file'); // file, redis, memcached

// Timezone
date_default_timezone_set('Asia/Bangkok');

// Locale settings
setlocale(LC_ALL, 'th_TH.UTF-8');

// Additional security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (defined('HSTS_ENABLED') && HSTS_ENABLED && isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=' . HSTS_MAX_AGE . '; includeSubDomains');
    }
}
?>
