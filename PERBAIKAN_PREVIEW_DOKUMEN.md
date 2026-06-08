# Perbaikan: Preview Dokumen Tidak Menampilkan Isi

## Masalah

Preview dokumen sudah ada tapi tidak menampilkan isi karena:
1. File di folder `uploads/teacher_documents/` tidak bisa diakses langsung via URL
2. Tidak ada route/method untuk serve file dengan header yang tepat
3. JavaScript menggunakan path langsung ke file yang tidak accessible

## Solusi

Membuat method `viewDocument()` yang serve file dengan header yang tepat untuk preview inline.

## Perubahan

### 1. `app/Controllers/Admin/Teachers.php`

#### Tambah Method `viewDocument($documentId)`

```php
public function viewDocument($documentId)
{
    require_permission('teachers.read');
    
    $documentModel = new \App\Models\TeacherDocumentModel();
    $document = $documentModel->find($documentId);

    if (!$document) {
        return $this->response->setStatusCode(404)->setBody('Dokumen tidak ditemukan.');
    }

    $filePath = UPLOAD_PATH . 'teacher_documents/' . $document['filename'];
    if (!file_exists($filePath)) {
        return $this->response->setStatusCode(404)->setBody('File tidak ditemukan di server.');
    }

    // Set appropriate headers for inline viewing
    $this->response->setHeader('Content-Type', $document['file_type']);
    $this->response->setHeader('Content-Disposition', 'inline; filename="' . $document['original_name'] . '"');
    $this->response->setHeader('Content-Length', filesize($filePath));
    
    // Read and output file
    return $this->response->setBody(file_get_contents($filePath));
}
```

**Fungsi:**
- Serve file dengan header `Content-Disposition: inline` (bukan `attachment`)
- Set `Content-Type` sesuai MIME type file
- Set `Content-Length` untuk progress indicator
- Return file content langsung

### 2. `app/Config/Routes.php`

#### Tambah Route Baru

```php
$routes->get('teachers/viewDocument/(:num)', 'Teachers::viewDocument/$1', $auth127);
```

**Permission:** `auth127` (Admin, Kepsek, Staf)

### 3. `app/Views/admin/teachers/edit.php`

#### Update JavaScript Preview Handler

**Sebelum:**
```javascript
const fileUrl = `<?= base_url('uploads/teacher_documents') ?>/${filename}`;
```

**Sesudah:**
```javascript
const fileUrl = `<?= base_url('admin/teachers/viewDocument') ?>/${id}`;
```

**Perubahan:**
- Gunakan route `viewDocument` dengan document ID
- Tidak perlu filename di URL (lebih aman)
- File di-serve melalui controller dengan permission check

## Cara Kerja

### Flow Preview:

1. **User klik Preview** → Modal muncul
2. **JavaScript generate URL** → `admin/teachers/viewDocument/{id}`
3. **Controller viewDocument():**
   - Check permission (`teachers.read`)
   - Load document dari database
   - Verify file exists
   - Set headers untuk inline viewing
   - Return file content
4. **Browser render:**
   - PDF → Native PDF viewer
   - Image → Native image viewer
   - Word/Excel → Office Online Viewer (via iframe)

### Headers yang Di-set:

```
Content-Type: application/pdf (atau sesuai file type)
Content-Disposition: inline; filename="nama_file.pdf"
Content-Length: 123456
```

**`Content-Disposition: inline`** adalah kunci agar browser menampilkan file di iframe, bukan download.

## Keuntungan Solusi Ini

✅ **Secure** - File tidak bisa diakses langsung, harus melalui permission check  
✅ **Clean URL** - Tidak expose filename di URL  
✅ **Proper Headers** - Browser tahu cara handle file (inline vs download)  
✅ **Consistent** - Semua file type di-serve dengan cara yang sama  
✅ **Traceable** - Bisa log siapa yang view dokumen  

## Testing

Setelah perbaikan:

### PDF
1. Upload file PDF
2. Klik Preview
3. ✅ PDF harus tampil dalam iframe
4. ✅ Bisa scroll dan zoom

### Image (JPG/PNG)
1. Upload file gambar
2. Klik Preview
3. ✅ Gambar harus tampil centered
4. ✅ Responsive dan clear

### Word/Excel
1. Upload file Word/Excel
2. Klik Preview
3. ✅ Loading Office Online Viewer
4. ⚠️ Memerlukan koneksi internet
5. ⚠️ File harus accessible via public URL

### File Lain
1. Upload file ZIP/RAR
2. Klik Preview
3. ✅ Tampil pesan "Preview tidak tersedia"
4. ✅ Saran untuk download

## Troubleshooting

### Preview Masih Tidak Tampil?

**1. Check Browser Console**
```
F12 → Console → Lihat error
```

**2. Check Network Tab**
```
F12 → Network → Klik Preview → Lihat response
```

**3. Test Direct URL**
```
Buka: http://localhost/siakad/admin/teachers/viewDocument/1
Harus tampil file atau error message
```

**4. Check File Permissions**
```
Folder: uploads/teacher_documents/
Permission: 755 (readable)
```

**5. Check PHP Error Log**
```
XAMPP: xampp/apache/logs/error.log
```

### Office Documents Tidak Preview?

**Penyebab:**
- Tidak ada koneksi internet
- File terlalu besar (>10MB)
- Format file tidak didukung Office Online Viewer
- URL tidak accessible dari internet (localhost)

**Solusi:**
- Pastikan koneksi internet aktif
- Compress file jika terlalu besar
- Gunakan format standar (DOCX, XLSX)
- Deploy ke server public untuk test Office Viewer

## Catatan Keamanan

- ✅ Permission check di controller (`teachers.read`)
- ✅ Validate document ID dan file existence
- ✅ Tidak expose physical file path
- ✅ Random filename di storage
- ✅ MIME type validation saat upload

## Performance

- File di-load ke memory (`file_get_contents`)
- Untuk file besar (>10MB), pertimbangkan streaming:

```php
// Alternative untuk file besar
readfile($filePath);
```

Atau gunakan `Response::setBody()` dengan chunk reading.
