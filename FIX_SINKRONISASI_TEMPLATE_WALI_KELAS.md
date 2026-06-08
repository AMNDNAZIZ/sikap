# ✅ FIX: Sinkronisasi Template Admin dengan Wali Kelas

## 🔍 Masalah yang Ditemukan

### Ketidaksinkronan:
1. **Admin membuat dokumen template** (dengan `class_id = NULL`)
2. **Wali kelas TIDAK melihat template** di halaman index `/admin/kokurikuler`
3. **Wali kelas BISA melihat template** di modal "Cari Template"
4. **Hasil**: Wali kelas harus klik "Cari Template" untuk melihat template yang seharusnya sudah muncul di list

### Penyebab:
Method `getDocumentsWithCreator()` di model hanya menampilkan:
- Dokumen yang dibuat sendiri
- Dokumen yang sudah digunakan (`used_by_teacher_id`)
- **TIDAK menampilkan template dari admin**

---

## ✅ Solusi yang Diterapkan

### 1. **Update Model** (`app/Models/KokurikulerDocumentModel.php`)

#### Sebelum:
```php
// Guru kelas HANYA melihat:
// 1. Dokumen yang ia buat sendiri
// 2. Dokumen yang ia gunakan dari template
$builder->groupStart()
    ->where('kokurikuler_documents.created_by', $userId)
    ->orWhere('kokurikuler_documents.used_by_teacher_id', $teacherId)
->groupEnd();
```

#### Sesudah:
```php
// Guru kelas melihat:
// 1. Dokumen yang ia buat sendiri
// 2. Dokumen yang ia gunakan dari template
// 3. Template dari admin (class_id = NULL, status = completed) yang sesuai level kelasnya
$builder->groupStart()
    // Dokumen yang dibuat sendiri
    ->where('kokurikuler_documents.created_by', $userId)
    // Atau dokumen yang sudah digunakan
    ->orWhere('kokurikuler_documents.used_by_teacher_id', $teacherId)
    // Atau template dari admin yang sesuai level kelas
    ->orGroupStart()
        ->where('kokurikuler_documents.class_id IS NULL', null, false)
        ->where('kokurikuler_documents.status', 'completed')
        ->like('kokurikuler_documents.level_kelas', $classLevel)
    ->groupEnd()
->groupEnd();
```

### 2. **Update View** (`app/Views/admin/kokurikuler/index.php`)

#### Perubahan:

**A. Badge "Template" untuk Template Admin:**
```php
<?php if (empty($doc['class_id']) && $doc['status'] === 'completed'): ?>
    <br><span class="badge bg-primary badge-sm">📋 Template</span>
<?php endif; ?>
```

**B. Tombol Aksi yang Berbeda:**
- **Untuk Template Admin** (dilihat oleh wali kelas):
  - ✅ Tombol "Lihat Detail"
  - ✅ Tombol "Export PDF"
  - ✅ Tombol "Gunakan" (biru) - langsung gunakan template
  - ❌ TIDAK ada tombol "Hapus" (bukan milik wali kelas)

- **Untuk Dokumen Sendiri**:
  - ✅ Tombol "Lihat Detail"
  - ✅ Tombol "Export PDF"
  - ✅ Tombol "Aktifkan ke Tahun Baru"
  - ✅ Tombol "Hapus"

---

## 🎯 Hasil Setelah Fix

### Untuk Admin (Role ID = 1):
- ✅ Melihat semua dokumen (milik sendiri dan semua template)
- ✅ Bisa edit/hapus semua dokumen
- ✅ Bisa membuat template umum (`class_id = NULL`)

### Untuk Wali Kelas (Role ID = 3):
- ✅ Melihat dokumen yang dibuat sendiri
- ✅ Melihat dokumen yang sudah digunakan dari template
- ✅ **MELIHAT template dari admin** yang sesuai level kelasnya
- ✅ Badge "📋 Template" untuk membedakan template admin
- ✅ Tombol "Gunakan" langsung di list (tidak perlu klik "Cari Template")
- ✅ Tidak bisa hapus template admin (hanya bisa gunakan)

---

## 📊 Perbandingan

### Sebelum Fix:
```
Halaman Index Wali Kelas:
┌─────────────────────────────────────┐
│ [Buat Dokumen Baru] [Cari Template] │
├─────────────────────────────────────┤
│ 1. Dokumen Saya A                   │
│ 2. Dokumen Saya B                   │
│                                     │
│ (Template admin TIDAK muncul)       │
└─────────────────────────────────────┘

Modal "Cari Template":
┌─────────────────────────────────────┐
│ Template yang Tersedia:             │
├─────────────────────────────────────┤
│ 1. Template Admin X [Gunakan]       │
│ 2. Template Admin Y [Gunakan]       │
└─────────────────────────────────────┘
```

### Setelah Fix:
```
Halaman Index Wali Kelas:
┌─────────────────────────────────────┐
│ [Buat Dokumen Baru] [Cari Template] │
├─────────────────────────────────────┤
│ 1. Dokumen Saya A                   │
│    [Lihat] [PDF] [Aktifkan] [Hapus] │
│                                     │
│ 2. Dokumen Saya B                   │
│    [Lihat] [PDF] [Aktifkan] [Hapus] │
│                                     │
│ 3. Template Admin X 📋 Template     │
│    [Lihat] [PDF] [Gunakan]          │
│                                     │
│ 4. Template Admin Y 📋 Template     │
│    [Lihat] [PDF] [Gunakan]          │
└─────────────────────────────────────┘
```

---

## 🔄 Alur Penggunaan Template

### Cara Admin Membuat Template:

1. Login sebagai Admin
2. Buka `/admin/kokurikuler`
3. Klik "Buat Dokumen Baru"
4. **PENTING**: Jangan pilih kelas spesifik (biarkan kosong atau pilih "Semua Kelas")
5. Isi data lengkap
6. Generate AI
7. Status akan menjadi "Selesai" → Otomatis jadi template

### Cara Wali Kelas Menggunakan Template:

#### Opsi 1: Dari Halaman Index (BARU!)
1. Login sebagai Wali Kelas
2. Buka `/admin/kokurikuler`
3. Lihat list dokumen
4. Template admin akan muncul dengan badge "📋 Template"
5. Klik tombol "Gunakan" (biru)
6. Dokumen akan di-copy ke kelas wali kelas

#### Opsi 2: Dari Modal "Cari Template" (Lama)
1. Login sebagai Wali Kelas
2. Buka `/admin/kokurikuler`
3. Klik "Cari Template"
4. Pilih template yang sesuai
5. Klik "Gunakan"

---

## 🧪 Testing Checklist

### Test Case 1: Admin Membuat Template
- [ ] Login sebagai Admin
- [ ] Buat dokumen baru tanpa pilih kelas spesifik
- [ ] Generate AI hingga status "Selesai"
- [ ] Cek di database: `class_id` harus NULL
- [ ] Cek di halaman index: dokumen muncul

### Test Case 2: Wali Kelas Melihat Template
- [ ] Login sebagai Wali Kelas (misal kelas 4A)
- [ ] Buka `/admin/kokurikuler`
- [ ] Template admin untuk level 4 harus muncul
- [ ] Template admin untuk level lain (misal level 1) TIDAK muncul
- [ ] Badge "📋 Template" muncul di template admin
- [ ] Tombol "Gunakan" muncul (bukan "Hapus")

### Test Case 3: Wali Kelas Gunakan Template
- [ ] Klik tombol "Gunakan" di template admin
- [ ] Dokumen baru dibuat dengan `parent_id` = template ID
- [ ] Dokumen baru punya `class_id` = kelas wali kelas
- [ ] Dokumen baru punya `used_by_teacher_id` = teacher ID
- [ ] Dokumen baru muncul di list dengan label "(dari template)"

### Test Case 4: Filter Level Kelas
- [ ] Admin buat template untuk "Kelas 1, 2, 3"
- [ ] Wali kelas 1A login → template muncul ✅
- [ ] Wali kelas 4A login → template TIDAK muncul ✅
- [ ] Admin buat template untuk "Kelas 4, 5, 6"
- [ ] Wali kelas 4A login → template muncul ✅

### Test Case 5: Permission
- [ ] Wali kelas TIDAK bisa hapus template admin
- [ ] Wali kelas BISA hapus dokumen sendiri
- [ ] Admin BISA hapus semua dokumen

---

## 📝 Query SQL untuk Verifikasi

### Cek Template Admin:
```sql
SELECT id, tema, class_id, status, level_kelas, created_by
FROM kokurikuler_documents
WHERE class_id IS NULL 
  AND status = 'completed'
ORDER BY created_at DESC;
```

### Cek Dokumen yang Digunakan Wali Kelas:
```sql
SELECT 
    d.id,
    d.tema,
    d.class_id,
    d.parent_id,
    d.used_by_teacher_id,
    c.name as class_name,
    t.name as teacher_name
FROM kokurikuler_documents d
LEFT JOIN classes c ON c.id = d.class_id
LEFT JOIN teachers t ON t.id = d.used_by_teacher_id
WHERE d.parent_id IS NOT NULL
ORDER BY d.created_at DESC;
```

### Cek Level Kelas Wali Kelas:
```sql
SELECT 
    t.id as teacher_id,
    t.name as teacher_name,
    c.id as class_id,
    c.name as class_name,
    c.level
FROM teachers t
JOIN classes c ON c.teacher_id = t.id
WHERE t.id = 1; -- Ganti dengan teacher_id
```

---

## 🚀 Manfaat Setelah Fix

1. **Efisiensi**: Wali kelas tidak perlu klik "Cari Template" lagi
2. **Visibilitas**: Template admin langsung terlihat di halaman utama
3. **User Experience**: Badge "📋 Template" membedakan template vs dokumen sendiri
4. **Keamanan**: Wali kelas tidak bisa hapus template admin
5. **Fleksibilitas**: Admin bisa membuat template untuk level kelas tertentu

---

## 📞 Troubleshooting

### Template Admin Tidak Muncul di Wali Kelas?

**Cek 1: Apakah `class_id` NULL?**
```sql
SELECT id, tema, class_id FROM kokurikuler_documents WHERE id = 123;
```
Jika `class_id` ada nilai → Bukan template, ubah jadi NULL

**Cek 2: Apakah status "completed"?**
```sql
SELECT id, tema, status FROM kokurikuler_documents WHERE id = 123;
```
Jika status "draft" → Template tidak akan muncul

**Cek 3: Apakah level kelas sesuai?**
```sql
SELECT level_kelas FROM kokurikuler_documents WHERE id = 123;
-- Harus match dengan level kelas wali kelas (misal "4" untuk kelas 4A)
```

**Cek 4: Apakah wali kelas punya kelas?**
```sql
SELECT * FROM classes WHERE teacher_id = 1; -- Ganti dengan teacher_id
```
Jika tidak ada → Wali kelas belum ditugaskan

---

## ✅ Status

- **Model Updated**: ✅ `app/Models/KokurikulerDocumentModel.php`
- **View Updated**: ✅ `app/Views/admin/kokurikuler/index.php`
- **Logic Fixed**: ✅ Template admin sekarang muncul di halaman index wali kelas
- **UI Enhanced**: ✅ Badge dan tombol yang berbeda untuk template vs dokumen sendiri

**Ready for Testing!** 🚀
