# Perbaikan Method Restore Bank Soal

## Masalah yang Ditemukan

1. **Route tidak terdaftar**: Route untuk `POST: admin/cbt/banksoal/restore` tidak ada di Routes.php
2. **Tidak ada set teacher_id**: Saat guru restore bank soal, `teacher_id` tidak di-set
3. **Path handling yang aneh**: Ada `UPLOAD_PATH . ''` yang tidak jelas
4. **Tidak ada handling untuk media_audio**: Hanya media_image yang di-normalize URL-nya

## Perbaikan yang Dilakukan

### 1. Tambah Route di Routes.php ✓
```php
$routes->post('cbt/banksoal/restore', 'CbtBankSoal::restore', $auth123);
```

**Lokasi**: Setelah route `backup` di baris ~318

### 2. Set teacher_id untuk Guru ✓
```php
$context = get_cbt_user_context();

// Set teacher_id untuk guru yang restore
if ($context['is_teacher'] && $context['teacher_id']) {
    $bankData['teacher_id'] = $context['teacher_id'];
}
```

**Manfaat**: 
- Guru yang restore bank soal akan menjadi owner dari bank soal tersebut
- Bank soal hasil restore akan muncul di list bank soal guru tersebut
- Konsisten dengan isolasi data CBT

### 2. Perbaiki Path Handling
**Sebelum:**
```php
$targetPath = UPLOAD_PATH . '' . ltrim($cleanFilename, '/');
```

**Sesudah:**
```php
$uploadPath = WRITEPATH . 'uploads/';
$targetPath = $uploadPath . ltrim($cleanFilename, '/');
```

**Manfaat**:
- Path lebih jelas dan konsisten
- Menggunakan WRITEPATH yang merupakan konstanta CodeIgniter 4
- Menghindari error path yang tidak jelas

### 3. Skip data.json dari Proses Media
```php
// Skip data.json
if ($filename === 'data.json') {
    continue;
}
```

**Manfaat**:
- data.json tidak diproses sebagai media file
- Menghindari error atau file yang tidak perlu

### 4. Tambah Handling untuk media_audio
```php
// Normalize media_audio URLs
if (!empty($q['media_audio']) && !empty($oldBaseUrl)) {
    $q['media_audio'] = str_replace($oldBaseUrl, $newBaseUrl, $q['media_audio']);
}
```

**Manfaat**:
- Audio files juga ter-normalize URL-nya
- Konsisten dengan handling media_image

### 5. Tambah Null Coalescing untuk Safety
```php
$q['question_text'] = str_replace($oldBaseUrl, $newBaseUrl, $q['question_text'] ?? '');
$images = json_decode($q['media_image'] ?? '[]', true) ?? [];
```

**Manfaat**:
- Menghindari error jika field kosong atau null
- Kode lebih robust

## Flow Restore yang Benar

1. User upload file backup (.zip)
2. System extract dan validasi data.json
3. System prepare bank soal data:
   - Unset ID (akan dapat ID baru)
   - Set teacher_id jika yang restore adalah guru
   - Handle code collision (tambah suffix jika code sudah ada)
   - Set created_at dan updated_at
   - Set is_active = 0 (tidak aktif dulu)
4. System insert bank soal baru → dapat newBankId
5. System extract dan copy media files (gambar, audio)
6. System process questions:
   - Unset ID
   - Set bank_id = newBankId
   - Normalize URLs (base_url lama → base_url baru)
   - Normalize media_image URLs
   - Normalize media_audio URLs
   - Insert question
7. Success message dengan kode bank soal baru

## Skenario Testing

### Test sebagai Admin:
1. Login sebagai admin
2. Backup bank soal A
3. Restore bank soal A
4. ✅ Bank soal baru muncul dengan kode "A_RESTORED_1"
5. ✅ teacher_id tetap sesuai original (atau null jika original dari admin)
6. ✅ Semua soal dan media ter-restore dengan benar

### Test sebagai Guru:
1. Login sebagai Guru A
2. Backup bank soal milik Guru A
3. Restore bank soal tersebut
4. ✅ Bank soal baru muncul dengan kode "XXX_RESTORED_1"
5. ✅ teacher_id = ID Guru A (owner baru)
6. ✅ Bank soal muncul di list bank soal Guru A
7. ✅ Semua soal dan media ter-restore dengan benar

### Test Restore Bank Soal Guru Lain:
1. Login sebagai Guru A
2. Dapat file backup dari Guru B
3. Restore file backup tersebut
4. ✅ Bank soal baru muncul dengan teacher_id = ID Guru A
5. ✅ Guru A menjadi owner dari bank soal hasil restore
6. ✅ Ini benar karena yang restore adalah Guru A

## Files Modified

1. ✅ `app/Config/Routes.php` - Tambah route untuk restore
2. ✅ `app/Controllers/Admin/CbtBankSoal.php` - Method `restore()`

## Catatan Penting

1. **Ownership Transfer**: Saat guru restore bank soal (bahkan dari backup guru lain), guru tersebut menjadi owner baru. Ini benar karena:
   - Guru yang restore bertanggung jawab atas bank soal tersebut
   - Mencegah konflik ownership
   - Konsisten dengan prinsip isolasi data

2. **Code Collision**: System otomatis menambahkan suffix "_RESTORED_X" jika code sudah ada

3. **Media Files**: Semua media (gambar dan audio) ter-copy ke folder uploads dengan struktur yang sama

4. **URL Normalization**: Semua URL di question_text, media_image, dan media_audio di-normalize dari base_url lama ke base_url baru

5. **is_active = 0**: Bank soal hasil restore tidak langsung aktif, perlu diaktifkan manual oleh user

## Troubleshooting

### Error: "File tidak valid"
**Solusi**: Pastikan file yang diupload adalah file .zip hasil backup

### Error: "File data.json tidak ditemukan"
**Solusi**: File backup corrupt atau bukan file backup yang valid

### Error: "Format data.json tidak valid"
**Solusi**: File backup corrupt atau struktur data tidak sesuai

### Bank soal ter-restore tapi media tidak muncul
**Solusi**: 
1. Cek folder `writable/uploads/` apakah media ter-copy
2. Cek permission folder uploads (harus writable)
3. Cek URL di database apakah sudah ter-normalize

### Guru tidak bisa lihat bank soal hasil restore
**Solusi**: 
1. Cek field `teacher_id` di database, harus sesuai dengan ID guru yang restore
2. Clear cache browser
3. Logout dan login ulang
