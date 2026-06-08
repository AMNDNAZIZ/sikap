# Fitur Filter Tanggal di Jurnal Mengajar

## Tanggal
24 April 2026

## Deskripsi
Menambahkan filter berdasarkan rentang tanggal (dari tanggal - sampai tanggal) di halaman Jurnal Mengajar untuk memudahkan pencarian jurnal berdasarkan periode tertentu.

## Fitur yang Ditambahkan

### 1. Filter Tanggal di View
**Lokasi**: `app/Views/admin/teaching_journal/index.php`

**Komponen**:
- Input "Dari Tanggal" (date picker)
- Input "Sampai Tanggal" (date picker)
- Tombol "Reset" untuk menghapus semua filter

**Layout**:
- Filter tanggal ditambahkan di baris kedua form filter
- Responsive layout yang menyesuaikan dengan keberadaan filter Guru (untuk Admin/Kepsek)
- Tombol Reset muncul jika ada filter yang aktif

### 2. Filter Logic di Controller
**Lokasi**: `app/Controllers/Admin/TeachingJournalController.php`

**Method**: `index()`

**Parameter Baru**:
- `date_from` - Tanggal awal periode
- `date_to` - Tanggal akhir periode

**Implementasi**:
```php
$dateFrom = $this->request->getGet('date_from');
$dateTo = $this->request->getGet('date_to');

$data['date_from'] = $dateFrom;
$data['date_to'] = $dateTo;

$journalFilters = [];
// ... existing filters
if ($dateFrom) $journalFilters['date_from'] = $dateFrom;
if ($dateTo) $journalFilters['date_to'] = $dateTo;
```

### 3. Query Filter di Model
**Lokasi**: `app/Models/TeachingJournalModel.php`

**Method**: `getJournals($filters)`

**Query Logic**:
```php
// Filter tanggal
if (!empty($filters['date_from'])) {
    $builder->where('teaching_journals.date >=', $filters['date_from']);
}

if (!empty($filters['date_to'])) {
    $builder->where('teaching_journals.date <=', $filters['date_to']);
}
```

## Cara Penggunaan

### 1. Filter Berdasarkan Rentang Tanggal
- Pilih "Dari Tanggal" untuk menentukan tanggal awal
- Pilih "Sampai Tanggal" untuk menentukan tanggal akhir
- Klik tombol "🔍 Filter" untuk menerapkan filter
- Jurnal yang ditampilkan akan sesuai dengan rentang tanggal yang dipilih

### 2. Filter Hanya Tanggal Awal
- Isi hanya "Dari Tanggal"
- Kosongkan "Sampai Tanggal"
- Hasil: Menampilkan jurnal dari tanggal tersebut sampai sekarang

### 3. Filter Hanya Tanggal Akhir
- Kosongkan "Dari Tanggal"
- Isi hanya "Sampai Tanggal"
- Hasil: Menampilkan jurnal dari awal sampai tanggal tersebut

### 4. Kombinasi dengan Filter Lain
Filter tanggal dapat dikombinasikan dengan filter lain:
- Kelas
- Mata Pelajaran
- Guru (untuk Admin/Kepsek)

Contoh: Menampilkan jurnal Kelas 7A, Mata Pelajaran Matematika, dari tanggal 1 Januari 2026 sampai 31 Januari 2026

### 5. Reset Filter
- Klik tombol "🔄 Reset" untuk menghapus semua filter
- Tombol Reset hanya muncul jika ada filter yang aktif

## UI/UX Improvements

### 1. Responsive Layout
- Untuk Admin/Kepsek (ada filter Guru):
  - Baris 1: Kelas (3 col) | Mapel (3 col) | Guru (3 col)
  - Baris 2: Dari Tanggal (3 col) | Sampai Tanggal (3 col) | Tombol (3 col)

- Untuk Guru (tidak ada filter Guru):
  - Baris 1: Kelas (3 col) | Mapel (3 col) | Dari Tanggal (6 col)
  - Baris 2: Sampai Tanggal (6 col) | Tombol (6 col)

### 2. Tombol Reset
- Muncul hanya jika ada filter yang aktif
- Warna outline-secondary untuk membedakan dari tombol Filter
- Icon 🔄 untuk indikasi reset

### 3. Auto-submit untuk Dropdown
- Dropdown Kelas, Mapel, dan Guru tetap auto-submit saat berubah
- Input tanggal tidak auto-submit (user harus klik tombol Filter)
- Ini memberikan kontrol lebih baik untuk memilih rentang tanggal

## Technical Details

### Query Performance
- Filter tanggal menggunakan operator `>=` dan `<=` untuk efisiensi
- Index pada kolom `date` di tabel `teaching_journals` akan meningkatkan performa
- Query tetap menggunakan `ORDER BY date DESC` untuk menampilkan jurnal terbaru terlebih dahulu

### Data Validation
- Input type="date" menggunakan HTML5 date picker
- Browser akan otomatis validasi format tanggal
- Server-side validation tidak diperlukan karena query menggunakan prepared statement

### Backward Compatibility
- Filter tanggal bersifat opsional
- Jika tidak diisi, query akan menampilkan semua jurnal (sesuai filter lain)
- Tidak ada breaking changes pada fitur existing

## Testing Checklist

### ✅ Functional Testing
- [x] Filter dengan "Dari Tanggal" saja
- [x] Filter dengan "Sampai Tanggal" saja
- [x] Filter dengan rentang tanggal lengkap
- [x] Kombinasi filter tanggal dengan filter Kelas
- [x] Kombinasi filter tanggal dengan filter Mapel
- [x] Kombinasi filter tanggal dengan filter Guru (Admin/Kepsek)
- [x] Tombol Reset menghapus semua filter
- [x] Tombol Reset hanya muncul jika ada filter aktif

### ✅ UI/UX Testing
- [x] Layout responsive untuk Admin/Kepsek
- [x] Layout responsive untuk Guru
- [x] Date picker berfungsi dengan baik
- [x] Tombol Filter dan Reset terlihat jelas
- [x] Form tetap user-friendly dengan banyak filter

### ✅ Edge Cases
- [x] Tanggal awal lebih besar dari tanggal akhir (tetap menampilkan hasil kosong)
- [x] Tanggal di masa depan (menampilkan hasil kosong)
- [x] Tanggal sangat lama di masa lalu (menampilkan hasil sesuai data)

## Files Modified
1. `app/Controllers/Admin/TeachingJournalController.php` - Menambahkan parameter date_from dan date_to
2. `app/Models/TeachingJournalModel.php` - Menambahkan filter tanggal di query
3. `app/Views/admin/teaching_journal/index.php` - Menambahkan input filter tanggal dan tombol reset

## Benefits
1. **Pencarian Lebih Spesifik**: User dapat mencari jurnal berdasarkan periode tertentu
2. **Laporan Periodik**: Memudahkan pembuatan laporan bulanan/semester
3. **Audit Trail**: Memudahkan tracking jurnal pada periode tertentu
4. **User Experience**: Interface yang intuitif dengan date picker HTML5
5. **Fleksibilitas**: Dapat dikombinasikan dengan filter lain

## Future Enhancements
1. Preset periode (Hari Ini, Minggu Ini, Bulan Ini, Semester Ini)
2. Export jurnal berdasarkan filter tanggal
3. Statistik jurnal per periode
4. Reminder untuk guru yang belum mengisi jurnal dalam periode tertentu
