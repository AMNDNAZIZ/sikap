# Design Document: Role Staf & Kontributor

## Overview

Fitur ini menambahkan role **Staf** (role_id = 7) ke sistem SIAKAD dan mengklarifikasi batasan akses role **Kontributor** (role_id = 6) yang sudah ada. Implementasi mencakup migrasi database, konfigurasi route filter, sidebar baru, dashboard baru, dan update User Management.

Pendekatan desain mengikuti pola yang sudah ada di sistem:
- Role diidentifikasi via `role_id` di tabel `users`
- Akses dikontrol via filter `auth:role_ids` di `Routes.php`
- Tampilan navigasi dikontrol via file `sidebar_{role}.php` terpisah
- Dashboard dikontrol via `switch($roleId)` di `Dashboard.php`

---

## Architecture

```
HTTP Request
     |
  Routes.php  <- filter auth:1,2,7 dll
     |
  AuthFilter  <- cek session user.role_id
     |
  Controller
     |
  View Layer (app.php layout)
     |- sidebar_staf.php       (baru, role 7)
     |- sidebar_kontributor.php (diperbarui)
     |- dashboard/staf.php     (baru, role 7)
```

### Komponen yang Dimodifikasi

| Komponen | Tipe | Keterangan |
|---|---|---|
| `app/Database/Migrations/2026-04-19-000001_AddRolesKontributorStaf.php` | Baru | INSERT role Kontributor (6) dan Staf (7) ke tabel roles |
| `app/Config/Routes.php` | Modifikasi | Tambah variabel filter dan update filter route |
| `app/Views/layouts/partials/sidebar_staf.php` | Baru | Sidebar untuk role Staf |
| `app/Views/layouts/partials/sidebar_kontributor.php` | Modifikasi | Sesuaikan dengan requirements |
| `app/Views/layouts/app.php` | Modifikasi | Tambah case role_id == 7 untuk load sidebar_staf |
| `app/Controllers/Dashboard.php` | Modifikasi | Tambah case 7 untuk dashboard Staf |
| `app/Views/dashboard/staf.php` | Baru | View dashboard Staf |

> **Catatan:** `app/Controllers/Admin/User.php` dan view User Management tidak perlu dimodifikasi karena controller sudah menggunakan `$this->roleModel->findAll()` secara dinamis. Role Staf otomatis muncul di dropdown setelah migrasi dijalankan.

---

## Components and Interfaces

### 1. Migrasi Database

**File:** `app/Database/Migrations/2026-04-19-000001_AddRolesKontributorStaf.php`

Kedua role belum ada di database (hasil pengecekan: tabel `roles` hanya berisi id 1-5). Migrasi menambahkan keduanya sekaligus:

```php
public function up()
{
    $existing6 = $this->db->table('roles')->where('id', 6)->get()->getRowArray();
    if (!$existing6) {
        $this->db->table('roles')->insert(['id' => 6, 'name' => 'Kontributor']);
    }
    $existing7 = $this->db->table('roles')->where('id', 7)->get()->getRowArray();
    if (!$existing7) {
        $this->db->table('roles')->insert(['id' => 7, 'name' => 'Staf']);
    }
}

public function down()
{
    $this->db->table('roles')->whereIn('id', [6, 7])->delete();
}
```

### 2. Filter Variables di Routes.php

Tambahkan variabel filter baru di dalam `$routes->group('admin', ...)`:

```php
$auth17   = ['filter' => 'auth:1,7'];     // Admin + Staf
$auth127  = ['filter' => 'auth:1,2,7'];   // Admin + Kepsek + Staf
$auth1237 = ['filter' => 'auth:1,2,3,7']; // Admin + Kepsek + Guru + Staf
```

### 3. Mapping Route ke Filter

| Route Group | Filter Lama | Filter Baru |
|---|---|---|
| `admin/school` | `$auth12` | `$auth127` |
| `admin/academic-year` | `$auth12` | `$auth127` |
| `admin/holidays` | `$auth12` | `$auth127` |
| `admin/subjects` | `$auth12` | `$auth127` |
| `admin/mapel-master` | `$auth1` | `$auth17` |
| `admin/teachers` | `$auth12` | `$auth127` |
| `admin/teachingassignments` | `$auth12` | `$auth127` |
| `admin/dapodik` | `$auth12` | `$auth127` |
| `admin/students` | `$auth12` | `$auth127` |
| `admin/classes` | `$auth12` | `$auth127` |
| `admin/active-classes` | `$auth12` | `$auth127` |
| `admin/placement` | `$auth12` | `$auth127` |
| `admin/promotions` | `$auth12` | `$auth127` |
| `admin/student-records` | `$auth12` | `$auth127` |
| `admin/administrasi-guru/cp-master` | `$auth123` (group) | `$auth1237` (override) |
| `admin/administrasi-guru/mapping` | `$auth123` (group) | `$auth1237` (override) |
| `admin/attendance` | `$auth123` | tetap `$auth123` |
| `admin/assessments`, `admin/grades` | `$auth123` | tetap `$auth123` |
| `admin/cbt/*`, `admin/exam-schedule` | `$auth123` | tetap `$auth123` |
| `admin/kokurikuler` | `$auth13` | tetap `$auth13` |
| `admin/cms` | `$auth126` | tetap `$auth126` |
| `admin/users` | `$auth12` | tetap `$auth12` |
| `admin/updater` | `$auth1` | tetap `$auth1` |

**Catatan khusus `administrasi-guru`:** Group ini menggunakan `$auth123` sebagai default. Sub-route `cp-master` dan `mapping` perlu override filter individual ke `$auth1237`. Route lain di group ini (cp, tp, atp, prota-prosem, modul-ajar) tetap `$auth123`.

```php
$routes->group('administrasi-guru', $auth123, function ($routes) {
    $routes->get('/', 'AdministrasiGuru::index');
    // Override untuk Staf:
    $routes->get('mapping', 'AdministrasiGuru::mapping', $auth1237);
    $routes->post('mapping/update', 'AdministrasiGuru::updateMapping', $auth1237);
    $routes->get('cp-master', 'AdministrasiGuru::cpMasterIndex', $auth1237);
    $routes->post('cp-master/store', 'AdministrasiGuru::cpMasterStore', $auth1237);
    $routes->get('cp-master/delete/(:num)', 'AdministrasiGuru::cpMasterDelete/$1', $auth1237);
    // Sisa route tetap inherit $auth123 ...
});
```

### 4. Sidebar Staf

**File baru:** `app/Views/layouts/partials/sidebar_staf.php`

Struktur menu yang ditampilkan:
- Dashboard
- Lihat Website (link ke /)
- **SETUP**: Data Induk (collapse) berisi: Identitas Sekolah, Tahun Ajaran, Hari Libur, Mata Pelajaran, Mapel Master, Data Master CP, Mapping Kurikulum
- **SDM**: Guru & Staff (collapse) berisi: Data Guru & Staff, Plotting Pengajaran, Integrasi Dapodik
- **SDM**: Siswa & Kelas (collapse) berisi: Data Siswa, Master Kelas, Kelas Aktif, Penempatan Siswa, Kenaikan & Kelulusan, Riwayat Siswa
- Pengaturan Profil

Menu yang **tidak** ditampilkan: Kurikulum & Adm Guru (selain cp-master/mapping), Jadwal & Agenda, Presensi, Penilaian & Nilai, Kokurikuler, CBT, Konseling & Perilaku, Komunikasi, Konten Website, Akun & Sistem.

### 5. Update Sidebar Kontributor

**File:** `app/Views/layouts/partials/sidebar_kontributor.php`

Sidebar kontributor saat ini sudah mendekati benar, namun perlu diperbarui agar mencakup semua sub-menu CMS yang sesuai requirements:
- Dashboard
- Lihat Website
- **Konten Website** (collapse) berisi: Slider Hero, Berita & Artikel, Sarana Prasarana, Dokumentasi, Tautan Pintar
- Pengaturan Profil

Perubahan dari kondisi saat ini: tambahkan Slider Hero, Sarana Prasarana, dan Tautan Pintar ke dalam menu CMS (saat ini hanya ada Berita & Artikel dan Dokumentasi).

### 6. Update app.php Layout

**File:** `app/Views/layouts/app.php`

Tambahkan `elseif` untuk role_id 7 di dua tempat (sidebar desktop dan sidebar mobile offcanvas):

```php
<?php elseif ($user['role_id'] == 7): ?>
    <?= $this->include('layouts/partials/sidebar_staf') ?>
```

### 7. Update Dashboard.php

**File:** `app/Controllers/Dashboard.php`

Tambahkan `case 7` di dalam `switch ($roleId)`:

```php
case 7: // Staf
    $stats = [
        'total_teachers' => $db->table('teachers')->countAllResults(),
        'total_students' => $db->table('students')->countAllResults(),
        'total_classes'  => $db->table('classes')->countAllResults(),
        'active_students' => $db->table('student_records')
            ->where('status', 'aktif')->countAllResults(),
    ];
    $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
    return view('dashboard/staf', [
        'user'       => $user,
        'stats'      => $stats,
        'activeYear' => $activeYear,
    ]);
```

### 8. View Dashboard Staf

**File baru:** `app/Views/dashboard/staf.php`

Menampilkan kartu statistik ringkasan:
- Jumlah Guru Aktif
- Jumlah Siswa Aktif
- Jumlah Kelas
- Tahun Ajaran Berjalan

---

## Data Models

### Tabel `roles`

Tabel ini sudah ada. Migrasi hanya menambahkan satu baris:

| id | name |
|---|---|
| 1 | Admin |
| 2 | Kepala Sekolah |
| 3 | Guru |
| 4 | Orang Tua |
| 5 | Siswa |
| **6** | **Kontributor** ← ditambahkan via migrasi |
| **7** | **Staf** ← ditambahkan via migrasi |

### Tabel `users`

Tidak ada perubahan skema. Kolom `role_id` sudah ada dan akan menyimpan nilai `7` untuk pengguna Staf.

### AuthFilter Logic

AuthFilter yang ada di `app/Filters/AuthFilter.php` sudah menggunakan pola `auth:role_ids` — filter membaca `role_id` dari sesi dan memverifikasi apakah ada dalam daftar yang diizinkan. Tidak ada perubahan pada AuthFilter itu sendiri.

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter

*For any* kombinasi `role_id` pengguna dan daftar `allowed_roles` pada filter route, AuthFilter harus mengizinkan akses jika dan hanya jika `role_id` ada dalam `allowed_roles`, dan menolak akses jika `role_id` tidak ada dalam `allowed_roles`.

**Validates: Requirements 1.2, 1.3**

### Property 2: Username unik — penyimpanan kedua harus gagal

*For any* username yang valid, jika username tersebut sudah tersimpan di database, maka percobaan menyimpan user baru dengan username yang sama harus menghasilkan error (constraint violation atau validasi gagal), dan jumlah user di database tidak bertambah.

**Validates: Requirements 8.5**

---

## Error Handling

### Akses Ditolak (403)

Ketika AuthFilter menolak akses, pengguna diarahkan ke halaman login atau halaman error 403 sesuai implementasi AuthFilter yang sudah ada. Tidak ada perubahan pada mekanisme ini.

### Role Tidak Dikenal di Dashboard

Jika `role_id` tidak cocok dengan case manapun di `switch($roleId)`, `default` case sudah ada dan mengarahkan ke `/login`. Ini mencakup skenario edge case jika ada role_id yang tidak terdefinisi.

### Migrasi Gagal (Duplicate Entry)

Migrasi menggunakan pengecekan `if (!$existing)` sebelum INSERT sehingga aman dijalankan berulang kali.

---

## Testing Strategy

Fitur ini bersifat konfigurasi (route filter, sidebar, dashboard case) dengan sedikit logika bisnis murni. PBT hanya relevan untuk AuthFilter logic dan validasi username unik.

### Unit Tests

- Verifikasi `RoleModel::findAll()` mengembalikan 7 role setelah migrasi
- Verifikasi `Dashboard::index()` dengan `role_id = 7` mengembalikan view `dashboard/staf`
- Verifikasi `Dashboard::index()` dengan `role_id = 6` mengembalikan view `dashboard/kontributor`
- Verifikasi sidebar_staf.php mengandung link ke semua route yang diizinkan
- Verifikasi sidebar_staf.php tidak mengandung link ke route yang dilarang
- Verifikasi sidebar_kontributor.php mengandung semua 5 sub-menu CMS

### Property-Based Tests

Menggunakan library property-based testing yang tersedia untuk PHP (misalnya [eris](https://github.com/giorgiosironi/eris) atau implementasi sederhana dengan PHPUnit data provider).

**Property 1: AuthFilter allow/deny**
- Generate: pasangan `(role_id: int, allowed_roles: int[])` secara acak
- Assert: `in_array(role_id, allowed_roles)` harus konsisten dengan keputusan filter
- Minimum 100 iterasi
- Tag: `Feature: role-staf-kontributor, Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter`

**Property 2: Username uniqueness**
- Generate: username string acak yang valid
- Simpan user pertama, coba simpan user kedua dengan username sama
- Assert: operasi kedua gagal, count user tidak bertambah
- Minimum 100 iterasi
- Tag: `Feature: role-staf-kontributor, Property 2: Username unik — penyimpanan kedua harus gagal`

### Smoke Tests (Manual / Integration)

- Login sebagai Staf (role_id=7) → verifikasi sidebar_staf tampil
- Akses `admin/school` sebagai Staf → harus berhasil (200)
- Akses `admin/attendance` sebagai Staf → harus ditolak (redirect login/403)
- Akses `admin/cms/articles` sebagai Staf → harus ditolak
- Login sebagai Kontributor (role_id=6) → verifikasi sidebar_kontributor tampil dengan 5 sub-menu CMS
- Akses `admin/cms/sliders` sebagai Kontributor → harus berhasil
- Akses `admin/teachers` sebagai Kontributor → harus ditolak
- Admin buka User Management → dropdown role menampilkan "Staf" sebagai pilihan
