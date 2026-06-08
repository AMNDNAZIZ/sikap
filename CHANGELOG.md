# Changelog

All notable changes to SIAKAD project.

## [2.0.0] - 2026-02-21

### Added
- **Modul Peta Sebaran Siswa**
  - Form input lokasi untuk siswa (`/siswa/location`)
  - Peta interaktif dengan Leaflet.js untuk admin/kepsek/guru
  - Marker clustering untuk performa optimal
  - Filter berdasarkan tingkat, kelas, jarak, dan status
  - Statistik sebaran dengan Chart.js
  - Export data ke Excel (CSV)
  - Perhitungan jarak otomatis dari sekolah
  - Geolocation API untuk deteksi lokasi otomatis
  - Geocoder untuk pencarian alamat

- **Security Enhancements**
  - Password pattern system untuk distribusi massal (guru[NIP], siswa[NIS], ortu[NIS])
  - Session security dengan Database Handler
  - IP address matching untuk session
  - Session regeneration on login
  - XSS protection improvements
  - CSRF protection enhancements

- **Menu Navigation**
  - Menu "Peta Sebaran Siswa" untuk Admin, Kepsek, Guru
  - Menu "Lokasi Rumah Saya" untuk Siswa
  - Auto-expand menu saat halaman aktif

### Changed
- Migrasi dari TinyMCE ke CKEditor 5
- Session handler dari FileHandler ke DatabaseHandler
- Email system: Guru (real email), Siswa/Ortu (auto-generate)
- Improved CBT anti-cheat system
- Performance optimization untuk query database

### Fixed
- Error "Class 'App\Models\GradeModel' not found" di StudentMap controller
- Error "Undefined array key 'role_id'" di layout dan Location controller
- Error "Table 'ci_sessions' doesn't exist"
- XSS vulnerability di grades view
- Spacing issues di CKEditor
- 404 error untuk CKEditor CSS
- Auto-translate prevention
- Cache key errors

### Removed
- TinyMCE editor dan dependencies
- File dokumentasi development (70+ file .md)
- File SQL migrasi yang sudah dijalankan
- File testing dan monitoring scripts
- File PHP debug dan utilities
- File backup yang tidak diperlukan

## [1.0.0] - 2024

### Initial Release
- Manajemen Siswa & Guru
- Manajemen Kelas & Mata Pelajaran
- Absensi Siswa
- Penilaian & Rapor
- CBT (Computer Based Test)
- PWA Support
- Multi-role access
- Pengumuman & Chat

---

**Format:** [Major.Minor.Patch]
- **Major:** Breaking changes
- **Minor:** New features (backward compatible)
- **Patch:** Bug fixes
