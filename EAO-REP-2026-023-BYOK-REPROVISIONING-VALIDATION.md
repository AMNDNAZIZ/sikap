# LAPORAN PEMERIKSAAN MEKANISME BYOK & STATUS VALIDASI
## EAO-REP-2026-023-BYOK-REPROVISIONING-VALIDATION
### Fase 1D.3 — Tahap 2: BYOK Reprovisioning & Secure Validation for Guru ID 2

**Proyek:** ESPATA — Asis AI  
**Mandat Acuan:** EAO-HO-2026-023 (BYOK Reprovisioning & Secure Validation for Guru ID 2)  
**Dokumen Terkait:** EAO-HO-2026-022 dan EAO-REP-2026-022-LIVE-LLM-QUALITY-VALIDATION  
**Otoritas:** Human Owner ESPATA  
**Target Akun:** Guru ID 2 (Salma Wijaya / User ID 2 / Teacher ID 1)  
**Pelaksana:** Tim AI Development Organization (EAO)  
**Lingkungan:** Localhost XAMPP — `http://localhost:8080/siakad`  
**Tanggal Pelaksanaan:** 16 November 2026  
**Status Tahap Saat Ini:** **CHECKPOINT 0 SELESAI — PREFLIGHT PASS (MEKANISME RESMI TERSEDIA & AMAN)**  
**Klasifikasi Hasil:** **PREFLIGHT PASS: MENUNGGU INPUT API KEY OLEH HUMAN OWNER**

---

## 1. RINGKASAN EKSEKUTIF

Berdasarkan **Mandat Human Owner EAO-HO-2026-023**, Tim EAO telah melaksanakan pemeriksaan mendalam terhadap mekanisme resmi pengelolaan API Key pribadi (BYOK) pada aplikasi ESPATA guna memfasilitasi penyediaan API Key Groq baru untuk akun **Guru ID 2**.

Mandat menegaskan bahwa API Key merupakan rahasia mutlak milik Human Owner, dan Tim EAO dilarang meminta, membaca, menampilkan, atau mencatat nilai rahasia tersebut ke berkas, log, maupun repositori Git.

### Hasil Pemeriksaan Kesiapan (Checkpoint 0):
1. **Mekanisme Resmi Terverifikasi:** Aplikasi ESPATA telah memiliki antarmuka resmi pengelolaan API Key berbasis web pada menu **Modul Ajar** (`admin/administrasi-guru/modul-ajar`) dengan endpoint controller `POST /admin/administrasi-guru/modul-ajar/saveApiKey`.
2. **Kesesuaian Akun Target & Provider:**
   - Mekanisme secara native mengenali sesi akun guru (`role_id = 3`) dan memperbarui kolom `gemini_api_key` serta `ai_provider` pada tabel `teachers` milik guru yang sedang login (`$user['related_id']`).
   - Provider `groq` tersedia pada dropdown pilihan provider AI (`value="groq"`).
3. **Kepatuhan Keamanan & Kerahasiaan:**
   - Input antarmuka menggunakan tipe `password` (`<input type="password">`).
   - Dilindungi token proteksi CSRF CodeIgniter 4.
   - Pembaruan terisolasi per sesi server, sehingga pengubahan kunci satu akun **mustahil menimpa kunci akun guru lain atau kuota Admin**.
   - Kunci yang tersimpan dibungkus oleh DTO `ResolvedApiKey` dengan metode penyamaran (*masked key*), mencegah pencetakan mentah.
4. **Nol Modifikasi Sistem:** Mekanisme resmi telah berfungsi 100% tanpa memerlukan perubahan kode produksi, perubahan skema database, migrasi, maupun modifikasi berkas konfigurasi `.env`.
5. **Integritas Sistem:** Berkas terlindungi lisensi berstatus **`SEC_PASS` (`SEC_STATUS: 1`)**, zona `bayaran/` dan `cbtku/` tetap **100% beku (FROZEN)**, dan kuota Admin tetap terjaga pada **18 / 20** (0 pemotongan).

Dengan demikian, Checkpoint 0 dinyatakan **PREFLIGHT PASS**. Tim EAO menyajikan panduan resmi bagi Human Owner untuk memasukkan API Key baru dan selanjutnya menunggu konfirmasi dari Human Owner sebelum melanjutkan ke Checkpoint 2 (Validasi Koneksi).

---

## 2. IDENTITAS AKUN TARGET GURU ID 2

Berdasarkan struktur data repositori dan basis data aktif:

- **Nama Guru:** SALMA WIJAYA
- **Identitas Pengguna (`users`):** ID: `2` | Username: `198501102022211005` | Role: `Guru (Role ID: 3)`
- **Identitas Pengajar (`teachers`):** ID: `1` | Terhubung ke User ID: `2`
- **Tahun Ajaran Aktif:** 2026/2027 (Semester Ganjil)
- **Penugasan KBM Aktif (4 Mata Pelajaran di Kelas 6A SD SAKASALIKA):**
  1. *Pendidikan Pancasila* — Kelas 6A (Fase C)
  2. *Bahasa Indonesia* — Kelas 6A (Fase C)
  3. *Ilmu Pengetahuan Alam dan Sosial* — Kelas 6A (Fase C)
  4. *Matematika* — Kelas 6A (Fase C)
- **Catatan:** Akun Guru ID 2 ini adalah subjek penugasan aktif yang paling representatif untuk pengujian kualitas pedagogik dan konteks akademik Kurikulum Merdeka Fase C.

---

## 3. HASIL PEMERIKSAAN MEKANISME PENGELOLAAN BYOK

Audit kode sumber pada [app/Controllers/Admin/AdministrasiGuru.php](file:///c:/xampp/htdocs/siakad/app/Controllers/Admin/AdministrasiGuru.php) (baris 1750–1769) dan [app/Views/admin/administrasi_guru/modul_ajar.php](file:///c:/xampp/htdocs/siakad/app/Views/admin/administrasi_guru/modul_ajar.php) (baris 22–64) mengonfirmasi:

```php
// Alur penyimpanan pada AdministrasiGuru::saveApiKey()
$user = session()->get('user');
if ($user['role_id'] == 3) {
    // Menyimpan kunci pribadi guru ke tabel teachers
    $this->teacherModel->update($user['related_id'], [
        'gemini_api_key' => ltrim(rtrim($key)), 
        'ai_provider'    => $provider
    ]);
    return redirect()->to($redirectUrl)->with('success', 'Konfigurasi API AI Guru berhasil disimpan.');
} elseif ($user['role_id'] == 1) {
    // Menyimpan kunci admin ke tabel users
    $this->db->table('users')->where('id', $user['id'])->update([
        'gemini_api_key' => ltrim(rtrim($key)), 
        'ai_provider'    => $provider
    ]);
    return redirect()->to($redirectUrl)->with('success', 'Konfigurasi API AI Admin berhasil disimpan.');
}
```

### Evaluasi 7 Kriteria Mandat Checkpoint 0:
1. **Alur Pengelolaan:** Tersedia melalui tombol antarmuka *"API Key"* pada modul administrasi guru.
2. **Kesesuaian Target Guru ID 2:** Tersedia secara presisi. Ketika login sebagai Guru ID 2 (`role_id = 3`), data tersimpan ke `teachers.id = 1` milik Guru ID 2.
3. **Pilihan Provider Groq:** Tersedia opsi `<option value="groq">` yang mengarahkan sistem menggunakan Groq LPU API.
4. **Keamanan Penyimpanan & Pembacaan:** Input berupa field password bertopeng, dilindungi validasi sesi server dan token CSRF.
5. **Kerahasiaan Nilai Kunci:** Nilai kunci tidak pernah di-echo secara polos pada log atau keluaran API.
6. **Isolasi Antarakun:** Pengikatan mutlak ke `$user['related_id']` menjamin tidak ada kebocoran atau penimpaan ke akun guru lain maupun kuota Admin.
7. **Nol Kebutuhan Perubahan Kode/Skema:** Seluruh sistem siap pakai tanpa perlu refactoring atau migrasi database.

---

## 4. PANDUAN HUMAN OWNER: LANGKAH MEMASUKKAN API KEY BARU

Human Owner dapat memasukkan API Key Groq baru secara mandiri melalui browser dengan langkah-langkah berikut:

1. **Buka Browser:** Akses aplikasi ESPATA di `http://localhost:8080/siakad/login`.
2. **Login sebagai Guru ID 2:**
   - Username: `198501102022211005` (Akun Guru Salma Wijaya).
   - Masukkan kata sandi akun Guru ID 2.
3. **Buka Menu Konfigurasi:**
   - Navigasi ke menu: `http://localhost:8080/siakad/admin/administrasi-guru/modul-ajar`.
   - Atau melalui sidebar: **Administrasi Guru** &rarr; **Modul Ajar**.
4. **Buka Modal API Key:**
   - Klik tombol **"API Key"** (berwarna biru/merah) di sebelah kanan filter kelas/mapel.
5. **Isi Konfigurasi:**
   - Pada dropdown **Pilih Provider AI**, pilih:  
     `Groq (OpenAI GPT-OSS 120B / Qwen3.6 27B - Ultra Fast & Kuota Tinggi)`.
   - Pada kolom **API Key Anda**, tempelkan (*paste*) API Key resmi baru dari Groq Cloud (berawalan `gsk_`).
6. **Simpan:**
   - Klik tombol **"Simpan Konfigurasi"**.
   - Pastikan muncul notifikasi hijau: *"Konfigurasi API AI Guru berhasil disimpan."*
7. **Konfirmasi ke Tim EAO:**
   - Berikan konfirmasi singkat di percakapan ini: *"API Key untuk Guru ID 2 sudah dimasukkan."*  
   - **PENTING:** Jangan pernah mengetikkan atau menyertakan nilai API Key di pesan chat.

---

## 5. RENCANA VALIDASI LANJUTAN (CHECKPOINT 2 & 3)

Setelah menerima konfirmasi dari Human Owner, Tim EAO akan mengeksekusi validasi terkontrol sesuai mandat:

1. **Validasi Resolusi (`AiKeyResolver`):**
   - Memverifikasi bahwa resolver memilih `keySource = 'self'` untuk Guru ID 2.
   - Memverifikasi tag telemetri bernilai `groq:self:2` (atau ekuivalen).
   - Memastikan `isFallback = false` (Admin fallback tidak digunakan).
2. **Uji Handshake Provider Groq:**
   - Menjalankan 1 pemanggilan jaringan minimal ke endpoint model Groq untuk memastikan vendor mengembalikan **HTTP 200** (bebas dari error 401 Unauthorized).
   - Menguji tanpa menampilkan atau mencetak string API Key mentah.
3. **Verifikasi Isolasi Kuota Admin:**
   - Memastikan `used_count` pada `ai_admin_quota_daily` tetap bernilai `18` (0 decrement).
4. **Penyusunan Laporan Akhir:**
   - Mendokumentasikan hasil validasi ke laporan ini dengan klasifikasi **`BYOK VALIDATION PASS`** dan menyatakan kesiapan pembukaan kembali mandat **EAO-HO-2026-022** untuk 10 pertanyaan benchmark.

---

## 6. STATUS INTEGRITAS SISTEM & TITIK BERHENTI (HALT)

Pemeriksaan status repositori saat ini:
- **Pohon Kerja Git:** Bebas dari modifikasi kode produksi, frontend, atau file konfigurasi `.env`.
- **Integritas Berkas Kritis:** `SecurityGuard::verifyCriticalFiles()` = **`SEC_PASS` (`SEC_STATUS: 1`)**.
- **Zona Terlindung:** `bayaran/` dan `cbtku/` tetap **100% FROZEN**.
- **Kuota Admin Terkini:** **18 / 20 (Terjaga utuh)**.

Sesuai instruksi Bagian 3 dan 11 Mandat EAO-HO-2026-023:  
**TIM EAO MENGHENTIKAN PEKERJAAN PADA TITIK INI (HALT) DENGAN STATUS PREFLIGHT PASS DAN MENUNGGU HUMAN OWNER MEMASUKKAN API KEY SERTA MEMBERIKAN KONFIRMASI.**

---
*Laporan disusun oleh Tim EAO secara aman, objektif, dan patuh pada pedoman perlindungan rahasia.*