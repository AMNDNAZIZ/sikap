# Mapping Field Dapodik ke SIAKAD

Dokumen ini menjelaskan mapping field data dari Dapodik ke SIAKAD untuk sinkronisasi data siswa dan guru.

## 📊 Data Siswa

### Data Pribadi

| Field Dapodik | Field SIAKAD | Keterangan |
|---------------|--------------|------------|
| `nisn` | `nisn` | Nomor Induk Siswa Nasional (Primary Key) |
| `nis` | `nis` | Nomor Induk Siswa (jika tidak ada, gunakan NISN) |
| `nama` | `name` | Nama lengkap siswa (diubah ke UPPERCASE) |
| `nik` | `nik` | NIK siswa |
| `jenis_kelamin` | `gender` | L = Laki-laki, P = Perempuan |
| `tempat_lahir` | `birth_place` | Tempat lahir |
| `tanggal_lahir` | `birth_date` | Tanggal lahir (format: YYYY-MM-DD) |
| `anak_keberapa` | `child_order` | Anak ke berapa |
| `agama` | `religion` | Agama siswa |
| `kewarganegaraan` | `nationality` | Default: WNI |
| `kebutuhan_khusus` | `special_needs` | Kebutuhan khusus (jika ada) |

### Data Alamat & Tempat Tinggal

| Field Dapodik | Field SIAKAD | Keterangan |
|---------------|--------------|------------|
| `alamat_jalan` | `address` | Alamat lengkap |
| `jenis_tinggal` | `residence_type` | Bersama Orang Tua / Wali / Asrama |
| `moda_transportasi` | `transportation` | Cara ke sekolah |
| `jarak_rumah_ke_sekolah` | `distance` | Jarak dalam km |
| `latitude` | `latitude` | Koordinat latitude |
| `longitude` | `longitude` | Koordinat longitude |

### Data Orang Tua - Ayah

| Field Dapodik | Field SIAKAD | Keterangan |
|---------------|--------------|------------|
| `nama_ayah` | `father_name` | Nama lengkap ayah |
| `nik_ayah` | `father_nik` | NIK ayah |
| `tahun_lahir_ayah` | `father_birth_year` | Tahun lahir ayah |
| `pendidikan_ayah` | `father_education` | Pendidikan terakhir ayah |
| `pekerjaan_ayah` | `father_job` | Pekerjaan ayah |
| `penghasilan_ayah` | `father_income` | Penghasilan ayah |

### Data Orang Tua - Ibu

| Field Dapodik | Field SIAKAD | Keterangan |
|---------------|--------------|------------|
| `nama_ibu_kandung` | `mother_name` | Nama lengkap ibu kandung |
| `nik_ibu` | `mother_nik` | NIK ibu |
| `tahun_lahir_ibu` | `mother_birth_year` | Tahun lahir ibu |
| `pendidikan_ibu` | `mother_education` | Pendidikan terakhir ibu |
| `pekerjaan_ibu` | `mother_job` | Pekerjaan ibu |
| `penghasilan_ibu` | `mother_income` | Penghasilan ibu |

### Data Wali

| Field Dapodik | Field SIAKAD | Keterangan |
|---------------|--------------|------------|
| `nama_wali` | `guardian_name` | Nama lengkap wali |
| `pendidikan_wali` | `guardian_education` | Pendidikan terakhir wali |
| `pekerjaan_wali` | `guardian_job` | Pekerjaan wali |
| `penghasilan_wali` | `guardian_income` | Penghasilan wali |

### Data Rombel & Kelas

| Field Dapodik | Field SIAKAD | Keterangan |
|---------------|--------------|------------|
| `nama_rombel` | Auto-detect ke `class_id` | Sistem akan mencocokkan nama rombel dengan nama kelas di SIAKAD |

---

## 👨‍🏫 Data Guru

### Data Pribadi

| Field Dapodik | Field SIAKAD | Keterangan |
|---------------|--------------|------------|
| `nuptk` | `nuptk` | NUPTK (Primary Key) |
| `nip` | `nip` | NIP |
| `nama` | `name` | Nama lengkap guru (diubah ke UPPERCASE) |
| `jenis_kelamin` | `gender` | L = Laki-laki, P = Perempuan |
| `tempat_lahir` | `birth_place` | Tempat lahir |
| `tanggal_lahir` | `birth_date` | Tanggal lahir (format: YYYY-MM-DD) |
| `email` | `email` | Email guru (jika tidak ada, generate otomatis) |

---

## 🔄 Proses Sinkronisasi

### 1. Validasi Data
- Cek apakah NISN (siswa) atau NUPTK (guru) sudah ada
- Jika sudah ada, skip (tidak duplikat)
- Jika belum ada, lanjut proses insert

### 2. Pembuatan User Account
Setiap siswa/guru akan dibuatkan akun user otomatis:

**Siswa:**
- Username: NISN (lowercase)
- Email: `[nisn]@siswa.local`
- Password: `siswa[NISN]` (contoh: `siswa29192919`)
- Role: Siswa (role_id = 5)
- Must change password: Ya

**Guru:**
- Username: NUPTK atau NIP (lowercase)
- Email: dari Dapodik atau `[username]@guru.local`
- Password: `guru[NUPTK/NIP]` (contoh: `guru1234567890`)
- Role: Guru (role_id = 3)
- Must change password: Ya

### 3. Insert Data Lengkap
Semua field yang tersedia di Dapodik akan otomatis terisi di SIAKAD:
- Data pribadi siswa/guru
- Data alamat
- Data orang tua (untuk siswa)
- Data wali (untuk siswa)

### 4. Auto-Detect Kelas (Siswa)
Sistem akan mencoba mencocokkan `nama_rombel` dari Dapodik dengan `name` kelas di SIAKAD:
- Matching case-insensitive
- Jika cocok, siswa otomatis masuk ke kelas tersebut
- Jika tidak cocok, gunakan pilihan kelas manual

### 5. Insert ke Student Records
Siswa akan ditambahkan ke tabel `student_records` dengan:
- `student_id`: ID siswa yang baru dibuat
- `class_id`: Dari auto-detect atau pilihan manual
- `academic_year_id`: Tahun ajaran aktif saat ini
- `status`: aktif

---

## 📝 Catatan Penting

### Field yang Wajib Ada di Dapodik:
- **Siswa**: `nisn`, `nama`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`
- **Guru**: `nuptk` atau `nip`, `nama`, `jenis_kelamin`

### Field Opsional:
Semua field lainnya bersifat opsional. Jika tidak ada di Dapodik, akan diisi dengan:
- String kosong (`''`) untuk field text
- Default value untuk field tertentu (contoh: `nationality` = 'WNI')

### Handling Data Kosong:
Sistem menggunakan null coalescing operator (`??`) untuk semua field, sehingga:
- Tidak akan error jika field tidak ada di response Dapodik
- Field akan terisi jika ada data
- Field akan kosong jika tidak ada data

### Format Data:
- **Nama**: Diubah ke UPPERCASE untuk konsistensi
- **Username**: Diubah ke lowercase
- **Tanggal**: Format YYYY-MM-DD
- **Gender**: L atau P

---

## 🔍 Troubleshooting

### Data tidak lengkap setelah sync
**Penyebab**: Field tidak tersedia di response Dapodik
**Solusi**: 
1. Cek response Dapodik di log
2. Pastikan data sudah diisi lengkap di aplikasi Dapodik
3. Update data di Dapodik, lalu sync ulang

### Kelas tidak terdeteksi otomatis
**Penyebab**: Nama rombel di Dapodik tidak sama dengan nama kelas di SIAKAD
**Solusi**:
1. Samakan nama kelas di SIAKAD dengan nama rombel di Dapodik
2. Atau gunakan pilihan kelas manual saat sync

### Data orang tua tidak muncul
**Penyebab**: Data orang tua belum diisi di Dapodik
**Solusi**: Lengkapi data orang tua di aplikasi Dapodik, lalu sync ulang

---

## 🚀 Rekomendasi

1. **Lengkapi Data di Dapodik**: Pastikan semua data sudah lengkap di Dapodik sebelum melakukan sinkronisasi
2. **Samakan Nama Kelas**: Untuk auto-detect kelas, samakan nama kelas di SIAKAD dengan nama rombel di Dapodik
3. **Sync Berkala**: Lakukan sinkronisasi secara berkala untuk update data terbaru
4. **Backup Data**: Selalu backup database sebelum melakukan sinkronisasi massal
5. **Test Dulu**: Test dengan beberapa data dulu sebelum sync semua data

---

## 📞 Bantuan

Jika ada field tambahan dari Dapodik yang perlu dimapping ke SIAKAD, silakan:
1. Tambahkan field di tabel `students` atau `teachers`
2. Update mapping di controller `Dapodik.php` dan `DapodikApi.php`
3. Update hidden input di view `preview_students.php` atau `preview_teachers.php`
