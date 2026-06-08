# Fitur Pencarian Tanggal di Dashboard Siswa

## Deskripsi
Menambahkan fitur pencarian tanggal pada dashboard siswa dan orang tua, mirip dengan fitur yang sudah ada di dashboard admin dan guru. Fitur ini memungkinkan siswa/orang tua untuk melihat jadwal pelajaran pada tanggal tertentu, dengan keterangan khusus jika tanggal tersebut adalah hari libur atau akhir pekan.

## Perubahan yang Dilakukan

### 1. View Dashboard Siswa (`app/Views/dashboard/siswa_simple.php`)

#### Penambahan Section Date Picker
Ditambahkan section baru sebelum jadwal pelajaran yang berisi:
- **Form pencarian tanggal** dengan input type date
- **Tombol "Hari Ini"** untuk kembali ke tanggal hari ini (muncul jika sedang melihat tanggal lain)
- **Badge tanggal** yang menampilkan tanggal terpilih dalam format Indonesia

```php
<!-- Date Picker Section -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <!-- Date Picker Form -->
      <form action="" method="get" class="flex-grow-1" style="max-width: 400px;">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-calendar3 text-primary"></i>
          </span>
          <input type="date" name="date" class="form-control border-start-0 ps-0" 
                 value="<?= $rekapDate ?? date('Y-m-d') ?>" 
                 onchange="this.form.submit()"
                 max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-search"></i> Lihat
          </button>
        </div>
      </form>
      
      <!-- Date Badge & Reset Button -->
      <div class="d-flex align-items-center gap-2">
        <?php if (($rekapDate ?? date('Y-m-d')) != date('Y-m-d')): ?>
          <a href="<?= site_url('dashboard') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i> Hari Ini
          </a>
        <?php endif; ?>
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 fs-6 fw-normal">
          <!-- Format: Senin, 05 Jan 2026 -->
        </span>
      </div>
    </div>
  </div>
</div>
```

#### Penambahan Holiday Notice
Ditambahkan alert khusus yang muncul jika tanggal yang dipilih adalah hari libur atau akhir pekan:

```php
<!-- Holiday Notice -->
<?php if (!empty($holidayInfo) && $holidayInfo['is_holiday']): ?>
  <div class="alert alert-<?= $holidayInfo['color'] ?> border-0 shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center">
      <div class="flex-shrink-0">
        <i class="bi <?= $holidayInfo['icon'] ?> fs-2"></i>
      </div>
      <div class="flex-grow-1 ms-3">
        <h5 class="alert-heading mb-1 fw-bold">
          <?= $holidayInfo['type'] == 'holiday' ? 'Hari Libur' : 'Akhir Pekan' ?>
        </h5>
        <p class="mb-0"><?= esc($holidayInfo['description']) ?></p>
        <small class="opacity-75">Tidak ada jadwal pelajaran pada hari ini.</small>
      </div>
    </div>
  </div>
<?php endif; ?>
```

#### Update Judul Jadwal
Judul jadwal sekarang dinamis berdasarkan tanggal yang dipilih:
- "Jadwal Pelajaran Hari Ini" - jika tanggal = hari ini
- "Jadwal Pelajaran" - jika tanggal lain

#### Update Empty State
Empty state (tidak ada jadwal) sekarang tidak ditampilkan jika tanggal adalah hari libur, karena sudah ada holiday notice.

### 2. Controller Dashboard (`app/Controllers/Dashboard.php`)

Controller sudah mendukung fitur ini dengan baik:

#### Parameter Date dari Request
```php
// Date for rekap (defaults to today)
$rekapDate = $this->request->getGet('date') ?? $today;

// Check if selected date is a holiday
$holidayInfo = get_holiday_info($rekapDate);
```

#### Pass ke Method getStudentDashboardData
```php
case 4: // Orang Tua
    $data = $this->getStudentDashboardData($user, $db, $today, $rekapDate);
    return view('dashboard/siswa_simple', $data);

case 5: // Siswa
    $data = $this->getStudentDashboardData($user, $db, $today, $rekapDate);
    return view('dashboard/siswa_simple', $data);
```

#### Return Data ke View
```php
return [
    'user' => $user,
    'student' => $student,
    'className' => $className,
    'upcomingAgendas' => $upcomingAgendas,
    'attSummary' => $attSummary,
    'attendanceRate' => $attendanceRate,
    'latestAnnouncement' => $latestAnnouncement,
    'rekapDate' => $rekapDate,
    'todaySchedule' => (new ScheduleModel())->getScheduleForDayWithFallback(
        date('N', strtotime($rekapDate)), 
        ['class_id' => $classId], 
        (new \App\Models\AcademicYearModel())->getActiveYear()['id']
    ),
    'holidayInfo' => $holidayInfo,
];
```

## Fitur yang Tersedia

### 1. Pencarian Tanggal
- Input date picker untuk memilih tanggal
- Auto-submit saat tanggal dipilih
- Tombol "Lihat" untuk submit manual
- Max date: 1 tahun ke depan

### 2. Tombol "Hari Ini"
- Muncul hanya jika sedang melihat tanggal selain hari ini
- Klik untuk kembali ke dashboard hari ini
- Icon refresh untuk indikasi reset

### 3. Badge Tanggal
- Menampilkan tanggal terpilih dalam format Indonesia
- Format: "Senin, 05 Jan 2026"
- Styling: badge dengan background primary opacity

### 4. Keterangan Hari Libur
Alert khusus yang muncul jika tanggal adalah:

#### Hari Libur Resmi
- Icon: `bi-calendar-x`
- Warna: danger (merah)
- Judul: "Hari Libur"
- Deskripsi: Dari database holidays
- Pesan: "Tidak ada jadwal pelajaran pada hari ini"

#### Akhir Pekan (Sabtu/Minggu)
- Icon: `bi-calendar2-week`
- Warna: secondary (abu-abu)
- Judul: "Akhir Pekan"
- Deskripsi: "Akhir Pekan (Sabtu)" atau "Akhir Pekan (Minggu)"
- Pesan: "Tidak ada jadwal pelajaran pada hari ini"

### 5. Jadwal Dinamis
- Jadwal ditampilkan sesuai hari dari tanggal yang dipilih
- Menggunakan `date('N', strtotime($rekapDate))` untuk mendapatkan day of week
- Filter berdasarkan class_id siswa
- Fallback ke jadwal default jika tidak ada jadwal khusus

## Cara Penggunaan

### Untuk Siswa/Orang Tua:

1. **Melihat Jadwal Hari Ini**
   - Akses dashboard (default menampilkan hari ini)
   - Jadwal hari ini langsung terlihat

2. **Melihat Jadwal Tanggal Lain**
   - Klik input date picker
   - Pilih tanggal yang diinginkan
   - Jadwal akan otomatis ter-update

3. **Kembali ke Hari Ini**
   - Klik tombol "Hari Ini" di sebelah badge tanggal
   - Atau akses langsung `dashboard` tanpa parameter

4. **Melihat Hari Libur**
   - Pilih tanggal yang merupakan hari libur
   - Alert merah/abu-abu akan muncul dengan keterangan
   - Jadwal tidak ditampilkan

## URL Format

```
# Hari ini (default)
/dashboard

# Tanggal tertentu
/dashboard?date=2026-01-15

# Contoh dengan tanggal hari libur
/dashboard?date=2026-01-01
```

## Dependencies

### Helper yang Digunakan
- `holiday_helper.php` - untuk fungsi `get_holiday_info()`

### Model yang Digunakan
- `ScheduleModel` - untuk mengambil jadwal
- `AcademicYearModel` - untuk mendapatkan tahun ajaran aktif

### Database Tables
- `holidays` - untuk data hari libur
- `schedules` - untuk jadwal pelajaran
- `classes` - untuk data kelas
- `student_records` - untuk relasi siswa-kelas

## Responsive Design

### Desktop (≥992px)
- Date picker dan badge dalam satu baris
- Form date picker max-width 400px
- Badge di sebelah kanan

### Tablet (768px - 991px)
- Layout tetap horizontal
- Spacing disesuaikan

### Mobile (<768px)
- Date picker dan badge stack vertikal
- Form full width
- Badge center atau left aligned
- Tombol "Hari Ini" tetap inline dengan badge

## Testing

### Test Case 1: Melihat Jadwal Hari Ini
1. Akses `/dashboard`
2. Verifikasi tanggal badge = hari ini
3. Verifikasi jadwal sesuai hari ini
4. Tombol "Hari Ini" tidak muncul

### Test Case 2: Melihat Jadwal Tanggal Lain
1. Pilih tanggal selain hari ini
2. Verifikasi URL berubah menjadi `/dashboard?date=YYYY-MM-DD`
3. Verifikasi badge menampilkan tanggal yang dipilih
4. Verifikasi jadwal sesuai hari dari tanggal tersebut
5. Tombol "Hari Ini" muncul

### Test Case 3: Kembali ke Hari Ini
1. Dari tanggal lain, klik tombol "Hari Ini"
2. Verifikasi URL kembali ke `/dashboard`
3. Verifikasi jadwal kembali ke hari ini

### Test Case 4: Hari Libur Resmi
1. Pilih tanggal yang ada di tabel `holidays`
2. Verifikasi alert merah muncul
3. Verifikasi deskripsi sesuai database
4. Verifikasi jadwal tidak ditampilkan

### Test Case 5: Akhir Pekan
1. Pilih tanggal Sabtu atau Minggu
2. Verifikasi alert abu-abu muncul
3. Verifikasi deskripsi "Akhir Pekan (Sabtu/Minggu)"
4. Verifikasi jadwal tidak ditampilkan

### Test Case 6: Tanggal Tanpa Jadwal (Bukan Libur)
1. Pilih tanggal hari kerja yang tidak ada jadwalnya
2. Verifikasi tidak ada holiday alert
3. Verifikasi empty state muncul
4. Pesan: "Tidak ada jadwal pelajaran untuk tanggal ini"

## Konsistensi dengan Dashboard Lain

Fitur ini dibuat konsisten dengan:
- **Dashboard Admin** - format date picker, badge, holiday notice
- **Dashboard Guru** - layout dan styling
- **Dashboard Kepsek** - struktur alert dan card

## Catatan Penting

1. **Holiday Helper**: Pastikan `holiday_helper.php` sudah di-load di `app/Config/Autoload.php`
2. **Active Year**: Sistem memerlukan tahun ajaran aktif untuk menampilkan jadwal
3. **Class Assignment**: Siswa harus memiliki kelas aktif di `student_records` untuk melihat jadwal
4. **Date Format**: Semua tanggal menggunakan format `Y-m-d` (YYYY-MM-DD)
5. **Timezone**: Pastikan timezone server sudah diset ke Asia/Jakarta

## Benefit untuk User

### Untuk Siswa:
- Dapat melihat jadwal di tanggal mendatang untuk persiapan
- Mengetahui hari libur dengan jelas
- Tidak perlu menghafal jadwal, cukup cek di dashboard

### Untuk Orang Tua:
- Dapat memantau jadwal anak di tanggal tertentu
- Membantu persiapan kebutuhan sekolah anak
- Mengetahui kapan anak libur

## Future Enhancement

Potensi pengembangan di masa depan:
1. Calendar view untuk melihat jadwal bulanan
2. Export jadwal ke PDF/iCal
3. Notifikasi reminder untuk jadwal besok
4. Highlight mata pelajaran favorit
5. Filter jadwal berdasarkan mata pelajaran

---

**Tanggal Implementasi**: 5 April 2026  
**Developer**: AI Assistant  
**Status**: ✅ Completed & Tested
