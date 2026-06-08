# Migrasi Folder Uploads ke Public

## Masalah

Terdapat **dua lokasi folder uploads** yang menyebabkan konflik:
1. `uploads/` (di root project) - Folder lama
2. `public/uploads/` (FCPATH) - Folder yang seharusnya digunakan

Beberapa file ter-upload ke lokasi yang salah, menyebabkan:
- File tidak ditemukan saat preview
- Inkonsistensi path antara controller
- Potensi security issue (file di luar public folder)

## Solusi

### 1. Copy Semua File ke Public
```powershell
# Semua file dari uploads/ di-copy ke public/uploads/
✅ activities/
✅ articles/
✅ chat/
✅ facilities/
✅ logo/
✅ sliders/
✅ soal_images/
✅ teachers/
✅ teacher_docs/
✅ vision/
```

### 2. Rename Folder Lama sebagai Backup
```
uploads/ → uploads_OLD_BACKUP_20260419/
```

### 3. Verifikasi Path di Kode

Semua kode sudah menggunakan `FCPATH . 'uploads/'` yang mengarah ke `public/uploads/`:

**✅ Sudah Benar:**
- `app/Config/Constants.php`: `UPLOAD_PATH = FCPATH . 'uploads/'`
- `app/Controllers/Profile.php`: `FCPATH . 'uploads/teacher_docs/'`
- `app/Controllers/Admin/Teachers.php`: `FCPATH . 'uploads/teacher_docs/'`
- `app/Controllers/Admin/Cms/*`: `FCPATH . 'uploads/...'`
- `app/Views/*`: `base_url('uploads/...')`

## Keuntungan Migrasi

### Security
✅ **File di dalam public folder** - Accessible via web server  
✅ **Tidak ada file sensitif di luar public** - Best practice  
✅ **Consistent path** - Semua controller menggunakan path yang sama  

### Performance
✅ **Direct access** - Web server serve file langsung tanpa PHP  
✅ **Cacheable** - Browser bisa cache static files  
✅ **CDN ready** - Mudah di-integrate dengan CDN  

### Maintenance
✅ **Single source of truth** - Hanya satu lokasi uploads  
✅ **Easier backup** - Backup public/uploads/ saja  
✅ **Clear structure** - Tidak ada duplikasi folder  

## Struktur Folder Setelah Migrasi

```
siakad/
├── public/
│   ├── uploads/              ← LOKASI UTAMA (ACTIVE)
│   │   ├── activities/
│   │   ├── articles/
│   │   ├── chat/
│   │   ├── facilities/
│   │   ├── logo/
│   │   ├── sliders/
│   │   ├── soal_images/
│   │   ├── students/
│   │   ├── teachers/
│   │   ├── teacher_docs/
│   │   └── vision/
│   └── index.php
├── uploads_OLD_BACKUP_20260419/  ← BACKUP (INACTIVE)
└── app/
```

## Testing Setelah Migrasi

### 1. Test Upload File
- [x] Upload foto guru → Tersimpan di `public/uploads/teachers/`
- [x] Upload dokumen guru → Tersimpan di `public/uploads/teacher_docs/`
- [x] Upload artikel → Tersimpan di `public/uploads/articles/`
- [x] Upload slider → Tersimpan di `public/uploads/sliders/`

### 2. Test Akses File
- [x] Logo sekolah tampil di header
- [x] Foto guru tampil di halaman guru
- [x] Gambar artikel tampil di berita
- [x] Slider tampil di homepage
- [x] Dokumen guru bisa di-preview
- [x] Dokumen guru bisa di-download

### 3. Test Preview Dokumen
- [x] Admin preview dokumen guru → Tampil
- [x] Guru preview dokumen sendiri → Tampil
- [x] PDF preview dalam iframe → Berfungsi
- [x] Image preview → Berfungsi

## Rollback (Jika Diperlukan)

Jika terjadi masalah, rollback dengan:

```powershell
# 1. Hapus public/uploads/
Remove-Item -Recurse -Force public/uploads/

# 2. Rename backup kembali
Rename-Item uploads_OLD_BACKUP_20260419 uploads

# 3. Restart web server
```

## Cleanup Backup (Setelah Yakin)

Setelah 1-2 minggu testing dan yakin tidak ada masalah:

```powershell
# Hapus folder backup
Remove-Item -Recurse -Force uploads_OLD_BACKUP_20260419/
```

**JANGAN HAPUS SEBELUM YAKIN SEMUA BERFUNGSI!**

## Checklist Verifikasi

- [x] Semua file ter-copy ke public/uploads/
- [x] Folder lama di-rename sebagai backup
- [x] Semua path di kode menggunakan FCPATH
- [x] Logo sekolah tampil
- [x] Foto guru tampil
- [x] Artikel dengan gambar tampil
- [x] Slider homepage tampil
- [x] Upload file baru berfungsi
- [x] Preview dokumen berfungsi
- [x] Download dokumen berfungsi
- [ ] Testing 1-2 minggu tanpa issue
- [ ] Hapus folder backup

## Catatan Penting

1. **Folder backup** (`uploads_OLD_BACKUP_20260419/`) **JANGAN DIHAPUS** sampai yakin semua berfungsi normal minimal 1-2 minggu
2. **Monitor error log** untuk memastikan tidak ada file yang missing
3. **Test semua fitur** yang menggunakan upload file
4. **Backup database** sebelum cleanup final

## Path Reference

| Constant | Value | Keterangan |
|---|---|---|
| `FCPATH` | `C:/xampp/htdocs/siakad/public/` | Public folder |
| `UPLOAD_PATH` | `FCPATH . 'uploads/'` | Upload folder |
| Full Path | `C:/xampp/htdocs/siakad/public/uploads/` | Lokasi aktual |

## URL Access

| Folder | URL |
|---|---|
| Logo | `http://localhost:8080/siakad/uploads/logo/` |
| Teachers | `http://localhost:8080/siakad/uploads/teachers/` |
| Articles | `http://localhost:8080/siakad/uploads/articles/` |
| Teacher Docs | `http://localhost:8080/siakad/uploads/teacher_docs/` |

Semua accessible via web browser karena berada di public folder.
