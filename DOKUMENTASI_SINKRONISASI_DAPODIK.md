# Dokumentasi Sinkronisasi Dapodik - SIAKAD

Sistem SIAKAD menyediakan 2 metode sinkronisasi data dari Dapodik:

## 📍 Metode 1: Sinkronisasi Lokal (Same Network)

Digunakan ketika SIAKAD dan Dapodik terpasang di komputer yang sama atau dalam satu jaringan lokal.

### Cara Menggunakan:

1. **Akses Halaman Dapodik**
   - Login sebagai Admin atau Kepala Sekolah
   - Buka menu **Admin** > **Integrasi Dapodik**
   - URL: `http://localhost:8080/siakad/admin/dapodik`

2. **Konfigurasi Koneksi**
   - Isi form konfigurasi:
     - **URL API Dapodik:** `http://localhost:5774/WebService/` (default)
     - **Key Web Service:** Ambil dari aplikasi Dapodik (Menu Pengaturan > Web Service)
     - **NPSN:** Nomor NPSN sekolah Anda
   - Klik **Test Koneksi**
   - Jika berhasil, konfigurasi akan disimpan otomatis ke file `.env`

3. **Sinkronisasi Data Siswa**
   - Klik tombol **Ambil Data Siswa**
   - Sistem akan menampilkan preview data siswa dari Dapodik
   - Pilih siswa yang ingin disinkronkan (centang checkbox)
   - Pilih kelas tujuan (opsional, sistem akan deteksi otomatis dari nama rombel)
   - **Pilih Mode Sinkronisasi** untuk data yang sudah ada:
     - **Skip:** Lewati data yang sudah ada (tidak diubah)
     - **Update:** Timpa semua data dengan data terbaru dari Dapodik
     - **Merge:** Hanya isi field yang masih kosong (direkomendasikan)
   - Klik **Sinkronkan Siswa Terpilih**

4. **Sinkronisasi Data Guru**
   - Klik tombol **Ambil Data Guru**
   - Sistem akan menampilkan preview data guru dari Dapodik
   - Pilih guru yang ingin disinkronkan (centang checkbox)
   - Klik **Sinkronkan Guru Terpilih**

### Fitur Deteksi Kelas Otomatis:

Sistem akan mencoba mencocokkan nama rombel dari Dapodik dengan nama kelas di SIAKAD:
- Jika nama rombel cocok dengan nama kelas (case-insensitive), siswa akan otomatis masuk ke kelas tersebut
- Jika tidak cocok, gunakan pilihan kelas manual

**Contoh:**
- Rombel Dapodik: "Kelas 7A" → Cocok dengan Kelas "Kelas 7A" di SIAKAD
- Rombel Dapodik: "VII A" → Tidak cocok, perlu pilih manual

---

## 🌐 Metode 2: Bridge Sync (Online/Remote)

Digunakan ketika SIAKAD terpasang di hosting online, sedangkan Dapodik di PC lokal sekolah.

### Cara Menggunakan:

1. **Setup di Server Online**
   
   Edit file `.env` di server dan tambahkan:
   ```env
   SIAKAD_SYNC_TOKEN = "siakad_sync_2026_9b1a2b3c4d5e6f7g8h9i0j"
   ```
   
   **PENTING:** Gunakan token yang kuat dan unik!

2. **Setup di PC Dapodik**
   
   a. Buka folder `bridge/` di instalasi SIAKAD lokal
   
   b. Edit file `siakad_sync_agent.ps1`:
   ```powershell
   $SIAKAD_URL = "https://siakad-anda.com/api/dapodik/receive"
   $SIAKAD_TOKEN = "siakad_sync_2026_9b1a2b3c4d5e6f7g8h9i0j"
   $DAPODIK_URL = "http://127.0.0.1:5774/WebService"
   $DAPODIK_KEY = "Zikf0c0WK7snRmf"  # Ambil dari Dapodik
   $DAPODIK_NPSN = "20105399"  # NPSN sekolah Anda
   ```

3. **Jalankan Sync**
   
   Buka PowerShell dan jalankan:
   ```powershell
   cd C:\path\to\siakad\bridge
   .\siakad_sync_agent.ps1
   ```

4. **Verifikasi**
   
   - Login ke SIAKAD online
   - Cek menu **Admin** > **Data Siswa** dan **Data Guru**
   - Data dari Dapodik akan muncul dengan status "Harus Ganti Password"

### Dokumentasi Lengkap Bridge:

Lihat file `bridge/README.md` untuk dokumentasi lengkap tentang:
- Setup detail
- Troubleshooting
- Sinkronisasi berkala dengan Task Scheduler
- Error handling

---

## 🔐 Keamanan

### Password Default:

Setelah sinkronisasi, user akan dibuat dengan password default:
- **Siswa:** `siswa[NISN]` 
  - Contoh: NISN `29192919` → Password: `siswa29192919`
- **Guru:** `guru[NUPTK]` atau `guru[NIP]`
  - Contoh: NUPTK `1234567890` → Password: `guru1234567890`

**User HARUS mengganti password saat login pertama!**

### Token Sync:

- Token `SIAKAD_SYNC_TOKEN` harus dijaga kerahasiaannya
- Jangan share token ke pihak yang tidak berwenang
- Gunakan token yang kuat (minimal 32 karakter random)
- Ganti token secara berkala untuk keamanan

---

## 📊 Data yang Disinkronkan

### Data Siswa (34 Field):

**Data Pribadi (11 field):**
- NISN (identifier unik)
- NIS
- Nama lengkap
- NIK
- Jenis kelamin
- Tempat lahir
- Tanggal lahir
- Anak ke-berapa
- Agama
- Kewarganegaraan
- Nama rombel (untuk deteksi kelas)

**Data Alamat (7 field):**
- Alamat jalan
- Jenis tinggal
- Moda transportasi
- Jarak rumah ke sekolah
- Latitude
- Longitude
- Kebutuhan khusus

**Data Ayah (6 field):**
- Nama ayah
- NIK ayah
- Tahun lahir ayah
- Pendidikan ayah
- Pekerjaan ayah
- Penghasilan ayah

**Data Ibu (6 field):**
- Nama ibu kandung
- NIK ibu
- Tahun lahir ibu
- Pendidikan ibu
- Pekerjaan ibu
- Penghasilan ibu

**Data Wali (4 field):**
- Nama wali
- Pendidikan wali
- Pekerjaan wali
- Penghasilan wali

### Data Guru:
- NUPTK (sebagai identifier unik)
- NIP
- Nama lengkap
- Jenis kelamin
- Tempat lahir
- Tanggal lahir
- Email (jika ada)

---

## 🔄 Mode Sinkronisasi Data

Saat melakukan sinkronisasi siswa, Anda dapat memilih bagaimana sistem menangani data yang sudah ada:

### 1. Mode Skip (Lewati)
- Data yang NISN-nya sudah terdaftar akan **dilewati**
- Tidak ada perubahan pada data yang sudah ada
- Hanya menambahkan siswa baru
- **Gunakan jika:** Data di SIAKAD sudah benar dan tidak ingin diubah

### 2. Mode Update (Timpa)
- Data yang sudah ada akan **ditimpa sepenuhnya** dengan data dari Dapodik
- Semua field akan diperbarui dengan data terbaru
- **Gunakan jika:** Data Dapodik lebih lengkap/akurat dari SIAKAD
- **Hati-hati:** Data lama akan hilang dan diganti

### 3. Mode Merge (Isi yang Kosong) - DIREKOMENDASIKAN
- Hanya mengisi field yang **masih kosong** di SIAKAD
- Field yang sudah terisi tidak akan diubah
- **Gunakan jika:** Ingin melengkapi data tanpa menghilangkan data yang sudah ada
- **Contoh:** 
  - SIAKAD: Alamat kosong, Nama Ayah terisi
  - Dapodik: Alamat terisi, Nama Ayah terisi
  - Hasil: Alamat diisi dari Dapodik, Nama Ayah tetap dari SIAKAD

**Rekomendasi:** Gunakan mode **Merge** untuk sinkronisasi rutin agar data semakin lengkap tanpa kehilangan data yang sudah diinput manual.

---

## ⚠️ Catatan Penting

1. **Data Duplikat:** Penanganan data duplikat tergantung mode sinkronisasi yang dipilih:
   - Siswa diidentifikasi berdasarkan: NISN
   - Guru diidentifikasi berdasarkan: NUPTK atau NIP

2. **Kelas Akademik:** Siswa akan otomatis masuk ke tahun ajaran aktif saat ini

3. **Status Aktif:** Semua siswa yang disinkronkan akan berstatus "aktif"

4. **User Account:** Setiap siswa/guru akan dibuatkan akun user otomatis dengan:
   - Username: NISN (siswa) atau NUPTK/NIP (guru)
   - Email: `[username]@siswa.local` atau `[username]@guru.local`
   - Role: Siswa (role_id=5) atau Guru (role_id=3)

5. **Koneksi Dapodik:** Pastikan aplikasi Dapodik berjalan dan Web Service aktif sebelum melakukan sinkronisasi

---

## 🔧 Troubleshooting

### "Gagal terhubung ke Dapodik"

**Solusi:**
1. Pastikan aplikasi Dapodik terbuka
2. Cek Web Service di Dapodik (Menu Pengaturan > Web Service)
3. Pastikan Key Web Service benar
4. Cek firewall tidak memblokir port 5774

### "Unauthorized access. Invalid or missing sync token"

**Solusi:**
1. Pastikan token di script sama dengan di `.env` server
2. Pastikan tidak ada spasi atau karakter tersembunyi
3. Restart web server setelah mengubah `.env`

### "Siswa/Guru sudah ada"

Ini normal. Penanganan tergantung mode sinkronisasi:
- **Mode Skip:** Data dilewati (tidak ada perubahan)
- **Mode Update:** Data ditimpa dengan data Dapodik
- **Mode Merge:** Field kosong diisi dari Dapodik

Pesan sukses akan menampilkan jumlah siswa baru, diperbarui, dan dilewati.

### Kelas tidak terdeteksi otomatis

**Solusi:**
1. Cek nama kelas di SIAKAD apakah sama persis dengan nama rombel di Dapodik
2. Gunakan pilihan kelas manual jika nama berbeda
3. Sesuaikan nama kelas di SIAKAD agar cocok dengan Dapodik

---

## 📞 Bantuan

Jika mengalami masalah yang tidak tercantum di dokumentasi:
1. Cek log error di `writable/logs/log-[tanggal].php`
2. Cek output error di PowerShell (untuk bridge sync)
3. Hubungi administrator sistem atau developer
