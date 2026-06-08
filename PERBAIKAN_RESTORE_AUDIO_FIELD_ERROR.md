# Perbaikan Error: Unknown column 'audio_a' in 'field list'

## Masalah

Saat restore bank soal, muncul error:
```
Unknown column 'audio_a' in 'field list'
```

## Penyebab

1. **Model memiliki field yang tidak ada di database**: `CbtQuestionModel` memiliki field `audio_a`, `audio_b`, `audio_c`, `audio_d`, `audio_e` di `allowedFields`, tetapi kolom-kolom ini tidak ada di tabel `cbt_questions` di database.

2. **Data backup mungkin memiliki field lama**: File backup mungkin dibuat dari sistem lama yang memiliki struktur tabel berbeda.

## Struktur Tabel Aktual

Tabel `cbt_questions` memiliki kolom:
- id
- bank_id
- question_text
- raw_text
- option_a, option_b, option_c, option_d, option_e
- correct_option
- question_type
- essay_answer
- score
- media_image
- media_audio
- media_video
- has_image
- has_audio
- created_at
- updated_at

**TIDAK ADA**: audio_a, audio_b, audio_c, audio_d, audio_e

## Solusi

### 1. Hapus Field yang Tidak Ada dari Model ✓

**File**: `app/Models/CbtQuestionModel.php`

**Sebelum:**
```php
protected $allowedFields = [
    'bank_id',
    'question_text',
    // ... fields lain ...
    'has_image',
    'has_audio',
    'audio_a',      // ← TIDAK ADA DI DATABASE
    'audio_b',      // ← TIDAK ADA DI DATABASE
    'audio_c',      // ← TIDAK ADA DI DATABASE
    'audio_d',      // ← TIDAK ADA DI DATABASE
    'audio_e'       // ← TIDAK ADA DI DATABASE
];
```

**Sesudah:**
```php
protected $allowedFields = [
    'bank_id',
    'question_text',
    // ... fields lain ...
    'has_image',
    'has_audio'
    // audio_a, audio_b, dll dihapus
];
```

### 2. Filter Field Saat Restore ✓

**File**: `app/Controllers/Admin/CbtBankSoal.php` - Method `restore()`

Tambahkan filtering untuk hanya insert field yang ada di allowedFields:

```php
// 3. Process Questions
$allowedFields = $this->questionModel->allowedFields;

foreach ($data['questions'] as $q) {
    // ... normalisasi data ...

    // Filter hanya field yang ada di allowedFields
    $filteredData = [];
    foreach ($q as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $filteredData[$key] = $value;
        }
    }

    $this->questionModel->insert($filteredData);
}
```

**Manfaat**:
- Hanya field yang valid yang di-insert
- Backward compatibility dengan backup lama
- Tidak error jika backup memiliki field tambahan

## Mengapa Field audio_a, audio_b, dll Ada di Model?

Kemungkinan:
1. **Legacy code**: Field ini mungkin digunakan di versi lama sistem
2. **Planned feature**: Fitur audio per opsi jawaban yang belum diimplementasikan
3. **Copy-paste error**: Salah copy dari sistem lain

## Files Modified

1. ✅ `app/Models/CbtQuestionModel.php` - Hapus field yang tidak ada
2. ✅ `app/Controllers/Admin/CbtBankSoal.php` - Tambah filtering field

## Testing

### Test Restore dengan Backup Baru:
1. Backup bank soal dari sistem saat ini
2. Restore bank soal tersebut
3. ✅ Harus berhasil tanpa error
4. ✅ Semua soal ter-restore dengan benar

### Test Restore dengan Backup Lama:
1. Gunakan file backup dari sistem lama (jika ada)
2. Restore bank soal tersebut
3. ✅ Harus berhasil tanpa error
4. ✅ Field yang tidak ada di database di-skip
5. ✅ Field yang valid ter-restore dengan benar

## Catatan Penting

1. **Backward Compatibility**: Dengan filtering field, sistem bisa restore backup dari versi lama maupun baru

2. **Forward Compatibility**: Jika di masa depan ada field baru di database, tinggal tambahkan ke allowedFields

3. **Data Integrity**: Hanya field yang valid yang di-insert, menghindari error SQL

4. **No Data Loss**: Field yang tidak ada di database memang tidak perlu di-restore karena tidak ada tempatnya

## Troubleshooting

### Error: "Unknown column 'xxx' in 'field list'"
**Solusi**: 
1. Cek apakah field 'xxx' ada di tabel database
2. Jika tidak ada, hapus dari allowedFields di model
3. Filtering di method restore akan otomatis skip field tersebut

### Restore berhasil tapi data tidak lengkap
**Solusi**:
1. Cek apakah field yang hilang ada di allowedFields
2. Cek apakah field tersebut ada di tabel database
3. Jika field ada di database tapi tidak di allowedFields, tambahkan ke allowedFields

### Backup dari sistem lain tidak bisa di-restore
**Solusi**:
1. Pastikan struktur data.json sesuai (ada 'bank' dan 'questions')
2. Field yang berbeda akan otomatis di-skip oleh filtering
3. Jika error persist, cek log untuk detail error
