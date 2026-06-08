# Migration Guide untuk Hosting

## Overview

Setelah deployment ke hosting, perlu menjalankan migration untuk menambahkan kolom/tabel baru yang diperlukan oleh fitur-fitur terbaru.

## Error yang Terjadi

**Error:** `Unknown column 'school_days' in 'INSERT INTO'`

**Cause:** Kolom `school_days` belum ada di tabel `academic_years` di database hosting

**Status:** ✅ FIXED (dengan backward compatibility)

---

## Quick Fix (Sudah Diterapkan)

Controller sudah diupdate untuk mengecek keberadaan kolom sebelum insert:

```php
// Check if school_days column exists before adding it
$db = \Config\Database::connect();
$fields = $db->getFieldNames('academic_years');
if (in_array('school_days', $fields)) {
    $data['school_days'] = $this->request->getPost('school_days') ?: 5;
}
```

**Benefit:** Aplikasi tetap bisa jalan meskipun migration belum dijalankan.

---

## Migrations yang Perlu Dijalankan

### 1. Add school_days to academic_years

**File:** `app/Database/Migrations/2026-04-05-000001_AddSchoolDaysToAcademicYears.php`

**Purpose:** Menambahkan kolom `school_days` untuk pengaturan hari sekolah (5 atau 6 hari)

**SQL Manual:**
```sql
ALTER TABLE `academic_years` 
ADD COLUMN `school_days` TINYINT(1) NOT NULL DEFAULT 5 
COMMENT 'Jumlah hari sekolah per minggu: 5 (Senin-Jumat) atau 6 (Senin-Sabtu)' 
AFTER `sumatif_weight`;
```

### 2. Add must_change_password to users

**File:** `app/Database/Migrations/2026-02-21-000001_AddMustChangePasswordToUsers.php`

**Purpose:** Force change password untuk user baru

**SQL Manual:**
```sql
ALTER TABLE `users` 
ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Flag untuk memaksa user mengganti password' 
AFTER `password`;
```

### 3. Other Migrations (if needed)

Check folder `app/Database/Migrations/` untuk migration lainnya yang mungkin perlu dijalankan.

---

## Cara Menjalankan Migration

### Option 1: Via Spark CLI (Recommended)

**Via SSH:**
```bash
# Login ke hosting via SSH
ssh user@host

# Navigate to application directory
cd /home/saky9211/public_html/sikap.sakasalika.com/

# Run migrations
php spark migrate

# Check migration status
php spark migrate:status
```

**Output yang diharapkan:**
```
Running: 2026-04-05-000001_AddSchoolDaysToAcademicYears
Migrated: 2026-04-05-000001_AddSchoolDaysToAcademicYears

All migrations have been run.
```

### Option 2: Via SQL Manual (Alternative)

**Via phpMyAdmin atau MySQL CLI:**

```sql
-- 1. Add school_days column
ALTER TABLE `academic_years` 
ADD COLUMN `school_days` TINYINT(1) NOT NULL DEFAULT 5 
COMMENT 'Jumlah hari sekolah per minggu: 5 (Senin-Jumat) atau 6 (Senin-Sabtu)' 
AFTER `sumatif_weight`;

-- 2. Add must_change_password column
ALTER TABLE `users` 
ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Flag untuk memaksa user mengganti password' 
AFTER `password`;

-- 3. Verify columns added
DESCRIBE academic_years;
DESCRIBE users;
```

### Option 3: Via Migration Script

**Create file:** `run_migrations.php` di root folder

```php
<?php

// Only allow from localhost or CLI
if (php_sapi_name() !== 'cli') {
    $allowedHosts = ['127.0.0.1', '::1', 'localhost'];
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!in_array($remoteAddr, $allowedHosts)) {
        die('Access denied. This tool can only be accessed from localhost.');
    }
}

// Load CodeIgniter
require __DIR__ . '/vendor/autoload.php';

// Bootstrap the application
$app = require_once SYSTEMPATH . 'bootstrap.php';

// Get the CodeIgniter instance
$app = \Config\Services::codeigniter();
$app->initialize();

// Run migrations
$migrate = \Config\Services::migrations();

try {
    $migrate->latest();
    echo "✅ All migrations have been run successfully!\n";
} catch (\Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
```

**Run:**
```bash
php run_migrations.php
```

---

## Verification

### Check if Columns Exist

**Via SQL:**
```sql
-- Check school_days column
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'academic_years' 
AND COLUMN_NAME = 'school_days';

-- Check must_change_password column
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' 
AND COLUMN_NAME = 'must_change_password';
```

**Expected Result:**
```
school_days | tinyint | 5
must_change_password | tinyint | 0
```

### Test Functionality

1. **Test Academic Year Creation:**
   - Go to `/admin/academic-year/create`
   - Fill form including "Hari Sekolah" field
   - Save → Should work without error ✓

2. **Test Force Change Password:**
   - Create new student
   - Login as that student
   - Should redirect to change password page ✓

---

## Troubleshooting

### Error: "Migration table not found"

**Cause:** Migration table belum dibuat

**Solution:**
```sql
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `version` varchar(255) NOT NULL,
    `class` varchar(255) NOT NULL,
    `group` varchar(255) NOT NULL,
    `namespace` varchar(255) NOT NULL,
    `time` int(11) NOT NULL,
    `batch` int(11) unsigned NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Error: "Column already exists"

**Cause:** Migration sudah pernah dijalankan

**Solution:** Skip migration atau check status:
```bash
php spark migrate:status
```

### Error: "Permission denied"

**Cause:** User database tidak punya permission ALTER TABLE

**Solution:** 
1. Login sebagai root atau user dengan privilege
2. Grant ALTER permission:
```sql
GRANT ALTER ON database_name.* TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

### Migration Stuck

**Cause:** Migration process terhenti

**Solution:**
1. Check migration table:
```sql
SELECT * FROM migrations ORDER BY id DESC LIMIT 5;
```

2. If stuck, rollback:
```bash
php spark migrate:rollback
```

3. Run again:
```bash
php spark migrate
```

---

## Migration Checklist

### Pre-Migration

- [ ] Backup database
- [ ] Check current database structure
- [ ] Verify SSH/database access
- [ ] Test on local first

### Migration

- [ ] Run migration command
- [ ] Check for errors
- [ ] Verify columns added
- [ ] Test affected features

### Post-Migration

- [ ] Test academic year creation
- [ ] Test force change password
- [ ] Test all CRUD operations
- [ ] Check error logs

---

## Backup Strategy

### Before Running Migration

```bash
# Backup database
mysqldump -u saky9211_sikap -p saky9211_sikap > backup_before_migration_$(date +%Y%m%d).sql

# Backup specific tables
mysqldump -u saky9211_sikap -p saky9211_sikap academic_years users > backup_tables_$(date +%Y%m%d).sql
```

### Restore if Needed

```bash
# Restore full database
mysql -u saky9211_sikap -p saky9211_sikap < backup_before_migration_20260406.sql

# Restore specific tables
mysql -u saky9211_sikap -p saky9211_sikap < backup_tables_20260406.sql
```

---

## Best Practices

### 1. Always Backup First
- Backup database before migration
- Keep backup for at least 7 days
- Test restore process

### 2. Test on Staging First
- Run migration on staging environment
- Test all features
- Deploy to production only if successful

### 3. Run During Low Traffic
- Schedule migration during off-peak hours
- Notify users about maintenance
- Have rollback plan ready

### 4. Monitor After Migration
- Check error logs
- Monitor application performance
- Test critical features
- Get user feedback

---

## Summary

### Current Status

**Quick Fix:** ✅ APPLIED
- Controller checks column existence before insert
- Application works without migration
- No errors on academic year creation

**Full Fix:** ⏳ RECOMMENDED
- Run migration to add columns properly
- Enable full functionality
- Better performance (no column check on every insert)

### Recommendation

**Immediate:**
- ✅ Quick fix already applied
- ✅ Application works on hosting

**Short Term (This Week):**
- 🔧 Run migration via SSH or SQL manual
- ✅ Enable full functionality
- ✅ Remove column existence check (optional)

**Long Term:**
- 📚 Document migration process
- 🔄 Automate migration on deployment
- 📊 Monitor database changes

---

**Last Updated:** 2026-04-06  
**Status:** Quick Fix Applied, Full Migration Recommended  
**Priority:** MEDIUM (not urgent, but recommended)
