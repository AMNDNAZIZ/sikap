# 🔍 PANDUAN DEBUG: Sub Dimensi Tidak Muncul

## Langkah-Langkah Debugging

### STEP 1: Cek Browser Console
1. Buka halaman: `http://localhost:8080/siakad/admin/kokurikuler/penilaian/detail/12`
2. Tekan **F12** → Tab **Console**
3. Lihat output yang muncul

#### Output yang Diharapkan:
```javascript
Response from getRubrik: {success: true, rubrik: Array(3), regenerated: false}
Rubrik data loaded: (3) [{…}, {…}, {…}]
Total rubrik items: 3
Has sub_dimensi: true
```

#### Jika Muncul Alert:
```
⚠️ PERINGATAN: Rubrik tidak memiliki data sub_dimensi!
```
Artinya: Rubrik ada tapi field `sub_dimensi` kosong.

#### Expand Array di Console:
Klik tanda ▶ di `Rubrik data loaded: (3) [{…}, {…}, {…}]`

Lihat struktur data:
```javascript
0: {
  id: 1,
  document_id: 12,
  dimensi_profil: "Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia",
  sub_dimensi: "Akhlak beragama",  // ← HARUS ADA INI
  aspek_dinilai: "Akhlak beragama",
  urutan: 1
}
```

**Jika `sub_dimensi: null` atau tidak ada** → Lanjut ke STEP 2

---

### STEP 2: Cek Server Log
Buka file log server (biasanya di `writable/logs/log-YYYY-MM-DD.php`)

Atau via terminal:
```bash
tail -f writable/logs/log-*.php
```

#### Cari Log Ini:
```
INFO - === GET RUBRIK START === Document ID: 12
INFO - Rubrik count: 3
INFO - Needs regeneration: YES, Missing count: 3
INFO - Starting rubrik regeneration for document: 12
INFO - Deleted old rubrik, affected rows: 3
INFO - Generate result: SUCCESS
INFO - Reloaded rubrik count: 3
INFO - First rubrik item: {"id":123,"document_id":12,"dimensi_profil":"...","sub_dimensi":"Akhlak beragama",...}
INFO - === GET RUBRIK END === Returning 3 items
```

#### Jika Generate Result: FAILED
Cari error di log:
```
ERROR - GenerateRubrik - ...
```

**Jika tidak ada log sama sekali** → Route mungkin tidak terpanggil, cek STEP 3

---

### STEP 3: Cek Network Tab
1. Buka **F12** → Tab **Network**
2. Refresh halaman
3. Cari request ke `get-rubrik`

#### Klik Request → Tab Response:
```json
{
  "success": true,
  "rubrik": [
    {
      "id": "1",
      "document_id": "12",
      "dimensi_profil": "Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia",
      "sub_dimensi": "Akhlak beragama",
      "aspek_dinilai": "Akhlak beragama",
      "urutan": "1"
    }
  ],
  "regenerated": false
}
```

**Jika `sub_dimensi: null`** → Lanjut ke STEP 4

**Jika request 404 atau 500** → Cek route di `app/Config/Routes.php`

---

### STEP 4: Cek Database Langsung

#### A. Cek Struktur Tabel
```sql
DESCRIBE kokurikuler_rubrik;
```

**Pastikan ada field `sub_dimensi`:**
```
+------------------+--------------+------+-----+---------+
| Field            | Type         | Null | Key | Default |
+------------------+--------------+------+-----+---------+
| sub_dimensi      | varchar(255) | YES  |     | NULL    |  ← HARUS ADA
+------------------+--------------+------+-----+---------+
```

**Jika tidak ada** → Migration belum jalan, jalankan:
```bash
php spark migrate
```

#### B. Cek Data Rubrik
```sql
SELECT id, document_id, dimensi_profil, sub_dimensi, aspek_dinilai 
FROM kokurikuler_rubrik 
WHERE document_id = 12;
```

**Expected Output:**
```
+----+-------------+------------------+-------------------+-------------------+
| id | document_id | dimensi_profil   | sub_dimensi       | aspek_dinilai     |
+----+-------------+------------------+-------------------+-------------------+
|  1 |          12 | Beriman...       | Akhlak beragama   | Akhlak beragama   |
|  2 |          12 | Berkebinekaan... | Mengenal budaya   | Mengenal budaya   |
+----+-------------+------------------+-------------------+-------------------+
```

**Jika `sub_dimensi` NULL** → Lanjut ke STEP 5

#### C. Cek Kegiatan Detail
```sql
SELECT id, tema, jenis_kokurikuler, kegiatan_detail 
FROM kokurikuler_documents 
WHERE id = 12;
```

Lihat isi `kegiatan_detail`, pastikan ada `sub_dimensi`:
```json
{
  "items": [
    {
      "subject_id": "1",
      "tp_id": "5",
      "dimensi_profil": "Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia",
      "sub_dimensi": "Akhlak beragama"  ← HARUS ADA
    }
  ]
}
```

**Jika tidak ada `sub_dimensi` di kegiatan_detail** → Dokumen dibuat sebelum update, perlu dibuat ulang

---

### STEP 5: Manual Fix

#### Opsi A: Hapus Rubrik via Database
```sql
-- Backup dulu
CREATE TABLE kokurikuler_rubrik_backup_manual AS 
SELECT * FROM kokurikuler_rubrik WHERE document_id = 12;

-- Hapus rubrik
DELETE FROM kokurikuler_rubrik WHERE document_id = 12;
```

Kemudian buka: `http://localhost:8080/siakad/admin/kokurikuler/view/12`

Rubrik akan auto-generate ulang.

#### Opsi B: Buat Dokumen Baru
Jika dokumen lama tidak punya `sub_dimensi` di `kegiatan_detail`:
1. Buat dokumen kokurikuler baru
2. Pastikan pilih Sub Dimensi saat membuat
3. Generate AI
4. Cek penilaian detail

---

## Checklist Troubleshooting

- [ ] **Browser Console**: Apakah rubrik ter-load? Apakah ada `sub_dimensi`?
- [ ] **Server Log**: Apakah ada log regeneration? Apakah berhasil?
- [ ] **Network Tab**: Apakah response JSON punya `sub_dimensi`?
- [ ] **Database Structure**: Apakah field `sub_dimensi` ada di tabel?
- [ ] **Database Data**: Apakah rubrik punya nilai `sub_dimensi`?
- [ ] **Kegiatan Detail**: Apakah dokumen punya `sub_dimensi` di JSON?

---

## Solusi Berdasarkan Masalah

### Masalah 1: Field `sub_dimensi` Tidak Ada di Tabel
**Solusi**: Jalankan migration
```bash
php spark migrate
```

### Masalah 2: Rubrik Ada Tapi `sub_dimensi` NULL
**Solusi**: Hapus rubrik, buka halaman view untuk regenerate
```sql
DELETE FROM kokurikuler_rubrik WHERE document_id = 12;
```

### Masalah 3: Kegiatan Detail Tidak Punya `sub_dimensi`
**Solusi**: Dokumen dibuat sebelum update, buat dokumen baru

### Masalah 4: Route Tidak Ketemu (404)
**Solusi**: Cek `app/Config/Routes.php`, pastikan ada:
```php
$routes->get('penilaian/get-rubrik/(:num)', 'Kokurikuler::getRubrik/$1');
```

### Masalah 5: Auto-Regeneration Tidak Jalan
**Solusi**: Cek server log untuk error, atau hapus manual via database

---

## Test Case Sederhana

### Test 1: Cek Response AJAX
Buka browser console, jalankan:
```javascript
$.ajax({
    url: 'http://localhost:8080/siakad/admin/kokurikuler/penilaian/get-rubrik/12',
    type: 'GET',
    success: function(r) { 
        console.log('Success:', r); 
        console.log('First item sub_dimensi:', r.rubrik[0]?.sub_dimensi);
    },
    error: function(e) { 
        console.log('Error:', e); 
    }
});
```

### Test 2: Cek Dropdown Populate
Setelah pilih dimensi, jalankan di console:
```javascript
console.log('Selected Dimensi:', $('#filterDimensi').val());
console.log('Sub Dimensi Options:', $('#filterSubDimensi option').length);
$('#filterSubDimensi option').each(function() {
    console.log('  -', $(this).text());
});
```

---

## Kontak Support

Jika semua langkah sudah dicoba tapi masih gagal, kirimkan:
1. Screenshot browser console (tab Console)
2. Screenshot network tab (request get-rubrik)
3. Output query SQL dari STEP 4
4. Isi file log server (bagian GET RUBRIK)

Dengan informasi ini, masalah bisa diidentifikasi dengan tepat.
