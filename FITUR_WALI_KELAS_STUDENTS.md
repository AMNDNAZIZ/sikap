# Fitur Data Siswa Wali Kelas

## Deskripsi
Fitur ini memungkinkan guru yang menjadi wali kelas untuk melihat dan mengelola data siswa yang ada di kelasnya dengan tampilan yang terorganisir dan user-friendly.

## Akses
- **Role**: Guru (Role ID: 3)
- **URL**: `/admin/wali-kelas-students`
- **Persyaratan**: Guru harus memiliki kelas yang diwalikan

## Fitur Utama

### 1. Halaman Daftar Siswa (`/admin/wali-kelas-students`)

#### Informasi Header
- Nama kelas yang diwalikan
- Foto dan informasi wali kelas (guru)
- Statistik siswa:
  - Total siswa
  - Jumlah siswa laki-laki
  - Jumlah siswa perempuan
  - Tombol akses cepat ke halaman nilai

#### Pencarian dan Filter
- **Search**: Cari siswa berdasarkan nama
- **Filter Gender**: Filter siswa berdasarkan jenis kelamin (Laki-laki/Perempuan)
- Filter bekerja secara real-time di client-side

#### Kartu Siswa
Setiap siswa ditampilkan dalam kartu yang berisi:
- Foto profil siswa (atau placeholder jika tidak ada)
- Nama siswa
- NISN
- Gender
- Tanggal lahir
- Nomor telepon (jika ada)
- Email (jika ada)
- Tombol aksi:
  - **Detail**: Lihat detail lengkap siswa
  - **Nilai**: Akses langsung ke halaman nilai siswa

#### Statistik
- Total siswa aktif di kelas
- Breakdown gender (laki-laki/perempuan)
- Tombol akses ke halaman nilai kelas

### 2. Halaman Detail Siswa (`/admin/wali-kelas-students/detail/{student_id}`)

#### Informasi Pribadi
- Nama lengkap
- NISN
- Gender
- Tanggal lahir

#### Informasi Kontak
- Nomor telepon (clickable untuk call)
- Email (clickable untuk email)
- Alamat

#### Informasi Akademik
- Kelas
- Status (Aktif)

#### Aksi Cepat
- **Lihat Nilai**: Redirect ke `/admin/grades?student_id={student_id}`
- **Presensi**: Lihat data presensi siswa
- **Edit Data**: Edit data siswa

#### Informasi Wali Kelas
- Foto wali kelas
- Nama wali kelas
- NIP
- Nomor telepon

## Data yang Ditampilkan

### Dari Tabel `student_records`
- `student_id`
- `class_id`
- `nisn`
- `status`

### Dari Tabel `students`
- `name` (student_name)
- `gender`
- `birth_date`
- `address`
- `photo`

### Dari Tabel `users`
- `email`

### Dari Tabel `teachers`
- `name`
- `nip`
- `photo`

## Integrasi dengan Halaman Lain

### Nilai
- Link ke `/admin/grades?class_id={class_id}` untuk melihat nilai seluruh kelas
- Link ke `/admin/grades?student_id={student_id}` untuk melihat nilai siswa tertentu

### Presensi
- Link ke `/admin/attendance?student_id={student_id}` untuk melihat presensi siswa

### Edit Data Siswa
- Link ke `/admin/students/edit/{student_id}` untuk edit data siswa

## Keamanan
- Hanya guru dengan role ID 3 yang dapat mengakses
- Guru hanya dapat melihat siswa dari kelas yang diwalikannya
- Validasi dilakukan di controller untuk memastikan guru hanya akses kelas miliknya

## File yang Terlibat

### Controller
- `app/Controllers/Admin/WaliKelasStudents.php`
  - Method `index()`: Tampilkan daftar siswa
  - Method `detail($studentId)`: Tampilkan detail siswa

### Views
- `app/Views/admin/wali_kelas_students.php`: Halaman daftar siswa
- `app/Views/admin/wali_kelas_student_detail.php`: Halaman detail siswa

### Routes
- `app/Config/Routes.php`: Route untuk wali kelas students

### Dashboard
- `app/Views/dashboard/guru_fixed.php`: Link ke halaman data siswa dari card wali kelas

## Styling
- Menggunakan Bootstrap 5
- Gradient backgrounds untuk header
- Card-based layout untuk siswa
- Responsive design untuk mobile

## Fitur Tambahan

### Search & Filter (Client-side)
- Menggunakan JavaScript untuk filter real-time
- Tidak memerlukan reload halaman
- Kombinasi search dan filter gender

### Foto Profil
- Foto guru dari `/uploads/teachers/{photo}`
- Foto siswa dari `/uploads/students/{photo}`
- Placeholder icon jika foto tidak tersedia

### Responsive Design
- Mobile-friendly layout
- Adjustable untuk berbagai ukuran layar
- Touch-friendly buttons dan controls

## Pengembangan Lebih Lanjut

Fitur yang dapat ditambahkan di masa depan:
1. Export data siswa ke Excel/PDF
2. Print daftar siswa
3. Bulk actions (edit multiple students)
4. Catatan siswa dari wali kelas
5. Riwayat komunikasi dengan orang tua
6. Grafik performa akademik siswa
7. Integrasi dengan sistem pesan untuk komunikasi dengan orang tua
