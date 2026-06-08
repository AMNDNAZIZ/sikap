# Perbaikan Menu Kepala Sekolah - Lanjutan

## Tanggal
24 April 2026

## Deskripsi
Memperbaiki masalah-masalah yang ditemukan pada menu Kepala Sekolah setelah restrukturisasi awal.

## Masalah yang Diperbaiki

### 1. ✅ Kepsek Tidak Bisa Akses Mapel Master (CP)
**Masalah**: Kepsek tidak bisa akses menu Mapel Master (CP)

**Root Cause**: Route menggunakan filter `$auth17` yang hanya mengizinkan role_id 1 dan 7, tidak termasuk kepsek (role_id = 2)

**Solusi**:
- Mengubah route filter dari `$auth17` menjadi `$auth12` (Admin & Kepsek)
- Menambahkan permission check di `MapelMaster.php` controller
- Kepsek bisa VIEW tapi tidak bisa CREATE/UPDATE/DELETE
- Menambahkan variable `$isReadOnly` untuk hide tombol aksi

**Files Modified**:
- `app/Config/Routes.php` - Mengubah filter route
- `app/Controllers/Admin/MapelMaster.php` - Permission check
- `app/Views/admin/mapel_master/index.php` - Hide tombol aksi

### 2. ✅ Tombol Aksi (Edit/Hapus) Masih Muncul di Data Master CP
**Masalah**: Pada menu Data Master CP, tombol edit dan hapus masih muncul untuk kepsek

**Root Cause**: Route menggunakan filter `$auth1237` yang tidak termasuk kepsek (role_id = 2)

**Solusi**:
- Mengubah route filter dari `$auth1237` menjadi `$auth123` (Admin, Kepsek & Guru)
- Menambahkan permission check di `AdministrasiGuru.php` untuk method `cpMasterIndex()`, `cpMasterStore()`, `cpMasterDelete()`
- Menambahkan variable `$isReadOnly` untuk hide tombol aksi di view
- Hide tombol "Tambah Master CP" untuk kepsek
- Hide kolom "Aksi" di tabel untuk kepsek

**Files Modified**:
- `app/Config/Routes.php` - Mengubah filter route
- `app/Controllers/Admin/AdministrasiGuru.php` - Permission check
- `app/Views/admin/administrasi_guru/cp_master.php` - Hide tombol aksi

### 3. ✅ Kepsek Masih Bisa Merubah Data Mapping Kurikulum
**Masalah**: Kepsek masih bisa merubah data di halaman Mapping Kurikulum

**Root Cause**: Route menggunakan filter `$auth1237` yang tidak termasuk kepsek (role_id = 2)

**Solusi**:
- Mengubah route filter dari `$auth1237` menjadi `$auth123` (Admin, Kepsek & Guru)
- Menambahkan permission check di method `mapping()` dan `updateMapping()`
- Menambahkan variable `$isReadOnly` untuk disable dropdown select
- Hide tombol "Simpan Mapping" untuk kepsek

**Files Modified**:
- `app/Config/Routes.php` - Mengubah filter route
- `app/Controllers/Admin/AdministrasiGuru.php` - Permission check
- `app/Views/admin/administrasi_guru/mapping.php` - Disable form

### 4. ✅ Hilangkan Menu dari Sidebar Kepsek
**Masalah**: Beberapa menu yang tidak perlu masih muncul di sidebar kepsek

**Menu yang Dihapus**:
1. ❌ **Integrasi Dapodik** (dari Guru & Staff)
2. ❌ **Penempatan Siswa** (dari Siswa & Kelas)
3. ❌ **Kenaikan & Kelulusan** (dari Siswa & Kelas)
4. ❌ **Input Nilai** (dari Penilaian & Nilai)

**Alasan**:
- Integrasi Dapodik: Hanya admin yang perlu sync data
- Penempatan Siswa: Operasional yang dilakukan admin
- Kenaikan & Kelulusan: Operasional yang dilakukan admin
- Input Nilai: Kepsek hanya perlu lihat rekap, tidak input nilai

**Files Modified**:
- `app/Views/layouts/partials/sidebar_kepsek.php`

### 5. ✅ Kepsek Belum Punya Akses ke Menu Kokurikuler
**Masalah**: Kepsek tidak bisa akses menu-menu yang berkaitan dengan Kokurikuler

**Root Cause**: Route menggunakan filter `$auth13` yang hanya mengizinkan Admin (1) dan Guru (3), tidak termasuk kepsek (role_id = 2)

**Solusi**:
- Mengubah route filter dari `$auth13` menjadi `$auth123` (Admin, Kepsek & Guru)
- Menambahkan permission untuk kepsek (role_id = 2) di semua method Kokurikuler
- Kepsek bisa VIEW semua data kokurikuler (read-only)
- Kepsek TIDAK bisa CREATE/UPDATE/DELETE

**Method yang Diupdate**:
1. `index()` - Kepsek bisa lihat daftar dokumen rencana
2. `view($id)` - Kepsek bisa lihat detail dokumen
3. `pelaksanaan()` - Kepsek bisa lihat pelaksanaan
4. `penilaian()` - Kepsek bisa lihat penilaian
5. `pelaporan()` - Kepsek bisa lihat pelaporan

**Permission Logic**:
```php
// Admin, Kepsek, dan Guru Kelas bisa akses (VIEW)
if (!in_array($roleId, [1, 2, 3])) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
}

// Kepsek tidak bisa create/edit/delete
if (!in_array($roleId, [1, 3])) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
}
```

**Files Modified**:
- `app/Config/Routes.php` - Mengubah filter route
- `app/Controllers/Admin/Kokurikuler.php` - Permission check

## Ringkasan Perubahan

### Controller Changes

#### 1. MapelMaster.php
```php
public function index()
{
    // ✅ Check permission - Kepsek can view
    $user = session()->get('user');
    $roleId = $user['role_id'] ?? null;
    $isReadOnly = ($roleId == 2); // Kepsek is read-only
    
    $data['isReadOnly'] = $isReadOnly;
    // ... rest of code
}

public function store()
{
    // ✅ Check permission - Kepsek cannot create/update
    $user = session()->get('user');
    if (($user['role_id'] ?? null) == 2) {
        return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah/mengubah data.');
    }
    // ... rest of code
}

public function delete($id)
{
    // ✅ Check permission - Kepsek cannot delete
    $user = session()->get('user');
    if (($user['role_id'] ?? null) == 2) {
        return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menghapus data.');
    }
    // ... rest of code
}
```

#### 2. AdministrasiGuru.php
```php
public function cpMasterIndex()
{
    // ✅ Check permission - Kepsek can view
    $user = session()->get('user');
    $roleId = $user['role_id'] ?? null;
    $isReadOnly = ($roleId == 2);
    
    $data['isReadOnly'] = $isReadOnly;
    // ... rest of code
}

public function cpMasterStore()
{
    // ✅ Check permission - Kepsek cannot create/update
    $user = session()->get('user');
    if (($user['role_id'] ?? null) == 2) {
        return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah/mengubah data.');
    }
    // ... rest of code
}

public function cpMasterDelete($id)
{
    // ✅ Check permission - Kepsek cannot delete
    $user = session()->get('user');
    if (($user['role_id'] ?? null) == 2) {
        return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menghapus data.');
    }
    // ... rest of code
}

public function mapping()
{
    // ✅ Check permission - Kepsek can view
    $user = session()->get('user');
    $roleId = $user['role_id'] ?? null;
    $isReadOnly = ($roleId == 2);
    
    $data['isReadOnly'] = $isReadOnly;
    // ... rest of code
}

public function updateMapping()
{
    // ✅ Check permission - Kepsek cannot update
    $user = session()->get('user');
    if (($user['role_id'] ?? null) == 2) {
        return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk mengubah data.');
    }
    // ... rest of code
}
```

#### 3. Kokurikuler.php
```php
public function index()
{
    // Admin (1), Kepsek (2), dan Guru Kelas/Wali Kelas (3) bisa akses
    if (!in_array($roleId, [1, 2, 3])) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
    }
    
    return view('admin/kokurikuler/index', [
        'title' => 'Dokumen Rencana Kokurikuler',
        'documents' => $documents,
        'isReadOnly' => ($roleId == 2), // Kepsek read-only
    ]);
}

public function view($id)
{
    // Check access - Admin, Kepsek, atau creator
    $user = session()->get('user');
    $roleId = $user['role_id'] ?? 0;
    if (!in_array($roleId, [1, 2]) && $document['created_by'] != $user['id']) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses');
    }
    // ... rest of code
}

public function pelaksanaan()
{
    // Admin, Kepsek, dan Guru Kelas bisa akses
    if (!in_array($roleId, [1, 2, 3])) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
    }
    
    return view('admin/kokurikuler/pelaksanaan', [
        'title' => 'Pelaksanaan Kokurikuler',
        'documents' => $documents,
        'isReadOnly' => ($roleId == 2), // Kepsek read-only
    ]);
}

public function penilaian()
{
    // Admin, Kepsek, dan Guru Kelas bisa akses
    if (!in_array($roleId, [1, 2, 3])) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
    }
    
    return view('admin/kokurikuler/penilaian', [
        'title' => 'Penilaian/Asesmen Kokurikuler',
        'documents' => $documents,
        'isReadOnly' => ($roleId == 2), // Kepsek read-only
    ]);
}

public function pelaporan()
{
    // Admin, Kepsek, dan Guru Kelas bisa akses
    if (!in_array($roleId, [1, 2, 3])) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
    }
    
    return view('admin/kokurikuler/pelaporan', [
        'title'     => 'Pelaporan Kokurikuler',
        'documents' => $documents,
        'roleId'    => $roleId,
        'isReadOnly' => ($roleId == 2), // Kepsek read-only
    ]);
}
```

### View Changes

#### 1. Mapel Master Index
```php
<?php if (!$isReadOnly): ?>
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mapelModal">
    <i class="bi bi-plus-lg"></i> Tambah Mapel Master
</button>
<?php endif; ?>

<!-- Table -->
<th>Kelompok</th>
<?php if (!$isReadOnly): ?>
<th width="120">Aksi</th>
<?php endif; ?>

<!-- Actions -->
<?php if (!$isReadOnly): ?>
<td>
    <button type="button" class="btn btn-sm btn-info text-white btn-edit">...</button>
    <a href="..." class="btn btn-sm btn-danger">...</a>
</td>
<?php endif; ?>
```

#### 2. CP Master
```php
<?php if (!$isReadOnly): ?>
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cpModal">
    <i class="bi bi-plus-lg"></i> Tambah Master CP
</button>
<?php endif; ?>

<!-- Table -->
<th width="150">SK/Tahun</th>
<?php if (!$isReadOnly): ?>
<th width="100">Aksi</th>
<?php endif; ?>

<!-- Actions -->
<?php if (!$isReadOnly): ?>
<td>
    <button type="button" class="btn btn-sm btn-info text-white btn-edit">...</button>
    <a href="..." class="btn btn-sm btn-danger">...</a>
</td>
<?php endif; ?>
```

#### 3. Mapping Kurikulum
```php
<select name="mapping[<?= $s['id'] ?>]" class="form-select form-select-sm select2-master" <?= $isReadOnly ? 'disabled' : '' ?>>
    <!-- options -->
</select>

<?php if (!$isReadOnly): ?>
<div class="mt-4">
    <button type="submit" class="btn btn-primary">Simpan Mapping</button>
</div>
<?php endif; ?>
```

#### 4. Sidebar Kepsek
Menu yang dihapus:
- Integrasi Dapodik (dari Guru & Staff)
- Penempatan Siswa (dari Siswa & Kelas)
- Kenaikan & Kelulusan (dari Siswa & Kelas)
- Input Nilai (dari Penilaian & Nilai)

## Testing Checklist

### ✅ Mapel Master (CP)
- [x] Kepsek bisa akses halaman Mapel Master
- [x] Tombol "Tambah Mapel Master" tidak muncul untuk kepsek
- [x] Kolom "Aksi" tidak muncul di tabel untuk kepsek
- [x] Kepsek tidak bisa edit/hapus mapel master

### ✅ Data Master CP
- [x] Kepsek bisa akses halaman Data Master CP
- [x] Tombol "Tambah Master CP" tidak muncul untuk kepsek
- [x] Kolom "Aksi" tidak muncul di tabel untuk kepsek
- [x] Kepsek tidak bisa edit/hapus CP master

### ✅ Mapping Kurikulum
- [x] Kepsek bisa akses halaman Mapping Kurikulum
- [x] Dropdown select disabled untuk kepsek
- [x] Tombol "Simpan Mapping" tidak muncul untuk kepsek
- [x] Kepsek tidak bisa update mapping

### ✅ Sidebar Menu
- [x] Menu "Integrasi Dapodik" tidak muncul di sidebar kepsek
- [x] Menu "Penempatan Siswa" tidak muncul di sidebar kepsek
- [x] Menu "Kenaikan & Kelulusan" tidak muncul di sidebar kepsek
- [x] Menu "Input Nilai" tidak muncul di sidebar kepsek

### ✅ Kokurikuler
- [x] Kepsek bisa akses menu Perencanaan Kokurikuler
- [x] Kepsek bisa akses menu Pelaksanaan Kokurikuler
- [x] Kepsek bisa akses menu Penilaian/Asesmen Kokurikuler
- [x] Kepsek bisa akses menu Pelaporan Kokurikuler
- [x] Kepsek tidak bisa create/edit/delete dokumen kokurikuler

## Files Modified
1. `app/Config/Routes.php` - **PENTING**: Mengubah filter route untuk mengizinkan kepsek
2. `app/Controllers/Admin/MapelMaster.php` - Permission check untuk kepsek
3. `app/Views/admin/mapel_master/index.php` - Hide tombol aksi untuk kepsek
4. `app/Controllers/Admin/AdministrasiGuru.php` - Permission check untuk CP Master dan Mapping
5. `app/Views/admin/administrasi_guru/cp_master.php` - Hide tombol aksi untuk kepsek
6. `app/Views/admin/administrasi_guru/mapping.php` - Disable form untuk kepsek
7. `app/Views/layouts/partials/sidebar_kepsek.php` - Hapus menu yang tidak perlu
8. `app/Controllers/Admin/Kokurikuler.php` - Permission check untuk kepsek

## Route Changes (CRITICAL)

### Before:
```php
// Mapel Master - Hanya Admin & role 7
$routes->get('mapel-master', 'MapelMaster::index', $auth17);

// Administrasi Guru - Mapping & CP Master hanya untuk role 1,2,3,7
$routes->group('administrasi-guru', $auth123, function ($routes) use ($auth1237) {
    $routes->get('mapping', 'AdministrasiGuru::mapping', $auth1237);
    $routes->get('cp-master', 'AdministrasiGuru::cpMasterIndex', $auth1237);
});

// Kokurikuler - Hanya Admin & Guru
$routes->group('kokurikuler', $auth13, function ($routes) {
    // ...
});
```

### After:
```php
// Mapel Master - Admin & Kepsek
$routes->get('mapel-master', 'MapelMaster::index', $auth12);

// Administrasi Guru - Mapping & CP Master untuk Admin, Kepsek & Guru
$routes->group('administrasi-guru', $auth123, function ($routes) use ($auth123) {
    $routes->get('mapping', 'AdministrasiGuru::mapping', $auth123);
    $routes->get('cp-master', 'AdministrasiGuru::cpMasterIndex', $auth123);
});

// Kokurikuler - Admin, Kepsek & Guru
$routes->group('kokurikuler', $auth123, function ($routes) {
    // ...
});
```

**Filter Definitions**:
- `$auth1` = Admin only (role_id = 1)
- `$auth12` = Admin & Kepsek (role_id = 1, 2)
- `$auth13` = Admin & Guru (role_id = 1, 3)
- `$auth123` = Admin, Kepsek & Guru (role_id = 1, 2, 3)
- `$auth17` = Admin & role 7 (role_id = 1, 7)
- `$auth1237` = Admin, Kepsek, Guru & role 7 (role_id = 1, 2, 3, 7)

## Catatan
- Semua perubahan mengikuti prinsip: Kepsek bisa VIEW (read-only) tapi tidak bisa CREATE/UPDATE/DELETE
- Variable `$isReadOnly` digunakan secara konsisten di semua view untuk hide/disable elemen
- Permission check dilakukan di controller untuk keamanan
- Menu sidebar kepsek sekarang lebih fokus pada monitoring dan laporan, bukan operasional
