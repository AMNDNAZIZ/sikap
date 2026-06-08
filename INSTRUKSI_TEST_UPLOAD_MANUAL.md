# Instruksi Test Upload Manual

**Tanggal**: 19 April 2026  
**Status**: ⏳ MENUNGGU TEST  
**Tujuan**: Memverifikasi bahwa fix FCPATH berhasil

## ⚠️ PENTING

File-file yang ada di `uploads/teacher_docs/` saat ini:
- `doc_1_1776610138_1940.pdf` (19/04/2026 21:48)
- `doc_1_1776612306_8584.jpg` (19/04/2026 22:25)

File-file ini dibuat **SEBELUM** fix diterapkan, jadi keberadaan mereka BUKAN bukti bahwa fix gagal.

## 🎯 Tujuan Test

Memastikan bahwa upload file BARU (setelah fix) akan masuk ke `public/uploads/teacher_docs/` dan TIDAK ke `uploads/teacher_docs/` (root).

## 📋 Test Checklist

### Test 1: Upload dari Admin

1. **Login sebagai Admin**
   - URL: `http://localhost:8080/siakad/`
   - Username: admin
   - Password: (password admin)

2. **Buka Menu Guru**
   - Menu: Admin → Guru
   - Pilih salah satu guru
   - Klik tombol "Edit" (icon pensil)

3. **Buka Tab Arsip Dokumen**
   - Klik tab "Arsip Dokumen"
   - Pastikan tab terbuka dengan benar

4. **Upload File Test**
   - Klik tombol "Upload Dokumen"
   - Isi form:
     - Judul: "Test Upload Setelah Fix - Admin"
     - File: Pilih file PDF atau gambar (max 5MB)
   - Klik "Upload"

5. **Verifikasi Upload Berhasil**
   - Harus muncul notifikasi "Dokumen berhasil diupload"
   - File harus muncul di list dokumen

6. **Verifikasi Lokasi File**
   - Buka folder: `C:\xampp\htdocs\siakad\public\uploads\teacher_docs\`
   - **HARUS ADA** file baru dengan nama seperti: `doc_1_1776614xxx_xxxx.pdf`
   - Catat nama file dan timestamp

7. **Verifikasi Folder Lama**
   - Buka folder: `C:\xampp\htdocs\siakad\uploads\teacher_docs\`
   - **TIDAK BOLEH ADA** file baru dengan timestamp setelah fix
   - Hanya boleh ada 2 file lama:
     - `doc_1_1776610138_1940.pdf`
     - `doc_1_1776612306_8584.jpg`

### Test 2: Upload dari Guru

1. **Login sebagai Guru**
   - Logout dari admin
   - Login dengan akun guru
   - Username: (username guru)
   - Password: (password guru)

2. **Buka Menu Profile**
   - Menu: Profile (icon user di navbar)

3. **Buka Tab Arsip Dokumen**
   - Klik tab "Arsip Dokumen"
   - Pastikan tab terbuka dengan benar

4. **Upload File Test**
   - Klik tombol "Upload Dokumen"
   - Isi form:
     - Judul: "Test Upload Setelah Fix - Guru"
     - File: Pilih file PDF atau gambar (max 5MB)
   - Klik "Upload"

5. **Verifikasi Upload Berhasil**
   - Harus muncul notifikasi "Dokumen berhasil diunggah"
   - File harus muncul di list dokumen

6. **Verifikasi Lokasi File**
   - Buka folder: `C:\xampp\htdocs\siakad\public\uploads\teacher_docs\`
   - **HARUS ADA** file baru dengan nama seperti: `doc_1_1776615xxx_xxxx.pdf`
   - Catat nama file dan timestamp

7. **Verifikasi Folder Lama**
   - Buka folder: `C:\xampp\htdocs\siakad\uploads\teacher_docs\`
   - **TIDAK BOLEH ADA** file baru dengan timestamp setelah fix
   - Hanya boleh ada 2 file lama:
     - `doc_1_1776610138_1940.pdf`
     - `doc_1_1776612306_8584.jpg`

### Test 3: Preview Dokumen

1. **Buka List Dokumen**
   - Dari admin atau guru
   - Tab "Arsip Dokumen"

2. **Test Preview PDF**
   - Klik icon mata (👁) pada dokumen PDF
   - Modal harus muncul
   - PDF harus tampil di iframe
   - Bisa scroll untuk melihat halaman lain

3. **Test Preview Image**
   - Klik icon mata (👁) pada dokumen gambar
   - Modal harus muncul
   - Gambar harus tampil dengan jelas
   - Bisa zoom jika perlu

4. **Test Preview Word/Excel** (jika ada)
   - Klik icon mata (👁) pada dokumen Word/Excel
   - Modal harus muncul
   - Dokumen harus tampil via Office Online Viewer
   - Atau muncul pesan "Preview tidak tersedia untuk tipe file ini"

### Test 4: Download Dokumen

1. **Download File**
   - Klik icon download (⬇) pada dokumen
   - File harus terdownload ke folder Downloads
   - Nama file harus sesuai dengan original name

2. **Verifikasi File**
   - Buka file yang didownload
   - File harus bisa dibuka dengan aplikasi yang sesuai
   - Isi file harus sesuai dengan yang diupload

### Test 5: Delete Dokumen

1. **Delete File Test**
   - Klik icon delete (🗑) pada dokumen test
   - Muncul konfirmasi "Apakah Anda yakin?"
   - Klik "Ya" atau "OK"

2. **Verifikasi Delete**
   - File harus hilang dari list dokumen
   - Muncul notifikasi "Dokumen berhasil dihapus"

3. **Verifikasi File Terhapus**
   - Buka folder: `C:\xampp\htdocs\siakad\public\uploads\teacher_docs\`
   - File test harus sudah tidak ada

## 📊 Hasil Test

### ✅ Test BERHASIL jika:

1. Upload dari Admin:
   - ✅ File masuk ke `public/uploads/teacher_docs/`
   - ✅ File TIDAK masuk ke `uploads/teacher_docs/` (root)
   - ✅ Preview berfungsi
   - ✅ Download berfungsi
   - ✅ Delete berfungsi

2. Upload dari Guru:
   - ✅ File masuk ke `public/uploads/teacher_docs/`
   - ✅ File TIDAK masuk ke `uploads/teacher_docs/` (root)
   - ✅ Preview berfungsi
   - ✅ Download berfungsi
   - ✅ Delete berfungsi

### ❌ Test GAGAL jika:

1. Upload dari Admin atau Guru:
   - ❌ File masuk ke `uploads/teacher_docs/` (root)
   - ❌ File TIDAK masuk ke `public/uploads/teacher_docs/`
   - ❌ Preview tidak berfungsi
   - ❌ Download tidak berfungsi
   - ❌ Delete tidak berfungsi

## 🔍 Troubleshooting

### Jika Upload Masih ke Folder Root

1. **Clear Opcache** (jika menggunakan opcache):
   ```bash
   # Restart Apache
   # Atau buat file clear_opcache.php:
   <?php
   opcache_reset();
   echo "Opcache cleared";
   ```

2. **Clear Session**:
   - Logout dari aplikasi
   - Clear browser cache (Ctrl+Shift+Delete)
   - Clear cookies untuk localhost
   - Login kembali

3. **Restart Apache**:
   - Buka XAMPP Control Panel
   - Stop Apache
   - Start Apache

4. **Verifikasi index.php**:
   - Buka file: `C:\xampp\htdocs\siakad\index.php`
   - Pastikan isinya hanya:
     ```php
     <?php
     require __DIR__ . '/public/index.php';
     ```

### Jika Preview Tidak Berfungsi

1. **Check Console Browser**:
   - Tekan F12
   - Tab Console
   - Lihat error message

2. **Check Route**:
   - URL preview harus: `/admin/teachers/viewDocument/{id}`
   - Bukan: `/admin/teachers/viewDocument/1` (404)

3. **Check File Path**:
   - File harus ada di `public/uploads/teacher_docs/`
   - Bukan di `uploads/teacher_docs/` (root)

### Jika Download Tidak Berfungsi

1. **Check Browser Settings**:
   - Pastikan download tidak diblokir
   - Check folder Downloads

2. **Check File Permissions**:
   - File harus readable (644 atau 755)

## 📝 Laporan Test

Setelah melakukan test, catat hasil berikut:

```
=================================================================
LAPORAN TEST UPLOAD SETELAH FIX
=================================================================

Tanggal Test: _______________
Waktu Test: _______________

TEST 1: UPLOAD DARI ADMIN
- Upload berhasil: [ ] Ya [ ] Tidak
- File masuk ke public/uploads/teacher_docs/: [ ] Ya [ ] Tidak
- File TIDAK masuk ke uploads/teacher_docs/: [ ] Ya [ ] Tidak
- Nama file: _______________
- Timestamp file: _______________

TEST 2: UPLOAD DARI GURU
- Upload berhasil: [ ] Ya [ ] Tidak
- File masuk ke public/uploads/teacher_docs/: [ ] Ya [ ] Tidak
- File TIDAK masuk ke uploads/teacher_docs/: [ ] Ya [ ] Tidak
- Nama file: _______________
- Timestamp file: _______________

TEST 3: PREVIEW DOKUMEN
- Preview PDF: [ ] Berhasil [ ] Gagal
- Preview Image: [ ] Berhasil [ ] Gagal
- Preview Word/Excel: [ ] Berhasil [ ] Gagal [ ] N/A

TEST 4: DOWNLOAD DOKUMEN
- Download berhasil: [ ] Ya [ ] Tidak
- File bisa dibuka: [ ] Ya [ ] Tidak

TEST 5: DELETE DOKUMEN
- Delete berhasil: [ ] Ya [ ] Tidak
- File terhapus dari folder: [ ] Ya [ ] Tidak

KESIMPULAN:
[ ] FIX BERHASIL - Semua test passed
[ ] FIX GAGAL - Ada test yang failed

Catatan:
_______________________________________________________________
_______________________________________________________________
_______________________________________________________________

=================================================================
```

## 🎯 Next Steps

### Jika Test BERHASIL:

1. **Monitor selama 1-2 minggu**:
   - Pastikan tidak ada file baru di `uploads/teacher_docs/` (root)
   - Semua upload harus masuk ke `public/uploads/teacher_docs/`

2. **Setelah 1-2 minggu**:
   - Hapus folder `uploads/teacher_docs/` di root
   - Hapus folder `uploads_OLD_BACKUP_20260419/`

3. **Update dokumentasi**:
   - Tandai fix sebagai "VERIFIED"
   - Catat tanggal verifikasi

### Jika Test GAGAL:

1. **Laporkan hasil test**:
   - Screenshot error
   - Log file error
   - Nama file yang diupload
   - Lokasi file yang salah

2. **Investigasi lebih lanjut**:
   - Check opcache
   - Check preload
   - Check web server config
   - Check file permissions

3. **Minta bantuan**:
   - Sertakan laporan test
   - Sertakan screenshot
   - Sertakan log error

---

**PENTING**: Jangan hapus folder lama sampai yakin fix berhasil!
