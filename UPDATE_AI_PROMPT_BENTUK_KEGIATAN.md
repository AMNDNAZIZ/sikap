# Update AI Prompt - Integrasi Bentuk Kegiatan Konkret

## Perubahan yang Dilakukan

### 1. Tujuan Pembelajaran
**Sebelum:**
```
"Tujuan pembelajaran yang menggabungkan kompetensi dari dimensi profil dengan konten tema"
```

**Sesudah:**
```
"Tujuan pembelajaran yang menggabungkan kompetensi dari dimensi profil dengan konten tema. 
WAJIB diakhiri dengan kalimat: melalui kegiatan [bentuk kegiatan konkret]"
```

**Contoh Output:**
```
"Peserta didik mampu mengembangkan kemampuan penalaran kritis dan kreativitas dalam 
mengidentifikasi masalah lingkungan serta merancang solusi inovatif melalui kegiatan 
membuat poster dan video edukasi digital."
```

### 2. Rincian Kegiatan Per Pertemuan
**Sebelum:**
```json
{
  "pertemuan": 1,
  "kegiatan": "Judul kegiatan pertemuan 1",
  "deskripsi": "Deskripsi detail kegiatan"
}
```

**Sesudah:**
```json
{
  "pertemuan": 1,
  "kegiatan": "Judul kegiatan pertemuan 1 yang terkait dengan [bentuk kegiatan]",
  "deskripsi": "Deskripsi detail kegiatan yang realistis dan spesifik sesuai dengan bentuk kegiatan [bentuk kegiatan]"
}
```

**Contoh Output:**
```json
{
  "pertemuan": 1,
  "kegiatan": "Identifikasi Masalah Lingkungan untuk Poster Digital",
  "deskripsi": "Siswa melakukan observasi lingkungan sekolah untuk mengidentifikasi masalah lingkungan yang akan dijadikan tema poster. Siswa mencatat temuan dalam bentuk foto dan catatan. Diskusi kelompok untuk memilih 1 masalah utama yang akan diangkat dalam poster dan video edukasi digital."
}
```

## Struktur Prompt Baru

### Informasi yang Ditambahkan:
```
- Bentuk Kegiatan Konkret: [bentuk kegiatan yang diinput user]
```

### Instruksi Tambahan untuk AI:
```
PENTING:
1. Tujuan pembelajaran HARUS diakhiri dengan: 'melalui kegiatan {bentuk kegiatan}'
2. Setiap rincian kegiatan per pertemuan HARUS realistis dan spesifik sesuai dengan bentuk kegiatan
3. Deskripsi kegiatan harus menunjukkan langkah-langkah konkret yang dilakukan siswa
4. Kembalikan HANYA JSON yang valid, tanpa penjelasan atau teks tambahan
```

## Contoh Lengkap

### Input:
- **Tema**: Menjaga Lingkungan Sekolah
- **Bentuk Kegiatan Konkret**: Membuat poster dan video edukasi digital
- **Jumlah Pertemuan**: 4
- **Dimensi Profil**: Penalaran Kritis, Kreativitas

### Output Tujuan Pembelajaran:
```
"Peserta didik mampu mengembangkan kemampuan penalaran kritis dalam mengidentifikasi 
masalah lingkungan sekolah, serta kreativitas dalam merancang solusi inovatif yang 
dikomunikasikan secara efektif melalui kegiatan membuat poster dan video edukasi digital."
```

### Output Kegiatan Per Pertemuan:

**Pertemuan 1:**
```json
{
  "pertemuan": 1,
  "kegiatan": "Identifikasi Masalah dan Riset Konten Poster",
  "deskripsi": "Siswa melakukan observasi lingkungan sekolah untuk mengidentifikasi masalah lingkungan. Mengumpulkan data melalui foto, wawancara, dan pengamatan. Diskusi kelompok untuk memilih tema utama poster dan video. Riset informasi pendukung dari berbagai sumber untuk konten edukasi."
}
```

**Pertemuan 2:**
```json
{
  "pertemuan": 2,
  "kegiatan": "Desain dan Pembuatan Poster Digital",
  "deskripsi": "Siswa merancang layout poster menggunakan aplikasi desain grafis (Canva, Adobe Spark, atau sejenisnya). Membuat draft poster dengan elemen visual menarik: judul, gambar, infografis, dan call-to-action. Peer review untuk mendapat masukan perbaikan desain. Finalisasi desain poster digital."
}
```

**Pertemuan 3:**
```json
{
  "pertemuan": 3,
  "kegiatan": "Produksi Video Edukasi Digital",
  "deskripsi": "Siswa membuat storyboard untuk video edukasi (30-60 detik). Syuting video menggunakan smartphone dengan teknik pengambilan gambar yang baik. Merekam narasi atau voice over yang jelas dan informatif. Editing video menggunakan aplikasi sederhana (CapCut, InShot, atau sejenisnya) dengan menambahkan teks, musik, dan transisi."
}
```

**Pertemuan 4:**
```json
{
  "pertemuan": 4,
  "kegiatan": "Presentasi dan Publikasi Karya Digital",
  "deskripsi": "Setiap kelompok mempresentasikan poster dan video edukasi yang telah dibuat. Diskusi kelas tentang efektivitas pesan dan desain. Publikasi karya di media sosial sekolah atau platform digital lainnya. Refleksi pembelajaran tentang proses pembuatan poster dan video serta dampaknya terhadap kesadaran lingkungan."
}
```

## Manfaat Perubahan

### 1. Konsistensi
- Tujuan pembelajaran langsung terhubung dengan bentuk kegiatan
- Tidak ada ambiguitas tentang apa yang akan dilakukan siswa

### 2. Realisme
- Rincian kegiatan menjadi lebih spesifik dan aplikatif
- Guru dapat langsung mengimplementasikan tanpa perlu interpretasi tambahan

### 3. Kesesuaian dengan Rapor
- Bentuk kegiatan yang muncul di tujuan pembelajaran akan konsisten dengan yang muncul di rapor
- Format: "...melalui kegiatan [bentuk kegiatan]"

### 4. Panduan yang Jelas untuk AI
- AI mendapat instruksi eksplisit untuk mengintegrasikan bentuk kegiatan
- Mengurangi kemungkinan output yang generik atau tidak relevan

## Testing

### Skenario Test 1: Lintas Disiplin
- **Bentuk Kegiatan**: Membuat komik digital tentang sejarah lokal
- **Expected**: Kegiatan per pertemuan mencakup riset sejarah, penulisan naskah, ilustrasi digital, dan publikasi komik

### Skenario Test 2: 7 KAIH
- **Bentuk Kegiatan**: Kampanye hidup sehat melalui media sosial
- **Expected**: Kegiatan per pertemuan mencakup perencanaan konten, pembuatan konten visual, posting terjadwal, dan evaluasi engagement

### Skenario Test 3: Kegiatan Lainnya
- **Bentuk Kegiatan**: Pentas seni budaya daerah
- **Expected**: Kegiatan per pertemuan mencakup latihan tari/musik, persiapan kostum, gladi resik, dan pertunjukan

## File yang Dimodifikasi

- `app/Controllers/Admin/Kokurikuler.php`
  - Method: `buildAIPrompt()`
  - Perubahan: Integrasi bentuk_kegiatan_konkret ke dalam prompt AI

## Backward Compatibility

- Jika `bentuk_kegiatan_konkret` kosong (data lama), prompt akan menggunakan format lama
- Tidak ada breaking changes untuk dokumen yang sudah ada
