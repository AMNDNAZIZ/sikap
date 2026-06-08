# Perbaikan Error 403 pada Peta Sebaran Siswa

## Masalah
Pada halaman peta sebaran siswa (`/admin/student-map`), muncul error "403r access blocked" di area peta dengan pesan:
```
Referer is required by tile usage policy of OpenStreetMap's volunteer-run servers
```

Halaman berhasil dimuat tetapi tiles peta tidak muncul dan menampilkan error 403.

## Penyebab
Error disebabkan oleh **kebijakan OpenStreetMap** yang memerlukan HTTP Referer header yang valid:
1. OpenStreetMap tile servers memiliki usage policy yang ketat
2. Request tanpa Referer header yang proper akan diblokir dengan 403
3. Ini adalah proteksi untuk mencegah abuse dari tile servers mereka yang volunteer-run
4. CSP aplikasi juga perlu dikonfigurasi untuk mengizinkan tile providers

## Solusi
Mengganti tile provider dari OpenStreetMap ke **CartoDB** yang tidak memiliki batasan Referer yang ketat.

### 1. Mengganti Tile Provider di View
File: `app/Views/admin/student_map/index.php`

**Sebelum:**
```javascript
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
}).addTo(map);
```

**Sesudah:**
```javascript
// Menggunakan CartoDB Positron (light theme)
L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap contributors © CARTO',
    maxZoom: 19,
    subdomains: 'abcd'
}).addTo(map);
```

### 2. Update CSP Configuration
File: `app/Config/ContentSecurityPolicy.php`

**imageSrc:**
```php
public $imageSrc = [
    'self',
    'data:',
    'blob:',
    '*.tile.openstreetmap.org', // Backup
    '*.basemaps.cartocdn.com', // ✅ Primary - no referer issues
    'server.arcgisonline.com', // Alternative
    'unpkg.com', // Leaflet icons
];
```

**connectSrc:**
```php
public $connectSrc = [
    'self',
    'cdn.jsdelivr.net',
    'cdnjs.cloudflare.com',
    'cdn.ckeditor.com',
    '*.tile.openstreetmap.org', // Backup
    '*.basemaps.cartocdn.com', // ✅ Primary - no referer issues
    'server.arcgisonline.com', // Alternative
];
```

### 3. Alternatif Tile Providers
Dalam kode sudah disediakan 3 pilihan tile provider (tinggal uncomment):

**A. CartoDB Positron (Default - Light Theme)**
- URL: `https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png`
- Kelebihan: Ringan, cepat, tidak ada batasan referer
- Cocok untuk: Tampilan profesional dan clean

**B. CartoDB Voyager (Colored Theme)**
- URL: `https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png`
- Kelebihan: Lebih berwarna, detail lebih jelas
- Cocok untuk: Tampilan yang lebih menarik

**C. Esri World Street Map**
- URL: `https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}`
- Kelebihan: Kualitas tinggi, detail sangat baik
- Cocok untuk: Tampilan profesional dengan detail maksimal

## Mengapa Tidak Menggunakan OpenStreetMap Langsung?

OpenStreetMap memiliki **Tile Usage Policy** yang ketat:
1. **Referer Header Required**: Semua request harus menyertakan valid HTTP Referer
2. **Rate Limiting**: Dibatasi untuk mencegah abuse
3. **Volunteer-Run**: Server dijalankan oleh volunteer, bukan commercial service
4. **Heavy Usage Discouraged**: Untuk aplikasi production, disarankan menggunakan tile provider komersial atau self-hosted

Referensi: https://operations.osmfoundation.org/policies/tiles/

**Solusi yang Digunakan:**
- Menggunakan CartoDB yang menggunakan data OpenStreetMap tetapi dengan infrastructure yang lebih robust
- CartoDB tidak memiliki batasan Referer yang ketat
- Gratis untuk penggunaan reasonable
- Lebih stabil dan cepat untuk production use

## Cara Kerja Peta Sebaran Siswa

### Controller (`app/Controllers/Admin/StudentMap.php`)
1. **index()** - Menampilkan halaman peta dengan filter
2. **getData()** - API endpoint yang mengembalikan data siswa dalam format JSON
3. **statistics()** - Halaman statistik sebaran siswa
4. **export()** - Export data ke CSV

### View (`app/Views/admin/student_map/index.php`)
1. Memuat library Leaflet.js dan MarkerCluster
2. Membuat peta dengan center di koordinat sekolah
3. Memanggil API `/admin/student-map/get-data` untuk mendapatkan data siswa
4. Menampilkan marker untuk sekolah (ikon merah 🏫)
5. Menampilkan marker untuk siswa (biru 👦 untuk laki-laki, pink 👧 untuk perempuan)
6. Mengelompokkan marker siswa dengan MarkerCluster
7. Menampilkan popup dengan detail siswa dan tombol rute ke Google Maps

### Fitur
- **Filter**: Tingkat, Kelas, Jarak Maksimal, Status Koordinat
- **Statistik**: Total siswa, sudah/belum diisi koordinat, jarak rata-rata
- **Marker Clustering**: Mengelompokkan marker yang berdekatan
- **Popup Detail**: Menampilkan info siswa dan link rute
- **Tabel Siswa Belum Isi**: Daftar siswa yang belum mengisi koordinat
- **Export Excel**: Download data sebaran siswa dalam format CSV

### Permission
Hanya dapat diakses oleh:
- Admin (role_id = 1)
- Kepala Sekolah (role_id = 2)
- Guru (role_id = 3)

## Testing
1. Login sebagai Admin/Kepsek/Guru
2. Buka halaman `/admin/student-map`
3. Peta seharusnya muncul dengan tiles OpenStreetMap
4. Marker sekolah (merah) dan siswa (biru/pink) seharusnya muncul
5. Klik marker untuk melihat detail
6. Gunakan filter untuk menyaring data
7. Cek browser console - tidak ada error CSP

## Catatan Keamanan
- CSP tetap aktif untuk melindungi dari XSS dan injection attacks
- Hanya domain yang diperlukan yang ditambahkan ke whitelist
- OpenStreetMap adalah service publik yang aman dan terpercaya
- Unpkg.com digunakan untuk CDN Leaflet yang juga terpercaya

## File yang Dimodifikasi
1. `app/Config/ContentSecurityPolicy.php` - Menambahkan CartoDB dan Esri ke whitelist CSP
2. `app/Views/admin/student_map/index.php` - Peta sebaran siswa (admin)
3. `app/Views/admin/school/index.php` - Pengaturan lokasi sekolah (admin/kepsek)
4. `app/Views/siswa/location/index.php` - Pengaturan lokasi siswa

## Lokasi Peta dalam Aplikasi

### 1. Peta Sebaran Siswa (`/admin/student-map`)
- **Akses**: Admin, Kepsek, Guru
- **Fungsi**: Menampilkan lokasi rumah semua siswa
- **Fitur**: Filter kelas, statistik jarak, export
- **Status**: ✅ Diperbaiki

### 2. Pengaturan Lokasi Sekolah (`/admin/school`)
- **Akses**: Admin, Kepsek
- **Fungsi**: Set koordinat lokasi sekolah
- **Fitur**: Geocoder search, drag marker
- **Status**: ✅ Diperbaiki
- **Catatan**: **Peta ini yang muncul di dashboard kepsek**

### 3. Pengaturan Lokasi Siswa (`/siswa/location`)
- **Akses**: Siswa
- **Fungsi**: Siswa mengisi koordinat rumah mereka
- **Fitur**: Geocoder search, drag marker, auto-detect location
- **Status**: ✅ Diperbaiki

## Referensi
- OpenStreetMap: https://www.openstreetmap.org/
- Leaflet.js: https://leafletjs.com/
- MarkerCluster: https://github.com/Leaflet/Leaflet.markercluster
- CSP: https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
