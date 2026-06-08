# Perbaikan: Validasi Mata Pelajaran saat Restore Bank Soal

## Masalah Sebelumnya

Guru bisa restore bank soal dari mata pelajaran yang tidak ia ampu, yang menyebabkan:
1. Bank soal ter-restore tapi tidak muncul di halaman guru
2. Error message tidak jelas ("No valid context, teacher_id not set")
3. Guru bingung kenapa restore gagal

## Solusi yang Diimplementasikan

### Validasi Mata Pelajaran untuk Guru

**Aturan Baru**:
- ✅ **Guru**: Hanya bisa restore bank soal dari mata pelajaran yang ia ampu
- ✅ **Admin**: Bisa restore bank soal dari mata pelajaran apapun

### Implementasi

**File**: `app/Controllers/Admin/CbtBankSoal.php` - Method `restore()`

```php
// VALIDASI: Guru hanya bisa restore mapel yang diampu
if ($context['is_teacher'] && $context['teacher_id']) {
    $subjectId = $data['bank']['subject_id'] ?? null;
    
    if (!$subjectId) {
        $zip->close();
        return redirect()->back()->with('error', 'Bank soal tidak memiliki informasi mata pelajaran.');
    }
    
    // Cek apakah guru mengampu mata pelajaran ini
    $teacherSubjects = get_teacher_subjects($context['teacher_id']);
    $teacherSubjectIds = array_column($teacherSubjects, 'id');
    
    if (!in_array($subjectId, $teacherSubjectIds)) {
        // Ambil nama mata pelajaran untuk pesan error yang jelas
        $db = \Config\Database::connect();
        $subject = $db->table('subjects')->select('name')->where('id', $subjectId)->get()->getRow();
        $subjectName = $subject ? $subject->name : 'Unknown';
        
        $zip->close();
        log_message('warning', "Restore blocked: Teacher {$context['teacher_id']} tried to restore subject {$subjectId} ({$subjectName}) which they don't teach");
        
        return redirect()->back()->with('error', 
            "Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. " .
            "Bank soal ini untuk mata pelajaran: {$subjectName}. " .
            "Anda hanya bisa restore bank soal dari mata pelajaran yang Anda ampu."
        );
    }
    
    log_message('info', "Restore validation passed: Teacher {$context['teacher_id']} can restore subject {$subjectId}");
}
```

## Skenario Testing

### Skenario 1: Guru Restore Bank Soal Mata Pelajaran yang Diampu ✅

**Setup**:
- Guru A mengampu: Matematika (ID: 1)
- Backup: Bank Soal Matematika (subject_id: 1)

**Langkah**:
1. Login sebagai Guru A
2. Upload backup bank soal Matematika
3. Klik "Restore"

**Expected Result**:
```
✅ SUCCESS: Bank soal berhasil di-restore dengan kode: MATEMATIKA_RESTORED_1 (25 soal)
```

**Log**:
```
INFO - Restore validation passed: Teacher 5 can restore subject 1
INFO - Restore success: Bank ID=14, Code=MATEMATIKA_RESTORED_1, Teacher ID=5, Questions=25
```

### Skenario 2: Guru Restore Bank Soal Mata Pelajaran yang TIDAK Diampu ❌

**Setup**:
- Guru A mengampu: Matematika (ID: 1)
- Backup: Bank Soal Bahasa Indonesia (subject_id: 2)

**Langkah**:
1. Login sebagai Guru A
2. Upload backup bank soal Bahasa Indonesia
3. Klik "Restore"

**Expected Result**:
```
❌ ERROR: Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. 
Bank soal ini untuk mata pelajaran: Bahasa Indonesia. 
Anda hanya bisa restore bank soal dari mata pelajaran yang Anda ampu.
```

**Log**:
```
WARNING - Restore blocked: Teacher 5 tried to restore subject 2 (Bahasa Indonesia) which they don't teach
```

### Skenario 3: Admin Restore Bank Soal Mata Pelajaran Apapun ✅

**Setup**:
- Admin (tidak mengampu mata pelajaran)
- Backup: Bank Soal Bahasa Indonesia (subject_id: 2)

**Langkah**:
1. Login sebagai Admin
2. Upload backup bank soal Bahasa Indonesia
3. Klik "Restore"

**Expected Result**:
```
✅ SUCCESS: Bank soal berhasil di-restore dengan kode: BAHASA_INDONESIA_RESTORED_1 (30 soal)
```

**Log**:
```
INFO - Restore: Admin restore, teacher_id = NULL
INFO - Restore success: Bank ID=15, Code=BAHASA_INDONESIA_RESTORED_1, Teacher ID=, Questions=30
```

### Skenario 4: Backup Tanpa Informasi Mata Pelajaran ❌

**Setup**:
- Guru A mengampu: Matematika
- Backup: Bank Soal tanpa subject_id (corrupt/invalid)

**Langkah**:
1. Login sebagai Guru A
2. Upload backup bank soal tanpa subject_id
3. Klik "Restore"

**Expected Result**:
```
❌ ERROR: Bank soal tidak memiliki informasi mata pelajaran.
```

## Error Messages

### Error 1: Mata Pelajaran Tidak Diampu
```
Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. 
Bank soal ini untuk mata pelajaran: [NAMA_MAPEL]. 
Anda hanya bisa restore bank soal dari mata pelajaran yang Anda ampu.
```

**Kapan Muncul**: Guru mencoba restore bank soal dari mata pelajaran yang tidak ia ampu

**Solusi untuk User**:
1. Pastikan backup bank soal sesuai dengan mata pelajaran yang Anda ampu
2. Atau minta admin untuk restore bank soal tersebut

### Error 2: Tidak Ada Informasi Mata Pelajaran
```
Bank soal tidak memiliki informasi mata pelajaran.
```

**Kapan Muncul**: File backup corrupt atau tidak memiliki field `subject_id`

**Solusi untuk User**:
1. Cek file backup, pastikan valid
2. Export ulang bank soal dari sistem

## Keuntungan

### 1. Error Message yang Jelas
**Sebelum**:
```
WARNING - Restore: No valid context, teacher_id not set
```
User bingung, tidak tahu apa masalahnya.

**Sesudah**:
```
Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. 
Bank soal ini untuk mata pelajaran: Bahasa Indonesia.
```
User langsung paham masalahnya.

### 2. Mencegah Data Tidak Konsisten
- Guru tidak bisa restore bank soal yang tidak relevan dengan mata pelajaran yang ia ampu
- Bank soal yang ter-restore pasti sesuai dengan teaching assignment guru

### 3. Logging yang Lengkap
```
WARNING - Restore blocked: Teacher 5 tried to restore subject 2 (Bahasa Indonesia) which they don't teach
```
Admin bisa track siapa yang mencoba restore bank soal yang tidak sesuai.

## Alur Validasi

```
┌─────────────────────────────────────┐
│ User Upload Backup File             │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Extract & Parse data.json           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Cek Role User                       │
└──────────────┬──────────────────────┘
               │
       ┌───────┴───────┐
       │               │
       ▼               ▼
   [Admin]         [Guru]
       │               │
       │               ▼
       │    ┌─────────────────────────┐
       │    │ Ambil subject_id dari   │
       │    │ backup                  │
       │    └──────────┬──────────────┘
       │               │
       │               ▼
       │    ┌─────────────────────────┐
       │    │ Cek teaching_assignments│
       │    └──────────┬──────────────┘
       │               │
       │       ┌───────┴───────┐
       │       │               │
       │       ▼               ▼
       │   [Match]        [No Match]
       │       │               │
       │       │               ▼
       │       │    ┌─────────────────┐
       │       │    │ Return Error    │
       │       │    │ dengan nama     │
       │       │    │ mata pelajaran  │
       │       │    └─────────────────┘
       │       │
       └───────┴───────┐
                       │
                       ▼
            ┌─────────────────────────┐
            │ Lanjutkan Restore       │
            └─────────────────────────┘
```

## Checklist Testing

- [ ] Guru restore bank soal mata pelajaran yang diampu → Berhasil
- [ ] Guru restore bank soal mata pelajaran yang tidak diampu → Error dengan pesan jelas
- [ ] Admin restore bank soal mata pelajaran apapun → Berhasil
- [ ] Backup tanpa subject_id → Error dengan pesan jelas
- [ ] Log mencatat semua percobaan restore yang diblokir

## File yang Dimodifikasi

1. `app/Controllers/Admin/CbtBankSoal.php` - Method `restore()`
   - Tambah validasi mata pelajaran untuk guru
   - Tambah error message yang jelas
   - Tambah logging untuk tracking

## Dependencies

Menggunakan helper function yang sudah ada:
- `get_cbt_user_context()` - Untuk mendapatkan role dan teacher_id
- `get_teacher_subjects()` - Untuk mendapatkan mata pelajaran yang diampu guru

---

**Status**: ✅ IMPLEMENTED
**Date**: April 7, 2026
**Impact**: Mencegah guru restore bank soal yang tidak sesuai dengan mata pelajaran yang diampu
