# ASIS AI SECURITY & PRIVACY DESIGN
**Spesifikasi Desain Keamanan Siber, Tata Kelola Privasi Data, dan Kepatuhan Lisensi**
*Dokumen Arsitektur Keamanan Mandat EAO-002B — Disempurnakan oleh AI Security Reviewer & AI System Architect*

---

| Parameter | Keterangan Dokumen Keamanan |
|:---|:---|
| **Fokus Keamanan** | Perlindungan Zero PII 4-Lapis, Mitigasi Quasi-Identifier, RBAC Pre-Query, Anti-IDOR |
| **Status Dokumen** | **Rancangan Keamanan Final Tervalidasi (Menunggu Otorisasi Pemilik Proyek)** |
| **Kepatuhan Lisensi** | Wajib `SEC_PASS` (`SecurityGuard::verifyCriticalFiles()`), 6 Berkas Terlindung Read-Only |
| **Kepatuhan Regulasi** | Prinsip UU Perlindungan Data Pribadi (UU PDP No. 27/2022) & Standar OWASP Web Security |

---

## 1. Prinsip Dasar Keamanan & Privasi Asis AI

Asis AI ESPATA beroperasi di lingkungan pendidikan yang mengelola data sensitif anak di bawah umur (siswa), rekam jejak akademik, catatan bimbingan konseling (BK), dan identitas tenaga pendidik. Oleh karena itu, arsitektur keamanan Asis AI menganut lima prinsip mutlak:

1. **Zero Direct PII Leakage:** Data pribadi teridentifikasi secara langsung (Nama Lengkap, NIK, NISN, Alamat Rumah, Nomor HP, Email Pribadi) **DILARANG KERAS** dikirim ke server AI pihak ketiga (Groq Cloud API, Google Gemini API).
2. **Mitigasi Identifikasi Tidak Langsung (*Quasi-Identifier Mitigation*):** Kombinasi atribut data yang secara statistik dapat merekonstruksi identitas seorang siswa wajib disamarkan atau diagregasikan sebelum keluar dari sistem lokal.
3. **Pemberian Wewenang Sebelum Pengambilan Data (*Pre-Query RBAC Enforcement*):** Pengecekan hak akses pengguna dan kepemilikan data diverifikasi pada tingkat kueri basis data (*database query boundary*), bukan disaring setelah data ditarik ke memori aplikasi.
4. **Isolasi Mutlak Percakapan Antar-Pengguna (*Strict Conversation Isolation*):** Sesi percakapan terikat secara kriptografis pada identitas pengguna login (`user_id`). Tidak ada celah IDOR yang memungkinkan seorang pengguna mengintip atau menyuntikkan pesan ke sesi milik pengguna lain.
5. **Kepatuhan Zona Terlindung Lisensi:** Arsitektur Asis AI beroperasi di luar zona 6 berkas lisensi dan tidak boleh mengubah atau mengganggu mekanisme verifikasi hash `SecurityGuard.php`.

---

## 2. Arsitektur Perlindungan Data Pribadi Berlapis (Multi-Layer Zero PII)

Sistem menerapkan 4 lapisan penyaring data (*4-Layer Defense-in-Depth*) sebelum payload kueri dikirim ke penyedia LLM eksternal:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        LAPISAN 1: CLIENT-SIDE SANITIZATION             │
│  - Input form memindai pola teks NIK (16 digit) dan NISN (10 digit).  │
│  - Peringatan UI aktif: Mencegah guru memasukkan data rahasia siswa.   │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                 LAPISAN 2: CONTROLLER & PRE-QUERY RBAC GATE            │
│  - Verifikasi Session & Role ID pengguna login.                       │
│  - Verifikasi Kepemilikan Data: Guru A hanya dapat mengakses kelas &   │
│    mata pelajaran yang sah di teaching_assignments.                    │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│            LAPISAN 3: CONTEXT BUILDER & REGEX DATA SCRUBBER            │
│  - Pembersihan otomatis via PiiScrubber:                              │
│    * NIK (16 digit)       ──► [NIK_DISAMARKAN]                         │
│    * NISN (10 digit)      ──► [NISN_DISAMARKAN]                        │
│    * Nomor HP (+62/08xx)  ──► [NO_HP_DISAMARKAN]                       │
│    * Alamat Jalan/RT/RW   ──► [ALAMAT_DISAMARKAN]                      │
│    * Pola Gelar/Panggilan ──► [NAMA_DISAMARKAN]                        │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│          LAPISAN 4: QUASI-IDENTIFIER AGGREGATION & PAYLOAD BOUNDARY    │
│  - Transformasi data individu menjadi data tingkat capaian/kategori:  │
│    * "Budi Santoso (Nilai 40)" ──► "Siswa #1 (Kategori: Perlu Bimbingan)│
│  - Penerapan k-anonymity (k >= 3) dan pengelompokan rentang nilai.     │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
                   [ AMAN DIKIRIM KE GROQ / GEMINI ]
```

### Rincian Implementasi Lapisan 3 & 4 (Scrubber & Anonymizer):
- **Transformasi Token Siswa:** Setiap rujukan terhadap siswa dalam konteks analisis (misal: analisis nilai ulangan atau remedial) diubah menjadi label anonim dinamis: `Siswa A`, `Siswa B`, `Siswa C`.
- Pemetaan label asli (`Siswa A` $\leftrightarrow$ `Budi Santoso`) disimpan hanya dalam memori lokal controller/session selama siklus request berjalan dan tidak pernah dikirim ke LLM.
- Ketika LLM mengembalikan rekomendasi:
  *"Siswa A memerlukan pengayaan materi Geometri, sedangkan Siswa B perlu latihan dasar."*  
  Controller lokal memetakan kembali label tersebut ke nama siswa pada tampilan kartu antarmuka pengguna di sisi browser guru.

---

## 3. Evaluasi Risiko Identifikasi Tidak Langsung (Quasi-Identifiers) & Teks Bebas

### A. Pengujian Kombinasi Quasi-Identifiers
Bahkan tanpa nama atau NISN, seorang siswa dapat diidentifikasi secara unik jika kombinasi atribut data yang dikirimkan terlalu spesifik (*quasi-identifiers*).
- **Skenario Risiko Tinggi:** Kombinasi `[Tingkat Kelas] + [Tanggal Lahir / Usia Spesifik] + [Jenis Kelamin] + [Nilai Ekstrem Paling Rendah/Tinggi]`. Jika di kelas 7B hanya ada satu siswa perempuan yang berusia 11 tahun dan nilainya 20, maka data tersebut secara faktual telah merekonstruksi identitas anak tersebut.
- **Protokol Pengujian Kombinasi:**
  Sebelum konteks KBM dikirim ke LLM, filter lokal mengevaluasi tingkat keunikan baris data (*distinctness check*). Jika suatu rekaman data memiliki kombinasi atribut yang bernilai unik tunggal ($n = 1$) dalam populasi rombel tersebut:
  1. Data usia spesifik dipangkas menjadi rentang kelompok (*"Kelompok Usia Standar Fase D"*).
  2. Data jenis kelamin dihilangkan dari payload prompt.
  3. Nilai angka presisi (misal `21.50`) dikonversi ke kategori predikat (*"Perlu Bimbingan Khusus"*).

### B. Evaluasi Risiko Input Teks Bebas (*Free-Text Risk*)
Guru sering kali mengetikkan informasi konteks dalam bentuk teks bebas yang memuat nama orang tua, profesi, atau kondisi keluarga:
*Contoh Prompt Guru:* *"Asis, bagaimana cara menghadapi anak Bu Siti yang kemarin sakit typus dan ayahnya seorang camat?"*

**Strategi Mitigasi Masalah Teks Bebas:**
1. **Pemindaian Pola Panggilan & Jabatan (*Title & Entity Scrubber*):**
   Regex mendeteksi prefiks kekeluargaan/jabatan (`Bu [A-Z]\w+`, `Pak [A-Z]\w+`, `ayahnya [A-Z]\w+`, kata kunci penyakit medis umum, dan gelar jabatan publik) dan menyamarkannya menjadi `[Wali Murid]` atau `[Tokoh Lokal]`.
2. **Peringatan Etika Antarmuka (Active UI Warning Banner):**
   Di atas kotak input chat terpasang banner pengingat etika:
   > *⚠️ Asis AI menyamarkan data secara otomatis. Mohon tidak mengetikkan nama orang tua, kondisi medis privat, atau rahasia keluarga siswa pada kolom pertanyaan.*

### C. Penegasan Status: Mitigasi Risiko, Bukan Jaminan Mutlak
> [!IMPORTANT]
> **Penegasan Regulasi & Prinsip Kriptografis:**  
> Prinsip $k$-anonymity dan penyaringan data yang diterapkan pada Asis AI ESPATA berstatus sebagai **upaya mitigasi risiko teknis tingkat tinggi (*engineering risk mitigation*)**, dan **BUKAN jaminan matematis mutlak (*not an absolute mathematical guarantee*)**.  
> Dalam ekosistem sekolah skala kecil, seorang guru yang mengajar di kelas yang sama tentu memiliki pengetahuan latar (*background knowledge*) yang luas mengenai murid-muridnya. Oleh karena itu, pengawasan manusia (*Human-in-the-Loop*) dan kepatuhan etika profesi guru tetap menjadi pilar pertahanan terdepan dalam menjaga kerahasiaan data anak.

---

## 4. Batasan Muatan Data Eksternal (Payload Boundaries)

Tabel berikut menetapkan batas tegas data yang **Boleh** dan **Dilarang** dikirim ke penyedia AI eksternal:

| Kategori Data | Data yang DIIZINKAN ke LLM Eksternal | Data yang DILARANG KERAS ke LLM Eksternal |
|:---|:---|:---|
| **Identitas Siswa** | Label anonim (`Siswa 1`, `Siswa 2`), Tingkat Kelas (7/8/9), Fase Kurikulum (Fase D). | Nama Lengkap, NISN, NIK, Tempat/Tgl Lahir, Nomor HP, Foto Profil, Alamat Rumah. |
| **Identitas Guru** | Nama sapaan gelar akademik umum (*"Bapak Guru Fisika"*), Mata Pelajaran Ampuan, Jenjang Sekolah. | NIP/NUPTK, KTP, Nomor Rekening Bank, Password, Alamat Pribadi. |
| **Data Akademik** | Capaian TP, Rentang Nilai Agregat, Narasi Modul Ajar, Tema Kokurikuler, Rubrik CBT. | Rekam Medis Siswa, Riwayat Pelanggaran BK Sensitif, Nilai Rapor Final Teridentifikasi. |
| **Data Keuangan** | **TIDAK ADA (Modul Bayaran Berstatus Frozen & Terisolasi)**. | SPP, Tunggakan, Gaji Guru, Buku Kas, Nomor Rekening Sekolah. |
| **Data Sistem** | Versi PHP, nama role pengguna (`guru`, `kepsek`), waktu lokal (siang/sore). | Database DSN, Hash Password, Session Token, CSRF Secret, File Lisensi Hash. |

---

## 5. Verifikasi Hak Akses Sebelum Kueri (Pre-Query RBAC Enforcement)

Celah keamanan sering terjadi jika aplikasi mengambil seluruh data dari database terlebih dahulu, lalu menyaringnya di PHP. Jika terjadi galat logika, data yang tidak berhak dapat bocor ke memori atau konteks prompt.

### Aturan Baku Kueri Asis AI:
1. **Verifikasi Kepemilikan di Tingkat SQL:**
   Setiap kueri data akademik wajib mengikatkan parameter sesi pengguna (`user_id` dan `role_id`):
   ```php
   // CONTOH IMPLEMENTASI BAKU:
   // Memastikan Guru (Role 3) hanya mengakses kelas yang diampunya
   $builder = $db->table('classes c')
       ->join('teaching_assignments ta', 'ta.class_id = c.id')
       ->where('ta.teacher_id', $currentTeacherId)
       ->where('c.id', $targetClassId);
   ```
2. **Pengecekan Otorisasi Berjenjang:**
   - **Role 1 (Admin):** Berhak mengakses data teknis, konfigurasi menu, dan log audit, namun dibatasi dari membaca transkrip obrolan privat antar-guru.
   - **Role 2 (Kepala Sekolah):** Berhak membaca statistik agregat sekolah, supervisi kelengkapan modul ajar, dan rekapitulasi kehadiran GTK. Dilarang mengubah data nilai secara langsung.
   - **Role 3 (Guru):** Berhak mengakses daftar siswa dan nilai hanya untuk mata pelajaran dan rombel kelas yang ditugaskan dalam semester aktif.
   - **Role 4 (Orang Tua):** Dibatasi secara mutlak hanya pada data anak kandung yang terhubung pada tabel relasi orang tua-siswa (`parent_student_relations`).
   - **Role 5 (Siswa):** Dibatasi secara mutlak hanya pada data nilai pribadi, jadwal kelas sendiri, dan ujian CBT yang aktif untuk dirinya.

---

## 6. Isolasi Sesi Percakapan & Pencegahan IDOR

Untuk mencegah kebocoran riwayat obrolan antar-pengguna (*Insecure Direct Object Reference*):

1. **Penggunaan Identitas UUIDv4:**
   - Kolom `id` pada tabel `ai_conversations` menggunakan UUID acak 36 karakter (misal: `c4a8b79e-4a61-4f1e-9b2a-8d1234567890`), bukan integer berurutan (`1, 2, 3...`) yang mudah ditebak atau dipindai secara massal.
2. **Validasi Kepemilikan Ganda (Double Ownership Guard):**
   - Setiap endpoint yang membaca atau menambahkan pesan pada sesi wajib memeriksa kepemilikan sesi sebelum memproses:
     ```sql
     SELECT id, user_id, role_id, is_active 
     FROM ai_conversations 
     WHERE id = :conversation_id AND user_id = :current_user_id
     ```
   - Jika `conversation_id` tidak ditemukan untuk `user_id` tersebut, sistem langsung mengembalikan respon `HTTP 403 Forbidden` dan mencatat insiden ke log keamanan tanpa membocorkan eksistensi ID tersebut.
3. **Pemisahan Sesi Berdasarkan Peran Pengguna:**
   - Jika satu akun pengguna memiliki multi-peran (misal Guru yang juga menjadi Wakil Kepala Sekolah), riwayat obrolan dipisahkan berdasarkan peran aktif saat sesi dibuka (`role_id`), mencegah tercampurnya konteks tugas administratif dengan tugas mengajar.

---

## 7. Tata Kelola Kunci API (API Key Governance & Policy)

Sesuai arahan Mandat EAO-002B, ditetapkan hubungan resmi antara **Kunci Sistem Bawaan Sekolah** dan **BYOK Guru**:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        HIERARKI RESOLUSI API KEY                       │
└────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
                     [ Apakah Provider = Gemini? ]
                    /                             \
                 (Ya)                            (Tidak: Groq)
                 /                                     \
    [ Apakah Guru Punya BYOK? ]               [ Wajib Kunci Sekolah ]
    (teachers.gemini_api_key)                      (GROQ_API_KEY .env)
       /                 \                             │
  (Ada Kunci)        (Kosong / Nol)                    │
      /                     \                          ▼
[ Gunakan Kunci Guru ]  [ Gunakan GEMINI_API_KEY ] [ Eksekusi Groq ]
                           (dari .env Sekolah)
```

### Aturan Baku Tata Kelola Kunci:
1. **Cakupan Pembatasan BYOK (Bring Your Own Key):**
   - **BYOK HANYA BERLAKU UNTUK GOOGLE GEMINI.**
   - Guru diperbolehkan memasukkan kunci Gemini mandiri pada profil mereka (kolom eksisting `teachers.gemini_api_key`).
   - **Groq Cloud API TIDAK MENDUKUNG BYOK.** Groq dikelola secara terpusat oleh sekolah melalui file `.env` (`GROQ_API_KEY`) untuk menjaga kendali kuota, stabilitas monitoring, dan penyeragaman model.
2. **Aturan Prioritas Penggunaan:**
   - Jika pemanggilan ditujukan ke Gemini dan guru memiliki kunci mandiri yang valid di basis data, sistem memprioritaskan kunci BYOK guru tersebut (menghemat kuota sekolah).
   - Jika kunci BYOK guru kosong, sistem beralih menggunakan kunci sekolah di `.env`.
   - Jika kunci sekolah di `.env` juga tidak terkonfigurasi, sistem menolak pemanggilan secara anggun dengan pesan informatif kepada pengguna.
3. **Penyimpanan Eksisting Tanpa Perubahan:**
   - Sistem memanfaatkan kolom database eksisting (`teachers.gemini_api_key` dan `users.gemini_api_key`) serta file konfigurasi `.env`. Tidak ada migrasi basis data maupun perubahan struktur tabel untuk tata kelola kunci ini.
4. **Penyembunyian Nilai Mentah (*Key Masking*):**
   - Kunci API tidak pernah dikirim ke browser klien via JSON/HTML data-attribute. Pada halaman profil guru, nilai kunci disamarkan secara visual (`AIzaSy...4xQ1`).

---

## 8. Kepatuhan Zona Terlindung Lisensi (Protected License Compliance)

- **Audit Integritas:** Arsitektur Asis AI beroperasi penuh pada namespace `App\Services\Ai\` dan `App\Controllers\Api\`.
- **Nihil Interferensi:** Tidak ada berkas di `PROTECTED_FILES.md` yang dimodifikasi.
- **Pemeriksaan Berkala:** AI Security Reviewer memverifikasi status `SecurityGuard::verifyCriticalFiles()` pada setiap tahapan, dengan syarat kelulusan mutlak bernilai `1` (`SEC_PASS`).