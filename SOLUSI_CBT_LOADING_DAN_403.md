# Solusi CBT: Loading Lama & Error 403 Forbidden

## MASALAH 1: Jeda Lama Saat Mulai Ujian

### Root Cause:
- Fullscreen overlay menunggu user klik "MULAI UJIAN"
- Proses requestFullscreen() membutuhkan user gesture (security requirement browser)
- Tidak ada loading indicator yang jelas

### Solusi:
✅ Optimasi UX dengan loading indicator
✅ Preload resources sebelum fullscreen
✅ Instant feedback saat klik mulai

---

## MASALAH 2: Error 403 Forbidden Saat Save Answer

### Root Cause:
```php
// app/Config/Security.php
public int $expires = 7200; // 2 jam = 7200 detik
```

**Skenario:**
- Ujian dimulai pukul 08:00
- CSRF token dibuat pukul 08:00
- Token expired pukul 10:00 (2 jam kemudian)
- Jika ujian > 2 jam, semua saveAnswer request akan 403 Forbidden

### Solusi:
1. **Perpanjang CSRF token expiry** untuk ujian panjang
2. **Implement CSRF token refresh** di client-side
3. **Exclude saveAnswer dari CSRF check** (NOT RECOMMENDED)

---

## IMPLEMENTASI SOLUSI

### Solusi 1: Perpanjang CSRF Token (RECOMMENDED)
Ubah expiry menjadi 4 jam (14400 detik) untuk support ujian panjang

### Solusi 2: Auto-Refresh CSRF Token (BEST PRACTICE)
Refresh token setiap 30 menit via AJAX untuk long-running sessions

### Solusi 3: Hybrid Approach
- Perpanjang token ke 4 jam
- Implement auto-refresh setiap 1 jam
- Fallback handling jika token expired

---

## REKOMENDASI

**Untuk Production:**
1. Perpanjang CSRF expires ke 4-6 jam
2. Implement token refresh mechanism
3. Add error handling untuk token expired
4. Log semua CSRF failures untuk monitoring

**Untuk UX:**
1. Loading indicator saat mulai ujian
2. Auto-retry jika save gagal
3. Offline queue untuk answers (PWA)
4. Visual feedback untuk setiap save

---

## TESTING CHECKLIST

- [ ] Test ujian durasi 30 menit
- [ ] Test ujian durasi 2 jam
- [ ] Test ujian durasi 3+ jam
- [ ] Test save answer setelah 1 jam
- [ ] Test save answer setelah 2 jam
- [ ] Test network failure handling
- [ ] Test CSRF token refresh
- [ ] Test fullscreen loading UX
