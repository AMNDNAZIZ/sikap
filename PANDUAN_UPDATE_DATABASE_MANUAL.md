# Panduan Update Database Manual

## Latar Belakang

Karena sistem di online terlanjur error, update harus dilakukan manual termasuk database. File `update_database_manual.sql` berisi semua perubahan database yang diperlukan untuk membawa database dari struktur lama ke versi terbaru.

## File SQL yang Tersedia

### `update_database_manual.sql`
File SQL lengkap yang berisi semua migrasi database dari versi lama ke terbaru. File ini:
- ✅ Aman dijalankan berkali-kali (defensive SQL)
- ✅ Tidak akan error jika kolom/tabel sudah ada
- ✅ Mencakup semua 9 migrasi yang ada

## Perubahan Database yang Diterapkan

### Migration 1: Add must_change_password to users
**Tabel**: `users`
**Perubahan**:
- Tambah kolom `must_change_password` (TINYINT, default 0)
- Tambah kolom `password_changed_at` (DATETIME, nullable)

**Fungsi**: Untuk fitur force change password saat login pertama kali

---

### Migration 2: Add columns to students
**Tabel**: `students`
**Perubahan**:
- Tambah kolom `class_id` (INT UNSIGNED, nullable)
- Tambah kolom `room` (VARCHAR 50, nullable)
- Tambah kolom `plain_password` (VARCHAR 100, nullable)

**Fungsi**: 
- `class_id`: Relasi ke tabel classes
- `room`: Ruang kelas siswa
- `plain_password`: Password plain text untuk kartu peserta

---

### Migration 3: Add photo to teachers
**Tabel**: `teachers`
**Perubahan**:
- Tambah kolom `photo` (VARCHAR 255, nullable)

**Fungsi**: Untuk menyimpan foto guru

**Note**: Berdasarkan struktur_lama_sikap.sql, kolom ini sudah ada. Migration ini akan skip otomatis.

---

### Migration 4: Create announcement_targets
**Tabel**: `announcement_targets` (NEW)
**Struktur**:
```sql
- id (INT UNSIGNED, PK, AUTO_INCREMENT)
- announcement_id (INT UNSIGNED, FK to announcements)
- target_type (ENUM: 'role', 'class', 'student')
- target_value (VARCHAR 100)
```

**Fungsi**: Untuk targeting pengumuman ke role/class/student tertentu

---

### Migration 5: Update exam_schedules
**Tabel**: `exam_schedules`
**Perubahan**:
- Tambah kolom `subject_id` (INT UNSIGNED)
- Tambah kolom `class_id` (INT UNSIGNED)
- Tambah kolom `exam_date` (DATE)
- Tambah kolom `description` (TEXT, nullable)
- Tambah kolom `created_at` (DATETIME, nullable)
- Tambah kolom `updated_at` (DATETIME, nullable)

**Fungsi**: Memperbaiki struktur tabel exam_schedules agar lebih lengkap

**Note**: Tabel ini sudah ada di struktur lama tapi dengan struktur berbeda. SQL akan menambahkan kolom yang missing.

---

### Migration 6: Update app_license structure
**Tabel**: `app_license`
**Perubahan**:
- Modify `license_key` dari VARCHAR(255) ke VARCHAR(500)
- Modify `machine_id` dari VARCHAR(255) ke VARCHAR(500)
- Modify `hash` dari VARCHAR(255) ke TEXT
- Update `status` ENUM: 'active', 'inactive', 'expired'

**Fungsi**: Memperbesar ukuran kolom untuk menampung data lisensi yang lebih panjang

---

### Migration 7: Create subject_scores
**Tabel**: `subject_scores` (NEW)
**Struktur**:
```sql
- id (INT UNSIGNED, PK, AUTO_INCREMENT)
- student_id (INT UNSIGNED, FK to students)
- subject_id (INT UNSIGNED, FK to subjects)
- year_id (INT UNSIGNED, FK to academic_years)
- semester (ENUM: 'ganjil', 'genap')
- formatif_score (DECIMAL 5,2, nullable)
- sumatif_score (DECIMAL 5,2, nullable)
- final_exam_score (DECIMAL 5,2, nullable)
- report_score (DECIMAL 5,2, nullable)
- created_at (DATETIME, nullable)
- updated_at (DATETIME, nullable)
```

**Fungsi**: Tabel baru untuk menyimpan nilai siswa per mata pelajaran

---

### Migration 8: Ensure ci_sessions exists
**Tabel**: `ci_sessions`
**Struktur**:
```sql
- id (VARCHAR 128, PK)
- ip_address (VARCHAR 45)
- timestamp (TIMESTAMP, default CURRENT_TIMESTAMP)
- data (BLOB)
```

**Fungsi**: Tabel session CodeIgniter 4

**Note**: Berdasarkan struktur_lama_sikap.sql, tabel ini sudah ada. Migration ini akan skip otomatis.

---

### Migration 9: Add hardware_signature to app_license
**Tabel**: `app_license`
**Perubahan**:
- Tambah kolom `hardware_signature` (VARCHAR 64, nullable)

**Fungsi**: Untuk binding lisensi ke hardware tertentu (security enhancement)

---

## Cara Menggunakan

### Metode 1: Via phpMyAdmin (Recommended)

1. **Backup Database Dulu!**
   ```
   - Login ke phpMyAdmin
   - Pilih database
   - Tab "Export"
   - Klik "Go" untuk download backup
   ```

2. **Import File SQL**
   ```
   - Tab "Import"
   - Klik "Choose File"
   - Pilih: update_database_manual.sql
   - Klik "Go"
   ```

3. **Verify Hasil**
   ```
   - Scroll ke bawah untuk melihat hasil
   - Seharusnya muncul: "Database update completed successfully!"
   - Check struktur tabel yang di-update
   ```

---

### Metode 2: Via MySQL Command Line

1. **Backup Database Dulu!**
   ```bash
   mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Import File SQL**
   ```bash
   mysql -u username -p database_name < update_database_manual.sql
   ```

3. **Verify Hasil**
   ```bash
   mysql -u username -p database_name -e "SHOW COLUMNS FROM users"
   mysql -u username -p database_name -e "SHOW COLUMNS FROM students"
   mysql -u username -p database_name -e "SHOW COLUMNS FROM app_license"
   ```

---

## Keamanan File SQL

### Defensive SQL
File SQL ini menggunakan teknik defensive programming:

```sql
-- Check if column exists before adding
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',  -- Column exists, do nothing
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ...')  -- Add column
));
```

**Keuntungan**:
- ✅ Aman dijalankan berkali-kali
- ✅ Tidak akan error jika kolom sudah ada
- ✅ Tidak akan menghapus data existing
- ✅ Idempotent (hasil sama meskipun dijalankan berulang)

---

## Verification Checklist

Setelah import SQL, verify perubahan berikut:

### ☐ Tabel users
```sql
SHOW COLUMNS FROM users LIKE 'must_change_password';
SHOW COLUMNS FROM users LIKE 'password_changed_at';
```
Expected: 2 kolom baru

### ☐ Tabel students
```sql
SHOW COLUMNS FROM students LIKE 'class_id';
SHOW COLUMNS FROM students LIKE 'room';
SHOW COLUMNS FROM students LIKE 'plain_password';
```
Expected: 3 kolom baru

### ☐ Tabel app_license
```sql
SHOW COLUMNS FROM app_license LIKE 'hardware_signature';
DESCRIBE app_license;
```
Expected: 
- Kolom hardware_signature ada
- license_key: VARCHAR(500)
- machine_id: VARCHAR(500)
- hash: TEXT

### ☐ Tabel Baru
```sql
SHOW TABLES LIKE 'announcement_targets';
SHOW TABLES LIKE 'subject_scores';
```
Expected: 2 tabel baru

### ☐ Tabel exam_schedules
```sql
DESCRIBE exam_schedules;
```
Expected: Kolom subject_id, class_id, exam_date, description, created_at, updated_at ada

---

## Troubleshooting

### Error: "Table doesn't exist"
**Penyebab**: Tabel parent belum ada (misal: announcements, students, subjects)
**Solusi**: 
1. Check apakah tabel parent ada
2. Jika tidak ada, restore dari backup atau buat manual

### Error: "Duplicate column name"
**Penyebab**: Kolom sudah ada (seharusnya tidak terjadi karena defensive SQL)
**Solusi**: 
1. Ignore error ini (kolom sudah ada = OK)
2. Lanjutkan ke query berikutnya

### Error: "Cannot add foreign key constraint"
**Penyebab**: Data existing tidak konsisten (misal: student_id di subject_scores tidak ada di tabel students)
**Solusi**:
1. Check data yang tidak konsisten
2. Hapus atau perbaiki data tersebut
3. Jalankan ulang query foreign key

### Error: "Access denied"
**Penyebab**: User database tidak punya privilege untuk ALTER TABLE
**Solusi**:
1. Login sebagai root atau user dengan privilege penuh
2. Atau minta admin server untuk grant privilege

---

## Rollback (Jika Diperlukan)

Jika terjadi masalah setelah update:

### Rollback via Backup
```bash
# Restore dari backup
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
```

### Rollback Manual (Hapus Kolom/Tabel Baru)
```sql
-- Hapus kolom baru di users
ALTER TABLE users DROP COLUMN must_change_password;
ALTER TABLE users DROP COLUMN password_changed_at;

-- Hapus kolom baru di students
ALTER TABLE students DROP COLUMN class_id;
ALTER TABLE students DROP COLUMN room;
ALTER TABLE students DROP COLUMN plain_password;

-- Hapus kolom baru di app_license
ALTER TABLE app_license DROP COLUMN hardware_signature;

-- Hapus tabel baru
DROP TABLE IF EXISTS announcement_targets;
DROP TABLE IF EXISTS subject_scores;
```

---

## Setelah Update Database

### 1. Update File Aplikasi
Setelah database di-update, pastikan file aplikasi juga sudah di-update:
- Upload file terbaru via FTP/cPanel
- Atau gunakan fitur updater di `/admin/updater`

### 2. Regenerate Security Hashes
```bash
# Akses via browser:
http://yourdomain.com/generate_security_hash.php
http://yourdomain.com/generate_license_hash.php
```

### 3. Test Aplikasi
- Login sebagai admin
- Check fitur yang menggunakan tabel baru
- Check log error: `writable/logs/`

### 4. Update Migrations Table
Agar sistem tahu migrasi sudah dijalankan:
```sql
-- Insert migration records
INSERT INTO migrations (version, class, `group`, namespace, time, batch) VALUES
('2026-02-21-000001', 'App\\Database\\Migrations\\AddMustChangePasswordToUsers', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000002', 'App\\Database\\Migrations\\AddColumnsToStudents', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000003', 'App\\Database\\Migrations\\AddPhotoToTeachers', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000004', 'App\\Database\\Migrations\\CreateAnnouncementTargets', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000005', 'App\\Database\\Migrations\\CreateExamSchedules', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000006', 'App\\Database\\Migrations\\CreateAppLicense', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000007', 'App\\Database\\Migrations\\CreateSubjectScores', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000008', 'App\\Database\\Migrations\\CreateCiSessions', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000009', 'App\\Database\\Migrations\\AddHardwareSignatureToLicense', 'default', 'App', UNIX_TIMESTAMP(), 1)
ON DUPLICATE KEY UPDATE time = UNIX_TIMESTAMP();
```

---

## Summary

### File yang Dibuat:
- ✅ `update_database_manual.sql` - File SQL untuk update database
- ✅ `PANDUAN_UPDATE_DATABASE_MANUAL.md` - Dokumentasi lengkap (file ini)

### Perubahan Database:
- ✅ 2 kolom baru di `users`
- ✅ 3 kolom baru di `students`
- ✅ 1 kolom baru di `app_license` (hardware_signature)
- ✅ Update struktur `app_license` (VARCHAR 500, TEXT)
- ✅ Update struktur `exam_schedules` (tambah kolom)
- ✅ 2 tabel baru: `announcement_targets`, `subject_scores`

### Keamanan:
- ✅ Defensive SQL (aman dijalankan berkali-kali)
- ✅ Tidak akan menghapus data existing
- ✅ Check kolom/tabel exists sebelum create/alter

### Next Steps:
1. ✅ Backup database
2. ✅ Import `update_database_manual.sql`
3. ✅ Verify hasil
4. ✅ Update file aplikasi
5. ✅ Regenerate security hashes
6. ✅ Test aplikasi

---

**Status**: ✅ READY TO USE  
**Risk Level**: LOW (defensive SQL)  
**Tested**: YES (structure verified against migrations)
