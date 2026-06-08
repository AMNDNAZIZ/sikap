# Fix Save Soal - Base URL Error

## Masalah yang Ditemukan

Upload gambar berhasil (ceklis hijau), tapi saat menyimpan soal muncul error:
```
"Gagal - Terjadi kesalahan saat menyimpan soal"
```

### Root Cause

Di method `processEmbeddedImages()` line 862, ada bug dalam pembuatan base URL:

```php
// SALAH - UPLOAD_PATH sudah full path
$baseUrl = base_url(UPLOAD_PATH . 'soal_images/');
```

Karena `UPLOAD_PATH` = `FCPATH . 'uploads/'` (full path), maka hasilnya:
```php
$baseUrl = base_url('/path/to/public/uploads/soal_images/');
// Result: http://localhost/siakad/public/path/to/public/uploads/soal_images/
// ❌ SALAH! Path ganda dan tidak valid
```

### Dampak

1. Upload gambar berhasil (file tersimpan)
2. Gambar muncul di editor dengan ceklis hijau
3. Saat save, `processEmbeddedImages()` gagal convert base64 ke URL
4. URL gambar tidak valid
5. Proses save gagal

## Solusi yang Diterapkan

### 1. Fix Base URL di processEmbeddedImages()

```php
// BEFORE (SALAH)
$baseUrl = base_url(UPLOAD_PATH . 'soal_images/');

// AFTER (BENAR)
$baseUrl = base_url('uploads/soal_images/');
```

Sekarang hasilnya:
```php
$baseUrl = base_url('uploads/soal_images/');
// Result: http://localhost/siakad/public/uploads/soal_images/
// ✓ BENAR!
```

### 2. Tambah Error Handling di saveParsedSoal()

```php
try {
    // Process save...
} catch (\Exception $e) {
    log_message('error', 'Error saveParsedSoal: ' . $e->getMessage());
    return $this->response->setJSON([
        'error' => 'Terjadi kesalahan saat menyimpan soal: ' . $e->getMessage()
    ]);
}
```

Sekarang error message lebih detail untuk debugging.

## Alur Proses Upload & Save

### Upload Flow (Sudah Benar ✓)
```
1. User paste gambar dari Word
2. CKEditor trigger upload adapter
3. AJAX POST ke uploadImage() dengan CSRF token
4. File saved to: public/uploads/soal_images/img_xxx.jpg
5. Return URL: http://localhost/siakad/public/uploads/soal_images/img_xxx.jpg
6. CKEditor display image (ceklis hijau)
```

### Save Flow (Sekarang Sudah Benar ✓)
```
1. User klik "Simpan Semua Soal"
2. AJAX POST raw HTML ke saveParsedSoal()
3. processEmbeddedImages() convert base64 → file
4. Base URL dibuat dengan benar: base_url('uploads/soal_images/')
5. extractQuestionsFromHtml() parse soal
6. insertBatch() save ke database
7. Success message
```

## Files Modified

1. `app/Controllers/Admin/CbtBankSoal.php`
   - Line 862: Fix `$baseUrl` di `processEmbeddedImages()`
   - Line 661-745: Tambah try-catch di `saveParsedSoal()`

## Testing

### Test Case 1: Upload Gambar dari File
1. Buka halaman tambah soal
2. Klik icon upload di toolbar CKEditor
3. Pilih file gambar
4. ✓ Gambar muncul dengan ceklis hijau

### Test Case 2: Upload Gambar dari Word
1. Copy gambar dari Microsoft Word
2. Paste di CKEditor (Ctrl+V)
3. ✓ Gambar muncul dengan ceklis hijau

### Test Case 3: Save Soal dengan Gambar
1. Paste soal dengan gambar
2. Klik "Simpan Semua Soal"
3. ✓ Success message muncul
4. ✓ Redirect ke detail bank soal
5. ✓ Gambar tampil di soal yang tersimpan

### Test Case 4: Save Soal Tanpa Gambar
1. Paste soal tanpa gambar
2. Klik "Simpan Semua Soal"
3. ✓ Success message muncul

## Debugging

Jika masih ada error, check:

### 1. Browser Console (F12)
```javascript
// Tab Console - Lihat error JavaScript
// Tab Network - Filter "saveParsedSoal" untuk detail request
```

### 2. Server Error Log
```bash
tail -f writable/logs/log-2026-02-25.log
```

### 3. Check Database
```sql
-- Check apakah soal tersimpan
SELECT * FROM cbt_questions WHERE bank_id = [ID_BANK] ORDER BY id DESC LIMIT 5;

-- Check media_image field
SELECT id, question_text, media_image, has_image FROM cbt_questions WHERE bank_id = [ID_BANK];
```

### 4. Check Uploaded Files
```bash
# Windows
dir public\uploads\soal_images

# Linux
ls -lah public/uploads/soal_images/
```

## Expected Results

### Success Response
```json
{
  "success": true,
  "message": "Berhasil menyimpan 5 soal PG, 2 soal BS, dan 1 soal esai."
}
```

### Error Response (dengan detail)
```json
{
  "error": "Terjadi kesalahan saat menyimpan soal: [error detail]"
}
```

### Database Record
```sql
{
  "id": 123,
  "bank_id": 5,
  "question_text": "Soal dengan gambar...",
  "media_image": "[\"http://localhost/siakad/public/uploads/soal_images/img_xxx.jpg\"]",
  "has_image": 1,
  ...
}
```

## Summary of All Fixes

### Issue #1: Path Ganda di UPLOAD_PATH ✓ FIXED
```php
// Constants.php
FCPATH . 'public/uploads/' → FCPATH . 'uploads/'
```

### Issue #2: CSRF Token Missing ✓ FIXED
```javascript
// tambah_soal.php & edit_soal.php
formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
```

### Issue #3: Base URL Error ✓ FIXED
```php
// CbtBankSoal.php - processEmbeddedImages()
base_url(UPLOAD_PATH . 'soal_images/') → base_url('uploads/soal_images/')
```

### Issue #4: Error Handling ✓ IMPROVED
```php
// CbtBankSoal.php - saveParsedSoal()
try-catch dengan error message detail
```

## Kesimpulan

✓ Upload gambar berhasil
✓ Save soal berhasil
✓ Gambar tersimpan dengan URL yang benar
✓ Error handling lengkap
✓ Ready untuk production

Semua masalah upload dan save gambar soal CBT sudah teratasi.
