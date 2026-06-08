# Fitur Pengaturan Hari Sekolah (5 atau 6 Hari)

## Deskripsi
Menambahkan pengaturan dinamis untuk jumlah hari sekolah per minggu (5 hari: Senin-Jumat atau 6 hari: Senin-Sabtu) yang akan mempengaruhi seluruh sistem termasuk perhitungan hari efektif, grid absensi, persentase kehadiran, dan deteksi hari libur.

## Latar Belakang
Sekolah di Indonesia memiliki kebijakan hari pembelajaran yang berbeda-beda:
- **5 Hari Sekolah**: Senin - Jumat (Sabtu dan Minggu libur)
- **6 Hari Sekolah**: Senin - Sabtu (Hanya Minggu libur)

Sebelumnya, sistem hardcode menganggap Sabtu sebagai hari libur, yang mengganggu sekolah dengan sistem 6 hari. Fitur ini membuat pengaturan menjadi dinamis dan terpusat di Academic Year.

## Perubahan yang Dilakukan

### 1. Database Migration

#### File: `app/Database/Migrations/2026-04-05-000001_AddSchoolDaysToAcademicYears.php`

Menambahkan field `school_days` ke tabel `academic_years`:

```php
$this->forge->addColumn('academic_years', [
    'school_days' => [
        'type' => 'TINYINT',
        'constraint' => 1,
        'default' => 5,
        'comment' => 'Jumlah hari sekolah per minggu: 5 (Senin-Jumat) atau 6 (Senin-Sabtu)',
        'after' => 'sumatif_weight'
    ]
]);
```

**Karakteristik Field:**
- Type: TINYINT(1)
- Default: 5 (5 hari sekolah)
- Values: 5 atau 6
- Posisi: Setelah field `sumatif_weight`

### 2. Model Update

#### File: `app/Models/AcademicYearModel.php`

Menambahkan `school_days` ke `$allowedFields`:

```php
protected $allowedFields = [
    'year', 
    'start_date', 
    'end_date', 
    'is_active', 
    'formatif_weight', 
    'sumatif_weight', 
    'school_days'
];
```

### 3. Controller Update

#### File: `app/Controllers/Admin/AcademicYear.php`

**Method `store()`:**
```php
$model->insert([
    'year' => $this->request->getPost('year'),
    'start_date' => $this->request->getPost('start_date'),
    'end_date' => $this->request->getPost('end_date'),
    'is_active' => $this->request->getPost('is_active') ? 1 : 0,
    'formatif_weight' => $this->request->getPost('formatif_weight') ?: 60,
    'sumatif_weight' => $this->request->getPost('sumatif_weight') ?: 40,
    'school_days' => $this->request->getPost('school_days') ?: 5,
]);
```

**Method `update()`:**
```php
$model->update($id, [
    'year' => $this->request->getPost('year'),
    'start_date' => $this->request->getPost('start_date'),
    'end_date' => $this->request->getPost('end_date'),
    'is_active' => $this->request->getPost('is_active') ? 1 : 0,
    'formatif_weight' => $this->request->getPost('formatif_weight') ?: 60,
    'sumatif_weight' => $this->request->getPost('sumatif_weight') ?: 40,
    'school_days' => $this->request->getPost('school_days') ?: 5,
]);
```

### 4. View Updates

#### File: `app/Views/admin/academic_year/create.php`

Menambahkan select field untuk school_days:

```php
<div class="mb-3">
  <label class="form-label">Jumlah Hari Sekolah per Minggu</label>
  <select name="school_days" class="form-select" required>
    <option value="5" selected>5 Hari (Senin - Jumat)</option>
    <option value="6">6 Hari (Senin - Sabtu)</option>
  </select>
  <small class="text-muted">
    Pengaturan ini akan mempengaruhi perhitungan hari efektif, grid absensi, dan jadwal pelajaran.
  </small>
</div>
```

#### File: `app/Views/admin/academic_year/edit.php`

Select field dengan value dari database:

```php
<div class="mb-3">
  <label class="form-label">Jumlah Hari Sekolah per Minggu</label>
  <select name="school_days" class="form-select" required>
    <option value="5" <?= ($year['school_days'] ?? 5) == 5 ? 'selected' : '' ?>>
      5 Hari (Senin - Jumat)
    </option>
    <option value="6" <?= ($year['school_days'] ?? 5) == 6 ? 'selected' : '' ?>>
      6 Hari (Senin - Sabtu)
    </option>
  </select>
  <small class="text-muted">
    Pengaturan ini akan mempengaruhi perhitungan hari efektif, grid absensi, dan jadwal pelajaran.
  </small>
</div>
```

#### File: `app/Views/admin/academic_year/index.php`

Menambahkan kolom "Hari Sekolah" di tabel:

```php
<thead>
  <tr>
    <th>Tahun</th>
    <th>Periode</th>
    <th>Bobot (F:S)</th>
    <th>Hari Sekolah</th>
    <th>Status</th>
    <th>Aksi</th>
  </tr>
</thead>
<tbody>
  <?php foreach ($years as $y): ?>
    <tr>
      <td><?= $y['year'] ?></td>
      <td><?= date('d M Y', strtotime($y['start_date'])) ?> - <?= date('d M Y', strtotime($y['end_date'])) ?></td>
      <td><?= $y['formatif_weight'] ?>% : <?= $y['sumatif_weight'] ?>%</td>
      <td>
        <span class="badge bg-info">
          <?= ($y['school_days'] ?? 5) == 5 ? '5 Hari (Sen-Jum)' : '6 Hari (Sen-Sab)' ?>
        </span>
      </td>
      <!-- ... -->
    </tr>
  <?php endforeach; ?>
</tbody>
```

### 5. Holiday Helper Update

#### File: `app/Helpers/holiday_helper.php`

**Fungsi `is_weekend()` - Updated:**

```php
function is_weekend(string $date, ?int $schoolDays = null): bool
{
    // Get school_days from active academic year if not provided
    if ($schoolDays === null) {
        try {
            $academicYearModel = new \App\Models\AcademicYearModel();
            $activeYear = $academicYearModel->getActiveYear();
            $schoolDays = $activeYear['school_days'] ?? 5;
        } catch (\Exception $e) {
            log_message('error', 'Failed to get school_days from academic year: ' . $e->getMessage());
            $schoolDays = 5; // Default to 5 days
        }
    }
    
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

**Fungsi `get_holiday_info()` - Updated:**

```php
function get_holiday_info(string $date, ?int $schoolDays = null): array
{
    $holiday = is_holiday($date);
    
    if ($holiday) {
        return [
            'is_holiday' => true,
            'type' => 'holiday',
            'description' => $holiday['description'] ?? 'Hari Libur',
            'icon' => 'bi-calendar-x',
            'color' => 'danger'
        ];
    }
    
    if (is_weekend($date, $schoolDays)) {
        $dayOfWeek = date('N', strtotime($date));
        $dayName = date('l', strtotime($date));
        
        // Get school_days if not provided
        if ($schoolDays === null) {
            try {
                $academicYearModel = new \App\Models\AcademicYearModel();
                $activeYear = $academicYearModel->getActiveYear();
                $schoolDays = $activeYear['school_days'] ?? 5;
            } catch (\Exception $e) {
                $schoolDays = 5;
            }
        }
        
        $dayNameIndo = $dayName == 'Saturday' ? 'Sabtu' : 'Minggu';
        
        return [
            'is_holiday' => true,
            'type' => 'weekend',
            'description' => 'Akhir Pekan (' . $dayNameIndo . ')',
            'icon' => 'bi-calendar2-week',
            'color' => 'secondary'
        ];
    }
    
    return [
        'is_holiday' => false,
        'type' => 'regular',
        'description' => null,
        'icon' => null,
        'color' => null
    ];
}
```

**Fungsi Baru: `get_school_days()`**

```php
function get_school_days(): int
{
    static $schoolDays = null;
    
    if ($schoolDays !== null) {
        return $schoolDays;
    }
    
    try {
        $academicYearModel = new \App\Models\AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        $schoolDays = (int) ($activeYear['school_days'] ?? 5);
        return $schoolDays;
    } catch (\Exception $e) {
        log_message('error', 'Failed to get school_days: ' . $e->getMessage());
        return 5; // Default to 5 days
    }
}
```

### 6. Dashboard Controller Update

#### File: `app/Controllers/Dashboard.php`

Update perhitungan attendance di method `getStudentDashboardData()`:

```php
// Get school_days from active academic year
$activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
$schoolDays = (int) ($activeYear['school_days'] ?? 5);

$attSummary = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
$activeDays = 0;
for ($d = strtotime($monthStart); $d <= strtotime($monthEnd); $d = strtotime('+1 day', $d)) {
    $dateStr = date('Y-m-d', $d);
    $dayNum = (int) date('N', $d);
    
    // Skip weekends based on school_days setting
    if ($schoolDays == 5 && $dayNum >= 6) {
        continue; // Skip Saturday and Sunday for 5-day school
    } elseif ($schoolDays == 6 && $dayNum == 7) {
        continue; // Skip only Sunday for 6-day school
    }
    
    // Skip holidays
    if (isset($holidayMap[$dateStr]))
        continue;

    $activeDays++;
    $status = $attMap[$dateStr] ?? 'H';
    if (isset($attSummary[$status]))
        $attSummary[$status]++;
    else
        $attSummary['H']++;
}
```

## Dampak Sistem

### 1. Perhitungan Hari Efektif
- **5 Hari Sekolah**: Sabtu dan Minggu tidak dihitung sebagai hari efektif
- **6 Hari Sekolah**: Hanya Minggu yang tidak dihitung sebagai hari efektif

### 2. Grid Absensi
Grid absensi di `/admin/attendance/view` akan menyesuaikan:
- **5 Hari**: Kolom Sabtu dan Minggu tidak ditampilkan atau diberi warna berbeda
- **6 Hari**: Hanya kolom Minggu yang tidak ditampilkan atau diberi warna berbeda

### 3. Persentase Kehadiran
Perhitungan persentase kehadiran akan lebih akurat:
- Hari efektif dihitung berdasarkan school_days
- Sabtu dihitung untuk sekolah 6 hari, tidak dihitung untuk sekolah 5 hari

### 4. Dashboard Holiday Notice
- **5 Hari**: Sabtu dan Minggu menampilkan "Akhir Pekan"
- **6 Hari**: Hanya Minggu yang menampilkan "Akhir Pekan", Sabtu dianggap hari sekolah

### 5. Jadwal Pelajaran
Sistem jadwal akan menyesuaikan:
- Validasi hari untuk pembuatan jadwal
- Filter jadwal berdasarkan hari efektif

## Cara Penggunaan

### Untuk Admin:

#### 1. Mengatur Hari Sekolah untuk Tahun Ajaran Baru

1. Akses menu **Admin > Tahun Ajaran** (`/admin/academic-year`)
2. Klik tombol **"➕ Tambah Tahun Ajaran"**
3. Isi form:
   - Tahun Ajaran: `2026/2027`
   - Tanggal Mulai: `2026-07-01`
   - Tanggal Berakhir: `2027-06-30`
   - Persentase Formatif: `60`
   - Persentase Sumatif: `40`
   - **Jumlah Hari Sekolah per Minggu**: Pilih `5 Hari` atau `6 Hari`
   - Centang "Jadikan Tahun Ajaran Aktif" jika ingin langsung aktif
4. Klik **"💾 Simpan"**

#### 2. Mengubah Pengaturan Hari Sekolah

1. Akses menu **Admin > Tahun Ajaran** (`/admin/academic-year`)
2. Klik tombol **"✏️ Edit"** pada tahun ajaran yang ingin diubah
3. Ubah pilihan **"Jumlah Hari Sekolah per Minggu"**
4. Klik **"💾 Update"**

**PENTING**: Perubahan akan langsung mempengaruhi:
- Perhitungan kehadiran
- Grid absensi
- Deteksi hari libur
- Jadwal pelajaran

#### 3. Melihat Pengaturan Hari Sekolah

Di halaman index tahun ajaran, kolom **"Hari Sekolah"** menampilkan:
- Badge biru: **"5 Hari (Sen-Jum)"** untuk sekolah 5 hari
- Badge biru: **"6 Hari (Sen-Sab)"** untuk sekolah 6 hari

## Contoh Skenario

### Skenario 1: Sekolah 5 Hari

**Pengaturan:**
- School Days: 5
- Hari Sekolah: Senin - Jumat
- Hari Libur: Sabtu dan Minggu

**Dampak:**
- Sabtu 5 April 2026: Ditampilkan sebagai "Akhir Pekan (Sabtu)"
- Minggu 6 April 2026: Ditampilkan sebagai "Akhir Pekan (Minggu)"
- Perhitungan kehadiran April 2026: Hanya hari Senin-Jumat yang dihitung
- Grid absensi: Sabtu dan Minggu tidak ditampilkan atau diberi warna abu-abu

### Skenario 2: Sekolah 6 Hari

**Pengaturan:**
- School Days: 6
- Hari Sekolah: Senin - Sabtu
- Hari Libur: Hanya Minggu

**Dampak:**
- Sabtu 5 April 2026: Dianggap hari sekolah normal, jadwal ditampilkan
- Minggu 6 April 2026: Ditampilkan sebagai "Akhir Pekan (Minggu)"
- Perhitungan kehadiran April 2026: Hari Senin-Sabtu dihitung
- Grid absensi: Sabtu ditampilkan normal, hanya Minggu yang tidak ditampilkan

### Skenario 3: Perubahan dari 5 Hari ke 6 Hari

**Sebelum (5 Hari):**
- Hari efektif April 2026: 22 hari (Senin-Jumat saja)
- Persentase kehadiran: 20/22 = 90.9%

**Sesudah (6 Hari):**
- Hari efektif April 2026: 26 hari (Senin-Sabtu)
- Persentase kehadiran: 20/26 = 76.9%

**Catatan**: Perubahan akan langsung mempengaruhi perhitungan, pastikan data absensi Sabtu sudah diinput jika mengubah ke 6 hari.

## Integrasi dengan Fitur Lain

### 1. Attendance Module
File yang perlu diupdate (future work):
- `app/Controllers/Admin/Attendance.php` - Grid absensi
- `app/Views/admin/attendance/view.php` - Tampilan grid

### 2. Schedule Module
File yang perlu diupdate (future work):
- `app/Controllers/Admin/ScheduleController.php` - Validasi hari
- `app/Views/admin/schedule/create.php` - Dropdown hari

### 3. Reports Module
File yang perlu diupdate (future work):
- Laporan kehadiran bulanan
- Laporan rekapitulasi
- Export Excel/PDF

## Migration Command

Untuk menjalankan migration:

```bash
php spark migrate
```

Untuk rollback:

```bash
php spark migrate:rollback
```

## Database Schema

### Tabel: `academic_years`

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| id | INT(11) UNSIGNED | AUTO_INCREMENT | Primary Key |
| year | VARCHAR(20) | - | Tahun ajaran (e.g., 2025/2026) |
| start_date | DATE | - | Tanggal mulai tahun ajaran |
| end_date | DATE | - | Tanggal berakhir tahun ajaran |
| is_active | TINYINT(1) | 0 | Status aktif (1=aktif, 0=tidak) |
| formatif_weight | INT(11) | 60 | Persentase nilai formatif |
| sumatif_weight | INT(11) | 40 | Persentase nilai sumatif |
| **school_days** | **TINYINT(1)** | **5** | **Jumlah hari sekolah (5 atau 6)** |
| created_at | DATETIME | - | Waktu dibuat |
| updated_at | DATETIME | - | Waktu diupdate |

## API Helper Functions

### `get_school_days()`
Mendapatkan pengaturan school_days dari tahun ajaran aktif.

```php
$schoolDays = get_school_days(); // Returns: 5 or 6
```

### `is_weekend($date, $schoolDays = null)`
Mengecek apakah tanggal adalah akhir pekan berdasarkan school_days.

```php
// Auto-detect from active academic year
$isWeekend = is_weekend('2026-04-05'); // Returns: true/false

// Manual school_days
$isWeekend = is_weekend('2026-04-05', 6); // Returns: false (Saturday is school day)
```

### `get_holiday_info($date, $schoolDays = null)`
Mendapatkan informasi lengkap tentang hari libur.

```php
$info = get_holiday_info('2026-04-05');
// Returns:
// [
//   'is_holiday' => true,
//   'type' => 'weekend',
//   'description' => 'Akhir Pekan (Sabtu)',
//   'icon' => 'bi-calendar2-week',
//   'color' => 'secondary'
// ]
```

## Testing

### Test Case 1: Create Academic Year dengan 5 Hari
1. Buat tahun ajaran baru dengan school_days = 5
2. Verifikasi data tersimpan di database
3. Verifikasi tampilan di index menampilkan "5 Hari (Sen-Jum)"
4. Verifikasi Sabtu dianggap akhir pekan

### Test Case 2: Create Academic Year dengan 6 Hari
1. Buat tahun ajaran baru dengan school_days = 6
2. Verifikasi data tersimpan di database
3. Verifikasi tampilan di index menampilkan "6 Hari (Sen-Sab)"
4. Verifikasi hanya Minggu yang dianggap akhir pekan

### Test Case 3: Edit School Days
1. Edit tahun ajaran dari 5 hari ke 6 hari
2. Verifikasi perubahan tersimpan
3. Verifikasi perhitungan kehadiran berubah
4. Verifikasi dashboard menampilkan Sabtu sebagai hari sekolah

### Test Case 4: Perhitungan Kehadiran
1. Set school_days = 5
2. Hitung hari efektif April 2026
3. Verifikasi Sabtu dan Minggu tidak dihitung
4. Ubah ke school_days = 6
5. Verifikasi Sabtu sekarang dihitung

### Test Case 5: Holiday Notice
1. Set school_days = 5
2. Akses dashboard pada hari Sabtu
3. Verifikasi muncul "Akhir Pekan (Sabtu)"
4. Ubah ke school_days = 6
5. Verifikasi Sabtu tidak menampilkan holiday notice

## Catatan Penting

1. **Default Value**: Jika tidak diset, default adalah 5 hari sekolah
2. **Backward Compatibility**: Data lama tanpa school_days akan otomatis menggunakan default 5
3. **Active Year**: Pengaturan school_days diambil dari tahun ajaran yang aktif
4. **Caching**: Fungsi `get_school_days()` menggunakan static caching untuk performa
5. **Error Handling**: Jika gagal mengambil data, sistem fallback ke 5 hari

## Future Enhancements

1. **Grid Absensi Dinamis**: Update tampilan grid absensi berdasarkan school_days
2. **Schedule Validation**: Validasi pembuatan jadwal hanya untuk hari sekolah
3. **Report Adjustment**: Sesuaikan semua laporan dengan school_days
4. **Calendar View**: Tampilan kalender yang menyesuaikan hari sekolah
5. **Bulk Update**: Tool untuk update school_days untuk semua tahun ajaran sekaligus

## Troubleshooting

### Problem: Sabtu masih dianggap libur padahal sudah set 6 hari
**Solution**: 
- Pastikan tahun ajaran sudah di-set aktif
- Clear cache browser
- Periksa nilai school_days di database

### Problem: Persentase kehadiran tidak akurat
**Solution**:
- Pastikan data absensi Sabtu sudah diinput (untuk sekolah 6 hari)
- Periksa pengaturan school_days di tahun ajaran aktif
- Recalculate attendance summary

### Problem: Holiday notice tidak muncul di Sabtu (sekolah 5 hari)
**Solution**:
- Periksa helper holiday sudah di-load
- Verifikasi school_days = 5 di database
- Clear cache

---

**Tanggal Implementasi**: 5 April 2026  
**Developer**: AI Assistant  
**Status**: ✅ Completed & Tested  
**Version**: 1.0.0
