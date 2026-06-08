# Peningkatan Tampilan Rekap Ketidakhadiran

## Deskripsi
Meningkatkan tampilan dan informasi pada section "Rekap Ketidakhadiran" di dashboard admin dan guru dengan menambahkan:
1. Keterangan khusus untuk hari libur dan akhir pekan
2. Breakdown statistik ketidakhadiran (Sakit, Izin, Alpa)
3. Label status yang lebih jelas (bukan hanya huruf S/I/A)
4. Icon dan visual yang lebih informatif

## Latar Belakang
Sebelumnya, rekap ketidakhadiran hanya menampilkan:
- Daftar siswa yang tidak hadir dengan badge S/I/A
- Total jumlah yang tidak hadir
- Tidak ada keterangan untuk hari libur/weekend
- Tidak ada breakdown per jenis ketidakhadiran

Ini membuat admin/guru kurang mendapat informasi lengkap tentang pola ketidakhadiran.

## Perubahan yang Dilakukan

### File: `app/Views/dashboard/_absence_rekap.php`

#### 1. Penambahan Holiday/Weekend Notice

**Fitur Baru:**
```php
<?php 
// Check if selected date is holiday or weekend
helper('holiday');
$holidayInfo = get_holiday_info($rekapDate);
?>

<?php if (!empty($holidayInfo) && $holidayInfo['is_holiday']): ?>
    <!-- Holiday/Weekend Notice -->
    <div class="alert alert-<?= $holidayInfo['color'] ?> border-0 mb-3" role="alert">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0">
                <i class="bi <?= $holidayInfo['icon'] ?> fs-3"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="alert-heading mb-1 fw-bold">
                    <?= $holidayInfo['type'] == 'holiday' ? 'Hari Libur Resmi' : 'Akhir Pekan' ?>
                </h6>
                <p class="mb-2"><?= esc($holidayInfo['description']) ?></p>
                <small class="opacity-75">
                    <i class="bi bi-info-circle me-1"></i>
                    Tidak ada kegiatan belajar mengajar pada tanggal ini, sehingga tidak ada data ketidakhadiran yang perlu dicatat.
                </small>
            </div>
        </div>
    </div>
<?php endif; ?>
```

**Tampilan:**
- **Hari Libur Resmi**: Alert merah dengan icon calendar-x
- **Akhir Pekan**: Alert abu-abu dengan icon calendar2-week
- Menampilkan deskripsi libur (dari database atau "Akhir Pekan")
- Keterangan bahwa tidak ada KBM

#### 2. Peningkatan Empty State

**Sebelum:**
```php
<div class="text-center py-4">
    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
    <p class="mt-2 text-muted">Semua siswa hadir atau belum ada data hari ini.</p>
</div>
```

**Sesudah:**
```php
<?php if (empty($holidayInfo) || !$holidayInfo['is_holiday']): ?>
    <!-- Regular day with no absences -->
    <div class="text-center py-4">
        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
        <p class="mt-2 text-muted mb-0">Semua siswa hadir pada tanggal ini.</p>
        <small class="text-muted">Tidak ada siswa yang sakit, izin, atau alpa.</small>
    </div>
<?php endif; ?>
```

**Perubahan:**
- Empty state tidak ditampilkan jika hari libur (sudah ada holiday notice)
- Pesan lebih spesifik: "Semua siswa hadir pada tanggal ini"
- Tambahan keterangan: "Tidak ada siswa yang sakit, izin, atau alpa"

#### 3. Label Status yang Lebih Jelas

**Sebelum:**
```php
<span class="badge bg-<?= ... ?> rounded-pill ms-1">
    <?= $s['status'] ?>  <!-- Hanya S/I/A -->
</span>
```

**Sesudah:**
```php
<span class="badge bg-<?= ... ?> rounded-pill ms-1">
    <?php 
    $statusLabel = [
        'A' => 'Alpa',
        'S' => 'Sakit',
        'I' => 'Izin'
    ];
    echo $statusLabel[$s['status']] ?? $s['status'];
    ?>
</span>
```

**Perubahan:**
- Badge menampilkan "Sakit", "Izin", "Alpa" (bukan S/I/A)
- Lebih mudah dipahami tanpa perlu mengingat kode

#### 4. Icon untuk Nama Kelas

**Sebelum:**
```php
<h6 class="fw-bold border-bottom pb-1">
    <?= esc($className) ?>
</h6>
```

**Sesudah:**
```php
<h6 class="fw-bold border-bottom pb-1">
    <i class="bi bi-door-closed text-primary me-1"></i>
    <?= esc($className) ?>
</h6>
```

**Perubahan:**
- Tambahan icon door-closed untuk visual yang lebih baik

#### 5. Breakdown Statistik Ketidakhadiran

**Fitur Baru:**
```php
<div class="mt-3 pt-3 border-top">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="fw-bold">Total Ketidakhadiran:</span>
        <span class="badge bg-danger rounded-pill fs-6">
            <?= $totalAbsent ?> Siswa
        </span>
    </div>
    <div class="row text-center mt-3">
        <?php
        $statusCount = ['S' => 0, 'I' => 0, 'A' => 0];
        foreach ($absenceRekap as $students) {
            foreach ($students as $s) {
                if (isset($statusCount[$s['status']])) {
                    $statusCount[$s['status']]++;
                }
            }
        }
        ?>
        <div class="col-4">
            <div class="p-2 bg-warning bg-opacity-10 rounded">
                <div class="h5 mb-0 fw-bold text-warning"><?= $statusCount['S'] ?></div>
                <small class="text-muted">Sakit</small>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 bg-info bg-opacity-10 rounded">
                <div class="h5 mb-0 fw-bold text-info"><?= $statusCount['I'] ?></div>
                <small class="text-muted">Izin</small>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 bg-danger bg-opacity-10 rounded">
                <div class="h5 mb-0 fw-bold text-danger"><?= $statusCount['A'] ?></div>
                <small class="text-muted">Alpa</small>
            </div>
        </div>
    </div>
</div>
```

**Tampilan:**
- 3 card kecil menampilkan jumlah per status
- Warna berbeda untuk setiap status:
  - **Sakit**: Warning (kuning)
  - **Izin**: Info (biru)
  - **Alpa**: Danger (merah)
- Angka besar dan label jelas

## Tampilan Sebelum vs Sesudah

### Sebelum:
```
┌─────────────────────────────────────┐
│ Rekap Ketidakhadiran                │
│ Senin, 5 April 2026                 │
├─────────────────────────────────────┤
│ Kelas 1A                            │
│ 1. Ahmad (S)                        │
│ 2. Budi (I)                         │
│ 3. Citra (A)                        │
│                                     │
│ Jumlah yang tidak hadir: 3 Siswa   │
└─────────────────────────────────────┘
```

### Sesudah (Hari Biasa):
```
┌─────────────────────────────────────┐
│ Rekap Ketidakhadiran                │
│ Senin, 5 April 2026                 │
├─────────────────────────────────────┤
│ 🚪 Kelas 1A                         │
│ 1. Ahmad (Sakit)                    │
│ 2. Budi (Izin)                      │
│ 3. Citra (Alpa)                     │
│                                     │
│ Total Ketidakhadiran: 3 Siswa       │
│ ┌─────┐ ┌─────┐ ┌─────┐            │
│ │  1  │ │  1  │ │  1  │            │
│ │Sakit│ │Izin │ │Alpa │            │
│ └─────┘ └─────┘ └─────┘            │
└─────────────────────────────────────┘
```

### Sesudah (Hari Libur):
```
┌─────────────────────────────────────┐
│ Rekap Ketidakhadiran                │
│ Sabtu, 5 April 2026                 │
├─────────────────────────────────────┤
│ ⚠️ Akhir Pekan                      │
│ Akhir Pekan (Sabtu)                 │
│ ℹ️ Tidak ada kegiatan belajar       │
│ mengajar pada tanggal ini           │
└─────────────────────────────────────┘
```

## Skenario Penggunaan

### Skenario 1: Hari Biasa dengan Ketidakhadiran
**Kondisi:**
- Tanggal: Senin, 5 April 2026 (hari sekolah)
- Ada 5 siswa tidak hadir (2 sakit, 2 izin, 1 alpa)

**Tampilan:**
- Tidak ada holiday notice
- Daftar 5 siswa dengan badge "Sakit", "Izin", "Alpa"
- Total: 5 Siswa
- Breakdown: 2 Sakit | 2 Izin | 1 Alpa

### Skenario 2: Hari Biasa Tanpa Ketidakhadiran
**Kondisi:**
- Tanggal: Selasa, 6 April 2026 (hari sekolah)
- Semua siswa hadir

**Tampilan:**
- Tidak ada holiday notice
- Icon check-circle hijau
- Pesan: "Semua siswa hadir pada tanggal ini"
- Sub-pesan: "Tidak ada siswa yang sakit, izin, atau alpa"

### Skenario 3: Hari Libur Resmi
**Kondisi:**
- Tanggal: 1 Januari 2026 (Tahun Baru)
- Data di tabel holidays: "Tahun Baru Masehi"

**Tampilan:**
- Alert merah dengan icon calendar-x
- Judul: "Hari Libur Resmi"
- Deskripsi: "Tahun Baru Masehi"
- Keterangan: "Tidak ada kegiatan belajar mengajar..."
- Tidak ada daftar siswa

### Skenario 4: Akhir Pekan (Sekolah 5 Hari)
**Kondisi:**
- Tanggal: Sabtu, 5 April 2026
- School days: 5 (Senin-Jumat)

**Tampilan:**
- Alert abu-abu dengan icon calendar2-week
- Judul: "Akhir Pekan"
- Deskripsi: "Akhir Pekan (Sabtu)"
- Keterangan: "Tidak ada kegiatan belajar mengajar..."
- Tidak ada daftar siswa

### Skenario 5: Sabtu (Sekolah 6 Hari)
**Kondisi:**
- Tanggal: Sabtu, 5 April 2026
- School days: 6 (Senin-Sabtu)
- Ada 3 siswa tidak hadir

**Tampilan:**
- Tidak ada holiday notice (Sabtu = hari sekolah)
- Daftar 3 siswa dengan badge status
- Total dan breakdown seperti hari biasa

## Integrasi dengan Fitur Lain

### 1. Holiday Helper
Menggunakan fungsi dari `app/Helpers/holiday_helper.php`:
- `get_holiday_info($date)` - Mendapatkan info libur/weekend
- Otomatis menyesuaikan dengan school_days dari academic year

### 2. Academic Year Setting
- Respek terhadap pengaturan school_days (5 atau 6 hari)
- Sabtu dianggap weekend hanya jika school_days = 5

### 3. Dashboard Admin & Guru
Partial view ini digunakan di:
- Dashboard Admin (`app/Views/dashboard/admin_modern.php`)
- Dashboard Guru (`app/Views/dashboard/guru_fixed.php`)
- Dashboard Kepsek (`app/Views/dashboard/kepsek.php`)

## Benefit untuk User

### Untuk Admin/Guru:
1. **Lebih Informatif**: Langsung tahu jenis ketidakhadiran (sakit/izin/alpa)
2. **Visual yang Jelas**: Breakdown statistik memudahkan analisis cepat
3. **Tidak Bingung**: Keterangan jelas untuk hari libur/weekend
4. **Lebih Profesional**: Tampilan yang lebih modern dan informatif

### Untuk Monitoring:
1. **Pola Ketidakhadiran**: Mudah melihat apakah lebih banyak sakit atau alpa
2. **Tren**: Bisa membandingkan breakdown antar hari
3. **Alert Dini**: Jika alpa tinggi, perlu tindakan khusus

## Warna dan Icon

### Status Badge:
- **Sakit (S)**: Badge warning (kuning) → "Sakit"
- **Izin (I)**: Badge info (biru) → "Izin"
- **Alpa (A)**: Badge danger (merah) → "Alpa"

### Holiday Notice:
- **Hari Libur**: Alert danger (merah) + icon `bi-calendar-x`
- **Akhir Pekan**: Alert secondary (abu-abu) + icon `bi-calendar2-week`

### Breakdown Cards:
- **Sakit**: Background warning opacity-10 + text warning
- **Izin**: Background info opacity-10 + text info
- **Alpa**: Background danger opacity-10 + text danger

## Testing

### Test Case 1: Hari Biasa dengan Ketidakhadiran
1. Pilih tanggal hari kerja yang ada data absensi
2. Verifikasi tidak ada holiday notice
3. Verifikasi daftar siswa muncul dengan label lengkap
4. Verifikasi breakdown statistik benar
5. Verifikasi total sesuai

### Test Case 2: Hari Biasa Tanpa Ketidakhadiran
1. Pilih tanggal hari kerja tanpa ketidakhadiran
2. Verifikasi tidak ada holiday notice
3. Verifikasi muncul icon check-circle hijau
4. Verifikasi pesan "Semua siswa hadir"

### Test Case 3: Hari Libur Resmi
1. Pilih tanggal yang ada di tabel holidays
2. Verifikasi muncul alert merah
3. Verifikasi judul "Hari Libur Resmi"
4. Verifikasi deskripsi sesuai database
5. Verifikasi tidak ada daftar siswa

### Test Case 4: Sabtu (Sekolah 5 Hari)
1. Set school_days = 5
2. Pilih tanggal Sabtu
3. Verifikasi muncul alert abu-abu "Akhir Pekan"
4. Verifikasi tidak ada daftar siswa

### Test Case 5: Sabtu (Sekolah 6 Hari)
1. Set school_days = 6
2. Pilih tanggal Sabtu yang ada data absensi
3. Verifikasi tidak ada holiday notice
4. Verifikasi daftar siswa muncul normal

### Test Case 6: Minggu (Semua Sekolah)
1. Pilih tanggal Minggu
2. Verifikasi muncul alert "Akhir Pekan (Minggu)"
3. Verifikasi tidak ada daftar siswa

### Test Case 7: Breakdown Statistik
1. Pilih tanggal dengan mix ketidakhadiran
2. Hitung manual: 2 sakit, 3 izin, 1 alpa
3. Verifikasi breakdown: 2 | 3 | 1
4. Verifikasi total: 6

## Dependencies

### Helper:
- `holiday_helper.php` - Fungsi `get_holiday_info()`

### Model:
- `AcademicYearModel` - Untuk school_days setting

### Database:
- `holidays` - Data hari libur resmi
- `attendances` - Data kehadiran siswa
- `academic_years` - Setting school_days

## Responsive Design

### Desktop (≥992px):
- Breakdown cards dalam 3 kolom
- Alert dengan icon di samping

### Tablet (768px - 991px):
- Breakdown cards tetap 3 kolom
- Font size sedikit lebih kecil

### Mobile (<768px):
- Breakdown cards tetap 3 kolom (compact)
- Alert stack vertikal
- Font size disesuaikan

## Future Enhancements

1. **Grafik Tren**: Chart untuk melihat tren ketidakhadiran per minggu/bulan
2. **Export**: Export rekap ke PDF/Excel
3. **Filter**: Filter by kelas atau jenis ketidakhadiran
4. **Notifikasi**: Alert jika alpa melebihi threshold
5. **Perbandingan**: Compare dengan hari/minggu sebelumnya

---

**Tanggal Implementasi**: 5 April 2026  
**Developer**: AI Assistant  
**Status**: ✅ Completed & Tested  
**Impact**: Dashboard Admin, Guru, Kepsek
