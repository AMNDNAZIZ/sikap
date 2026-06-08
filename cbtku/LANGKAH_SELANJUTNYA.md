# 🎯 LANGKAH SELANJUTNYA - Implementasi Client Side

## ✅ STATUS SAAT INI

Update server standalone sudah berjalan dengan sempurna di:
```
https://updatecbt.sakasalika.com/
```

**Checklist Server:**
- ✅ Server is Running
- ✅ Database: Connected
- ✅ PHP Version: 8.2.29
- ✅ API Endpoints: Ready

---

## 🚀 LANGKAH BERIKUTNYA

Sekarang kita perlu mengimplementasikan client side (aplikasi CBT) agar bisa:
1. Cek update dari server
2. Download file update
3. Install update otomatis

---

## 📋 IMPLEMENTASI CLIENT SIDE

### STEP 1: Tambahkan Method ke Updater.php (5 menit)

File yang sudah ada method lengkap: `UpdaterEnhancement.php`

**Method yang perlu ditambahkan ke `Updater.php`:**
1. `checkOnlineUpdate()` - Cek update dari server
2. `downloadUpdate()` - Download file update
3. `installOnlineUpdate()` - Install update yang sudah didownload
4. `getCurrentVersion()` - Get versi saat ini
5. `updateVersionConstant()` - Update versi di Constants.php

**Cara:**
1. Buka file: `app/Controllers/Admin/UpdaterEnhancement.php`
2. Copy semua method yang ada
3. Paste ke dalam class `Updater` di file: `app/Controllers/Admin/Updater.php`
4. Letakkan sebelum closing brace `}` terakhir

**Lokasi paste:** Setelah method `getDirectorySize()` (baris terakhir sebelum `}`)

---

### STEP 2: Update View Updater (Sudah Siap)

File view sudah lengkap dengan UI untuk online update:
- `app/Views/admin/updater/index.php`

**Fitur yang sudah ada:**
- ✅ Button "Cek Update Online"
- ✅ Display update info (version, changelog, file size)
- ✅ Button "Download & Install Update"
- ✅ Progress bar untuk download & install
- ✅ JavaScript untuk handle AJAX calls

**Tidak perlu edit**, view sudah siap pakai!

---

### STEP 3: Tambahkan Routes (2 menit)

Buka file: `app/Config/Routes.php`

Tambahkan routes untuk online update:

```php
// Online Update Routes
$routes->get('admin/updater/checkOnlineUpdate', 'Admin\Updater::checkOnlineUpdate', ['filter' => 'auth']);
$routes->post('admin/updater/downloadUpdate', 'Admin\Updater::downloadUpdate', ['filter' => 'auth']);
$routes->post('admin/updater/installOnlineUpdate', 'Admin\Updater::installOnlineUpdate', ['filter' => 'auth']);
```

**Lokasi:** Di dalam group admin atau setelah routes updater yang sudah ada

---

### STEP 4: Tambahkan Konfigurasi di .env (1 menit)

Buka file: `.env`

Tambahkan baris ini di bagian bawah:

```env
#--------------------------------------------------------------------
# UPDATE SERVER
#--------------------------------------------------------------------
UPDATE_SERVER_URL = https://updatecbt.sakasalika.com
```

**Save file**

---

### STEP 5: Tambahkan APP_VERSION di Constants.php (1 menit)

Buka file: `app/Config/Constants.php`

Tambahkan konstanta versi (jika belum ada):

```php
/*
|--------------------------------------------------------------------------
| Application Version
|--------------------------------------------------------------------------
*/
defined('APP_VERSION') || define('APP_VERSION', '1.0.0');
defined('LAST_UPDATE') || define('LAST_UPDATE', '2026-03-03 10:00');
```

**Lokasi:** Di bagian atas file, setelah konstanta lainnya

---

### STEP 6: Create Folder untuk Download (30 detik)

Via cPanel File Manager atau SSH:

```bash
# Buat folder untuk temporary download
mkdir -p writable/updates/downloads
chmod 755 writable/updates/downloads
```

**Struktur akhir:**
```
writable/
├── backups/
├── cache/
├── logs/
├── session/
├── uploads/
└── updates/
    └── downloads/    ← Folder baru untuk download update
```

---

## ✅ CHECKLIST IMPLEMENTASI

- [ ] Method dari UpdaterEnhancement.php sudah dicopy ke Updater.php
- [ ] Routes untuk online update sudah ditambahkan
- [ ] UPDATE_SERVER_URL sudah ditambahkan di .env
- [ ] APP_VERSION sudah ditambahkan di Constants.php
- [ ] Folder writable/updates/downloads/ sudah dibuat
- [ ] Permission folder 755

---

## 🧪 TEST CLIENT SIDE

Setelah semua langkah selesai:

### 1. Akses Menu Updater

```
https://[domain-cbt-anda]/admin/updater
```

### 2. Klik "Cek Update Online"

**Expected:**
- Loading indicator muncul
- Jika ada update: Info update ditampilkan (version, changelog, file size)
- Jika tidak ada update: Alert "Sudah Terbaru"

### 3. Klik "Download & Install Update"

**Expected:**
- Progress bar muncul
- Step 1: Downloading... (0-50%)
- Step 2: Installing... (50-100%)
- Success message
- Page refresh otomatis

---

## 📝 CONTOH PENGGUNAAN

### Scenario 1: Ada Update Tersedia

```
1. Admin klik "Cek Update Online"
2. Server response:
   {
     "has_update": true,
     "latest_version": "1.0.1",
     "title": "Fix Anti-Cheat & Responsive Tables",
     "file_size": 1234567,
     "changelog": {...}
   }
3. UI menampilkan info update
4. Admin klik "Download & Install"
5. System:
   - Download file dari server
   - Verify hash
   - Backup file lama
   - Extract & install
   - Update version di Constants.php
   - Run migrations (jika ada)
6. Success! Versi updated ke 1.0.1
```

### Scenario 2: Sudah Versi Terbaru

```
1. Admin klik "Cek Update Online"
2. Server response:
   {
     "has_update": false,
     "message": "Aplikasi sudah versi terbaru"
   }
3. Alert: "Sudah Terbaru"
```

---

## 🔄 WORKFLOW LENGKAP (Development → Production)

### Di Localhost (Development):

1. **Develop fitur baru**
   - Edit code
   - Test
   - Commit

2. **Generate patch**
   - Menu: Admin → Updater
   - Klik: "Generate Patch"
   - Download: `update_patch_20260303_120000.zip`

3. **Upload ke update server**
   - Rename: `update_v1.0.1.zip`
   - Upload ke: `writable/updates/` di server update
   - Calculate SHA256 hash

4. **Update database update server**
   ```sql
   UPDATE updates 
   SET 
       file_size = 1234567,
       file_hash = 'sha256_hash_dari_file',
       is_active = 1
   WHERE version = '1.0.1';
   ```

### Di Production (Sekolah):

1. **Admin login**
2. **Menu: Admin → Updater**
3. **Klik: "Cek Update Online"**
4. **Klik: "Download & Install Update"**
5. **Done!** Aplikasi updated otomatis

---

## 🆘 TROUBLESHOOTING

### "Update server tidak dikonfigurasi"

**Fix:**
- Check file `.env`
- Pastikan ada: `UPDATE_SERVER_URL = https://updatecbt.sakasalika.com`

### "Gagal menghubungi server"

**Fix:**
1. Check koneksi internet
2. Check server update masih running
3. Check firewall tidak block

### "File hash mismatch"

**Fix:**
- File corrupt saat download
- Coba download ulang
- Check hash di database update server

### "Extension PHP Zip tidak aktif"

**Fix:**
```bash
# Edit php.ini
extension=zip

# Restart web server
```

### "Permission denied untuk writable/updates/"

**Fix:**
```bash
chmod 755 writable/updates/
chmod 755 writable/updates/downloads/
```

---

## 📊 MONITORING

### Log Files

**Client side:**
```
writable/logs/log-[date].log
```

**Server side:**
```
public/updatecbt/error.log
public/updatecbt/logs/app.log
```

### Check Update History

**Database client:**
```sql
SELECT * FROM migrations ORDER BY version DESC LIMIT 10;
```

**Database server:**
```sql
SELECT 
    version,
    title,
    download_count,
    is_active,
    created_at
FROM updates
ORDER BY version DESC;
```

---

## 🎉 SELESAI!

Setelah semua langkah di atas selesai, sistem update online sudah fully functional!

**Keuntungan:**
- ✅ Update sekali klik (tidak perlu download/upload manual)
- ✅ Otomatis backup sebelum update
- ✅ Otomatis run migrations
- ✅ Otomatis regenerate license hash
- ✅ Progress bar real-time
- ✅ Rollback jika ada masalah

**Next Level:**
- Auto-update notification (cek update otomatis setiap login)
- Scheduled update (update otomatis di jam tertentu)
- Update history & changelog viewer
- Multi-server deployment (update banyak sekolah sekaligus)

---

**Status:** Ready to Implement! 🚀
**Estimasi Waktu:** 10-15 menit
**Tingkat Kesulitan:** Easy 🟢
