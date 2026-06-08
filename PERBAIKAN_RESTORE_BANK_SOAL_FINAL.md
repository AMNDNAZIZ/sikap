# Perbaikan Restore Bank Soal - Final

## Masalah yang Diperbaiki

1. **Restore gagal tanpa error message yang jelas**
2. **Tidak ada validasi field yang proper**
3. **Tidak ada transaction handling**
4. **Tidak ada logging untuk debugging**

## Solusi yang Diimplementasikan

### 1. Transaction Handling
```php
$db->transStart();
try {
    // ... proses restore ...
    $db->transComplete();
    if ($db->transStatus() === false) {
        throw new \Exception('Transaksi database gagal.');
    }
} catch (\Exception $e) {
    $db->transRollback();
    log_message('error', 'Restore error: ' . $e->getMessage());
    return redirect()->back()->with('error', 'Gagal restore: ' . $e->getMessage());
}
```

### 2. Field Filtering untuk Bank Soal
```php
// Filter bank data to only allowed fields
$bankAllowedFields = $this->bankModel->allowedFields;
$filteredBankData = [];
foreach ($bankData as $key => $value) {
    if (in_array($key, $bankAllowedFields)) {
        $filteredBankData[$key] = $value;
    }
}
```

### 3. Ownership Assignment

#### Untuk Guru:
```php
if ($context['is_teacher'] && $context['teacher_id']) {
    $bankData['teacher_id'] = $context['teacher_id'];
}
```
**Artinya**: Guru yang restore menjadi pemilik bank soal, TIDAK PEDULI siapa pembuat aslinya.

#### Untuk Admin:
```php
elseif ($context['is_admin']) {
    // Admin bisa restore dengan teacher_id asli atau null
    if (!isset($bankData['teacher_id'])) {
        $bankData['teacher_id'] = null;
    }
}
```
**Artinya**: Admin bisa restore dengan mempertahankan teacher_id asli dari backup.

### 4. Error Handling & Logging
```php
// Log success
log_message('info', "Restore success: Bank ID={$newBankId}, Code={$code}, Questions={$count}");

// Log errors
log_message('error', 'Restore error: ' . $e->getMessage());
log_message('warning', 'Restore: Skipping question without question_text');
```

### 5. Validation
```php
// Validasi bank soal berhasil disimpan
if (!$newBankId) {
    throw new \Exception('Gagal menyimpan bank soal ke database.');
}

// Validasi question_text wajib ada
if (empty($filteredData['question_text'])) {
    log_message('warning', 'Restore: Skipping question without question_text');
    continue;
}
```

## Fitur Restore Bank Soal

### Aturan Ownership

| User Role | Restore dari Backup Guru Lain | Teacher ID Hasil Restore |
|-----------|-------------------------------|--------------------------|
| **Guru A** | ✅ BISA | Guru A (yang restore) |
| **Guru B** | ✅ BISA | Guru B (yang restore) |
| **Admin** | ✅ BISA | Teacher ID asli dari backup (atau null) |

### Contoh Skenario

#### Skenario 1: Guru A Restore Backup Guru B
```
Backup: Bank Soal Matematika (teacher_id = Guru B)
Restore by: Guru A
Result: Bank Soal Matematika_RESTORED_1 (teacher_id = Guru A)
```

#### Skenario 2: Admin Restore Backup Guru A
```
Backup: Bank Soal Fisika (teacher_id = Guru A)
Restore by: Admin
Result: Bank Soal Fisika_RESTORED_1 (teacher_id = Guru A)
```

#### Skenario 3: Guru A Restore Backup Admin
```
Backup: Bank Soal Kimia (teacher_id = null)
Restore by: Guru A
Result: Bank Soal Kimia_RESTORED_1 (teacher_id = Guru A)
```

## Proses Restore

### 1. Upload File ZIP
- User upload file backup (.zip)
- Validasi file valid dan bisa dibuka

### 2. Extract Data
- Baca `data.json` dari ZIP
- Validasi struktur JSON (harus ada `bank` dan `questions`)

### 3. Prepare Bank Soal
- Hapus `id` dari data bank
- Set `teacher_id` sesuai aturan ownership
- Handle collision kode (tambah suffix `_RESTORED_n`)
- Set `is_active = 0` (non-aktif dulu)
- Filter field sesuai `allowedFields`

### 4. Extract Media Files
- Extract semua file media dari ZIP
- Skip `data.json`
- Sanitize path (prevent directory traversal)
- Copy ke `writable/uploads/`

### 5. Insert Questions
- Loop semua soal dari backup
- Set `bank_id` ke bank soal baru
- Normalize URL (ganti base_url lama dengan baru)
- Filter field sesuai `allowedFields`
- Skip soal tanpa `question_text`
- Insert ke database

### 6. Success Message
```
Bank soal berhasil di-restore dengan kode: MATEMATIKA_RESTORED_1 (25 soal)
```

## Debugging

### Cek Log File
```bash
# Windows
type writable\logs\log-2026-04-07.php

# Linux/Mac
tail -f writable/logs/log-2026-04-07.php
```

### Log Messages
```
[INFO] Restore success: Bank ID=123, Code=MATEMATIKA_RESTORED_1, Questions=25
[ERROR] Restore error: Unknown column 'invalid_field' in 'field list'
[WARNING] Restore: Skipping question without question_text
[WARNING] Restore: Skipping file with path traversal: ../../../etc/passwd
```

## Testing

### Test 1: Guru Restore Backup Guru Lain
1. Login sebagai Guru A
2. Upload backup bank soal dari Guru B
3. ✅ Expected: Berhasil, teacher_id = Guru A

### Test 2: Guru Restore Backup Admin
1. Login sebagai Guru A
2. Upload backup bank soal dari Admin (teacher_id = null)
3. ✅ Expected: Berhasil, teacher_id = Guru A

### Test 3: Admin Restore Backup Guru
1. Login sebagai Admin
2. Upload backup bank soal dari Guru A
3. ✅ Expected: Berhasil, teacher_id = Guru A (tetap)

### Test 4: Collision Kode
1. Sudah ada bank soal dengan kode "MATEMATIKA"
2. Restore backup dengan kode "MATEMATIKA"
3. ✅ Expected: Berhasil dengan kode "MATEMATIKA_RESTORED_1"

### Test 5: Media Files
1. Backup berisi gambar dan audio
2. Restore backup
3. ✅ Expected: File media ter-copy ke uploads/
4. ✅ Expected: URL di soal sudah ter-update

### Test 6: Invalid Backup
1. Upload file ZIP kosong
2. ✅ Expected: Error "File data.json tidak ditemukan"
3. Upload file ZIP dengan JSON invalid
4. ✅ Expected: Error "Format data.json tidak valid"

## Keamanan

### 1. Path Traversal Prevention
```php
if (strpos($filename, '..') !== false) {
    log_message('warning', 'Restore: Skipping file with path traversal: ' . $filename);
    continue;
}
```

### 2. Field Filtering
```php
// Hanya field yang ada di allowedFields yang disimpan
$filteredData = [];
foreach ($q as $key => $value) {
    if (in_array($key, $allowedFields)) {
        $filteredData[$key] = $value;
    }
}
```

### 3. Transaction Rollback
```php
// Jika ada error, semua perubahan di-rollback
catch (\Exception $e) {
    $db->transRollback();
    return redirect()->back()->with('error', 'Gagal restore: ' . $e->getMessage());
}
```

## Kesimpulan

✅ Guru BISA restore backup dari guru lain
✅ Guru yang restore menjadi pemilik bank soal hasil restore
✅ Admin bisa restore dengan mempertahankan teacher_id asli
✅ Proses restore menggunakan transaction (all or nothing)
✅ Error handling dan logging lengkap
✅ Field filtering untuk keamanan
✅ Path traversal prevention

---

**File Modified**: `app/Controllers/Admin/CbtBankSoal.php`
**Method**: `restore()`
**Date**: April 7, 2026
**Status**: ✅ FIXED
