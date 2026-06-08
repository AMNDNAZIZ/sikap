# Perbaikan Bug Model

Selain migrasi database, ditemukan beberapa inkonsistensi di model yang telah diperbaiki.

## Bug yang Diperbaiki

### 1. StudentAcademicRecordModel
**Masalah:** Menggunakan `year_id` di allowedFields, padahal di database menggunakan `academic_year_id`

**Perbaikan:**
```php
// SEBELUM (SALAH)
protected $allowedFields = [
    'student_id', 'year_id', 'class_id', 'status'
];

// SESUDAH (BENAR)
protected $allowedFields = [
    'student_id', 'academic_year_id', 'class_id', 'status', 'note'
];
```

**Dampak:** Field `year_id` tidak akan bisa disimpan, dan field `note` tidak bisa digunakan.

---

### 2. FinalExamScoresModel
**Masalah:** Memiliki field `semester` di allowedFields, padahal tabel `final_exam_scores` tidak memiliki kolom semester

**Perbaikan:**
```php
// SEBELUM (SALAH)
protected $allowedFields = [
    'student_id', 'subject_id', 'year_id', 'semester',
    'score', 'created_at', 'updated_at'
];

// SESUDAH (BENAR)
protected $allowedFields = [
    'student_id', 'subject_id', 'year_id',
    'score', 'created_at', 'updated_at'
];
```

**Dampak:** Tidak ada dampak langsung karena field tidak ada di database, tapi bisa membingungkan developer.

---

### 3. GradesModel
**Masalah 1:** Field `final_exam` tidak ada di allowedFields, padahal ada di database

**Perbaikan:**
```php
// SEBELUM (SALAH)
protected $allowedFields = [
    'student_id', 'subject_id', 'year_id', 'semester',
    'formative_score', 'summative_score', 'final_exam_score', 
    'report_score', 'created_at', 'updated_at'
];

// SESUDAH (BENAR)
protected $allowedFields = [
    'student_id', 'subject_id', 'year_id', 'semester',
    'formative_score', 'summative_score', 'final_exam_score', 'final_exam',
    'report_score', 'created_at', 'updated_at'
];
```

**Dampak:** Field `final_exam` tidak bisa disimpan melalui model.

---

**Masalah 2:** Query ke `final_exam_scores` menggunakan filter `semester`, padahal tabel tidak punya kolom semester

**Perbaikan:**
```php
// SEBELUM (SALAH)
$finalExam = $this->db->table('final_exam_scores')
    ->selectAvg('score')
    ->where([
        'student_id' => $studentId,
        'subject_id' => $subjectId,
        'year_id'    => $yearId,
        'semester'   => $semester  // ← KOLOM INI TIDAK ADA!
    ])->get()->getRow()->score ?? 0;

// SESUDAH (BENAR)
$finalExam = $this->db->table('final_exam_scores')
    ->selectAvg('score')
    ->where([
        'student_id' => $studentId,
        'subject_id' => $subjectId,
        'year_id'    => $yearId
    ])->get()->getRow()->score ?? 0;
```

**Dampak:** Query akan error atau tidak mengembalikan hasil yang benar.

---

**Masalah 3:** Query ke `subject_weights` menggunakan filter `semester`, padahal tabel tidak punya kolom semester

**Perbaikan:**
```php
// SEBELUM (SALAH)
$weightModel = new \App\Models\SubjectWeightsModel();
$weights = $weightModel->where([
    'subject_id' => $subjectId,
    'year_id'    => $yearId,
    'semester'   => $semester  // ← KOLOM INI TIDAK ADA!
])->first();

// SESUDAH (BENAR)
$weightModel = new \App\Models\SubjectWeightModel();
$weights = $weightModel->where([
    'subject_id' => $subjectId,
    'year_id'    => $yearId
])->first();
```

**Dampak:** Query akan error atau tidak mengembalikan hasil yang benar. Juga memperbaiki nama class dari `SubjectWeightsModel` ke `SubjectWeightModel` (sesuai nama file).

---

## Catatan Penting

### Standar Penamaan Kolom di Aplikasi

Aplikasi ini menggunakan 2 standar penamaan untuk foreign key ke tabel `academic_years`:

1. **`academic_year_id`** - Digunakan di:
   - student_records
   - teaching_assignments
   - cbt_exams
   - teacher_careers

2. **`year_id`** - Digunakan di:
   - subject_materials
   - subject_weights
   - grades
   - final_exam_scores
   - summative_scores
   - subject_scores (tabel baru)

**Rekomendasi:** Untuk konsistensi, sebaiknya gunakan satu standar saja (`academic_year_id`) di masa depan.

---

## File yang Diperbaiki

1. `app/Models/StudentAcademicRecordModel.php`
2. `app/Models/FinalExamScoresModel.php`
3. `app/Models/GradesModel.php`

---

## Testing Setelah Perbaikan

Setelah perbaikan ini, test fitur-fitur berikut:

1. **Student Records**
   - Tambah/edit record siswa
   - Pastikan field `academic_year_id` dan `note` bisa disimpan

2. **Final Exam Scores**
   - Input nilai ujian akhir
   - Pastikan tidak ada error

3. **Grades / Nilai Rapor**
   - Hitung nilai rapor
   - Pastikan perhitungan benar
   - Pastikan field `final_exam` bisa disimpan

---

## Verifikasi

Jalankan query berikut untuk memastikan struktur tabel sesuai:

```sql
-- Cek kolom di student_records
DESCRIBE student_records;
-- Harus ada: academic_year_id (bukan year_id)

-- Cek kolom di final_exam_scores
DESCRIBE final_exam_scores;
-- Tidak boleh ada: semester

-- Cek kolom di grades
DESCRIBE grades;
-- Harus ada: final_exam

-- Cek kolom di subject_weights
DESCRIBE subject_weights;
-- Tidak boleh ada: semester
```

---

**Tanggal Perbaikan:** 21 Februari 2026  
**Status:** Selesai  
**Tested:** Belum (perlu testing manual)
