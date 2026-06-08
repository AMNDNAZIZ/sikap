# Perbaikan Penandaan Hari Sabtu di Absensi

## Masalah
Penandaan hari Sabtu sebagai hari libur tidak menyesuaikan dengan pengaturan `school_days` (5 atau 6 hari) yang ada di tahun ajaran aktif.

### Perilaku yang Salah
- Kode menggunakan hardcoded `$dow >= 6` yang selalu menganggap Sabtu dan Minggu sebagai libur
- Tidak mempertimbangkan pengaturan `school_days` dari tahun ajaran

### Perilaku yang Benar
- Jika `school_days = 6`: Hanya Minggu yang libur (Sabtu adalah hari belajar)
- Jika `school_days = 5`: Sabtu dan Minggu adalah hari libur

## Solusi
Menggunakan helper function `is_weekend($date, $schoolDays)` yang sudah tersedia di `app/Helpers/holiday_helper.php` untuk semua pengecekan weekend.

## File yang Diperbaiki

### 1. Controller: `app/Controllers/Admin/Attendance.php`

#### Method `view()` ✅
- Sudah menggunakan `is_weekend()` helper
- Sudah mengirim `$schoolDays` ke view

#### Method `excel()` ✅
- Sudah menggunakan `is_weekend()` helper
- Sudah mengambil `$schoolDays` dari tahun ajaran aktif

#### Method `rekap()` ✅
- Sudah menggunakan `is_weekend()` helper
- Sudah mengambil `$schoolDays` dari tahun ajaran aktif

#### Method `pdf()` ✅ (BARU DIPERBAIKI)
- **Sebelum**: Tidak mengirim `$schoolDays` ke view
- **Sesudah**: Mengambil `$schoolDays` dari tahun ajaran dan mengirimnya ke view

```php
// Ambil pengaturan hari pembelajaran
$activeYear = $this->yearModel->getActiveYear() ?: [];
$schoolDays = $activeYear['school_days'] ?? 5;

$data = [
    'class' => $class,
    'month' => $month,
    'students' => $students,
    'dates' => $dates,
    'attMap' => $attMap,
    'holidays' => $holidays,
    'schoolDays' => $schoolDays,  // ← DITAMBAHKAN
];
```

#### Method `generateRekap()` ✅ (BARU DIPERBAIKI)
- **Sebelum**: Menggunakan hardcoded `$dow >= 6`
- **Sesudah**: Menggunakan `is_weekend()` helper

```php
// Ambil pengaturan hari pembelajaran
$schoolDays = $activeYear['school_days'] ?? 5;

foreach ($students as $s) {
    $H = $I = $S = $A = 0;

    foreach ($dates as $d) {
        // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
        $isWeekend = is_weekend($d, $schoolDays);
        $isHoliday = $isWeekend || isset($holidays[$d]);
        if ($isHoliday || $d > $today)
            continue;
        
        // ... rest of code
    }
}
```

### 2. Views

#### `app/Views/admin/attendance/month_grid.php` ✅
- Sudah menggunakan `is_weekend()` helper

#### `app/Views/admin/attendance/week_grid.php` ✅
- Sudah menggunakan `is_weekend()` helper

#### `app/Views/admin/attendance/pdf_grid.php` ✅ (BARU DIPERBAIKI)
- **Sebelum**: Menggunakan hardcoded `$dow >= 6`
- **Sesudah**: Menggunakan `is_weekend()` helper

```php
<?php
  $holidayCols = [];
  $totalWorkDays = 0;
  $today = date('Y-m-d');
  foreach ($dates as $idx => $d) {
      // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
      $isWeekend = is_weekend($d, $schoolDays);
      $isHoliday = $isWeekend || isset($holidays[$d]);
      $holidayCols[$idx] = $isHoliday;
      if (!$isHoliday && $d <= $today) {
          $totalWorkDays++;
      }
  }
?>
```

#### `app/Views/admin/attendance/rekap_pdf_template.php` ✅
- Tidak perlu diperbaiki karena hanya menampilkan hasil rekap yang sudah dihitung di controller
- Data rekap sudah menggunakan `is_weekend()` helper melalui method `generateRekap()`

## Helper Function Reference

File: `app/Helpers/holiday_helper.php`

```php
/**
 * Check if a given date is weekend based on school_days setting
 * 
 * @param string $date Date in Y-m-d format
 * @param int|null $schoolDays Number of school days (5 or 6)
 * @return bool
 */
function is_weekend(string $date, ?int $schoolDays = null): bool
{
    $dayOfWeek = date('N', strtotime($date));
    
    // If 5 days school: Saturday (6) and Sunday (7) are weekends
    // If 6 days school: Only Sunday (7) is weekend
    if ($schoolDays == 6) {
        return ($dayOfWeek == 7); // Only Sunday
    } else {
        return ($dayOfWeek >= 6); // Saturday and Sunday
    }
}
```

## Testing Checklist

### Skenario 1: School Days = 6 (Sabtu Masuk)
- [ ] Tampilan bulanan: Sabtu tidak ditandai merah, Minggu ditandai merah
- [ ] Tampilan mingguan: Sabtu tidak ditandai merah, Minggu ditandai merah
- [ ] Export PDF: Sabtu tidak ditandai merah, Minggu ditandai merah
- [ ] Export Excel: Sabtu dihitung sebagai hari kerja
- [ ] Rekap: Sabtu dihitung dalam total hari kerja

### Skenario 2: School Days = 5 (Sabtu Libur)
- [ ] Tampilan bulanan: Sabtu dan Minggu ditandai merah
- [ ] Tampilan mingguan: Sabtu dan Minggu ditandai merah
- [ ] Export PDF: Sabtu dan Minggu ditandai merah
- [ ] Export Excel: Sabtu tidak dihitung sebagai hari kerja
- [ ] Rekap: Sabtu tidak dihitung dalam total hari kerja

## Cara Testing

1. Buka menu **Admin > Tahun Ajaran**
2. Edit tahun ajaran aktif
3. Ubah pengaturan **Hari Pembelajaran** antara 5 dan 6 hari
4. Buka menu **Admin > Absensi**
5. Pilih kelas dan bulan yang mengandung hari Sabtu
6. Test semua fitur:
   - Tampilan bulanan
   - Tampilan mingguan
   - Export PDF
   - Export Excel
   - Rekap absensi (PDF dan Excel)
7. Verifikasi bahwa hari Sabtu ditandai sesuai dengan pengaturan `school_days`

## Status
✅ **SELESAI** - Semua file sudah diperbaiki dan menggunakan helper `is_weekend()` dengan benar.
