# Update Sub Dimensi di View & PDF Export

## Perubahan yang Dilakukan

### 1. Halaman View Detail (`app/Views/admin/kokurikuler/view.php`)

#### A. Lintas Disiplin Ilmu
**Sebelum:**
```html
<em class="text-muted">Dimensi: Penalaran Kritis</em>
```

**Sesudah:**
```html
<em class="text-muted">
    <i class="bi bi-star-fill"></i> Dimensi: Penalaran Kritis
    <br><i class="bi bi-arrow-return-right"></i> Sub Dimensi: Penyampaian Argumentasi
</em>
```

**Tampilan:**
```
★ Dimensi: Penalaran Kritis
↳ Sub Dimensi: Penyampaian Argumentasi
```

#### B. 7 KAIH
**Sebelum:**
```html
<em class="text-muted">Dimensi: Kesehatan</em>
```

**Sesudah:**
```html
<em class="text-muted">
    <i class="bi bi-star-fill"></i> Dimensi: Kesehatan
    <br><i class="bi bi-arrow-return-right"></i> Sub Dimensi: Kebugaran, kesehatan fisik dan kesehatan mental
</em>
```

**Tampilan:**
```
★ Dimensi: Kesehatan
↳ Sub Dimensi: Kebugaran, kesehatan fisik dan kesehatan mental
```

### 2. Controller (`app/Controllers/Admin/Kokurikuler.php`)

#### Method: view()
**Update:**
```php
$subjectTPs[$subjectId][] = [
    'id' => $tp['id'],
    'kode_tp' => $tp['kode_tp'],
    'deskripsi' => $tp['deskripsi'],
    'dimensi_profil' => $dimensi,
    'sub_dimensi' => $item['sub_dimensi'] ?? ''  // NEW!
];
```

#### Method: exportPDF()
**Update:**
```php
$subjectTPs[$subjectId][] = [
    'id' => $tp['id'],
    'kode_tp' => $tp['kode_tp'],
    'deskripsi' => $tp['deskripsi'],
    'dimensi_profil' => $dimensi,
    'sub_dimensi' => $item['sub_dimensi'] ?? ''  // NEW!
];
```

### 3. PDF Template - Lintas Disiplin (`pdf_lintas_disiplin.php`)

**Sebelum:**
```html
<em style="color: #666; font-size: 0.9em;">(Dimensi: Penalaran Kritis)</em>
```

**Sesudah:**
```html
<em style="color: #666; font-size: 0.9em;">
    Dimensi: Penalaran Kritis | Sub Dimensi: Penyampaian Argumentasi
</em>
```

**Output PDF:**
```
TP.1.2. Peserta didik mampu menyampaikan argumentasi dengan logis
Dimensi: Penalaran Kritis | Sub Dimensi: Penyampaian Argumentasi
```

### 4. PDF Template - 7 KAIH (`pdf_7kaih.php`)

**Sebelum:**
```html
<strong>Berolahraga</strong>
<em style="color: #666;"> - Dimensi: Kesehatan</em>
```

**Sesudah:**
```html
<strong>Berolahraga</strong>
<br><em style="color: #666; font-size: 0.9em;">
    Dimensi: Kesehatan | Sub Dimensi: Kebugaran, kesehatan fisik dan kesehatan mental
</em>
```

**Output PDF:**
```
• Berolahraga
  Dimensi: Kesehatan | Sub Dimensi: Kebugaran, kesehatan fisik dan kesehatan mental
```

## Contoh Tampilan Lengkap

### Halaman View - Lintas Disiplin

```
Detail Kegiatan

Mata Pelajaran yang Terlibat:

📖 Bahasa Indonesia
   Tujuan Pembelajaran:
   • TP.1.2. Peserta didik mampu menyampaikan argumentasi dengan logis dan sistematis
     ★ Dimensi: Penalaran Kritis
     ↳ Sub Dimensi: Penyampaian Argumentasi

📖 Seni Budaya
   Tujuan Pembelajaran:
   • TP.2.1. Peserta didik mampu menghasilkan karya visual yang kreatif
     ★ Dimensi: Kreativitas
     ↳ Sub Dimensi: Karya
```

### Halaman View - 7 KAIH

```
Detail Kegiatan

Kegiatan 7 KAIH:
• Berolahraga
  ★ Dimensi: Kesehatan
  ↳ Sub Dimensi: Kebugaran, kesehatan fisik dan kesehatan mental

• Bermasyarakat
  ★ Dimensi: Kolaborasi
  ↳ Sub Dimensi: Peduli
```

### PDF Export - Lintas Disiplin

```
C. MATA PELAJARAN YANG TERLIBAT

1. Bahasa Indonesia
   • TP.1.2. Peserta didik mampu menyampaikan argumentasi dengan logis
     Dimensi: Penalaran Kritis | Sub Dimensi: Penyampaian Argumentasi

2. Seni Budaya
   • TP.2.1. Peserta didik mampu menghasilkan karya visual yang kreatif
     Dimensi: Kreativitas | Sub Dimensi: Karya
```

### PDF Export - 7 KAIH

```
C. KEGIATAN 7 KAIH YANG DIPILIH

• Berolahraga
  Dimensi: Kesehatan | Sub Dimensi: Kebugaran, kesehatan fisik dan kesehatan mental

• Bermasyarakat
  Dimensi: Kolaborasi | Sub Dimensi: Peduli
```

## Icon yang Digunakan

### Bootstrap Icons (di View HTML):
- `bi-star-fill` (★) - Untuk Dimensi
- `bi-arrow-return-right` (↳) - Untuk Sub Dimensi
- `bi-book-fill` (📖) - Untuk Mata Pelajaran

### Format PDF:
- Separator: `|` (pipe) untuk memisahkan Dimensi dan Sub Dimensi
- Line break: `<br>` untuk sub dimensi di baris baru

## Backward Compatibility

### Data Lama Tanpa Sub Dimensi:
```php
<?php if (!empty($item['sub_dimensi'])): ?>
    <br><i class="bi bi-arrow-return-right"></i> Sub Dimensi: <?= esc($item['sub_dimensi']) ?>
<?php endif; ?>
```

**Hasil:**
- Jika ada sub_dimensi: Tampilkan dimensi + sub dimensi
- Jika tidak ada: Hanya tampilkan dimensi (seperti sebelumnya)

## File yang Dimodifikasi

1. **View**:
   - `app/Views/admin/kokurikuler/view.php`

2. **Controller**:
   - `app/Controllers/Admin/Kokurikuler.php`
     - Method: `view()`
     - Method: `exportPDF()`

3. **PDF Templates**:
   - `app/Views/admin/kokurikuler/pdf_lintas_disiplin.php`
   - `app/Views/admin/kokurikuler/pdf_7kaih.php`

## Testing

### Test Case 1: Dokumen Baru dengan Sub Dimensi
1. Buat dokumen kokurikuler baru
2. Pilih dimensi dan sub dimensi
3. Buka halaman view
4. ✅ Sub dimensi muncul di bawah dimensi
5. Export PDF
6. ✅ Sub dimensi muncul di PDF

### Test Case 2: Dokumen Lama Tanpa Sub Dimensi
1. Buka dokumen lama (sebelum update)
2. Buka halaman view
3. ✅ Hanya dimensi yang muncul (tidak error)
4. Export PDF
5. ✅ Hanya dimensi yang muncul di PDF

### Test Case 3: Multiple Mata Pelajaran
1. Dokumen lintas disiplin dengan 3 mapel
2. Setiap mapel punya dimensi dan sub dimensi berbeda
3. ✅ Semua sub dimensi muncul dengan benar
4. ✅ Tidak ada duplikasi atau missing data

## Manfaat

1. **Informasi Lebih Lengkap**: Guru dan siswa dapat melihat detail sub dimensi yang dikembangkan
2. **Konsistensi**: Format sama antara view dan PDF
3. **Kesesuaian Rapor**: Sub dimensi yang muncul di dokumen akan sama dengan yang muncul di rapor
4. **Visual Hierarchy**: Icon dan indentasi membuat struktur dimensi-subdimensi jelas
5. **Backward Compatible**: Dokumen lama tetap bisa dibuka tanpa error
