# Phase 2: Form & Controller - COMPLETED ✅

## Perubahan yang Telah Dilakukan

### 1. Form Create (`app/Views/admin/kokurikuler/create.php`)

#### A. Perubahan Struktur Form
- **Renamed**: "Bentuk Kegiatan" → "Jenis Kokurikuler"
  - Field name: `bentuk_kegiatan` → `jenis_kokurikuler`
  - Label: "Bentuk Kegiatan" → "Jenis Kokurikuler"
  
- **Added**: Input "Bentuk Kegiatan Konkret"
  - Field name: `bentuk_kegiatan_konkret`
  - Type: Text input
  - Placeholder: "Contoh: Membuat poster dan video edukasi digital"
  - Required, min 10 karakter
  - Akan muncul di rapor sebagai kegiatan nyata

#### B. Update Dropdown Dimensi Profil
- Hapus teks bahasa Inggris dari semua dimensi
- Urutan: Keimanan, Kewargaan, Penalaran Kritis, Kreativitas, Kemandirian, Kolaborasi, Kesehatan, Komunikasi

#### C. Template Lintas Disiplin - Tambah Sub Dimensi
```html
<div class="mb-3">
    <label>Sub Dimensi <span class="text-danger">*</span></label>
    <select name="lintas_subdimensi_{{INDEX}}" class="form-select subdimensi-select" required disabled>
        <option value="">-- Pilih Dimensi Profil terlebih dahulu --</option>
    </select>
</div>
```

#### D. Template 7 KAIH - Tambah Sub Dimensi
```html
<div class="mb-3">
    <label>Sub Dimensi <span class="text-danger">*</span></label>
    <select name="kaih_subdimensi_{{INDEX}}" class="form-select kaih-subdimensi-select" required disabled>
        <option value="">-- Pilih Dimensi Profil terlebih dahulu --</option>
    </select>
</div>
```

#### E. JavaScript Updates

**1. Data Sub Dimensi**
```javascript
const subDimensiData = <?= json_encode($subDimensiData) ?>;
```

**2. Function: populateSubDimensiOptions()**
```javascript
function populateSubDimensiOptions($select, dimensi) {
    $select.empty();
    $select.append('<option value="">-- Pilih Sub Dimensi --</option>');
    
    if (dimensi && subDimensiData[dimensi]) {
        subDimensiData[dimensi].forEach(function(subDimensi) {
            $select.append(new Option(subDimensi, subDimensi));
        });
        $select.prop('disabled', false);
    } else {
        $select.append('<option value="">-- Pilih Dimensi Profil terlebih dahulu --</option>');
        $select.prop('disabled', true);
    }
}
```

**3. Event Handler: Lintas Disiplin Dimensi Change**
```javascript
$(document).on('change', '.dimensi-select', function() {
    const dimensi = $(this).val();
    const $subDimensiSelect = $(this).closest('.subject-item').find('.subdimensi-select');
    populateSubDimensiOptions($subDimensiSelect, dimensi);
});
```

**4. Event Handler: KAIH Dimensi Change**
```javascript
$(document).on('change', '.kaih-dimensi-select', function() {
    const dimensi = $(this).val();
    const $subDimensiSelect = $(this).closest('.kaih-item').find('.kaih-subdimensi-select');
    populateSubDimensiOptions($subDimensiSelect, dimensi);
});
```

**5. Form Validation Update**
```javascript
// Validate bentuk kegiatan konkret
const bentukKegiatanKonkret = $('#bentuk_kegiatan_konkret').val().trim();
if (bentukKegiatanKonkret.length < 10) {
    alert('Bentuk kegiatan konkret minimal 10 karakter');
    return false;
}
```

**6. Variable Name Changes**
- `bentuk_kegiatan` → `jenis_kokurikuler` di semua JavaScript

### 2. Controller (`app/Controllers/Admin/Kokurikuler.php`)

#### A. Method: create()
**Added**: Load Sub Dimensi Config
```php
// Load Sub Dimensi data
$subDimensiConfig = new \Config\SubDimensiProfilLulusan();
$subDimensiData = $subDimensiConfig->getSubDimensi();

return view('admin/kokurikuler/create', [
    'title' => 'Buat Dokumen Rencana Kokurikuler',
    'activeYear' => $activeYear,
    'schoolLevel' => $schoolLevel,
    'subjects' => $subjects,
    'subDimensiData' => $subDimensiData, // NEW!
]);
```

#### B. Method: store()
**Updated Validation Rules**:
```php
$rules = [
    'semester' => 'required|in_list[1,2]',
    'fase' => 'required',
    'level_kelas' => 'required',
    'jumlah_pertemuan' => 'required|integer|greater_than[0]',
    'dimensi_profil' => 'required',
    'tema' => 'required|min_length[3]',
    'jenis_kokurikuler' => 'required|in_list[lintas_disiplin,7kaih,lainnya]', // RENAMED
    'bentuk_kegiatan_konkret' => 'required|min_length[10]', // NEW!
];
```

**Updated Data Preparation**:
```php
$data = [
    'year_id' => $activeYear['id'],
    'semester' => $semester,
    'fase' => $this->request->getPost('fase'),
    'level_kelas' => $levelKelas,
    'class_id' => $classId,
    'jumlah_pertemuan' => $this->request->getPost('jumlah_pertemuan'),
    'dimensi_profil' => json_encode($this->request->getPost('dimensi_profil')),
    'tema' => $this->request->getPost('tema'),
    'jenis_kokurikuler' => $this->request->getPost('jenis_kokurikuler'), // RENAMED
    'bentuk_kegiatan_konkret' => $this->request->getPost('bentuk_kegiatan_konkret'), // NEW!
    'kegiatan_detail' => $this->prepareKegiatanDetail(),
    'kemitraan' => json_encode($this->request->getPost('kemitraan') ?? []),
    'teknologi_digital' => json_encode($this->request->getPost('teknologi_digital') ?? []),
    'status' => 'draft',
    'is_template' => $isTemplate,
    'created_by' => $userId,
];
```

#### C. Method: prepareKegiatanDetail()
**Updated untuk include sub_dimensi**:

**Lintas Disiplin**:
```php
$dimensi = $postData['lintas_dimensi_' . $index] ?? '';
$subDimensi = $postData['lintas_subdimensi_' . $index] ?? ''; // NEW!

$items[] = [
    'subject_id' => $subjectId,
    'tp_id' => $tpId,
    'dimensi_profil' => $dimensi,
    'sub_dimensi' => $subDimensi, // NEW!
];
```

**7 KAIH**:
```php
$dimensi = $postData['kaih_dimensi_' . $index] ?? '';
$subDimensi = $postData['kaih_subdimensi_' . $index] ?? ''; // NEW!

$items[] = [
    'kaih' => $kaihItem,
    'dimensi_profil' => $dimensi,
    'sub_dimensi' => $subDimensi, // NEW!
];
```

## Testing Checklist

### Manual Testing Required:
- [ ] Buka form create kokurikuler
- [ ] Pilih dimensi profil (1-3 dimensi)
- [ ] Pilih jenis kokurikuler: Lintas Disiplin
  - [ ] Tambah 2+ mata pelajaran
  - [ ] Pilih tujuan pembelajaran
  - [ ] Pilih dimensi profil
  - [ ] Pastikan dropdown sub dimensi muncul dan terisi sesuai dimensi
  - [ ] Pilih sub dimensi
- [ ] Pilih jenis kokurikuler: 7 KAIH
  - [ ] Tambah 1+ kebiasaan
  - [ ] Pilih dimensi profil
  - [ ] Pastikan dropdown sub dimensi muncul dan terisi sesuai dimensi
  - [ ] Pilih sub dimensi
- [ ] Isi bentuk kegiatan konkret (min 10 karakter)
- [ ] Submit form
- [ ] Cek database: field `jenis_kokurikuler`, `bentuk_kegiatan_konkret` terisi
- [ ] Cek database: `kegiatan_detail` JSON include `sub_dimensi`

### Expected JSON Structure in kegiatan_detail:

**Lintas Disiplin**:
```json
{
  "subjects": [1, 2],
  "items": [
    {
      "subject_id": 1,
      "tp_id": 5,
      "dimensi_profil": "Penalaran Kritis",
      "sub_dimensi": "Penyampaian Argumentasi"
    }
  ]
}
```

**7 KAIH**:
```json
{
  "items": [
    {
      "kaih": "Berolahraga",
      "dimensi_profil": "Kesehatan",
      "sub_dimensi": "Kebugaran, kesehatan fisik dan kesehatan mental"
    }
  ]
}
```

## Next Steps: Phase 3

Phase 3 akan fokus pada:
1. Update rubrik generation untuk include sub_dimensi
2. Update form penilaian untuk tampilkan sub dimensi
3. Update view penilaian detail

## Notes

- Backward compatibility: Data lama tanpa `bentuk_kegiatan_konkret` dan `sub_dimensi` akan null
- Sub dimensi dropdown disabled sampai dimensi profil dipilih
- Validation memastikan bentuk kegiatan konkret minimal 10 karakter
- Sub dimensi akan digunakan untuk generate deskripsi rapor yang lebih detail
