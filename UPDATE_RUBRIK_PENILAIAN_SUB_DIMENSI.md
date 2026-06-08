# Update Rubrik Penilaian - Per Sub Dimensi dengan Skala Berkembang, Cakap, Mahir

## Perubahan Sistem Penilaian

### Konsep Lama vs Baru

#### ❌ Sistem Lama:
- Rubrik per Tujuan Pembelajaran (TP) atau per Kebiasaan 7 KAIH
- Skala: Sangat Baik, Baik, Cukup, Kurang
- Banyak rubrik (1 rubrik per TP)
- Penilaian detail dan memakan waktu

#### ✅ Sistem Baru:
- **Rubrik per Sub Dimensi**
- **Skala: Berkembang, Cakap, Mahir**
- **Default: Cakap**
- Lebih sederhana dan efisien
- Fokus pada pengembangan kompetensi

## Perubahan Database

### Tabel: `kokurikuler_rubrik`

**Field Baru:**
```sql
sub_dimensi VARCHAR(255) NULL
```

**Struktur Lengkap:**
- `id` - Primary key
- `document_id` - FK ke kokurikuler_documents
- `dimensi_profil` - Nama dimensi (contoh: "Penalaran Kritis")
- `sub_dimensi` - **NEW!** Nama sub dimensi (contoh: "Penyampaian Argumentasi")
- `aspek_dinilai` - Deskripsi aspek (sama dengan sub_dimensi)
- `urutan` - Urutan tampilan
- `created_at` - Timestamp

## Logika Generate Rubrik

### Lintas Disiplin Ilmu

**Input dari kegiatan_detail:**
```json
{
  "items": [
    {
      "subject_id": 1,
      "tp_id": 5,
      "dimensi_profil": "Penalaran Kritis",
      "sub_dimensi": "Penyampaian Argumentasi"
    },
    {
      "subject_id": 1,
      "tp_id": 6,
      "dimensi_profil": "Penalaran Kritis",
      "sub_dimensi": "Penyampaian Argumentasi"
    },
    {
      "subject_id": 2,
      "tp_id": 10,
      "dimensi_profil": "Kreativitas",
      "sub_dimensi": "Karya"
    }
  ]
}
```

**Output Rubrik (Grouped by Dimensi + Sub Dimensi):**
```
1. Dimensi: Penalaran Kritis | Sub Dimensi: Penyampaian Argumentasi
2. Dimensi: Kreativitas | Sub Dimensi: Karya
```

**Keuntungan:**
- Tidak ada duplikasi (2 TP dengan sub dimensi sama = 1 rubrik)
- Penilaian lebih holistik per sub dimensi

### 7 KAIH

**Input dari kegiatan_detail:**
```json
{
  "items": [
    {
      "kaih": "Berolahraga",
      "dimensi_profil": "Kesehatan",
      "sub_dimensi": "Kebugaran, kesehatan fisik dan kesehatan mental"
    },
    {
      "kaih": "Bermasyarakat",
      "dimensi_profil": "Kolaborasi",
      "sub_dimensi": "Peduli"
    }
  ]
}
```

**Output Rubrik:**
```
1. Dimensi: Kesehatan | Sub Dimensi: Kebugaran, kesehatan fisik dan kesehatan mental
2. Dimensi: Kolaborasi | Sub Dimensi: Peduli
```

## Skala Penilaian Baru

### 3 Tingkat Capaian:

#### 1. 🟡 Berkembang
**Deskripsi:** Siswa mulai menunjukkan kemampuan dasar
- Masih memerlukan bimbingan
- Pemahaman dasar sudah ada
- Perlu pengembangan lebih lanjut

#### 2. 🔵 Cakap (Default)
**Deskripsi:** Siswa menunjukkan kemampuan yang memadai
- Dapat melakukan dengan baik
- Pemahaman yang cukup
- Standar yang diharapkan tercapai

#### 3. 🟢 Mahir
**Deskripsi:** Siswa menunjukkan kemampuan yang sangat baik
- Menguasai dengan sangat baik
- Dapat mengaplikasikan secara mandiri
- Melebihi standar yang diharapkan

### Kenapa Default "Cakap"?
- Asumsi positif: Siswa yang mengikuti kegiatan sudah mencapai standar
- Guru hanya perlu adjust ke Berkembang (jika kurang) atau Mahir (jika sangat baik)
- Mempercepat proses penilaian

## Tampilan Form Penilaian

### Struktur Card per Sub Dimensi:

```
┌─────────────────────────────────────────────────────────────┐
│ ★ Penalaran Kritis                                          │
│ ↳ Penyampaian Argumentasi                                   │
│                                                              │
│ Penyampaian Argumentasi                                     │
│                                                              │
│ [🟡 Berkembang] [🔵 Cakap ✓] [🟢 Mahir]                     │
└─────────────────────────────────────────────────────────────┘
```

### Petunjuk Penilaian (Info Box):
```
ℹ️ Petunjuk Penilaian:
Berkembang: Siswa mulai menunjukkan kemampuan dasar
Cakap: Siswa menunjukkan kemampuan yang memadai (Default)
Mahir: Siswa menunjukkan kemampuan yang sangat baik
```

## Contoh Implementasi

### Skenario 1: Lintas Disiplin - Poster Digital

**Dokumen:**
- Tema: Lingkungan Sekolah
- Bentuk Kegiatan: Membuat poster dan video edukasi digital
- Mapel: Bahasa Indonesia + Seni Budaya

**Kegiatan Detail:**
```json
{
  "items": [
    {
      "subject_id": 1,
      "tp_id": 5,
      "dimensi_profil": "Penalaran Kritis",
      "sub_dimensi": "Penyampaian Argumentasi"
    },
    {
      "subject_id": 1,
      "tp_id": 6,
      "dimensi_profil": "Penalaran Kritis",
      "sub_dimensi": "Penyampaian Argumentasi"
    },
    {
      "subject_id": 2,
      "tp_id": 10,
      "dimensi_profil": "Kreativitas",
      "sub_dimensi": "Karya"
    }
  ]
}
```

**Rubrik yang Digenerate:**
1. Penalaran Kritis → Penyampaian Argumentasi
2. Kreativitas → Karya

**Form Penilaian untuk Siswa "Ahmad":**
```
┌─────────────────────────────────────────────────────────────┐
│ ★ Penalaran Kritis                                          │
│ ↳ Penyampaian Argumentasi                                   │
│                                                              │
│ [Berkembang] [Cakap ✓] [Mahir]                             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ★ Kreativitas                                               │
│ ↳ Karya                                                     │
│                                                              │
│ [Berkembang] [Cakap] [Mahir ✓]                             │
└─────────────────────────────────────────────────────────────┘

Catatan Tambahan:
Ahmad sangat kreatif dalam membuat desain poster. Argumentasi
dalam video juga disampaikan dengan jelas.
```

### Skenario 2: 7 KAIH - Hidup Sehat

**Dokumen:**
- Tema: Hidup Sehat
- Bentuk Kegiatan: Kampanye hidup sehat
- 7 KAIH: Berolahraga, Makan Sehat

**Kegiatan Detail:**
```json
{
  "items": [
    {
      "kaih": "Berolahraga",
      "dimensi_profil": "Kesehatan",
      "sub_dimensi": "Kebugaran, kesehatan fisik dan kesehatan mental"
    },
    {
      "kaih": "Makan Sehat",
      "dimensi_profil": "Kesehatan",
      "sub_dimensi": "Kebugaran, kesehatan fisik dan kesehatan mental"
    }
  ]
}
```

**Rubrik yang Digenerate:**
1. Kesehatan → Kebugaran, kesehatan fisik dan kesehatan mental

**Catatan:** Meskipun ada 2 kebiasaan (Berolahraga & Makan Sehat), karena sub dimensinya sama, hanya 1 rubrik yang digenerate.

## File yang Dimodifikasi

### 1. Model
**File:** `app/Models/KokurikulerRubrikModel.php`

**Perubahan:**
- Update `allowedFields` - tambah `sub_dimensi`
- Update `generateRubrikFromDocument()`:
  - Group by dimensi + sub_dimensi
  - Hindari duplikasi
  - Generate 1 rubrik per unique pair

### 2. View
**File:** `app/Views/admin/kokurikuler/penilaian_detail.php`

**Perubahan:**
- Update `renderPenilaianForm()`:
  - Tampilkan dimensi dan sub dimensi dengan badge
  - Ganti skala menjadi Berkembang, Cakap, Mahir
  - Set default "Cakap"
  - Tambah info box petunjuk penilaian
- Update validation:
  - Ubah dari 4 options ke 3 options

## Keuntungan Sistem Baru

### 1. Efisiensi Waktu
- **Lama:** 10 TP = 10 rubrik = 10 penilaian per siswa
- **Baru:** 10 TP dengan 3 sub dimensi unik = 3 rubrik = 3 penilaian per siswa
- **Hemat:** 70% waktu penilaian

### 2. Fokus pada Kompetensi
- Penilaian holistik per sub dimensi
- Tidak terpecah per TP
- Lebih sesuai dengan filosofi Kurikulum Merdeka

### 3. Konsistensi dengan Rapor
- Sub dimensi yang dinilai = sub dimensi yang muncul di rapor
- Format deskripsi rapor lebih mudah digenerate

### 4. User-Friendly
- Default "Cakap" mempercepat input
- 3 pilihan lebih sederhana dari 4
- Visual yang jelas dengan icon dan warna

## Testing Checklist

### Test Case 1: Generate Rubrik Baru
- [ ] Buat dokumen kokurikuler baru (Lintas Disiplin)
- [ ] Pilih 2 mapel dengan total 5 TP
- [ ] Pastikan ada sub dimensi yang sama di beberapa TP
- [ ] Generate AI
- [ ] Cek rubrik: Harus grouped by sub dimensi (tidak duplikat)

### Test Case 2: Form Penilaian
- [ ] Buka form penilaian siswa
- [ ] Pastikan tampil dimensi + sub dimensi
- [ ] Pastikan default "Cakap" tercentang
- [ ] Ubah beberapa ke "Mahir" dan "Berkembang"
- [ ] Simpan
- [ ] Reload: Pastikan pilihan tersimpan

### Test Case 3: Multiple Students
- [ ] Nilai 3 siswa dengan nilai berbeda
- [ ] Pastikan setiap siswa punya nilai independen
- [ ] Cek progress bar update

### Test Case 4: Backward Compatibility
- [ ] Buka dokumen lama (sebelum update)
- [ ] Pastikan tidak error
- [ ] Generate ulang rubrik jika perlu

## Migration Command

Jika perlu regenerate rubrik untuk dokumen yang sudah ada:

```sql
-- Hapus rubrik lama
DELETE FROM kokurikuler_rubrik WHERE document_id = [ID];

-- Trigger regenerate dari aplikasi
-- Atau panggil method generateRubrikFromDocument() dari controller
```

## Notes

- Sub dimensi field sudah ditambahkan di migration sebelumnya (`2026-04-16-000001_UpdateKokurikulerStructure.php`)
- Sistem lama masih compatible (rubrik tanpa sub_dimensi akan tampil normal)
- Untuk dokumen baru, wajib ada sub_dimensi
