# ✅ FIX: Error Penilaian - Unknown column 'students.academic_year_id'

## 🔍 Masalah

**Error**: `Unknown column 'students.academic_year_id' in 'where clause'`

**Lokasi**: Halaman penilaian kokurikuler saat klik "Tampilkan Penilaian"

**Penyebab**: 
Query di method `getStudentsPenilaian()` menggunakan kolom `students.academic_year_id` yang tidak ada di tabel `students`.

---

## ✅ Solusi

### Struktur Database yang Benar:

**Tabel `students`:**
- `id`
- `nis`
- `name`
- `class_id` (deprecated - tidak digunakan lagi)
- ... (kolom lain)

**Tabel `student_records`:**
- `id`
- `student_id` (FK ke students)
- `class_id` (FK ke classes)
- `year_id` (FK ke academic_years)
- `status` (active/graduated/moved/etc)

**Relasi**: 
- Satu siswa bisa punya banyak record (per tahun ajaran)
- Data kelas dan tahun ajaran siswa ada di `student_records`, bukan di `students`

---

## 🔧 Perubahan Kode

### Sebelum (SALAH):
```php
$students = $db->table('students')
    ->select('students.id, students.nis, students.name, classes.name as class_name')
    ->join('classes', 'classes.id = students.class_id')
    ->where('students.class_id', $document['class_id'])
    ->where('students.academic_year_id', $document['year_id']) // ❌ Kolom tidak ada!
    ->orderBy('students.name', 'ASC')
    ->get()
    ->getResultArray();
```

### Sesudah (BENAR):
```php
$students = $db->table('students')
    ->select('students.id, students.nis, students.name, classes.name as class_name')
    ->join('student_records', 'student_records.student_id = students.id')
    ->join('classes', 'classes.id = student_records.class_id')
    ->where('student_records.class_id', $document['class_id'])
    ->where('student_records.year_id', $document['year_id']) // ✅ Dari student_records
    ->where('student_records.status', 'active') // ✅ Hanya siswa aktif
    ->orderBy('students.name', 'ASC')
    ->get()
    ->getResultArray();
```

---

## 🎯 Hasil Setelah Fix

1. ✅ Error "Unknown column" hilang
2. ✅ Nama siswa muncul di tabel penilaian
3. ✅ Hanya siswa aktif yang ditampilkan
4. ✅ Data siswa sesuai dengan kelas dan tahun ajaran dokumen

---

## 🧪 Testing

### Test Case 1: Tampilkan Penilaian
1. Login sebagai Wali Kelas
2. Buka halaman penilaian kokurikuler
3. Klik "Nilai" pada dokumen
4. Pilih Dimensi dan Sub Dimensi
5. Klik "Tampilkan Penilaian"

**Expected**:
- ✅ Tidak ada error
- ✅ Tabel siswa muncul dengan nama lengkap
- ✅ Dropdown capaian tersedia (Berkembang/Cakap/Mahir)
- ✅ Input catatan tersedia

### Test Case 2: Siswa Sesuai Kelas
1. Dokumen untuk Kelas 6A
2. Tampilkan penilaian

**Expected**:
- ✅ Hanya siswa Kelas 6A yang muncul
- ✅ Siswa kelas lain tidak muncul

### Test Case 3: Siswa Aktif Saja
1. Ada siswa yang sudah lulus/pindah (status != 'active')
2. Tampilkan penilaian

**Expected**:
- ✅ Hanya siswa dengan status 'active' yang muncul
- ✅ Siswa lulus/pindah tidak muncul

---

## 📝 File yang Diubah

- ✅ `app/Controllers/Admin/Kokurikuler.php` - Method `getStudentsPenilaian()`

---

## ✅ Status

**Fixed**: ✅  
**Ready for Testing**: ✅

Silakan refresh halaman dan test penilaian lagi!
