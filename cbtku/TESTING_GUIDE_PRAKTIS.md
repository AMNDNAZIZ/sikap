# 🧪 TESTING GUIDE PRAKTIS

**Tanggal**: 9 Maret 2026  
**Purpose**: Panduan testing yang mudah diikuti untuk verify semua fixes

---

## 📋 TESTING CHECKLIST

Copy checklist ini dan isi hasilnya:

```
PRIORITY 1 - CRITICAL:
[ ] Test 1: Form Token Mobile
[ ] Test 2: License Redirect Loop
[ ] Test 3: Session Timeout

PRIORITY 2 - HIGH:
[ ] Test 4: Bug Ujian Berulang
[ ] Test 5: Gagal Menyimpan Jawaban

PRIORITY 3 - MEDIUM:
[ ] Test 6: Status Ujian Berlangsung
[ ] Test 7: Detail Aktivitas
```

---

## 🎯 PRIORITY 1: CRITICAL TESTS

### Test 1: Form Token Mobile Browser

**Tujuan**: Verify form token muncul di browser HP lama

**Persiapan**:
- HP dengan Android 4.x/5.x atau browser lama
- Atau gunakan emulator Android
- Akun siswa untuk login

**Steps**:
1. Buka browser bawaan HP (bukan Chrome)
2. Akses: `http://[server]/cbtku/`
3. Login sebagai siswa
4. Lihat kartu ujian yang sedang berlangsung

**Expected Result**:
- ✅ Form input token muncul
- ✅ Tombol "Mulai Ujian" muncul
- ✅ Tidak ada error di console (F12)
- ✅ Layout responsive bekerja

**Actual Result**:
```
[ ] PASS - Form token muncul dengan normal
[ ] FAIL - Form token tidak muncul
[ ] PARTIAL - Form muncul tapi ada error

Notes:
_____________________________________________
_____________________________________________
_____________________________________________
```

**Jika FAIL**:
1. Buka DevTools Console (F12)
2. Screenshot error yang muncul
3. Check apakah file `index.php` sudah terupdate
4. Clear cache browser (Ctrl+Shift+Del)
5. Hard refresh (Ctrl+F5)

---

### Test 2: License Redirect Loop

**Tujuan**: Verify tidak ada redirect loop antara login dan activate

**Persiapan**:
- Fresh install atau clear license table
- Atau gunakan browser incognito

**Steps**:
1. Clear cookies & cache
2. Akses: `http://[server]/cbtku/`
3. **Expected**: Redirect ke `/activate`
4. Input license key yang valid
5. Klik "Aktivasi"
6. **Expected**: Redirect ke `/login` dengan pesan "Aplikasi sudah diaktivasi"
7. Login dengan credentials
8. **Expected**: Masuk ke `/dashboard`

**Expected Result**:
- ✅ Flow: activate → login → dashboard
- ✅ Tidak ada redirect loop
- ✅ Tidak ada error "Too many redirects"
- ✅ Pesan success muncul

**Actual Result**:
```
[ ] PASS - Flow bekerja dengan normal
[ ] FAIL - Ada redirect loop
[ ] PARTIAL - Flow bekerja tapi ada warning

Notes:
_____________________________________________
_____________________________________________
_____________________________________________
```

**Jika FAIL**:
1. Check logs: `tail -f writable/logs/log-*.log | grep -i "license\|redirect"`
2. Verify files updated: BaseController, Activate, LicenseFilter
3. Clear session: Delete `writable/session/*`
4. Try again dengan fresh browser

---

### Test 3: Session Timeout

**Tujuan**: Verify user tidak logout paksa saat aktif

**Persiapan**:
- Akun admin atau siswa
- Timer (set 10 menit)

**Steps**:
1. Login sebagai admin
2. Tunggu 5 menit (idle, jangan klik apa-apa)
3. Refresh page (F5)
4. **Expected**: Masih login ✅
5. Tunggu 5 menit lagi (total 10 menit)
6. Klik menu atau refresh
7. **Expected**: Masih login ✅
8. Tunggu 2 jam + 5 menit (idle)
9. Refresh page
10. **Expected**: Logout (session expired) ✅

**Expected Result**:
- ✅ 5 menit idle: Masih login
- ✅ 10 menit idle: Masih login
- ✅ 2 jam idle: Logout (normal)
- ✅ Tidak ada logout paksa saat aktif

**Actual Result**:
```
[ ] PASS - Session bekerja dengan normal
[ ] FAIL - Logout paksa sebelum 2 jam
[ ] PARTIAL - Kadang logout, kadang tidak

Notes:
_____________________________________________
_____________________________________________
_____________________________________________
```

**Jika FAIL**:
1. Check Session.php config:
   - expiration = 7200 (2 jam)
   - timeToUpdate = 300 (5 menit)
2. Check Cookie.php config:
   - expires = 0 (session cookie)
3. Check Boot files: Tidak ada ini_set() session
4. Check BaseController: Tidak ada session_set_cookie_params()
5. Check Auth.php: Tidak ada markAsTempdata()

---

## 🎯 PRIORITY 2: HIGH TESTS

### Test 4: Bug Ujian Berulang

**Tujuan**: Verify siswa tidak bisa input token lagi setelah selesai

**Persiapan**:
- Akun siswa
- Ujian yang sudah selesai (status = 'finished')

**Steps**:
1. Login sebagai siswa yang sudah selesai ujian
2. Lihat dashboard, kartu ujian show "UJIAN SUDAH SELESAI"
3. Coba input token ujian yang sama
4. **Expected**: Error "Anda sudah menyelesaikan ujian ini. Tidak dapat mengulang ujian." ✅
5. Coba akses URL langsung: `/siswa/cbt/mulai/{testId}`
6. **Expected**: Redirect ke dashboard dengan error ✅

**Expected Result**:
- ✅ Token input di-block
- ✅ Direct URL access di-block
- ✅ Error message yang jelas
- ✅ Log warning di logs

**Actual Result**:
```
[ ] PASS - Siswa tidak bisa re-entry
[ ] FAIL - Siswa bisa input token lagi
[ ] PARTIAL - Block tapi tidak ada error message

Notes:
_____________________________________________
_____________________________________________
_____________________________________________
```

**Jika FAIL**:
1. Check database: `SELECT * FROM cbt_student_sessions WHERE student_id = X AND test_id = Y`
2. Verify status = 'finished'
3. Check Cbt.php verifyToken() method (line ~1514)
4. Check Cbt.php mulai() method (line ~517)
5. Check logs: `tail -f writable/logs/log-*.log | grep -i "re-enter"`

---

### Test 5: Gagal Menyimpan Jawaban

**Tujuan**: Verify jawaban siswa tersimpan dengan baik

**Persiapan**:
- Akun siswa
- Ujian yang sedang berlangsung
- Access ke database

**Steps**:
1. Login sebagai siswa
2. Input token, mulai ujian
3. Jawab 5 soal
4. Klik "Simpan Jawaban" atau tunggu auto-save
5. **Expected**: Response "status: ok, saved: 5" ✅
6. Check database: `SELECT * FROM cbt_answers WHERE student_id = X AND test_id = Y`
7. **Expected**: 5 jawaban tersimpan ✅
8. Refresh page (F5)
9. **Expected**: Jawaban masih ada (tidak hilang) ✅

**Expected Result**:
- ✅ Jawaban tersimpan ke database
- ✅ Response success dari server
- ✅ Jawaban tidak hilang setelah refresh
- ✅ Tidak ada error di console

**Actual Result**:
```
[ ] PASS - Jawaban tersimpan dengan baik
[ ] FAIL - Jawaban tidak tersimpan
[ ] PARTIAL - Kadang tersimpan, kadang tidak

Notes:
_____________________________________________
_____________________________________________
_____________________________________________
```

**Jika FAIL**:
1. Check console: Ada error JavaScript?
2. Check Network tab: Request ke saveAnswersBulk berhasil?
3. Check database: Jawaban ada di table cbt_answers?
4. Check logs: `tail -f writable/logs/log-*.log | grep -i "saveAnswer"`
5. Check session: Masih valid?

---

## 🎯 PRIORITY 3: MEDIUM TESTS

### Test 6: Status Ujian Berlangsung

**Tujuan**: Verify admin bisa monitoring ujian real-time

**Persiapan**:
- Akun admin
- Siswa yang sedang ujian (status = 'active')

**Steps**:
1. Login sebagai admin
2. Akses: `/admin/cbt/aktivitas`
3. Lihat list ujian
4. **Expected**: Badge "Sedang Berlangsung" muncul untuk ujian yang aktif ✅
5. **Expected**: Row highlight dengan warna berbeda ✅
6. **Expected**: Animated indicator (berkedip) ✅
7. Tunggu 30 detik
8. **Expected**: Page auto-refresh ✅
9. Check browser title
10. **Expected**: Title berkedip dengan jumlah ujian berlangsung ✅

**Expected Result**:
- ✅ Badge visual muncul
- ✅ Row highlighting bekerja
- ✅ Animated indicator bekerja
- ✅ Auto-refresh setiap 30 detik
- ✅ Browser title indicator bekerja

**Actual Result**:
```
[ ] PASS - Monitoring bekerja dengan baik
[ ] FAIL - Status tidak muncul
[ ] PARTIAL - Muncul tapi tidak auto-refresh

Notes:
_____________________________________________
_____________________________________________
_____________________________________________
```

---

### Test 7: Detail Aktivitas

**Tujuan**: Verify tidak ada siswa hilang atau dobel

**Persiapan**:
- Akun admin
- Ujian dengan beberapa siswa

**Steps**:
1. Login sebagai admin
2. Akses: `/admin/cbt/aktivitas`
3. Klik "Detail" pada salah satu ujian
4. Lihat list siswa
5. **Expected**: Semua siswa yang terdaftar muncul ✅
6. **Expected**: Tidak ada siswa yang dobel ✅
7. Check database: `SELECT COUNT(*) FROM cbt_student_sessions WHERE test_id = X`
8. Compare dengan jumlah di halaman detail
9. **Expected**: Jumlah sama ✅

**Expected Result**:
- ✅ Semua siswa muncul
- ✅ Tidak ada siswa dobel
- ✅ Jumlah match dengan database
- ✅ Status siswa akurat (active/finished)

**Actual Result**:
```
[ ] PASS - Detail aktivitas akurat
[ ] FAIL - Ada siswa hilang atau dobel
[ ] PARTIAL - Muncul tapi jumlah tidak match

Notes:
_____________________________________________
_____________________________________________
_____________________________________________
```

---

## 📊 TESTING SUMMARY TEMPLATE

Copy template ini dan isi setelah testing:

```
═══════════════════════════════════════════════════════════════
  TESTING SUMMARY - [Tanggal]
═══════════════════════════════════════════════════════════════

Tester: _______________________
Environment: [ ] Development [ ] Production
Browser: _______________________
Device: _______________________

───────────────────────────────────────────────────────────────
  PRIORITY 1 - CRITICAL
───────────────────────────────────────────────────────────────

Test 1: Form Token Mobile
  Status: [ ] PASS [ ] FAIL [ ] PARTIAL
  Notes: ___________________________________________________

Test 2: License Redirect Loop
  Status: [ ] PASS [ ] FAIL [ ] PARTIAL
  Notes: ___________________________________________________

Test 3: Session Timeout
  Status: [ ] PASS [ ] FAIL [ ] PARTIAL
  Notes: ___________________________________________________

───────────────────────────────────────────────────────────────
  PRIORITY 2 - HIGH
───────────────────────────────────────────────────────────────

Test 4: Bug Ujian Berulang
  Status: [ ] PASS [ ] FAIL [ ] PARTIAL
  Notes: ___________________________________________________

Test 5: Gagal Menyimpan Jawaban
  Status: [ ] PASS [ ] FAIL [ ] PARTIAL
  Notes: ___________________________________________________

───────────────────────────────────────────────────────────────
  PRIORITY 3 - MEDIUM
───────────────────────────────────────────────────────────────

Test 6: Status Ujian Berlangsung
  Status: [ ] PASS [ ] FAIL [ ] PARTIAL
  Notes: ___________________________________________________

Test 7: Detail Aktivitas
  Status: [ ] PASS [ ] FAIL [ ] PARTIAL
  Notes: ___________________________________________________

───────────────────────────────────────────────────────────────
  OVERALL RESULT
───────────────────────────────────────────────────────────────

Total Tests: 7
Passed: _____ / 7
Failed: _____ / 7
Partial: _____ / 7

Overall Status: [ ] READY FOR DEPLOYMENT [ ] NEED FIXES

───────────────────────────────────────────────────────────────
  ISSUES FOUND
───────────────────────────────────────────────────────────────

Issue 1:
  Description: _______________________________________________
  Severity: [ ] Critical [ ] High [ ] Medium [ ] Low
  Action: ___________________________________________________

Issue 2:
  Description: _______________________________________________
  Severity: [ ] Critical [ ] High [ ] Medium [ ] Low
  Action: ___________________________________________________

───────────────────────────────────────────────────────────────
  RECOMMENDATIONS
───────────────────────────────────────────────────────────────

1. ___________________________________________________________
2. ___________________________________________________________
3. ___________________________________________________________

───────────────────────────────────────────────────────────────
  SIGN OFF
───────────────────────────────────────────────────────────────

Tested By: _______________________
Date: _______________________
Approved By: _______________________
Date: _______________________

═══════════════════════════════════════════════════════════════
```

---

## 🚨 TROUBLESHOOTING GUIDE

### Problem: Form Token Tidak Muncul

**Symptoms**:
- Form input token tidak muncul
- Tombol "Mulai Ujian" tidak ada
- Console error: "Unexpected token" atau "SyntaxError"

**Solutions**:
1. Clear browser cache (Ctrl+Shift+Del)
2. Hard refresh (Ctrl+F5)
3. Check file updated: `app/Views/siswa/cbt/index.php`
4. Check console for JavaScript errors
5. Try different browser

---

### Problem: Redirect Loop

**Symptoms**:
- Browser error: "Too many redirects"
- Loop antara /login dan /activate
- Tidak bisa masuk aplikasi

**Solutions**:
1. Clear cookies & cache
2. Check license table: `SELECT * FROM licenses`
3. Verify files updated: BaseController, Activate, LicenseFilter
4. Clear session: Delete `writable/session/*`
5. Check logs: `tail -f writable/logs/log-*.log | grep -i "redirect"`

---

### Problem: Logout Paksa

**Symptoms**:
- User logout setelah beberapa menit
- Session expired terlalu cepat
- Harus login berulang kali

**Solutions**:
1. Check Session.php: expiration = 7200, timeToUpdate = 300
2. Check Cookie.php: expires = 0
3. Check Boot files: Tidak ada ini_set() session
4. Check BaseController: Tidak ada session_set_cookie_params()
5. Check Auth.php: Tidak ada markAsTempdata()
6. Restart server (jika perlu)

---

### Problem: Jawaban Tidak Tersimpan

**Symptoms**:
- Response error dari server
- Jawaban hilang setelah refresh
- Database tidak ada record

**Solutions**:
1. Check console: JavaScript error?
2. Check Network tab: Request berhasil?
3. Check database: Table cbt_answers ada?
4. Check logs: `tail -f writable/logs/log-*.log | grep -i "saveAnswer"`
5. Check session: Masih valid?
6. Check anti-cheat: Tidak locked?

---

## 📞 SUPPORT CONTACTS

**Developer**: [Your contact]  
**Server Admin**: [Admin contact]  
**Database Admin**: [DBA contact]

**Emergency Hotline**: [Emergency number]

---

## 📝 NOTES

- Test di environment yang sama dengan production
- Gunakan data real (bukan dummy)
- Document semua issues yang ditemukan
- Screenshot error untuk reference
- Backup sebelum deploy

---

**Last Updated**: 9 Maret 2026  
**Version**: 1.0  
**Status**: Ready for Testing
