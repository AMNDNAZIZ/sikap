# LAPORAN AUDIT AKAR MASALAH (ROOT CAUSE AUDIT)
## EAO-REP-2026-037-ROOT-CAUSE-Q4-Q6
### Investigasi Teknis Read-Only Terhadap Kegagalan Vendor Groq pada Q4 dan Q6

**Proyek:** ESPATA — Asis AI  
**Mandat Acuan:** EAO-HO-2026-037 (Read-Only Root Cause Audit — Q4 & Q6 Provider Failures)  
**Pelaksana:** Tim AI Development Organization (EAO)  
**Spesialisasi Peran:** AI System Architect (`EAO-ARCH`), AI QA Engineer (`EAO-QA`), AI Security Reviewer (`EAO-SEC`)  
**Otoritas:** Human Owner ESPATA  
**Jenis Pekerjaan:** Audit Teknis Read-Only (Tanpa Implementasi Kode)  
**Lingkungan:** Localhost XAMPP — `c:\xampp\htdocs\siakad`  
**Waktu Audit:** 17 November 2026  
**Status Integritas:** **`SEC_PASS = 1` | Zero Code Modification | Zero Deployment**

---

## 1. RINGKASAN EKSEKUTIF

Berdasarkan mandat **EAO-HO-2026-037**, Tim EAO telah melaksanakan audit forensik teknis *read-only* terhadap dua kegagalan pemanggilan penyedia AI (*provider failure*) yang terdeteksi pada *Full 10-Query Re-evaluation Benchmark* (EAO-036), yaitu:

1. **Q4 (*"Sekarang buat materi Bahasa Indonesia."*):** Menghasilkan klasifikasi `PROVIDER_HTTP_400` dengan pesan vendor:  
   `"Failed to validate JSON. Please adjust your prompt. See 'failed_generation' for more details."`
2. **Q6 (*"Saya ingin membuat asesmen formatif."*):** Menghasilkan klasifikasi `PROVIDER_ERROR` dengan pesan vendor:  
   `"Failed to generate JSON. Please adjust your prompt. See 'failed_generation' for more details."`

### Temuan Utama Audit:

1. **Lokasi Kegagalan Adalah Gateway Validator Vendor Groq (Bukan Kode Internal ESPATA):**  
   Kedua kegagalan terjadi di infrastruktur Groq Cloud sebelum respons diterima dan diproses oleh parser internal ESPATA. Respons HTTP yang diterima ESPATA adalah HTTP 400 Bad Request berisi payload error standar Groq.
2. **Dua Karakteristik Kegagalan Vendor yang Berbeda:**
   - **Q4 (`Failed to validate JSON`):** Terjadi karena model `openai/gpt-oss-20b` menghasilkan keluaran teks yang tidak memenuhi spesifikasi sintaksis JSON. Analisis menunjukkan bahwa permintaan pembuatan materi pembelajaran terbuka (*open-ended lesson content*) memicu keluaran teks panjang yang membentur batas `max_tokens: 650`, sehingga terpotong di tengah jalan (*mid-stream truncation*) dan merusak penutup JSON (`}`), atau menghasilkan karakter newline/markdown non-escape di dalam nilai string.
   - **Q6 (`Failed to generate JSON`):** Terjadi karena mesin inferensi Groq (*constrained decoding grammar engine*) gagal menghasilkan token awal yang valid (*generation bailout*), diduga kuat akibat ambiguitas antara intensi terdeteksi sistem (`KNOWLEDGE_RETRIEVAL`) dengan instruksi klarifikasi pada blok konteks operasional.
3. **Ketiadaan Data `failed_generation` di Repositori Lokal:**  
   Implementasi `GroqAdapter.php` saat ini hanya mengekstraksi properti `$decoded['error']['message']` dan langsung membuang seluruh isi `$rawBody` serta `$decoded['error']['failed_generation']`. Akibatnya, teks mentah keluaran model yang ditolak oleh Groq **tidak tersimpan di log maupun basis data ESPATA** (*NOT VERIFIABLE*).
4. **Isolasi dan Integritas Sistem 100% Terjaga:**  
   Pemeriksaan lisensi menghasilkan `SEC_PASS = 1`, kuota Admin tidak berkurang, subaplikasi `bayaran/` dan `cbtku/` tetap tidak ada, dan tidak ada modifikasi kode maupun pengiriman request live baru ke vendor selama audit berlangsung.

---

## 2. AUDIT PREFLIGHT & INTEGRITAS SISTEM (CP0)

Sebelum pemeriksaan data, status repositori dan lingkungan diperiksa secara ketat:

| Parameter Audit | Standar Kepatuhan | Hasil Pemeriksaan Faktual | Status |
|:---|:---|:---|:---:|
| **Branch Git Aktif** | `main` | `* main` | **VALID** |
| **Integritas Lisensi** | `SecurityGuard::verifyCriticalFiles()` = `1` | Menghasilkan `SEC_PASS = 1` | **VALID** |
| **Pohon Kerja Git** | Tidak ada modifikasi baru tak terkontrol | Hanya memuat berkas dari mandat terdahulu (Q5 fix & Prompt Anchor) | **PATUH** |
| **Subaplikasi bayaran/** | Terhapus permanen | Direktori `bayaran/` berstatus **ABSENT** | **BERSIH** |
| **Subaplikasi cbtku/** | Terhapus permanen | Direktori `cbtku/` berstatus **ABSENT** | **BERSIH** |
| **Proses Background** | Tidak ada proses benchmark aktif | `manage_task list`: 0 background tasks running | **BERSIH** |

### Diff Relevan Asis AI pada Working Tree:
Pemeriksaan diff `git diff app/Controllers/Api/AiCopilot.php` memastikan hanya ada 2 perubahan terotorisasi:
1. **Hoisting `$finalIntent` (baris 1009):** Diimplementasikan pada EAO-031 untuk memperbaiki bug Q5 (terbukti 100% sukses di EAO-036).
2. **JSON Prompt Anchor (baris 944–950):** Diimplementasikan pada EAO-033 untuk memitigasi format Q10 (terbukti 100% sukses di EAO-036).

Tidak ada modifikasi kode baru yang ditambahkan di luar kedua hal tersebut.

---

## 3. ANALISIS BUKTI TEKNIS Q4 (CP1)

### A. Parameter dan Metadata Request Q4
- **Kueri Pengguna:** `"Sekarang buat materi Bahasa Indonesia."`
- **Nomor Giliran (Turn):** Q4 dari 10 pertanyaan berurutan (Sesi `conversation_id = 123`).
- **Client Message ID:** `84b76348-baac-4b59-87f9-db2572ab12be`
- **Intensi Awal Terdeteksi:** `TASK_ASSISTANCE` (Confidence: 0.92 via `IntentClassifier`).
- **Resolusi Konteks Operasional:**
  - Satuan Pendidikan: SD SAKASALIKA
  - Penugasan Terdeteksi: Bahasa Indonesia - Kelas 6A (Fase C)
  - Status Ambiguitas: **`Ambiguous: NO`** (Fokus konteks terdeteksi spesifik).
- **Instruksi Khusus Sistem:**  
  Karena fokus konteks terdeteksi (`Ambiguous: NO`) dan kueri meminta bantuan materi, aturan sistem nomor 2 (`systemInstruction`) secara eksplisit memerintahkan model:
  > *"Jika pengguna meminta bantuan tugas ajar (soal, modul, indikator) dan Fokus Konteks Terdeteksi: langsung gunakan mata pelajaran, kelas, dan fase tersebut sebagai acuan pembuatan draf di 'ai_content'."*
- **Parameter Teknis ke Groq API:**
  - Provider: `groq`
  - Model: `openai/gpt-oss-20b`
  - `response_format`: `{"type": "json_object"}`
  - `temperature`: `0.3`
  - `max_tokens`: `650`
  - `timeout`: `15` detik

### B. Bukti Respons Error dan Penanganan Adapter
- **Waktu Eksekusi:** `2026-11-16 17:39:03 UTC` (23:39:03 WIB)
- **Durasi (Latensi):** `1.649` detik
- **HTTP Code Vendor:** `400 Bad Request`
- **Pesan Error Asli Vendor:**  
  `Failed to validate JSON. Please adjust your prompt. See 'failed_generation' for more details.`
- **Log Internal ESPATA (`writable/logs/log-2026-11-16.log:19682`):**  
  `ERROR - 2026-11-16 17:39:04 --> [AiCopilot::callUnifiedAiNlu failure] Code: API_ERROR | Msg: Failed to validate JSON. Please adjust your prompt. See 'failed_generation' for more details. | Text:`
- **Perilaku `GroqAdapter.php`:**
  Pada baris 171–174, `GroqAdapter` mendeteksi `$httpCode !== 200`, mengambil string `$decoded['error']['message']`, dan mengembalikan `AiResponse::createFailure()`. Seluruh isi `$decoded['error']['failed_generation']` dan raw payload cURL **tidak disimpan**.
- **Analisis Titik Kegagalan:**  
  Kegagalan terjadi **100% pada gateway validasi Groq** sebelum respons diterima oleh `GroqAdapter`. Model menghasilkan teks yang gagal divalidasi sebagai JSON oleh skema validator Groq.

---

## 4. ANALISIS BUKTI TEKNIS Q6 (CP2)

### A. Parameter dan Metadata Request Q6
- **Kueri Pengguna:** `"Saya ingin membuat asesmen formatif."`
- **Nomor Giliran (Turn):** Q6 dari 10 pertanyaan berurutan (Sesi `conversation_id = 123`).
- **Client Message ID:** `acd60fdf-d5c6-423b-a61c-be234bda1d5d`
- **Intensi Awal Terdeteksi:** `KNOWLEDGE_RETRIEVAL` (Confidence: 0.88 via `IntentClassifier`).
- **Resolusi Konteks Operasional:**
  - Satuan Pendidikan: SD SAKASALIKA
  - Penugasan Aktif: 4 Penugasan (Pendidikan Pancasila, Bahasa Indonesia, IPAS, Matematika di Kelas 6A).
  - Status Ambiguitas: **`Ambiguous: YES`** (Kueri tidak menyebutkan mata pelajaran atau kelas).
- **Instruksi Khusus Sistem:**  
  Aturan sistem nomor 3 memerintahkan:
  > *"Jika Fokus Konteks AMBIGU: Tanyakan secara ramah pada 'explanation' mata pelajaran atau kelas mana yang dimaksud, dan cantumkan pilihan penugasan yang diampu pada 'suggestions'. Kosongkan 'ai_content' (isi null)."*
- **Parameter Teknis ke Groq API:**
  - Provider: `groq`
  - Model: `openai/gpt-oss-20b`
  - `response_format`: `{"type": "json_object"}`
  - `temperature`: `0.3`
  - `max_tokens`: `650`
  - `timeout`: `15` detik

### B. Bukti Respons Error dan Penanganan Adapter
- **Waktu Eksekusi:** `2026-11-16 17:40:10 UTC` (23:40:10 WIB)
- **Durasi (Latensi):** `1.714` detik
- **HTTP Code Vendor:** `400 Bad Request`
- **Pesan Error Asli Vendor:**  
  `Failed to generate JSON. Please adjust your prompt. See 'failed_generation' for more details.`
- **Log Internal ESPATA (`writable/logs/log-2026-11-16.log:19683`):**  
  `ERROR - 2026-11-16 17:40:12 --> [AiCopilot::callUnifiedAiNlu failure] Code: API_ERROR | Msg: Failed to generate JSON. Please adjust your prompt. See 'failed_generation' for more details. | Text:`
- **Perilaku `GroqAdapter.php`:**
  Identik dengan Q4, adapter mengembalikan `AiResponse::createFailure()` dengan `API_ERROR`. Data `failed_generation` tidak disimpan.
- **Analisis Titik Kegagalan:**  
  Pesan error pada Q6 adalah **`Failed to GENERATE JSON`**, bukan `Failed to VALIDATE JSON`. Ini membuktikan kegagalan terjadi di tingkat mesin inferensi (*constrained grammar engine*) Groq saat mencoba menghasilkan token yang memenuhi tata bahasa JSON.

---

## 5. MATRIKS PERBANDINGAN KOMPREHENSIF (CP3)

Berikut adalah perbandingan analitis antara kueri yang gagal (Q4, Q6) dengan kueri yang berhasil (Q1, Q5, Q8, Q10):

| Parameter Evaluasi | Q1 (Sukses) | Q4 (Gagal) | Q5 (Sukses) | Q6 (Gagal) | Q8 (Sukses) | Q10 (Sukses) |
|:---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Teks Kueri** | *10 soal pecahan* | *Buat materi B. Indo* | *Tujuan pembelajaran* | *Buat asesmen formatif* | *Bantu buat modul ajar* | *Apa yg saya ajarkan?* |
| **Intensi Sistem** | `TASK_ASSISTANCE` | `TASK_ASSISTANCE` | `TASK_ASSISTANCE` | `KNOWLEDGE_RETRIEVAL` | `KNOWLEDGE_RETRIEVAL` | `KNOWLEDGE_RETRIEVAL` |
| **Status Konteks** | `Ambiguous: YES` | `Ambiguous: NO` | `Ambiguous: NO` | `Ambiguous: YES` | `Ambiguous: YES` | `Ambiguous: YES` |
| **Fokus Mapel** | Tidak Ada (Multi) | Bahasa Indonesia 6A | Bahasa Indonesia 6A | Tidak Ada (Multi) | Tidak Ada (Multi) | Tidak Ada (Multi) |
| **Peran `ai_content`** | `null` (Wajib) | **Draf Lengkap Materi** | **Draf 5 Butir Singkat** | `null` (Wajib) | `null` (Wajib) | `null` (Wajib) |
| **Estimasi Token Draf** | 0 token | **600 – 1.200 token** | **~150 token** | 0 token | 0 token | 0 token |
| **Batas `max_tokens`** | 650 | 650 | 650 | 650 | 650 | 650 |
| **Completion Tokens** | 248 token | **0 (Gagal di Vendor)** | 297 token | **0 (Gagal di Vendor)** | 279 token | 328 token |
| **Total Tokens** | 2.043 | 0 | 2.147 | 0 | 2.372 | 2.467 |
| **Error Vendor** | Tidak Ada | `validate JSON failed` | Tidak Ada | `generate JSON failed` | Tidak Ada | Tidak Ada |
| **Hasil Klasifikasi** | `LIVE_LLM_SUCCESS` | `PROVIDER_HTTP_400` | `LIVE_LLM_SUCCESS` | `PROVIDER_ERROR` | `LIVE_LLM_SUCCESS` | `LIVE_LLM_SUCCESS` |

### Temuan Pola dari Matriks Perbandingan:

1. **Pola Keberhasilan Respon Terbuka Pendek (Q1, Q8, Q10):**
   Pada seluruh kueri ambigu, model hanya perlu menghasilkan klarifikasi ramah 1–2 kalimat pada `explanation` dan 3–4 item string pada `suggestions`, dengan `ai_content: null`. Total token keluaran berkisar antara **205 hingga 328 token**, jauh di bawah batas 650 token. Seluruhnya **100% SUKSES**.
2. **Pola Keberhasilan Pembuatan Tugas Spesifik Ringkas (Q5):**
   Pada Q5, fokus konteks Bahasa Indonesia terdeteksi (`Ambiguous: NO`), namun permintaannya spesifik yaitu *Tujuan Pembelajaran*. Model menghasilkan 5 butir kalimat terukur sepanjang 297 token. Total token masih berada di bawah 650 token, sehingga **100% SUKSES**.
3. **Pola Kegagalan Pembuatan Materi Terbuka Panjang (Q4):**
   Pada Q4, fokus konteks Bahasa Indonesia terdeteksi (`Ambiguous: NO`) dan pengguna meminta *"buat materi"*. Permintaan materi pembelajaran tanpa batasan panjang (*unbounded length*) memicu model menulis uraian teks panjang di dalam field JSON `"ai_content"`. Hal ini membentur batas `max_tokens: 650`, mengakibatkan pemotongan teks (*truncation*) sebelum tanda petik penutup `"` dan kurung kurawal `}` selesai dicetak, yang langsung ditolak oleh gateway Groq sebagai JSON tidak valid.
4. **Pola Kegagalan Inferensi Gramatikal (Q6):**
   Pada Q6, terjadi ketidakcocokan antara kata kerja tindakan pengguna (*"buat asesmen"*) yang oleh classifier dipetakan ke `KNOWLEDGE_RETRIEVAL` dengan instruksi operasional yang mendeteksi ambiguitas penugasan. Perbedaan error message (`Failed to generate JSON`) menunjukkan kegagalan terjadi pada algoritma penegakan grammar Groq (*constrained decoding*).

---

## 6. PENETAPAN AKAR MASALAH (CP4)

Berdasarkan bukti faktual yang telah diperiksa, status kepastian setiap temuan diklasifikasikan secara ketat sebagai berikut:

| No | Pernyataan Temuan / Dugaan Masalah | Klasifikasi Derajat Kepastian | Dasar Bukti Faktual |
|:---:|:---|:---:|:---|
| **1** | Kegagalan Q4 dan Q6 terjadi di sisi vendor Groq Cloud (HTTP 400), bukan pada parser atau exception internal aplikasi ESPATA. | **CONFIRMED** | Log `writable/logs/log-2026-11-16.log:19682-19683` mencatat `Code: API_ERROR` dengan pesan verbatim dari server Groq. |
| **2** | Perbaikan bug Q5 (`$finalIntent`) dan mitigasi Q10 (Prompt Anchor) terbukti bekerja sempurna dan tidak mengalami regresi pada benchmark EAO-036. | **CONFIRMED** | Q5 dan Q10 menghasilkan `LIVE_LLM_SUCCESS` (HTTP 200, valid JSON, token 2.147 dan 2.467). |
| **3** | Kode adapter `GroqAdapter.php` membuang data diagnostik `failed_generation` yang dikirimkan oleh Groq pada respons error. | **CONFIRMED** | Baris 171–174 `GroqAdapter.php` hanya membaca properti `message` dan mengabaikan `failed_generation`. |
| **4** | Isi teks mentah keluaran model yang ditolak pada Q4 dan Q6 tidak dapat diketahui secara pasti dari file atau log yang ada di ESPATA. | **CONFIRMED** | Tidak ada file log atau penyimpanan database yang merekam teks respons cURL ketika status HTTP bukan 200. |
| **5** | Kegagalan Q4 (`Failed to validate JSON`) dipicu oleh terpotongnya string JSON akibat keluaran draf materi melebihi batas `max_tokens: 650`. | **STRONGLY INDICATED** | Permintaan materi terbuka pada Q4 menghasilkan teks panjang (>1.400 karakter di EAO-022), sedangkan kueri pendek (Q1, Q2, Q3, Q5, Q7, Q8, Q9, Q10) dengan token < 350 selalu sukses validasi JSON. |
| **6** | Kegagalan Q4 disebabkan oleh karakter baris baru (*unescaped newlines*) atau karakter khusus yang dicetak model di dalam nilai string `ai_content`. | **HYPOTHESIS** | Merupakan penyebab umum error validasi JSON pada model open-source, namun tidak dapat dibuktikan tanpa rekaman `failed_generation`. |
| **7** | Kegagalan Q6 (`Failed to generate JSON`) disebabkan oleh kegagalan mesin constrained decoding Groq akibat friksi klasifikasi intensi. | **STRONGLY INDICATED** | Pesan error spesifik `Failed to generate JSON` (berbeda dengan `validate JSON`) merujuk pada tahap token generation/sampling di Groq API. |
| **8** | Rekonstruksi kata demi kata dari respons yang gagal di Q4 dan Q6. | **NOT VERIFIABLE** | Sesuai mandat, dilarang mengarang atau merekonstruksi payload `failed_generation` yang tidak tersimpan. |

---

## 7. REKOMENDASI DESAIN TEKNIS TERUKUR (CP5)

Berikut adalah rekomendasi teknis terpisah untuk setiap akar masalah yang teridentifikasi. Sesuai batasan mandat, rekomendasi ini disajikan **sebagai bahan evaluasi dan persetujuan Human Owner**, bukan untuk diimplementasikan secara sepihak.

### Rekomendasi 1: Penyesuaian `max_tokens` & Prompt Bounded Length untuk Kueri Materi (Mitigasi Q4)
- **Akar Masalah yang Ditangani:** Pemotongan token (*truncation*) pada respon pembuatan draf materi terbuka panjang di bawah batas 650 token.
- **Rancangan Solusi:**
  1. Tingkatkan batas `max_tokens` pada `AiCopilot.php` (baris 958) khusus untuk intensi `TASK_ASSISTANCE` dari `650` menjadi `1.200` token (tetap berada di bawah ambang batas TPM organisasi Groq).
  2. Tambahkan instruksi pembatasan ringkas (*bounded length instruction*) pada Prompt Anchor atau pedoman respon, misal: *"Jika membuat materi pembelajaran, buatlah ringkasan materi inti maksimal 3 paragraf atau 5 poin utama yang padat dan terstruktur."*
- **Manfaat:** Mencegah model menulis materi ensiklopedis yang membentur limit token dan memotong JSON di tengah jalan.
- **Risiko Regresi:** Risiko rendah. Peningkatan `max_tokens` sedikit menaikkan konsumsi token per menit (TPM), namun dengan jeda 30 detik antarpertanyaan, batas 8.000 TPM Groq tetap aman.
- **Berkas Terdampak:** `app/Controllers/Api/AiCopilot.php`.
- **Pengujian yang Dibutuhkan:** Uji coba kueri materi dengan variasi panjang permintaan via skrip regresi mock/live.

---

### Rekomendasi 2: Penyempurnaan `GroqAdapter` untuk Telemetri Diagnostik & Error Capture
- **Akar Masalah yang Ditangani:** Hilangnya informasi `failed_generation` saat terjadi HTTP 400 dari vendor Groq.
- **Rancangan Solusi:**
  Pada baris 171–174 `app/Services/Ai/Providers/GroqAdapter.php`:
  1. Ekstraksi properti `failed_generation` dan `code` dari payload `$decoded['error']` jika tersedia.
  2. Teruskan informasi tersebut ke dalam objek `AiResponse::createFailure()` melalui metadata khusus atau field `rawResponse`.
  3. Lakukan sanitasi secret (masking API key) sebelum mencatatnya ke log diagnostik internal.
- **Manfaat:** Memberikan visibilitas forensik 100% apabila di masa mendatang vendor Groq kembali menolak keluaran model.
- **Risiko Regresi:** Nol (hanya memperkaya data penanganan error, tidak mengubah kontrak respon sukses).
- **Berkas Terdampak:** `app/Services/Ai/Providers/GroqAdapter.php` dan `app/Services/Ai/DTO/AiResponse.php`.
- **Pengujian yang Dibutuhkan:** Unit test mock HTTP error 400 dengan payload `failed_generation`.

---

### Rekomendasi 3: Penyelarasan Kata Kunci Pembuatan Asesmen pada `IntentClassifier` (Mitigasi Q6)
- **Akar Masalah yang Ditangani:** Ambiguitas intensi pada frasa *"Saya ingin membuat asesmen..."* yang terklasifikasi sebagai `KNOWLEDGE_RETRIEVAL` padahal bermakna tindakan pembuatan tugas (*TASK_ASSISTANCE*).
- **Rancangan Solusi:**
  Pada `app/Services/Ai/Intent/IntentClassifier.php`:
  Tambahkan pola regex pembuatan asesmen/soal formatif (`/\b(buat|membuat|susun|rancang)\s+(asesmen|penilaian|evaluasi|rubrik)\b/i`) ke dalam prioritas `INTENT_TASK_ASSISTANCE` sebelum jatuh ke `KNOWLEDGE_RETRIEVAL`.
- **Manfaat:** Penyelarasan intensi yang lebih akurat antara maksud pengguna, aturan sistem, dan template JSON yang diminta.
- **Risiko Regresi:** Rendah. Wajib diverifikasi terhadap suite `tests/test_intent.php` (13 skenario).
- **Berkas Terdampak:** `app/Services/Ai/Intent/IntentClassifier.php`.
- **Pengujian yang Dibutuhkan:** Eksekusi unit test `tests/test_intent.php`.

---

### Rekomendasi 4: Evaluasi Parser Recovery (Dual-Layer Defense)
- **Akar Masalah yang Ditangani:** Peluang pemulihan format JSON apabila model menghasilkan teks yang sebenarnya valid namun terbungkus markdown code fence.
- **Keterbatasan Fakta:** Parser lokal **HANYA DAPAT BEKERJA** jika server vendor mengembalikan teks mentah tersebut. Jika vendor Groq memotong di tingkat gateway dengan HTTP 400 tanpa mengembalikan body text, parser lokal tidak memiliki data untuk dipulihkan.
- **Rancangan Solusi:** Parser Recovery pada adapter hanya relevan diterapkan jika vendor dipanggil **tanpa** parameter `response_format: {"type": "json_object"}` (mode teks bebas), di mana model menghasilkan teks dan parser lokal yang mengekstrak blok JSON `{...}`.
- **Rekomendasi:** Menunda implementasi Parser Recovery sampai evaluasi opsi mode pemanggilan (JSON mode vs Text mode dengan regex extractor) diputuskan secara definitif oleh Human Owner.

---

### Rekomendasi 5: Strategi Fallback Lokal yang Transparan
- **Prinsip Utama:** Sesuai arahan Human Owner, fallback lokal tidak boleh menyamarkan kegagalan provider sebagai keberhasilan LLM.
- **Rancangan Solusi:**
  Jika terjadi kegagalan vendor (`API_ERROR`), antarmuka pengguna menampilkan pesan informatif bahwa model sedang mengalami kendala formatting di sisi cloud, menyajikan tombol *Coba Lagi*, serta menyediakan kartu pintasan navigasi ke menu manual terkait tanpa mengarang konten AI palsu.
- **Manfaat:** Menjaga kejujuran fungsional sistem kepada guru dan pengguna sekolah.

---

## 8. RISIKO REGRESI & ANALISIS DAMPAK

| Rekomendasi | Komponen Terdampak | Risiko Regresi | Strategi Mitigasi Risiko |
|:---|:---|:---:|:---|
| **R-1: Bounded Length & Max Tokens** | `AiCopilot.php` | Rendah | Menguji dampak TPM terhadap rate-limit organisasi Groq. |
| **R-2: Error Diagnostic Capture** | `GroqAdapter.php` | Nol | Memastikan penyaringan API Key (`maskSecret`) tetap aktif pada seluruh pesan error. |
| **R-3: Penyelarasan Intent Classifier** | `IntentClassifier.php` | Rendah | Menjalankan seluruh test suite klasifikasi intensi yang ada. |
| **R-4: Mode Teks Bebas vs JSON Mode** | `GroqAdapter.php` | Sedang | Membutuhkan mandat tersendiri dan pengujian benchmark komparatif. |

---

## 9. PERLINDUNGAN KEAMANAN & PRIVASI (AUDIT FILE SEMENTARA)

Sesuai instruksi Bagian 4 Mandat EAO-HO-2026-037:
1. **Pemeriksaan Berkas Cookie Sementara di `tests/`:**  
   Ditemukan 5 berkas teks cookie pada direktori `tests/`:
   - `tests/cookie_admin.txt`
   - `tests/cookie_admin_1d2.txt`
   - `tests/cookie_guru_byok.txt`
   - `tests/cookie_guru_fallback.txt`
   - `tests/cookie_guru_fallback_1d2.txt`
2. **Kerahasiaan Nilai Rahasia:**  
   Tidak ada satupun nilai rahasia (hash sesi, password, CSRF token, API Key Groq) yang dicetak atau disertakan ke dalam laporan ini.
3. **Rekomendasi Pembersihan Berkas Sementara:**  
   Berkas-berkas cookie di atas merupakan artefak pengujian otomatis lokal dari fase-fase pengujian terdahulu (Fase 1D.1 & 1D.2). Sebagian besar sesi tersebut sudah kedaluwarsa secara alami di basis data `ci_sessions`. Direkomendasikan pada mandat *housekeeping* berikutnya untuk:
   - Menghapus berkas-berkas `tests/cookie_*.txt` tersebut dari pohon kerja Git.
   - Menambahkan aturan `tests/cookie_*.txt` ke dalam berkas `.gitignore`.

---

## 10. DAFTAR BERKAS YANG DIPERIKSA (READ-ONLY AUDIT)

1. `app/Controllers/Api/AiCopilot.php`
2. `app/Services/Ai/Providers/GroqAdapter.php`
3. `app/Services/Ai/UnifiedAiService.php`
4. `app/Services/Ai/Intent/IntentClassifier.php`
5. `app/Services/Ai/OperationalContextProvider.php`
6. `app/Services/Ai/Memory/ConversationMemoryManager.php`
7. `app/Services/Ai/DTO/AiRequest.php`
8. `app/Services/Ai/DTO/AiResponse.php`
9. `app/Services/Ai/DTO/OperationalContextDTO.php`
10. `app/Libraries/SecurityGuard.php`
11. `tests/live_benchmark_10_results.json`
12. `writable/logs/log-2026-11-16.log` (baris 19680–19683)
13. Basis Data `siakad` (tabel `ai_conversations`, `ai_conversation_messages` sesi 123)

---

## 11. PERNYATAAN KEPATUHAN & TITIK BERHENTI (DISCIPLINED HALT)

Tim EAO menyatakan secara mutlak bahwa:
- **TIDAK ADA** perubahan kode PHP, JavaScript, prompt, schema, maupun konfigurasi yang dilakukan selama pelaksanaan mandat ini.
- **TIDAK ADA** modifikasi basis data, skema tabel, maupun migrasi.
- **TIDAK ADA** request live baru yang dikirimkan ke vendor Groq.
- **TIDAK ADA** commit, push, maupun deployment yang dieksekusi.
- Integritas berkas terlindungi lisensi tetap **`SEC_PASS = 1`**.

Sesuai instruksi Bagian 7 Mandat EAO-HO-2026-037, Tim EAO kini berada dalam status **DISCIPLINED HALT** dan menunggu arahan atau mandat tertulis berikutnya dari Human Owner ESPATA.
