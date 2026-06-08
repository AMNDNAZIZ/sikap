# Perbaikan Tampilan Hari Libur di Dashboard

## 🐛 Masalah

Keterangan hari libur tidak muncul di dashboard meskipun sudah diimplementasikan. Jadwal tetap ditampilkan seperti biasa.

## 🔍 Root Cause

Variable `$holidayInfo` tidak di-pass ke partial view `_today_schedule.php` dan view dashboard yang tidak menggunakan partial (admin_modern.php, kepsek.php).

### Detail Masalah:

1. **Partial View `_today_schedule.php`**
   - Dipanggil dengan `view()` helper
   - Hanya menerima data yang di-pass secara eksplisit
   - `$holidayInfo` tidak otomatis ter-pass dari parent view

2. **Dashboard Admin & Kepsek**
   - Tidak menggunakan partial `_today_schedule.php`
   - Memiliki kode schedule sendiri
   - Tidak ada logika pengecekan holiday

## ✅ Solusi

### 1. Pass `$holidayInfo` ke Partial View

**File yang Dimodifikasi:**

**a. `app/Views/dashboard/guru_fixed.php`**
```php
// Jadwal Mengajar
<?= view('dashboard/_today_schedule', [
    'schedules' => $personalSchedule, 
    'scheduleTitle' => 'Jadwal Mengajar Saya', 
    'rekapDate' => $rekapDate,
    'scheduleType' => 'teaching',
    'hideTeacher' => true,
    'holidayInfo' => $holidayInfo ?? null  // ← ADDED
]) ?>

// Jadwal Kelas Walian
<?= view('dashboard/_today_schedule', [
    'schedules' => $waliClassSchedule, 
    'scheduleTitle' => 'Jadwal Kelas ' . esc($waliClass['name']), 
    'rekapDate' => $rekapDate,
    'scheduleType' => 'homeroom',
    'hideClass' => true,
    'holidayInfo' => $holidayInfo ?? null  // ← ADDED
]) ?>
```

**b. `app/Views/dashboard/siswa_simple.php`**
```php
<?= view('dashboard/_today_schedule', [
    'schedules' => $todaySchedule, 
    'scheduleTitle' => 'Jadwal Pelajaran Hari Ini', 
    'rekapDate' => $rekapDate,
    'holidayInfo' => $holidayInfo ?? null  // ← ADDED
]) ?>
```

### 2. Tambah Logika Holiday di Dashboard Admin & Kepsek

**a. `app/Views/dashboard/admin_modern.php`**

Tambahkan sebelum render schedule:
```php
<div class="card-body p-0">
  <?php 
  // Check if it's a holiday
  $isHoliday = isset($holidayInfo) && $holidayInfo['is_holiday'];
  ?>
  
  <?php if ($isHoliday): ?>
    <!-- Holiday Notice -->
    <div class="p-5 text-center">
      <div class="mb-4">
        <i class="<?= $holidayInfo['icon'] ?> text-<?= $holidayInfo['color'] ?>" 
           style="font-size: 4rem; opacity: 0.3;"></i>
      </div>
      <h4 class="fw-bold text-<?= $holidayInfo['color'] ?> mb-3">
        <?= esc($holidayInfo['description']) ?>
      </h4>
      <p class="text-muted mb-4">
        <?php 
        helper('holiday');
        echo format_holiday_date($rekapDate ?? date('Y-m-d')); 
        ?>
      </p>
      <div class="alert alert-<?= $holidayInfo['color'] ?> alert-dismissible fade show d-inline-block">
        <i class="bi bi-info-circle me-2"></i>
        <?php if ($holidayInfo['type'] == 'holiday'): ?>
          <strong>Tidak ada kegiatan belajar mengajar</strong> pada hari ini.
        <?php else: ?>
          <strong>Akhir pekan</strong> - Tidak ada jadwal pelajaran.
        <?php endif; ?>
      </div>
      
      <?php if (!empty($todaySchedule)): ?>
        <div class="mt-4 pt-4 border-top">
          <p class="text-muted mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Meskipun hari libur, terdapat <?= count($todaySchedule) ?> jadwal yang terdaftar di sistem.
          </p>
          <button class="btn btn-sm btn-outline-secondary" type="button" 
                  data-bs-toggle="collapse" data-bs-target="#holidayScheduleCollapse">
            <i class="bi bi-eye me-1"></i> Lihat Jadwal
          </button>
        </div>
      <?php endif; ?>
    </div>
    
    <?php if (!empty($todaySchedule)): ?>
      <!-- Collapsible schedule list -->
      <div class="collapse" id="holidayScheduleCollapse">
        <div class="border-top">
    <?php endif; ?>
  <?php elseif (empty($todaySchedule)): ?>
    <!-- Empty schedule message -->
  <?php else: ?>
    <!-- Normal schedule display -->
  <?php endif; ?>
```

Tutup collapse setelah summary footer:
```php
<!-- Summary Footer -->
<div class="border-top p-3 bg-light bg-opacity-10">
  ...
</div>

<?php if ($isHoliday && !empty($todaySchedule)): ?>
      </div>
    </div>
<?php endif; ?>
```

**b. `app/Views/dashboard/kepsek.php`**

Sama seperti admin_modern.php, tambahkan logika holiday yang identik.

## 📊 Struktur Kondisional

```
IF holiday:
    Show holiday notice
    IF has schedules:
        Show warning
        Show "Lihat Jadwal" button
        <collapse>
            Show schedules
        </collapse>
ELSE IF empty schedules:
    Show "Tidak ada jadwal" message
ELSE:
    Show normal schedules
```

## 🎯 Affected Files

1. ✅ `app/Views/dashboard/guru_fixed.php` - Pass $holidayInfo
2. ✅ `app/Views/dashboard/siswa_simple.php` - Pass $holidayInfo
3. ✅ `app/Views/dashboard/admin_modern.php` - Add holiday logic
4. ✅ `app/Views/dashboard/kepsek.php` - Add holiday logic
5. ✅ `app/Views/dashboard/_today_schedule.php` - Already has holiday logic (no change needed)

## 🧪 Testing

### Test 1: Hari Libur Resmi
1. Tambah hari libur di database
2. Login sebagai Admin/Guru/Siswa
3. Pilih tanggal libur di dashboard
4. **Expected:** Muncul keterangan libur, tidak ada jadwal

### Test 2: Akhir Pekan
1. Login sebagai Admin/Guru/Siswa
2. Pilih tanggal Sabtu atau Minggu
3. **Expected:** Muncul keterangan "Akhir Pekan", tidak ada jadwal

### Test 3: Hari Biasa
1. Login sebagai Admin/Guru/Siswa
2. Pilih tanggal hari kerja (bukan libur)
3. **Expected:** Jadwal normal muncul

### Test 4: Multi-Dashboard
- ✅ Admin Dashboard
- ✅ Kepsek Dashboard
- ✅ Guru Dashboard (2 jadwal: mengajar & walian)
- ✅ Siswa Dashboard
- ✅ Orang Tua Dashboard

## 🔍 Debugging Tips

Jika masih tidak muncul:

1. **Cek Variable di Controller**
   ```php
   // Di Dashboard.php
   dd($holidayInfo); // Sebelum return view
   ```

2. **Cek Variable di View**
   ```php
   // Di dashboard view
   <?php var_dump($holidayInfo); ?>
   ```

3. **Cek Helper Loaded**
   ```php
   // Di view
   <?php 
   helper('holiday');
   var_dump(function_exists('is_holiday')); // Should be true
   ?>
   ```

4. **Cek Database**
   ```sql
   SELECT * FROM holidays WHERE date = '2026-04-05';
   ```

## ✅ Verification Checklist

- [x] Helper `holiday_helper.php` created
- [x] Helper added to autoload
- [x] Controller passes `$holidayInfo` to all views
- [x] Guru dashboard passes `$holidayInfo` to partial
- [x] Siswa dashboard passes `$holidayInfo` to partial
- [x] Admin dashboard has holiday logic
- [x] Kepsek dashboard has holiday logic
- [x] Partial `_today_schedule.php` has holiday logic
- [x] No syntax errors
- [x] Ready for testing

## 📝 Notes

- Variable `$holidayInfo` menggunakan null coalescing (`?? null`) untuk backward compatibility
- Jika `$holidayInfo` tidak ada, view akan render normal tanpa error
- Collapse menggunakan Bootstrap 5 collapse component
- Icon dan warna dinamis berdasarkan tipe holiday

---

**Fixed:** 2026-04-04  
**Issue:** Holiday info not displaying  
**Solution:** Pass $holidayInfo to all views and add holiday logic to admin/kepsek dashboards
