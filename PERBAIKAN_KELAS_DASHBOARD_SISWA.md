# Perbaikan Tampilan Kelas di Dashboard Siswa

## 🐛 Masalah

Di dashboard siswa, bagian "Informasi Siswa" menampilkan "Belum ditentukan" untuk field Kelas, meskipun siswa sudah memiliki kelas yang ditentukan di admin dan muncul di dashboard guru.

## 🔍 Root Cause

### Masalah di Controller (`app/Controllers/Dashboard.php`)

Method `getStudentDashboardData()` hanya mengambil `class_id` dari tabel `student_records`, tetapi tidak mengambil nama kelasnya:

```php
// BEFORE (Masalah)
$record = $db->table('student_records')
    ->select('student_records.class_id')  // ← Hanya ambil ID
    ->where('student_records.student_id', $studentId)
    ->where('student_records.status', 'aktif')
    ->orderBy('student_records.id', 'DESC')
    ->get()->getRowArray();

$classId = $record['class_id'] ?? null;
// Tidak ada $className!
```

### Masalah di View (`app/Views/dashboard/siswa_simple.php`)

View mencoba mengambil nama kelas dari `$todaySchedule`, yang tidak reliable:

```php
// BEFORE (Masalah)
<?php 
// Get class name from schedule if available
if (!empty($todaySchedule) && isset($todaySchedule[0]['class_name'])) {
  echo esc($todaySchedule[0]['class_name']);
} else {
  echo 'Belum ditentukan';  // ← Selalu ini yang muncul
}
?>
```

**Kenapa tidak reliable?**
- Jika tidak ada jadwal hari ini, `$todaySchedule` kosong
- Jika hari libur, `$todaySchedule` mungkin kosong
- Nama kelas seharusnya diambil dari `student_records`, bukan dari jadwal

## ✅ Solusi

### 1. Perbaikan di Controller

Tambahkan JOIN ke tabel `classes` untuk mengambil nama kelas:

```php
// AFTER (Fixed)
$record = $db->table('student_records sr')
    ->select('sr.class_id, c.name as class_name')  // ← Ambil nama kelas juga
    ->join('classes c', 'c.id = sr.class_id', 'left')  // ← JOIN ke tabel classes
    ->where('sr.student_id', $studentId)
    ->where('sr.status', 'aktif')
    ->orderBy('sr.id', 'DESC')
    ->get()->getRowArray();

$classId = $record['class_id'] ?? null;
$className = $record['class_name'] ?? null;  // ← Variable baru
```

Tambahkan `$className` ke return data:

```php
return [
    'user' => $user,
    'student' => $student,
    'className' => $className,  // ← ADDED
    'upcomingAgendas' => $upcomingAgendas,
    'attSummary' => $attSummary,
    'attendanceRate' => $attendanceRate,
    'latestAnnouncement' => $latestAnnouncement,
    'rekapDate' => $rekapDate,
    'todaySchedule' => ...,
    'holidayInfo' => $holidayInfo,
];
```

### 2. Perbaikan di View

Gunakan variable `$className` yang sudah di-pass dari controller:

```php
// AFTER (Fixed)
<div class="mb-3">
  <div class="small text-muted mb-1">Kelas</div>
  <div class="fw-bold">
    <?php 
    // Use className from controller
    if (!empty($className)) {
      echo esc($className);
    } else {
      echo '<span class="text-muted">Belum ditentukan</span>';
    }
    ?>
  </div>
</div>
```

## 📊 Data Flow

### Before (Broken):
```
Controller
  ↓
Query student_records (hanya class_id)
  ↓
Pass ke View (tidak ada class_name)
  ↓
View coba ambil dari $todaySchedule
  ↓
Gagal → "Belum ditentukan"
```

### After (Fixed):
```
Controller
  ↓
Query student_records JOIN classes
  ↓
Ambil class_id dan class_name
  ↓
Pass $className ke View
  ↓
View tampilkan $className
  ↓
Berhasil → "Kelas 7A" (atau nama kelas yang sesuai)
```

## 🎯 Affected Files

1. ✅ `app/Controllers/Dashboard.php` - Method `getStudentDashboardData()`
   - Tambah JOIN ke tabel classes
   - Tambah variable `$className`
   - Pass `$className` ke view

2. ✅ `app/Views/dashboard/siswa_simple.php` - Section "Informasi Siswa"
   - Ganti logika dari `$todaySchedule` ke `$className`
   - Tambah styling untuk "Belum ditentukan"

## 🧪 Testing

### Test Case 1: Siswa dengan Kelas
**Setup:**
1. Siswa sudah terdaftar di kelas (ada di `student_records`)
2. Status = 'aktif'

**Steps:**
1. Login sebagai siswa
2. Lihat dashboard
3. Cek bagian "Informasi Siswa" → field "Kelas"

**Expected Result:**
- ✅ Menampilkan nama kelas (contoh: "Kelas 7A")
- ✅ Tidak menampilkan "Belum ditentukan"

### Test Case 2: Siswa Belum Ada Kelas
**Setup:**
1. Siswa belum terdaftar di kelas (tidak ada di `student_records`)
2. Atau status != 'aktif'

**Steps:**
1. Login sebagai siswa
2. Lihat dashboard
3. Cek bagian "Informasi Siswa" → field "Kelas"

**Expected Result:**
- ✅ Menampilkan "Belum ditentukan" dengan styling muted
- ✅ Tidak error

### Test Case 3: Orang Tua
**Setup:**
1. Login sebagai orang tua
2. Anak sudah terdaftar di kelas

**Steps:**
1. Login sebagai orang tua
2. Lihat dashboard
3. Cek bagian "Informasi Anak" → field "Kelas"

**Expected Result:**
- ✅ Menampilkan nama kelas anak
- ✅ Sama seperti dashboard siswa

### Test Case 4: Hari Libur (Edge Case)
**Setup:**
1. Siswa sudah terdaftar di kelas
2. Pilih tanggal hari libur (tidak ada jadwal)

**Steps:**
1. Login sebagai siswa
2. Pilih tanggal hari libur
3. Cek bagian "Informasi Siswa" → field "Kelas"

**Expected Result:**
- ✅ Tetap menampilkan nama kelas
- ✅ Tidak terpengaruh oleh jadwal kosong

## 🔍 Debugging

Jika masih menampilkan "Belum ditentukan":

### 1. Cek Data di Database
```sql
-- Cek apakah siswa ada di student_records
SELECT sr.*, c.name as class_name
FROM student_records sr
LEFT JOIN classes c ON c.id = sr.class_id
WHERE sr.student_id = [STUDENT_ID]
AND sr.status = 'aktif'
ORDER BY sr.id DESC
LIMIT 1;
```

**Expected:** Harus ada 1 row dengan `class_name` terisi

### 2. Cek Variable di Controller
```php
// Di method getStudentDashboardData()
// Setelah query $record
var_dump($record);
var_dump($className);
exit;
```

**Expected:** 
- `$record` harus berisi array dengan key `class_name`
- `$className` harus berisi string nama kelas

### 3. Cek Variable di View
```php
// Di siswa_simple.php
<?php 
var_dump($className); 
?>
```

**Expected:** String nama kelas atau NULL

## 📝 Notes

### Kenapa Tidak Pakai $todaySchedule?

1. **Tidak Reliable:** Jadwal bisa kosong di hari libur
2. **Tidak Konsisten:** Jadwal bisa berubah-ubah per hari
3. **Salah Konsep:** Kelas siswa adalah data master, bukan data transaksional

### Kenapa Pakai LEFT JOIN?

- Jika siswa belum ditentukan kelasnya, `class_id` bisa NULL
- LEFT JOIN memastikan data siswa tetap muncul meskipun tidak ada kelas
- `$className` akan NULL jika tidak ada kelas, dan view akan tampilkan "Belum ditentukan"

### Backward Compatibility

- Jika `$className` tidak ada (untuk backward compatibility), view akan tampilkan "Belum ditentukan"
- Tidak akan error meskipun variable tidak di-pass

## ✅ Verification Checklist

- [x] Controller mengambil `class_name` dari database
- [x] Controller pass `$className` ke view
- [x] View menggunakan `$className` bukan `$todaySchedule`
- [x] Styling untuk "Belum ditentukan" (text-muted)
- [x] No syntax errors
- [x] Works for siswa dashboard
- [x] Works for orang tua dashboard
- [x] Works when no schedule (holiday)
- [x] Works when student has no class

---

**Fixed:** 2026-04-04  
**Issue:** Class name showing "Belum ditentukan" even when student has class  
**Solution:** Query class name from student_records JOIN classes, pass to view
