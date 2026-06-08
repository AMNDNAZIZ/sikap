# DEBUG: Sub Dimensi Tidak Muncul

## Kemungkinan Penyebab:

### 1. **Rubrik Lama Belum Punya Field sub_dimensi**
Jika dokumen kokurikuler dibuat SEBELUM migration yang menambahkan field `sub_dimensi`, maka rubrik yang sudah di-generate tidak memiliki data sub_dimensi.

### 2. **Solusi: Regenerate Rubrik**

Ada 2 cara:

#### Cara 1: Hapus dan Generate Ulang (Manual)
1. Buka database
2. Hapus rubrik untuk dokumen tertentu:
   ```sql
   DELETE FROM kokurikuler_rubrik WHERE document_id = 12;
   ```
3. Buka halaman view dokumen: `http://localhost:8080/siakad/admin/kokurikuler/view/12`
4. Rubrik akan otomatis di-generate ulang dengan sub_dimensi

#### Cara 2: Script Regenerate Otomatis (Recommended)
Buat controller method untuk regenerate semua rubrik.

---

## Debugging Steps:

### Step 1: Cek Console Browser
1. Buka halaman penilaian detail
2. Buka Developer Tools (F12) → Console
3. Lihat output:
   - `Response from getRubrik:` - Apakah ada data?
   - `Rubrik data loaded:` - Apakah field `sub_dimensi` ada?
   - `Selected Dimensi:` - Dimensi yang dipilih
   - `Checking rubrik:` - Setiap rubrik yang dicek
   - `Found matching sub_dimensi:` - Sub dimensi yang ditemukan
   - `Sub Dimensi Set:` - Array sub dimensi yang akan ditampilkan

### Step 2: Cek Database
```sql
-- Cek apakah field sub_dimensi ada
DESCRIBE kokurikuler_rubrik;

-- Cek data rubrik untuk dokumen tertentu
SELECT id, document_id, dimensi_profil, sub_dimensi, aspek_dinilai 
FROM kokurikuler_rubrik 
WHERE document_id = 12;
```

### Step 3: Cek Response AJAX
1. Buka Developer Tools → Network tab
2. Refresh halaman
3. Cari request ke `get-rubrik/12`
4. Klik request tersebut
5. Lihat Response → Apakah field `sub_dimensi` ada di setiap item?

---

## Expected Data Structure:

```json
{
  "success": true,
  "rubrik": [
    {
      "id": 1,
      "document_id": 12,
      "dimensi_profil": "Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia",
      "sub_dimensi": "Akhlak beragama",
      "aspek_dinilai": "Akhlak beragama",
      "urutan": 1
    },
    {
      "id": 2,
      "document_id": 12,
      "dimensi_profil": "Berkebinekaan Global",
      "sub_dimensi": "Mengenal dan menghargai budaya",
      "aspek_dinilai": "Mengenal dan menghargai budaya",
      "urutan": 2
    }
  ]
}
```

---

## Quick Fix Script

Jika sub_dimensi NULL di database, jalankan query ini untuk regenerate:

```sql
-- Backup dulu
CREATE TABLE kokurikuler_rubrik_backup AS SELECT * FROM kokurikuler_rubrik;

-- Hapus rubrik yang sub_dimensi-nya NULL
DELETE FROM kokurikuler_rubrik WHERE sub_dimensi IS NULL OR sub_dimensi = '';
```

Kemudian buka halaman view dokumen untuk auto-generate ulang.
