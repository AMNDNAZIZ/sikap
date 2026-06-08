# 🚀 MULAI DISINI - Migrasi Database SIAKAD

Selamat datang! File ini adalah panduan utama untuk migrasi database SIAKAD dari struktur lama ke struktur terbaru.

## 📌 Apa yang Harus Saya Lakukan?

Pilih salah satu berdasarkan kebutuhan Anda:

### 🚨 PENTING: Perbaikan Hosting (Baca Ini Dulu!)

**Baca:** [`PERBAIKAN_HOSTING.md`](PERBAIKAN_HOSTING.md)

Perbaikan untuk 2 masalah kritis yang ditemukan di hosting:
1. Tabel `ci_sessions` tidak ada (error session)
2. Error `shell_exec()` di sistem lisensi

**WAJIB dibaca jika Anda sudah deploy ke hosting!**

---

### 🏃 Saya Ingin Cepat! (5 Menit)

**Baca:** [`QUICK_START_MIGRASI.md`](QUICK_START_MIGRASI.md)

Panduan cepat 5 langkah untuk migrasi database. Cocok untuk yang sudah familiar dengan migrasi database.

### 📋 Saya Ingin Ikuti Checklist (15 Menit)

**Baca:** [`CHECKLIST_MIGRASI.md`](CHECKLIST_MIGRASI.md)

Checklist lengkap dari persiapan hingga selesai. Cocok untuk yang ingin memastikan tidak ada yang terlewat.

### 📚 Saya Ingin Memahami Detail (30 Menit)

**Baca:** [`MIGRASI_DATABASE.md`](MIGRASI_DATABASE.md)

Dokumentasi lengkap dengan penjelasan setiap perubahan, alasan, dan troubleshooting detail.

### 📝 Saya Ingin Lihat Ringkasan

**Baca:** [`RINGKASAN_MIGRASI.txt`](RINGKASAN_MIGRASI.txt)

Ringkasan singkat semua perubahan dan cara menjalankan migrasi.

### 🐛 Saya Ingin Tahu Bug yang Diperbaiki

**Baca:** [`PERBAIKAN_BUG_MODEL.md`](PERBAIKAN_BUG_MODEL.md)

Dokumentasi bug yang ditemukan dan diperbaiki di model-model aplikasi.

### 📝 Cara Update Changelog Saat Rilis

**Baca:** [`QUICK_GUIDE_UPDATE_CHANGELOG.md`](QUICK_GUIDE_UPDATE_CHANGELOG.md)

Panduan singkat untuk memastikan changelog terupdate otomatis saat rilis versi baru.

---

## 🎯 Apa yang Akan Berubah?

### Penambahan Kolom (6 kolom baru)
- **users**: 2 kolom untuk keamanan password
- **students**: 3 kolom untuk informasi tambahan
- **teachers**: 1 kolom untuk foto profil

### Tabel Baru (5 tabel)
- **announcement_targets**: Target pengumuman yang lebih fleksibel
- **exam_schedules**: Jadwal ujian
- **app_license**: Sistem lisensi aplikasi
- **subject_scores**: Nilai mata pelajaran
- **ci_sessions**: Session handler CodeIgniter (PENTING!)

### Perbaikan Bug Model (3 model)
- **StudentAcademicRecordModel**: Perbaikan field `year_id` → `academic_year_id`
- **FinalExamScoresModel**: Hapus field `semester` yang tidak ada di database
- **GradesModel**: Tambah field `final_exam` dan perbaiki query

### Perbaikan Hosting (2 perbaikan)
- **Tabel ci_sessions**: Ditambahkan untuk session handler
- **shell_exec() error**: Diperbaiki di license_helper.php

**Total:** 6 kolom baru + 5 tabel baru + 3 perbaikan bug + 2 perbaikan hosting = 16 perubahan

---

## ⚡ Quick Start (Untuk yang Terburu-buru)

### 1. Backup Database
```bash
# Via phpMyAdmin: Export → Go → Save
# Atau via command line:
mysqldump -u username -p database_name > backup.sql
```

### 2. Jalankan Migrasi

**Opsi A - CodeIgniter (Recommended):**
```bash
cd C:\xampp\htdocs\siakad
php spark migrate
```

**Opsi B - SQL Manual:**
- Buka phpMyAdmin
- Import file: `database_migration_manual.sql`

### 3. Verifikasi
```sql
SELECT * FROM migrations ORDER BY batch DESC LIMIT 7;
```

### 4. Test Aplikasi
- Login ke aplikasi
- Cek halaman utama
- Selesai! ✅

---

## 📂 Struktur File

```
siakad/
├── MULAI_DISINI.md                    ← Anda di sini!
├── QUICK_START_MIGRASI.md             ← Panduan cepat
├── CHECKLIST_MIGRASI.md               ← Checklist lengkap
├── MIGRASI_DATABASE.md                ← Dokumentasi detail
├── RINGKASAN_MIGRASI.txt              ← Ringkasan
├── DAFTAR_FILE_MIGRASI.txt            ← Daftar semua file
├── database_migration_manual.sql      ← SQL untuk import manual
│
└── app/Database/Migrations/
    ├── README_MIGRASI.md              ← Panduan di folder migrations
    ├── 2026-02-21-000001_AddMustChangePasswordToUsers.php
    ├── 2026-02-21-000002_AddColumnsToStudents.php
    ├── 2026-02-21-000003_AddPhotoToTeachers.php
    ├── 2026-02-21-000004_CreateAnnouncementTargets.php
    ├── 2026-02-21-000005_CreateExamSchedules.php
    ├── 2026-02-21-000006_CreateAppLicense.php
    └── 2026-02-21-000007_CreateSubjectScores.php
```

---

## ❓ FAQ (Pertanyaan Umum)

### Q: Apakah data saya akan hilang?
**A:** TIDAK. Migrasi ini hanya menambah kolom dan tabel baru, tidak mengubah atau menghapus data existing.

### Q: Berapa lama prosesnya?
**A:** 5-10 menit untuk migrasi, 5 menit untuk verifikasi. Total ~15 menit.

### Q: Apakah harus backup?
**A:** YA, WAJIB! Selalu backup sebelum migrasi untuk berjaga-jaga.

### Q: Bagaimana jika terjadi error?
**A:** 
1. Jangan panik
2. Baca bagian Troubleshooting di `MIGRASI_DATABASE.md`
3. Atau restore dari backup
4. Hubungi support jika perlu

### Q: Apakah bisa rollback?
**A:** YA. Gunakan `php spark migrate:rollback` atau restore dari backup.

### Q: Harus pakai metode mana?
**A:** Recommended: CodeIgniter Migration (`php spark migrate`). Lebih aman dan otomatis.

---

## ⚠️ Hal Penting yang HARUS Diperhatikan

### ✅ DO (Lakukan)
- ✅ Backup database sebelum migrasi
- ✅ Test di development environment dulu (jika memungkinkan)
- ✅ Baca dokumentasi minimal quick start
- ✅ Verifikasi setelah migrasi
- ✅ Test aplikasi setelah migrasi

### ❌ DON'T (Jangan)
- ❌ Migrasi tanpa backup
- ❌ Migrasi langsung di production tanpa test
- ❌ Skip verifikasi
- ❌ Panik jika ada error (Anda punya backup!)

---

## 🎓 Rekomendasi Berdasarkan Pengalaman

### Pemula / Pertama Kali Migrasi
1. Baca: `QUICK_START_MIGRASI.md`
2. Ikuti: `CHECKLIST_MIGRASI.md`
3. Jika ada masalah: Baca `MIGRASI_DATABASE.md`

### Berpengalaman
1. Baca: `RINGKASAN_MIGRASI.txt`
2. Backup database
3. Jalankan: `php spark migrate`
4. Verifikasi & selesai

### Developer / Technical
1. Review: File-file di `app/Database/Migrations/`
2. Baca: `MIGRASI_DATABASE.md` (bagian teknis)
3. Customize jika perlu
4. Jalankan migrasi

---

## 🆘 Butuh Bantuan?

### Dokumentasi
- **Quick Start**: `QUICK_START_MIGRASI.md`
- **Checklist**: `CHECKLIST_MIGRASI.md`
- **Detail**: `MIGRASI_DATABASE.md`
- **Ringkasan**: `RINGKASAN_MIGRASI.txt`

### Troubleshooting
Lihat bagian Troubleshooting di `MIGRASI_DATABASE.md`

### Support
- Hubungi tim development
- Buat issue di repository
- Email support team

---

## ✨ Tips Sukses

1. **Jangan Terburu-buru** - Luangkan 15 menit untuk membaca dan memahami
2. **Backup Adalah Kunci** - Selalu backup sebelum migrasi
3. **Test Dulu** - Jika memungkinkan, test di development
4. **Verifikasi** - Selalu verifikasi setelah migrasi
5. **Dokumentasi** - Catat apa yang Anda lakukan

---

## 🎉 Siap Mulai?

Pilih panduan yang sesuai dengan kebutuhan Anda:

- 🏃 **Cepat**: [`QUICK_START_MIGRASI.md`](QUICK_START_MIGRASI.md)
- 📋 **Lengkap**: [`CHECKLIST_MIGRASI.md`](CHECKLIST_MIGRASI.md)
- 📚 **Detail**: [`MIGRASI_DATABASE.md`](MIGRASI_DATABASE.md)

**Selamat bermigrasi! 🚀**

---

**Versi:** 1.0  
**Tanggal:** 21 Februari 2026  
**Status:** Ready for Production  
**Estimasi Waktu:** 15 menit  
**Tingkat Kesulitan:** ⭐ Mudah
