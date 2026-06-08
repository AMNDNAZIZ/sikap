# Analisis dan Perbaikan Isolasi Data CBT Berdasarkan Guru

## Tujuan
Memastikan setiap guru hanya bisa mengakses, mengelola, dan menggunakan data CBT yang mereka buat sendiri, kecuali admin yang bisa melihat semua.

## Struktur Role
- `role_id = 1`: Admin (akses penuh)
- `role_id = 2`: Kepala Sekolah (readonly)
- `role_id = 3`: Guru (akses terbatas ke data sendiri)

## Modul CBT yang Perlu Diperbaiki

### 1. Nama Ujian (cbt_exam_names)
**Status**: Perlu ditambahkan field `created_by`
**Kebutuhan**:
- Tambah kolom `created_by` (user_id atau teacher_id)
- Guru hanya bisa melihat dan menggunakan nama ujian yang ia buat
- Admin bisa melihat semua

### 2. Bank Soal (cbt_question_banks)
**Status**: Sudah ada field `teacher_id` ✓
**Kebutuhan**:
- Guru hanya bisa CRUD bank soal miliknya
- Admin bisa CRUD semua bank soal
**Perbaikan**:
- Fix role_id check (sekarang salah: role_id=2, seharusnya role_id=3)
- Tambah validasi ownership di semua method (detail, edit, delete, dll)

### 3. Status Tes (cbt_test_status)
**Status**: Sudah ada field `created_by` ✓
**Kebutuhan**:
- Guru hanya bisa membuat status tes dari bank soal miliknya
- Guru hanya bisa menambahkan kelas yang ia ampu
- Guru hanya bisa CRUD status tes yang ia buat
- Admin bisa CRUD semua

### 4. Aktivitas (cbt_sessions / cbt_student_sessions)
**Status**: Perlu validasi berdasarkan `created_by` dari test_status
**Kebutuhan**:
- Guru hanya bisa melihat aktivitas ujian yang ia jadwalkan
- Admin bisa melihat semua

### 5. Konversi Nilai
**Status**: Perlu validasi berdasarkan subject_id dan teaching assignment
**Kebutuhan**:
- Guru hanya bisa konversi nilai untuk mata pelajaran yang ia ampu
- Admin bisa konversi semua

## Masalah yang Ditemukan

### 1. Role ID Salah di CbtBankSoal.php
```php
// SALAH
if ($roleId == 1) {
    // Admin
} elseif ($roleId == 2 && $teacherId) {  // ← SALAH! Seharusnya role_id = 3
    // Guru
}
```

### 2. Tidak Ada Validasi Ownership
Banyak method tidak memvalidasi apakah user berhak mengakses/mengubah data:
- `detail($id)` - tidak cek ownership
- `edit($id)` - tidak cek ownership
- `delete($id)` - tidak cek ownership
- `copy($id)` - tidak cek ownership

### 3. Session Key Tidak Konsisten
Beberapa tempat menggunakan `$user['teacher_id']`, seharusnya `$user['related_id']`

### 4. Nama Ujian Tidak Ada Isolasi
Tabel `cbt_exam_names` tidak memiliki field untuk tracking pembuat

## Rencana Perbaikan

### Phase 1: Database Migration
1. Tambah kolom `created_by` ke tabel `cbt_exam_names`
2. Update data existing dengan default admin

### Phase 2: Model Updates
1. Update `CbtExamNameModel` - tambah `created_by` ke allowedFields
2. Update `CbtBankSoalModel` - tambah method untuk validasi ownership
3. Update `CbtTestStatusModel` - tambah method untuk validasi ownership

### Phase 3: Controller Updates

#### CbtExamName Controller
- `index()`: Filter berdasarkan created_by untuk guru
- `store()`: Set created_by saat create
- `update()`: Validasi ownership
- `delete()`: Validasi ownership

#### CbtBankSoal Controller
- Fix role_id check (2 → 3)
- Tambah validasi ownership di semua method CRUD
- `detail()`: Cek ownership
- `edit()`: Cek ownership
- `delete()`: Cek ownership
- `copy()`: Cek ownership
- `tambahSoal()`: Cek ownership
- `parseSoal()`: Cek ownership

#### CbtTestStatus Controller
- `index()`: Filter berdasarkan created_by
- `create()`: Filter bank soal berdasarkan teacher_id
- `create()`: Filter kelas berdasarkan teaching assignment
- `store()`: Set created_by
- `update()`: Validasi ownership
- `delete()`: Validasi ownership

#### CbtAktivitas Controller
- `index()`: Filter berdasarkan created_by dari test_status
- `detail()`: Validasi ownership

#### CbtConvertNilai Controller
- `index()`: Filter berdasarkan teaching assignment
- `convert()`: Validasi teaching assignment

### Phase 4: Helper Functions
Buat helper untuk:
- `getUserRole()`: Get role_id dan related_id
- `isAdmin()`: Check if admin
- `isTeacher()`: Check if teacher
- `getTeacherId()`: Get teacher_id dari session
- `canAccessBank($bankId)`: Check ownership bank soal
- `canAccessTestStatus($testStatusId)`: Check ownership test status
- `getTeachingClasses($teacherId)`: Get kelas yang diampu
- `getTeachingSubjects($teacherId)`: Get mata pelajaran yang diampu

## Prioritas
1. **HIGH**: Fix role_id check di semua controller CBT
2. **HIGH**: Tambah validasi ownership di Bank Soal
3. **HIGH**: Tambah validasi ownership di Status Tes
4. **MEDIUM**: Tambah field created_by ke Nama Ujian
5. **MEDIUM**: Filter aktivitas berdasarkan ownership
6. **MEDIUM**: Filter konversi nilai berdasarkan teaching assignment
7. **LOW**: Tambah audit log untuk tracking perubahan

## Testing Checklist
- [ ] Admin bisa melihat semua bank soal
- [ ] Guru hanya bisa melihat bank soal miliknya
- [ ] Guru tidak bisa edit/delete bank soal guru lain
- [ ] Guru tidak bisa copy bank soal guru lain
- [ ] Guru hanya bisa membuat status tes dari bank soal miliknya
- [ ] Guru hanya bisa menambahkan kelas yang ia ampu
- [ ] Guru hanya bisa melihat aktivitas ujian yang ia jadwalkan
- [ ] Guru hanya bisa konversi nilai mata pelajaran yang ia ampu
- [ ] Kepala Sekolah bisa melihat semua (readonly)
