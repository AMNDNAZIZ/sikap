# 🔍 DEBUG: Dokumen yang Muncul Berbeda dengan yang Dibuat Admin

## Masalah
Admin membuat dokumen A, B, C → Wali kelas cari template, yang muncul: D, E, F (berbeda!)

---

## 🧪 Langkah Debugging

### STEP 1: Jalankan Query SQL untuk Cek Data

Buka file `CEK_SINKRONISASI_TEMPLATE.sql` dan jalankan query-query di dalamnya.

**Yang perlu dicatat:**
1. ID dokumen yang dibuat admin
2. ID dokumen yang muncul di pencarian wali kelas
3. Level kelas wali kelas
4. Tahun ajaran aktif

### STEP 2: Cek Server Log

1. Login sebagai **Wali Kelas**
2. Buka halaman `/admin/kokurikuler`
3. Klik tombol **"Cari Template"**
4. Buka file log server: `writable/logs/log-YYYY-MM-DD.php`

**Cari section ini:**
```
=== GET AVAILABLE TEMPLATES START ===
Teacher ID: X
Wali Class ID: Y, Name: Kelas 4A, Level: 4
Active Year ID: Z, Year: 2024/2025
Total documents in active year: 10
Doc ID 1: tema='...', class_id=NULL, status=completed, level_kelas=4,5,6, isTemplate=YES, isCompleted=YES, levelMatch=YES
Doc ID 2: tema='...', class_id=5, status=completed, level_kelas=4, isTemplate=NO, isCompleted=YES, levelMatch=YES
...
Templates found after filter: 3
Template ID 1: tema='...', creator=Admin, level=4,5,6
Template ID 2: tema='...', creator=Admin, level=4
=== GET AVAILABLE TEMPLATES END ===
```

### STEP 3: Analisis Log

Dari log di atas, cek setiap dokumen:

#### Dokumen yang SEHARUSNYA muncul:
- ✅ `isTemplate=YES` (class_id = NULL)
- ✅ `isCompleted=YES` (status = completed)
- ✅ `levelMatch=YES` (level_kelas mengandung level wali kelas)

#### Dokumen yang TIDAK SEHARUSNYA muncul:
- ❌ `isTemplate=NO` (class_id ada nilai) → Dokumen kelas spesifik
- ❌ `isCompleted=NO` (status = draft) → Belum selesai
- ❌ `levelMatch=NO` (level_kelas tidak sesuai) → Untuk level lain

---

## 🔍 Kemungkinan Penyebab

### Penyebab 1: Admin Tidak Membuat Template dengan Benar

**Cek:**
```sql
SELECT id, tema, class_id, status, level_kelas
FROM kokurikuler_documents
WHERE created_by IN (SELECT id FROM users WHERE role_id = 1)
ORDER BY created_at DESC;
```

**Masalah yang mungkin:**
- `class_id` ada nilai (bukan NULL) → Admin memilih kelas spesifik saat membuat
- `status` = 'draft' → Dokumen belum di-generate AI atau belum selesai
- `level_kelas` tidak sesuai → Admin membuat untuk level lain

**Solusi:**
Admin harus membuat dokumen dengan:
- **Jangan pilih kelas spesifik** (biarkan kosong atau pilih "Semua Kelas")
- **Generate AI hingga selesai** (status = completed)
- **Pilih level kelas yang sesuai** (misal: 4, 5, 6)

### Penyebab 2: Tahun Ajaran Tidak Sesuai

**Cek:**
```sql
-- Dokumen admin
SELECT id, tema, year_id FROM kokurikuler_documents WHERE id = 123;

-- Tahun ajaran aktif
SELECT id, year, is_active FROM academic_years WHERE is_active = 1;
```

**Masalah:**
Dokumen admin dibuat untuk tahun ajaran lama, tapi sekarang tahun ajaran sudah berganti.

**Solusi:**
Update `year_id` dokumen admin ke tahun ajaran aktif:
```sql
UPDATE kokurikuler_documents 
SET year_id = (SELECT id FROM academic_years WHERE is_active = 1)
WHERE id = 123;
```

### Penyebab 3: Level Kelas Tidak Match

**Cek:**
```sql
-- Dokumen admin
SELECT id, tema, level_kelas FROM kokurikuler_documents WHERE id = 123;

-- Kelas wali kelas
SELECT id, name, level FROM classes WHERE teacher_id = 1;
```

**Masalah:**
- Dokumen admin: `level_kelas = "1,2,3"`
- Wali kelas: `level = 4`
- Tidak match!

**Solusi:**
Update `level_kelas` dokumen admin:
```sql
UPDATE kokurikuler_documents 
SET level_kelas = '4,5,6'
WHERE id = 123;
```

### Penyebab 4: Dokumen yang Muncul Bukan dari Admin

**Cek:**
```sql
-- Dokumen yang muncul di pencarian (dari log)
SELECT id, tema, created_by, class_id, status
FROM kokurikuler_documents
WHERE id IN (10, 11, 12); -- ID dari log

-- Cek siapa pembuatnya
SELECT 
    d.id,
    d.tema,
    u.fullname,
    u.role_id,
    CASE 
        WHEN u.role_id = 1 THEN 'Admin'
        WHEN u.role_id = 3 THEN 'Wali Kelas'
        ELSE 'Lainnya'
    END as role_name
FROM kokurikuler_documents d
LEFT JOIN users u ON u.id = d.created_by
WHERE d.id IN (10, 11, 12);
```

**Masalah:**
Dokumen yang muncul ternyata dibuat oleh wali kelas lain, bukan admin!

**Penyebab:**
Wali kelas lain membuat dokumen tanpa pilih kelas spesifik (`class_id = NULL`), sehingga jadi "template" juga.

**Solusi:**
Pastikan hanya admin yang membuat template umum. Atau update `class_id` dokumen wali kelas:
```sql
-- Cari dokumen wali kelas yang class_id NULL
SELECT id, tema, created_by, class_id
FROM kokurikuler_documents
WHERE created_by IN (SELECT id FROM users WHERE role_id = 3)
  AND class_id IS NULL;

-- Update class_id ke kelas yang sesuai
UPDATE kokurikuler_documents 
SET class_id = (SELECT class_id FROM ... WHERE ...)
WHERE id = 123;
```

---

## 📋 Checklist Verifikasi

Untuk setiap dokumen yang dibuat admin, pastikan:

- [ ] `class_id` = NULL (template umum)
- [ ] `status` = 'completed' (sudah selesai)
- [ ] `level_kelas` sesuai dengan target (misal: "4,5,6")
- [ ] `year_id` = tahun ajaran aktif
- [ ] `created_by` = user ID admin (role_id = 1)

---

## 🛠️ Cara Admin Membuat Template yang Benar

### Langkah-langkah:

1. **Login sebagai Admin**
2. **Buka** `/admin/kokurikuler`
3. **Klik** "Buat Dokumen Baru"
4. **Isi form:**
   - Tahun Ajaran: Pilih tahun ajaran aktif
   - Semester: 1 atau 2
   - Fase: Sesuai jenjang
   - **Level Kelas: Pilih level yang sesuai** (misal: 4, 5, 6)
   - **PENTING: Jangan pilih kelas spesifik!** (biarkan kosong)
   - Tema: Isi tema kegiatan
   - Jenis Kokurikuler: Pilih jenis
   - Bentuk Kegiatan Konkret: Isi deskripsi
   - Dimensi Profil: Pilih dimensi
   - Sub Dimensi: Pilih sub dimensi
5. **Klik** "Simpan dan Generate AI"
6. **Tunggu** hingga status menjadi "Selesai"
7. **Verifikasi** di database:
   ```sql
   SELECT id, tema, class_id, status, level_kelas
   FROM kokurikuler_documents
   WHERE id = [ID_DOKUMEN_BARU];
   ```
   - `class_id` harus NULL
   - `status` harus 'completed'

---

## 🧪 Test Case

### Test 1: Admin Buat Template untuk Level 4

**Setup:**
- Admin login
- Buat dokumen baru
- Level Kelas: 4, 5, 6
- Jangan pilih kelas spesifik
- Generate AI hingga selesai

**Expected:**
```sql
SELECT id, tema, class_id, status, level_kelas
FROM kokurikuler_documents
WHERE id = [ID_BARU];

-- Result:
-- id: 100
-- tema: "Tema Test"
-- class_id: NULL
-- status: completed
-- level_kelas: 4,5,6
```

### Test 2: Wali Kelas 4A Cari Template

**Setup:**
- Wali Kelas 4A login (level = 4)
- Klik "Cari Template"

**Expected:**
- Template ID 100 muncul di list
- Tema: "Tema Test"
- Creator: Admin

**Verifikasi Log:**
```
=== GET AVAILABLE TEMPLATES START ===
Wali Class ID: X, Level: 4
Total documents in active year: 5
Doc ID 100: tema='Tema Test', class_id=NULL, status=completed, level_kelas=4,5,6, isTemplate=YES, isCompleted=YES, levelMatch=YES
Templates found after filter: 1
Template ID 100: tema='Tema Test', creator=Admin, level=4,5,6
=== GET AVAILABLE TEMPLATES END ===
```

### Test 3: Wali Kelas 1A Cari Template

**Setup:**
- Wali Kelas 1A login (level = 1)
- Klik "Cari Template"

**Expected:**
- Template ID 100 TIDAK muncul (karena level tidak sesuai)

**Verifikasi Log:**
```
Doc ID 100: tema='Tema Test', class_id=NULL, status=completed, level_kelas=4,5,6, isTemplate=YES, isCompleted=YES, levelMatch=NO
Templates found after filter: 0
```

---

## 📞 Instruksi untuk User

**Tolong lakukan ini:**

1. **Login sebagai Wali Kelas**
2. **Klik "Cari Template"**
3. **Screenshot** dokumen yang muncul (catat ID-nya)
4. **Buka phpMyAdmin** atau MySQL client
5. **Jalankan query:**
   ```sql
   -- Ganti ID sesuai yang muncul di pencarian
   SELECT 
       d.id,
       d.tema,
       d.class_id,
       d.status,
       d.level_kelas,
       d.year_id,
       u.fullname as creator,
       u.role_id
   FROM kokurikuler_documents d
   LEFT JOIN users u ON u.id = d.created_by
   WHERE d.id IN (10, 11, 12); -- Ganti dengan ID yang muncul
   ```
6. **Screenshot hasil query**
7. **Kirimkan kedua screenshot**

Dengan informasi ini, saya bisa identifikasi masalah yang sebenarnya.

---

## 🔧 Quick Fix

Jika sudah tahu ID dokumen admin yang seharusnya muncul tapi tidak muncul:

```sql
-- Cek dulu
SELECT id, tema, class_id, status, level_kelas, year_id
FROM kokurikuler_documents
WHERE id = 123; -- ID dokumen admin

-- Fix class_id
UPDATE kokurikuler_documents SET class_id = NULL WHERE id = 123;

-- Fix status
UPDATE kokurikuler_documents SET status = 'completed' WHERE id = 123;

-- Fix level_kelas (sesuaikan)
UPDATE kokurikuler_documents SET level_kelas = '4,5,6' WHERE id = 123;

-- Fix year_id ke tahun ajaran aktif
UPDATE kokurikuler_documents 
SET year_id = (SELECT id FROM academic_years WHERE is_active = 1)
WHERE id = 123;
```

Setelah fix, refresh halaman dan coba "Cari Template" lagi.
