# Checklist Migrasi Database SIAKAD

Gunakan checklist ini untuk memastikan proses migrasi berjalan lancar dan aman.

## Persiapan Sebelum Migrasi

- [ ] Baca dokumentasi lengkap di `MIGRASI_DATABASE.md`
- [ ] Baca ringkasan di `RINGKASAN_MIGRASI.txt`
- [ ] Pastikan memiliki akses ke database (username & password)
- [ ] Pastikan memiliki akses ke server/hosting
- [ ] Catat versi PHP dan MySQL/MariaDB yang digunakan
- [ ] Pastikan tidak ada user yang sedang menggunakan aplikasi

## Backup Database

- [ ] Backup database melalui phpMyAdmin (Export → SQL)
- [ ] Simpan file backup dengan nama yang jelas (contoh: `siakad_backup_2026-02-21.sql`)
- [ ] Verifikasi file backup berhasil didownload
- [ ] Simpan backup di lokasi yang aman (minimal 2 tempat berbeda)
- [ ] Test restore backup di database dummy (optional tapi recommended)

## Testing di Development (Recommended)

- [ ] Buat database baru untuk testing
- [ ] Restore backup database ke database testing
- [ ] Jalankan migrasi di database testing
- [ ] Verifikasi struktur tabel sudah benar
- [ ] Test aplikasi dengan database testing
- [ ] Catat masalah yang ditemukan (jika ada)

## Eksekusi Migrasi di Production

### Opsi A: Menggunakan CodeIgniter Migration

- [ ] Buka terminal/command prompt
- [ ] Navigasi ke folder aplikasi: `cd C:\xampp\htdocs\siakad`
- [ ] Jalankan: `php spark migrate`
- [ ] Tunggu hingga proses selesai
- [ ] Catat output/pesan yang muncul
- [ ] Cek apakah ada error

### Opsi B: Menggunakan SQL Manual

- [ ] Buka phpMyAdmin
- [ ] Pilih database aplikasi
- [ ] Klik tab "Import"
- [ ] Pilih file `database_migration_manual.sql`
- [ ] Klik "Go" untuk eksekusi
- [ ] Tunggu hingga proses selesai
- [ ] Catat pesan yang muncul
- [ ] Cek apakah ada error

## Verifikasi Setelah Migrasi

### Cek Tabel Migrations

```sql
SELECT * FROM migrations ORDER BY batch DESC LIMIT 10;
```

- [ ] Ada 7 record baru dengan batch terbaru
- [ ] Semua migrasi tercatat dengan benar

### Cek Struktur Tabel Users

```sql
DESCRIBE users;
```

- [ ] Kolom `must_change_password` ada (TINYINT)
- [ ] Kolom `password_changed_at` ada (DATETIME)

### Cek Struktur Tabel Students

```sql
DESCRIBE students;
```

- [ ] Kolom `class_id` ada (INT UNSIGNED)
- [ ] Kolom `room` ada (VARCHAR 50)
- [ ] Kolom `plain_password` ada (VARCHAR 100)

### Cek Struktur Tabel Teachers

```sql
DESCRIBE teachers;
```

- [ ] Kolom `photo` ada (VARCHAR 255)

### Cek Tabel Baru

```sql
SHOW TABLES LIKE 'announcement_targets';
SHOW TABLES LIKE 'exam_schedules';
SHOW TABLES LIKE 'app_license';
SHOW TABLES LIKE 'subject_scores';
```

- [ ] Tabel `announcement_targets` ada
- [ ] Tabel `exam_schedules` ada
- [ ] Tabel `app_license` ada
- [ ] Tabel `subject_scores` ada

### Cek Foreign Keys

```sql
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('announcement_targets', 'exam_schedules', 'subject_scores')
ORDER BY TABLE_NAME;
```

- [ ] Foreign keys terbuat dengan benar
- [ ] Tidak ada error constraint

## Testing Aplikasi

### Test Fitur Existing

- [ ] Login sebagai Admin
- [ ] Login sebagai Guru
- [ ] Login sebagai Siswa
- [ ] Buka halaman Dashboard
- [ ] Buka halaman Data Siswa
- [ ] Buka halaman Data Guru
- [ ] Buka halaman Pengumuman
- [ ] Buka halaman Nilai

### Test Fitur Baru (Jika Sudah Diimplementasi)

- [ ] Test fitur change password (must_change_password)
- [ ] Test upload foto guru
- [ ] Test pengumuman dengan target spesifik
- [ ] Test jadwal ujian
- [ ] Test input nilai di subject_scores

## Monitoring Setelah Migrasi

### Hari Pertama

- [ ] Monitor error log aplikasi
- [ ] Monitor error log database
- [ ] Tanya feedback dari user
- [ ] Catat masalah yang dilaporkan

### Minggu Pertama

- [ ] Review performa aplikasi
- [ ] Review performa query database
- [ ] Pastikan tidak ada data yang hilang
- [ ] Pastikan semua fitur berjalan normal

## Dokumentasi

- [ ] Catat tanggal dan waktu migrasi
- [ ] Catat versi database sebelum migrasi
- [ ] Catat versi database setelah migrasi
- [ ] Catat masalah yang ditemukan dan solusinya
- [ ] Update dokumentasi aplikasi jika diperlukan

## Rollback (Jika Diperlukan)

Jika terjadi masalah serius:

- [ ] Putuskan apakah perlu rollback
- [ ] Informasikan ke semua user
- [ ] Jalankan script rollback (lihat MIGRASI_DATABASE.md)
- [ ] Atau restore dari backup
- [ ] Verifikasi aplikasi kembali normal
- [ ] Analisa penyebab masalah
- [ ] Perbaiki masalah sebelum migrasi ulang

## Selesai

- [ ] Semua checklist sudah dicentang
- [ ] Aplikasi berjalan normal
- [ ] User dapat menggunakan aplikasi
- [ ] Backup database baru dibuat
- [ ] Dokumentasi migrasi lengkap

---

**Catatan:**
- Jangan skip langkah backup!
- Jika ragu, test di development terlebih dahulu
- Simpan dokumentasi ini untuk referensi di masa depan
- Jika ada masalah, jangan panik - Anda sudah punya backup!

**Tanggal Migrasi:** _______________  
**Dilakukan oleh:** _______________  
**Status:** [ ] Berhasil  [ ] Gagal  [ ] Rollback  
**Catatan:** _______________________________________________
