# Perbaikan Manajemen Kelas - Validasi Level Sekolah

## Masalah
Halaman Manajemen Kelas (`/admin/classes`) perlu validasi yang lebih ketat untuk tingkat kelas berdasarkan level sekolah:
- SD: Tingkat 1-6
- SMP: Tingkat 7-9
- SMA: Tingkat 10-12

Sebelumnya hanya ada validasi maksimal, belum ada validasi minimal.

## Solusi Implementasi

### 1. Controller (`app/Controllers/Admin/Classes.php`)

#### A. Method `index()` - Menambahkan Info Level Sekolah

```php
public function index()
{
    $model = new ClassModel();
    $data['classes'] = $model->select('classes.*, teachers.name as teacher_name')
        ->join('teachers', 'teachers.id = classes.teacher_id', 'left')
        ->orderBy('classes.level', 'ASC')
        ->orderBy('classes.name', 'ASC')
        ->findAll();

    // Get school level info
    $schoolModel = new \App\Models\SchoolModel();
    $school = $schoolModel->first() ?: [];
    $data['school_level'] = $school['level'] ?? 1;
    $data['school_level_name'] = match((int)($school['level'] ?? 1)) {
        1 => 'SD / Sederajat',
        2 => 'SMP / Sederajat',
        3 => 'SMA / Sederajat',
        default => 'Unknown'
    };
    $data['max_level'] = $this->getMaxLevel();
    $data['level_range'] = match((int)($school['level'] ?? 1)) {
        1 => '1-6',
        2 => '7-9',
        3 => '10-12',
        default => '1-6'
    };

    return view('admin/classes/index', $data);
}
```

**Perubahan:**
- Menambahkan ordering berdasarkan level dan nama kelas
- Menambahkan info school_level, school_level_name, dan level_range untuk view

#### B. Method `create()` - Menambahkan Min & Max Level

```php
public function create()
{
    $teacherModel = new TeacherModel();
    $schoolModel = new \App\Models\SchoolModel();
    $school = $schoolModel->first() ?: [];
    
    $maxLevel = $this->getMaxLevel();
    $minLevel = match((int)($school['level'] ?? 1)) {
        1 => 1,  // SD: 1-6
        2 => 7,  // SMP: 7-9
        3 => 10, // SMA: 10-12
        default => 1
    };
    
    $data = [
        'teachers' => $teacherModel->findAll(),
        'maxLevel' => $maxLevel,
        'minLevel' => $minLevel,
        'school_level' => $school['level'] ?? 1,
        'school_level_name' => match((int)($school['level'] ?? 1)) {
            1 => 'SD / Sederajat',
            2 => 'SMP / Sederajat',
            3 => 'SMA / Sederajat',
            default => 'Unknown'
        },
        'level_range' => match((int)($school['level'] ?? 1)) {
            1 => '1-6',
            2 => '7-9',
            3 => '10-12',
            default => '1-6'
        },
    ];

    return view('admin/classes/create', $data);
}
```

**Perubahan:**
- Menambahkan `minLevel` berdasarkan level sekolah
- Menambahkan info lengkap untuk ditampilkan di view

#### C. Method `store()` - Validasi Min & Max Level

```php
public function store()
{
    $schoolModel = new \App\Models\SchoolModel();
    $school = $schoolModel->first() ?: [];
    $schoolLevel = $school['level'] ?? 1;
    
    $maxLevel = $this->getMaxLevel();
    $minLevel = match((int)$schoolLevel) {
        1 => 1,  // SD: 1-6
        2 => 7,  // SMP: 7-9
        3 => 10, // SMA: 10-12
        default => 1
    };
    
    $inputLevel = $this->request->getPost('level');
    
    // Validate level range based on school level
    if ($inputLevel < $minLevel || $inputLevel > $maxLevel) {
        $levelRange = match((int)$schoolLevel) {
            1 => '1-6 (SD)',
            2 => '7-9 (SMP)',
            3 => '10-12 (SMA)',
            default => '1-6'
        };
        return redirect()->back()
            ->withInput()
            ->with('error', "Tingkat kelas harus antara {$levelRange} sesuai dengan level sekolah.");
    }
    
    $rules = [
        'name' => 'required',
        'level' => "required|is_natural_no_zero|greater_than_equal_to[$minLevel]|less_than_equal_to[$maxLevel]",
    ];

    if (!$this->validate($rules)) {
        return redirect()->back()->withInput()->with('error', 'Validasi gagal: Tingkat harus antara ' . $minLevel . ' dan ' . $maxLevel);
    }

    $model = new ClassModel();
    $model->save([
        'name' => $this->request->getPost('name'),
        'level' => $inputLevel,
        'teacher_id' => $this->request->getPost('teacher_id') ?: null,
    ]);

    return redirect()->to('/admin/classes')->with('success', 'Kelas berhasil ditambahkan');
}
```

**Perubahan:**
- Menambahkan validasi manual untuk range level
- Menambahkan validasi `greater_than_equal_to[$minLevel]`
- Error message yang lebih spesifik dengan range yang jelas

#### D. Method `edit()` & `update()` - Sama dengan create & store

Implementasi yang sama diterapkan untuk edit dan update.

### 2. View Updates

#### A. Index View (`app/Views/admin/classes/index.php`)

```php
<div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <div>
      <h1 class="mb-0">Manajemen Kelas</h1>
      <small class="text-muted">
        Level Sekolah: <span class="badge bg-primary"><?= esc($school_level_name) ?></span> | 
        Tingkat Kelas: <?= esc($level_range) ?>
      </small>
    </div>
    <!-- ... -->
</div>
```

**Perubahan:**
- Menampilkan badge level sekolah
- Menampilkan range tingkat kelas yang diizinkan
- Improved UI dengan card dan table responsive

#### B. Create View (`app/Views/admin/classes/create.php`)

```php
<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  Level Sekolah: <strong><?= esc($school_level_name) ?></strong> | 
  Tingkat Kelas yang Diizinkan: <strong><?= esc($level_range) ?></strong>
</div>

<div class="mb-3">
  <label class="form-label">Tingkat <span class="text-danger">*</span></label>
  <input type="number" name="level" class="form-control" 
         min="<?= $minLevel ?>" max="<?= $maxLevel ?>" 
         placeholder="<?= $minLevel ?> - <?= $maxLevel ?>" required>
  <small class="text-muted">
    Tingkat kelas harus antara <strong><?= $minLevel ?></strong> dan <strong><?= $maxLevel ?></strong> 
    sesuai level sekolah <?= esc($school_level_name) ?>
  </small>
</div>
```

**Perubahan:**
- Alert info menampilkan level sekolah dan range yang diizinkan
- Input number dengan min dan max yang dinamis
- Helper text yang jelas
- Breadcrumb navigation
- Improved form layout dengan card

#### C. Edit View (`app/Views/admin/classes/edit.php`)

Implementasi yang sama dengan create view.

## Mapping Level Sekolah ke Tingkat Kelas

| Level Sekolah | Jenjang | Tingkat Kelas | Keterangan |
|---------------|---------|---------------|------------|
| 1 | SD / Sederajat | 1-6 | Kelas 1 sampai 6 |
| 2 | SMP / Sederajat | 7-9 | Kelas 7 sampai 9 |
| 3 | SMA / Sederajat | 10-12 | Kelas 10 sampai 12 |

## Validasi yang Diterapkan

### 1. Validasi Server-Side (Controller)
- **Manual Check**: Cek apakah input level berada dalam range min-max
- **CodeIgniter Validation**: 
  - `required`: Tingkat wajib diisi
  - `is_natural_no_zero`: Harus angka positif (> 0)
  - `greater_than_equal_to[$minLevel]`: Minimal sesuai jenjang
  - `less_than_equal_to[$maxLevel]`: Maksimal sesuai jenjang

### 2. Validasi Client-Side (HTML5)
- `min="<?= $minLevel ?>"`: Browser tidak akan accept nilai < minLevel
- `max="<?= $maxLevel ?>"`: Browser tidak akan accept nilai > maxLevel
- `required`: Field wajib diisi

## Alur Kerja

### Skenario 1: Tambah Kelas di Sekolah SMP (level = 2)
1. Admin klik "Tambah Kelas"
2. Halaman create menampilkan:
   - Alert: "Level Sekolah: SMP / Sederajat | Tingkat Kelas yang Diizinkan: 7-9"
   - Input tingkat dengan min=7, max=9
3. Admin isi nama kelas (misal: "7A") dan tingkat (misal: 7)
4. Submit form
5. Controller validasi:
   - Cek apakah 7 >= 7 (minLevel) ✓
   - Cek apakah 7 <= 9 (maxLevel) ✓
6. Data tersimpan

### Skenario 2: Coba Input Tingkat di Luar Range
1. Admin di sekolah SMP coba input tingkat 10 (SMA)
2. Browser HTML5 validation akan mencegah (karena max=9)
3. Jika bypass HTML5 (inspect element), server-side validation akan menolak:
   - Error: "Tingkat kelas harus antara 7-9 (SMP) sesuai dengan level sekolah"
4. Redirect back dengan error message

### Skenario 3: Edit Kelas
1. Admin klik edit pada kelas yang ada
2. Form pre-filled dengan data kelas
3. Validasi sama seperti create
4. Update data jika valid

## Keuntungan

1. **Validasi Ketat**: Tidak bisa input tingkat kelas di luar range jenjang
2. **User-Friendly**: Info yang jelas tentang range yang diizinkan
3. **Double Validation**: Client-side (HTML5) + Server-side (Controller)
4. **Error Message Jelas**: User tahu kenapa input ditolak
5. **Konsistensi Data**: Kelas selalu sesuai dengan level sekolah
6. **Improved UI**: Card layout, breadcrumb, icons, responsive table

## Testing

### Test 1: Sekolah SD (level = 1)
```sql
UPDATE school_profile SET level = 1;
```
- Range tingkat: 1-6
- Input tingkat 7 → Ditolak
- Input tingkat 3 → Diterima

### Test 2: Sekolah SMP (level = 2)
```sql
UPDATE school_profile SET level = 2;
```
- Range tingkat: 7-9
- Input tingkat 6 → Ditolak
- Input tingkat 10 → Ditolak
- Input tingkat 8 → Diterima

### Test 3: Sekolah SMA (level = 3)
```sql
UPDATE school_profile SET level = 3;
```
- Range tingkat: 10-12
- Input tingkat 9 → Ditolak
- Input tingkat 11 → Diterima

### Test 4: Bypass HTML5 Validation
- Inspect element, ubah max="12"
- Input tingkat 12 di sekolah SMP
- Submit form
- Server-side validation menolak dengan error message

## File yang Dimodifikasi

1. `app/Controllers/Admin/Classes.php` - Added min/max level validation based on school level
2. `app/Views/admin/classes/index.php` - Added school level info and improved UI
3. `app/Views/admin/classes/create.php` - Added level range info and validation
4. `app/Views/admin/classes/edit.php` - Added level range info and validation

## Status
✅ Selesai - Manajemen Kelas sekarang memvalidasi tingkat kelas berdasarkan level sekolah dengan range yang ketat (SD: 1-6, SMP: 7-9, SMA: 10-12)
