# Solusi Upload Slider - CMS Sliders

## 📋 Ringkasan Masalah

User melaporkan kegagalan saat pengisian form slider di `http://localhost/siakad/admin/cms/sliders`.

## ✅ Solusi yang Sudah Diimplementasikan

### 1. **Validation Rules** (Controller)
File: `app/Controllers/Admin/Cms/Sliders.php`

#### Store Method (Create)
```php
$rules = [
    'image' => [
        'rules' => 'uploaded[image]|max_size[image,2048]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]',
        'errors' => [
            'uploaded' => 'Gambar harus diupload',
            'max_size' => 'Ukuran gambar maksimal 2MB',
            'is_image' => 'File harus berupa gambar',
            'mime_in' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP'
        ]
    ],
    'title' => 'permit_empty|max_length[255]',
    'order' => 'permit_empty|integer'
];
```

#### Update Method (Edit)
- Image menjadi **optional** (permit_empty)
- Validasi lainnya sama dengan store

### 2. **Error Handling** (Controller)

#### Try-Catch Block
```php
try {
    // Upload logic
    $file->move($uploadPath, $newName);
    $this->sliderModel->insert($data);
    return redirect()->with('success', 'Slider berhasil ditambahkan');
    
} catch (\Exception $e) {
    // Cleanup uploaded file if database fails
    if (isset($newName) && file_exists($uploadPath . '/' . $newName)) {
        unlink($uploadPath . '/' . $newName);
    }
    log_message('error', 'Failed to store slider: ' . $e->getMessage());
    return redirect()->back()->withInput()->with('error', 'Gagal menyimpan slider');
}
```

#### Auto-Create Upload Folder
```php
$uploadPath = FCPATH . 'uploads/sliders';
if (!is_dir($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}
```

### 3. **Error Display** (View)
File: `app/Views/admin/cms/sliders/form.php`

#### Flash Messages
```php
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Error!</strong> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
```

#### Validation Errors
```php
<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Validation Errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
```

#### Old Input Values
```php
<input type="text" name="title" value="<?= old('title', $slider['title'] ?? '') ?>">
```

### 4. **Info Box** (View)
```php
<div class="alert alert-info mt-3">
    <strong>ℹ️ Tips:</strong>
    <ul class="mb-0 mt-2">
        <li>Gunakan gambar dengan resolusi tinggi untuk hasil terbaik</li>
        <li>Ukuran file maksimal 2MB untuk performa optimal</li>
        <li>Slider akan ditampilkan di halaman utama website</li>
        <li>Atur urutan untuk mengontrol posisi tampil slider</li>
    </ul>
</div>
```

### 5. **File Cleanup on Error**
Jika database insert/update gagal, file yang sudah diupload akan dihapus otomatis:
```php
if (isset($newName) && file_exists($uploadPath . '/' . $newName)) {
    unlink($uploadPath . '/' . $newName);
}
```

### 6. **Logging**
Semua error dicatat ke log file:
```php
log_message('error', 'Failed to store slider: ' . $e->getMessage());
```

## 🔍 Diagnostic Tools

### 1. **diagnostic_slider.php**
Script untuk diagnosa lengkap sistem upload slider.

**Cara Pakai:**
```bash
# Akses via browser
http://localhost/siakad/diagnostic_slider.php
```

**Yang Diperiksa:**
- ✅ PHP version & configuration
- ✅ Upload settings (upload_max_filesize, post_max_size)
- ✅ GD library (image processing)
- ✅ Upload folder (exists & writable)
- ✅ Database connection & table structure
- ✅ Controller, model, dan view files
- ✅ Current sliders count

### 2. **test_slider_upload.php**
Script CLI untuk test cepat (masih dalam development).

## 🐛 Common Issues & Solutions

### Issue 1: Error "uploaded[image]"
**Penyebab:**
- File tidak diupload
- Field name salah (bukan "image")
- Form tidak memiliki `enctype="multipart/form-data"`

**Solusi:**
```html
<form method="post" enctype="multipart/form-data">
    <input type="file" name="image" required>
</form>
```

### Issue 2: Error "max_size"
**Penyebab:**
- File terlalu besar (> 2MB)

**Solusi:**
1. Kompres gambar sebelum upload
2. Atau tingkatkan limit di `php.ini`:
```ini
upload_max_filesize = 8M
post_max_size = 8M
```

### Issue 3: Error "is_image" atau "mime_in"
**Penyebab:**
- File bukan gambar
- Format tidak didukung

**Solusi:**
- Upload file dengan format: JPG, JPEG, PNG, atau WEBP
- Pastikan file benar-benar gambar (bukan file lain yang di-rename)

### Issue 4: Error "Gagal memindahkan file"
**Penyebab:**
- Folder `uploads/sliders` tidak writable

**Solusi:**
```bash
# Windows (via File Explorer)
Right-click folder → Properties → Security → Edit → Allow Full Control

# Linux/Mac
chmod 755 uploads/sliders
```

### Issue 5: Table "landing_sliders" not found
**Penyebab:**
- Database belum ter-update

**Solusi:**
```bash
php spark migrate
```

### Issue 6: Anti-Tampering Error
**Penyebab:**
- File hash belum di-regenerate setelah update

**Solusi:**
```bash
php regenerate_hash_after_update.php
```

## 📝 Testing Checklist

### Test Scenario 1: Upload Gambar Valid
- [ ] Buka http://localhost/siakad/admin/cms/sliders/create
- [ ] Upload gambar JPG/PNG < 2MB
- [ ] Isi form (title, description, link, order)
- [ ] Klik "Simpan Slider"
- [ ] **Expected:** Success message, redirect ke index, gambar muncul di list

### Test Scenario 2: Upload Gambar > 2MB
- [ ] Upload gambar > 2MB
- [ ] **Expected:** Validation error "Ukuran gambar maksimal 2MB"

### Test Scenario 3: Upload File Non-Image
- [ ] Upload file PDF/DOC
- [ ] **Expected:** Validation error "File harus berupa gambar"

### Test Scenario 4: Create Tanpa Upload Gambar
- [ ] Submit form tanpa upload gambar
- [ ] **Expected:** Validation error "Gambar harus diupload"

### Test Scenario 5: Update Slider Dengan Gambar Baru
- [ ] Edit slider existing
- [ ] Upload gambar baru
- [ ] **Expected:** Gambar lama terhapus, gambar baru tersimpan

### Test Scenario 6: Update Slider Tanpa Ganti Gambar
- [ ] Edit slider existing
- [ ] Tidak upload gambar baru
- [ ] **Expected:** Gambar lama tetap, data lain ter-update

### Test Scenario 7: Delete Slider
- [ ] Klik tombol delete
- [ ] Confirm delete
- [ ] **Expected:** Slider terhapus dari database, file gambar terhapus

## 🔧 Troubleshooting Steps

### Step 1: Run Diagnostic
```bash
# Via browser
http://localhost/siakad/diagnostic_slider.php
```

### Step 2: Check Log File
```bash
# Windows
type writable\logs\log-2026-02-25.log | findstr /i "slider upload error"

# Linux/Mac
tail -f writable/logs/log-$(date +%Y-%m-%d).log | grep -i "slider\|upload\|error"
```

### Step 3: Verify Database
```sql
-- Check table exists
SHOW TABLES LIKE 'landing_sliders';

-- Check table structure
DESCRIBE landing_sliders;

-- Check current data
SELECT * FROM landing_sliders;
```

### Step 4: Verify Upload Folder
```bash
# Check folder exists
ls -la uploads/sliders

# Check permissions (Linux/Mac)
stat -c "%a %n" uploads/sliders

# Check files
ls -lh uploads/sliders/
```

### Step 5: Test Upload Manually
1. Buka http://localhost/siakad/admin/cms/sliders/create
2. Buka Developer Tools (F12) → Network tab
3. Upload gambar dan submit form
4. Check response:
   - Status 200 = Success
   - Status 302 = Redirect (check redirect URL)
   - Status 500 = Server error (check log file)

## 📊 File Structure

```
siakad/
├── app/
│   ├── Controllers/
│   │   └── Admin/
│   │       └── Cms/
│   │           └── Sliders.php          ← Controller (UPDATED)
│   ├── Models/
│   │   └── LandingSliderModel.php       ← Model
│   └── Views/
│       └── admin/
│           └── cms/
│               └── sliders/
│                   ├── index.php         ← List view
│                   ├── form.php          ← Form view (UPDATED)
│                   ├── create.php        ← Create wrapper
│                   └── edit.php          ← Edit wrapper
├── uploads/
│   └── sliders/                          ← Upload folder (auto-created)
│       ├── 1234567890_abc.jpg
│       └── ...
├── diagnostic_slider.php                 ← Diagnostic tool (NEW)
└── test_slider_upload.php                ← CLI test tool (NEW)
```

## 🎯 Next Steps

1. **Test Upload Functionality**
   - Jalankan diagnostic: http://localhost/siakad/diagnostic_slider.php
   - Test semua scenario di Testing Checklist

2. **Verify Error Handling**
   - Test dengan berbagai kondisi error
   - Pastikan error message tampil dengan jelas
   - Verify file cleanup on error

3. **Check Log Files**
   - Monitor log file saat testing
   - Pastikan error ter-log dengan baik

4. **Production Deployment**
   - Pastikan folder `uploads/sliders` writable
   - Verify PHP upload settings
   - Test di production environment

## 📚 Related Files

- `app/Controllers/Admin/Cms/Sliders.php` - Controller dengan validation & error handling
- `app/Views/admin/cms/sliders/form.php` - Form dengan error display
- `app/Models/LandingSliderModel.php` - Model untuk database operations
- `diagnostic_slider.php` - Diagnostic tool
- `regenerate_hash_after_update.php` - Fix anti-tampering

## 🔐 Security Notes

1. **File Validation**
   - Hanya accept image files (JPG, PNG, WEBP)
   - Max size 2MB
   - Validate MIME type

2. **File Naming**
   - Random filename untuk prevent overwrite
   - Preserve original extension

3. **Folder Permissions**
   - 755 untuk folder
   - 644 untuk files

4. **Error Messages**
   - User-friendly messages
   - Technical details di log file only

## ✅ Summary

Sistem upload slider sudah dilengkapi dengan:
- ✅ Validation rules lengkap
- ✅ Error handling dengan try-catch
- ✅ Auto-create upload folder
- ✅ File cleanup on error
- ✅ Error display di form
- ✅ Logging untuk debugging
- ✅ Diagnostic tools
- ✅ Documentation lengkap

**Status:** READY FOR TESTING
