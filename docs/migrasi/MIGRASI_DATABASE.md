# Dokumentasi Migrasi Database SIAKAD

## Ringkasan

Dokumen ini menjelaskan perubahan struktur database dari versi lama ke versi terbaru aplikasi SIAKAD. Migrasi ini diperlukan untuk menyinkronkan database yang sudah ada di hosting dengan struktur database terbaru.

## Perubahan Database

### A. Penambahan Kolom pada Tabel Existing

#### 1. Tabel `users`
Ditambahkan 2 kolom baru untuk fitur keamanan password:

| Kolom | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `must_change_password` | TINYINT(1) | 0 | Flag untuk memaksa user mengganti password saat login |
| `password_changed_at` | DATETIME | NULL | Timestamp terakhir kali password diubah |

**Alasan:** Meningkatkan keamanan dengan memaksa user mengganti password default atau password yang sudah lama.

#### 2. Tabel `students`
Ditambahkan 3 kolom baru:

| Kolom | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `class_id` | INT(11) UNSIGNED | NULL | ID kelas siswa saat ini (denormalisasi untuk performa) |
| `room` | VARCHAR(50) | NULL | Ruangan/lokal kelas |
| `plain_password` | VARCHAR(100) | NULL | Password plain text untuk keperluan cetak kartu siswa |

**Alasan:** 
- `class_id`: Mempercepat query dengan menghindari join ke `student_records`
- `room`: Informasi tambahan untuk administrasi kelas
- `plain_password`: Keperluan cetak kartu login siswa

#### 3. Tabel `teachers`
Ditambahkan 1 kolom baru:

| Kolom | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `photo` | VARCHAR(255) | NULL | Path file foto guru |

**Alasan:** Menampilkan foto profil guru di berbagai halaman aplikasi.

### B. Tabel Baru

#### 4. Tabel `announcement_targets`
Tabel untuk menyimpan target penerima pengumuman (lebih fleksibel dari kolom JSON).

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | INT(10) UNSIGNED | Primary Key |
| `announcement_id` | INT(10) UNSIGNED | Foreign Key ke `announcements` |
| `target_type` | ENUM('role','class','student') | Jenis target |
| `target_value` | VARCHAR(100) | Nilai target (ID role/class/student) |

**Relasi:**
- `announcement_id` → `announcements.id` (CASCADE)

**Alasan:** Memisahkan target pengumuman ke tabel terpisah untuk query yang lebih efisien dan fleksibel.

#### 5. Tabel `exam_schedules`
Tabel untuk jadwal ujian.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | INT(10) UNSIGNED | Primary Key |
| `subject_id` | INT(10) UNSIGNED | Foreign Key ke `subjects` |
| `class_id` | INT(10) UNSIGNED | Foreign Key ke `classes` |
| `exam_date` | DATE | Tanggal ujian |
| `start_time` | TIME | Jam mulai |
| `end_time` | TIME | Jam selesai |
| `description` | TEXT | Keterangan tambahan |
| `created_at` | DATETIME | Timestamp dibuat |
| `updated_at` | DATETIME | Timestamp diupdate |

**Relasi:**
- `subject_id` → `subjects.id` (CASCADE)
- `class_id` → `classes.id` (CASCADE)

**Alasan:** Fitur baru untuk manajemen jadwal ujian yang terpisah dari CBT.

#### 6. Tabel `app_license`
Tabel untuk manajemen lisensi aplikasi.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | INT(10) UNSIGNED | Primary Key |
| `license_key` | VARCHAR(500) | Kunci lisensi (encrypted) |
| `domain` | VARCHAR(255) | Domain yang terdaftar |
| `machine_id` | VARCHAR(500) | ID mesin (encrypted) |
| `status` | ENUM | Status: active/inactive/expired |
| `last_check` | DATETIME | Terakhir validasi |
| `expires_at` | DATETIME | Tanggal kadaluarsa |
| `hash` | VARCHAR(255) | Hash untuk validasi integritas |
| `created_at` | DATETIME | Timestamp dibuat |
| `updated_at` | DATETIME | Timestamp diupdate |

**Alasan:** Sistem lisensi untuk proteksi aplikasi.

#### 7. Tabel `subject_scores`
Tabel untuk nilai mata pelajaran (alternatif/pelengkap tabel `grades`).

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | INT(10) UNSIGNED | Primary Key |
| `student_id` | INT(10) UNSIGNED | Foreign Key ke `students` |
| `subject_id` | INT(10) UNSIGNED | Foreign Key ke `subjects` |
| `year_id` | INT(10) UNSIGNED | Foreign Key ke `academic_years` |
| `semester` | ENUM('ganjil','genap') | Semester |
| `formatif_score` | DECIMAL(5,2) | Nilai formatif |
| `sumatif_score` | DECIMAL(5,2) | Nilai sumatif |
| `final_exam_score` | DECIMAL(5,2) | Nilai ujian akhir |
| `report_score` | DECIMAL(5,2) | Nilai rapor |
| `created_at` | DATETIME | Timestamp dibuat |
| `updated_at` | DATETIME | Timestamp diupdate |

**Relasi:**
- `student_id` → `students.id` (CASCADE)
- `subject_id` → `subjects.id` (CASCADE)
- `year_id` → `academic_years.id` (CASCADE)

**Alasan:** Struktur nilai yang lebih terorganisir per mata pelajaran.

## Cara Menjalankan Migrasi

### Opsi 1: Menggunakan CodeIgniter Migration (Recommended)

1. Pastikan Anda sudah backup database
2. Buka terminal/command prompt
3. Navigasi ke folder root aplikasi
4. Jalankan:
   ```bash
   php spark migrate
   ```

File migrasi yang akan dijalankan (urutan otomatis):
- `2026-02-21-000001_AddMustChangePasswordToUsers.php`
- `2026-02-21-000002_AddColumnsToStudents.php`
- `2026-02-21-000003_AddPhotoToTeachers.php`
- `2026-02-21-000004_CreateAnnouncementTargets.php`
- `2026-02-21-000005_CreateExamSchedules.php`
- `2026-02-21-000006_CreateAppLicense.php`
- `2026-02-21-000007_CreateSubjectScores.php`

### Opsi 2: Menggunakan SQL Manual

1. Backup database terlebih dahulu
2. Buka phpMyAdmin atau MySQL client
3. Pilih database aplikasi
4. Import atau jalankan file: `database_migration_manual.sql`

File SQL ini sudah dilengkapi dengan pengecekan keberadaan kolom/tabel untuk menghindari error jika sudah ada.

## Verifikasi Setelah Migrasi

### 1. Cek Tabel Migrations
```sql
SELECT * FROM migrations ORDER BY batch DESC LIMIT 10;
```

Pastikan ada 7 record baru dengan batch terbaru.

### 2. Cek Struktur Tabel yang Diubah

```sql
-- Cek kolom baru di users
DESCRIBE users;

-- Cek kolom baru di students
DESCRIBE students;

-- Cek kolom baru di teachers
DESCRIBE teachers;

-- Cek tabel baru
SHOW TABLES LIKE 'announcement_targets';
SHOW TABLES LIKE 'exam_schedules';
SHOW TABLES LIKE 'app_license';
SHOW TABLES LIKE 'subject_scores';
```

### 3. Test Aplikasi

Setelah migrasi, test fitur-fitur berikut:
- Login user (untuk test kolom must_change_password)
- Halaman profil guru (untuk test kolom photo)
- Halaman data siswa (untuk test kolom class_id, room, plain_password)
- Halaman pengumuman (untuk test tabel announcement_targets)
- Halaman jadwal ujian (untuk test tabel exam_schedules)
- Halaman nilai (untuk test tabel subject_scores)

## Rollback

Jika terjadi masalah dan perlu rollback:

### Menggunakan CodeIgniter
```bash
php spark migrate:rollback
```

### Manual SQL
Jalankan query berikut secara berurutan dari bawah ke atas:

```sql
-- Hapus tabel baru
DROP TABLE IF EXISTS `subject_scores`;
DROP TABLE IF EXISTS `app_license`;
DROP TABLE IF EXISTS `exam_schedules`;
DROP TABLE IF EXISTS `announcement_targets`;

-- Hapus kolom yang ditambahkan
ALTER TABLE `teachers` DROP COLUMN `photo`;
ALTER TABLE `students` DROP COLUMN `plain_password`, DROP COLUMN `room`, DROP COLUMN `class_id`;
ALTER TABLE `users` DROP COLUMN `password_changed_at`, DROP COLUMN `must_change_password`;
```

## Catatan Penting

1. **WAJIB BACKUP** - Selalu backup database sebelum migrasi
2. **Test di Development** - Test di environment development terlebih dahulu
3. **Downtime** - Pertimbangkan maintenance mode saat migrasi di production
4. **Data Existing** - Migrasi ini tidak mengubah data yang sudah ada, hanya struktur
5. **Foreign Keys** - Tabel baru memiliki foreign key constraints, pastikan data referensi valid

## Troubleshooting

### Error: "Table already exists"
**Solusi:** Tabel sudah ada, skip migrasi atau hapus tabel terlebih dahulu.

### Error: "Column already exists"
**Solusi:** Kolom sudah ada, skip migrasi atau hapus kolom terlebih dahulu.

### Error: "Foreign key constraint fails"
**Solusi:** Pastikan tabel parent (subjects, classes, students, academic_years, announcements) sudah ada dan memiliki data.

### Error: "Cannot add foreign key constraint"
**Solusi:** 
1. Cek tipe data kolom foreign key sama dengan kolom yang direferensikan
2. Cek engine tabel (harus InnoDB)
3. Cek collation tabel sama

## Kontak Support

Jika mengalami masalah saat migrasi, silakan hubungi tim development atau buat issue di repository.

---

**Dibuat:** 21 Februari 2026  
**Versi:** 1.0  
**Status:** Ready for Production
