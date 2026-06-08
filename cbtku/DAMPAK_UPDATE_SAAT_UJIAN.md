# DAMPAK UPDATE SAAT UJIAN BERLANGSUNG

**Question:** Apakah update file saat siswa sedang ujian akan berpengaruh?

**Answer:** YA, ada dampak! Tapi bisa diminimalkan.

---

## ⚠️ DAMPAK LANGSUNG

### File 1: `app/Config/Session.php`

**Dampak:** ⚠️ MEDIUM RISK

**Yang Terjadi:**
- File ini di-load saat aplikasi start
- Perubahan TIDAK langsung affect session yang sudah aktif
- Session yang sudah berjalan tetap pakai timeout lama (2 jam)
- Session BARU (login baru) akan pakai timeout baru (4 jam)

**Kesimpulan:**
- ✅ Siswa yang SUDAH ujian: TIDAK terpengaruh
- ⚠️ Siswa yang BARU login: Dapat timeout baru
- ✅ AMAN untuk di-update saat ujian

---

### File 2: `app/Controllers/Siswa/Cbt.php`

**Dampak:** ⚠️ MEDIUM-HIGH RISK

**Yang Terjadi:**
- File ini di-load setiap kali ada request (save answer, submit, dll)
- Perubahan LANGSUNG affect request berikutnya
- Jika ada syntax error → semua siswa error saat save jawaban
- Jika ada logic error → bisa gagal save

**Kesimpulan:**
- ⚠️ Siswa yang sedang ujian: TERPENGARUH di request berikutnya
- ❌ RISIKO jika ada error
- ⚠️ HATI-HATI update saat ujian

---

### File 3: `app/Views/siswa/cbt/ujian.php`

**Dampak:** ✅ LOW RISK

**Yang Terjadi:**
- File ini di-load saat halaman ujian dibuka
- Siswa yang SUDAH buka halaman ujian: TIDAK terpengaruh
- Siswa yang BARU buka halaman: Dapat versi baru
- Browser cache halaman lama

**Kesimpulan:**
- ✅ Siswa yang sudah buka halaman: TIDAK terpengaruh
- ⚠️ Siswa yang refresh/reload: Dapat versi baru
- ✅ RELATIF AMAN

---

## 🎯 REKOMENDASI

### OPTION 1: TUNGGU UJIAN SELESAI (RECOMMENDED) ✅

**Kapan:**
- Tunggu semua siswa selesai ujian
- Update di luar jam ujian
- Paling aman, zero risk

**Pros:**
- ✅ Zero risk untuk siswa
- ✅ Bisa test dengan tenang
- ✅ Rollback mudah jika ada masalah

**Cons:**
- ⏳ Harus tunggu ujian selesai
- ⏳ Masalah "gagal menyimpan" masih terjadi

**Timeline:**
```
Sekarang: Siswa ujian (jangan update)
    ↓
Ujian selesai: Update files
    ↓
Test: 5-10 menit
    ↓
Ujian berikutnya: Sudah pakai versi baru
```

---

### OPTION 2: UPDATE HANYA Session.php (SAFE) ✅

**Kapan:**
- Jika hanya ingin extend session timeout
- Tidak mengubah logic

**Steps:**
```bash
# 1. Backup
cp app/Config/Session.php app/Config/Session.php.backup

# 2. Update Session.php
# Edit: public int $expiration = 14400;

# 3. Clear cache (optional)
rm -rf writable/cache/*
```

**Dampak:**
- ✅ Siswa yang sedang ujian: TIDAK terpengaruh
- ✅ Session baru: Dapat timeout 4 jam
- ✅ AMAN untuk di-update sekarang

**Pros:**
- ✅ Zero risk untuk siswa aktif
- ✅ Ujian berikutnya langsung benefit
- ✅ Bisa dilakukan sekarang

**Cons:**
- ⏳ Siswa yang sedang ujian masih pakai timeout lama
- ⏳ Logging belum ada

---

### OPTION 3: UPDATE BERTAHAP (MEDIUM RISK) ⚠️

**Kapan:**
- Jika masalah "gagal menyimpan" sangat urgent
- Sudah yakin code benar

**Steps:**
```bash
# STEP 1: Backup semua
cp app/Config/Session.php app/Config/Session.php.backup
cp app/Controllers/Siswa/Cbt.php app/Controllers/Siswa/Cbt.php.backup
cp app/Views/siswa/cbt/ujian.php app/Views/siswa/cbt/ujian.php.backup

# STEP 2: Update Session.php dulu (SAFE)
# Edit Session.php
rm -rf writable/cache/*

# STEP 3: Tunggu 5 menit, monitor

# STEP 4: Update Cbt.php (CAREFUL!)
# Edit Cbt.php
rm -rf writable/cache/*

# STEP 5: Monitor logs real-time
tail -f writable/logs/log-$(date +%Y-%m-%d).php

# STEP 6: Update ujian.php (SAFE)
# Siswa yang sudah buka tidak terpengaruh
```

**Pros:**
- ✅ Fix langsung apply
- ✅ Logging langsung ada
- ✅ Bisa rollback per file

**Cons:**
- ⚠️ Risk untuk siswa aktif
- ⚠️ Harus monitor ketat
- ⚠️ Jika error, bisa chaos

---

### OPTION 4: UPDATE EMERGENCY (HIGH RISK) ❌

**Kapan:**
- HANYA jika sistem sudah down
- Banyak siswa gagal save
- Emergency fix

**NOT RECOMMENDED saat ujian normal!**

---

## 📊 RISK MATRIX

| File | Update Saat Ujian | Risk Level | Dampak ke Siswa Aktif |
|------|-------------------|------------|----------------------|
| Session.php | ✅ SAFE | LOW | Tidak terpengaruh |
| Cbt.php | ⚠️ CAREFUL | MEDIUM-HIGH | Terpengaruh di request berikutnya |
| ujian.php | ✅ SAFE | LOW | Tidak terpengaruh (sudah load) |

---

## 🔍 MONITORING JIKA UPDATE SAAT UJIAN

### Terminal 1: Application Logs
```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).php | grep -E "ERROR|CRITICAL|saveAnswersBulk"
```

### Terminal 2: PHP Error Logs
```bash
tail -f /var/log/php-fpm/error.log
```

### Terminal 3: Active Sessions
```bash
watch -n 5 'ls -la writable/session/ | wc -l'
```

### Browser: Admin Panel
- Monitor jumlah siswa aktif
- Check error reports
- Ready untuk rollback

---

## 🚨 ROLLBACK PLAN (Jika Ada Masalah)

### Quick Rollback (30 detik)
```bash
# Restore backups
cp app/Config/Session.php.backup app/Config/Session.php
cp app/Controllers/Siswa/Cbt.php.backup app/Controllers/Siswa/Cbt.php
cp app/Views/siswa/cbt/ujian.php.backup app/Views/siswa/cbt/ujian.php

# Clear cache
rm -rf writable/cache/*

# Restart PHP-FPM (if needed)
sudo systemctl restart php-fpm
```

### Verify Rollback
```bash
# Check files restored
diff app/Config/Session.php app/Config/Session.php.backup
# Should show no difference

# Monitor logs
tail -f writable/logs/log-$(date +%Y-%m-%d).php
```

---

## 💡 BEST PRACTICE

### DO ✅
1. **Backup files** sebelum update
2. **Test di localhost** dulu
3. **Update di luar jam ujian** (paling aman)
4. **Monitor logs** real-time jika update saat ujian
5. **Siapkan rollback plan**
6. **Inform pengawas** sebelum update

### DON'T ❌
1. **Jangan update** tanpa backup
2. **Jangan update** tanpa test
3. **Jangan update** saat peak time ujian
4. **Jangan update** semua file sekaligus
5. **Jangan update** jika tidak yakin
6. **Jangan panic** jika ada error (rollback!)

---

## 🎯 REKOMENDASI FINAL

### Situasi Sekarang: Siswa Sedang Ujian

**RECOMMENDED APPROACH:**

**OPTION A: TUNGGU UJIAN SELESAI** (SAFEST) ✅
```
Timeline:
- Sekarang: Jangan update, monitor saja
- Ujian selesai: Update semua files
- Test: 5-10 menit
- Ujian berikutnya: Sudah fix
```

**OPTION B: UPDATE SESSION.PHP SAJA** (SAFE) ✅
```
Steps:
1. Backup Session.php
2. Update expiration: 7200 → 14400
3. Clear cache
4. Done!

Impact:
- Siswa aktif: Tidak terpengaruh
- Ujian baru: Dapat timeout 4 jam
- Risk: ZERO
```

**OPTION C: UPDATE SEMUA (JIKA URGENT)** ⚠️
```
Only if:
- Banyak siswa gagal save (>10%)
- Masalah critical
- Sudah test di localhost
- Siap monitor & rollback

Steps:
1. Backup semua
2. Update Session.php → Monitor 5 menit
3. Update Cbt.php → Monitor 10 menit
4. Update ujian.php → Monitor
5. Ready rollback jika ada masalah
```

---

## 📞 DECISION TREE

```
Siswa sedang ujian?
    ├─ Ya
    │   ├─ Masalah critical? (>10% gagal save)
    │   │   ├─ Ya → OPTION C (Update semua, monitor ketat)
    │   │   └─ No → OPTION B (Update Session.php saja)
    │   │
    │   └─ Bisa tunggu ujian selesai?
    │       ├─ Ya → OPTION A (RECOMMENDED)
    │       └─ No → OPTION B (Update Session.php saja)
    │
    └─ Tidak (di luar jam ujian)
        └─ Update semua files dengan tenang ✅
```

---

## 📝 KESIMPULAN

### Jawaban Singkat:

**Q: Apakah update saat ujian akan berpengaruh?**

**A: YA, tapi tergantung file:**

1. **Session.php** → ✅ AMAN (siswa aktif tidak terpengaruh)
2. **Cbt.php** → ⚠️ RISIKO (affect request berikutnya)
3. **ujian.php** → ✅ AMAN (siswa yang sudah load tidak terpengaruh)

### Rekomendasi:

**Jika tidak urgent:**
- ✅ Tunggu ujian selesai
- ✅ Update di luar jam ujian
- ✅ Zero risk

**Jika agak urgent:**
- ✅ Update Session.php saja sekarang (AMAN)
- ⏳ Update sisanya setelah ujian

**Jika sangat urgent:**
- ⚠️ Update semua dengan monitoring ketat
- ⚠️ Siap rollback
- ⚠️ Inform pengawas

---

**Last Updated:** 2026-03-09  
**Status:** ⚠️ DECISION REQUIRED

**RECOMMENDATION:** Tunggu ujian selesai untuk update yang paling aman!

