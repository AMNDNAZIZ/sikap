# Analisis Folder Uploads - SIAKAD

## 📋 Pertanyaan

**"Di dalam root ada folder uploads, demikian juga di folder public, mana yang dipakai ataukah dua-duanya?"**

---

## 🔍 Hasil Analisis

### Struktur Folder Uploads

#### 1. Root `/uploads/` (9 subfolder)
```
uploads/
├── activities/
├── articles/
├── chat/
├── facilities/
├── logo/
├── sliders/
├── soal_images/
├── teachers/
└── vision/
```

#### 2. Public `/public/uploads/` (6 subfolder)
```
public/uploads/
├── activities/
├── articles/
├── chat/
├── facilities/
├── sliders/
└── vision/
```

---

## ✅ Jawaban: YANG DIPAKAI ADALAH ROOT `/uploads/`

### Bukti dari Kode:

#### Upload Files (Controllers):
```php
// app/Controllers/Admin/Cms/Sliders.php
$uploadPath = FCPATH . 'uploads/sliders';  // ✅ ROOT uploads
$file->move($uploadPath, $newName);

// app/Controllers/Admin/Teachers.php
$photoFile->move(FCPATH . 'uploads/teachers', $photoName);  // ✅ ROOT uploads

// app/Controllers/Siswa/Chat.php
$file->move(FCPATH . 'uploads/chat', $newName);  // ✅ ROOT uploads

// app/Controllers/Admin/School.php
$logo->move('uploads/logo', $logoName);  // ✅ ROOT uploads (relative path)
```

#### Display Files (Views):
```php
// app/Views/index.php
<img src="<?= base_url('uploads/logo/' . $school['logo']) ?>">  // ✅ ROOT uploads

// app/Views/siswa/profile.php
<img src="<?= base_url('uploads/students/' . $student['photo']) ?>">  // ✅ ROOT uploads

// app/Views/news/view.php
<img src="<?= base_url('uploads/articles/' . $article['image']) ?>">  // ✅ ROOT uploads
```

**Kesimpulan:** Semua kode menggunakan **ROOT `/uploads/`**, bukan `/public/uploads/`

---

## ⚠️ Masalah: Folder `/public/uploads/` Tidak Terpakai

### Analisis:

1. **Folder `/public/uploads/` adalah DUPLIKAT yang tidak terpakai**
2. **Hanya 6 dari 9 subfolder yang ada** (missing: logo, soal_images, teachers)
3. **Kemungkinan penyebab:**
   - Copy-paste manual yang tidak lengkap
   - Sisa dari struktur lama
   - Backup yang tidak dihapus

### Dampak:

#### ❌ Masalah Keamanan:
- File di `/uploads/` (root) **TIDAK AMAN** karena di luar `public/`
- File bisa diakses langsung tanpa melalui CodeIgniter
- Tidak ada proteksi dari `.htaccess` atau routing

#### ❌ Masalah Struktur:
- Tidak sesuai best practice CodeIgniter 4
- File upload seharusnya di dalam `public/` atau `writable/`

#### ❌ Masalah Deployment:
- Saat deploy, folder `/uploads/` di root bisa terhapus
- Backup/restore lebih ribet (2 lokasi)

---

## 🔧 Solusi: Pindahkan ke `/public/uploads/`

### Mengapa Harus Pindah?

#### ✅ Keamanan:
- File di `public/` aman karena melalui web server
- Bisa dikontrol dengan `.htaccess`
- Tidak bisa diakses langsung ke file PHP

#### ✅ Best Practice:
- Sesuai struktur CodeIgniter 4
- Semua asset publik di `public/`
- Mudah di-manage

#### ✅ Deployment:
- Lebih mudah backup (1 lokasi)
- Tidak ada konflik saat update
- Struktur lebih jelas

---

## 📝 Langkah Migrasi

### STEP 1: Backup Data

```bash
# Backup folder uploads saat ini
cp -r uploads uploads_backup_$(date +%Y%m%d)
cp -r public/uploads public/uploads_backup_$(date +%Y%m%d)
```

### STEP 2: Pindahkan File

```bash
# Pindahkan semua file dari root/uploads ke public/uploads
# Pastikan tidak overwrite file yang sudah ada

# Logo
cp -r uploads/logo/* public/uploads/logo/ 2>/dev/null || mkdir -p public/uploads/logo && cp -r uploads/logo/* public/uploads/logo/

# Teachers
mkdir -p public/uploads/teachers
cp -r uploads/teachers/* public/uploads/teachers/

# Soal Images
mkdir -p public/uploads/soal_images
cp -r uploads/soal_images/* public/uploads/soal_images/

# Students (jika ada)
mkdir -p public/uploads/students
cp -r uploads/students/* public/uploads/students/ 2>/dev/null || true

# Audio (jika ada)
mkdir -p public/uploads/audio
cp -r uploads/audio/* public/uploads/audio/ 2>/dev/null || true

# Sync subfolder yang sudah ada
rsync -av uploads/activities/ public/uploads/activities/
rsync -av uploads/articles/ public/uploads/articles/
rsync -av uploads/chat/ public/uploads/chat/
rsync -av uploads/facilities/ public/uploads/facilities/
rsync -av uploads/sliders/ public/uploads/sliders/
rsync -av uploads/vision/ public/uploads/vision/
```

### STEP 3: Update Kode

#### A. Update Controllers (Find & Replace)

**Find:**
```php
FCPATH . 'uploads/
```

**Replace with:**
```php
FCPATH . 'public/uploads/
```

**Atau lebih baik, buat konstanta:**

```php
// app/Config/Constants.php
defined('UPLOAD_PATH') || define('UPLOAD_PATH', FCPATH . 'public/uploads/');
```

**Lalu gunakan:**
```php
// Sebelum:
$file->move(FCPATH . 'uploads/teachers', $photoName);

// Sesudah:
$file->move(UPLOAD_PATH . 'teachers', $photoName);
```

#### B. Update Views (Tidak Perlu Diubah!)

Views sudah benar karena menggunakan `base_url('uploads/...')` yang akan otomatis resolve ke `public/uploads/` jika file ada di sana.

```php
// Ini sudah benar, tidak perlu diubah
<img src="<?= base_url('uploads/logo/' . $school['logo']) ?>">
```

### STEP 4: Update .htaccess (Proteksi)

Tambahkan proteksi untuk file upload:

```apache
# public/uploads/.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Deny access to PHP files
    <FilesMatch "\.php$">
        Order Deny,Allow
        Deny from all
    </FilesMatch>
    
    # Allow only image, video, audio, pdf
    <FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|mp3|pdf|doc|docx|xls|xlsx)$">
        Order Allow,Deny
        Allow from all
    </FilesMatch>
</IfModule>
```

### STEP 5: Test

```bash
# Test upload file baru
# - Upload foto guru
# - Upload slider
# - Upload artikel
# - Upload chat attachment

# Test display file
# - Lihat dashboard (logo sekolah)
# - Lihat profil guru (foto)
# - Lihat landing page (slider)
# - Lihat artikel (gambar)

# Test CBT
# - Lihat soal dengan gambar
# - Upload audio soal
```

### STEP 6: Cleanup (Setelah Yakin Semua OK)

```bash
# Hapus folder uploads di root (HATI-HATI!)
# Pastikan sudah backup dan test dulu!

# Rename dulu untuk safety
mv uploads uploads_old

# Test aplikasi selama 1-2 hari

# Jika tidak ada masalah, baru hapus
rm -rf uploads_old
```

---

## 🚀 Alternatif: Symbolic Link (Quick Fix)

Jika tidak mau ubah kode, bisa pakai symbolic link:

### Linux/Mac:
```bash
# Hapus folder uploads di root
rm -rf uploads

# Buat symbolic link ke public/uploads
ln -s public/uploads uploads
```

### Windows:
```cmd
# Hapus folder uploads di root
rmdir /s uploads

# Buat junction (symbolic link Windows)
mklink /J uploads public\uploads
```

**Keuntungan:**
- ✅ Tidak perlu ubah kode
- ✅ File tetap di `public/uploads/` (aman)
- ✅ Akses via `uploads/` tetap jalan

**Kekurangan:**
- ⚠️ Tidak semua hosting support symbolic link
- ⚠️ Bisa membingungkan developer lain

---

## 📊 Rekomendasi

### Untuk Development (Localhost):

**Opsi 1: Symbolic Link (Cepat)**
```bash
# Pindahkan semua file ke public/uploads
rsync -av uploads/ public/uploads/

# Hapus uploads di root
rm -rf uploads

# Buat symbolic link
ln -s public/uploads uploads
```

**Keuntungan:** Tidak perlu ubah kode, cepat

### Untuk Production (Server):

**Opsi 2: Update Kode (Recommended)**
1. Pindahkan file ke `public/uploads/`
2. Update semua controller (find & replace)
3. Tambah konstanta `UPLOAD_PATH`
4. Test semua fitur upload
5. Hapus folder `uploads/` di root

**Keuntungan:** Lebih aman, best practice, tidak ada dependency

---

## ⚠️ Catatan Penting

### Jangan Lupa:

1. **Backup dulu sebelum pindah!**
   ```bash
   tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
   ```

2. **Test semua fitur upload setelah migrasi:**
   - Upload foto guru
   - Upload slider
   - Upload artikel
   - Upload soal CBT
   - Upload chat attachment

3. **Update file permissions:**
   ```bash
   chmod -R 775 public/uploads
   chown -R www-data:www-data public/uploads
   ```

4. **Update .gitignore:**
   ```gitignore
   # Ignore uploads (kecuali .gitkeep)
   public/uploads/*
   !public/uploads/.gitkeep
   !public/uploads/*/.gitkeep
   ```

5. **Buat .gitkeep di setiap subfolder:**
   ```bash
   find public/uploads -type d -exec touch {}/.gitkeep \;
   ```

---

## 🎯 Kesimpulan

### Status Saat Ini:
- ❌ Folder `/uploads/` di root (DIPAKAI, tapi TIDAK AMAN)
- ❌ Folder `/public/uploads/` (ADA tapi TIDAK LENGKAP)
- ❌ Struktur tidak sesuai best practice

### Yang Harus Dilakukan:
1. **Pindahkan semua file ke `/public/uploads/`**
2. **Update kode controller** (find & replace)
3. **Hapus folder `/uploads/` di root**
4. **Test semua fitur upload**

### Prioritas:
- 🔴 **HIGH:** Untuk production (keamanan)
- 🟡 **MEDIUM:** Untuk development (best practice)

### Estimasi Waktu:
- Symbolic link: 5 menit
- Update kode: 30-60 menit
- Testing: 30 menit
- **Total: 1-2 jam**

---

## 📞 Script Otomatis

Saya bisa buatkan script untuk migrasi otomatis jika Anda mau. Script akan:
1. Backup folder uploads
2. Pindahkan file ke public/uploads
3. Update kode controller
4. Generate .htaccess
5. Test file access

Mau saya buatkan? 🚀

---

**Last Updated:** 2026-02-25
**Status:** ANALYSIS COMPLETE
**Recommendation:** MIGRATE TO public/uploads/

