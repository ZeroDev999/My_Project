# Task Tracking System - Production Deployment Guide

## Deployment to https://nc-it-projects.com/My_Project/

This guide will help you deploy the Task Tracking System to your hosting environment.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- SSL certificate (for HTTPS)
- FTP/SFTP access to your hosting account

## Step 1: Upload Files

1. Upload all files from your local project to your hosting account
2. Ensure the following directory structure is maintained:
   ```
   /My_Project/
   ├── api/
   ├── assets/
   ├── auth/
   ├── config/
   ├── dashboard/
   ├── database/
   ├── includes/
   ├── logs/
   ├── profile/
   ├── projects/
   ├── reports/
   ├── settings/
   ├── tasks/
   ├── uploads/
   ├── users/
   ├── .htaccess (minimal security settings only)
   ├── index.php
   ├── setup.php
   └── DEPLOYMENT_GUIDE.md
   ```

## Step 2: .htaccess File (Optional)

The included `.htaccess` file contains only essential security settings:
- Blocks access to sensitive files (config, database, logs)
- Prevents PHP execution in uploads directory
- No complex rewrite rules that might cause loading issues

**If your website doesn't load:** You can safely delete the `.htaccess` file - the website will work without it.

## Step 3: Set Directory Permissions

Set the following permissions on your hosting:

```bash
# Set permissions for directories
chmod 755 /path/to/your/project
chmod 755 uploads/
chmod 755 logs/
chmod 755 assets/
chmod 755 assets/imgs/

# Set permissions for files
chmod 644 *.php
chmod 644 .htaccess
chmod 644 config/*.php
```

## Step 4: Configure Environment Variables

Set the following environment variables in your hosting control panel or `.env` file:

### Database Configuration
```
DB_HOST=localhost:3306
DB_NAME=ncitproj_ikkyuz
DB_USER=ncitproj_ikkyuz
DB_PASS=it2_ikkyuz
```

### Email Configuration
```
SMTP_HOST=your_smtp_server
SMTP_PORT=587
SMTP_USERNAME=your_email@thsv25.hostatom.com
SMTP_PASSWORD=your_email_password
FROM_EMAIL=noreply@thsv25.hostatom.com
```

### Optional Environment Variables
```
ENVIRONMENT=production
LOG_LEVEL=INFO
CACHE_ENABLED=true
BACKUP_ENABLED=true
```

## Step 5: Database Configuration

The database has been pre-configured with the following credentials:
- **Host:** localhost:3306
- **Database Name:** ncitproj_ikkyuz
- **Username:** ncitproj_ikkyuz
- **Password:** it2_ikkyuz

### Test Database Connection (Optional)

Before proceeding with installation, you can test the database connection by visiting:
`https://nc-it-projects.com/My_Project/test_db_connection.php`

This will verify that your database credentials are working correctly.

## Step 6: Run Installation

1. Navigate to `https://nc-it-projects.com/My_Project/setup.php`
2. The database credentials are pre-filled with your hosting details
3. Click "Install System" to proceed
4. The system will create all necessary tables and insert default data

## Step 7: Default Login Credentials

After installation, you can log in with:
- **Username:** admin
- **Email:** admin@example.com
- **Password:** password

**⚠️ IMPORTANT:** Change these credentials immediately after first login!

## Step 8: Configure Email Settings

1. Log into the admin panel
2. Go to Settings > System Settings
3. Configure your SMTP settings
4. Test email functionality

## Step 9: Security Checklist

### ✅ Completed Automatically:
- [x] File access restrictions (via .htaccess)
- [x] Input sanitization
- [x] CSRF protection
- [x] Session security

### ⚠️ Manual Steps Required:
- [ ] Change default admin password
- [ ] Update admin email address
- [ ] Configure backup settings
- [ ] Set up SSL certificate (if not already done)
- [ ] Configure HTTPS redirects (if needed)
- [ ] Configure firewall rules (if available)
- [ ] Set up monitoring/logging

## Step 10: Performance Optimization

### Enable Caching
1. Ensure `CACHE_ENABLED=true` in environment variables
2. Configure your hosting provider's caching system

### Database Optimization
1. Enable MySQL query cache
2. Optimize database indexes
3. Regular database maintenance

### File Optimization
1. Compress images in `/assets/imgs/`
2. Enable Gzip compression (if supported by your hosting)
3. Use CDN for static assets (optional)

## Step 11: Backup Configuration

1. Set up automated database backups
2. Configure file backups for uploads directory
3. Test backup restoration process

## Troubleshooting

### Common Issues:

#### 1. Database Connection Failed
- Verify database credentials
- Check if database server is accessible
- Ensure MySQL service is running

#### 2. Permission Denied Errors
- Check file/directory permissions
- Ensure web server has read/write access
- Verify .htaccess is working correctly

#### 3. Email Not Working
- Verify SMTP credentials
- Check if your hosting allows SMTP connections
- Test with a simple email script

#### 4. File Upload Issues
- Check uploads directory permissions (755)
- Verify PHP upload limits
- Check .htaccess file restrictions

#### 5. SSL/HTTPS Issues
- Verify SSL certificate is installed
- Check .htaccess HTTPS redirect rules
- Test mixed content warnings

#### 6. URL/Path Issues
- Verify BASE_URL in config/config.php matches your domain
- Check that all navigation links use BASE_URL constant
- Test URLs using the test_urls.php script
- Verify subdirectory structure is correct
- If .htaccess causes issues, you can delete it - the site will work without it

### Error Logs
Check the following locations for error logs:
- `/logs/error.log` - Application errors
- `/logs/application.log` - General application logs
- Hosting provider's error logs

## Maintenance

### Regular Tasks:
1. **Weekly:** Check error logs and system performance
2. **Monthly:** Update passwords and review user access
3. **Quarterly:** Database optimization and cleanup
4. **Annually:** Security audit and system updates

### Monitoring:
- Set up uptime monitoring
- Monitor disk space usage
- Track database performance
- Monitor error rates

## Support

For technical support or questions about deployment:
1. Check the error logs first
2. Review this deployment guide
3. Contact your hosting provider for server-related issues
4. Check the application documentation

## Additional Notes

- The system is configured for Thai language support
- All file uploads are restricted to specific file types
- The system includes built-in activity logging
- User sessions are secured with proper timeout settings
- The application is optimized for mobile devices

## Version Information

- **Application Version:** 1.0
- **PHP Requirements:** 7.4+
- **MySQL Requirements:** 5.7+
- **Last Updated:** September 2024

---

**Success!** Your Task Tracking System should now be running on https://nc-it-projects.com/My_Project/

Remember to:
1. Change default passwords
2. Configure email settings
3. Set up regular backups
4. Monitor system performance
