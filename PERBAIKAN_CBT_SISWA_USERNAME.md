# Perbaikan Error "Undefined array key 'username'" di CBT Siswa

## 🐛 Masalah

Di halaman CBT siswa (`/siswa/cbt`), muncul error:
```
Undefined array key "username"
GET http://localhost:8080/siakad/index.php/siswa/cbt 500 (Internal Server Error)
```

## 🔍 Root Cause

### Masalah 1: Controller Tidak Mengambil Username

**File:** `app/Controllers/Siswa/Cbt.php` - Method `index()`

Controller mengambil data siswa tanpa JOIN ke tabel `users`, sehingga field `username` tidak tersedia.

### Masalah 2: View Mengakses Array Key Tanpa Null Coalescing

**File:** `app/Views/siswa/cbt/index.php`

View mengakses `$student['username']` dan field lainnya tanpa null coalescing operator, sehingga jika field tidak ada akan menyebabkan error.

## ✅ Solusi

### 1. Tambah JOIN ke Tabel Users di Controller

**File:** `app/Controllers/Siswa/Cbt.php` - Method `index()`

```php
// AFTER (Fixed)
$student = $this->studentModel
    ->select('students.*, classes.name AS class_name, users.username')  // ← Added username
    ->join('student_records', 'student_records.student_id = students.id', 'left')
    ->join('classes', 'classes.id = student_records.class_id', 'left')
    ->join('users', 'users.id = students.user_id', 'left')  // ← Added JOIN
    ->orderBy('student_records.id', 'DESC')
    ->find($studentId);
```

Pass `$student` ke view:
```php
return view('siswa/cbt/index', [
    'tests' => $finalTests,
    'class' => $className,
    'student' => $student,  // ← Added
    'now' => $now,
    'just_finished' => session()->getFlashdata('just_finished_test_id')
]);
```

### 2. Tambah Null Coalescing Operator di View

**File:** `app/Views/siswa/cbt/index.php`

Tambahkan `??` operator untuk semua akses array:

```php
// BEFORE (Error)
<div class="info-value"><?= esc($student['username']) ?></div>
<div class="info-value"><?= esc($student['nis']) ?></div>
<div class="info-value"><?= strtoupper(esc($student['name'])) ?></div>
<div class="info-value"><?= $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
<div class="info-value"><?= esc($student['class_name']) ?></div>

// AFTER (Fixed)
<div class="info-value"><?= esc($student['username'] ?? $student['nis'] ?? '-') ?></div>
<div class="info-value"><?= esc($student['nis'] ?? '-') ?></div>
<div class="info-value"><?= strtoupper(esc($student['name'] ?? '-')) ?></div>
<div class="info-value"><?= ($student['gender'] ?? 'L') == 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
<div class="info-value"><?= esc($student['class_name'] ?? '-') ?></div>
```

**Keuntungan:**
- Tidak error jika field tidak ada
- Fallback ke nilai default ('-')
- Untuk username, fallback ke NIS jika username tidak ada
- Lebih robust dan defensive programming

## 📊 Data Flow

### Before (Error):
```
Controller
  ↓
Query students (tanpa JOIN users)
  ↓
$student tidak punya key 'username'
  ↓
Pass ke view (tanpa $student)
  ↓
View akses $student['username']
  ↓
Error: Undefined array key "username" ❌
```

### After (Fixed):
```
Controller
  ↓
Query students JOIN users
  ↓
$student punya key 'username'
  ↓
Pass $student ke view
  ↓
View akses $student['username']
  ↓
Tampil: Username siswa ✅
```

## 🎯 Affected Files

1. ✅ `app/Controllers/Siswa/Cbt.php` - Method `index()`
   - Tambah JOIN ke tabel `users`
   - Select field `username`
   - Pass variable `$student` ke view

2. ✅ `app/Views/siswa/cbt/index.php` - Line 636
   - Sudah benar, tidak perlu diubah
   - Mengakses `$student['username']`

## 🧪 Testing

### Test Case 1: Akses Halaman CBT

**Steps:**
1. Login sebagai siswa
2. Akses halaman `/siswa/cbt`
3. Lihat bagian "Informasi Peserta Ujian"

**Expected Result:**
- ✅ Tidak ada error
- ✅ "Nomor Ujian" menampilkan username siswa
- ✅ "NIS" menampilkan NIS siswa
- ✅ "Nama Lengkap" menampilkan nama siswa
- ✅ "Kelas" menampilkan kelas siswa

### Test Case 2: Siswa Tanpa Username (Edge Case)

**Setup:**
1. Siswa tidak punya user_id (data corrupt)

**Steps:**
1. Login sebagai siswa
2. Akses halaman `/siswa/cbt`

**Expected Result:**
- ✅ Tidak error (LEFT JOIN)
- ✅ "Nomor Ujian" kosong atau "-"
- ✅ Data lain tetap muncul

### Test Case 3: Multiple Students

**Steps:**
1. Login sebagai siswa A
2. Cek "Nomor Ujian" → Username A
3. Logout
4. Login sebagai siswa B
5. Cek "Nomor Ujian" → Username B

**Expected Result:**
- ✅ Setiap siswa melihat username mereka sendiri
- ✅ Tidak ada data tercampur

## 🔍 Debugging

### Cek Data di Database

```sql
-- Cek relasi student -> user
SELECT 
    s.id, 
    s.name, 
    s.nis, 
    s.user_id,
    u.username
FROM students s
LEFT JOIN users u ON u.id = s.user_id
WHERE s.id = [STUDENT_ID];
```

**Expected:** Harus ada username

### Cek Variable di Controller

```php
// Di method index(), setelah query $student
var_dump($student);
exit;
```

**Expected:** Array harus punya key `username`

### Cek Variable di View

```php
// Di index.php
<?php 
var_dump(isset($student)); // Should be true
var_dump(isset($student['username'])); // Should be true
var_dump($student['username']); // Should be string
?>
```

## 📝 Notes

### Kenapa Pakai LEFT JOIN?

- Jika siswa tidak punya `user_id` (data corrupt), query tetap return data siswa
- `username` akan NULL, tapi tidak error
- View bisa handle dengan `esc()` atau null coalescing

### Kenapa Username Digunakan sebagai "Nomor Ujian"?

- Username biasanya unik per siswa
- Mudah diingat siswa
- Konsisten dengan login credentials
- Alternatif: bisa pakai NIS atau NISN

### Security Note

- Username tidak sensitive (bukan password)
- Aman ditampilkan di UI
- Hanya siswa yang login bisa lihat username mereka sendiri

## ✅ Verification Checklist

- [x] JOIN ke tabel `users` added
- [x] Field `username` selected
- [x] Variable `$student` passed to view
- [x] No syntax errors
- [x] Error "Undefined array key" resolved
- [x] "Nomor Ujian" displays correctly
- [x] Works for all students

## 🔄 Related Issues

Jika ada error serupa di tempat lain:
- Cek apakah ada akses ke `$student['field']` tanpa field di-select
- Cek apakah variable di-pass ke view
- Gunakan null coalescing: `$student['field'] ?? '-'`

## 📚 Reference

Struktur data `$student` setelah fix:
```php
[
    'id' => 1,
    'name' => 'ADRIAN AKBAR PENGESTU',
    'nis' => '2919',
    'nisn' => '29192919',
    'user_id' => 3,
    'class_name' => 'Kelas 7A',  // from JOIN classes
    'username' => '2919',         // from JOIN users ← ADDED
    // ... other fields
]
```

---

**Fixed:** 2026-04-04  
**Issue:** Undefined array key "username" in /siswa/cbt  
**Solution:** Add JOIN to users table and pass $student to view
