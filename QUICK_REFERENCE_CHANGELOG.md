# Quick Reference: Update Versi & Changelog

## 🚀 Langkah Cepat

### 1. Akses Menu Changelog
```
URL: http://localhost:8080/siakad/admin/changelogs
```
⚠️ **PENTING:** Hanya bisa diakses dari `localhost` atau `127.0.0.1` (dengan atau tanpa port)

### 2. Tambah Changelog Baru
- Klik **"Tambah Baru"**
- Isi form:
  - **Versi:** `1.1.5`
  - **Tanggal:** `2026-04-04`
  - **Deskripsi:** (lihat draft di bawah)
  - **Stable:** ✅ Checked
- Klik **"Simpan Changelog"**

### 3. Verifikasi
- File `app/Database/changelogs_sync.php` otomatis ter-update
- Versi baru muncul di list changelog

---

## 📝 Draft Deskripsi untuk v1.1.5

Copy-paste teks ini ke field "Deskripsi Update":

```
Penambahan 3 mode sinkronisasi data siswa dari Dapodik (Skip/Update/Merge)
Ekspansi mapping data siswa dari 6 field menjadi 34 field lengkap (data pribadi, alamat, orang tua, wali)
Perbaikan timeout saat mengambil data siswa dari Dapodik (timeout ditingkatkan menjadi 120 detik)
Penambahan data tempat lahir dan tanggal lahir untuk sinkronisasi guru
Perbaikan error "Undefined array key email" di halaman preview data Dapodik
Perbaikan halaman detail siswa wali kelas yang menampilkan blank page
Penambahan fitur deteksi kelas otomatis berdasarkan nama rombel dari Dapodik
Pembersihan file-file view dashboard yang tidak digunakan lagi
Update dokumentasi lengkap sistem sinkronisasi Dapodik
Penambahan dokumentasi mapping field Dapodik ke SIAKAD
Penambahan dokumentasi teknis fitur mode sinkronisasi
```

---

## 🔄 Workflow Deploy ke Production

1. ✅ Tambah changelog di localhost (langkah di atas)
2. ✅ Commit & push ke Git
3. ✅ Buka menu **Admin > Updater**
4. ✅ Klik **"Generate Patch ZIP"**
5. ✅ Download file patch
6. ✅ Upload ke production
7. ✅ Jalankan **"Update File"**
8. ✅ Jalankan **"Migrasi Database"**
9. ✅ Verifikasi versi baru di footer

---

## 📂 File yang Ter-update Otomatis

- `app/Database/changelogs_sync.php` - Auto-generated saat save changelog

---

## ⚠️ Troubleshooting

| Problem | Solution |
|---------|----------|
| Menu tidak bisa diakses | Pastikan akses dari `localhost` |
| File sync tidak ter-update | Jalankan `php app/Database/sync_changelogs_to_file.php` |
| Versi tidak muncul di production | Jalankan migrasi database di production |

---

## 📚 Dokumentasi Lengkap

Lihat file berikut untuk detail:
- `PANDUAN_UPDATE_VERSI_DAN_CHANGELOG.md` - Panduan lengkap
- `CHANGELOG_DRAFT_V1.1.5.md` - Draft lengkap untuk v1.1.5

---

**Last Updated:** 2026-04-04
