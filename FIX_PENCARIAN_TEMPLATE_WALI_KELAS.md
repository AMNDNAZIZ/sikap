# ✅ FIX: Pencarian Template untuk Wali Kelas

## 🔍 Masalah yang Ditemukan

### Ketidaksinkronan di Pencarian Template:

**Masalah 1: Wali Kelas Melihat Dokumen Wali Kelas Lain**
- Saat klik "Cari Template", wali kelas bisa melihat dokumen wali kelas lain
- Seharusnya HANYA melihat template umum dari admin (`class_id = NULL`)

**Masalah 2: Template yang Sudah Digunakan Masih Muncul**
- Template yang sudah digunakan masih muncul di pencarian
- Seharusnya template yang sudah digunakan tidak muncul lagi

### Penyebab:

Di method `getAvailableTemplates()`, kondisi filter:
```php
->groupStart()
    ->where('kokurikuler_documents.class_id !=', $waliClass->id)
    ->orWhere('kokurikuler_documents.class_id IS NULL', null, false)
->groupEnd()
```

Ini akan menampilkan:
- ✅ Template admin (`class_id = NULL`) → **BENAR**
- ❌ Dokumen wali kelas lain (`class_id != wali_class_id`) → **SALAH!**

---

## ✅ Solusi yang Diterapkan

### Update Method `getAvailableTemplates()` di Controller

#### Sebelum:
```php
// Menampilkan:
// 1. Template admin (class_id = NULL)
// 2. Dokumen wali kelas lain (class_id != wali_class_id) ← SALAH!

$templates = $this->documentModel
    ->where('kokurikuler_documents.year_id', $activeYear['id'])
    ->where('kokurikuler_documents.status', 'completed')
    ->like('kokurikuler_documents.level_kelas', $waliClass->level)
    ->groupStart()
        ->where('kokurikuler_documents.class_id !=', $waliClass->id)
        ->orWhere('kokurikuler_documents.class_id IS NULL', null, false)
    ->groupEnd()
    ->findAll();
```

#### Sesudah:
```php
// Menampilkan:
// 1. HANYA template admin (class_id = NULL)
// 2. Level kelas sesuai
// 3. Tahun ajaran aktif
// 4. Belum digunakan oleh wali kelas ini

// Get dokumen yang sudah digunakan
$usedTemplateIds = $this->documentModel
    ->select('parent_id')
    ->where('used_by_teacher_id', $teacherId)
    ->where('year_id', $activeYear['id'])
    ->findAll();

$usedIds = array_filter(array_column($usedTemplateIds, 'parent_id'));

$builder = $this->documentModel
    ->where('kokurikuler_documents.year_id', $activeYear['id'])
    ->where('kokurikuler_documents.status', 'completed')
    ->where('kokurikuler_documents.class_id IS NULL', null, false) // HANYA template umum
    ->like('kokurikuler_documents.level_kelas', $waliClass->level);

// Exclude template yang sudah digunakan
if (!empty($usedIds)) {
    $builder->whereNotIn('kokurikuler_documents.id', $usedIds);
}

$templates = $builder->findAll();
```

---

## 🎯 Hasil Setelah Fix

### Logika yang Benar:

#### 1. **Halaman Index Wali Kelas** (`/admin/kokurikuler`)
Menampilkan:
- ✅ Dokumen yang dibuat sendiri
- ✅ Dokumen yang sudah digunakan dari template (`used_by_teacher_id`)
- ❌ TIDAK menampilkan template admin (harus cari dulu)
- ❌ TIDAK menampilkan dokumen wali kelas lain

#### 2. **Modal "Cari Template"** (klik tombol "Cari Template")
Menampilkan:
- ✅ Template admin (`class_id = NULL`)
- ✅ Status = completed
- ✅ Level kelas sesuai
- ✅ Tahun ajaran aktif
- ✅ Belum digunakan oleh wali kelas ini
- ❌ TIDAK menampilkan dokumen wali kelas lain
- ❌ TIDAK menampilkan template yang sudah digunakan

#### 3. **Setelah Klik "Gunakan"**
- ✅ Dokumen baru dibuat dengan `parent_id` = template ID
- ✅ Dokumen baru punya `class_id` = kelas wali kelas
- ✅ Dokumen baru punya `used_by_teacher_id` = teacher ID
- ✅ Dokumen baru muncul di halaman index wali kelas
- ✅ Template tidak muncul lagi di pencarian (sudah digunakan)

---

## 📊 Skenario Testing

### Skenario 1: Admin Membuat Template

**Setup:**
- Admin login
- Buat dokumen kokurikuler
- **PENTING**: Jangan pilih kelas spesifik (biarkan `class_id = NULL`)
- Level kelas: "4, 5, 6"
- Generate AI hingga status "Selesai"

**Expected:**
- Dokumen tersimpan dengan `class_id = NULL`
- Status = "completed"
- Muncul di halaman index admin

### Skenario 2: Wali Kelas Mencari Template

**Setup:**
- Wali Kelas 4A login (level = 4)
- Buka `/admin/kokurikuler`
- Klik tombol "Cari Template"

**Expected:**
- Modal terbuka
- Menampilkan template admin untuk level 4
- TIDAK menampilkan template untuk level 1, 2, 3
- TIDAK menampilkan dokumen wali kelas lain (misal Wali Kelas 4B)

### Skenario 3: Wali Kelas Gunakan Template

**Setup:**
- Dari modal "Cari Template"
- Klik "Gunakan" pada template admin

**Expected:**
- Redirect ke halaman view dokumen baru
- Dokumen baru punya:
  - `parent_id` = ID template admin
  - `class_id` = ID kelas 4A
  - `used_by_teacher_id` = ID teacher wali kelas 4A
  - `created_by` = ID user wali kelas
- Notifikasi sukses muncul

### Skenario 4: Template Tidak Muncul Lagi

**Setup:**
- Wali Kelas 4A sudah gunakan template X
- Klik "Cari Template" lagi

**Expected:**
- Template X TIDAK muncul lagi di list
- Template lain (yang belum digunakan) masih muncul

### Skenario 5: Dokumen Muncul di Index

**Setup:**
- Wali Kelas 4A sudah gunakan template
- Buka `/admin/kokurikuler`

**Expected:**
- Dokumen yang digunakan dari template muncul di list
- Ada label "(dari template)" di kolom "Dibuat Oleh"
- Tombol aksi: Lihat, PDF, Aktifkan, Hapus

### Skenario 6: Wali Kelas Lain Tidak Terpengaruh

**Setup:**
- Wali Kelas 4A sudah gunakan template X
- Wali Kelas 4B login
- Klik "Cari Template"

**Expected:**
- Template X masih muncul untuk Wali Kelas 4B
- Wali Kelas 4B bisa gunakan template X juga
- Dokumen Wali Kelas 4A TIDAK muncul di pencarian Wali Kelas 4B

---

## 🧪 Query SQL untuk Verifikasi

### 1. Cek Template Admin:
```sql
SELECT 
    id,
    tema,
    class_id,
    status,
    level_kelas,
    created_by
FROM kokurikuler_documents
WHERE class_id IS NULL 
  AND status = 'completed'
ORDER BY created_at DESC;
```

**Expected**: `class_id` harus NULL untuk template admin

### 2. Cek Dokumen yang Digunakan Wali Kelas:
```sql
SELECT 
    d.id,
    d.tema,
    d.class_id,
    d.parent_id,
    d.used_by_teacher_id,
    c.name as class_name,
    t.name as teacher_name,
    parent.tema as template_tema
FROM kokurikuler_documents d
LEFT JOIN classes c ON c.id = d.class_id
LEFT JOIN teachers t ON t.id = d.used_by_teacher_id
LEFT JOIN kokurikuler_documents parent ON parent.id = d.parent_id
WHERE d.parent_id IS NOT NULL
ORDER BY d.created_at DESC;
```

**Expected**: 
- `parent_id` ada nilai (ID template)
- `class_id` ada nilai (ID kelas wali kelas)
- `used_by_teacher_id` ada nilai (ID teacher)

### 3. Cek Template yang Sudah Digunakan:
```sql
SELECT 
    parent_id as template_id,
    COUNT(*) as jumlah_penggunaan,
    GROUP_CONCAT(DISTINCT c.name) as digunakan_oleh_kelas
FROM kokurikuler_documents d
LEFT JOIN classes c ON c.id = d.class_id
WHERE parent_id IS NOT NULL
GROUP BY parent_id;
```

**Expected**: Menampilkan berapa kali template digunakan dan oleh kelas mana

### 4. Cek Dokumen Wali Kelas Tertentu:
```sql
-- Ganti teacher_id = 1 dengan ID teacher yang ingin dicek
SELECT 
    d.id,
    d.tema,
    d.class_id,
    d.parent_id,
    d.created_by,
    c.name as class_name,
    CASE 
        WHEN d.parent_id IS NOT NULL THEN 'Dari Template'
        WHEN d.class_id IS NULL THEN 'Template Admin'
        ELSE 'Dokumen Sendiri'
    END as tipe_dokumen
FROM kokurikuler_documents d
LEFT JOIN classes c ON c.id = d.class_id
WHERE d.created_by = (SELECT id FROM users WHERE related_id = 1 AND role_id = 3)
   OR d.used_by_teacher_id = 1
ORDER BY d.created_at DESC;
```

---

## 🔍 Debugging

### Jika Template Tidak Muncul di Pencarian:

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
-- Template
SELECT id, tema, level_kelas FROM kokurikuler_documents WHERE id = 123;

-- Kelas wali kelas
SELECT id, name, level FROM classes WHERE teacher_id = 1;
```
Level harus match (misal template "4,5,6" dan wali kelas level "4")

**Cek 4: Apakah sudah digunakan?**
```sql
SELECT * FROM kokurikuler_documents 
WHERE parent_id = 123 
  AND used_by_teacher_id = 1;
```
Jika ada hasil → Template sudah digunakan, tidak muncul lagi

**Cek 5: Apakah tahun ajaran sesuai?**
```sql
-- Template
SELECT id, tema, year_id FROM kokurikuler_documents WHERE id = 123;

-- Tahun ajaran aktif
SELECT id, year, is_active FROM academic_years WHERE is_active = 1;
```
`year_id` template harus sama dengan tahun ajaran aktif

### Jika Dokumen Wali Kelas Lain Muncul di Pencarian:

**Cek Log Server:**
```bash
tail -f writable/logs/log-*.php | grep "GetAvailableTemplates"
```

Look for:
```
INFO - GetAvailableTemplates - Found X templates
INFO - GetAvailableTemplates - Templates: [1,2,3]
```

**Cek Database:**
```sql
-- Cek apakah dokumen wali kelas lain punya class_id NULL
SELECT id, tema, class_id, created_by 
FROM kokurikuler_documents 
WHERE id IN (1,2,3);
```

Jika ada `class_id` yang tidak NULL → Bug! Seharusnya tidak muncul.

---

## 📝 Checklist Implementasi

- [x] Update method `getAvailableTemplates()` di controller
- [x] Filter HANYA template admin (`class_id = NULL`)
- [x] Exclude template yang sudah digunakan
- [x] Enhanced logging untuk debugging
- [x] Dokumentasi lengkap

---

## ✅ Status

**Fixed**: ✅  
**Tested**: Perlu testing manual  
**Ready for Production**: Setelah testing berhasil

---

## 🚀 Manfaat Setelah Fix

1. **Privasi**: Wali kelas tidak bisa melihat dokumen wali kelas lain
2. **Kejelasan**: Hanya template admin yang muncul di pencarian
3. **Efisiensi**: Template yang sudah digunakan tidak muncul lagi
4. **Konsistensi**: Logika sesuai dengan requirement awal

---

## 📞 Testing Instructions

1. **Login sebagai Admin**
   - Buat dokumen tanpa pilih kelas spesifik
   - Generate AI hingga selesai
   - Cek database: `class_id` harus NULL

2. **Login sebagai Wali Kelas 4A**
   - Klik "Cari Template"
   - Verifikasi: Hanya template admin yang muncul
   - Verifikasi: Tidak ada dokumen wali kelas lain
   - Klik "Gunakan" pada template
   - Verifikasi: Dokumen baru muncul di index

3. **Klik "Cari Template" Lagi**
   - Verifikasi: Template yang sudah digunakan tidak muncul

4. **Login sebagai Wali Kelas 4B**
   - Klik "Cari Template"
   - Verifikasi: Template yang sama masih muncul
   - Verifikasi: Dokumen Wali Kelas 4A tidak muncul

Jika semua verifikasi berhasil → Fix sukses! ✅
