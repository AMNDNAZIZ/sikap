# Solusi Final Upload Gambar Soal CBT

## Masalah yang Ditemukan

**Error 403 Forbidden** saat upload gambar dari CKEditor

```
POST https://localhost/siakad/index.php/admin/cbt/banksoal/uploadImage 403 (Forbidden)
```

## Root Cause

CSRF token tidak valid atau expired karena:
1. Token di-generate saat halaman load
2. Jika halaman dibuka lama, token bisa expired
3. Jika ada multiple upload, token tidak di-refresh

## Solusi yang Diterapkan

### 1. Enhanced Error Logging

Menambahkan console.log untuk debugging di upload adapter:

```javascript
const csrfName = '<?= csrf_token() ?>';
const csrfHash = '<?= csrf_hash() ?>';
formData.append(csrfName, csrfHash);

console.log('Upload with CSRF:', csrfName, '=', csrfHash);
```

### 2. Better Error Messages

```javascript
error: function (xhr) {
  console.error('Upload error:', xhr);
  console.error('Status:', xhr.status, xhr.statusText);
  console.error('Response:', xhr.responseText);
  
  let errorMsg = 'Upload gagal';
  if (xhr.status === 403) {
    errorMsg = 'Upload gagal: CSRF token invalid atau session expired. Silakan refresh halaman.';
  }
  reject(errorMsg);
}
```

### 3. Protocol Auto-Detection

Di `CbtBankSoal.php`:

```php
// Detect current protocol
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$currentBaseUrl = base_url();

// Replace protocol if needed
if (strpos($currentBaseUrl, 'http://') === 0 && $protocol === 'https') {
    $currentBaseUrl = str_replace('http://', 'https://', $currentBaseUrl);
}

$fileUrl = rtrim($currentBaseUrl, '/') . '/uploads/soal_images/' . $newName;
```

## Cara Mengatasi Error 403

### Solusi 1: Refresh Halaman

Jika muncul error 403:
1. Refresh halaman (F5)
2. CSRF token akan di-generate ulang
3. Coba upload lagi

### Solusi 2: Check Login Status

Pastikan sudah login sebagai:
- Admin (role 1)
- Kepala Sekolah (role 2)
- Guru (role 3)

### Solusi 3: Check Browser Console

1. Buka Developer Tools (F12)
2. Tab Console
3. Lihat log "Upload with CSRF"
4. Pastikan token tidak kosong

### Solusi 4: Clear Browser Cache

1. Clear browser cache
2. Clear cookies
3. Login ulang
4. Coba upload lagi

## Testing Steps

### Step 1: Check Console Log

Setelah paste gambar, check browser console:

```
Upload with CSRF: csrf_test_name = abc123def456...
```

Jika token kosong atau undefined, ada masalah di view.

### Step 2: Check Network Tab

1. Buka Developer Tools (F12)
2. Tab Network
3. Filter: "uploadImage"
4. Check Request Headers:
   - Content-Type: multipart/form-data
   - Cookie: ci_session=...
5. Check Form Data:
   - file: (binary)
   - csrf_test_name: abc123def456...

### Step 3: Check Response

Jika 403:
```
The action you requested is not allowed.
```

Jika 200:
```json
{
  "url": "http://localhost/siakad/public/uploads/soal_images/image.png",
  "location": "http://localhost/siakad/public/uploads/soal_images/image.png"
}
```

## Files Modified

1. `app/Config/Constants.php` - Fix UPLOAD_PATH
2. `app/Controllers/Admin/CbtBankSoal.php` - Fix uploadImage() and processEmbeddedImages()
3. `app/Views/admin/cbt/banksoal/tambah_soal.php` - Enhanced logging
4. `app/Views/admin/cbt/banksoal/edit_soal.php` - Enhanced logging

## Debugging Checklist

- [ ] Login sebagai admin/guru
- [ ] Buka halaman tambah/edit soal
- [ ] Buka browser console (F12)
- [ ] Paste gambar dari Word
- [ ] Check console log untuk CSRF token
- [ ] Check network tab untuk request detail
- [ ] Jika 403, refresh halaman dan coba lagi
- [ ] Check server error log: `writable/logs/log-*.log`

## Common Scenarios

### Scenario 1: First Upload Success, Second Upload Fails

**Cause:** CSRF token tidak di-refresh setelah first upload

**Solution:** 
- Refresh halaman sebelum upload kedua
- Atau implement CSRF token refresh (advanced)

### Scenario 2: Upload Success in Chrome, Fails in Firefox

**Cause:** Browser-specific cookie/session handling

**Solution:**
- Check browser console untuk error spesifik
- Clear browser cache dan cookies
- Test dengan incognito/private mode

### Scenario 3: Upload Fails After Long Idle

**Cause:** Session expired

**Solution:**
- Refresh halaman untuk renew session
- Adjust session timeout di `app/Config/App.php`

## Production Recommendations

### 1. Session Configuration

```php
// app/Config/App.php
public int $sessionExpiration = 7200; // 2 hours
public bool $sessionRegenerateDestroy = false;
```

### 2. CSRF Configuration

```php
// app/Config/Security.php
public int $csrfExpire = 7200; // 2 hours
public bool $csrfRegenerate = true;
```

### 3. Error Handling

Implement user-friendly error messages:
- "Session expired, silakan refresh halaman"
- "Upload gagal, silakan coba lagi"
- "File terlalu besar, maksimal 10MB"

### 4. Monitoring

Monitor error logs untuk pattern:
- Frequent 403 errors → Session timeout too short
- Frequent CSRF errors → Token regeneration issue
- Upload timeouts → File size or network issue

## Next Steps

1. Test upload dengan berbagai skenario
2. Monitor error logs
3. Adjust session/CSRF timeout jika perlu
4. Implement CSRF token refresh untuk multiple uploads
5. Add upload progress indicator
6. Add file size validation before upload

## Summary

✓ Enhanced error logging untuk debugging
✓ Better error messages untuk user
✓ Protocol auto-detection untuk mixed content
✓ Clear instructions untuk mengatasi 403 error

Jika masih ada error 403, langkah pertama adalah **refresh halaman** untuk mendapatkan CSRF token baru.
