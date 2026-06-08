# Deployment Guide untuk Hosting

## Error yang Terjadi

**Error:** `Undefined variable $title` di Dashboard controller

**Cause:** Error view dipanggil tanpa passing variable `$title`

**Status:** ✅ FIXED

---

## Langkah-langkah Deployment ke Hosting

### 1. Persiapan File

#### A. Update Environment Configuration

**File:** `.env`

```env
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

app.baseURL = 'https://sikap.sakasalika.com/'
app.forceGlobalSecureRequests = true
app.CSPEnabled = false

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------

database.default.hostname = localhost
database.default.database = saky9211_sikap
database.default.username = saky9211_sikap
database.default.password = [YOUR_DB_PASSWORD]
database.default.DBDriver = MySQLi
database.default.DBPrefix = 
database.default.port = 3306

#--------------------------------------------------------------------
# ENCRYPTION
#--------------------------------------------------------------------

encryption.key = [GENERATE_NEW_KEY]

#--------------------------------------------------------------------
# SESSION
#--------------------------------------------------------------------

session.driver = DatabaseHandler
session.cookieName = ci_session
session.expiration = 7200
session.savePath = ci_sessions
session.matchIP = true
session.timeToUpdate = 300
session.regenerateDestroy = true
```

#### B. Generate Encryption Key

```bash
php spark key:generate
```

Atau manual:
```php
// Generate 32-byte hex key
echo bin2hex(random_bytes(32));
```

#### C. Update Base URL

**File:** `app/Config/App.php`

```php
public string $baseURL = 'https://sikap.sakasalika.com/';
```

### 2. Upload Files ke Hosting

#### A. File Structure di Hosting

```
/home/saky9211/public_html/sikap.sakasalika.com/
├── app/
├── public/
├── system/
├── writable/
├── vendor/
├── .env
├── composer.json
└── spark
```

#### B. Set Permissions

```bash
# Set writable permissions
chmod -R 755 writable/
chmod -R 755 public/

# Set secure permissions for sensitive files
chmod 600 .env
chmod 600 app/Config/.security_hash
chmod 600 app/Config/.lic_hash
```

### 3. Database Setup

#### A. Create Database

Via cPanel atau phpMyAdmin:
1. Create database: `saky9211_sikap`
2. Create user: `saky9211_sikap`
3. Grant all privileges

#### B. Import Database

```bash
mysql -u saky9211_sikap -p saky9211_sikap < database.sql
```

#### C. Create Session Table

```sql
CREATE TABLE IF NOT EXISTS `ci_sessions` (
    `id` varchar(128) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `data` blob NOT NULL,
    KEY `ci_sessions_timestamp` (`timestamp`)
);
```

### 4. Regenerate Security Hash

**IMPORTANT:** Setelah upload ke hosting, regenerate security hash:

```bash
# Via SSH
cd /home/saky9211/public_html/sikap.sakasalika.com/
php generate_security_hash.php

# Via browser (localhost only won't work on hosting)
# Need to temporarily allow remote access or use SSH
```

**Alternative:** Copy `.security_hash` dari local ke hosting:

```bash
# From local
scp app/Config/.security_hash user@host:/home/saky9211/public_html/sikap.sakasalika.com/app/Config/
```

### 5. Configure Web Server

#### A. Apache (.htaccess)

**File:** `public/.htaccess`

```apache
# Disable directory browsing
Options -Indexes

# Rewrite engine
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Redirect to HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Remove index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>

# Deny access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect .env file
<Files .env>
    Order allow,deny
    Deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Set cache headers
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

#### B. Document Root

Set document root ke folder `public`:

```
Document Root: /home/saky9211/public_html/sikap.sakasalika.com/public
```

### 6. Testing

#### A. Check Application

1. Visit: `https://sikap.sakasalika.com/`
2. Should redirect to login page
3. Test login functionality
4. Check dashboard loads correctly

#### B. Check Security

1. Try accessing: `https://sikap.sakasalika.com/.env` → Should be blocked
2. Try accessing: `https://sikap.sakasalika.com/app/` → Should be blocked
3. Check HTTPS is enforced
4. Check CSRF protection works

#### C. Check Logs

```bash
# Check error logs
tail -f writable/logs/log-*.php

# Check Apache error log
tail -f /var/log/apache2/error.log
```

### 7. Troubleshooting

#### Error: "Undefined variable $title"

**Status:** ✅ FIXED in Dashboard.php

**Solution:** Added `$title` to error view data

#### Error: "Undefined variable $exception"

**Status:** ✅ FIXED in Dashboard.php

**Solution:** Created custom error view (`app/Views/errors/custom_error.php`) instead of using exception view

**Files Modified:**
- `app/Controllers/Dashboard.php` - Use custom error view
- `app/Views/errors/custom_error.php` - NEW custom error page

#### Error: "Cache key contains reserved characters"

**Status:** ✅ FIXED in Auth.php

**Solution:** Already fixed by sanitizing IP address

#### Error: "The action you requested is not allowed"

**Status:** ✅ FIXED in Security.php

**Solution:** Already fixed by setting `regenerate = false`

#### Error: "Tampering detected"

**Cause:** Security hash mismatch

**Solution:**
```bash
# Regenerate security hash on hosting
php generate_security_hash.php

# Or copy from local
scp app/Config/.security_hash user@host:/path/to/app/Config/
```

#### Error: "Session table not found"

**Cause:** Session table not created

**Solution:**
```sql
CREATE TABLE IF NOT EXISTS `ci_sessions` (
    `id` varchar(128) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `data` blob NOT NULL,
    KEY `ci_sessions_timestamp` (`timestamp`)
);
```

#### Error: "Database connection failed"

**Cause:** Wrong database credentials

**Solution:** Check `.env` file:
```env
database.default.hostname = localhost
database.default.database = saky9211_sikap
database.default.username = saky9211_sikap
database.default.password = [CORRECT_PASSWORD]
```

### 8. Post-Deployment Checklist

- [ ] Environment set to `production`
- [ ] Base URL updated to production URL
- [ ] Database credentials configured
- [ ] Encryption key generated
- [ ] Security hash regenerated
- [ ] File permissions set correctly
- [ ] Session table created
- [ ] HTTPS enforced
- [ ] Login tested
- [ ] Dashboard loads correctly
- [ ] CSRF protection works
- [ ] Rate limiting works
- [ ] File upload works
- [ ] Error logs checked

### 9. Monitoring

#### A. Enable Error Logging

**File:** `app/Config/Logger.php`

```php
public int $threshold = 4; // 4 = All messages except debug
```

#### B. Monitor Logs

```bash
# Watch error logs
tail -f writable/logs/log-*.php

# Check for security issues
grep -i "error\|critical\|warning" writable/logs/log-*.php
```

#### C. Performance Monitoring

- Monitor page load times
- Check database query performance
- Monitor server resources (CPU, RAM, Disk)

### 10. Backup Strategy

#### A. Database Backup

```bash
# Daily backup
mysqldump -u saky9211_sikap -p saky9211_sikap > backup_$(date +%Y%m%d).sql

# Automated backup (cron)
0 2 * * * mysqldump -u saky9211_sikap -p[PASSWORD] saky9211_sikap > /backups/sikap_$(date +\%Y\%m\%d).sql
```

#### B. File Backup

```bash
# Backup writable folder (uploads, cache, logs)
tar -czf writable_backup_$(date +%Y%m%d).tar.gz writable/

# Backup entire application
tar -czf sikap_backup_$(date +%Y%m%d).tar.gz /home/saky9211/public_html/sikap.sakasalika.com/
```

---

## Quick Fix Summary

### Fixed Issues

1. ✅ **Undefined variable $title** - Added `$title` to error view
2. ✅ **Cache key error** - Sanitized IP address
3. ✅ **CSRF error** - Set `regenerate = false`
4. ✅ **Security hash** - Regenerated for modified files

### Deployment Status

**Ready for Production:** ✅ YES

**Remaining Tasks:**
- Configure `.env` for production
- Set correct database credentials
- Regenerate security hash on hosting
- Test all functionality

---

## Support

### Common Issues

1. **White screen** → Check error logs
2. **404 errors** → Check .htaccess and document root
3. **Database errors** → Check credentials in .env
4. **Permission errors** → Check writable/ permissions
5. **CSRF errors** → Clear browser cache

### Contact

For deployment issues, check:
1. Error logs: `writable/logs/`
2. Apache logs: `/var/log/apache2/`
3. PHP error log: Check hosting control panel

---

**Last Updated:** 2026-04-06  
**Status:** Ready for Production  
**Version:** 1.0.0
