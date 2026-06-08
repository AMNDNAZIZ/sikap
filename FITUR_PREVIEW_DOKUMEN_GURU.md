# Fitur Preview Dokumen Guru untuk Admin

## Deskripsi

Menambahkan fitur **Preview Dokumen** di tab Arsip Dokumen halaman edit guru, memungkinkan admin untuk melihat isi dokumen tanpa perlu mendownload terlebih dahulu.

## Fitur Preview yang Didukung

### 1. PDF (✅ Full Preview)
- Preview langsung menggunakan iframe
- Tampilan full-screen dalam modal
- Fallback link jika browser tidak support

**Teknologi:** Native browser PDF viewer

### 2. Gambar (✅ Full Preview)
- Format: JPG, JPEG, PNG
- Preview dengan zoom otomatis
- Responsive dan centered

**Teknologi:** Native HTML `<img>` tag

### 3. Microsoft Word (⚠️ Online Viewer)
- Format: DOC, DOCX
- Preview menggunakan Office Online Viewer
- Memerlukan koneksi internet
- Fallback download jika tidak bisa preview

**Teknologi:** Microsoft Office Online Viewer API

### 4. Microsoft Excel (⚠️ Online Viewer)
- Format: XLS, XLSX
- Preview menggunakan Office Online Viewer
- Memerlukan koneksi internet
- Fallback download jika tidak bisa preview

**Teknologi:** Microsoft Office Online Viewer API

### 5. File Lainnya (❌ No Preview)
- Menampilkan pesan "Preview tidak tersedia"
- Menyarankan untuk download file

## Perubahan File

### `app/Views/admin/teachers/edit.php`

#### 1. Tambah Tombol Preview di Kartu Dokumen
```php
<button type="button" class="btn btn-outline-info btn-sm btn-preview-doc"
        data-id="<?= $doc['id'] ?>" 
        data-title="<?= esc($doc['title']) ?>"
        data-type="<?= $doc['file_type'] ?>"
        data-filename="<?= $doc['filename'] ?>">
  <i class="bi bi-eye"></i> Preview
</button>
```

#### 2. Tambah Modal Preview (Modal XL)
- Header: Judul dokumen
- Body: Area preview dengan min-height 500px
- Footer: Tombol Download dan Tutup

#### 3. JavaScript Preview Handler
**Logika:**
- Deteksi tipe file dari MIME type
- Generate preview sesuai tipe:
  - **PDF**: `<iframe>` langsung ke file
  - **Image**: `<img>` dengan styling responsive
  - **Word/Excel**: `<iframe>` ke Office Online Viewer
  - **Lainnya**: Alert "Preview tidak tersedia"

## Cara Kerja

### Flow Preview:
1. User klik tombol "Preview" pada dokumen
2. Modal preview muncul dengan loading spinner
3. JavaScript membaca data-attributes (id, title, type, filename)
4. Generate preview berdasarkan tipe file
5. Tampilkan preview dalam modal
6. User bisa download langsung dari modal

### Office Online Viewer:
```javascript
const viewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(fileUrl)}`;
```

**Catatan:** 
- File harus bisa diakses publik via URL
- Memerlukan koneksi internet
- Tidak semua file Word/Excel bisa di-preview (tergantung kompleksitas)

## UI/UX

### Tombol Preview
- Icon: 👁️ (bi-eye)
- Warna: Info (biru muda)
- Posisi: Sebelum tombol Download

### Modal Preview
- Ukuran: XL (extra large)
- Min-height: 500px
- Loading state: Spinner dengan teks
- Responsive: Menyesuaikan ukuran layar

### Preview Content
- **PDF**: Full-width iframe 600px height
- **Image**: Centered, max-height 600px, object-fit contain
- **Word/Excel**: Full-width iframe 600px height dengan alert info
- **Unsupported**: Warning alert dengan icon file

## Keuntungan

✅ **Cepat** - Tidak perlu download untuk melihat isi dokumen  
✅ **Praktis** - Preview langsung dalam modal  
✅ **Hemat Bandwidth** - Tidak perlu download jika hanya ingin melihat  
✅ **User Friendly** - Interface yang intuitif  
✅ **Fallback** - Selalu ada opsi download jika preview gagal  

## Limitasi

⚠️ **Office Documents:**
- Memerlukan koneksi internet
- File harus accessible via public URL
- Tidak semua format/versi didukung
- Loading bisa lambat tergantung ukuran file

⚠️ **Browser Compatibility:**
- PDF preview tergantung browser support
- Beberapa browser lama mungkin tidak support

⚠️ **File Size:**
- File besar (>10MB) bisa lambat di-preview
- Disarankan tetap download untuk file besar

## Testing

Setelah implementasi:
1. ✅ Upload dokumen PDF → Preview harus tampil dalam iframe
2. ✅ Upload gambar JPG/PNG → Preview harus tampil centered
3. ✅ Upload dokumen Word → Preview via Office Online Viewer
4. ✅ Upload dokumen Excel → Preview via Office Online Viewer
5. ✅ Upload file ZIP → Tampil pesan "Preview tidak tersedia"
6. ✅ Tombol Download di modal berfungsi
7. ✅ Modal responsive di mobile

## Catatan Keamanan

- File disimpan di folder `uploads/teacher_documents/`
- Akses file melalui URL publik (untuk Office Viewer)
- Permission check tetap berlaku (teachers.read)
- Filename di-random untuk keamanan

## Future Enhancement

Potensial improvement di masa depan:
- [ ] Preview untuk file TXT/CSV
- [ ] Zoom in/out untuk gambar
- [ ] Pagination untuk PDF multi-halaman
- [ ] Thumbnail preview di kartu dokumen
- [ ] Full-screen mode untuk preview
- [ ] Print langsung dari preview
