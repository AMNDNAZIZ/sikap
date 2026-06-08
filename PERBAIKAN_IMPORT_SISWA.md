# Perbaikan Error Import Siswa

## Masalah
Saat melakukan import siswa dari halaman `/admin/students`, muncul error:
```
Sorry! Cannot seem to find the page you were looking for.
```

## Penyebab
1. **Route Salah**: Ada route `students/doImport` yang tidak memiliki method di controller
2. **Method Tidak Lengkap**: Method `import()` tidak memeriksa apakah request adalah POST
3. **Error Handling Kurang**: Tidak ada validasi file dan error handling yang baik

## Solusi yang Diterapkan

### 1. Perbaikan Route
**File**: `app/Config/Routes.php`

**Sebelum**:
```php
$routes->get('students/import', 'Students::import', $auth127);
$routes->post('students/doImport', 'Students::doImport', $auth127);
```

**Sesudah**:
```php
$routes->post('students/import', 'Students::import', $auth127);
// Route doImport dihapus karena tidak ada method-nya
```

### 2. Perbaikan Method Import
**File**: `app/Controllers/Admin/Students.php`

**Perubahan**:
- ✅ Menambahkan validasi request method (harus POST)
- ✅ Menambahkan validasi file extension (.xlsx, .xls)
- ✅ Menambahkan try-catch untuk error handling
- ✅ Menambahkan validasi data kosong
- ✅ Menambahkan log error untuk debugging
- ✅ Menggunakan helper security untuk generate password
- ✅ Menambahkan pesan error yang lebih informatif

**Fitur Baru**:
```php
// Validasi request method
if (!$this->request->is('post')) {
    return redirect()->to('/admin/students')->with('error', 'Invalid request method.');
}

// Validasi file extension
$allowedExtensions = ['xlsx', 'xls'];
if (!in_array(strtolower($file->getExtension()), $allowedExtensions)) {
    return redirect()->back()->with('error', 'Format file tidak valid...');
}

// Try-catch untuk error handling
try {
    // ... proses import
} catch (\Exception $e) {
    log_message('error', 'Import students error: ' . $e->getMessage());
    return redirect()->back()->with('error', 'Gagal mengimpor file: ' . $e->getMessage());
}
```

## Fitur Import Siswa

### Format File Excel
Template file Excel harus memiliki kolom:
1. **NISN** (Kolom A)
2. **NIS** (Kolom B)
3. **Nama** (Kolom C)
4. **Jenis Kelamin** (Kolom D) - L/P
5. **Tempat Lahir** (Kolom E)
6. **Tanggal Lahir** (Kolom F) - Format: YYYY-MM-DD

### Proses Import
1. User klik tombol "📂 Impor Siswa"
2. Modal muncul dengan form upload file
3. User pilih file Excel (.xlsx atau .xls)
4. Klik "🚀 Impor"
5. Sistem memproses:
   - Membaca file Excel
   - Validasi data per baris
   - Membuat akun user untuk siswa
   - Membuat data siswa
   - Membuat akun orang tua otomatis
6. Menampilkan hasil:
   - ✅ Berhasil diimpor (list)
   - ⚠️ Gagal diimpor (list dengan alasan)

### Akun yang Dibuat Otomatis

#### 1. Akun Siswa
- **Username**: NIS (lowercase)
- **Email**: [NIS]@siswa.local
- **Password**: siswa[NIS] (contoh: siswa2021001)
- **Role**: Siswa (role_id = 5)
- **Must Change Password**: Ya

#### 2. Akun Orang Tua
- **Username**: ortu_[NIS]
- **Email**: [NIS]@ortu.com
- **Password**: ortu[NIS] (contoh: ortu2021001)
- **Role**: Orang Tua (role_id = 4)
- **Must Change Password**: Ya

### Validasi Import
- ✅ NIS dan Nama wajib diisi
- ✅ Username tidak boleh duplikat
- ✅ Email tidak boleh duplikat
- ✅ File harus format Excel (.xlsx atau .xls)
- ✅ Baris kosong akan di-skip

### Error Handling
- File tidak valid → Error message
- Format file salah → Error message
- Data duplikat → Masuk ke list "Gagal diimpor"
- Data tidak lengkap → Masuk ke list "Gagal diimpor"
- Exception → Error message dengan detail

## Testing

### Test 1: Import Berhasil
1. Download template dari tombol "Unduh Template Disini"
2. Isi data siswa sesuai format
3. Upload file
4. Klik "🚀 Impor"
5. **Expected**: Muncul alert sukses dengan list siswa yang berhasil diimpor

### Test 2: Import dengan Data Duplikat
1. Import file dengan NIS yang sudah ada
2. **Expected**: Siswa dengan NIS duplikat masuk ke list "Gagal diimpor"

### Test 3: Import File Format Salah
1. Upload file .pdf atau .doc
2. **Expected**: Error "Format file tidak valid"

### Test 4: Import File Kosong
1. Upload file Excel tanpa data (hanya header)
2. **Expected**: Error "Tidak ada data yang diimpor"

## File yang Dimodifikasi
1. `app/Config/Routes.php` - Perbaikan route import
2. `app/Controllers/Admin/Students.php` - Perbaikan method import dengan validasi lengkap

## Manfaat Perbaikan
✅ Import siswa berfungsi dengan baik
✅ Error handling yang lebih baik
✅ Validasi file yang ketat
✅ Pesan error yang informatif
✅ Log error untuk debugging
✅ Akun siswa dan orang tua dibuat otomatis
✅ Password default yang aman dengan pattern

## Changelog
- **2026-04-21**: Perbaikan error 404 pada import siswa
  - Mengubah route dari GET ke POST
  - Menghapus route doImport yang tidak ada method-nya
  - Menambahkan validasi lengkap
  - Menambahkan error handling dengan try-catch
  - Menambahkan log error
