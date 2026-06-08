# Perbaikan CP Master - Validasi Level Sekolah

## Masalah
Halaman CP Master (`/admin/administrasi-guru/cp-master`) belum memvalidasi mata pelajaran dan fase berdasarkan level sekolah. Admin bisa menambahkan CP untuk mapel/fase yang tidak sesuai dengan level sekolah.

## Solusi Implementasi

### 1. Controller (`app/Controllers/Admin/AdministrasiGuru.php`)

#### A. Method `cpMasterIndex()` - Filter Data Berdasarkan Level Sekolah

```php
public function cpMasterIndex()
{
    $data['title'] = 'Data Master CP';
    
    // Get school level to filter mapel
    $school = $this->db->table('school_profile')->get()->getRowArray();
    $schoolLevel = $school['level'] ?? 1;

    // Filter CP by school level (through mapel_master join)
    $data['cp_master'] = $this->cpMasterModel
        ->select('cp_master.*, mapel_master.nama as mapel_nama, jenjang_master.nama as jenjang_nama')
        ->join('mapel_master', 'mapel_master.id = cp_master.mapel_master_id')
        ->join('jenjang_master', 'jenjang_master.id = mapel_master.jenjang_id')
        ->where('mapel_master.jenjang_id', $schoolLevel)
        ->paginate(20);
    $data['pager'] = $this->cpMasterModel->pager;
    
    // Filter mapel by school level
    $data['mapel_master'] = $this->mapelMasterModel->where('jenjang_id', $schoolLevel)->findAll();
    
    // Add school level info
    $data['school_level'] = $schoolLevel;
    $data['school_level_name'] = match((int)$schoolLevel) {
        1 => 'SD / Sederajat',
        2 => 'SMP / Sederajat',
        3 => 'SMA / Sederajat',
        default => 'Unknown'
    };
    
    return view('admin/administrasi_guru/cp_master', $data);
}
```

**Perubahan:**
- Filter CP hanya yang mapelnya sesuai dengan level sekolah
- Filter mapel dropdown hanya yang sesuai dengan level sekolah
- Menambahkan `school_level` dan `school_level_name` untuk view

#### B. Method `cpMasterStore()` - Validasi Mapel Saat Simpan

```php
public function cpMasterStore()
{
    // Get school level for validation
    $school = $this->db->table('school_profile')->get()->getRowArray();
    $schoolLevel = $school['level'] ?? 1;
    
    $id = $this->request->getPost('id');
    $mapelMasterId = $this->request->getPost('mapel_master_id');
    
    // Validate that selected mapel belongs to school level
    $mapel = $this->mapelMasterModel->find($mapelMasterId);
    if (!$mapel || (int)$mapel['jenjang_id'] !== (int)$schoolLevel) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Mata pelajaran tidak sesuai dengan level sekolah. Silakan pilih mapel yang sesuai.');
    }
    
    // ... rest of the code
}
```

**Perubahan:**
- Menambahkan validasi: mapel yang dipilih harus sesuai dengan level sekolah
- Jika tidak sesuai, redirect dengan error message
- Mencegah penyimpanan CP dengan mapel yang salah

### 2. View (`app/Views/admin/administrasi_guru/cp_master.php`)

#### A. Header - Menampilkan Level Sekolah

```php
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">📜 Master Capaian Pembelajaran (CP)</h1>
        <small class="text-muted">Level Sekolah: <span class="badge bg-primary"><?= esc($school_level_name) ?></span></small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cpModal">
        <i class="bi bi-plus-lg"></i> Tambah Master CP
    </button>
</div>
```

**Perubahan:**
- Menampilkan badge level sekolah di header
- User tahu bahwa CP yang ditampilkan sesuai dengan level sekolah

#### B. Modal Form - Info Level & Filter Fase

```php
<div class="modal-body">
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Hanya menampilkan mata pelajaran untuk level sekolah: <strong><?= esc($school_level_name) ?></strong>
    </div>
    
    <div class="row">
        <div class="col-md-5 mb-3">
            <label class="form-label">Mata Pelajaran Master:</label>
            <select name="mapel_master_id" id="mapel_master_id" class="form-select" required>
                <option value="">-- Pilih Mapel --</option>
                <?php foreach ($mapel_master as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= esc($m['nama']) ?> (<?= esc($m['kode']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Mapel sesuai jenjang <?= esc($school_level_name) ?></small>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Fase:</label>
            <select name="fase" id="fase" class="form-select" required>
                <?php if ($school_level == 1): // SD ?>
                    <option value="A">Fase A (Kls 1-2)</option>
                    <option value="B">Fase B (Kls 3-4)</option>
                    <option value="C">Fase C (Kls 5-6)</option>
                <?php elseif ($school_level == 2): // SMP ?>
                    <option value="D">Fase D (Kls 7-9)</option>
                <?php elseif ($school_level == 3): // SMA ?>
                    <option value="E">Fase E (Kls 10)</option>
                    <option value="F">Fase F (Kls 11-12)</option>
                <?php endif; ?>
            </select>
            <small class="text-muted">Fase sesuai jenjang</small>
        </div>
    </div>
</div>
```

**Perubahan:**
- Menampilkan info alert bahwa mapel disesuaikan dengan level sekolah
- Filter dropdown fase berdasarkan level sekolah:
  - SD: Fase A, B, C
  - SMP: Fase D
  - SMA: Fase E, F
- Menambahkan helper text untuk clarity

## Mapping Level Sekolah ke Fase

| Level | Jenjang | Fase yang Tersedia | Keterangan |
|-------|---------|-------------------|------------|
| 1 | SD | A, B, C | Fase A (Kls 1-2), B (Kls 3-4), C (Kls 5-6) |
| 2 | SMP | D | Fase D (Kls 7-9) |
| 3 | SMA | E, F | Fase E (Kls 10), F (Kls 11-12) |

## Alur Kerja

### Skenario 1: Tambah CP Baru
1. Admin klik "Tambah Master CP"
2. Modal terbuka dengan info level sekolah
3. Dropdown mapel hanya menampilkan mapel sesuai level sekolah
4. Dropdown fase hanya menampilkan fase sesuai level sekolah
5. Admin isi elemen, deskripsi CP, nomor SK, dan tahun
6. Submit form
7. Controller validasi: mapel harus sesuai dengan school level
8. Jika valid: simpan data
9. Jika tidak valid: redirect dengan error message

### Skenario 2: Edit CP
1. Admin klik tombol edit
2. Modal terbuka dengan data CP
3. Mapel dan fase tetap sesuai level sekolah
4. Admin edit data
5. Submit form
6. Controller validasi: mapel harus sesuai dengan school level
7. Update data

### Skenario 3: Tampil Data
1. Halaman index hanya menampilkan CP dengan mapel yang `jenjang_id = school level`
2. CP dari jenjang lain tidak ditampilkan
3. Badge level sekolah ditampilkan di header

## Keuntungan

1. **Data Konsisten**: CP selalu sesuai dengan level sekolah
2. **User-Friendly**: Dropdown fase otomatis disesuaikan dengan level
3. **Validasi Ketat**: Mencegah input CP dengan mapel yang salah
4. **Informasi Jelas**: Badge dan alert membantu user memahami konteks
5. **Error Handling**: Error message yang jelas jika ada kesalahan
6. **Fase Relevan**: Hanya menampilkan fase yang sesuai dengan jenjang

## Testing

### Test 1: Sekolah SD (level = 1)
```sql
UPDATE school_profile SET level = 1;
```
- Halaman CP Master hanya menampilkan CP dengan mapel jenjang_id = 1
- Dropdown mapel hanya menampilkan mapel SD
- Dropdown fase hanya menampilkan Fase A, B, C
- Badge menampilkan "SD / Sederajat"

### Test 2: Sekolah SMP (level = 2)
```sql
UPDATE school_profile SET level = 2;
```
- Halaman CP Master hanya menampilkan CP dengan mapel jenjang_id = 2
- Dropdown mapel hanya menampilkan mapel SMP
- Dropdown fase hanya menampilkan Fase D
- Badge menampilkan "SMP / Sederajat"

### Test 3: Sekolah SMA (level = 3)
```sql
UPDATE school_profile SET level = 3;
```
- Halaman CP Master hanya menampilkan CP dengan mapel jenjang_id = 3
- Dropdown mapel hanya menampilkan mapel SMA
- Dropdown fase hanya menampilkan Fase E, F
- Badge menampilkan "SMA / Sederajat"

### Test 4: Validasi Error
- Coba manipulasi form untuk pilih mapel dari jenjang lain
- Submit form dengan mapel yang tidak sesuai
- Harus muncul error: "Mata pelajaran tidak sesuai dengan level sekolah"

## File yang Dimodifikasi

1. `app/Controllers/Admin/AdministrasiGuru.php` - Added school level filtering and validation for CP Master
2. `app/Views/admin/administrasi_guru/cp_master.php` - Updated UI to show school level and filter fase options

## Hubungan dengan Mapel Master

CP Master bergantung pada Mapel Master. Oleh karena itu:
- Mapel Master harus sudah difilter berdasarkan level sekolah (sudah diimplementasi sebelumnya)
- CP Master akan otomatis terfilter karena menggunakan mapel yang sudah terfilter
- Validasi di CP Master memastikan tidak ada CP yang menggunakan mapel dari jenjang lain

## Status
✅ Selesai - CP Master sekarang memvalidasi dan memfilter berdasarkan level sekolah dengan fase yang sesuai
