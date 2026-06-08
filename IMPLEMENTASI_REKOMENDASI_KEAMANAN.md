# Implementasi Rekomendasi Keamanan & Kinerja

## 🎯 Quick Start Guide

Dokumen ini berisi langkah-langkah praktis untuk mengimplementasikan rekomendasi dari audit keamanan dan kinerja.

---

## 🔴 PHASE 1: Critical Security (Week 1)

### 1. Database Security (30 menit)

#### Step 1: Create Dedicated Database User
```sql
-- Login ke MySQL sebagai root
mysql -u root -p

-- Create user baru
CREATE USER 'siakad_user'@'localhost' IDENTIFIED BY 'SiAkAd2026!SecurePass#';

-- Grant privileges (hanya yang diperlukan)
GRANT SELECT, INSERT, UPDATE, DELETE ON siakad.* TO 'siakad_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Test connection
mysql -u siakad_user -p siakad
```

#### Step 2: Update .env
```env
database.default.username = siakad_user
database.default.password = SiAkAd2026!SecurePass#
```

#### Step 3: Verify
```bash
php spark db:table users
# Should work with new credentials
```

---

### 2. Enable Security Headers (15 menit)

#### Step 1: Update Filters.php
```php
// app/Config/Filters.php

public array $globals = [
    'before' => [
        'maintenance',
        'csrf',
        'license',
        'gzip',
        'passwordchange',
        'secureheaders', // ✅ ADD THIS
    ],
    'after' => [
        'secureheaders', // ✅ ADD THIS TOO
    ],
];
```

#### Step 2: Configure Security Headers
```php
// app/Config/SecureHeaders.php (create if not exists)
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class SecureHeaders extends BaseConfig
{
    public array $headers = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];
}
```

#### Step 3: Test
```bash
curl -I http://localhost/siakad/
# Should see security headers in response
```

---

### 3. Enable Content Security Policy (20 menit)

#### Step 1: Enable CSP
```php
// app/Config/App.php

public bool $CSPEnabled = true; // Change to true
```

#### Step 2: Configure CSP
```php
// app/Config/ContentSecurityPolicy.php

public function __construct()
{
    parent::__construct();
    
    // Default source
    $this->defaultSrc = "'self'";
    
    // Scripts
    $this->scriptSrc = [
        "'self'",
        "'unsafe-inline'", // For inline scripts (try to remove later)
        'cdn.jsdelivr.net',
        'code.jquery.com',
        'cdn.datatables.net',
    ];
    
    // Styles
    $this->styleSrc = [
        "'self'",
        "'unsafe-inline'", // For inline styles (try to remove later)
        'cdn.jsdelivr.net',
        'cdn.datatables.net',
        'fonts.googleapis.com',
    ];
    
    // Images
    $this->imgSrc = [
        "'self'",
        'data:',
        'blob:',
    ];
    
    // Fonts
    $this->fontSrc = [
        "'self'",
        'fonts.gstatic.com',
        'cdn.jsdelivr.net',
    ];
    
    // Frames
    $this->frameSrc = "'self'";
    
    // Connect (AJAX)
    $this->connectSrc = "'self'";
}
```

#### Step 3: Test
```bash
# Open browser console
# Should not see CSP violations
# If violations appear, add sources to whitelist
```

---

### 4. Fix SQL Injection Vulnerabilities (1 hour)

#### Issue: Raw Queries in Updater.php

**Before:**
```php
// app/Controllers/Admin/Updater.php
$db->query("SHOW CREATE TABLE `{$table}`");
$db->query("SELECT * FROM `{$table}`");
```

**After:**
```php
// Whitelist allowed tables
private function getAllowedTables(): array
{
    return [
        'users', 'students', 'teachers', 'classes',
        'subjects', 'attendance', 'grades', 'assessments',
        'teaching_assignments', 'academic_years', 'holidays',
        'agendas', 'announcements', 'materials', 'student_notes',
        'behaviors', 'cbt_bank_soal', 'cbt_questions', 'cbt_test_status',
        'cbt_sessions', 'cbt_answers', 'exam_schedules', 'app_license',
        'landing_sliders', 'landing_links', 'landing_facilities',
        'landing_articles', 'landing_activities', 'changelogs',
    ];
}

// Validate table name
private function isValidTable(string $table): bool
{
    return in_array($table, $this->getAllowedTables(), true);
}

// Use in generatePatch()
foreach ($tables as $table) {
    if (!$this->isValidTable($table)) {
        log_message('warning', "Skipping invalid table: {$table}");
        continue;
    }
    
    // Now safe to use
    $createTableQuery = $db->query("SHOW CREATE TABLE `{$table}`")->getRow();
    $rows = $db->query("SELECT * FROM `{$table}`")->getResultArray();
}
```

---

### 5. Input Validation (1 hour)

#### Add Validation Rules to All Controllers

**Example: Students Controller**
```php
// app/Controllers/Admin/Students.php

public function store()
{
    $rules = [
        'nis' => 'required|is_unique[students.nis]|max_length[20]|alpha_numeric',
        'name' => 'required|max_length[255]|regex_match[/^[a-zA-Z\s]+$/]',
        'email' => 'permit_empty|valid_email|max_length[255]',
        'phone' => 'permit_empty|numeric|max_length[15]',
        'address' => 'permit_empty|max_length[500]',
        'birth_date' => 'permit_empty|valid_date[Y-m-d]',
        'gender' => 'required|in_list[L,P]',
        'class_id' => 'required|integer|is_not_unique[classes.id]',
    ];
    
    if (!$this->validate($rules)) {
        return redirect()->back()
            ->withInput()
            ->with('errors', $this->validator->getErrors());
    }
    
    // Sanitize inputs
    $data = [
        'nis' => esc($this->request->getPost('nis')),
        'name' => esc($this->request->getPost('name')),
        'email' => esc($this->request->getPost('email')),
        // ... etc
    ];
    
    $this->studentModel->insert($data);
    
    return redirect()->to('admin/students')
        ->with('success', 'Student added successfully');
}
```

---

### 6. HTTPS Enforcement (5 menit)

#### For Production Only
```php
// app/Config/App.php

public bool $forceGlobalSecureRequests = (ENVIRONMENT === 'production');
```

#### Or use .htaccess
```apache
# .htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

---

## 🟡 PHASE 2: Performance Optimization (Week 2-3)

### 1. Database Indexes (30 menit)

```sql
-- Students table
ALTER TABLE students ADD INDEX idx_nis (nis);
ALTER TABLE students ADD INDEX idx_class_id (class_id);
ALTER TABLE students ADD INDEX idx_status (status);

-- Users table
ALTER TABLE users ADD INDEX idx_username (username);
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_role_id (role_id);

-- Attendance table
ALTER TABLE attendance ADD INDEX idx_date (date);
ALTER TABLE attendance ADD INDEX idx_student_id (student_id);
ALTER TABLE attendance ADD INDEX idx_class_id (class_id);
ALTER TABLE attendance ADD INDEX idx_date_student (date, student_id);

-- Teaching Assignments
ALTER TABLE teaching_assignments ADD INDEX idx_teacher_id (teacher_id);
ALTER TABLE teaching_assignments ADD INDEX idx_class_id (class_id);
ALTER TABLE teaching_assignments ADD INDEX idx_subject_id (subject_id);
ALTER TABLE teaching_assignments ADD INDEX idx_teacher_class (teacher_id, class_id);

-- Grades
ALTER TABLE grades ADD INDEX idx_student_id (student_id);
ALTER TABLE grades ADD INDEX idx_subject_id (subject_id);
ALTER TABLE grades ADD INDEX idx_semester (semester);

-- CBT Sessions
ALTER TABLE cbt_sessions ADD INDEX idx_student_id (student_id);
ALTER TABLE cbt_sessions ADD INDEX idx_test_id (test_id);
ALTER TABLE cbt_sessions ADD INDEX idx_status (status);

-- Verify indexes
SHOW INDEX FROM students;
SHOW INDEX FROM users;
SHOW INDEX FROM attendance;
```

---

### 2. Redis Caching (1 hour)

#### Step 1: Install Redis
```bash
# Windows (via Chocolatey)
choco install redis-64

# Or download from: https://github.com/microsoftarchive/redis/releases

# Start Redis
redis-server

# Test
redis-cli ping
# Should return: PONG
```

#### Step 2: Install PHP Redis Extension
```bash
# Download php_redis.dll for your PHP version
# Copy to php/ext/
# Add to php.ini:
extension=redis
```

#### Step 3: Configure CodeIgniter
```php
// app/Config/Cache.php

public string $handler = 'redis';

public array $redis = [
    'host'     => '127.0.0.1',
    'password' => null,
    'port'     => 6379,
    'timeout'  => 0,
    'database' => 0,
];
```

#### Step 4: Use Caching
```php
// In controllers
$cache = \Config\Services::cache();

// Cache students list
$students = $cache->remember('students_class_' . $classId, 3600, function() use ($classId) {
    return $this->studentModel->where('class_id', $classId)->findAll();
});

// Clear cache when data changes
$cache->delete('students_class_' . $classId);

// Cache views
<?= $this->cache(3600) ?> // Cache for 1 hour
```

---

### 3. Asset Optimization (1 hour)

#### Step 1: Minify CSS/JS
```bash
# Install tools
npm install -g uglify-js clean-css-cli

# Minify JS
uglifyjs public/assets/js/app.js -o public/assets/js/app.min.js

# Minify CSS
cleancss public/assets/css/style.css -o public/assets/css/style.min.css
```

#### Step 2: Use CDN
```html
<!-- Replace local libraries with CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

#### Step 3: Lazy Load Images
```html
<img src="image.jpg" loading="lazy" alt="Description">
```

#### Step 4: Optimize Images
```bash
# Use WebP format
# Compress images before upload
# Use image optimization tools
```

---

### 4. Query Optimization (1 hour)

#### Use Select to Limit Columns
```php
// BAD
$students = $this->studentModel->findAll();

// GOOD
$students = $this->studentModel
    ->select('id, name, nis, class_id')
    ->findAll();
```

#### Use Joins Instead of Multiple Queries
```php
// BAD
$students = $this->studentModel->findAll();
foreach ($students as &$student) {
    $student['class'] = $this->classModel->find($student['class_id']);
}

// GOOD
$students = $this->db->table('students s')
    ->join('classes c', 's.class_id = c.id')
    ->select('s.*, c.name as class_name')
    ->get()
    ->getResultArray();
```

#### Use Pagination
```php
// BAD
$students = $this->studentModel->findAll();

// GOOD
$students = $this->studentModel->paginate(20);
$pager = $this->studentModel->pager;
```

---

## 🟢 PHASE 3: Code Quality (Week 4)

### 1. Add Unit Tests (2 hours)

#### Step 1: Install PHPUnit
```bash
composer require --dev phpunit/phpunit
```

#### Step 2: Create Test
```php
// tests/unit/Models/StudentModelTest.php
<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\StudentModel;

class StudentModelTest extends CIUnitTestCase
{
    protected $studentModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->studentModel = new StudentModel();
    }
    
    public function testFindStudentByNis()
    {
        $student = $this->studentModel->where('nis', '12345')->first();
        $this->assertNotNull($student);
        $this->assertEquals('12345', $student['nis']);
    }
    
    public function testCreateStudent()
    {
        $data = [
            'nis' => '99999',
            'name' => 'Test Student',
            'class_id' => 1,
        ];
        
        $id = $this->studentModel->insert($data);
        $this->assertIsInt($id);
        
        // Cleanup
        $this->studentModel->delete($id);
    }
}
```

#### Step 3: Run Tests
```bash
vendor/bin/phpunit tests/unit/Models/StudentModelTest.php
```

---

### 2. Code Refactoring (2 hours)

#### Extract Common Logic to Helpers
```php
// app/Helpers/common_helper.php

if (!function_exists('format_date')) {
    function format_date($date, $format = 'd-m-Y')
    {
        return date($format, strtotime($date));
    }
}

if (!function_exists('get_user_role_name')) {
    function get_user_role_name($roleId)
    {
        $roles = [
            1 => 'Admin',
            2 => 'Principal',
            3 => 'Teacher',
            4 => 'Parent',
            5 => 'Student',
        ];
        return $roles[$roleId] ?? 'Unknown';
    }
}
```

---

## 📋 VERIFICATION CHECKLIST

### Security
- [ ] Database user created with strong password
- [ ] Security headers enabled
- [ ] CSP enabled and configured
- [ ] SQL injection vulnerabilities fixed
- [ ] Input validation added to all forms
- [ ] HTTPS enforcement enabled (production)
- [ ] Session security configured
- [ ] Error handling improved

### Performance
- [ ] Database indexes added
- [ ] Redis caching implemented
- [ ] Assets minified
- [ ] CDN used for libraries
- [ ] Images optimized
- [ ] Queries optimized
- [ ] Pagination implemented

### Code Quality
- [ ] Unit tests added
- [ ] Code refactored
- [ ] Documentation updated
- [ ] Error handling improved

---

## 🔧 TROUBLESHOOTING

### Issue: CSP Blocking Resources
**Solution:** Add sources to whitelist in ContentSecurityPolicy.php

### Issue: Redis Connection Failed
**Solution:** Check if Redis server is running: `redis-cli ping`

### Issue: Database Indexes Not Working
**Solution:** Run `ANALYZE TABLE table_name` after adding indexes

### Issue: Tests Failing
**Solution:** Check database configuration in phpunit.xml

---

## 📞 SUPPORT

For questions or issues during implementation:
1. Check AUDIT_KEAMANAN_KINERJA_LENGKAP.md
2. Review IMPLEMENTASI_REKOMENDASI_KEAMANAN.md (this file)
3. Check QUICK_GUIDE_SECURITY_IMPROVEMENTS.txt

---

**Document Version:** 1.0
**Last Updated:** 2026-02-25
**Status:** READY FOR IMPLEMENTATION
