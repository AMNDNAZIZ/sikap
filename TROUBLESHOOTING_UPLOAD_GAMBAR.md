# Troubleshooting Upload Gambar Soal CBT

## Masalah yang Dilaporkan

1. **Gambar di "Lihat Soal" tidak ditampilkan**
2. **Upload berhasil (centang hijau) tapi gagal saat disimpan**
3. **Gambar di Edit tidak ditampilkan**
4. **Upload gagal saat paste gambar di Edit**

## Root Cause Analysis

### Kemungkinan Penyebab

1. **Mixed Content (HTTP vs HTTPS)**
   - Browser mengakses dengan HTTPS (`https://localhost`)
   - Base URL di .env menggunakan HTTP (`http://localhost`)
   - Browser memblokir loading image HTTP dari halaman HTTPS

2. **Path Tidak Konsisten**
   - Upload menggunakan path berbeda dengan display
   - URL di database tidak match dengan file fisik

3. **CSRF Token Issue**
   - Token expired atau tidak valid
   - Token tidak dikirim dalam request

4. **Permission Issue**
   - Folder tidak writable
   - File tidak readable

## Solusi yang Diterapkan

### 1. Auto-Detect Protocol

Menambahkan deteksi protocol otomatis di `uploadImage()` dan `processEmbeddedImages()`:

```php
// Detect current protocol
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$currentBaseUrl = base_url();

// Replace protocol in base_url if needed
if (strpos($currentBaseUrl, 'http://') === 0 && $protocol === 'https') {
    $currentBaseUrl = str_replace('http://', 'https://', $currentBaseUrl);
} elseif (strpos($currentBaseUrl, 'https://') === 0 && $protocol === 'http') {
    $currentBaseUrl = str_replace('https://', 'http://', $currentBaseUrl);
}

$fileUrl = rtrim($currentBaseUrl, '/') . '/uploads/soal_images/' . $newName;
```

### 2. Konsistensi Path

Memastikan semua path menggunakan pattern yang sama:
- Upload: `UPLOAD_PATH . 'soal_images/'`
- URL: `base_url() . 'uploads/soal_images/'`
- Physical: `public/uploads/soal_images/`

### 3. CSRF Token

Memastikan CSRF token dikirim dalam setiap upload request:
```javascript
formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
```

## Testing Steps

### Test 1: Check Configuration
```bash
php check_upload_config.php
```

Expected output:
```
✓ UPLOAD_PATH sudah benar: FCPATH . 'uploads/'
✓ Folder public/uploads/soal_images/ writable
✓ uploadImage() menggunakan UPLOAD_PATH dengan benar
```

### Test 2: Test Upload
1. Buka `test_upload_debug.html` di browser
2. Pilih gambar
3. Klik Upload
4. Check hasil:
   - ✓ Upload berhasil
   - ✓ Image loaded
   - ✓ AJAX HEAD request accessible

### Test 3: Check Protocol
1. Buka browser console (F12)
2. Check current URL protocol
3. Pastikan match dengan base URL di .env

### Test 4: Check Database
```bash
php fix_image_urls_simple.php
```

### Test 5: Test Full Flow
1. Login sebagai admin/guru
2. Buka halaman tambah soal
3. Paste gambar dari Word
4. Check browser console untuk error
5. Klik "Simpan Semua Soal"
6. Check apakah berhasil
7. Buka halaman detail bank soal
8. Klik "Lihat" pada soal
9. Check apakah gambar tampil

## Debugging Checklist

### Browser Console (F12)

Check for errors:
- [ ] Mixed Content Warning
- [ ] CORS Error
- [ ] 404 Not Found
- [ ] 403 Forbidden
- [ ] CSRF Token Error

### Network Tab (F12)

Check upload request:
- [ ] Request URL correct
- [ ] Request Method: POST
- [ ] Status Code: 200
- [ ] Response contains URL
- [ ] CSRF token included in FormData

Check image request:
- [ ] Request URL correct
- [ ] Status Code: 200
- [ ] Content-Type: image/*
- [ ] Image size > 0

### Server Error Log

```bash
tail -f writable/logs/log-*.log
```

Look for:
- Upload errors
- File permission errors
- Database errors
- CSRF errors

### Database Check

```sql
-- Check latest questions with images
SELECT id, question_text, raw_text, media_image, has_image 
FROM cbt_questions 
WHERE has_image = 1 
ORDER BY id DESC 
LIMIT 5;

-- Check URL patterns
SELECT id, 
  CASE 
    WHEN question_text LIKE '%https://localhost%' THEN 'HTTPS'
    WHEN question_text LIKE '%http://localhost%' THEN 'HTTP'
    ELSE 'OTHER'
  END as protocol
FROM cbt_questions 
WHERE has_image = 1;
```

### File System Check

```bash
# Windows
dir public\uploads\soal_images
icacls public\uploads\soal_images

# Linux
ls -lah public/uploads/soal_images/
stat public/uploads/soal_images/
```

## Common Issues & Solutions

### Issue 1: Mixed Content Warning

**Symptom:**
```
Mixed Content: The page at 'https://localhost/siakad/...' was loaded over HTTPS, 
but requested an insecure image 'http://localhost/siakad/public/uploads/...'. 
This content should also be served over HTTPS.
```

**Solution:**
1. Update .env to use HTTPS:
   ```
   app.baseURL = 'https://localhost/siakad/'
   ```
2. Or disable HTTPS in browser (not recommended for production)
3. Or use protocol-relative URLs (implemented in fix)

### Issue 2: 404 Not Found

**Symptom:**
```
GET http://localhost/siakad/public/uploads/soal_images/image.png 404 (Not Found)
```

**Solution:**
1. Check if file exists:
   ```bash
   dir public\uploads\soal_images\image.png
   ```
2. Check URL pattern in database
3. Run `fix_image_urls_simple.php`

### Issue 3: 403 Forbidden

**Symptom:**
```
GET http://localhost/siakad/public/uploads/soal_images/image.png 403 (Forbidden)
```

**Solution:**
1. Check folder permissions:
   ```bash
   icacls public\uploads /grant Everyone:F /T
   ```
2. Check .htaccess rules
3. Check Apache configuration

### Issue 4: CSRF Token Mismatch

**Symptom:**
```
The action you requested is not allowed.
```

**Solution:**
1. Check if CSRF token is sent in FormData
2. Check browser console for token value
3. Refresh page to get new token
4. Check CSRF configuration in Filters.php

### Issue 5: Upload Berhasil Tapi Save Gagal

**Symptom:**
- Upload shows green checkmark
- Save shows error message

**Solution:**
1. Check browser console for error
2. Check server error log
3. Check `processEmbeddedImages()` method
4. Check database connection
5. Check if base URL is correct

## Files Modified

1. `app/Config/Constants.php` - Fix UPLOAD_PATH
2. `app/Controllers/Admin/CbtBankSoal.php` - Fix uploadImage() and processEmbeddedImages()
3. `app/Views/admin/cbt/banksoal/tambah_soal.php` - Add CSRF token
4. `app/Views/admin/cbt/banksoal/edit_soal.php` - Add CSRF token

## Testing Tools

1. `check_upload_config.php` - Check configuration
2. `fix_image_urls_simple.php` - Fix URLs in database
3. `test_upload_debug.html` - Test upload and display
4. `debug_upload_complete.php` - Complete diagnostic tool

## Next Steps

1. Test dengan browser yang berbeda (Chrome, Firefox, Edge)
2. Test dengan HTTP dan HTTPS
3. Test dengan file size berbeda
4. Test dengan format gambar berbeda (PNG, JPG, GIF)
5. Monitor error log untuk masalah lain

## Production Checklist

- [ ] Base URL di .env sudah benar (HTTP atau HTTPS)
- [ ] Folder uploads writable (777 atau 755)
- [ ] .htaccess di public/uploads/ ada
- [ ] CSRF protection aktif
- [ ] Error logging aktif
- [ ] Backup folder uploads
- [ ] Test upload dari berbagai browser
- [ ] Test dengan file size besar
- [ ] Test dengan koneksi lambat
- [ ] Monitor disk space

## Support

Jika masih ada masalah:
1. Jalankan semua testing tools
2. Capture screenshot error
3. Copy error log
4. Copy browser console log
5. Copy network tab detail
