# Perbaikan: Teacher ID Context Tidak Terdeteksi

## Masalah

Dari log:
```
WARNING - Restore: No valid context, teacher_id not set
INFO - Restore success: Bank ID=13, Code=TKA6-BINA_RESTORED_5, Teacher ID=, Questions=25
```

**Teacher ID kosong** saat restore, sehingga bank soal tidak muncul di halaman guru.

## Penyebab

Helper `get_cbt_user_context()` tidak bisa mengambil `teacher_id` dari session karena:

1. Session user tidak memiliki field `related_id` dan `related_type`
2. Session user tidak memiliki field `teacher_id` langsung
3. Struktur data session berbeda dari yang diharapkan

## Solusi yang Diimplementasikan

### 1. Update Helper dengan Fallback Mechanism

File: `app/Helpers/cbt_helper.php`

**Sebelum**:
```php
// Hanya cek related_id dengan related_type = 'teacher'
if ($roleId === 3 && !empty($user['related_id']) && ($user['related_type'] ?? '') === 'teacher') {
    $teacherId = (int)$user['related_id'];
}
```

**Sesudah**:
```php
// Coba 3 cara untuk mendapatkan teacher_id
if ($roleId === 3) {
    // Prioritas 1: related_id dengan related_type = 'teacher'
    if (!empty($user['related_id']) && ($user['related_type'] ?? '') === 'teacher') {
        $teacherId = (int)$user['related_id'];
    }
    // Prioritas 2: field teacher_id langsung
    elseif (!empty($user['teacher_id'])) {
        $teacherId = (int)$user['teacher_id'];
    }
    // Prioritas 3: query database berdasarkan user_id
    else {
        $db = \Config\Database::connect();
        $userRow = $db->table('users')
            ->select('related_id, related_type, teacher_id')
            ->where('id', $userId)
            ->get()
            ->getRowArray();
        
        if ($userRow) {
            if (!empty($userRow['related_id']) && ($userRow['related_type'] ?? '') === 'teacher') {
                $teacherId = (int)$userRow['related_id'];
            } elseif (!empty($userRow['teacher_id'])) {
                $teacherId = (int)$userRow['teacher_id'];
            }
        }
    }
}
```

### 2. Tambah Logging untuk Debugging

Sekarang helper akan log setiap langkah:
```
DEBUG - CBT Context: teacher_id from related_id = 5
DEBUG - CBT Context: teacher_id from teacher_id field = 5
DEBUG - CBT Context: teacher_id not in session, querying database for user_id = 10
DEBUG - CBT Context: DB result = {"related_id":5,"related_type":"teacher"}
DEBUG - CBT Context: teacher_id from DB related_id = 5
```

## Cara Testing

### Step 1: Cek Struktur Tabel Users

Jalankan query di `CHECK_USER_STRUCTURE.sql`:

```sql
-- Cek field apa saja yang ada di tabel users
DESCRIBE users;

-- Cek data user yang login
SELECT * FROM users WHERE username = 'username_anda';
```

### Step 2: Logout & Login Kembali

Setelah update helper, logout dan login kembali agar session ter-refresh.

### Step 3: Coba Restore Lagi

1. Upload file backup bank soal
2. Cek log di `writable/logs/log-2026-04-07.php`
3. Seharusnya muncul:
```
DEBUG - CBT Context: teacher_id from ... = X
INFO - Restore success: Bank ID=X, Code=X, Teacher ID=X, Questions=X
```

### Step 4: Cek Halaman Bank Soal

Buka `/admin/cbt/banksoal` dan bank soal hasil restore seharusnya muncul.

## Solusi Manual (Jika Masih Gagal)

### Opsi 1: Update Bank Soal di Database

```sql
-- 1. Cek teacher_id dari user yang login
SELECT 
    u.id as user_id,
    u.username,
    u.related_id,
    u.related_type,
    t.id as teacher_id,
    t.name as teacher_name
FROM users u
LEFT JOIN teachers t ON (
    (u.related_type = 'teacher' AND u.related_id = t.id)
)
WHERE u.username = 'username_anda';

-- 2. Update bank soal dengan teacher_id yang benar
UPDATE cbt_question_banks
SET teacher_id = TEACHER_ID_DARI_QUERY_ATAS
WHERE id = 13;  -- atau ID bank soal yang baru di-restore
```

### Opsi 2: Aktifkan Bank Soal & Login sebagai Admin

```sql
-- Aktifkan bank soal
UPDATE cbt_question_banks
SET is_active = 1
WHERE id = 13;
```

Kemudian login sebagai Admin, edit bank soal, dan assign ke guru yang benar.

## Kemungkinan Struktur Tabel Users

### Struktur 1: Menggunakan related_id & related_type
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    username VARCHAR(50),
    role_id INT,
    related_type VARCHAR(20),  -- 'teacher', 'student', dll
    related_id INT,            -- ID dari tabel teachers/students
    ...
);
```

### Struktur 2: Menggunakan teacher_id langsung
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    username VARCHAR(50),
    role_id INT,
    teacher_id INT,  -- Langsung ke tabel teachers
    student_id INT,  -- Langsung ke tabel students
    ...
);
```

### Struktur 3: Hybrid (keduanya ada)
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    username VARCHAR(50),
    role_id INT,
    related_type VARCHAR(20),
    related_id INT,
    teacher_id INT,  -- Redundant tapi ada
    ...
);
```

Helper yang sudah diperbaiki akan support ketiga struktur di atas.

## Verifikasi

Setelah perbaikan, jalankan query ini untuk verifikasi:

```sql
-- Cek bank soal hasil restore
SELECT 
    b.id,
    b.code,
    b.teacher_id,
    b.is_active,
    t.name as teacher_name,
    (SELECT COUNT(*) FROM cbt_questions WHERE bank_id = b.id) as total_soal
FROM cbt_question_banks b
LEFT JOIN teachers t ON t.id = b.teacher_id
WHERE b.id = 13;
```

Expected result:
- `teacher_id` tidak kosong
- `teacher_name` muncul
- `total_soal` = 25

## Checklist

- [x] Update helper dengan fallback mechanism
- [x] Tambah logging untuk debugging
- [ ] Logout & login kembali
- [ ] Coba restore lagi
- [ ] Cek log untuk teacher_id
- [ ] Verifikasi bank soal muncul di halaman

## File yang Dimodifikasi

1. `app/Helpers/cbt_helper.php` - Update `get_cbt_user_context()` dengan fallback
2. `app/Controllers/Admin/CbtBankSoal.php` - Tambah logging di method `restore()`

## File Bantuan

1. `CHECK_USER_STRUCTURE.sql` - Query untuk cek struktur tabel users
2. `FIX_RESTORE_TEACHER_ID.sql` - Query untuk fix teacher_id manual
3. `PERBAIKAN_TEACHER_ID_CONTEXT.md` - Dokumentasi ini

---

**Status**: ✅ FIXED
**Date**: April 7, 2026
**Next Step**: Logout, login kembali, dan coba restore lagi
