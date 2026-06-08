# Fitur Tampilan Hari Libur di Jadwal Pelajaran

## 📌 Overview

Fitur ini menampilkan keterangan khusus di dashboard ketika tanggal yang dipilih adalah hari libur (berdasarkan data di tabel `holidays`) atau akhir pekan (Sabtu/Minggu), menggantikan tampilan jadwal pelajaran biasa dengan informasi yang lebih informatif.

## ✨ Fitur Utama

### 1. Deteksi Hari Libur Otomatis
- Mengecek database `holidays` untuk hari libur resmi
- Mendeteksi akhir pekan (Sabtu & Minggu)
- Menampilkan keterangan yang sesuai

### 2. Tampilan Khusus Hari Libur
- Icon besar yang menarik perhatian
- Judul dengan warna yang sesuai (merah untuk libur, abu-abu untuk weekend)
- Tanggal dalam format Indonesia yang mudah dibaca
- Pesan informatif tentang tidak ada kegiatan belajar

### 3. Handling Jadwal di Hari Libur
- Jika ada jadwal yang terdaftar di hari libur (edge case), tetap bisa dilihat
- Jadwal disembunyikan dalam collapse yang bisa dibuka
- Peringatan bahwa meskipun libur, ada jadwal terdaftar

## 🏗️ Arsitektur

### 1. Helper Functions (`app/Helpers/holiday_helper.php`)

**Fungsi Utama:**

```php
// Cek apakah tanggal adalah hari libur
is_holiday(string $date): array|false

// Cek apakah tanggal adalah akhir pekan
is_weekend(string $date): bool

// Dapatkan informasi lengkap hari libur
get_holiday_info(string $date): array

// Format tanggal untuk tampilan
format_holiday_date(string $date): string
```

**Return Format `get_holiday_info()`:**
```php
[
    'is_holiday' => true/false,
    'type' => 'holiday'|'weekend'|'regular',
    'description' => 'Deskripsi libur',
    'icon' => 'bi-calendar-x',
    'color' => 'danger'|'secondary'
]
```

### 2. Controller (`app/Controllers/Dashboard.php`)

**Perubahan:**
- Load helper `holiday` di awal method `index()`
- Panggil `get_holiday_info($rekapDate)` untuk setiap request
- Pass `$holidayInfo` ke semua view dashboard

**Affected Methods:**
- `index()` - Main dashboard method
- `getStudentDashboardData()` - Student/parent dashboard data

### 3. View (`app/Views/dashboard/_today_schedule.php`)

**Perubahan:**
- Cek `$holidayInfo['is_holiday']` sebelum render jadwal
- Tampilkan holiday notice jika hari libur
- Sembunyikan jadwal dalam collapse jika ada jadwal di hari libur
- Tetap tampilkan jadwal normal jika bukan hari libur

## 🎨 Tampilan UI

### Hari Libur Resmi
```
┌─────────────────────────────────────┐
│   🗓️ (Icon Merah Besar)             │
│                                     │
│   Hari Raya Idul Fitri              │
│   Senin, 10 April 2026              │
│                                     │
│   ⚠️ Tidak ada kegiatan belajar     │
│      mengajar pada hari ini.        │
│                                     │
│   [Lihat Jadwal] (jika ada)         │
└─────────────────────────────────────┘
```

### Akhir Pekan
```
┌─────────────────────────────────────┐
│   📅 (Icon Abu-abu Besar)           │
│                                     │
│   Akhir Pekan (Sabtu)               │
│   Sabtu, 05 April 2026              │
│                                     │
│   ⚠️ Akhir pekan - Tidak ada        │
│      jadwal pelajaran.              │
└─────────────────────────────────────┘
```

### Hari Biasa (Tidak Libur)
```
┌─────────────────────────────────────┐
│   Jadwal Pelajaran                  │
│   Senin, 07 April 2026              │
│                                     │
│   1. Matematika (07:00 - 08:30)     │
│   2. Bahasa Indonesia (08:30 - 10:00)│
│   ...                               │
└─────────────────────────────────────┘
```

## 📊 Data Flow

```
User memilih tanggal
    ↓
Dashboard Controller
    ↓
Load holiday_helper
    ↓
get_holiday_info($date)
    ↓
Check database holidays
    ↓
Check if weekend
    ↓
Return holiday info
    ↓
Pass to View
    ↓
View checks $holidayInfo['is_holiday']
    ↓
If TRUE: Show holiday notice
If FALSE: Show schedule as usual
```

## 🎯 Affected Dashboards

Fitur ini aktif di semua dashboard:

1. **Admin Dashboard** (`dashboard/admin_modern.php`)
   - Menampilkan keterangan libur untuk semua kelas
   
2. **Kepala Sekolah Dashboard** (`dashboard/kepsek.php`)
   - Menampilkan keterangan libur untuk semua kelas
   
3. **Guru Dashboard** (`dashboard/guru_fixed.php`)
   - Menampilkan keterangan libur untuk jadwal mengajar
   - Menampilkan keterangan libur untuk jadwal kelas walian
   
4. **Siswa Dashboard** (`dashboard/siswa_simple.php`)
   - Menampilkan keterangan libur untuk jadwal kelas
   
5. **Orang Tua Dashboard** (`dashboard/siswa_simple.php`)
   - Menampilkan keterangan libur untuk jadwal kelas anak

## 🔧 Konfigurasi

### Menambah Hari Libur

1. Login sebagai Admin
2. Buka menu **Admin > Hari Libur** (`/admin/holidays`)
3. Klik "Tambah Hari Libur"
4. Isi form:
   - **Tanggal:** Pilih tanggal libur
   - **Deskripsi:** Nama hari libur (contoh: "Hari Raya Idul Fitri")
5. Simpan

### Database Structure

Tabel: `holidays`
```sql
CREATE TABLE `holidays` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
);
```

## 💡 Use Cases

### Use Case 1: Hari Libur Nasional
**Scenario:** Admin menambahkan Hari Kemerdekaan (17 Agustus)
**Result:** Semua user melihat keterangan "Hari Kemerdekaan RI" saat memilih tanggal 17 Agustus

### Use Case 2: Akhir Pekan
**Scenario:** User memilih tanggal Sabtu atau Minggu
**Result:** Otomatis menampilkan "Akhir Pekan" tanpa perlu input manual

### Use Case 3: Jadwal di Hari Libur (Edge Case)
**Scenario:** Ada jadwal yang terdaftar di hari libur (mungkin kesalahan input)
**Result:** 
- Menampilkan keterangan hari libur
- Menampilkan peringatan ada jadwal terdaftar
- Jadwal bisa dilihat dengan klik tombol "Lihat Jadwal"

### Use Case 4: Guru Melihat Jadwal Besok
**Scenario:** Guru memilih tanggal besok yang kebetulan libur
**Result:** Melihat keterangan libur, tidak perlu bingung kenapa tidak ada jadwal

## 🎨 Styling

### Color Scheme

| Type | Color | Bootstrap Class | Icon |
|------|-------|----------------|------|
| Hari Libur Resmi | Merah | `text-danger` | `bi-calendar-x` |
| Akhir Pekan | Abu-abu | `text-secondary` | `bi-calendar2-week` |
| Hari Biasa | Biru | `text-primary` | `bi-clock-history` |

### Icons (Bootstrap Icons)

- `bi-calendar-x` - Hari libur resmi
- `bi-calendar2-week` - Akhir pekan
- `bi-info-circle` - Informasi
- `bi-exclamation-triangle` - Peringatan (jadwal di hari libur)

## 🔍 Testing

### Test Scenarios

1. **Test Hari Libur Resmi**
   - Tambah hari libur di database
   - Pilih tanggal tersebut di dashboard
   - Verifikasi keterangan muncul

2. **Test Akhir Pekan**
   - Pilih tanggal Sabtu
   - Verifikasi keterangan "Akhir Pekan (Sabtu)" muncul
   - Pilih tanggal Minggu
   - Verifikasi keterangan "Akhir Pekan (Minggu)" muncul

3. **Test Hari Biasa**
   - Pilih tanggal Senin-Jumat yang bukan libur
   - Verifikasi jadwal normal muncul

4. **Test Jadwal di Hari Libur**
   - Buat jadwal di hari libur (untuk testing)
   - Pilih tanggal tersebut
   - Verifikasi keterangan libur muncul
   - Verifikasi tombol "Lihat Jadwal" muncul
   - Klik tombol, verifikasi jadwal muncul

5. **Test Multi-Role**
   - Test sebagai Admin
   - Test sebagai Kepsek
   - Test sebagai Guru
   - Test sebagai Siswa
   - Test sebagai Orang Tua

## 📈 Performance

### Caching
- Helper `is_holiday()` menggunakan static cache
- Satu query per tanggal per request
- Minimal impact pada performa

### Database Query
```sql
SELECT * FROM holidays WHERE date = '2026-04-10' LIMIT 1;
```
- Simple query dengan index pada kolom `date`
- Execution time: < 1ms

## 🔄 Future Enhancements

Fitur yang bisa ditambahkan di masa depan:

1. **Bulk Import Hari Libur**
   - Import dari file Excel/CSV
   - Import kalender nasional otomatis

2. **Kategori Hari Libur**
   - Libur Nasional
   - Libur Keagamaan
   - Libur Sekolah
   - Cuti Bersama

3. **Notifikasi Hari Libur**
   - Email reminder sebelum hari libur
   - Push notification

4. **Recurring Holidays**
   - Hari libur yang berulang setiap tahun
   - Hari libur yang berulang setiap minggu

5. **Custom Holiday per Kelas**
   - Kelas tertentu libur, kelas lain tidak
   - Untuk kegiatan khusus

## 📞 Support

Jika ada masalah:
1. Cek tabel `holidays` di database
2. Cek helper `holiday_helper.php` ter-load
3. Cek log error di `writable/logs/`
4. Verifikasi `$holidayInfo` di-pass ke view

---

**Created:** 2026-04-04  
**Feature:** Holiday Display in Schedule  
**Version:** 1.0
