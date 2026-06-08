# Perbaikan Mapel Master - Validasi Level Sekolah

## Masalah
Halaman Mapel Master (`/admin/mapel-master`) tidak memvalidasi jenjang berdasarkan level sekolah. Admin bisa menambahkan mapel untuk jenjang yang tidak sesuai dengan level sekolah (misal: menambahkan mapel SMA di sekolah SD).

## Solusi Implementasi

### 1. Controller (`app/Controllers/Admin/MapelMaster.php`)

#### A. Method `index()` - Filter Data Berdasarkan Level Sekolah

```php
public function index()
{
    $data['title'] = 'Mapel Master (CP)';
    
    // Get school level to filter mapel
    $schoolModel = new \App\Models\SchoolModel();
    $school = $schoolModel->first();
    $schoolLevel = $school['level'] ?? 1; // Default to SD if not set
    
    // Filter mapel by school level (jenjang_id should match school level)
    $data['mapel'] = $this->mapelModel
        ->select('mapel_master.*, jenjang_master.nama as jenjang_nama')
        ->join('jenjang_master', 'jenjang_master.id = mapel_master.jenjang_id')
        ->where('mapel_master.jenjang_id', $schoolLevel)
        ->findAll();
        
    // Only show jenjang that matches school level
    $data['jenjang'] = $this->jenjangModel->where('id', $schoolLevel)->findAll();
    $data['school_level'] = $schoolLevel;
    $data['school_level_name'] = match((int)$schoolLevel) {
        1 => 'SD / Sederajat',
        2 => 'SMP / Sederajat',
        3 => 'SMA / Sederajat',
        default => 'Unknown'
    };
    
    return view('admin/mapel_master/index', $data);
}
```

**Perubahan:**
- Mengambil level sekolah dari `school_profile`
- Filter data mapel hanya yang sesuai dengan level sekolah (`jenjang_id = school level`)
- Filter jenjang dropdown hanya yang sesuai dengan level sekolah
- Menambahkan `school_level_name` untuk ditampilkan di view

#### B. Method `store()` - Validasi Jenjang Saat Simpan

```php
public function store()
{
    // Get school level for validation
    $schoolModel = new \App\Models\SchoolModel();
    $school = $schoolModel->first();
    $schoolLevel = $school['level'] ?? 1;
    
    $id = $this->request->getPost('id');
    $jenjangId = $this->request->getPost('jenjang_id');
    
    // Validate that jenjang_id matches school level
    if ((int)$jenjangId !== (int)$schoolLevel) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Jenjang tidak sesuai dengan level sekolah. Silakan pilih jenjang yang sesuai.');
    }
    
    $data = [
        'jenjang_id' => $jenjangId,
        'kode'       => $this->request->getPost('kode'),
        'nama'       => $this->request->getPost('nama'),
        'kelompok'   => $this->request->getPost('kelompok'),
        'is_active'  => $this->request->getPost('is_active') ?? 1,
    ];

    if ($id) {
        $this->mapelModel->update($id, $data);
        $msg = 'Mapel Master berhasil diperbarui.';
    } else {
        $this->mapelModel->insert($data);
        $msg = 'Mapel Master berhasil ditambahkan.';
    }

    return redirect()->to(base_url('admin/mapel-master'))->with('success', $msg);
}
```

**Perubahan:**
- Menambahkan validasi: `jenjang_id` harus sama dengan `school level`
- Jika tidak sesuai, redirect dengan error message
- Mencegah penyimpanan data mapel dengan jenjang yang salah

### 2. View (`app/Views/admin/mapel_master/index.php`)

#### A. Header - Menampilkan Level Sekolah

```php
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">📚 Mapel Master (CP)</h1>
        <small class="text-muted">Level Sekolah: <span class="badge bg-primary"><?= esc($school_level_name) ?></span></small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mapelModal">
        <i class="bi bi-plus-lg"></i> Tambah Mapel Master
    </button>
</div>
```

**Perubahan:**
- Menampilkan badge level sekolah di header
- User tahu bahwa mapel yang ditampilkan sesuai dengan level sekolah

#### B. Alert Error Message

```php
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
```

**Perubahan:**
- Menambahkan alert untuk menampilkan error message dari validasi

#### C. Modal Form - Jenjang Otomatis

```php
<div class="modal-body">
    <!-- Hidden field for jenjang_id (auto-filled based on school level) -->
    <input type="hidden" name="jenjang_id" id="jenjang_id" value="<?= $school_level ?>">
    
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Jenjang otomatis disesuaikan dengan level sekolah: <strong><?= esc($school_level_name) ?></strong>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Kode Mapel:</label>
        <input type="text" name="kode" id="kode" class="form-control" required placeholder="MISAL: MTK, IPA">
    </div>
    <!-- ... other fields ... -->
</div>
```

**Perubahan:**
- Menghapus dropdown jenjang (tidak perlu dipilih manual)
- Menggunakan hidden field dengan value otomatis dari `school_level`
- Menampilkan info alert bahwa jenjang otomatis disesuaikan
- User tidak bisa salah pilih jenjang

#### D. JavaScript - Reset Jenjang ke School Level

```javascript
$('#mapelModal').on('hidden.bs.modal', function () {
    $('#mapel_id').val('');
    $('#jenjang_id').val('<?= $school_level ?>'); // Reset to school level
    $('#kode').val('');
    $('#nama').val('');
    $('#kelompok').val('');
    $('#modalTitle').text('Tambah Mapel Master');
});
```

**Perubahan:**
- Saat modal ditutup, jenjang_id di-reset ke school level
- Memastikan jenjang selalu sesuai dengan level sekolah

## Mapping Level Sekolah

| Level | Jenjang | Keterangan |
|-------|---------|------------|
| 1 | SD | Sekolah Dasar / Sederajat |
| 2 | SMP | Sekolah Menengah Pertama / Sederajat |
| 3 | SMA | Sekolah Menengah Atas / Sederajat |

Level sekolah disimpan di tabel `school_profile` kolom `level`.

## Alur Kerja

### Skenario 1: Tambah Mapel Baru
1. Admin klik "Tambah Mapel Master"
2. Modal terbuka dengan info level sekolah
3. Jenjang otomatis terisi sesuai level sekolah (hidden field)
4. Admin isi kode, nama, dan kelompok
5. Submit form
6. Controller validasi: jenjang_id harus sama dengan school level
7. Jika valid: simpan data
8. Jika tidak valid: redirect dengan error message

### Skenario 2: Edit Mapel
1. Admin klik tombol edit
2. Modal terbuka dengan data mapel
3. Jenjang tetap sesuai level sekolah (tidak bisa diubah)
4. Admin edit kode, nama, atau kelompok
5. Submit form
6. Controller validasi: jenjang_id harus sama dengan school level
7. Update data

### Skenario 3: Tampil Data
1. Halaman index hanya menampilkan mapel dengan `jenjang_id = school level`
2. Mapel dari jenjang lain tidak ditampilkan
3. Badge level sekolah ditampilkan di header

## Keuntungan

1. **Data Konsisten**: Mapel selalu sesuai dengan level sekolah
2. **User-Friendly**: Admin tidak perlu pilih jenjang manual (otomatis)
3. **Validasi Ketat**: Mencegah input data yang salah
4. **Informasi Jelas**: Badge level sekolah membantu user memahami konteks
5. **Error Handling**: Error message yang jelas jika ada kesalahan

## Testing

### Test 1: Sekolah SD (level = 1)
```sql
UPDATE school_profile SET level = 1;
```
- Halaman mapel-master hanya menampilkan mapel dengan jenjang_id = 1
- Form tambah/edit otomatis set jenjang_id = 1
- Badge menampilkan "SD / Sederajat"

### Test 2: Sekolah SMP (level = 2)
```sql
UPDATE school_profile SET level = 2;
```
- Halaman mapel-master hanya menampilkan mapel dengan jenjang_id = 2
- Form tambah/edit otomatis set jenjang_id = 2
- Badge menampilkan "SMP / Sederajat"

### Test 3: Validasi Error
- Coba manipulasi form (inspect element) untuk ubah jenjang_id
- Submit form dengan jenjang_id yang tidak sesuai
- Harus muncul error: "Jenjang tidak sesuai dengan level sekolah"

## File yang Dimodifikasi

1. `app/Controllers/Admin/MapelMaster.php` - Added school level filtering and validation
2. `app/Views/admin/mapel_master/index.php` - Updated UI to show school level and auto-fill jenjang

## Status
✅ Selesai - Mapel Master sekarang memvalidasi dan memfilter berdasarkan level sekolah
