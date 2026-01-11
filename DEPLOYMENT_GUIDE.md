# AliveChMS Deployment Guide

## Overview
This guide covers deploying AliveChMS to production environments, including server setup, configuration, security considerations, and maintenance procedures.

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Server Setup](#server-setup)
3. [Application Deployment](#application-deployment)
4. [Database Setup](#database-setup)
5. [Web Server Configuration](#web-server-configuration)
6. [Security Configuration](#security-configuration)
7. [Performance Optimization](#performance-optimization)
8. [Monitoring and Logging](#monitoring-and-logging)
9. [Backup and Recovery](#backup-and-recovery)
10. [Maintenance](#maintenance)

---

## System Requirements

### Minimum Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: 512MB RAM minimum, 2GB recommended
- **Storage**: 1GB minimum, 10GB recommended
- **SSL Certificate**: Required for production

### Recommended Requirements
- **PHP**: 8.1 or higher
- **MySQL**: 8.0 or higher
- **Memory**: 4GB RAM or more
- **Storage**: SSD with 20GB+ available space
- **CPU**: 2+ cores

### PHP Extensions Required
```bash
# Required extensions
php-mysql
php-json
php-mbstring
php-curl
php-xml
php-zip
php-gd
php-intl
php-bcmath

# Optional but recommended
php-opcache
php-redis (if using Redis cache)
php-memcached (if using Memcached)
```

---

## Server Setup

### Ubuntu/Debian Setup

#### 1. Update System
```bash
sudo apt update && sudo apt upgrade -y
```

#### 2. Install PHP and Extensions
```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-json \
    php8.1-mbstring php8.1-curl php8.1-xml php8.1-zip php8.1-gd \
    php8.1-intl php8.1-bcmath php8.1-opcache
```

#### 3. Install MySQL
```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

#### 4. Install Web Server (Nginx)
```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

#### 5. Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### CentOS/RHEL Setup

#### 1. Update System
```bash
sudo yum update -y
```

#### 2. Install PHP and Extensions
```bash
# Enable EPEL and Remi repositories
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Install PHP
sudo yum module enable php:remi-8.1 -y
sudo yum install -y php php-fpm php-mysql php-json php-mbstring \
    php-curl php-xml php-zip php-gd php-intl php-bcmath php-opcache
```

#### 3. Install MySQL
```bash
sudo yum install -y mysql-server
sudo systemctl enable mysqld
sudo systemctl start mysqld
sudo mysql_secure_installation
```

---

## Application Deployment

### 1. Create Application Directory
```bash
sudo mkdir -p /var/www/alivechms
sudo chown -R www-data:www-data /var/www/alivechms
```

### 2. Clone Repository
```bash
cd /var/www/alivechms
git clone <repository-url> .
```

### 3. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 4. Set Permissions
```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/alivechms

# Set directory permissions
sudo find /var/www/alivechms -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/alivechms -type f -exec chmod 644 {} \;

# Make cache and logs writable
sudo chmod -R 775 /var/www/alivechms/cache
sudo chmod -R 775 /var/www/alivechms/logs
```

### 5. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Edit configuration
nano .env
```

#### Production Environment Variables
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=localhost
DB_NAME=alivechms_prod
DB_USER=alivechms_user
DB_PASS=secure_password_here

# Cache
CACHE_DRIVER=file
CACHE_DEFAULT_TTL=3600
CACHE_FALLBACK=true
CACHE_CLEANUP=true

# Security
SESSION_LIFETIME=7200
BCRYPT_ROUNDS=12

# Performance
CACHE_MEMORY_MAX=104857600  # 100MB
CACHE_COMPRESS=true
CACHE_EVENTS=false  # Disable in production for performance
```

---

## Database Setup

### 1. Create Database and User
```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create database
CREATE DATABASE alivechms_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'alivechms_user'@'localhost' IDENTIFIED BY 'secure_password_here';

-- Grant permissions
GRANT ALL PRIVILEGES ON alivechms_prod.* TO 'alivechms_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### 2. Run Migrations
```bash
cd /var/www/alivechms
php migrate.php up
```

### 3. Verify Database Setup
```bash
php -r "
require_once 'core/Database.php';
try {
    \$db = Database::getInstance();
    echo 'Database connection successful\n';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
}
"
```

---

## Web Server Configuration

### Nginx Configuration

#### 1. Create Site Configuration
```bash
sudo nano /etc/nginx/sites-available/alivechms
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/alivechms/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # PHP handling
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_param HTTP_PROXY "";
        fastcgi_param SERVER_NAME $host;
        fastcgi_param HTTPS on;
    }
    
    # Handle all requests through index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(cache|logs|migrations|tests|vendor) {
        deny all;
    }
    
    location ~ \.(env|md|json|lock)$ {
        deny all;
    }
}
```

#### 2. Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/alivechms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

#### 1. Create Virtual Host
```bash
sudo nano /etc/apache2/sites-available/alivechms.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/alivechms/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
    
    # Directory Configuration
    <Directory /var/www/alivechms/public>
        AllowOverride All
        Require all granted
        
        # Enable .htaccess
        Options -Indexes +FollowSymLinks
        
        # PHP Configuration
        <FilesMatch \.php$>
            SetHandler "proxy:unix:/var/run/php/php8.1-fpm.sock|fcgi://localhost"
        </FilesMatch>
    </Directory>
    
    # Deny access to sensitive directories
    <DirectoryMatch "/(cache|logs|migrations|tests|vendor)">
        Require all denied
    </DirectoryMatch>
    
    # Deny access to sensitive files
    <FilesMatch "\.(env|md|json|lock)$">
        Require all denied
    </FilesMatch>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/alivechms_error.log
    CustomLog ${APACHE_LOG_DIR}/alivechms_access.log combined
</VirtualHost>
```

#### 2. Enable Site and Modules
```bash
sudo a2enmod ssl rewrite headers proxy_fcgi
sudo a2ensite alivechms
sudo systemctl reload apache2
```

---

## Security Configuration

### 1. SSL Certificate Setup (Let's Encrypt)
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### 2. Firewall Configuration
```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow ssh

# Allow HTTP and HTTPS
sudo ufw allow 'Nginx Full'

# Check status
sudo ufw status
```

### 3. PHP Security Configuration
```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

```ini
# Security settings
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# File upload limits
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 20
post_max_size = 10M

# Memory and execution limits
memory_limit = 256M
max_execution_time = 30
max_input_time = 60

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

# Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### 4. Database Security
```sql
-- Remove test database
DROP DATABASE IF EXISTS test;

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Remove remote root access
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Flush privileges
FLUSH PRIVILEGES;
```

---

## Performance Optimization

### 1. PHP OPcache Configuration
```bash
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.validate_timestamps=0  # Set to 0 in production
```

### 2. PHP-FPM Optimization
```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

```ini
# Process management
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# Performance
request_terminate_timeout = 30s
rlimit_files = 1024
rlimit_core = 0
```

### 3. MySQL Optimization
```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
# InnoDB settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query cache
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# Connection settings
max_connections = 200
connect_timeout = 10
wait_timeout = 600
interactive_timeout = 600

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### 4. Cache Configuration
```env
# Use file cache for production
CACHE_DRIVER=file
CACHE_DEFAULT_TTL=3600
CACHE_COMPRESS=true
CACHE_CLEANUP=true

# Increase memory limits
CACHE_MEMORY_MAX=104857600  # 100MB
CACHE_FILE_DIR=/var/www/alivechms/cache/data
```

---

## Monitoring and Logging

### 1. Application Logging
```bash
# Create log directory
sudo mkdir -p /var/log/alivechms
sudo chown www-data:www-data /var/log/alivechms

# Configure log rotation
sudo nano /etc/logrotate.d/alivechms
```

```
/var/log/alivechms/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.1-fpm
    endscript
}
```

### 2. System Monitoring
```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Monitor PHP-FPM
sudo systemctl status php8.1-fpm

# Monitor MySQL
sudo systemctl status mysql

# Check disk usage
df -h

# Check memory usage
free -h
```

### 3. Application Health Check
```bash
# Create health check script
sudo nano /usr/local/bin/alivechms-health-check
```

```bash
#!/bin/bash

# Check web server
if ! curl -f -s http://localhost > /dev/null; then
    echo "Web server is down"
    exit 1
fi

# Check database
if ! mysql -u alivechms_user -p'password' -e "SELECT 1" alivechms_prod > /dev/null 2>&1; then
    echo "Database is down"
    exit 1
fi

# Check cache directory
if [ ! -w /var/www/alivechms/cache ]; then
    echo "Cache directory is not writable"
    exit 1
fi

echo "All systems operational"
exit 0
```

```bash
sudo chmod +x /usr/local/bin/alivechms-health-check

# Add to crontab for regular checks
echo "*/5 * * * * /usr/local/bin/alivechms-health-check" | sudo crontab -
```

---

## Backup and Recovery

### 1. Database Backup
```bash
# Create backup script
sudo nano /usr/local/bin/alivechms-backup
```

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/alivechms"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="alivechms_prod"
DB_USER="alivechms_user"
DB_PASS="password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/database_$DATE.sql.gz

# Application backup
tar -czf $BACKUP_DIR/application_$DATE.tar.gz -C /var/www alivechms --exclude='cache/*' --exclude='logs/*'

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
sudo chmod +x /usr/local/bin/alivechms-backup

# Schedule daily backups
echo "0 2 * * * /usr/local/bin/alivechms-backup" | sudo crontab -
```

### 2. Recovery Procedure
```bash
# Restore database
gunzip -c /var/backups/alivechms/database_YYYYMMDD_HHMMSS.sql.gz | mysql -u alivechms_user -p alivechms_prod

# Restore application files
cd /var/www
sudo tar -xzf /var/backups/alivechms/application_YYYYMMDD_HHMMSS.tar.gz

# Set permissions
sudo chown -R www-data:www-data /var/www/alivechms
sudo chmod -R 775 /var/www/alivechms/cache
sudo chmod -R 775 /var/www/alivechms/logs
```

---

## Maintenance

### 1. Regular Maintenance Tasks

#### Daily Tasks
```bash
# Check system resources
df -h
free -h

# Check error logs
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.1-fpm.log

# Clean cache if needed
cd /var/www/alivechms
php -r "require_once 'core/Cache.php'; echo Cache::cleanup() . ' expired entries cleaned\n';"
```

#### Weekly Tasks
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Analyze MySQL performance
mysql -u root -p -e "SHOW PROCESSLIST;"
mysql -u root -p -e "SHOW ENGINE INNODB STATUS\G"

# Check disk usage
du -sh /var/www/alivechms/*
du -sh /var/backups/alivechms/*
```

#### Monthly Tasks
```bash
# Review security updates
sudo apt list --upgradable

# Analyze slow query log
sudo mysqldumpslow /var/log/mysql/slow.log

# Review access logs for security issues
sudo grep "404\|403\|500" /var/log/nginx/access.log | tail -100
```

### 2. Update Procedure
```bash
# 1. Backup current installation
/usr/local/bin/alivechms-backup

# 2. Put site in maintenance mode
echo "Site under maintenance" > /var/www/alivechms/public/maintenance.html

# 3. Update application
cd /var/www/alivechms
git pull origin main
composer install --no-dev --optimize-autoloader

# 4. Run migrations
php migrate.php up

# 5. Clear cache
rm -rf cache/data/*

# 6. Test application
curl -f http://localhost/

# 7. Remove maintenance mode
rm /var/www/alivechms/public/maintenance.html
```

### 3. Troubleshooting Common Issues

#### High CPU Usage
```bash
# Check PHP-FPM processes
sudo ps aux | grep php-fpm

# Check slow queries
sudo tail -f /var/log/mysql/slow.log

# Monitor system resources
htop
```

#### Memory Issues
```bash
# Check memory usage
free -h

# Check PHP memory usage
sudo grep "memory_limit\|max_execution_time" /etc/php/8.1/fpm/php.ini

# Monitor cache usage
du -sh /var/www/alivechms/cache/*
```

#### Database Connection Issues
```bash
# Check MySQL status
sudo systemctl status mysql

# Check connection limits
mysql -u root -p -e "SHOW VARIABLES LIKE 'max_connections';"
mysql -u root -p -e "SHOW STATUS LIKE 'Threads_connected';"

# Check error logs
sudo tail -f /var/log/mysql/error.log
```

This deployment guide provides comprehensive instructions for setting up AliveChMS in a production environment with proper security, performance optimization, and maintenance procedures.