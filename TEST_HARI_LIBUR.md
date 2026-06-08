# Testing Fitur Hari Libur di Jadwal

## 🧪 Quick Test Guide

### Persiapan

1. **Tambah Data Hari Libur untuk Testing**
   
   Akses: `http://localhost:8080/siakad/admin/holidays`
   
   Tambahkan beberapa hari libur:
   - Tanggal: 2026-04-10, Deskripsi: "Hari Raya Idul Fitri"
   - Tanggal: 2026-04-17, Deskripsi: "Cuti Bersama"
   - Tanggal: 2026-05-01, Deskripsi: "Hari Buruh Internasional"

### Test 1: Hari Libur Resmi

**Steps:**
1. Login sebagai Admin/Guru/Siswa
2. Di dashboard, pilih tanggal yang sudah ditambahkan sebagai hari libur
3. Klik tombol search/filter tanggal

**Expected Result:**
- ✅ Muncul icon kalender merah besar
- ✅ Judul "Hari Raya Idul Fitri" (atau deskripsi yang diinput)
- ✅ Tanggal dalam format Indonesia
- ✅ Pesan "Tidak ada kegiatan belajar mengajar pada hari ini"
- ✅ Tidak ada daftar jadwal (kecuali ada jadwal yang terdaftar)

### Test 2: Akhir Pekan (Sabtu)

**Steps:**
1. Login sebagai Admin/Guru/Siswa
2. Di dashboard, pilih tanggal Sabtu (contoh: 2026-04-04)
3. Klik tombol search/filter tanggal

**Expected Result:**
- ✅ Muncul icon kalender abu-abu besar
- ✅ Judul "Akhir Pekan (Sabtu)"
- ✅ Tanggal dalam format Indonesia
- ✅ Pesan "Akhir pekan - Tidak ada jadwal pelajaran"
- ✅ Tidak ada daftar jadwal

### Test 3: Akhir Pekan (Minggu)

**Steps:**
1. Login sebagai Admin/Guru/Siswa
2. Di dashboard, pilih tanggal Minggu (contoh: 2026-04-05)
3. Klik tombol search/filter tanggal

**Expected Result:**
- ✅ Muncul icon kalender abu-abu besar
- ✅ Judul "Akhir Pekan (Minggu)"
- ✅ Tanggal dalam format Indonesia
- ✅ Pesan "Akhir pekan - Tidak ada jadwal pelajaran"
- ✅ Tidak ada daftar jadwal

### Test 4: Hari Biasa (Bukan Libur)

**Steps:**
1. Login sebagai Admin/Guru/Siswa
2. Di dashboard, pilih tanggal hari kerja yang bukan libur (contoh: 2026-04-07 - Senin)
3. Klik tombol search/filter tanggal

**Expected Result:**
- ✅ Muncul daftar jadwal pelajaran normal
- ✅ Tidak ada keterangan hari libur
- ✅ Jadwal ditampilkan dengan lengkap

### Test 5: Multi-Role Testing

**Test sebagai Admin:**
1. Login sebagai Admin
2. Pilih tanggal libur
3. Verifikasi keterangan muncul

**Test sebagai Kepsek:**
1. Login sebagai Kepsek
2. Pilih tanggal libur
3. Verifikasi keterangan muncul

**Test sebagai Guru:**
1. Login sebagai Guru
2. Pilih tanggal libur
3. Verifikasi keterangan muncul di:
   - Jadwal Mengajar
   - Jadwal Kelas Walian (jika wali kelas)

**Test sebagai Siswa:**
1. Login sebagai Siswa
2. Pilih tanggal libur
3. Verifikasi keterangan muncul

**Test sebagai Orang Tua:**
1. Login sebagai Orang Tua
2. Pilih tanggal libur
3. Verifikasi keterangan muncul

### Test 6: Edge Case - Jadwal di Hari Libur

**Persiapan:**
1. Buat jadwal di tanggal yang merupakan hari libur
   (Ini untuk testing edge case, normalnya tidak boleh ada)

**Steps:**
1. Login sebagai Admin/Guru
2. Pilih tanggal libur yang ada jadwalnya
3. Klik tombol search/filter tanggal

**Expected Result:**
- ✅ Muncul keterangan hari libur
- ✅ Muncul peringatan "Meskipun hari libur, terdapat X jadwal yang terdaftar"
- ✅ Muncul tombol "Lihat Jadwal"
- ✅ Klik tombol, jadwal muncul dalam collapse

### Test 7: Date Picker Navigation

**Steps:**
1. Login sebagai Admin/Guru/Siswa
2. Gunakan date picker untuk navigasi:
   - Pilih "Hari Ini"
   - Pilih "Besok"
   - Pilih "Minggu Ini"
3. Pilih tanggal libur manual

**Expected Result:**
- ✅ Semua navigasi berfungsi
- ✅ Keterangan libur muncul sesuai tanggal yang dipilih
- ✅ URL parameter `?date=YYYY-MM-DD` ter-update

## 🐛 Common Issues & Solutions

### Issue 1: Keterangan Libur Tidak Muncul

**Possible Causes:**
- Helper tidak ter-load
- Data libur tidak ada di database
- Variable `$holidayInfo` tidak di-pass ke view

**Solutions:**
1. Cek `app/Config/Autoload.php` - pastikan `'holiday'` ada di array `$helpers`
2. Cek database `holidays` - pastikan ada data
3. Cek controller - pastikan `$holidayInfo` di-pass ke view

### Issue 2: Error "Call to undefined function is_holiday()"

**Cause:** Helper tidak ter-load

**Solution:**
1. Cek file `app/Helpers/holiday_helper.php` ada
2. Cek `app/Config/Autoload.php` - tambahkan `'holiday'` ke array `$helpers`
3. Clear cache: `php spark cache:clear`

### Issue 3: Jadwal Tetap Muncul di Hari Libur

**Cause:** View tidak mengecek `$holidayInfo`

**Solution:**
1. Cek `app/Views/dashboard/_today_schedule.php`
2. Pastikan ada pengecekan `if ($isHoliday):`
3. Pastikan variable `$holidayInfo` di-pass dari controller

### Issue 4: Format Tanggal Salah

**Cause:** Fungsi `format_holiday_date()` tidak berfungsi

**Solution:**
1. Cek helper `holiday_helper.php`
2. Pastikan fungsi `format_holiday_date()` ada
3. Pastikan helper di-load dengan `helper('holiday')`

## ✅ Checklist Testing

Gunakan checklist ini untuk memastikan semua berfungsi:

- [ ] Helper `holiday_helper.php` ter-load otomatis
- [ ] Fungsi `is_holiday()` berfungsi
- [ ] Fungsi `is_weekend()` berfungsi
- [ ] Fungsi `get_holiday_info()` berfungsi
- [ ] Fungsi `format_holiday_date()` berfungsi
- [ ] Dashboard Admin menampilkan keterangan libur
- [ ] Dashboard Kepsek menampilkan keterangan libur
- [ ] Dashboard Guru menampilkan keterangan libur
- [ ] Dashboard Siswa menampilkan keterangan libur
- [ ] Dashboard Orang Tua menampilkan keterangan libur
- [ ] Hari libur resmi terdeteksi
- [ ] Akhir pekan (Sabtu) terdeteksi
- [ ] Akhir pekan (Minggu) terdeteksi
- [ ] Hari biasa menampilkan jadwal normal
- [ ] Edge case jadwal di hari libur ter-handle
- [ ] Date picker berfungsi dengan baik
- [ ] Format tanggal Indonesia benar
- [ ] Icon dan warna sesuai
- [ ] Responsive di mobile

## 📊 Test Results Template

```
Test Date: _______________
Tester: _______________

| Test Case | Status | Notes |
|-----------|--------|-------|
| Hari Libur Resmi | ⬜ Pass ⬜ Fail | |
| Akhir Pekan (Sabtu) | ⬜ Pass ⬜ Fail | |
| Akhir Pekan (Minggu) | ⬜ Pass ⬜ Fail | |
| Hari Biasa | ⬜ Pass ⬜ Fail | |
| Admin Dashboard | ⬜ Pass ⬜ Fail | |
| Kepsek Dashboard | ⬜ Pass ⬜ Fail | |
| Guru Dashboard | ⬜ Pass ⬜ Fail | |
| Siswa Dashboard | ⬜ Pass ⬜ Fail | |
| Orang Tua Dashboard | ⬜ Pass ⬜ Fail | |
| Edge Case | ⬜ Pass ⬜ Fail | |
| Date Picker | ⬜ Pass ⬜ Fail | |

Overall Result: ⬜ Pass ⬜ Fail

Issues Found:
1. _______________
2. _______________
3. _______________
```

## 🚀 Quick SQL for Testing

Jika ingin menambah data libur via SQL langsung:

```sql
-- Tambah hari libur untuk testing
INSERT INTO holidays (date, description) VALUES
('2026-04-10', 'Hari Raya Idul Fitri'),
('2026-04-11', 'Cuti Bersama Idul Fitri'),
('2026-04-17', 'Isra Mi\'raj'),
('2026-05-01', 'Hari Buruh Internasional'),
('2026-05-16', 'Hari Raya Waisak'),
('2026-06-01', 'Hari Lahir Pancasila'),
('2026-08-17', 'Hari Kemerdekaan RI');

-- Cek data libur
SELECT * FROM holidays ORDER BY date ASC;

-- Hapus data testing (setelah selesai test)
DELETE FROM holidays WHERE date >= '2026-04-10';
```

---

**Test Guide Version:** 1.0  
**Created:** 2026-04-04  
**For Feature:** Holiday Display in Schedule
