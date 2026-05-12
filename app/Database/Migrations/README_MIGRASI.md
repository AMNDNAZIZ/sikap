# Panduan Migrasi Database

File-file migrasi ini dibuat untuk menyinkronkan struktur database lama dengan database terbaru.

## Daftar Perubahan

### 1. Tabel `users`
**File:** `2026-02-21-000001_AddMustChangePasswordToUsers.php`

Menambahkan kolom baru:
- `must_change_password` (TINYINT, default 0) - Flag untuk memaksa user mengganti password
- `password_changed_at` (DATETIME, nullable) - Timestamp terakhir kali password diubah

### 2. Tabel `students`
**File:** `2026-02-21-000002_AddColumnsToStudents.php`

Menambahkan kolom baru:
- `class_id` (INT UNSIGNED, nullable) - ID kelas siswa saat ini
- `room` (VARCHAR 50, nullable) - Ruangan/lokal kelas
- `plain_password` (VARCHAR 100, nullable) - Password plain text untuk keperluan tertentu

### 3. Tabel `teachers`
**File:** `2026-02-21-000003_AddPhotoToTeachers.php`

Menambahkan kolom baru:
- `photo` (VARCHAR 255, nullable) - Path foto guru

### 4. Tabel `announcement_targets` (BARU)
**File:** `2026-02-21-000004_CreateAnnouncementTargets.php`

Membuat tabel baru untuk menyimpan target pengumuman:
- `id` (INT UNSIGNED, PK, auto_increment)
- `announcement_id` (INT UNSIGNED, FK ke announcements)
- `target_type` (ENUM: 'role', 'class', 'student')
- `target_value` (VARCHAR 100)

### 5. Tabel `exam_schedules` (BARU)
**File:** `2026-02-21-000005_CreateExamSchedules.php`

Membuat tabel baru untuk jadwal ujian:
- `id` (INT UNSIGNED, PK, auto_increment)
- `subject_id` (INT UNSIGNED, FK ke subjects)
- `class_id` (INT UNSIGNED, FK ke classes)
- `exam_date` (DATE)
- `start_time` (TIME)
- `end_time` (TIME)
- `description` (TEXT)
- `created_at`, `updated_at` (DATETIME)

### 6. Tabel `app_license` (BARU)
**File:** `2026-02-21-000006_CreateAppLicense.php`

Membuat tabel baru untuk manajemen lisensi aplikasi:
- `id` (INT UNSIGNED, PK, auto_increment)
- `license_key` (VARCHAR 500, encrypted)
- `domain` (VARCHAR 255)
- `machine_id` (VARCHAR 500, encrypted)
- `status` (ENUM: 'active', 'inactive', 'expired')
- `last_check` (DATETIME)
- `expires_at` (DATETIME)
- `hash` (VARCHAR 255)
- `created_at`, `updated_at` (DATETIME)

### 7. Tabel `subject_scores` (BARU)
**File:** `2026-02-21-000007_CreateSubjectScores.php`

Membuat tabel baru untuk nilai mata pelajaran:
- `id` (INT UNSIGNED, PK, auto_increment)
- `student_id` (INT UNSIGNED, FK ke students)
- `subject_id` (INT UNSIGNED, FK ke subjects)
- `year_id` (INT UNSIGNED, FK ke academic_years)
- `semester` (ENUM: 'ganjil', 'genap')
- `formatif_score` (DECIMAL 5,2)
- `sumatif_score` (DECIMAL 5,2)
- `final_exam_score` (DECIMAL 5,2)
- `report_score` (DECIMAL 5,2)
- `created_at`, `updated_at` (DATETIME)

## Cara Menjalankan Migrasi

### Melalui Command Line (Recommended)

1. Buka terminal/command prompt
2. Navigasi ke root folder aplikasi
3. Jalankan perintah:

```bash
php spark migrate
```

### Melalui Browser

Akses URL berikut di browser:
```
http://localhost/siakad/migrate
```

## Rollback Migrasi

Jika terjadi masalah dan ingin membatalkan migrasi:

```bash
php spark migrate:rollback
```

Atau untuk rollback ke batch tertentu:
```bash
php spark migrate:rollback -b 1
```

## Catatan Penting

1. **BACKUP DATABASE** - Pastikan Anda sudah melakukan backup database sebelum menjalankan migrasi
2. **Testing** - Disarankan untuk test di environment development terlebih dahulu
3. **Urutan Eksekusi** - File migrasi akan dijalankan berdasarkan timestamp di nama file (000001, 000002, dst)
4. **Foreign Keys** - Beberapa tabel baru memiliki foreign key constraints, pastikan data referensi sudah ada

## Troubleshooting

### Error: "Table already exists"
Jika tabel sudah ada, Anda bisa:
1. Skip migrasi tersebut dengan menghapus file migrasi yang bermasalah
2. Atau jalankan rollback terlebih dahulu

### Error: "Foreign key constraint fails"
Pastikan tabel parent (yang direferensikan) sudah ada dan memiliki data yang valid.

### Error: "Column already exists"
Kolom mungkin sudah ditambahkan sebelumnya. Anda bisa:
1. Skip migrasi dengan menghapus file
2. Atau modifikasi file migrasi untuk mengecek keberadaan kolom terlebih dahulu

## Verifikasi

Setelah migrasi berhasil, verifikasi dengan:

1. Cek tabel migrations:
```sql
SELECT * FROM migrations ORDER BY batch DESC;
```

2. Cek struktur tabel yang diubah:
```sql
DESCRIBE users;
DESCRIBE students;
DESCRIBE teachers;
SHOW TABLES LIKE 'announcement_targets';
SHOW TABLES LIKE 'exam_schedules';
SHOW TABLES LIKE 'app_license';
SHOW TABLES LIKE 'subject_scores';
```

## Support

Jika mengalami masalah, silakan hubungi tim development atau buat issue di repository.
