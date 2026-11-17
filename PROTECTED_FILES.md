# PROTECTED_FILES.md — Zona Terlindung & Integritas Lisensi
**Daftar Berkas Kritis, Mekanisme Pengawasan Kriptografis, dan Larangan Akses Tim AI**

---

## 1. Latar Belakang & Prinsip Integritas
Aplikasi ESPATA dilengkapi dengan sistem pengamanan mandiri (*Self-Defense Integrity & License Guard*) untuk mencegah pembajakan, manipulasi berkas otorisasi, dan perusakan kode keamanan. Sistem ini diawasi secara otomatis oleh kelas `App\Libraries\SecurityGuard`.

**PRINSIP MUTLAK:**  
Enam berkas yang terdaftar di bawah ini berstatus **ZONA TERLINDUNG MUTLAK (READ-ONLY)** bagi seluruh agen AI. Agen hanya diizinkan membaca isi berkas untuk keperluan analisis, dan **DILARANG MENGUBAH, MENGEDIT, MERENAME, MEMINDAHKAN, ATAU MENGHAPUS** berkas-berkas ini.

---

## 2. Daftar 6 Berkas Terlindung (Critical Protected Files)

Sesuai definisi resmi pada fungsi `SecurityGuard::getCriticalFiles()`:

| No | Lokasi Berkas | Peran & Tanggung Jawab Berkas |
|:---:|:---|:---|
| **1** | `app/Helpers/license_helper.php` | Helper fungsi lisensi, verifikasi domain, dan pengecekan status aktivasi aplikasi. |
| **2** | `app/Libraries/LicenseGuard.php` | Mesin validasi lisensi hardware binding, dekripsi kunci, dan sinkronisasi server lisensi. |
| **3** | `app/Libraries/SecurityGuard.php` | Penjaga integritas hash, pemindai anti-tampering berkas kritis, dan verifikator `SEC_PASS`. |
| **4** | `app/Libraries/SecureConfig.php` | Pengelola penyimpanan konfigurasi terenkripsi yang aman dari pembacaan langsung. |
| **5** | `app/Filters/LicenseFilter.php` | Middleware penyaring request HTTP; memblokir akses sistem jika lisensi tidak valid atau berkas dimodifikasi. |
| **6** | `app/Models/LicenseModel.php` | Model akses basis data tabel `app_license` untuk membaca token dan masa berlaku lisensi. |

---

## 3. Mekanisme Pengawasan Kriptografis

1. **Berkas Hash Integritas:**
   - `app/Config/.security_hash`: Berisi kumpulan signature hash SHA-256 dari keenam berkas di atas.
   - `app/Config/.lic_hash`: Berisi signature enkripsi status lisensi dan pengikatan domain/hardware.
2. **Dampak Perubahan Ilegal (*Tampering Trigger*):**
   - Mengubah bahkan hanya **satu spasi, baris kosong, atau satu karakter** pada salah satu dari 6 berkas tersebut akan menghasilkan perbedaan hash (*hash mismatch*).
   - `SecurityGuard::verifyCriticalFiles()` seketika mengembalikan nilai `false` (`SEC_FAIL`).
   - `LicenseFilter` akan mendeteksi status kegagalan integritas, mencatat log peringatan, dan mengarahkan seluruh pengguna ke halaman aktivasi darurat (`/activate`), menyebabkan **aplikasi terkunci total**.

---

## 4. Prosedur Uji Coba Keamanan bagi AI QA & Security

Untuk memastikan integritas 6 berkas lisensi tetap aman setelah melakukan pekerjaan di modul lain, agen AI wajib menjalankan verifikasi baris perintah berikut:

```bash
php -r "define('APPPATH', __DIR__ . '/app/'); define('SYSTEMPATH', __DIR__ . '/system/'); require 'app/Libraries/SecurityGuard.php'; echo \App\Libraries\SecurityGuard::verifyCriticalFiles() ? 'SEC_PASS' : 'SEC_FAIL';"
```
- **Kondisi Normal:** Menghasilkan output string `SEC_PASS`.
- **Kondisi Bahaya:** Menghasilkan output string `SEC_FAIL`. Jika ini terjadi, pekerjaan harus segera dihentikan dan di-rollback.

---

## 5. Prosedur Resmi Pembaruan Lisensi (Hanya oleh Pemilik Proyek)

Jika di masa mendatang pemilik proyek secara sadar ingin memperbarui logika sistem lisensi:
1. Pemilik proyek melakukan perubahan secara manual atau memberikan mandat khusus.
2. Menghasilkan ulang signature hash resmi melalui perintah terminal CodeIgniter:
   ```bash
   php spark license:rehash
   ```
   atau mengeksekusi skrip internal darurat dengan token resmi yang diatur pemilik proyek.
3. Agen AI dilarang menjalankan skrip pembaruan hash tanpa instruksi dan pengawasan langsung dari pemilik proyek.
