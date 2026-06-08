# Update Detail Siswa Wali Kelas - Sesuai Format Edit Siswa

## Perubahan yang Dilakukan

Halaman detail siswa wali kelas telah diperbarui untuk menampilkan data yang sama lengkap seperti halaman edit siswa (`/admin/students/edit/...`), namun dalam mode **read-only** (hanya tampil, tidak bisa edit).

## Struktur Data yang Ditampilkan

### 1. Header Section
- Foto siswa (100x100px)
- Nama lengkap siswa
- Status siswa (Aktif/Nonaktif/Dropout/Lulus) dengan badge warna

### 2. Tab Pribadi (Personal Information)
Data pribadi siswa yang ditampilkan:
- Nama Lengkap
- NISN (Nomor Induk Siswa Nasional)
- NIS (Nomor Induk Sekolah)
- NIK Siswa
- Jenis Kelamin (Laki-laki/Perempuan)
- Agama
- Tempat Lahir
- Tanggal Lahir (format: dd F yyyy)
- Kewarganegaraan
- Anak ke (urutan anak)
- Jenis Pendaftaran (Siswa Baru/Pindahan)
- Tanggal Masuk
- Diterima di Kelas (Permanen)
- Kelas Saat Ini (Rombel)
- Kebutuhan Khusus

### 3. Tab Alamat (Address Information)
Data alamat siswa:
- Alamat Lengkap
- Jenis Tempat Tinggal
- Transportasi
- Jarak ke Sekolah
- Koordinat (dengan link ke Google Maps jika ada)

### 4. Tab Keluarga (Family Information)
Data keluarga siswa:

**Data Ayah:**
- Nama Ayah
- NIK Ayah
- Tahun Lahir Ayah
- Pendidikan Ayah
- Pekerjaan Ayah
- Penghasilan Ayah

**Data Ibu:**
- Nama Ibu
- NIK Ibu
- Tahun Lahir Ibu
- Pendidikan Ibu
- Pekerjaan Ibu
- Penghasilan Ibu

**Data Wali:**
- Nama Wali
- Pendidikan Wali
- Pekerjaan Wali
- Penghasilan Wali

### 5. Tab Aksi (Actions)
Tombol aksi cepat:
- **Edit Data Siswa** → Link ke `/admin/students/edit/{id}`
- **Lihat Nilai** → Link ke `/admin/grades?student_id={id}`
- **Presensi** → Link ke `/admin/attendance?student_id={id}`
- **Kembali ke Daftar** → Kembali ke halaman daftar siswa

**Informasi Wali Kelas:**
- Foto wali kelas
- Nama wali kelas
- NIP wali kelas
- Nomor telepon wali kelas (jika ada)

## Perubahan di Controller

File: `app/Controllers/Admin/WaliKelasStudents.php`

### Method `detail($studentId)`
- Mengambil semua kolom dari tabel `students` (bukan hanya kolom tertentu)
- Mengambil email dari tabel `users`
- Mengambil daftar semua kelas untuk referensi
- Mengirim data `record` (alias untuk student_records) ke view

```php
$student = $db->table('student_records sr')
    ->select('sr.*, s.*, u.email')
    ->join('students s', 's.id = sr.student_id')
    ->join('users u', 'u.id = s.user_id', 'left')
    ->where('sr.student_id', $studentId)
    ->where('sr.class_id', $waliClass['id'])
    ->where('sr.status', 'aktif')
    ->get()
    ->getRowArray();
```

## Perubahan di View

File: `app/Views/admin/wali_kelas_student_detail.php`

### Struktur Layout
- Menggunakan card dengan header
- Menggunakan Bootstrap tabs untuk navigasi antar section
- Menggunakan `form-control-plaintext` untuk tampilan read-only
- Styling konsisten dengan halaman edit siswa

### Fitur Khusus
- **Koordinat dengan Google Maps**: Jika ada latitude dan longitude, ditampilkan sebagai link ke Google Maps
- **Status Badge**: Warna berbeda untuk setiap status (Aktif=hijau, Nonaktif=kuning, Dropout=merah, Lulus=biru)
- **Foto Responsive**: Foto siswa dan wali kelas ditampilkan dengan ukuran yang sesuai
- **Placeholder Icon**: Icon placeholder jika foto tidak tersedia

## Data yang Diambil dari Database

### Dari Tabel `student_records`
- `student_id`
- `class_id`
- `status`

### Dari Tabel `students`
Semua kolom:
- `nisn`, `nis`, `name`, `nik`, `gender`, `birth_place`, `birth_date`
- `child_order`, `religion`, `nationality`, `admission_date`, `admission_class`
- `registration_type`, `photo`, `address`, `residence_type`, `transportation`
- `distance`, `latitude`, `longitude`, `special_needs`
- `father_name`, `father_nik`, `father_birth_year`, `father_education`, `father_job`, `father_income`
- `mother_name`, `mother_nik`, `mother_birth_year`, `mother_education`, `mother_job`, `mother_income`
- `guardian_name`, `guardian_education`, `guardian_job`, `guardian_income`

### Dari Tabel `users`
- `email`

### Dari Tabel `teachers`
- `name`, `nip`, `phone`, `photo`

## Keamanan

- Hanya guru dengan role ID 3 yang dapat akses
- Guru hanya dapat melihat siswa dari kelas yang diwalikannya
- Data ditampilkan dalam mode read-only (tidak bisa diedit)
- Untuk edit data, user harus klik tombol "Edit Data Siswa" yang akan redirect ke halaman edit resmi

## Responsiveness

- Desktop: Semua kolom ditampilkan normal
- Tablet: Layout menyesuaikan dengan ukuran layar
- Mobile: Font size berkurang, padding berkurang, tetap readable

## Perbandingan dengan Halaman Edit Siswa

| Aspek | Detail Wali Kelas | Edit Siswa |
|-------|-------------------|-----------|
| Mode | Read-only | Editable |
| Akses | Guru wali kelas | Admin/Kepsek |
| Tab | 5 tab (+ Aksi) | 3 tab |
| Edit | Tidak bisa | Bisa |
| Foto | Hanya tampil | Bisa upload |
| Status | Hanya tampil | Bisa ubah |

## File yang Diubah

1. `app/Controllers/Admin/WaliKelasStudents.php`
   - Update method `detail()` untuk mengambil semua data siswa

2. `app/Views/admin/wali_kelas_student_detail.php`
   - Recreate dengan struktur tab seperti edit siswa
   - Menampilkan semua data dalam mode read-only
   - Tambah tab Aksi dengan tombol-tombol penting

## Testing Checklist

- ✅ Halaman detail terbuka dengan benar
- ✅ Semua data siswa ditampilkan
- ✅ Tab navigasi berfungsi
- ✅ Link aksi berfungsi (Edit, Nilai, Presensi)
- ✅ Foto siswa dan wali kelas ditampilkan
- ✅ Koordinat Google Maps berfungsi
- ✅ Responsive di mobile
- ✅ Hanya guru wali kelas yang bisa akses
