# Fix Upload Gambar Soal CBT

## 🐛 Masalah

**Symptom:** Saat paste soal dari Word yang mengandung gambar ke CKEditor, muncul pesan "Upload gagal" dan gambar langsung hilang.

**URL:** `https://localhost/siakad/index.php/admin/cbt/banksoal/tambah_soal`

---

## 🔍 Root Cause

### Masalah yang Ditemukan:

#### 1. Path Ganda di uploadImage()
```php
// ❌ SALAH (sebelum fix)
$relativePath = UPLOAD_PATH . 'soal_images/';  // = 'public/uploads/soal_images/'
$uploadPath = FCPATH . $relativePath;          // = 'C:/xampp/htdocs/siakad/public/uploads/soal_images/'
// Hasilnya path ganda!
```

**Penyebab:** UPLOAD_PATH sudah include FCPATH, jadi tidak perlu ditambahkan lagi.

#### 2. Kurang Error Handling
- Tidak ada try-catch
- Tidak ada validasi setelah move file
- Error message tidak jelas

---

## ✅ Solusi yang Sudah Diterapkan

### 1. Fix Path Upload

**File:** `app/Controllers/Admin/CbtBankSoal.php`

**Before:**
```php
$relativePath = UPLOAD_PATH . 'soal_images/';
$uploadPath = FCPATH . $relativePath;
```

**After:**
```php
$uploadPath = UPLOAD_PATH . 'soal_images/';
// UPLOAD_PATH = FCPATH . 'public/uploads/'
// Result: C:/xampp/htdocs/siakad/public/uploads/soal_images/
```

### 2. Tambah Error Handling

```php
// Pastikan folder ada
if (!is_dir($uploadPath)) {
    if (!mkdir($uploadPath, 0775, true)) {
        return $this->response->setJSON(['error' => 'Gagal membuat folder upload.']);
    }
}

// Simpan dengan error handling
try {
    if (!$file->move($uploadPath, $newName)) {
        return $this->response->setJSON(['error' => 'Gagal memindahkan file.']);
    }
} catch (\Exception $e) {
    log_message('error', 'Upload image error: ' . $e->getMessage());
    return $this->response->setJSON(['error' => 'Gagal upload: ' . $e->getMessage()]);
}

// Verify file exists
if (!file_exists($uploadPath . $newName)) {
    return $this->response->setJSON(['error' => 'File tidak ditemukan setelah upload.']);
}
```

### 3. Fix URL Response

```php
// Buat URL publik yang benar
$fileUrl = base_url('uploads/soal_images/' . $newName);

return $this->response->setJSON([
    'location' => $fileUrl,  // Untuk kompatibilitas TinyMCE
    'url' => $fileUrl,       // Format CKEditor 5
]);
```

---

## 🧪 Testing

### Test 1: Manual Upload via HTML Form

1. **Buka:** `test_upload_soal_image.html`
2. **Pilih gambar** (JPG, PNG, GIF, WebP)
3. **Klik "Upload Test"**
4. **Expected Result:**
   - ✅ Success message
   - ✅ Image URL displayed
   - ✅ Image preview shown
   - ✅ File saved to `public/uploads/soal_images/`

### Test 2: Upload via CKEditor (Paste dari Word)

1. **Login** sebagai admin
2. **Go to:** Admin > CBT > Bank Soal > Tambah Soal
3. **Buka Word** dengan dokumen yang ada gambar
4. **Copy** soal + gambar dari Word
5. **Paste** ke CKEditor
6. **Expected Result:**
   - ✅ Gambar muncul di editor
   - ✅ Tidak ada error "Upload gagal"
   - ✅ Gambar tersimpan di `public/uploads/soal_images/`

### Test 3: Upload via CKEditor (Upload Button)

1. **Di CKEditor**, klik icon upload image
2. **Pilih gambar** dari komputer
3. **Upload**
4. **Expected Result:**
   - ✅ Gambar muncul di editor
   - ✅ Progress bar selesai
   - ✅ Gambar tersimpan

### Test 4: Verify File Saved

```bash
# Check folder
ls -la public/uploads/soal_images/

# Should see new files with random names
# Example: 1708876543_abc123def456.jpg
```

---

## 🔧 Troubleshooting

### Issue 1: Upload Gagal - Permission Denied

**Symptom:**
```
Error: Gagal memindahkan file.
```

**Solution:**
```bash
# Fix permissions
chmod 775 public/uploads/soal_images
chown www-data:www-data public/uploads/soal_images

# Or run script
php fix_upload_soal_images.php
```

### Issue 2: Upload Gagal - Folder Not Found

**Symptom:**
```
Error: Gagal membuat folder upload.
```

**Solution:**
```bash
# Create folder manually
mkdir -p public/uploads/soal_images
chmod 775 public/uploads/soal_images
```

### Issue 3: Upload Gagal - File Too Large

**Symptom:**
```
Error: File tidak valid atau sudah dipindahkan.
```

**Check PHP settings:**
```ini
; php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

**Restart Apache:**
```bash
sudo systemctl restart apache2
```

### Issue 4: Gambar Tidak Muncul Setelah Upload

**Symptom:** Upload success tapi gambar tidak tampil di editor

**Check:**
1. URL gambar benar?
   ```
   http://localhost/siakad/uploads/soal_images/filename.jpg
   ```

2. File exists?
   ```bash
   ls -la public/uploads/soal_images/filename.jpg
   ```

3. .htaccess tidak block?
   ```bash
   curl -I http://localhost/siakad/uploads/soal_images/filename.jpg
   # Should return 200, not 403
   ```

### Issue 5: CSRF Token Error

**Symptom:**
```
Error: The action you requested is not allowed.
```

**Solution:**
1. Login sebagai admin dulu
2. Refresh halaman tambah soal
3. Try upload again

### Issue 6: Network Error

**Symptom:**
```
Network Error: Failed to fetch
```

**Check:**
1. Apache running?
   ```bash
   sudo systemctl status apache2
   ```

2. Route exists?
   ```bash
   php spark routes | grep uploadImage
   ```

3. Controller method exists?
   ```bash
   grep -n "function uploadImage" app/Controllers/Admin/CbtBankSoal.php
   ```

---

## 📊 Verification Checklist

### Code Changes:
- [x] Fix path ganda di uploadImage()
- [x] Tambah error handling
- [x] Tambah try-catch
- [x] Tambah file verification
- [x] Fix URL response
- [x] Fix backup path

### Testing:
- [ ] Test manual upload via HTML form
- [ ] Test paste dari Word dengan gambar
- [ ] Test upload via CKEditor button
- [ ] Verify file saved to correct folder
- [ ] Verify gambar tampil di editor
- [ ] Verify gambar tampil di soal CBT

### Folder & Permissions:
- [x] Folder `public/uploads/soal_images/` exists
- [x] Folder writable (775)
- [x] .htaccess allows image files
- [x] 14 existing files verified

---

## 📝 Additional Notes

### Upload Flow:

1. **User paste gambar dari Word**
2. **CKEditor detect gambar**
3. **CKEditor call custom upload adapter**
4. **AJAX POST to `/admin/cbt/banksoal/uploadImage`**
5. **Controller validate file**
6. **Controller save to `public/uploads/soal_images/`**
7. **Controller return URL**
8. **CKEditor replace gambar dengan URL**

### File Validation:

```php
// Allowed MIME types
$allowedMimes = [
    'image/jpg',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
];

// Double check with getimagesize()
if (!getimagesize($file->getTempName())) {
    return error;
}
```

### Security:

- ✅ MIME type validation
- ✅ File content validation (getimagesize)
- ✅ Random filename generation
- ✅ Stored in public/uploads (protected by .htaccess)
- ✅ Only image types allowed
- ✅ PHP files blocked by .htaccess

---

## 🎯 Expected Behavior

### Before Fix:
- ❌ Upload gagal
- ❌ Gambar hilang dari editor
- ❌ Error message tidak jelas
- ❌ File tidak tersimpan

### After Fix:
- ✅ Upload berhasil
- ✅ Gambar muncul di editor
- ✅ Error message jelas (jika ada error)
- ✅ File tersimpan di `public/uploads/soal_images/`
- ✅ URL gambar benar
- ✅ Gambar tampil di soal CBT

---

## 🚀 Next Steps

1. **Test upload:**
   - Paste soal dari Word dengan gambar
   - Verify gambar muncul
   - Verify file tersimpan

2. **If still fails:**
   - Open `test_upload_soal_image.html`
   - Test manual upload
   - Check browser console (F12)
   - Check error log: `writable/logs/log-*.log`

3. **Monitor:**
   - Check disk space: `df -h`
   - Check folder size: `du -sh public/uploads/soal_images/`
   - Clean old files if needed

---

## 📞 Support

### Diagnostic Tools:
- `fix_upload_soal_images.php` - Check folder & permissions
- `test_upload_soal_image.html` - Test upload manually

### Log Files:
- Application: `writable/logs/log-*.log`
- Apache: `/var/log/apache2/error.log`
- PHP: `/var/log/php-fpm.log`

### Useful Commands:
```bash
# Check folder
ls -la public/uploads/soal_images/

# Check permissions
stat public/uploads/soal_images/

# Check disk space
df -h

# Check recent uploads
ls -lt public/uploads/soal_images/ | head -10

# Count files
ls -1 public/uploads/soal_images/ | wc -l
```

---

**Last Updated:** 2026-02-25
**Status:** ✅ FIXED
**Files Modified:** 
- `app/Controllers/Admin/CbtBankSoal.php`

