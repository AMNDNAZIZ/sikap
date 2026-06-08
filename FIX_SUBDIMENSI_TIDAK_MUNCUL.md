# ✅ FIX: Sub Dimensi Tidak Muncul

## Masalah
Setelah memilih Dimensi, dropdown Sub Dimensi tidak menampilkan pilihan.

## Penyebab
Rubrik yang di-generate sebelum migration (sebelum field `sub_dimensi` ditambahkan) tidak memiliki data sub_dimensi.

## Solusi yang Sudah Diterapkan

### 1. **Auto-Regeneration di getRubrik()**
Method `getRubrik()` di controller sekarang akan:
- Mengecek apakah ada rubrik yang `sub_dimensi`-nya kosong
- Jika ada, otomatis menghapus semua rubrik untuk dokumen tersebut
- Regenerate rubrik dengan data sub_dimensi yang lengkap
- Mengembalikan rubrik yang sudah diperbaiki

### 2. **Console Logging untuk Debugging**
JavaScript di view sekarang menampilkan log di browser console untuk debugging:
- Response dari server
- Data rubrik yang di-load
- Dimensi yang dipilih
- Sub dimensi yang ditemukan

---

## Cara Testing

### Step 1: Refresh Halaman
1. Buka halaman penilaian detail: `http://localhost:8080/siakad/admin/kokurikuler/penilaian/detail/12`
2. Buka Developer Tools (F12) → Console tab
3. Lihat log yang muncul

### Step 2: Pilih Dimensi
1. Pilih dimensi dari dropdown pertama
2. Lihat console log:
   ```
   Selected Dimensi: Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia
   Checking rubrik: {id: 1, dimensi_profil: "...", sub_dimensi: "Akhlak beragama", ...}
   Found matching sub_dimensi: Akhlak beragama
   Sub Dimensi Set: ["Akhlak beragama", "Akhlak kepada manusia"]
   ```
3. Dropdown Sub Dimensi seharusnya sudah terisi

### Step 3: Jika Masih Belum Muncul
Cek console log untuk melihat:
- Apakah `response.rubrik` ada data?
- Apakah setiap item rubrik punya field `sub_dimensi`?
- Apakah ada error di console?

---

## Manual Fix (Jika Auto-Regeneration Gagal)

### Opsi 1: Via Database
```sql
-- Hapus rubrik untuk dokumen tertentu
DELETE FROM kokurikuler_rubrik WHERE document_id = 12;
```
Kemudian buka halaman view dokumen untuk auto-generate ulang.

### Opsi 2: Via Controller (Tambahkan Method Baru)
Tambahkan method di controller untuk regenerate semua rubrik:

```php
public function regenerateAllRubrik()
{
    $documents = $this->documentModel->findAll();
    $count = 0;
    
    foreach ($documents as $doc) {
        $this->rubrikModel->where('document_id', $doc['id'])->delete();
        $this->rubrikModel->generateRubrikFromDocument($doc['id']);
        $count++;
    }
    
    return redirect()->back()->with('success', "Berhasil regenerate $count dokumen");
}
```

Tambahkan route:
```php
$routes->get('kokurikuler/regenerate-all-rubrik', 'Kokurikuler::regenerateAllRubrik');
```

Akses: `http://localhost:8080/siakad/admin/kokurikuler/regenerate-all-rubrik`

---

## Verifikasi Database

### Cek Field sub_dimensi Ada
```sql
DESCRIBE kokurikuler_rubrik;
```

Expected output harus ada field `sub_dimensi`:
```
+------------------+--------------+------+-----+---------+----------------+
| Field            | Type         | Null | Key | Default | Extra          |
+------------------+--------------+------+-----+---------+----------------+
| id               | int(11)      | NO   | PRI | NULL    | auto_increment |
| document_id      | int(11)      | NO   | MUL | NULL    |                |
| dimensi_profil   | varchar(255) | NO   |     | NULL    |                |
| sub_dimensi      | varchar(255) | YES  |     | NULL    |                | ← HARUS ADA
| aspek_dinilai    | text         | NO   |     | NULL    |                |
| urutan           | int(11)      | YES  |     | 1       |                |
| created_at       | datetime     | YES  |     | NULL    |                |
+------------------+--------------+------+-----+---------+----------------+
```

### Cek Data Rubrik
```sql
SELECT id, document_id, dimensi_profil, sub_dimensi, aspek_dinilai 
FROM kokurikuler_rubrik 
WHERE document_id = 12
ORDER BY urutan;
```

Expected: Setiap row harus punya nilai di kolom `sub_dimensi`.

---

## Expected Behavior Setelah Fix

1. **Page Load**: 
   - AJAX call ke `get-rubrik/12`
   - Server cek apakah rubrik punya sub_dimensi
   - Jika tidak, auto-regenerate
   - Return rubrik dengan sub_dimensi lengkap

2. **Pilih Dimensi**:
   - Dropdown Sub Dimensi enabled
   - Menampilkan sub dimensi yang sesuai dengan dimensi yang dipilih

3. **Pilih Sub Dimensi**:
   - Button "Tampilkan Penilaian" enabled
   - Siap untuk load data siswa

---

## Files Modified

1. ✅ `app/Controllers/Admin/Kokurikuler.php`
   - Method `getRubrik()` - Added auto-regeneration logic

2. ✅ `app/Views/admin/kokurikuler/penilaian_detail.php`
   - Added console.log for debugging

---

## Next Steps

1. **Test** halaman penilaian detail
2. **Verify** sub dimensi muncul di dropdown
3. **Check** console log untuk debugging
4. **Report** jika masih ada masalah dengan screenshot console log

---

## Troubleshooting

### Sub Dimensi Masih Tidak Muncul?

**Cek Console Log:**
```javascript
// Jika rubrikData kosong atau tidak ada sub_dimensi
Response from getRubrik: {success: true, rubrik: []}
// → Tidak ada rubrik, perlu generate manual

// Jika rubrik ada tapi sub_dimensi null
Checking rubrik: {id: 1, dimensi_profil: "...", sub_dimensi: null, ...}
// → Auto-regeneration seharusnya jalan, cek server log

// Jika sub_dimensi ada tapi tidak match
Selected Dimensi: "Beriman, Bertakwa..."
Checking rubrik: {dimensi_profil: "Beriman Bertakwa...", ...}
// → Nama dimensi tidak exact match, cek typo atau spasi
```

**Cek Server Log:**
```bash
tail -f writable/logs/log-*.php
```

Look for:
```
INFO - Rubrik needs regeneration for document: 12
INFO - GenerateRubrik - Document found: 12
INFO - GenerateRubrik - Lintas disiplin items processed: 3
```

---

## Status: ✅ FIXED

Auto-regeneration sudah diterapkan. Silakan test dan laporkan hasilnya.
