# Perbaikan: Validasi Restore untuk Guru Baru (Tanpa Teaching Assignment)

## Masalah

Guru baru yang belum diberikan tugas mengajar (plotting pembelajaran) mencoba restore bank soal:
- ❌ Restore gagal tanpa error message
- ❌ Tidak ada keterangan penyebab kegagalan
- ❌ Guru bingung kenapa tidak bisa restore

## Penyebab

Kode sebelumnya tidak mengecek apakah guru memiliki teaching assignment atau tidak:

```php
$teacherSubjects = get_teacher_subjects($context['teacher_id']);
$teacherSubjectIds = array_column($teacherSubjects, 'id');

// Jika $teacherSubjects kosong, $teacherSubjectIds juga kosong
// in_array($subjectId, []) selalu return false
// Tapi tidak ada error message yang jelas
```

## Solusi

Tambahkan validasi eksplisit untuk guru tanpa teaching assignment:

```php
if ($context['is_teacher'] && $context['teacher_id']) {
    // 1. CEK APAKAH GURU SUDAH MEMILIKI TEACHING ASSIGNMENT
    $teacherSubjects = get_teacher_subjects($context['teacher_id']);
    
    if (empty($teacherSubjects)) {
        $zip->close();
        log_message('warning', "Restore blocked: Teacher {$context['teacher_id']} has no teaching assignments");
        
        return redirect()->back()->with('error', 
            "Anda belum memiliki tugas mengajar (plotting pembelajaran). " .
            "Silakan hubungi admin untuk menambahkan tugas mengajar Anda terlebih dahulu sebelum melakukan restore bank soal."
        );
    }
    
    // 2. CEK APAKAH MATA PELAJARAN SESUAI
    $subjectId = $data['bank']['subject_id'] ?? null;
    $teacherSubjectIds = array_column($teacherSubjects, 'id');
    
    if (!in_array($subjectId, $teacherSubjectIds)) {
        // Error dengan daftar mata pelajaran yang diampu
        $teacherSubjectNames = array_column($teacherSubjects, 'name');
        $subjectList = implode(', ', $teacherSubjectNames);
        
        return redirect()->back()->with('error', 
            "Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. " .
            "Bank soal ini untuk mata pelajaran: <strong>{$subjectName}</strong>. " .
            "Mata pelajaran yang Anda ampu: <strong>{$subjectList}</strong>."
        );
    }
}
```

## Skenario Testing

### Skenario 1: Guru Baru Tanpa Teaching Assignment ❌

**Setup**:
- Guru Baru (ID: 10)
- Teaching assignments: KOSONG (belum di-plotting)
- Backup: Bank Soal Matematika

**Langkah**:
1. Login sebagai Guru Baru
2. Upload backup bank soal Matematika
3. Klik "Restore"

**Expected Result**:
```
❌ ERROR: Anda belum memiliki tugas mengajar (plotting pembelajaran). 
Silakan hubungi admin untuk menambahkan tugas mengajar Anda terlebih dahulu 
sebelum melakukan restore bank soal.
```

**Log**:
```
WARNING - Restore blocked: Teacher 10 has no teaching assignments
```

**Solusi untuk User**:
1. Hubungi admin
2. Minta admin untuk menambahkan tugas mengajar di menu "Plotting Pembelajaran"
3. Setelah ada tugas mengajar, coba restore lagi

### Skenario 2: Guru dengan Teaching Assignment, Restore Mapel yang Diampu ✅

**Setup**:
- Guru A (ID: 5)
- Teaching assignments: Matematika, Fisika
- Backup: Bank Soal Matematika

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
INFO - Restore success: Bank ID=16, Code=MATEMATIKA_RESTORED_1, Teacher ID=5, Questions=25
```

### Skenario 3: Guru dengan Teaching Assignment, Restore Mapel yang TIDAK Diampu ❌

**Setup**:
- Guru A (ID: 5)
- Teaching assignments: Matematika, Fisika
- Backup: Bank Soal Bahasa Indonesia

**Langkah**:
1. Login sebagai Guru A
2. Upload backup bank soal Bahasa Indonesia
3. Klik "Restore"

**Expected Result**:
```
❌ ERROR: Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. 
Bank soal ini untuk mata pelajaran: Bahasa Indonesia. 
Mata pelajaran yang Anda ampu: Matematika, Fisika.
```

**Log**:
```
WARNING - Restore blocked: Teacher 5 tried to restore subject 2 (Bahasa Indonesia) which they don't teach
```

### Skenario 4: Admin Restore (Tidak Ada Validasi) ✅

**Setup**:
- Admin (tidak perlu teaching assignment)
- Backup: Bank Soal apapun

**Langkah**:
1. Login sebagai Admin
2. Upload backup bank soal
3. Klik "Restore"

**Expected Result**:
```
✅ SUCCESS: Bank soal berhasil di-restore
```

## Error Messages

### Error 1: Guru Belum Memiliki Teaching Assignment
```
Anda belum memiliki tugas mengajar (plotting pembelajaran). 
Silakan hubungi admin untuk menambahkan tugas mengajar Anda terlebih dahulu 
sebelum melakukan restore bank soal.
```

**Kapan Muncul**: 
- Guru baru yang belum di-plotting
- Guru yang teaching assignment-nya dihapus semua

**Solusi**:
1. Admin masuk ke menu "Plotting Pembelajaran" atau "Teaching Assignments"
2. Tambahkan mata pelajaran dan kelas untuk guru tersebut
3. Guru coba restore lagi

### Error 2: Mata Pelajaran Tidak Sesuai (dengan Daftar Mapel)
```
Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. 
Bank soal ini untuk mata pelajaran: Bahasa Indonesia. 
Mata pelajaran yang Anda ampu: Matematika, Fisika.
```

**Kapan Muncul**: 
- Guru sudah punya teaching assignment
- Tapi bank soal bukan dari mata pelajaran yang ia ampu

**Solusi**:
1. Restore bank soal yang sesuai dengan mata pelajaran yang diampu
2. Atau minta admin untuk menambahkan mata pelajaran tersebut ke teaching assignment
3. Atau minta admin untuk restore bank soal tersebut

## Alur Validasi Lengkap

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
       │    │ Cek Teaching Assignment │
       │    └──────────┬──────────────┘
       │               │
       │       ┌───────┴───────┐
       │       │               │
       │       ▼               ▼
       │   [Ada]          [Kosong]
       │       │               │
       │       │               ▼
       │       │    ┌─────────────────┐
       │       │    │ ERROR: Belum    │
       │       │    │ ada tugas       │
       │       │    │ mengajar        │
       │       │    └─────────────────┘
       │       │
       │       ▼
       │    ┌─────────────────────────┐
       │    │ Ambil subject_id dari   │
       │    │ backup                  │
       │    └──────────┬──────────────┘
       │               │
       │               ▼
       │    ┌─────────────────────────┐
       │    │ Cek apakah subject_id   │
       │    │ ada di teaching         │
       │    │ assignments             │
       │    └──────────┬──────────────┘
       │               │
       │       ┌───────┴───────┐
       │       │               │
       │       ▼               ▼
       │   [Match]        [No Match]
       │       │               │
       │       │               ▼
       │       │    ┌─────────────────┐
       │       │    │ ERROR: Mapel    │
       │       │    │ tidak sesuai    │
       │       │    │ + daftar mapel  │
       │       │    └─────────────────┘
       │       │
       └───────┴───────┐
                       │
                       ▼
            ┌─────────────────────────┐
            │ Lanjutkan Restore       │
            └─────────────────────────┘
```

## Perbandingan Error Messages

### Sebelum Perbaikan:
```
(Tidak ada error message, hanya gagal diam-diam)
```

### Setelah Perbaikan:

**Kasus 1: Guru Baru**
```
❌ Anda belum memiliki tugas mengajar (plotting pembelajaran). 
Silakan hubungi admin untuk menambahkan tugas mengajar Anda terlebih dahulu 
sebelum melakukan restore bank soal.
```

**Kasus 2: Mapel Tidak Sesuai**
```
❌ Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. 
Bank soal ini untuk mata pelajaran: Bahasa Indonesia. 
Mata pelajaran yang Anda ampu: Matematika, Fisika.
```

## Checklist Testing

- [ ] Guru baru tanpa teaching assignment → Error dengan pesan jelas
- [ ] Guru dengan teaching assignment, restore mapel yang diampu → Berhasil
- [ ] Guru dengan teaching assignment, restore mapel yang tidak diampu → Error dengan daftar mapel
- [ ] Admin restore apapun → Berhasil tanpa validasi
- [ ] Log mencatat semua percobaan restore yang diblokir

## File yang Dimodifikasi

1. `app/Controllers/Admin/CbtBankSoal.php` - Method `restore()`
   - Tambah validasi untuk guru tanpa teaching assignment
   - Tambah daftar mata pelajaran yang diampu di error message
   - Tambah logging untuk tracking

## Manfaat

1. **Error Message yang Jelas**: Guru langsung tahu kenapa restore gagal
2. **Panduan Solusi**: Error message memberikan instruksi apa yang harus dilakukan
3. **Mencegah Kebingungan**: Guru tidak perlu bertanya-tanya kenapa restore gagal
4. **Tracking**: Admin bisa monitor guru mana yang belum di-plotting

---

**Status**: ✅ IMPLEMENTED
**Date**: April 7, 2026
**Impact**: Guru baru mendapat error message yang jelas saat mencoba restore tanpa teaching assignment
