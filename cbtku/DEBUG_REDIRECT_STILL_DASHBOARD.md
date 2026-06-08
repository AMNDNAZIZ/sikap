# DEBUG: Redirect Masih ke Dashboard

## MASALAH
Setelah selesai ujian, siswa masih langsung diarahkan ke dashboard, bukan ke halaman hasil/selesai.

## KEMUNGKINAN PENYEBAB

### 1. File Belum Ter-Upload ❌
File `app/Controllers/Siswa/Cbt.php` yang sudah diubah belum di-upload ke server.

**Cara Check:**
```bash
# Di server, jalankan:
php test_submit_redirect.php
```

Script akan check apakah method `determineSubmitRedirect` sudah benar.

**Expected Output:**
```
✓ Found: Redirect to HASIL page
  Code: site_url('siswa/cbt/hasil/' . $testId)
✓ Found: Redirect to SELESAI page
  Code: site_url('siswa/cbt/selesai/' . $testId)
✓ Not found: Direct redirect to dashboard (WRONG)
```

**Jika masih salah:**
- Upload ulang file `app/Controllers/Siswa/Cbt.php`
- Pastikan file tidak corrupt saat upload

### 2. PHP OPcache Belum Di-Clear ❌
PHP OPcache masih menyimpan bytecode file lama.

**Cara Fix:**
```bash
# Restart Apache
sudo systemctl restart apache2

# Atau akses via browser
http://localhost:8080/cbtku/clear_opcache.php
```

**Verifikasi:**
```bash
# Check OPcache status
php -r "var_dump(opcache_get_status());"
```

### 3. Browser Cache ❌
Browser masih menyimpan JavaScript lama yang mungkin punya logic redirect berbeda.

**Cara Fix:**
```
1. Hard Refresh: Ctrl + Shift + R
2. Clear Cache: Ctrl + Shift + Del → Clear cache
3. Test dengan Incognito: Ctrl + Shift + N
```

### 4. Response Server Salah ❌
Server mengembalikan redirect URL yang salah.

**Cara Check:**
1. Buka `test_actual_submit.html` di browser
2. Login sebagai siswa di tab lain
3. Copy CSRF token dari cookie (DevTools → Application → Cookies → csrf_cookie_name)
4. Paste ke form
5. Klik "Test Submit"

**Expected Response:**
```json
{
  "redirect": "http://localhost:8080/cbtku/index.php/siswa/cbt/hasil/1"
}
```
atau
```json
{
  "redirect": "http://localhost:8080/cbtku/index.php/siswa/cbt/selesai/1"
}
```

**Jika response salah:**
```json
{
  "redirect": "http://localhost:8080/cbtku/index.php/siswa/cbt"
}
```

Berarti file belum ter-update atau OPcache belum di-clear.

### 5. JavaScript Override ❌
Ada JavaScript di halaman ujian yang meng-override redirect URL.

**Cara Check:**
1. Buka halaman ujian
2. Buka DevTools (F12) → Console
3. Klik "Selesai Ujian" → Konfirmasi
4. Lihat console log

**Expected Log:**
```
[SAVE] Success: ...
```

**Jika ada log aneh:**
```
Redirecting to: http://localhost:8080/cbtku/index.php/siswa/cbt
```

Berarti ada JavaScript yang override. Check file `ujian.php`.

### 6. Bfcache Restore ❌
Browser me-restore halaman ujian dari cache setelah redirect.

**Cara Check:**
Lihat apakah ada flash halaman ujian sebentar sebelum ke dashboard.

**Jika ya:**
- Fix bfcache sudah ditambahkan di `ujian.php`
- Pastikan file sudah ter-upload
- Clear browser cache

## LANGKAH DEBUG SISTEMATIS

### Step 1: Verifikasi File di Server
```bash
php test_submit_redirect.php
```

Jika output menunjukkan kode sudah benar, lanjut ke step 2.
Jika masih salah, upload ulang file.

### Step 2: Clear All Cache
```bash
# 1. Clear PHP OPcache
sudo systemctl restart apache2

# 2. Clear browser cache
Ctrl + Shift + Del

# 3. Test dengan incognito
Ctrl + Shift + N
```

### Step 3: Test dengan Monitoring
1. Buka halaman ujian
2. Buka DevTools (F12)
3. Tab Network → Filter: XHR
4. Tab Console → Clear console
5. Klik "Selesai Ujian" → Konfirmasi
6. Monitor:
   - Network: Lihat request `submit/[ID]`
   - Response: Lihat JSON response
   - Console: Lihat log error

**Expected Network Response:**
```
Request URL: http://localhost:8080/cbtku/index.php/siswa/cbt/submit/1
Status: 200 OK
Response:
{
  "redirect": "http://localhost:8080/cbtku/index.php/siswa/cbt/hasil/1"
}
```

**Expected Console Log:**
```
[determineSubmitRedirect] testId=1, show_score=ya, isForced=false
[determineSubmitRedirect] SHOW_SCORE=YA redirect: http://localhost:8080/cbtku/index.php/siswa/cbt/hasil/1
```

### Step 4: Check Logs
```bash
# Lihat log terbaru
tail -50 writable/logs/log-*.php

# Cari log determineSubmitRedirect
grep "determineSubmitRedirect" writable/logs/log-*.php | tail -10
```

**Expected Log:**
```
DEBUG - [determineSubmitRedirect] testId=1, show_score=ya, isForced=false
DEBUG - [determineSubmitRedirect] SHOW_SCORE=YA redirect: http://localhost:8080/cbtku/index.php/siswa/cbt/hasil/1
```

**Jika log tidak muncul:**
- Method tidak dipanggil (ada error sebelumnya)
- Log level terlalu tinggi (set ke DEBUG di .env)

### Step 5: Test Manual Redirect
Buka URL langsung di browser:
```
http://localhost:8080/cbtku/index.php/siswa/cbt/hasil/1
http://localhost:8080/cbtku/index.php/siswa/cbt/selesai/1
```

**Jika halaman error:**
- Check method `hasil()` dan `selesai()` di controller
- Check view files ada

**Jika halaman OK:**
- Berarti masalah di redirect URL yang dikembalikan server

## TOOLS UNTUK DEBUG

### 1. test_submit_redirect.php
Check apakah kode di controller sudah benar.

```bash
php test_submit_redirect.php
```

### 2. test_actual_submit.html
Test actual submit dan lihat response JSON.

```
1. Buka di browser: test_actual_submit.html
2. Login sebagai siswa
3. Copy CSRF token dari cookie
4. Test submit
```

### 3. Browser DevTools
Monitor request/response real-time.

```
F12 → Network → XHR
F12 → Console
```

### 4. Server Logs
Lihat log server untuk debug.

```bash
tail -f writable/logs/log-*.php
```

## CHECKLIST TROUBLESHOOTING

- [ ] File `Cbt.php` sudah ter-upload
- [ ] PHP OPcache sudah di-clear (restart Apache)
- [ ] Browser cache sudah di-clear
- [ ] Test dengan incognito mode
- [ ] Response JSON menunjukkan URL yang benar
- [ ] Console log tidak ada error
- [ ] Server log menunjukkan redirect yang benar
- [ ] Halaman hasil/selesai bisa diakses langsung

## JIKA SEMUA SUDAH DICOBA

Jika semua langkah sudah dicoba tapi masih redirect ke dashboard:

1. **Backup dan re-upload file:**
   ```bash
   # Di local
   cp app/Controllers/Siswa/Cbt.php Cbt.php.backup
   
   # Upload ulang dengan FTP/SFTP
   # Pastikan file tidak corrupt
   ```

2. **Check file permissions:**
   ```bash
   ls -la app/Controllers/Siswa/Cbt.php
   # Should be readable: -rw-r--r--
   ```

3. **Check file size:**
   ```bash
   wc -l app/Controllers/Siswa/Cbt.php
   # Should be around 2300+ lines
   ```

4. **Compare with original:**
   ```bash
   diff Cbt.php.backup app/Controllers/Siswa/Cbt.php
   # Should show no differences
   ```

5. **Contact support dengan info:**
   - Screenshot response JSON dari Network tab
   - Screenshot console log
   - Copy server log (last 50 lines)
   - Output dari `test_submit_redirect.php`

## KESIMPULAN

Masalah redirect ke dashboard biasanya disebabkan oleh:
1. File belum ter-upload (paling sering)
2. OPcache belum di-clear
3. Browser cache

Solusi: Upload ulang file, restart Apache, clear browser cache.
