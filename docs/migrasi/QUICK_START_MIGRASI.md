# Quick Start - Migrasi Database SIAKAD

Panduan cepat untuk menjalankan migrasi database dalam 5 langkah mudah.

## 🚀 Langkah Cepat (5 Menit)

### 1️⃣ Backup Database (WAJIB!)

**Via phpMyAdmin:**
1. Buka phpMyAdmin
2. Pilih database aplikasi
3. Klik tab "Export"
4. Klik "Go"
5. Simpan file SQL yang didownload

**Via Command Line:**
```bash
mysqldump -u username -p nama_database > backup_siakad_2026-02-21.sql
```

### 2️⃣ Pilih Metode Migrasi

#### Metode A: CodeIgniter Migration (Recommended) ⭐

```bash
# Buka terminal/command prompt
cd C:\xampp\htdocs\siakad

# Jalankan migrasi
php spark migrate
```

**Selesai!** Migrasi akan berjalan otomatis.

#### Metode B: SQL Manual

1. Buka phpMyAdmin
2. Pilih database aplikasi
3. Klik tab "Import"
4. Pilih file: `database_migration_manual.sql`
5. Klik "Go"

**Selesai!** Tunggu hingga proses selesai.

### 3️⃣ Verifikasi (30 Detik)

Jalankan query ini di phpMyAdmin:

```sql
-- Cek migrasi berhasil
SELECT * FROM migrations ORDER BY batch DESC LIMIT 7;

-- Cek tabel baru
SHOW TABLES LIKE '%announcement_targets%';
SHOW TABLES LIKE '%exam_schedules%';
SHOW TABLES LIKE '%app_license%';
SHOW TABLES LIKE '%subject_scores%';
```

✅ Jika muncul 7 record di migrations dan 4 tabel baru, migrasi BERHASIL!

### 4️⃣ Test Aplikasi

1. Buka aplikasi di browser
2. Login sebagai Admin
3. Cek halaman Dashboard
4. Cek halaman Data Siswa
5. Cek halaman Data Guru

✅ Jika semua halaman bisa dibuka, aplikasi NORMAL!

### 5️⃣ Selesai! 🎉

Migrasi database berhasil! Aplikasi siap digunakan dengan struktur database terbaru.

---

## 📋 Apa yang Berubah?

### Kolom Baru:
- **users**: +2 kolom (keamanan password)
- **students**: +3 kolom (class_id, room, plain_password)
- **teachers**: +1 kolom (photo)

### Tabel Baru:
- **announcement_targets**: Target pengumuman
- **exam_schedules**: Jadwal ujian
- **app_license**: Lisensi aplikasi
- **subject_scores**: Nilai mata pelajaran

---

## ⚠️ Troubleshooting

### Error: "Table already exists"
**Solusi:** Tabel sudah ada, migrasi sudah pernah dijalankan. Tidak perlu migrasi lagi.

### Error: "Column already exists"
**Solusi:** Kolom sudah ada, migrasi sudah pernah dijalankan. Tidak perlu migrasi lagi.

### Error: "php command not found"
**Solusi:** 
```bash
# Gunakan path lengkap PHP
C:\xampp\php\php.exe spark migrate
```

### Aplikasi Error Setelah Migrasi
**Solusi:**
1. Cek error log di `writable/logs/`
2. Atau restore backup database
3. Hubungi support

---

## 🆘 Butuh Bantuan?

### Dokumentasi Lengkap:
- `MIGRASI_DATABASE.md` - Dokumentasi detail
- `CHECKLIST_MIGRASI.md` - Checklist lengkap
- `RINGKASAN_MIGRASI.txt` - Ringkasan perubahan

### Rollback (Jika Perlu):
```bash
php spark migrate:rollback
```

Atau restore dari backup database.

---

## ✅ Checklist Singkat

- [ ] Backup database
- [ ] Jalankan migrasi (pilih metode A atau B)
- [ ] Verifikasi dengan query SQL
- [ ] Test aplikasi
- [ ] Selesai!

---

**Estimasi Waktu:** 5-10 menit  
**Tingkat Kesulitan:** ⭐ Mudah  
**Risiko:** ⚠️ Rendah (jika sudah backup)

**Dibuat:** 21 Februari 2026  
**Untuk:** SIAKAD Database Migration
