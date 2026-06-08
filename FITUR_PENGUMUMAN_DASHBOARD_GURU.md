# Fitur Pengumuman Terbaru di Dashboard Guru

## Deskripsi
Menampilkan pengumuman terbaru dari Admin/Kepala Sekolah yang ditujukan untuk guru di bagian atas "Akses Cepat" pada dashboard guru.

## Lokasi
Dashboard Guru → Kolom Kanan → Di atas "Akses Cepat Guru"

## Fitur

### 1. **Tampilan Pengumuman**
   - Card dengan gradient header (ungu-biru)
   - Icon megaphone untuk menarik perhatian
   - Judul pengumuman yang jelas
   - Informasi pembuat (Admin/Kepsek)
   - Timestamp relatif (contoh: "2 jam yang lalu", "3 hari yang lalu")

### 2. **Konten Pengumuman**
   - Menampilkan isi pengumuman (maksimal 300 karakter)
   - Auto-truncate dengan "..." jika terlalu panjang
   - Scrollable jika konten panjang
   - Custom scrollbar yang stylish

### 3. **Informasi Tambahan**
   - Badge target audience (Semua, Guru, Siswa, Orang Tua)
   - Tombol "Lihat Semua" untuk ke halaman pengumuman lengkap
   - Hanya menampilkan pengumuman dari Admin (role_id = 1) atau Kepsek (role_id = 2)

### 4. **Filter Otomatis**
   - Hanya menampilkan pengumuman yang target-nya:
     - Mengandung "guru"
     - Atau mengandung "all" (untuk semua)
   - Diurutkan berdasarkan tanggal terbaru

## Logika Query

```php
$latestAnnouncement = $db->table('announcements a')
    ->select('a.*, u.fullname as creator_name')
    ->join('users u', 'u.id = a.created_by', 'left')
    ->where('u.role_id IN (1, 2)', null, false) // Admin atau Kepsek
    ->groupStart()
    ->like('a.target', 'guru', 'both')
    ->orLike('a.target', 'all', 'both')
    ->groupEnd()
    ->orderBy('a.created_at', 'DESC')
    ->limit(1)
    ->get()
    ->getRowArray();
```

## Tampilan

### Desktop
- Card penuh dengan padding yang nyaman
- Konten maksimal 200px tinggi dengan scroll
- Gradient header yang menarik

### Mobile
- Responsive layout
- Konten maksimal 150px tinggi
- Font size yang disesuaikan

## Styling

### Warna
- Header: Gradient ungu-biru (#667eea → #764ba2)
- Badge: Primary blue
- Text: Dark gray (#495057)

### Animasi
- Smooth scroll pada konten panjang
- Hover effect pada tombol "Lihat Semua"

## Kondisi Tampilan

### Jika Ada Pengumuman
- Menampilkan card pengumuman lengkap dengan semua informasi

### Jika Tidak Ada Pengumuman
- Card tidak ditampilkan
- Langsung menampilkan "Akses Cepat Guru"

## Timestamp Relatif

Sistem menampilkan waktu relatif:
- **< 1 jam**: "X menit yang lalu"
- **< 24 jam**: "X jam yang lalu"
- **< 7 hari**: "X hari yang lalu"
- **≥ 7 hari**: Tanggal lengkap (dd MMM YYYY)

## Integrasi

### File yang Dimodifikasi
1. **app/Controllers/Dashboard.php**
   - Menambahkan query untuk mengambil pengumuman terbaru
   - Filter berdasarkan role pembuat dan target

2. **app/Views/dashboard/guru_fixed.php**
   - Menambahkan section pengumuman di kolom kanan
   - Styling CSS untuk tampilan yang menarik

## Manfaat

✅ Guru langsung melihat pengumuman penting dari pimpinan
✅ Meningkatkan komunikasi antara Admin/Kepsek dengan Guru
✅ Informasi penting tidak terlewat
✅ Tampilan yang menarik dan mudah dibaca
✅ Responsive untuk semua device

## Testing

### Test 1: Pengumuman Muncul
1. Login sebagai Admin/Kepsek
2. Buat pengumuman dengan target "Guru" atau "Semua"
3. Login sebagai Guru
4. Cek dashboard → Pengumuman harus muncul

### Test 2: Filter Target
1. Buat pengumuman dengan target "Siswa" saja
2. Login sebagai Guru
3. Pengumuman tidak muncul (karena tidak untuk guru)

### Test 3: Filter Pembuat
1. Login sebagai Guru
2. Buat pengumuman
3. Login sebagai Guru lain
4. Pengumuman tidak muncul (karena bukan dari Admin/Kepsek)

### Test 4: Timestamp Relatif
1. Buat pengumuman baru
2. Cek dashboard → Harus muncul "X menit yang lalu"
3. Tunggu beberapa jam
4. Refresh → Harus muncul "X jam yang lalu"

## Future Enhancement

Potensi pengembangan:
- Notifikasi badge untuk pengumuman belum dibaca
- Mark as read functionality
- Filter pengumuman berdasarkan kategori
- Push notification untuk pengumuman urgent
- Arsip pengumuman lama

## Changelog

- **2026-04-21**: Implementasi fitur pengumuman terbaru di dashboard guru
  - Menampilkan pengumuman dari Admin/Kepsek
  - Filter otomatis berdasarkan target
  - Timestamp relatif
  - Responsive design
