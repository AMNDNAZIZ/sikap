# SIAKAD - Sistem Informasi Akademik

Sistem Informasi Akademik berbasis web untuk mengelola data sekolah, siswa, guru, dan proses pembelajaran.

## 🚀 Fitur Utama

### 📚 Manajemen Akademik
- Manajemen Siswa & Guru
- Manajemen Kelas & Mata Pelajaran
- Penjadwalan & Agenda
- Absensi Siswa
- Penilaian & Rapor

### 📝 CBT (Computer Based Test)
- Bank Soal (Pilihan Ganda & Essay)
- Ujian Online
- Anti-Cheat System
- Auto-Grading
- Analisis Hasil Ujian

### 🗺️ Peta Sebaran Siswa
- Visualisasi lokasi rumah siswa
- Perhitungan jarak dari sekolah
- Statistik sebaran geografis
- Export data ke Excel

### 🔐 Keamanan
- Password Pattern untuk distribusi massal
- Session Security (Database Handler)
- XSS Protection
- CSRF Protection
- License Management dengan Hardware Binding
- Anti-Tampering Protection
- Encrypted Configuration Storage
- File Integrity Verification

### 📱 Fitur Tambahan
- PWA (Progressive Web App)
- Responsive Design
- Multi-Role Access (Admin, Kepsek, Guru, Siswa, Orang Tua)
- Pengumuman & Notifikasi
- Chat Kelas

## 💻 Teknologi

- **Framework:** CodeIgniter 4
- **Database:** MySQL/MariaDB
- **Frontend:** Bootstrap 5, Leaflet.js, Chart.js
- **Editor:** CKEditor 5
- **Maps:** OpenStreetMap

## 📋 Persyaratan Sistem

- PHP 8.0 atau lebih tinggi
- MySQL 5.7+ atau MariaDB 10.3+
- Apache/Nginx Web Server
- Composer
- Extension PHP: intl, mbstring, json, mysqlnd

## 🔧 Instalasi

### 1. Clone/Download Repository
```bash
git clone [repository-url]
cd siakad
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Konfigurasi Database
```bash
# Copy file .env
cp env .env

# Edit .env dan sesuaikan konfigurasi database
database.default.hostname = localhost
database.default.database = siakad
database.default.username = root
database.default.password = 
```

### 4. Import Database
```bash
# Import file SQL ke database
mysql -u root -p siakad < database.sql
```

### 5. Jalankan Aplikasi
```bash
# Development
php spark serve

# Production: Akses via web server (Apache/Nginx)
http://localhost/siakad
```

## 👥 Default Login

### Admin
- Username: `admin`
- Password: `admin123`

### Guru
- Username: `guru[NIP]`
- Password: `guru[NIP]`
- Contoh: `guru123456` / `guru123456`

### Siswa
- Username: `siswa[NIS]`
- Password: `siswa[NIS]`
- Contoh: `siswa001` / `siswa001`

**⚠️ PENTING:** Semua user WAJIB mengganti password saat login pertama kali!

## 📖 Dokumentasi

### 📚 Dokumentasi Lengkap

Dokumentasi lengkap tersedia di folder `docs/`:

- **[Migrasi Database](docs/migrasi/MULAI_DISINI.md)** - Panduan lengkap migrasi database
- **[Perbaikan Bug](docs/perbaikan/)** - Dokumentasi perbaikan bug dan masalah
- **[Sistem Changelog](docs/changelog/)** - Cara update changelog otomatis
- **[Keamanan Lisensi](docs/keamanan/)** - Analisis keamanan & rekomendasi perbaikan

### Quick Links

- 🚀 **[Quick Start Migrasi](docs/migrasi/QUICK_START_MIGRASI.md)** - Migrasi database dalam 5 menit
- 🐛 **[Quick Fix Hosting](docs/perbaikan/QUICK_FIX_HOSTING.md)** - Perbaikan masalah hosting
- 📝 **[Update Changelog](docs/changelog/QUICK_GUIDE_UPDATE_CHANGELOG.md)** - Update changelog otomatis
- 🔐 **[Analisis Keamanan](docs/keamanan/README.md)** - Analisis & perbaikan sistem lisensi

### Struktur Folder
```
siakad/
├── app/
│   ├── Controllers/     # Controller files
│   ├── Models/          # Model files
│   ├── Views/           # View files
│   ├── Config/          # Configuration files
│   ├── Filters/         # Middleware filters
│   ├── Helpers/         # Helper functions
│   └── Libraries/       # Custom libraries
├── public/
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── uploads/        # User uploads
├── writable/
│   ├── cache/          # Cache files
│   ├── logs/           # Log files
│   └── session/        # Session files
└── vendor/             # Composer dependencies
```

### Konfigurasi Penting

#### Session (Database Handler)
Session disimpan di database untuk keamanan lebih baik:
```php
// app/Config/Session.php
public string $driver = 'CodeIgniter\Session\Handlers\DatabaseHandler';
```

#### Email System
- Guru: Email REAL (input manual)
- Siswa: Auto-generate `[NIS]@siswa.local`
- Orang Tua: Auto-generate `[NIS]@ortu.com`

#### Password Pattern
- Guru: `guru[NIP]`
- Siswa: `siswa[NIS]`
- Orang Tua: `ortu[NIS]`

## 🗺️ Modul Peta Sebaran Siswa

### Untuk Siswa
1. Login sebagai siswa
2. Klik menu "Lokasi Rumah Saya" 📍
3. Klik "Deteksi Lokasi Otomatis" atau drag marker di peta
4. Klik "Simpan Lokasi"

### Untuk Admin/Kepsek/Guru
1. Login sesuai role
2. Klik menu "Peta Sebaran Siswa" 🗺️
3. Lihat visualisasi lokasi siswa di peta
4. Gunakan filter untuk menyaring data
5. Klik "Statistik" untuk melihat analisis
6. Klik "Export Excel" untuk download data

## 🔒 Keamanan

### Best Practices
1. Ganti semua password default
2. Gunakan HTTPS di production
3. Backup database secara berkala
4. Update framework dan dependencies
5. Monitor log files secara rutin

### Session Security
- Session disimpan di database (bukan file)
- IP Address matching enabled
- Session regeneration on login
- Secure cookie settings

## 🐛 Troubleshooting

### Error: Table 'ci_sessions' doesn't exist
```sql
CREATE TABLE IF NOT EXISTS `ci_sessions` (
    `id` varchar(128) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `data` blob NOT NULL,
    KEY `ci_sessions_timestamp` (`timestamp`),
    PRIMARY KEY (`id`, `ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Error: Undefined array key "role_id"
Pastikan session user memiliki struktur lengkap. Logout dan login kembali.

### Peta tidak menampilkan marker
1. Pastikan siswa sudah mengisi koordinat
2. Refresh halaman peta admin (F5)
3. Cek filter yang aktif

## 📝 Changelog

### Version 2.1 (Latest) - Security Update
- ✅ Anti-Tampering Protection
- ✅ Encrypted Config Storage
- ✅ Hardware Signature Binding
- ✅ Enhanced License Security (6.5/10 → 8.5/10)
- ✅ Security Tools for Maintenance
- ✅ Auto-renewal License System

### Version 2.0
- ✅ Modul Peta Sebaran Siswa
- ✅ Session Security (Database Handler)
- ✅ Password Pattern System
- ✅ XSS & CSRF Protection
- ✅ CBT Anti-Cheat Improvements
- ✅ CKEditor 5 Migration
- ✅ Bug Fixes & Performance Optimization

## 📄 License

[Sesuaikan dengan lisensi Anda]

## 👨‍💻 Developer

Dikembangkan dengan ❤️ untuk pendidikan Indonesia

## 📞 Support

Untuk bantuan dan pertanyaan:
- Email: [email-support]
- Website: [website-url]
- Documentation: [docs-url]

---

**© 2024 SIAKAD. All rights reserved.**
