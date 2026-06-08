# Troubleshooting: Preview Dokumen Error 404

## Error
```
GET http://localhost:8080/siakad/admin/teachers/viewDocument/1 404 (Not Found)
```

## Penyebab Potensial

### 1. Route Tidak Terdaftar
**Check:**
```bash
php spark routes | grep "viewDocument"
```

**Expected Output:**
```
| GET | admin/teachers/viewDocument/([0-9]+) | \App\Controllers\Admin\Teachers::viewDocument/$1
```

**Fix:** Route sudah terdaftar ✅

### 2. Urutan Route Salah
Route dengan parameter spesifik harus di atas route general.

**Bad:**
```php
$routes->get('teachers/edit/(:num)', ...);  // Ini akan match semua teachers/*
$routes->get('teachers/viewDocument/(:num)', ...);  // Tidak pernah tercapai
```

**Good:**
```php
$routes->get('teachers/viewDocument/(:num)', ...);  // Spesifik dulu
$routes->get('teachers/edit/(:num)', ...);  // General setelahnya
```

**Fix:** Sudah diperbaiki ✅

### 3. Permission Check Gagal
`require_permission('teachers.read')` mungkin tidak ada atau error.

**Fix:** Ganti dengan manual role check ✅

### 4. Method Tidak Ada / Typo
**Check:**
```bash
grep -n "function viewDocument" app/Controllers/Admin/Teachers.php
```

**Fix:** Method ada di baris 617 ✅

### 5. Cache Route
CodeIgniter mungkin cache route lama.

**Fix:**
```bash
# Clear cache
rm -rf writable/cache/*

# Atau restart server
```

### 6. .htaccess Issue
Rewrite rules mungkin tidak benar.

**Check:** `public/.htaccess`
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

### 7. Base URL Salah
**Check:** `app/Config/App.php`
```php
public string $baseURL = 'http://localhost:8080/siakad/';
```

## Langkah Debugging

### Step 1: Test Route Langsung
Buka di browser:
```
http://localhost:8080/siakad/admin/teachers/viewDocument/1
```

**Expected:**
- Jika dokumen ada: File tampil atau download
- Jika dokumen tidak ada: "Dokumen tidak ditemukan"
- Jika tidak login: Redirect ke login atau 403

**Actual:** 404 Not Found

### Step 2: Check Error Log
```bash
# XAMPP
tail -f C:/xampp/apache/logs/error.log

# Linux
tail -f /var/log/apache2/error.log
```

### Step 3: Enable CI4 Debug Mode
`app/Config/Boot/development.php`:
```php
ini_set('display_errors', '1');
error_reporting(E_ALL);
```

`.env`:
```
CI_ENVIRONMENT = development
```

### Step 4: Test dengan File Debug
Buka: `http://localhost:8080/siakad/test_viewdocument.php`

Akan menampilkan:
1. Link test ke viewDocument
2. Check upload path exists
3. List files di folder
4. Check database records

### Step 5: Manual Test Method
Buat file `test_method.php`:
```php
<?php
require 'vendor/autoload.php';

$app = \Config\Services::codeigniter();
$app->initialize();

$controller = new \App\Controllers\Admin\Teachers();
$response = $controller->viewDocument(1);

echo $response->getBody();
```

## Solusi Sementara

Jika route tetap tidak work, gunakan alternatif:

### Alternatif 1: Serve via Public Folder
Buat symlink atau copy file ke public folder:

```php
// In uploadDocument method
$publicPath = FCPATH . 'documents/';
if (!is_dir($publicPath)) {
    mkdir($publicPath, 0755, true);
}
copy($uploadPath . $filename, $publicPath . $filename);
```

Akses langsung:
```javascript
const fileUrl = `<?= base_url('documents') ?>/${filename}`;
```

**Cons:** File accessible tanpa permission check

### Alternatif 2: Data URI
Untuk file kecil (<1MB), encode ke base64:

```php
public function getDocumentData($id) {
    // ... load document ...
    $data = file_get_contents($filePath);
    $base64 = base64_encode($data);
    return $this->response->setJSON([
        'type' => $document['file_type'],
        'data' => 'data:' . $document['file_type'] . ';base64,' . $base64
    ]);
}
```

```javascript
fetch(`/admin/teachers/getDocumentData/${id}`)
    .then(r => r.json())
    .then(data => {
        previewBody.innerHTML = `<iframe src="${data.data}"></iframe>`;
    });
```

**Cons:** Lambat untuk file besar

### Alternatif 3: Proxy via PHP
Buat file `document_proxy.php` di public:

```php
<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1,2,7])) {
    http_response_code(403);
    exit('Access denied');
}

$id = $_GET['id'] ?? 0;
// Load document from DB
// Serve file
```

Akses:
```javascript
const fileUrl = `<?= base_url('document_proxy.php') ?>?id=${id}`;
```

## Checklist Debugging

- [x] Route terdaftar di `php spark routes`
- [x] Urutan route sudah benar (spesifik sebelum general)
- [x] Method viewDocument ada di controller
- [x] Permission check diganti manual role check
- [ ] Test route langsung di browser
- [ ] Check error log Apache/PHP
- [ ] Enable CI4 debug mode
- [ ] Run test_viewdocument.php
- [ ] Clear cache dan restart server
- [ ] Check .htaccess rewrite rules
- [ ] Verify base URL di config

## Next Steps

1. Jalankan `test_viewdocument.php` untuk info debug
2. Test route langsung di browser
3. Check error log
4. Jika masih 404, gunakan alternatif solusi
