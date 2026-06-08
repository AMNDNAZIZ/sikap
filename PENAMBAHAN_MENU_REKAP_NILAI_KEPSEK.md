# Penambahan Menu Rekap Nilai untuk Kepala Sekolah

## Ringkasan
Menambahkan menu "Rekap Nilai" ke sidebar kepala sekolah agar dapat mengakses halaman `/admin/grades/rekap` untuk melihat rekapitulasi nilai siswa.

## Perubahan yang Dilakukan

### File yang Dimodifikasi
`app/Views/layouts/partials/sidebar_kepsek.php`

### Perubahan Detail

#### 1. Menambahkan Menu Item Baru

**Lokasi**: Di bawah menu "Laporan Rapor" dalam section "Akademik & Konseling"

**Kode yang Ditambahkan**:
```php
<li><a class="nav-link <?= url_is('admin/grades/rekap*') ? 'active' : '' ?>"
    href="<?= base_url('admin/grades/rekap') ?>">📈 <span class="label">Rekap Nilai</span></a></li>
```

**Fitur**:
- Icon: 📈 (chart increasing)
- Label: "Rekap Nilai"
- URL: `/admin/grades/rekap`
- Active state: Otomatis highlight saat di halaman rekap nilai

#### 2. Update Kondisi Active untuk Menu "Laporan Rapor"

**Sebelum**:
```php
<li><a class="nav-link <?= url_is('admin/grades') && !url_is('admin/grades/tracking') ? 'active' : '' ?>"
    href="<?= base_url('admin/grades') ?>">📊 <span class="label">Laporan Rapor</span></a></li>
```

**Sesudah**:
```php
<li><a class="nav-link <?= url_is('admin/grades') && !url_is('admin/grades/tracking') && !url_is('admin/grades/rekap') ? 'active' : '' ?>"
    href="<?= base_url('admin/grades') ?>">📊 <span class="label">Laporan Rapor</span></a></li>
```

**Alasan**: Agar menu "Laporan Rapor" tidak active saat berada di halaman "Rekap Nilai"

## Struktur Menu Akademik & Konseling (Setelah Perubahan)

```
📚 Akademik & Konseling
├── 🗂️ Plotting Pengajaran
├── 📊 Laporan Rapor
├── 📈 Rekap Nilai ← BARU
├── 🕵️ Tracking Nilai
├── 🕒 Presensi/Absensi
├── 📅 Agenda Kelas
├── 📓 Jurnal Mengajar
├── 🗓️ Jadwal Pelajaran
├── 🗒️ Catatan Siswa
└── 📢 Pengumuman
```

## Urutan Menu

Menu "Rekap Nilai" ditempatkan di posisi ke-3, setelah "Laporan Rapor" dan sebelum "Tracking Nilai", karena:

1. **Logical Flow**: 
   - Laporan Rapor → Rekap Nilai → Tracking Nilai
   - Dari general ke specific

2. **Frequency of Use**:
   - Rekap Nilai lebih sering diakses daripada Tracking Nilai
   - Kepala Sekolah perlu melihat rekap untuk monitoring

3. **Related Functionality**:
   - Ketiga menu terkait dengan nilai/grades
   - Dikelompokkan bersama untuk kemudahan navigasi

## Permission & Access Control

### Akses Halaman
- **URL**: `/admin/grades/rekap`
- **Permission**: Sudah ada di controller (Admin & Kepsek)
- **Role yang Dapat Akses**:
  - Admin (role_id = 1)
  - Kepala Sekolah (role_id = 2)

### Tidak Perlu Perubahan Route
Route sudah ada di `app/Config/Routes.php`:
```php
$routes->get('grades/rekap', 'Grades::rekap', $auth12);
```

## Fungsi Halaman Rekap Nilai

Halaman `/admin/grades/rekap` menyediakan:

1. **Rekapitulasi Nilai per Kelas**
   - Melihat nilai semua siswa dalam satu kelas
   - Filter berdasarkan kelas dan tahun ajaran

2. **Statistik Nilai**
   - Rata-rata nilai per mata pelajaran
   - Distribusi nilai (tinggi, sedang, rendah)
   - Perbandingan antar kelas

3. **Export Data**
   - Export ke Excel/PDF
   - Untuk keperluan laporan

4. **Monitoring**
   - Kepala Sekolah dapat memonitor performa akademik
   - Identifikasi kelas/siswa yang perlu perhatian khusus

## Testing

### Test Case 1: Menu Visibility
1. Login sebagai Kepala Sekolah
2. Lihat sidebar menu
3. **Expected**: Menu "Rekap Nilai" muncul di section "Akademik & Konseling"

### Test Case 2: Menu Navigation
1. Login sebagai Kepala Sekolah
2. Klik menu "Rekap Nilai"
3. **Expected**: Redirect ke `/admin/grades/rekap`

### Test Case 3: Active State
1. Login sebagai Kepala Sekolah
2. Navigasi ke halaman Rekap Nilai
3. **Expected**: 
   - Menu "Rekap Nilai" ter-highlight (active)
   - Menu "Laporan Rapor" tidak active
   - Section "Akademik & Konseling" terbuka (expanded)

### Test Case 4: Permission
1. Login sebagai Guru (role_id = 3)
2. Coba akses `/admin/grades/rekap`
3. **Expected**: Access denied atau redirect (sesuai permission di controller)

### Test Case 5: Mobile View
1. Login sebagai Kepala Sekolah di mobile
2. Buka offcanvas menu
3. **Expected**: Menu "Rekap Nilai" muncul dan berfungsi

## Konsistensi dengan Menu Admin

### Perbandingan Menu Grades

| Menu | Admin | Kepsek |
|------|-------|--------|
| Laporan Rapor | ✅ | ✅ |
| Rekap Nilai | ✅ | ✅ |
| Tracking Nilai | ✅ | ✅ |

**Catatan**: Kepala Sekolah memiliki akses yang sama dengan Admin untuk menu-menu terkait nilai, karena perlu untuk monitoring dan supervisi.

## Keuntungan

### 1. Akses Mudah untuk Kepsek
- Tidak perlu mengingat URL manual
- Langsung dari sidebar menu
- Konsisten dengan menu lainnya

### 2. Monitoring Lebih Baik
- Kepala Sekolah dapat dengan mudah memonitor nilai
- Akses cepat ke rekapitulasi
- Mendukung supervisi akademik

### 3. User Experience
- Navigasi yang intuitif
- Menu terorganisir dengan baik
- Active state yang jelas

### 4. Konsistensi
- Sesuai dengan struktur menu Admin
- Icon yang konsisten (📈 untuk rekap/chart)
- Naming yang jelas

## Catatan Teknis

### Mengapa Tidak Menggunakan Submenu?

Menu "Rekap Nilai" tidak dijadikan submenu dari "Laporan Rapor" karena:

1. **Different Purpose**:
   - Laporan Rapor: Generate rapor individual
   - Rekap Nilai: Lihat rekapitulasi semua siswa

2. **Frequency of Use**:
   - Kedua menu sama-sama sering diakses
   - Tidak ada yang lebih "parent" dari yang lain

3. **Simplicity**:
   - Flat structure lebih mudah dinavigasi
   - Tidak perlu expand submenu lagi

### Icon Selection

Icon 📈 (chart increasing) dipilih karena:
- Merepresentasikan data/statistik
- Berbeda dari 📊 (bar chart) untuk Laporan Rapor
- Berbeda dari 🕵️ (detective) untuk Tracking Nilai
- Mudah dikenali dan intuitif

## File yang Dimodifikasi

1. `app/Views/layouts/partials/sidebar_kepsek.php` - Menambahkan menu Rekap Nilai

## Referensi

- Controller: `app/Controllers/Admin/Grades.php` (method `rekap()`)
- Route: `app/Config/Routes.php` (sudah ada)
- Permission: Admin (1) dan Kepsek (2)
