# Production Deployment Guide - SIAKAD

## 📋 Overview

Panduan lengkap untuk deploy aplikasi SIAKAD ke production server.

**Target:** Production-ready deployment
**Estimated Time:** 2-3 hours
**Difficulty:** Medium

---

## ✅ Pre-Deployment Checklist

### 1. Server Requirements

#### Minimum Requirements:
- **OS:** Linux (Ubuntu 20.04+ / CentOS 7+) atau Windows Server
- **Web Server:** Apache 2.4+ atau Nginx 1.18+
- **PHP:** 8.1+ (8.2 recommended)
- **Database:** MySQL 5.7+ atau MariaDB 10.3+
- **RAM:** 2GB minimum (4GB recommended)
- **Storage:** 10GB minimum (20GB recommended)
- **SSL Certificate:** Required (Let's Encrypt free)

#### PHP Extensions Required:
```bash
php -m | grep -E 'intl|mbstring|json|mysqlnd|xml|curl|gd|zip|fileinfo'
```

Required extensions:
- ✅ intl
- ✅ mbstring
- ✅ json
- ✅ mysqlnd (or mysqli)
- ✅ xml
- ✅ curl
- ✅ gd
- ✅ zip
- ✅ fileinfo

### 2. Database Preparation

#### Create Production Database:
```sql
-- Login to MySQL
mysql -u root -p

-- Create database
CREATE DATABASE siakad_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create dedicated user (JANGAN GUNAKAN ROOT!)
CREATE USER 'siakad_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';

-- Grant privileges
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER 
ON siakad_production.* TO 'siakad_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Verify
SHOW GRANTS FOR 'siakad_user'@'localhost';

-- Exit
EXIT;
```

#### Test Database Connection:
```bash
mysql -u siakad_user -p siakad_production
```

### 3. SSL Certificate Setup

#### Option A: Let's Encrypt (Free, Recommended)
```bash
# Install Certbot
sudo apt update
sudo apt install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (already configured by certbot)
sudo certbot renew --dry-run
```

#### Option B: Commercial SSL
- Purchase SSL certificate
- Install according to provider instructions
- Configure Apache/Nginx

### 4. File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/siakad

# Set directory permissions
sudo find /var/www/siakad -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/siakad -type f -exec chmod 644 {} \;

# Writable directories
sudo chmod -R 775 /var/www/siakad/writable
sudo chmod -R 775 /var/www/siakad/public/uploads

# Secure sensitive files
sudo chmod 600 /var/www/siakad/.env
sudo chmod 600 /var/www/siakad/app/Config/.security_hash
sudo chmod 600 /var/www/siakad/app/Config/.lic_hash
```

---

## 🚀 Deployment Steps

### STEP 1: Backup Current System (if upgrading)

```bash
# Backup database
mysqldump -u root -p siakad_old > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/siakad_old

# Backup .env
cp /var/www/siakad_old/.env /var/www/siakad_old/.env.backup
```

### STEP 2: Upload Files to Server

#### Option A: FTP/SFTP
```bash
# Using SCP
scp -r siakad/* user@server:/var/www/siakad/

# Using SFTP
sftp user@server
put -r siakad/* /var/www/siakad/
```

#### Option B: Git (Recommended)
```bash
# On server
cd /var/www
git clone https://github.com/yourrepo/siakad.git
cd siakad
git checkout production
```

### STEP 3: Configure Environment

#### Create .env file:
```bash
cd /var/www/siakad
cp env .env
nano .env
```

#### Production .env Configuration:
```env
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'https://yourdomain.com/'
app.indexPage = ''
app.appTimezone = 'Asia/Jakarta'

# SECURITY: Enable in production
app.forceGlobalSecureRequests = true
app.CSPEnabled = true

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = localhost
database.default.database = siakad_production
database.default.username = siakad_user
database.default.password = STRONG_PASSWORD_HERE
database.default.DBDriver = MySQLi
database.default.DBPrefix = 
database.default.port = 3306

# IMPORTANT: Enable persistent connections for better performance
database.default.pConnect = true

#--------------------------------------------------------------------
# ENCRYPTION
#--------------------------------------------------------------------
# Generate new key: php spark key:generate
encryption.key = YOUR_ENCRYPTION_KEY_HERE

#--------------------------------------------------------------------
# SESSION
#--------------------------------------------------------------------
# Use database sessions for better performance
session.driver = 'CodeIgniter\Session\Handlers\DatabaseHandler'
session.cookieName = 'siakad_session'
session.expiration = 7200
session.savePath = 'ci_sessions'
session.matchIP = false
session.timeToUpdate = 300
session.regenerateDestroy = true

# SECURITY: Secure cookies
session.cookieSecure = true
session.cookieSameSite = 'Lax'

#--------------------------------------------------------------------
# LOGGER
#--------------------------------------------------------------------
logger.threshold = 4

#--------------------------------------------------------------------
# CACHE
#--------------------------------------------------------------------
# Use file cache (or Redis if available)
cache.handler = 'file'
cache.storePath = 'writable/cache/'

# If using Redis (recommended for production):
# cache.handler = 'redis'
# cache.redis.host = '127.0.0.1'
# cache.redis.password = null
# cache.redis.port = 6379
# cache.redis.timeout = 0
# cache.redis.database = 0
```

### STEP 4: Install Dependencies

```bash
cd /var/www/siakad

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Clear cache
php spark cache:clear
```

### STEP 5: Database Migration

```bash
# Run migrations
php spark migrate

# Verify migrations
php spark migrate:status

# If importing from backup:
mysql -u siakad_user -p siakad_production < backup.sql

# Then run new migrations only:
php spark migrate
```

### STEP 6: Apply Database Indexes

```bash
# Apply performance indexes
php apply_indexes_simple.php

# Or manually:
mysql -u siakad_user -p siakad_production < add_database_indexes.sql
```

### STEP 7: Generate Security Hashes

```bash
# Generate security hashes
php generate_security_hash.php

# Generate license hash
php generate_license_hash.php

# Verify hashes exist
ls -la app/Config/.security_hash
ls -la app/Config/.lic_hash
```

### STEP 8: Set File Permissions (Again)

```bash
# After file upload, set permissions again
sudo chown -R www-data:www-data /var/www/siakad
sudo find /var/www/siakad -type d -exec chmod 755 {} \;
sudo find /var/www/siakad -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/siakad/writable
sudo chmod -R 775 /var/www/siakad/public/uploads
sudo chmod 600 /var/www/siakad/.env
```

### STEP 9: Configure Web Server

#### Apache Configuration:

Create `/etc/apache2/sites-available/siakad.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    # Redirect to HTTPS
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    DocumentRoot /var/www/siakad/public
    
    <Directory /var/www/siakad/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/siakad_error.log
    CustomLog ${APACHE_LOG_DIR}/siakad_access.log combined
</VirtualHost>
```

Enable site and modules:
```bash
# Enable modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers

# Enable site
sudo a2ensite siakad.conf

# Disable default site
sudo a2dissite 000-default.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

#### Nginx Configuration (Alternative):

Create `/etc/nginx/sites-available/siakad`:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS Server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/siakad/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;
    
    # PHP Processing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ ^/(app|system|writable) {
        deny all;
    }
    
    # Logs
    access_log /var/log/nginx/siakad_access.log;
    error_log /var/log/nginx/siakad_error.log;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/siakad /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### STEP 10: Test Application

```bash
# Test homepage
curl -I https://yourdomain.com

# Should return:
# HTTP/2 200
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
# etc.

# Test login
curl -I https://yourdomain.com/auth/login

# Test database connection
php spark db:table users
```

### STEP 11: Activate License

1. Access: `https://yourdomain.com/activate`
2. Enter license code
3. Verify activation successful
4. Login as admin

### STEP 12: Final Security Check

```bash
# Run security verification
php test_security.php

# Check file permissions
ls -la app/Config/.security_hash
ls -la app/Config/.lic_hash
ls -la .env

# Verify .env is not accessible
curl https://yourdomain.com/.env
# Should return 403 Forbidden

# Verify app directory is not accessible
curl https://yourdomain.com/app/
# Should return 403 Forbidden
```

---

## 🔒 Post-Deployment Security

### 1. Remove Development Files

```bash
# Remove diagnostic scripts
rm -f diagnostic*.php
rm -f test_*.php
rm -f apply_*.php
rm -f generate_*.php
rm -f regenerate_*.php

# Keep only:
# - regenerate_hash_after_update.php (for updates)

# Remove documentation (optional, or move to secure location)
mkdir -p /var/backups/siakad_docs
mv *.md /var/backups/siakad_docs/
mv *.txt /var/backups/siakad_docs/
```

### 2. Secure Database

```sql
-- Remove test accounts
DELETE FROM users WHERE username LIKE 'test%';

-- Change default admin password
UPDATE users SET password = '$2y$10$NEW_HASH_HERE' WHERE username = 'admin';

-- Verify no empty passwords
SELECT username FROM users WHERE password = '' OR password IS NULL;
```

### 3. Configure Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Fail2ban (brute force protection)
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 4. Setup Monitoring

#### Log Rotation:
```bash
# Create logrotate config
sudo nano /etc/logrotate.d/siakad

# Add:
/var/www/siakad/writable/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

#### Cron Jobs:
```bash
# Edit crontab
sudo crontab -e

# Add:
# Clear cache daily at 2 AM
0 2 * * * cd /var/www/siakad && php spark cache:clear

# Backup database daily at 3 AM
0 3 * * * mysqldump -u siakad_user -pPASSWORD siakad_production > /var/backups/siakad_$(date +\%Y\%m\%d).sql

# Delete old backups (keep 7 days)
0 4 * * * find /var/backups/siakad_*.sql -mtime +7 -delete

# Check for expired CBT sessions every 5 minutes
*/5 * * * * cd /var/www/siakad && php spark cbt:cleanup
```

### 5. Setup Backup Strategy

```bash
# Create backup script
sudo nano /usr/local/bin/backup-siakad.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/siakad"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u siakad_user -pPASSWORD siakad_production | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/siakad \
    --exclude='/var/www/siakad/writable/cache' \
    --exclude='/var/www/siakad/writable/logs'

# Delete old backups (keep 30 days)
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-siakad.sh

# Add to crontab (daily at 1 AM)
0 1 * * * /usr/local/bin/backup-siakad.sh
```

---

## 📊 Performance Optimization

### 1. Enable OPcache

```bash
# Edit PHP config
sudo nano /etc/php/8.1/apache2/php.ini

# Add/uncomment:
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

# Restart Apache
sudo systemctl restart apache2
```

### 2. Configure MySQL for Performance

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Add under [mysqld]:
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 64M
max_connections = 200

# Restart MySQL
sudo systemctl restart mysql
```

### 3. Setup Redis (Optional, Recommended)

```bash
# Install Redis
sudo apt install redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf

# Set:
maxmemory 256mb
maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis

# Update .env
cache.handler = 'redis'
cache.redis.host = '127.0.0.1'
cache.redis.port = 6379
```

---

## ✅ Production Checklist

### Pre-Launch:
- [ ] SSL certificate installed and working
- [ ] Database created with dedicated user
- [ ] .env configured for production
- [ ] All migrations run successfully
- [ ] Database indexes applied
- [ ] Security hashes generated
- [ ] File permissions set correctly
- [ ] Web server configured
- [ ] License activated
- [ ] Admin password changed
- [ ] Test accounts removed

### Security:
- [ ] HTTPS enforced (HTTP redirects to HTTPS)
- [ ] Security headers enabled
- [ ] CSP enabled
- [ ] Rate limiting active
- [ ] Firewall configured
- [ ] Fail2ban installed
- [ ] Development files removed
- [ ] .env not accessible via web
- [ ] app/ directory not accessible
- [ ] Error display disabled (CI_ENVIRONMENT=production)

### Performance:
- [ ] OPcache enabled
- [ ] Database optimized
- [ ] Gzip compression enabled
- [ ] Asset optimization enabled
- [ ] Database indexes verified
- [ ] Redis configured (optional)
- [ ] Persistent database connections enabled

### Monitoring:
- [ ] Log rotation configured
- [ ] Backup cron jobs set
- [ ] Monitoring cron jobs set
- [ ] Error logging enabled
- [ ] Access logs enabled

### Testing:
- [ ] Homepage loads correctly
- [ ] Login works
- [ ] Dashboard loads
- [ ] CBT module works
- [ ] File uploads work
- [ ] Database queries fast
- [ ] No console errors
- [ ] Mobile responsive
- [ ] SSL certificate valid

---

## 🚨 Troubleshooting

### Issue 1: 500 Internal Server Error

**Check:**
```bash
# Apache error log
sudo tail -f /var/log/apache2/siakad_error.log

# Application log
sudo tail -f /var/www/siakad/writable/logs/log-*.log

# PHP error log
sudo tail -f /var/log/php8.1-fpm.log
```

**Common causes:**
- Wrong file permissions
- .htaccess issues
- PHP errors
- Database connection failed

### Issue 2: Database Connection Failed

**Check:**
```bash
# Test connection
mysql -u siakad_user -p siakad_production

# Check .env
cat /var/www/siakad/.env | grep database

# Check MySQL is running
sudo systemctl status mysql
```

### Issue 3: SSL Certificate Issues

**Check:**
```bash
# Test SSL
openssl s_client -connect yourdomain.com:443

# Renew certificate
sudo certbot renew

# Check certificate expiry
sudo certbot certificates
```

### Issue 4: Performance Issues

**Check:**
```bash
# Check server load
top
htop

# Check MySQL slow queries
sudo mysql -e "SHOW FULL PROCESSLIST;"

# Check disk space
df -h

# Check memory
free -h
```

---

## 📞 Support & Maintenance

### Regular Maintenance Tasks:

**Daily:**
- Check error logs
- Monitor server resources
- Verify backups completed

**Weekly:**
- Review access logs
- Check for failed login attempts
- Update security patches

**Monthly:**
- Review and optimize database
- Check SSL certificate expiry
- Test backup restoration
- Review user accounts

### Emergency Contacts:

- **Server Admin:** [contact]
- **Database Admin:** [contact]
- **Application Support:** [contact]

### Useful Commands:

```bash
# Restart services
sudo systemctl restart apache2
sudo systemctl restart mysql
sudo systemctl restart redis

# Check logs
sudo tail -f /var/log/apache2/siakad_error.log
sudo tail -f /var/www/siakad/writable/logs/log-*.log

# Clear cache
cd /var/www/siakad && php spark cache:clear

# Run migrations
cd /var/www/siakad && php spark migrate

# Regenerate hashes (after update)
cd /var/www/siakad && php regenerate_hash_after_update.php
```

---

## 🎉 Deployment Complete!

Setelah semua checklist selesai, aplikasi SIAKAD siap digunakan di production!

**Next Steps:**
1. Inform users about new system
2. Provide training if needed
3. Monitor for first few days
4. Collect feedback
5. Plan for regular updates

**Congratulations! 🚀**

---

**Last Updated:** 2026-02-25
**Version:** 2.0.0 Production
**Status:** READY FOR DEPLOYMENT ✅

