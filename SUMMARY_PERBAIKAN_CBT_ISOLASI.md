# Summary Perbaikan CBT Isolasi Data Guru

## Yang Sudah Dikerjakan ✓

### 1. Database Migration
- [x] Buat migration untuk menambahkan `created_by` ke `cbt_exam_names`
- File: `app/Database/Migrations/2026-04-07-000001_AddCreatedByToCbtExamNames.php`

### 2. Helper Functions
- [x] Buat helper `cbt_helper.php` dengan functions:
  - `get_cbt_user_context()` - Get user role dan teacher_id
  - `can_access_cbt_bank()` - Validasi akses bank soal
  - `can_access_cbt_test_status()` - Validasi akses status tes
  - `can_access_cbt_exam_name()` - Validasi akses nama ujian
  - `get_teacher_classes()` - Get kelas yang diampu guru
  - `get_teacher_subjects()` - Get mata pelajaran yang diampu guru
  - `can_convert_subject_score()` - Validasi konversi nilai

### 3. Model Updates
- [x] Update `CbtExamNameModel` - tambah `created_by` dan method `getFiltered()`

### 4. Controller CbtBankSoal - Partial
- [x] Load helper di constructor
- [x] Fix method `index()` - ganti role_id check
- [x] Fix method `storeAjax()` - ganti role_id check dan session key

## Yang Perlu Dilakukan Selanjutnya

### 5. Controller CbtBankSoal - Tambah Validasi Ownership

Tambahkan validasi di awal setiap method berikut:

```php
// Method detail($id)
if (!can_access_cbt_bank($id)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}

// Method copy($id)
if (!can_access_cbt_bank($id)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}

// Method print($id)
if (!can_access_cbt_bank($id)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}

// Method edit_Soal($bankId, $questionId)
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}

// Method updateSoal($bankId, $questionId)
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}

// Method delete($id)
if (!can_access_cbt_bank($id)) {
    return $this->response->setJSON([
        'success' => false,
        'message' => 'Anda tidak memiliki akses ke bank soal ini.'
    ]);
}

// Method toggle($id)
if (!can_access_cbt_bank($id)) {
    return $this->response->setJSON([
        'success' => false,
        'message' => 'Anda tidak memiliki akses ke bank soal ini.'
    ]);
}

// Method tambahSoal($bankId)
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}

// Method parseSoal() - setelah get bank_id
$bankId = $this->request->getPost('bank_id');
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}

// Method saveParsedSoal($bankId)
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}

// Method backup($id)
if (!can_access_cbt_bank($id)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

### 6. Controller CbtExamName

Buat atau update controller untuk:
- Filter nama ujian berdasarkan `created_by`
- Set `created_by` saat create
- Validasi ownership saat update/delete

### 7. Controller CbtTestStatus

Update untuk:
- Filter bank soal berdasarkan `teacher_id` saat create
- Filter kelas berdasarkan teaching assignment
- Set `created_by` saat create
- Validasi ownership saat update/delete
- Filter list berdasarkan `created_by` untuk guru

### 8. Controller CbtAktivitas

Update untuk:
- Filter aktivitas berdasarkan `created_by` dari test_status
- Guru hanya bisa lihat aktivitas ujian yang ia jadwalkan

### 9. Controller CbtConvertNilai

Update untuk:
- Filter berdasarkan teaching assignment
- Validasi subject_id dengan `can_convert_subject_score()`

### 10. Autoload Helper

Edit `app/Config/Autoload.php`:
```php
public $helpers = ['authorization', 'cbt'];
```

### 11. Run Migration

```bash
php spark migrate
```

## Cara Melanjutkan Perbaikan

### Opsi 1: Manual (Recommended untuk Understanding)
1. Buka setiap controller yang disebutkan di atas
2. Tambahkan validasi ownership sesuai dokumentasi
3. Test setiap perubahan

### Opsi 2: Batch Update
Saya bisa membantu membuat script atau melakukan perubahan batch untuk semua controller sekaligus.

## Testing Plan

Setelah semua perbaikan selesai, test skenario berikut:

### Bank Soal
1. Login sebagai Admin → Bisa lihat semua bank soal
2. Login sebagai Guru A → Hanya lihat bank soal milik Guru A
3. Guru A coba akses URL detail bank soal Guru B → Error/redirect
4. Guru A coba edit bank soal Guru B → Error/redirect
5. Guru A coba delete bank soal Guru B → Error message
6. Guru A coba copy bank soal Guru B → Error/redirect

### Nama Ujian
1. Guru A buat nama ujian → Tersimpan dengan created_by = user_id Guru A
2. Guru A lihat list nama ujian → Hanya miliknya + yang legacy (created_by = null)
3. Guru A coba edit nama ujian Guru B → Error

### Status Tes
1. Guru A buat status tes → Dropdown bank soal hanya milik Guru A
2. Guru A buat status tes → Dropdown kelas hanya yang diampu
3. Guru A lihat list status tes → Hanya yang ia buat
4. Guru A coba edit status tes Guru B → Error

### Aktivitas
1. Guru A lihat aktivitas → Hanya ujian yang dijadwalkan Guru A
2. Admin lihat aktivitas → Semua ujian

### Konversi Nilai
1. Guru A konversi nilai → Hanya mata pelajaran yang diampu
2. Admin konversi nilai → Semua mata pelajaran

## Estimasi Waktu
- Menambahkan validasi di CbtBankSoal: 30 menit
- Update CbtExamName: 20 menit
- Update CbtTestStatus: 40 menit
- Update CbtAktivitas: 20 menit
- Update CbtConvertNilai: 20 menit
- Testing: 60 menit
- **Total: ~3 jam**

## Prioritas Eksekusi
1. **CRITICAL**: Tambah validasi di CbtBankSoal (mencegah guru akses bank soal guru lain)
2. **HIGH**: Update CbtTestStatus (mencegah guru jadwalkan ujian dengan bank soal guru lain)
3. **MEDIUM**: Update CbtExamName, CbtAktivitas, CbtConvertNilai
4. **LOW**: Testing menyeluruh dan dokumentasi

## Catatan Penting
- Backup database sebelum run migration
- Test di development environment dulu
- Pastikan helper di-autoload
- Data existing tanpa teacher_id/created_by akan accessible oleh semua (legacy support)
