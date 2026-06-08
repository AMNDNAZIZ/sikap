# Fitur Mode Sinkronisasi Dapodik

## Status: ✅ SELESAI

Fitur ini menambahkan 3 mode sinkronisasi untuk menangani data siswa yang sudah ada di sistem saat melakukan sinkronisasi dari Dapodik.

## Perubahan yang Dilakukan

### 1. Controller (`app/Controllers/Admin/Dapodik.php`)

**Method Baru:**
- `mapStudentData()` - Helper untuk mapping data siswa dari Dapodik ke format SIAKAD (34 field)
- `updateStudentData()` - Mode Update: Timpa semua data dengan data Dapodik
- `mergeStudentData()` - Mode Merge: Hanya isi field yang kosong

**Method yang Dimodifikasi:**
- `syncStudents()` - Menambahkan logika untuk 3 mode sinkronisasi:
  - **Skip Mode:** Lewati data yang sudah ada (behavior original)
  - **Update Mode:** Timpa semua data dengan data terbaru dari Dapodik
  - **Merge Mode:** Hanya isi field yang masih kosong (DIREKOMENDASIKAN)

**Pesan Sukses yang Ditingkatkan:**
- Menampilkan jumlah siswa baru yang ditambahkan
- Menampilkan jumlah siswa yang diperbarui
- Menampilkan jumlah siswa yang dilewati

### 2. View (`app/Views/admin/dapodik/preview_students.php`)

**Penambahan UI:**
- Dropdown selector untuk memilih mode sinkronisasi
- Label dan deskripsi untuk setiap mode
- Alert warning untuk mengingatkan user tentang penanganan data duplikat

**Opsi Mode:**
```html
<select name="sync_mode" class="form-select" required>
    <option value="skip">Skip - Lewati (tidak diubah)</option>
    <option value="update">Update - Timpa dengan data Dapodik</option>
    <option value="merge">Merge - Isi yang kosong saja</option>
</select>
```

### 3. Dokumentasi (`DOKUMENTASI_SINKRONISASI_DAPODIK.md`)

**Bagian Baru:**
- Penjelasan lengkap 3 mode sinkronisasi
- Kapan menggunakan setiap mode
- Contoh penggunaan mode Merge
- Rekomendasi penggunaan

**Update Bagian:**
- Cara menggunakan sinkronisasi siswa (menambahkan langkah pilih mode)
- Data yang disinkronkan (detail 34 field)
- Troubleshooting untuk data duplikat

## Cara Menggunakan

1. **Akses Menu Dapodik**
   - Login sebagai Admin
   - Buka menu Admin > Integrasi Dapodik

2. **Ambil Data Siswa**
   - Klik "Tarik Data Siswa"
   - Tunggu preview data muncul (1-2 menit)

3. **Pilih Mode Sinkronisasi**
   - **Skip:** Untuk sinkronisasi pertama kali atau jika tidak ingin mengubah data yang sudah ada
   - **Update:** Jika data Dapodik lebih akurat dan ingin menimpa semua data
   - **Merge:** Untuk sinkronisasi rutin, melengkapi data tanpa menghilangkan yang sudah ada

4. **Pilih Siswa dan Kelas**
   - Centang siswa yang ingin disinkronkan
   - Pilih kelas tujuan (opsional, sistem auto-detect dari nama rombel)

5. **Sinkronkan**
   - Klik "Mulai Sinkronisasi"
   - Sistem akan memproses sesuai mode yang dipilih

## Contoh Skenario

### Skenario 1: Sinkronisasi Pertama Kali
**Mode:** Skip atau Merge (tidak ada perbedaan karena belum ada data)
**Hasil:** Semua siswa baru ditambahkan dengan 34 field lengkap

### Skenario 2: Update Data Berkala
**Mode:** Merge (DIREKOMENDASIKAN)
**Contoh:**
- Data di SIAKAD: Alamat kosong, Nama Ayah = "Budi"
- Data di Dapodik: Alamat = "Jl. Merdeka", Nama Ayah = "Budi Santoso"
- Hasil: Alamat diisi "Jl. Merdeka", Nama Ayah tetap "Budi"

### Skenario 3: Reset Data dari Dapodik
**Mode:** Update
**Hasil:** Semua data ditimpa dengan data terbaru dari Dapodik
**Peringatan:** Data yang sudah diinput manual akan hilang!

## Data yang Disinkronkan (34 Field)

### Data Pribadi (11 field)
- NISN, NIS, Nama, NIK, Jenis Kelamin
- Tempat Lahir, Tanggal Lahir, Anak Ke-berapa
- Agama, Kewarganegaraan, Nama Rombel

### Data Alamat (7 field)
- Alamat Jalan, Jenis Tinggal, Moda Transportasi
- Jarak Rumah ke Sekolah, Latitude, Longitude
- Kebutuhan Khusus

### Data Ayah (6 field)
- Nama, NIK, Tahun Lahir, Pendidikan, Pekerjaan, Penghasilan

### Data Ibu (6 field)
- Nama, NIK, Tahun Lahir, Pendidikan, Pekerjaan, Penghasilan

### Data Wali (4 field)
- Nama, Pendidikan, Pekerjaan, Penghasilan

## Logging

Sistem mencatat setiap operasi sinkronisasi:
- Mode Skip: Tidak ada log (data dilewati)
- Mode Update: `Dapodik Sync: Updated student NISN [nisn] (mode: update)`
- Mode Merge: `Dapodik Sync: Merged student NISN [nisn] - Updated X fields (mode: merge)`

Log dapat dilihat di `writable/logs/log-[tanggal].php`

## Testing

Untuk menguji fitur ini:

1. **Test Mode Skip:**
   - Sinkronkan siswa pertama kali
   - Sinkronkan lagi dengan mode Skip
   - Verifikasi: Tidak ada perubahan, pesan menunjukkan "melewati X siswa"

2. **Test Mode Update:**
   - Edit data siswa di SIAKAD (misal: ubah alamat)
   - Sinkronkan dengan mode Update
   - Verifikasi: Data kembali ke data Dapodik

3. **Test Mode Merge:**
   - Kosongkan beberapa field di SIAKAD (misal: hapus alamat)
   - Isi field lain secara manual (misal: nama ayah)
   - Sinkronkan dengan mode Merge
   - Verifikasi: Field kosong terisi dari Dapodik, field yang sudah ada tidak berubah

## Rekomendasi

- **Sinkronisasi Pertama:** Gunakan mode Skip atau Merge
- **Sinkronisasi Rutin:** Gunakan mode Merge untuk melengkapi data
- **Reset Total:** Gunakan mode Update (hati-hati!)

## File yang Dimodifikasi

1. `app/Controllers/Admin/Dapodik.php` - Logika sinkronisasi
2. `app/Views/admin/dapodik/preview_students.php` - UI mode selector
3. `app/Views/admin/dapodik/index.php` - Info untuk user
4. `DOKUMENTASI_SINKRONISASI_DAPODIK.md` - Dokumentasi lengkap

## Catatan Pengembangan Selanjutnya

Fitur yang bisa ditambahkan:
- [ ] Mode sinkronisasi untuk data guru (saat ini hanya skip)
- [ ] Preview perubahan sebelum sinkronisasi (diff view)
- [ ] Rollback sinkronisasi jika terjadi kesalahan
- [ ] Sinkronisasi terjadwal otomatis
- [ ] Export/import mapping field custom
