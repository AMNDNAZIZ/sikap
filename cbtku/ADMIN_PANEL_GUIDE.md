# ADMIN PANEL - UPDATE SERVER

**Fitur Baru:** Admin Panel dengan Upload Interface!

---

## 🎉 FITUR BARU

Sekarang Update Server dilengkapi dengan:

1. **🔐 Admin Login** - Autentikasi untuk keamanan
2. **📊 Dashboard** - Statistics dan overview
3. **📤 Upload Interface** - Upload ZIP langsung dari browser
4. **⚙️ Manage Updates** - Activate/deactivate updates
5. **📋 Download Logs** - Monitor download activity

---

## 🔑 DEFAULT CREDENTIALS

**IMPORTANT:** Ganti credentials ini setelah setup!

```
Username: admin
Password: admin123
```

**Cara ganti:**
Edit file: `updatecbt_standalone/admin_login.php`
Baris 18-19:
```php
$admin_username = 'admin';        // ← Ganti
$admin_password = 'admin123';     // ← Ganti dengan password kuat!
```

---

## 🚀 CARA MENGGUNAKAN

### STEP 1: Login

1. Buka: `https://updatecbt.sakasalika.com/admin_login.php`
2. Username: `admin`
3. Password: `admin123`
4. Klik: **Login**

---

### STEP 2: Dashboard

Setelah login, Anda akan melihat:

**Statistics:**
- Total Updates
- Active Updates
- Total Downloads

**Quick Actions:**
- 📤 Upload Update
- ⚙️ Manage Updates
- 📋 Download Logs
- 🌐 View Public Page

**Recent Updates Table:**
- List 10 update terbaru
- Status (Active/Inactive)
- Download count
- Actions (Edit/Delete)

---

### STEP 3: Upload Update

**Cara Upload:**

1. Klik: **📤 Upload Update**

2. Isi form:
   - **Version:** 1.0.2 (format X.Y.Z)
   - **Title:** Fix Auto-Regenerate Hash Bug
   - **Description:** Perbaikan bug... (optional)
   - **File:** Click atau drag & drop ZIP file
   - **Critical:** Check jika update wajib

3. Klik: **📤 Upload Update**

4. Wait upload & processing...

5. Done! Update langsung aktif dan siap didownload

**Yang Terjadi Otomatis:**
- ✅ File di-upload ke `writable/updates/`
- ✅ File hash di-calculate (SHA256)
- ✅ Data di-insert ke database
- ✅ Update langsung aktif
- ✅ Ready untuk client download

---

### STEP 4: Manage Updates

**Cara Manage:**

1. Klik: **⚙️ Manage Updates**

2. Anda akan lihat table semua updates

3. Actions available:
   - **Activate/Deactivate:** Toggle status update
   - **Delete:** Hapus update (file & database)

**Use Cases:**

**Deactivate Update:**
- Jika ada bug di update
- Temporary disable
- Testing purposes

**Activate Update:**
- Enable update yang di-disable
- Re-enable setelah fix

**Delete Update:**
- Remove update permanently
- Clean up old updates
- Free up space

---

## 📊 WORKFLOW BARU (DENGAN ADMIN PANEL)

### OLD WORKFLOW (Manual):
```
1. Create update di localhost
2. Upload ZIP via FTP/cPanel
3. Login phpMyAdmin
4. Copy-paste SQL query
5. Run query
6. Test API
```
**Time:** ~15 menit

### NEW WORKFLOW (Admin Panel):
```
1. Create update di localhost
2. Login admin panel
3. Upload ZIP via browser
4. Done!
```
**Time:** ~3 menit! 🚀

---

## 🆚 COMPARISON

| Task | Manual | Admin Panel |
|------|--------|-------------|
| **Upload File** | FTP/cPanel | Drag & drop browser |
| **Register DB** | phpMyAdmin + SQL | Automatic |
| **Calculate Hash** | Manual | Automatic |
| **Activate** | SQL query | One click |
| **Manage** | phpMyAdmin | Web interface |
| **Time** | ~15 min | ~3 min |
| **Ease** | ⭐⭐ | ⭐⭐⭐⭐⭐ |

---

## 🔒 SECURITY FEATURES

### 1. Session-Based Authentication
- Login required untuk akses admin
- Session timeout
- Logout functionality

### 2. File Validation
- Only ZIP files allowed
- File size limit (50MB)
- Extension check

### 3. Input Validation
- Version format validation (X.Y.Z)
- Required fields check
- SQL injection prevention (prepared statements)

### 4. Access Control
- Admin pages check authentication
- Redirect to login if not authenticated
- No direct file access

---

## 📁 NEW FILES

```
updatecbt_standalone/
├── admin_login.php          ✅ Login page
├── admin_dashboard.php      ✅ Dashboard
├── admin_upload.php         ✅ Upload interface
├── admin_manage.php         ✅ Manage updates
├── admin_logout.php         ✅ Logout
└── views/
    └── welcome.php          ✅ Updated (with admin link)
```

---

## 🎯 SETUP ADMIN PANEL

### STEP 1: Ganti Credentials (WAJIB!)

Edit: `updatecbt_standalone/admin_login.php`

```php
// Line 18-19
$admin_username = 'your_username';     // ← Ganti
$admin_password = 'your_strong_pass';  // ← Ganti
```

**Password Tips:**
- Minimal 12 karakter
- Kombinasi huruf besar, kecil, angka, simbol
- Jangan gunakan password default!

---

### STEP 2: Upload ke Server

Sama seperti sebelumnya:
1. ZIP folder `updatecbt_standalone/`
2. Upload ke hosting
3. Extract
4. Rename to `updatecbt`

**New files akan otomatis ter-include!**

---

### STEP 3: Test Admin Panel

1. Browse: `https://updatecbt.sakasalika.com/admin_login.php`
2. Login dengan credentials
3. Check dashboard
4. Try upload test file

---

## 📝 USAGE EXAMPLES

### Example 1: Upload Update v1.0.2

**Scenario:** Upload fix hash bug

**Steps:**
1. Login admin panel
2. Click: Upload Update
3. Fill form:
   - Version: 1.0.2
   - Title: Fix Auto-Regenerate Hash Bug
   - Description: Perbaikan bug loop aktivasi
   - File: update_v1.0.2.zip (from localhost)
   - Critical: No
4. Click: Upload
5. Done! ✅

**Result:**
- File uploaded to `writable/updates/update_v1.0.2.zip`
- Database updated
- API returns new update
- Clients can download immediately

---

### Example 2: Deactivate Buggy Update

**Scenario:** Update v1.0.3 has bug, need to disable

**Steps:**
1. Login admin panel
2. Click: Manage Updates
3. Find: v1.0.3
4. Click: Deactivate
5. Done! ✅

**Result:**
- Update v1.0.3 inactive
- API won't return this update
- Clients won't see it
- Can re-activate later after fix

---

### Example 3: Delete Old Update

**Scenario:** Remove old update v1.0.1

**Steps:**
1. Login admin panel
2. Click: Manage Updates
3. Find: v1.0.1
4. Click: Delete
5. Confirm
6. Done! ✅

**Result:**
- File deleted from server
- Database record deleted
- Space freed up

---

## 🔧 TROUBLESHOOTING

### Issue 1: Cannot Login

**Symptoms:** "Username atau password salah"

**Solution:**
```
1. Check credentials di admin_login.php
2. Verify username dan password correct
3. Check case-sensitive
```

---

### Issue 2: Upload Failed

**Symptoms:** "Gagal menyimpan file"

**Possible causes:**
- Folder permission
- Disk space full
- File too large

**Solution:**
```
1. Check writable/updates/ permission (755)
2. Check disk space
3. Check file size < 50MB
```

---

### Issue 3: Session Expired

**Symptoms:** Redirect to login after some time

**Solution:**
```
Normal behavior - session timeout
Just login again
```

---

## 📊 STATISTICS & MONITORING

### View Statistics

**Dashboard shows:**
- Total updates uploaded
- Active updates count
- Total downloads across all updates

### Download Logs

**Coming soon:**
- Per-update download count
- Download by domain
- Download success/fail rate
- Download timeline

---

## 🎯 BEST PRACTICES

### 1. Security

- ✅ Change default credentials immediately
- ✅ Use strong password
- ✅ Logout after use
- ✅ Don't share credentials

### 2. Upload

- ✅ Test update di localhost first
- ✅ Verify ZIP file correct
- ✅ Use proper version numbering
- ✅ Write clear title & description

### 3. Management

- ✅ Deactivate buggy updates immediately
- ✅ Delete old unused updates
- ✅ Monitor download statistics
- ✅ Keep only necessary updates active

---

## 📞 QUICK REFERENCE

**URLs:**
```
Login:     https://updatecbt.sakasalika.com/admin_login.php
Dashboard: https://updatecbt.sakasalika.com/admin_dashboard.php
Upload:    https://updatecbt.sakasalika.com/admin_upload.php
Manage:    https://updatecbt.sakasalika.com/admin_manage.php
```

**Default Credentials:**
```
Username: admin
Password: admin123
```

**File Locations:**
```
Admin files: updatecbt_standalone/admin_*.php
Uploads: writable/updates/
Database: update_server
```

---

## 🎉 BENEFITS

### Before Admin Panel:
- ❌ Manual FTP upload
- ❌ Manual SQL query
- ❌ Manual hash calculation
- ❌ No easy management
- ⏱️ ~15 minutes per update

### After Admin Panel:
- ✅ Browser upload (drag & drop)
- ✅ Automatic database insert
- ✅ Automatic hash calculation
- ✅ Easy management interface
- ⏱️ ~3 minutes per update

**Time Saved: 80%!** 🚀

---

**Last Updated:** 2026-03-08  
**Version:** 1.0  
**Status:** ✅ Ready to Use

**Recommendation:** Use Admin Panel for all update uploads!
