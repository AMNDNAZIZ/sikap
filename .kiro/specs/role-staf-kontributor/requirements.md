# Requirements Document

## Introduction

Fitur ini menambahkan dua role baru pada sistem SIAKAD (Sistem Informasi Kelas Absensi dan Penilaian) berbasis CodeIgniter 4:

1. **Staf** (role_id 7) — Role baru untuk pengelola data induk dan SDM sekolah. Staf dapat mengakses fitur administrasi data master (identitas sekolah, tahun ajaran, hari libur, mata pelajaran, data guru & staff, data siswa, kelas, penempatan siswa), namun tidak dapat mengakses fitur akademik seperti nilai, absensi, keuangan, atau CBT.

2. **Kontributor** (role_id 6) — Role yang sudah ada, namun aksesnya perlu dikaji ulang dan dibatasi hanya pada pengelolaan konten website (CMS): berita/artikel, slider, fasilitas, dokumentasi, dan tautan. Kontributor tidak boleh mengakses fitur akademik, SDM, atau sistem lainnya.

Saat ini sistem memiliki role: Admin (1), Kepsek (2), Guru (3), Orang Tua (4), Siswa (5), Kontributor (6). Filter auth diterapkan per-route di `Routes.php` menggunakan format `auth:role_id`.

---

## Glossary

- **SIAKAD**: Sistem Informasi Kelas Absensi dan Penilaian — aplikasi berbasis CodeIgniter 4 yang digunakan sekolah.
- **Role**: Peran pengguna dalam sistem yang menentukan hak akses, disimpan sebagai `role_id` di tabel `users`.
- **Auth_Filter**: Filter CodeIgniter 4 yang memvalidasi `role_id` dari sesi pengguna sebelum mengizinkan akses ke route tertentu.
- **Staf**: Pengguna dengan role_id 7 yang bertugas mengelola data induk dan SDM sekolah.
- **Kontributor**: Pengguna dengan role_id 6 yang bertugas mengelola konten website (CMS).
- **Data_Induk**: Kumpulan data master sekolah meliputi identitas sekolah, tahun ajaran, hari libur, mata pelajaran, mapel master, dan mapping kurikulum.
- **SDM**: Sumber Daya Manusia — mencakup data guru, staff, siswa, kelas, dan penempatan siswa.
- **CMS**: Content Management System — fitur pengelolaan konten website publik sekolah (slider, artikel, fasilitas, dokumentasi, tautan).
- **Dashboard**: Halaman utama setelah login yang menampilkan ringkasan informasi sesuai role.
- **Sidebar**: Menu navigasi samping yang ditampilkan sesuai role pengguna yang sedang login.
- **Routes_php**: File `app/Config/Routes.php` yang mendefinisikan semua route beserta filter auth-nya.

---

## Requirements

### Requirement 1: Penambahan Role Staf ke Database dan Sistem Auth

**User Story:** Sebagai Admin, saya ingin menambahkan role Staf ke sistem, sehingga pengguna dengan peran staf administrasi dapat login dan mendapatkan akses yang sesuai.

#### Acceptance Criteria

1. THE SIAKAD SHALL menyimpan role Staf dengan `role_id = 7` pada tabel referensi role di database.
2. WHEN pengguna dengan `role_id = 7` berhasil login, THE Auth_Filter SHALL mengizinkan akses ke route yang memiliki filter `auth:7` atau filter yang menyertakan role 7.
3. WHEN pengguna dengan `role_id = 7` mencoba mengakses route yang tidak menyertakan role 7 dalam filter auth, THE Auth_Filter SHALL menolak akses dan mengarahkan pengguna ke halaman login atau halaman error 403.
4. THE SIAKAD SHALL menampilkan pilihan role "Staf" pada form pembuatan dan pengeditan akun pengguna di User Management.

---

### Requirement 2: Hak Akses Staf — Data Induk

**User Story:** Sebagai Staf, saya ingin dapat mengelola data induk sekolah, sehingga saya dapat memperbarui informasi dasar sekolah tanpa memerlukan bantuan Admin.

#### Acceptance Criteria

1. WHEN pengguna Staf mengakses halaman Identitas Sekolah (`admin/school`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
2. WHEN pengguna Staf mengakses halaman Tahun Ajaran (`admin/academic-year`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
3. WHEN pengguna Staf mengakses halaman Hari Libur (`admin/holidays`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
4. WHEN pengguna Staf mengakses halaman Mata Pelajaran (`admin/subjects`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
5. WHEN pengguna Staf mengakses halaman Mapel Master (`admin/mapel-master`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
6. WHEN pengguna Staf mengakses halaman Data Master CP (`admin/administrasi-guru/cp-master`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
7. WHEN pengguna Staf mengakses halaman Mapping Kurikulum (`admin/administrasi-guru/mapping`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.

---

### Requirement 3: Hak Akses Staf — SDM (Guru, Staff, Siswa, Kelas)

**User Story:** Sebagai Staf, saya ingin dapat mengelola data SDM sekolah, sehingga saya dapat memperbarui data guru, siswa, dan kelas tanpa memerlukan bantuan Admin.

#### Acceptance Criteria

1. WHEN pengguna Staf mengakses halaman Data Guru & Staff (`admin/teachers`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
2. WHEN pengguna Staf mengakses halaman Plotting Pengajaran (`admin/teachingassignments`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
3. WHEN pengguna Staf mengakses halaman Integrasi Dapodik (`admin/dapodik`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
4. WHEN pengguna Staf mengakses halaman Data Siswa (`admin/students`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
5. WHEN pengguna Staf mengakses halaman Master Kelas (`admin/classes`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
6. WHEN pengguna Staf mengakses halaman Kelas Aktif (`admin/active-classes`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
7. WHEN pengguna Staf mengakses halaman Penempatan Siswa (`admin/placement`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
8. WHEN pengguna Staf mengakses halaman Kenaikan & Kelulusan (`admin/promotions`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.
9. WHEN pengguna Staf mengakses halaman Riwayat Siswa (`admin/student-records`), THE Auth_Filter SHALL mengizinkan akses karena route tersebut menyertakan role 7.

---

### Requirement 4: Pembatasan Akses Staf — Fitur Akademik

**User Story:** Sebagai Admin, saya ingin memastikan Staf tidak dapat mengakses fitur akademik sensitif, sehingga integritas data nilai, absensi, dan CBT tetap terjaga.

#### Acceptance Criteria

1. WHEN pengguna Staf mencoba mengakses halaman Presensi/Absensi (`admin/attendance`), THE Auth_Filter SHALL menolak akses karena route tersebut tidak menyertakan role 7.
2. WHEN pengguna Staf mencoba mengakses halaman Penilaian & Nilai (`admin/assessments`, `admin/grades`), THE Auth_Filter SHALL menolak akses karena route tersebut tidak menyertakan role 7.
3. WHEN pengguna Staf mencoba mengakses halaman CBT (`admin/cbt`, `admin/exam-schedule`, `admin/kartu-peserta`), THE Auth_Filter SHALL menolak akses karena route tersebut tidak menyertakan role 7.
4. WHEN pengguna Staf mencoba mengakses halaman Kokurikuler (`admin/kokurikuler`), THE Auth_Filter SHALL menolak akses karena route tersebut tidak menyertakan role 7.
5. WHEN pengguna Staf mencoba mengakses halaman Kurikulum & Administrasi Guru (`admin/administrasi-guru`) selain cp-master dan mapping, THE Auth_Filter SHALL menolak akses karena route tersebut tidak menyertakan role 7.
6. WHEN pengguna Staf mencoba mengakses halaman Konseling & Perilaku (`admin/student-notes`, `admin/behaviors`, `admin/student-academic-record`), THE Auth_Filter SHALL menolak akses karena route tersebut tidak menyertakan role 7.
7. WHEN pengguna Staf mencoba mengakses halaman Komunikasi (`admin/announcements`, `admin/chat`), THE Auth_Filter SHALL menolak akses karena route tersebut tidak menyertakan role 7.
8. WHEN pengguna Staf mencoba mengakses halaman CMS (`admin/cms`), THE Auth_Filter SHALL menolak akses karena pengelolaan konten website bukan tanggung jawab Staf.
9. WHEN pengguna Staf mencoba mengakses halaman User Management (`admin/users`), THE Auth_Filter SHALL menolak akses karena pengelolaan akun pengguna adalah wewenang Admin dan Kepsek.
10. WHEN pengguna Staf mencoba mengakses halaman Update Sistem (`admin/updater`), THE Auth_Filter SHALL menolak akses karena fitur tersebut hanya untuk Admin.

---

### Requirement 5: Sidebar Staf

**User Story:** Sebagai Staf, saya ingin melihat menu navigasi yang hanya menampilkan fitur yang dapat saya akses, sehingga saya tidak bingung dengan menu yang tidak relevan.

#### Acceptance Criteria

1. WHEN pengguna Staf login dan mengakses dashboard, THE SIAKAD SHALL menampilkan sidebar khusus Staf yang hanya memuat grup menu: Setup (Data Induk) dan SDM (Guru & Staff, Siswa & Kelas).
2. THE Sidebar SHALL menampilkan menu Dashboard dan Lihat Website kepada pengguna Staf.
3. THE Sidebar SHALL menampilkan menu Pengaturan Profil kepada pengguna Staf.
4. THE Sidebar SHALL tidak menampilkan menu Kurikulum, Kegiatan Harian, Konseling & Komunikasi, Konten Website, dan Akun & Sistem kepada pengguna Staf.
5. WHEN pengguna Staf berada di halaman yang aktif, THE Sidebar SHALL menandai menu yang sesuai dengan kelas CSS `active`.

---

### Requirement 6: Klarifikasi dan Pembatasan Akses Kontributor

**User Story:** Sebagai Admin, saya ingin memastikan Kontributor hanya dapat mengakses fitur CMS, sehingga pengelola konten website tidak dapat mengubah data akademik atau SDM sekolah.

#### Acceptance Criteria

1. WHEN pengguna Kontributor (`role_id = 6`) login, THE Auth_Filter SHALL mengizinkan akses hanya ke route CMS (`admin/cms/*`) yang memiliki filter `auth:1,2,6`.
2. WHEN pengguna Kontributor mencoba mengakses route di luar grup CMS (misalnya `admin/teachers`, `admin/students`, `admin/attendance`), THE Auth_Filter SHALL menolak akses karena route tersebut tidak menyertakan role 6.
3. THE Sidebar SHALL menampilkan kepada pengguna Kontributor hanya menu: Dashboard, Lihat Website, Konten Website (Slider Hero, Berita & Artikel, Sarana Prasarana, Dokumentasi, Tautan Pintar), dan Pengaturan Profil.
4. THE Sidebar SHALL tidak menampilkan menu Setup, SDM, Kurikulum, Kegiatan Harian, Konseling & Komunikasi, dan Akun & Sistem kepada pengguna Kontributor.
5. WHEN pengguna Kontributor mengakses halaman CMS mana pun, THE SIAKAD SHALL menampilkan antarmuka pengelolaan konten yang lengkap (buat, edit, hapus).

---

### Requirement 7: Dashboard Staf dan Kontributor

**User Story:** Sebagai Staf atau Kontributor, saya ingin melihat dashboard yang relevan setelah login, sehingga saya langsung mengetahui ringkasan informasi yang berkaitan dengan tugas saya.

#### Acceptance Criteria

1. WHEN pengguna Staf mengakses `/dashboard`, THE Dashboard SHALL menampilkan ringkasan data induk dan SDM yang relevan (jumlah guru aktif, jumlah siswa aktif, tahun ajaran berjalan).
2. WHEN pengguna Kontributor mengakses `/dashboard`, THE Dashboard SHALL menampilkan ringkasan konten CMS yang relevan (jumlah artikel, jumlah slider aktif, jumlah fasilitas).
3. IF pengguna Staf atau Kontributor mengakses `/dashboard` tanpa sesi login yang valid, THEN THE SIAKAD SHALL mengarahkan pengguna ke halaman login.

---

### Requirement 8: Pengelolaan Akun Staf dan Kontributor oleh Admin

**User Story:** Sebagai Admin, saya ingin dapat membuat, mengedit, dan menghapus akun pengguna dengan role Staf dan Kontributor, sehingga saya dapat mengelola hak akses tim administrasi dan konten.

#### Acceptance Criteria

1. WHEN Admin mengakses halaman User Management (`admin/users`), THE SIAKAD SHALL menampilkan daftar semua pengguna termasuk yang memiliki role Staf (7) dan Kontributor (6).
2. WHEN Admin membuat akun baru, THE SIAKAD SHALL menyediakan pilihan role "Staf" dan "Kontributor" pada dropdown pemilihan role.
3. WHEN Admin mengubah role pengguna yang sudah ada menjadi Staf atau Kontributor, THE SIAKAD SHALL memperbarui `role_id` pengguna tersebut di database.
4. WHEN Admin menghapus akun pengguna dengan role Staf atau Kontributor, THE SIAKAD SHALL menghapus akun tersebut dari database.
5. IF Admin mencoba membuat akun dengan username yang sudah ada, THEN THE SIAKAD SHALL menampilkan pesan error yang menjelaskan bahwa username sudah digunakan.
