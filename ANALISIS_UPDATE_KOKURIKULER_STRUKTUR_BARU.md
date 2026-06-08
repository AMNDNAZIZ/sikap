# Analisis Update Struktur Kokurikuler

## Temuan Baru dari Panduan Resmi Kemendikbud

Berdasarkan panduan penilaian rapor resmi dari Kementerian, ditemukan struktur yang berbeda dari implementasi saat ini:

### Struktur Lama (SALAH)
- **Bentuk Kegiatan**: Lintas Disiplin Ilmu, 7 KAIH, Kegiatan Lainnya

### Struktur Baru (BENAR)
1. **Jenis Kokurikuler**: Lintas Disiplin Ilmu, 7 KAIH, Kegiatan Lainnya
2. **Bentuk Kegiatan**: Kegiatan konkret yang dilakukan (contoh: "Membuat poster dan video edukasi digital")
3. **Dimensi Profil Lulusan**: 8 dimensi utama
4. **Sub Dimensi**: Sub dimensi dari setiap dimensi profil

## Contoh Output Rapor

```
Pada semester ini, ananda menunjukkan capaian yang cukup baik dalam penguatan profil lulusan, 
yang ditunjukkan melalui kegiatan kokurikuler Membuat poster dan video edukasi digital.

Pada dimensi keimanan dan ketakwaan terhadap Tuhan Yang Maha Esa, ananda berkembang dalam 
subdimensi hubungan dengan sesama manusia.

Pada dimensi penalaran kritis, ananda berkembang dalam subdimensi penyampaian argumentasi.

Pada dimensi kemandirian, ananda berkembang dalam subdimensi bertanggung jawab.
```

### Penjelasan:
- **"Membuat poster dan video edukasi digital"** = Bentuk Kegiatan (konkret)
- **"Keimanan dan ketakwaan", "Penalaran kritis", "Kemandirian"** = Dimensi Profil Lulusan
- **"Hubungan dengan sesama manusia", "Penyampaian argumentasi", "Bertanggung jawab"** = Sub Dimensi

## Perubahan yang Diperlukan

### 1. Database Schema

#### Tabel: `kokurikuler_documents`
- **RENAME**: `bentuk_kegiatan` → `jenis_kokurikuler`
- **ADD**: `bentuk_kegiatan_konkret` VARCHAR(500) - Kegiatan nyata yang dilakukan

#### Tabel: `kokurikuler_rubrik`
- **ADD**: `sub_dimensi` VARCHAR(255) - Sub dimensi dari dimensi profil

### 2. File Config Baru

**File**: `app/Config/SubDimensiProfilLulusan.php`
- Mapping 8 Dimensi Profil ke Sub Dimensi masing-masing
- Helper methods untuk validasi dan retrieval

### 3. Form Create (`app/Views/admin/kokurikuler/create.php`)

**Perubahan Struktur:**

#### Step 3: Tema dan Jenis Kegiatan (UPDATED)
```
- Tema Kegiatan
- Jenis Kokurikuler (dropdown):
  * Kolaboratif Lintas Disiplin Ilmu
  * Melalui 7 KAIH
  * Kegiatan Lainnya
- Bentuk Kegiatan Konkret (text input) - NEW!
  Placeholder: "Contoh: Membuat poster dan video edukasi digital"
```

#### Dynamic Section (tetap sama)
- Lintas Disiplin: Pilih mapel + TP + Dimensi + **Sub Dimensi** (NEW!)
- 7 KAIH: Pilih kebiasaan + Dimensi + **Sub Dimensi** (NEW!)
- Lainnya: Text area + Dimensi + **Sub Dimensi** (NEW!)

### 4. Controller Updates

**File**: `app/Controllers/Admin/Kokurikuler.php`

#### Method `create()`
- Load SubDimensiProfilLulusan config
- Pass ke view

#### Method `store()`
- Update field name: `bentuk_kegiatan` → `jenis_kokurikuler`
- Add field: `bentuk_kegiatan_konkret`
- Validate bentuk_kegiatan_konkret (required, min 10 chars)

#### Method `prepareKegiatanDetail()`
- Update untuk include sub_dimensi di setiap item

### 5. Rubrik Generation

**File**: Method yang generate rubrik (perlu dicek)

Update untuk:
- Menyimpan `sub_dimensi` di tabel `kokurikuler_rubrik`
- Setiap dimensi profil harus punya sub_dimensi yang dipilih

### 6. Penilaian Form

**File**: `app/Views/admin/kokurikuler/penilaian_detail.php`

Update untuk:
- Menampilkan sub_dimensi di rubrik penilaian
- Format: "Dimensi: [Nama Dimensi] - Sub Dimensi: [Nama Sub Dimensi]"

### 7. Pelaporan/Rapor

**File**: `app/Views/admin/kokurikuler/pelaporan.php` atau method terkait

Update format deskripsi:
```
Pada semester ini, ananda menunjukkan capaian yang [capaian] dalam penguatan profil lulusan, 
yang ditunjukkan melalui kegiatan kokurikuler [bentuk_kegiatan_konkret].

Pada dimensi [dimensi_1], ananda berkembang dalam subdimensi [sub_dimensi_1].
Pada dimensi [dimensi_2], ananda berkembang dalam subdimensi [sub_dimensi_2].
...
```

### 8. PDF Export

**Files**: 
- `app/Views/admin/kokurikuler/pdf_lintas_disiplin.php`
- `app/Views/admin/kokurikuler/pdf_7kaih.php`
- `app/Views/admin/kokurikuler/pdf_lainnya.php`

Update untuk:
- Menampilkan "Jenis Kokurikuler" bukan "Bentuk Kegiatan"
- Menampilkan "Bentuk Kegiatan" sebagai kegiatan konkret
- Menampilkan Sub Dimensi di rubrik

## Sub Dimensi Profil Lulusan (Lengkap - Data Resmi Kemendikbud)

### 1. Keimanan dan Ketakwaan Terhadap Tuhan YME
- Hubungan dengan Tuhan Yang Maha Esa
- Hubungan dengan sesama manusia
- Hubungan dengan lingkungan alam

### 2. Kewargaan
- Kewargaan Lokal
- Kewargaan Nasional
- Kewargaan Global

### 3. Penalaran Kritis
- Penyampaian Argumentasi
- Pengambilan Keputusan
- Penyelesaian Masalah

### 4. Kreativitas
- Gagasan baru
- Fleksibilitas berpikir
- Karya

### 5. Kemandirian
- Bertanggung jawab
- Kepemimpinan
- Pengembangan Diri

### 6. Kolaborasi
- Peduli
- Berbagi
- Bekerja sama

### 7. Kesehatan
- Hidup bersih dan sehat
- Kebugaran, kesehatan fisik dan kesehatan mental
- Kesehatan Lingkungan

### 8. Komunikasi
- Menyimak
- Berbicara
- Membaca
- Menulis

## Langkah Implementasi

### Phase 1: Database & Config (DONE)
- [x] Migration untuk update struktur database
- [x] Config file SubDimensiProfilLulusan

### Phase 2: Form & Controller (TODO)
- [ ] Update form create.php
- [ ] Update controller Kokurikuler.php
- [ ] Update validation rules

### Phase 3: Rubrik & Penilaian (TODO)
- [ ] Update rubrik generation
- [ ] Update form penilaian
- [ ] Update view penilaian

### Phase 4: Pelaporan & PDF (TODO)
- [ ] Update format pelaporan/rapor
- [ ] Update PDF templates
- [ ] Update view detail

### Phase 5: Testing (TODO)
- [ ] Test create dokumen baru
- [ ] Test rubrik generation
- [ ] Test penilaian
- [ ] Test pelaporan
- [ ] Test PDF export

## Notes

1. **Backward Compatibility**: Data lama dengan `bentuk_kegiatan` akan otomatis menjadi `jenis_kokurikuler` setelah migration
2. **Data Migration**: Dokumen lama tidak punya `bentuk_kegiatan_konkret` dan `sub_dimensi`, perlu diisi manual atau generate ulang
3. **Validation**: Bentuk kegiatan konkret harus diisi, minimal 10 karakter
4. **AI Prompt**: Update AI prompt untuk generate konten yang include sub dimensi

## Migration Command

```bash
php spark migrate
```

Atau jika sudah ada data:
```bash
php spark migrate:refresh
```

**WARNING**: `migrate:refresh` akan menghapus semua data! Backup dulu sebelum refresh.
