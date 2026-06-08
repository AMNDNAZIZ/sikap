# Perbaikan: Tab Arsip Dokumen Guru untuk Admin

## Masalah

Admin tidak bisa mengakses tab **Arsip Dokumen** di halaman edit guru. Tab ini tidak tersedia di halaman `admin/teachers/edit/{id}`, padahal fitur arsip dokumen sudah ada untuk guru yang login ke profil mereka sendiri.

## Solusi

Menambahkan tab "Arsip Dokumen" ke halaman edit guru admin dengan fitur lengkap:
- Upload dokumen (PDF, DOC, DOCX, XLS, XLSX, JPG, PNG)
- Download dokumen
- Delete dokumen
- Tampilan kartu dokumen dengan icon sesuai tipe file

## File yang Diubah

### 1. `app/Controllers/Admin/Teachers.php`

#### a. Update method `edit()` - Menambahkan data dokumen
```php
public function edit($id)
{
    // ... existing code ...
    
    $documentModel = new \App\Models\TeacherDocumentModel();

    return view('admin/teachers/edit', [
        'title' => 'Edit Guru',
        'teacher' => $teacher,
        'educations' => $this->educationModel->getByTeacher($id),
        'trainings' => $this->trainingModel->getByTeacher($id),
        'careers' => $this->careerModel->getByTeacher($id),
        'documents' => $documentModel->where('teacher_id', $id)->orderBy('created_at', 'DESC')->findAll(),
        'years' => (new \App\Models\AcademicYearModel())->findAll()
    ]);
}
```

#### b. Menambahkan 3 method baru:

**`uploadDocument($teacherId)`**
- Validasi file type (PDF, DOC, DOCX, XLS, XLSX, JPG, PNG)
- Validasi ukuran file (max 5MB)
- Upload ke folder `uploads/teacher_documents/`
- Simpan metadata ke database

**`deleteDocument($documentId)`**
- Hapus file dari disk
- Hapus record dari database

**`downloadDocument($documentId)`**
- Download file dengan nama original

### 2. `app/Config/Routes.php`

Menambahkan 3 route baru di section teachers:
```php
$routes->post('teachers/uploadDocument/(:num)', 'Teachers::uploadDocument/$1', $auth127);
$routes->get('teachers/deleteDocument/(:num)', 'Teachers::deleteDocument/$1', $auth127);
$routes->get('teachers/downloadDocument/(:num)', 'Teachers::downloadDocument/$1', $auth127);
```

### 3. `app/Views/admin/teachers/edit.php`

#### a. Menambahkan tab baru di header
```php
<li class="nav-item">
  <button class="nav-link" id="documents-tab" data-bs-toggle="tab" 
          data-bs-target="#documents" type="button">Arsip Dokumen</button>
</li>
```

#### b. Menambahkan konten tab Arsip Dokumen
- Tampilan grid kartu dokumen
- Icon dinamis berdasarkan tipe file (PDF, Word, Excel, Image)
- Informasi: judul, nama file, ukuran, tanggal upload
- Tombol download dan delete
- Alert jika belum ada dokumen

#### c. Menambahkan modal upload dokumen
- Form upload dengan field: judul dan file
- Validasi client-side untuk tipe file
- Info maksimal ukuran file

#### d. Menambahkan JavaScript handler
- Handler untuk delete dokumen dengan konfirmasi
- Integrasi dengan localStorage untuk active tab

## Fitur yang Ditambahkan

### Upload Dokumen
- ✅ Validasi tipe file: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG
- ✅ Validasi ukuran: maksimal 5MB
- ✅ Random filename untuk keamanan
- ✅ Simpan nama original untuk download

### Tampilan Dokumen
- ✅ Grid layout responsive (3 kolom di desktop, 2 di tablet, 1 di mobile)
- ✅ Icon dinamis sesuai tipe file:
  - 📄 PDF → icon merah
  - 📘 Word → icon biru
  - 📗 Excel → icon hijau
  - 🖼️ Image → icon cyan
  - 📋 Lainnya → icon default
- ✅ Badge ukuran file dalam KB
- ✅ Tanggal upload

### Download & Delete
- ✅ Download dengan nama file original
- ✅ Delete dengan konfirmasi
- ✅ Hapus file dari disk dan database

## Permission

Semua fitur menggunakan permission yang sama dengan edit guru:
- Upload: `teachers.update`
- Delete: `teachers.update`
- Download: `teachers.read`

## Folder Upload

Dokumen disimpan di: `uploads/teacher_documents/`

Folder akan dibuat otomatis jika belum ada dengan permission 0755.

## Testing

Setelah perubahan ini:
1. ✅ Admin bisa melihat tab "Arsip Dokumen" di halaman edit guru
2. ✅ Admin bisa upload dokumen untuk guru
3. ✅ Admin bisa download dokumen guru
4. ✅ Admin bisa delete dokumen guru
5. ✅ Tab tetap aktif setelah refresh (localStorage)
6. ✅ Validasi file type dan size berfungsi
7. ✅ Icon dokumen tampil sesuai tipe file

## Catatan

- Fitur ini konsisten dengan fitur arsip dokumen yang sudah ada di profil guru (`app/Views/profile/documents.php`)
- Menggunakan model `TeacherDocumentModel` yang sudah ada
- Tabel `teacher_documents` sudah ada dari migrasi sebelumnya
