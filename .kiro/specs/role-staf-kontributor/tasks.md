# Implementation Plan: Role Staf & Kontributor

## Overview

Implementasi menambahkan role Staf (id=7) dan mengklarifikasi role Kontributor (id=6) ke sistem SIAKAD. Urutan pengerjaan: migrasi database → update Routes.php → sidebar baru/update → update app.php → update Dashboard.php → view dashboard baru → update beranda publik bagian Tenaga Pengajar.

## Tasks

- [x] 1. Buat file migrasi database untuk role Kontributor dan Staf
  - Buat file `app/Database/Migrations/2026-04-19-000001_AddRolesKontributorStaf.php`
  - Implementasikan method `up()`: cek keberadaan id=6 dan id=7 sebelum INSERT, tambahkan keduanya sekaligus
  - Implementasikan method `down()`: DELETE WHERE id IN (6, 7)
  - Gunakan `$this->db->table('roles')` sesuai pola migrasi yang sudah ada
  - _Requirements: 1.1, 8.1, 8.2_

- [x] 2. Update filter variables dan route permissions di Routes.php
  - [x] 2.1 Tambahkan variabel filter baru di dalam `$routes->group('admin', ...)`
    - Tambahkan `$auth17 = ['filter' => 'auth:1,7']`
    - Tambahkan `$auth127 = ['filter' => 'auth:1,2,7']`
    - Tambahkan `$auth1237 = ['filter' => 'auth:1,2,3,7']`
    - _Requirements: 1.2, 1.3_

  - [x] 2.2 Update filter route Data Induk dari `$auth12` ke `$auth127`
    - `admin/school` dan `admin/school/update`
    - `admin/academic-year` dan semua sub-route-nya
    - `admin/holidays` dan semua sub-route-nya
    - `admin/subjects` dan semua sub-route-nya
    - `admin/teachingassignments` dan semua sub-route-nya
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 2.3 Update filter route SDM dari `$auth12` ke `$auth127`
    - `admin/teachers` dan semua sub-route-nya
    - `admin/students` dan semua sub-route-nya
    - `admin/classes` dan semua sub-route-nya
    - `admin/active-classes` dan semua sub-route-nya
    - `admin/placement` dan semua sub-route-nya
    - `admin/promotions` dan semua sub-route-nya
    - `admin/student-records` dan semua sub-route-nya
    - `admin/dapodik` (jika ada)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9_

  - [x] 2.4 Update filter route `mapel-master` dari `$auth1` ke `$auth17`
    - `admin/mapel-master` dan semua sub-route-nya
    - _Requirements: 2.5_

  - [x] 2.5 Override filter sub-route `administrasi-guru` untuk cp-master dan mapping
    - Di dalam group `administrasi-guru`, tambahkan parameter filter `$auth1237` secara eksplisit pada:
      - `GET mapping`, `POST mapping/update`
      - `GET cp-master`, `POST cp-master/store`, `GET cp-master/delete/(:num)`
    - Route lain di group ini tetap inherit `$auth123`
    - _Requirements: 2.6, 2.7, 4.5_

  - [x] 2.6 Tulis unit test untuk verifikasi filter variables terdefinisi
    - Verifikasi `$auth17`, `$auth127`, `$auth1237` terdefinisi dengan nilai yang benar
    - _Requirements: 1.2, 1.3_

- [x] 3. Buat sidebar_staf.php
  - Buat file baru `app/Views/layouts/partials/sidebar_staf.php`
  - Implementasikan menu: Dashboard, Lihat Website (link ke `/`)
  - Implementasikan collapse **SETUP — Data Induk**: Identitas Sekolah, Tahun Ajaran, Hari Libur, Mata Pelajaran, Mapel Master, Data Master CP (`admin/administrasi-guru/cp-master`), Mapping Kurikulum (`admin/administrasi-guru/mapping`)
  - Implementasikan collapse **SDM — Guru & Staff**: Data Guru & Staff, Plotting Pengajaran, Integrasi Dapodik
  - Implementasikan collapse **SDM — Siswa & Kelas**: Data Siswa, Master Kelas, Kelas Aktif, Penempatan Siswa, Kenaikan & Kelulusan, Riwayat Siswa
  - Implementasikan menu Pengaturan Profil (link ke `/profile`)
  - Gunakan `url_is()` untuk kelas CSS `active` pada setiap link, sesuai pola sidebar yang sudah ada
  - Pastikan TIDAK ada link ke: attendance, assessments, grades, cbt, kokurikuler, cms, users, updater, announcements, chat, student-notes, behaviors
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 4. Update sidebar_kontributor.php
  - Buka `app/Views/layouts/partials/sidebar_kontributor.php`
  - Tambahkan sub-menu yang belum ada ke dalam collapse `cmsMenu`:
    - Slider Hero → `admin/cms/sliders`
    - Sarana Prasarana → `admin/cms/facilities`
    - Tautan Pintar → `admin/cms/links`
  - Pertahankan sub-menu yang sudah ada: Berita & Artikel, Dokumentasi
  - Urutkan sub-menu: Slider Hero, Berita & Artikel, Sarana Prasarana, Dokumentasi, Tautan Pintar
  - Tambahkan `url_is()` active check untuk setiap sub-menu baru
  - _Requirements: 6.3, 6.4_

- [x] 5. Update app.php untuk load sidebar_staf
  - Buka `app/Views/layouts/app.php`
  - Cari blok kondisional yang memuat sidebar berdasarkan `$user['role_id']`
  - Tambahkan `elseif ($user['role_id'] == 7)` yang memanggil `$this->include('layouts/partials/sidebar_staf')` di dua tempat: sidebar desktop dan sidebar mobile offcanvas
  - _Requirements: 5.1_

- [x] 6. Update Dashboard.php — tambahkan case 7 untuk Staf
  - Buka `app/Controllers/Dashboard.php`
  - Di dalam `switch ($roleId)`, tambahkan `case 7:` sebelum `default:`
  - Implementasikan query stats: `total_teachers`, `total_students`, `total_classes`, `active_students` (dari `student_records` WHERE status='aktif')
  - Ambil `$activeYear` menggunakan `(new \App\Models\AcademicYearModel())->getActiveYear()`
  - Return `view('dashboard/staf', ['user' => $user, 'stats' => $stats, 'activeYear' => $activeYear])`
  - _Requirements: 7.1, 7.3_

- [x] 7. Buat view dashboard/staf.php
  - Buat file baru `app/Views/dashboard/staf.php`
  - Gunakan layout `app.php` (extend atau include sesuai pola view yang sudah ada, lihat `dashboard/kontributor.php` sebagai referensi)
  - Tampilkan 4 kartu statistik: Jumlah Guru Aktif (`$stats['total_teachers']`), Jumlah Siswa Aktif (`$stats['active_students']`), Jumlah Kelas (`$stats['total_classes']`), Tahun Ajaran Berjalan (`$activeYear['year']`)
  - Gunakan Bootstrap card grid yang konsisten dengan dashboard lain
  - _Requirements: 7.1_

- [x] 8. Checkpoint — Verifikasi integrasi end-to-end
  - Pastikan semua file baru terbuat dan tidak ada syntax error PHP
  - Pastikan `case 7` di Dashboard.php tidak memiliki `break` yang hilang
  - Pastikan sidebar_staf.php tidak memiliki link yang seharusnya dilarang
  - Pastikan sidebar_kontributor.php memiliki tepat 5 sub-menu CMS
  - Tanyakan kepada user jika ada pertanyaan sebelum melanjutkan.

- [x] 9. Tulis property test untuk AuthFilter allow/deny (Property 1)
  - **Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter**
  - Generate pasangan `(role_id: int, allowed_roles: int[])` secara acak, minimum 100 iterasi
  - Assert: `in_array(role_id, allowed_roles)` konsisten dengan keputusan filter
  - **Validates: Requirements 1.2, 1.3**

- [x] 10. Tulis property test untuk username uniqueness (Property 2)
  - **Property 2: Username unik — penyimpanan kedua harus gagal**
  - Generate username string acak yang valid, simpan user pertama, coba simpan user kedua dengan username sama
  - Assert: operasi kedua gagal, count user tidak bertambah
  - Minimum 100 iterasi
  - **Validates: Requirements 8.5**

- [x] 11. Update beranda publik — bagian Tenaga Pengajar
  - [x] 11.1 Update `TeacherModel::getPublicTeachers()` di `app/Models/TeacherModel.php`
    - Tambahkan JOIN ke tabel `users` untuk mendapatkan `role_id`
    - Tambahkan JOIN ke tabel `classes` (kondisional: hanya untuk `teaching_assignments.role = "guru_kelas"`) untuk mendapatkan nama kelas wali
    - Tambahkan kolom `wali_class_name` via `MAX(CASE WHEN role = "guru_kelas" THEN classes.name END)`
    - _Requirements: 5.1, 6.3_

  - [x] 11.2 Update tampilan kartu guru di `app/Views/index.php`
    - Ganti logika label "Mengajar" dengan aturan berikut:
      - Jika `role_id = 7` → tampilkan **Staf**
      - Jika `role_id = 6` → tampilkan **Kontributor**
      - Jika level sekolah SD (`level = 1`):
        - Guru wali kelas → `Mengajar: Guru Kelas [nama kelas]`
        - Guru mapel → `Mengajar: Guru [kode mapel]`
      - Jika level SMP/SMA (`level = 2` atau `3`):
        - Wali kelas + mapel → `Mengajar: Wali Kelas [nama kelas], Guru [kode mapel]`
        - Mapel saja → `Mengajar: Guru [kode mapel]`
        - Wali kelas saja → `Mengajar: Wali Kelas [nama kelas]`
    - Gunakan variabel `$school['level']` yang sudah tersedia di view
    - _Requirements: 5.1, 6.3_

## Notes

- Tasks bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Urutan task 1→8 harus diikuti karena ada dependensi (migrasi harus jalan sebelum test login)
- Untuk menjalankan migrasi: `php spark migrate`
- Property tests (task 9-10) memerlukan library PBT untuk PHP (eris atau PHPUnit data provider)
- Level sekolah: `1` = SD, `2` = SMP, `3` = SMA (disimpan di tabel `school_profile.level`)
