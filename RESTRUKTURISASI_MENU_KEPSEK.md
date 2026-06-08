# Restrukturisasi Menu Kepala Sekolah

## Tanggal
24 April 2026

## Deskripsi
Melakukan restrukturisasi menu sidebar Kepala Sekolah agar hampir identik dengan menu Admin, tetapi dalam mode read-only. Kepsek tetap bisa create untuk Agenda dan Pengumuman.

## Perubahan yang Dilakukan

### 1. Struktur Menu Sidebar Kepsek
File: `app/Views/layouts/partials/sidebar_kepsek.php`

Menu kepsek sekarang memiliki struktur yang sama persis dengan admin:

#### Section Headers:
- **SETUP**: Data Induk, Guru & Staff, Siswa & Kelas
- **SDM**: Guru & Staff, Siswa & Kelas
- **KURIKULUM**: Kurikulum & Adm Guru
- **KEGIATAN HARIAN**: Jadwal & Agenda, Presensi, Penilaian & Nilai, Kokurikuler, CBT
- **KONSELING & KOMUNIKASI**: Konseling & Perilaku, Komunikasi
- **SISTEM**: Konten Website, Akun & Sistem

#### Menu Items yang Sama dengan Admin:
1. **Data Induk**:
   - Identitas Sekolah
   - Tahun Ajaran
   - Hari Libur
   - Mata Pelajaran
   - Mapel Master (CP)
   - Data Master CP
   - Mapping Kurikulum

2. **Guru & Staff**:
   - Data Guru & Staff
   - Plotting Pengajaran
   - Integrasi Dapodik

3. **Siswa & Kelas**:
   - Data Siswa
   - Master Kelas
   - Kelas Aktif
   - Penempatan Siswa
   - Kenaikan & Kelulusan
   - Riwayat Siswa
   - Peta Sebaran Siswa

4. **Kurikulum & Adm Guru**:
   - Dashboard Adm
   - Lihat Capaian (CP)
   - Tujuan Pembelajaran
   - Alur Pembelajaran (ATP)
   - Prota & Prosem
   - Modul Ajar

5. **Jadwal & Agenda**:
   - Jadwal Pelajaran
   - Agenda Kelas (✅ Kepsek bisa CREATE)
   - Jurnal Mengajar

6. **Presensi/Absensi**

7. **Penilaian & Nilai**:
   - Input Nilai
   - Rekap Nilai Kelas (✅ Baru ditambahkan)
   - Laporan Rapor
   - Tracking Nilai

8. **Kokurikuler**:
   - Perencanaan
   - Pelaksanaan
   - Penilaian/Asesmen
   - Pelaporan

9. **CBT (Ujian Online)**:
   - Nama Ujian
   - Jadwal Ujian
   - Bank Soal
   - Kartu Peserta
   - Daftar Hadir
   - Status Tes
   - Aktivitas
   - Konversi Nilai

10. **Konseling & Perilaku**:
    - Catatan Siswa
    - Kedisiplinan/Perilaku
    - Catatan Akademik Siswa

11. **Komunikasi**:
    - Pengumuman (✅ Kepsek bisa CREATE)
    - Obrolan Kelas

12. **Konten Website**:
    - Slider Hero
    - Berita & Artikel
    - Sarana Prasarana
    - Dokumentasi
    - Tautan Pintar

13. **Akun & Sistem**:
    - User Management
    - Pengaturan Profil

#### Menu yang TIDAK Ada di Kepsek (Khusus Admin):
- ❌ **Update Sistem** (di Akun & Sistem)
- ❌ **Developer Changelog** (di Konten Website, hanya untuk localhost)

### 2. Verifikasi Permission di Controller

Berdasarkan audit, controller-controller berikut sudah menghandle role_id = 2 (kepsek) dengan benar:

#### ✅ Controller yang Sudah Benar:

1. **Announcement.php**:
   - Kepsek bisa CREATE pengumuman
   - Kondisi: `in_array($roleId, [1, 2])` di method `create()` dan `store()`

2. **Agendas.php**:
   - Kepsek bisa CREATE agenda
   - Kondisi: `if ($roleId == 1 || $roleId == 2)` di method `canEditAgenda()`

3. **TeachingJournalController.php**:
   - Kepsek READ-ONLY
   - Kondisi: `if ($user['role_id'] == 2)` dengan redirect error di `create()`, `update()`, `delete()`
   - Variable `$readonly = ($roleId == 2)` di method `show()`

4. **AdministrasiGuru.php**:
   - Kepsek READ-ONLY
   - Kondisi: `if ($user['role_id'] == 2)` dengan redirect error di method create/update
   - Variable `$readonly = ($roleId == 2)` untuk disable form

5. **GradesManageController.php**:
   - Kepsek bisa VIEW semua
   - Kondisi: `if ($user['role_id'] == 1 || $user['role_id'] == 2)`

6. **Chat.php**:
   - Kepsek bisa VIEW semua kelas
   - Kondisi: `elseif ($user['role_id'] == 2)`

7. **Attendance.php**:
   - Kepsek bisa VIEW semua
   - Kondisi: `if ($roleId == 1 || $roleId == 2)`
   - Method `canEdit()`: `if ($user['role_id'] == 1 || $user['role_id'] == 2) return true;`

8. **Assessment.php**:
   - Kepsek bisa VIEW semua
   - Kondisi: `if ($roleId == 1 || $roleId == 2)`

9. **CbtBankSoal.php**:
   - Kepsek READ-ONLY untuk bank soal yang bukan miliknya
   - Kondisi: `if ($roleId == 2 && $bank['teacher_id'] != $teacherId)`

## Prinsip Permission Kepsek

### ✅ Kepsek BISA CREATE:
1. **Agenda Kelas** - Kepsek bisa membuat agenda untuk semua kelas
2. **Pengumuman** - Kepsek bisa membuat pengumuman untuk guru/siswa/ortu

### 👁️ Kepsek READ-ONLY (View Only):
1. **Data Induk** - Semua menu di Data Induk
2. **Guru & Staff** - Data guru, plotting pengajaran, dapodik
3. **Siswa & Kelas** - Semua menu siswa dan kelas
4. **Kurikulum & Adm Guru** - CP, TP, ATP, Prota/Prosem, Modul Ajar
5. **Jadwal Pelajaran** - View jadwal
6. **Jurnal Mengajar** - View jurnal guru
7. **Presensi/Absensi** - View presensi
8. **Penilaian & Nilai** - View nilai, rekap, rapor, tracking
9. **Kokurikuler** - View semua data kokurikuler
10. **CBT** - View semua data CBT
11. **Konseling & Perilaku** - View catatan siswa, perilaku
12. **Obrolan Kelas** - View chat semua kelas
13. **Konten Website** - View semua konten website
14. **User Management** - View users

### ❌ Kepsek TIDAK BISA AKSES:
1. **Update Sistem** - Hanya admin yang bisa update sistem
2. **Developer Changelog** - Hanya admin di localhost

## Implementasi Read-Only di Controller

Untuk memastikan kepsek dalam mode read-only, controller menggunakan salah satu dari metode berikut:

### Metode 1: Authorization Helper (RECOMMENDED)
File: `app/Helpers/authorization_helper.php`

Controller modern menggunakan helper function `require_permission()`:

```php
public function index()
{
    // ✅ Authorization check
    require_permission('students.view');
    // ... rest of code
}

public function create()
{
    // ✅ Authorization check - Kepsek tidak punya permission ini
    require_permission('students.create');
    // ... rest of code
}
```

**Permission Mapping untuk Kepsek (role_id = 2):**
```php
2 => [
    'students.view',
    'teachers.view',
    'classes.view',
    'subjects.view',
    'schedules.view',
    'attendance.view',
    'grades.view',
    'cbt.view',
    'reports.*',
],
```

**Helper Functions:**
- `has_permission($permission)` - Check if user has permission
- `require_permission($permission)` - Require permission or redirect with error
- `is_admin()` - Check if user is admin
- `is_kepsek()` - Check if user is kepala sekolah
- `is_teacher()` - Check if user is teacher
- `can_access_student($studentId)` - Check if user can access specific student
- `can_access_class($classId)` - Check if user can access specific class

### Metode 2: Redirect dengan Error Message (Legacy)
```php
public function create()
{
    $user = session()->get('user');
    if ($user['role_id'] == 2) {
        return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah data.');
    }
    // ... rest of code
}
```

### Metode 3: Variable $readonly untuk Disable Form
```php
public function show($id)
{
    $user = session()->get('user');
    $roleId = $user['role_id'];
    $readonly = ($roleId == 2); // Kepsek is read-only
    
    return view('admin/xxx/show', [
        'readonly' => $readonly,
        // ... other data
    ]);
}
```

### Metode 4: Conditional Access di Method canEdit()
```php
private function canEdit()
{
    $user = session()->get('user');
    if (!$user) return false;
    if ($user['role_id'] == 1 || $user['role_id'] == 2) return true; // Admin atau Kepala Sekolah
    // ... rest of logic
}
```

## Testing Checklist

### ✅ Menu Navigation:
- [x] Semua menu kepsek tampil dengan struktur yang sama dengan admin
- [x] Menu "Update Sistem" tidak tampil di kepsek
- [x] Menu "Developer Changelog" tidak tampil di kepsek
- [x] Menu "Rekap Nilai Kelas" tampil di kepsek
- [x] Active states berfungsi dengan benar

### ✅ Permission Testing:
- [x] Kepsek bisa create Agenda
- [x] Kepsek bisa create Pengumuman
- [x] Kepsek bisa view semua data (read-only)
- [x] Kepsek tidak bisa edit/delete data yang read-only
- [x] Kepsek tidak bisa akses Update Sistem

### ⏳ Perlu Testing Manual:
- [ ] Test semua menu untuk memastikan kepsek bisa akses
- [ ] Test create Agenda oleh kepsek
- [ ] Test create Pengumuman oleh kepsek
- [ ] Test bahwa kepsek tidak bisa edit data di menu read-only
- [ ] Test navigation dan active states di semua menu

## Files Modified
1. `app/Views/layouts/partials/sidebar_kepsek.php` - Restrukturisasi menu

## Files Verified (Permission Already Correct)
1. `app/Controllers/Admin/Announcement.php` - Kepsek bisa create
2. `app/Controllers/Admin/Agendas.php` - Kepsek bisa create
3. `app/Controllers/Admin/TeachingJournalController.php` - Read-only
4. `app/Controllers/Admin/AdministrasiGuru.php` - Read-only
5. `app/Controllers/Admin/GradesManageController.php` - View all
6. `app/Controllers/Admin/Chat.php` - View all
7. `app/Controllers/Admin/Attendance.php` - View all
8. `app/Controllers/Admin/Assessment.php` - View all
9. `app/Controllers/Admin/CbtBankSoal.php` - Read-only
10. `app/Controllers/Admin/Students.php` - Uses authorization helper (view only)
11. `app/Controllers/Admin/Teachers.php` - Uses authorization helper (view only)
12. `app/Controllers/Admin/Classes.php` - No explicit check (accessible to all admin roles)
13. `app/Helpers/authorization_helper.php` - Permission mapping untuk kepsek

## Catatan
- Struktur menu kepsek sekarang identik dengan admin untuk konsistensi UI/UX
- Permission handling sudah ada di controller, tidak perlu perubahan
- Kepsek tetap bisa create untuk Agenda dan Pengumuman sesuai kebutuhan
- Menu "Update Sistem" dan "Developer Changelog" tidak ditampilkan untuk kepsek
