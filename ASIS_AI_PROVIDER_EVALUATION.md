# ASIS AI PROVIDER EVALUATION
**Evaluasi Komparatif & Kerangka Pengujian Model Penyedia AI untuk ESPATA**
*Dokumen Analisis Mandat EAO-002B — Disempurnakan oleh AI Product Analyst & AI System Architect*

---

| Parameter | Keterangan Dokumen |
|:---|:---|
| **Penyedia yang Dievaluasi** | **Groq Cloud API** & **Google Gemini API** |
| **Kandidat Penyedia Utama** | **Groq Cloud API** (Kandidat Utama untuk Chat Cepat & Latensi Rendah) |
| **Kandidat Penyedia Cadangan** | **Google Gemini API** (Kandidat Cadangan untuk Dokumen Panjang & Konteks Luas) |
| **Status Penetapan Model** | **Kandidat Awal untuk Pengujian Empiris (Bukan Model Permanen)** |
| **Status Auto-Failover** | **NONAKTIF MUTLAK (Manual/Graceful Degradation Only)** |
| **Lingkungan Sasaran** | Shared Hosting Indonesia (Apache/LiteSpeed, PHP 8.1+, Resolusi IPv4) |

---

## 1. Latar Belakang & Prinsip Pemilihan Model

Pengalaman pengguna Asis AI ditentukan oleh keseimbangan antara **kecepatan respon (*latency*)**, **ketepatan penalaran kurikulum (*pedagogical reasoning*)**, dan **resiliensi jaringan hosting murah**.
Berdasarkan fakta repositori, integrasi AI di ESPATA saat ini tersebar:
- `AiCopilot.php`: memanggil langsung `gemini-1.5-flash`.
- `Kokurikuler.php`: memanggil Groq (`openai/gpt-oss-20b`, `qwen/qwen3.8-27b`) dan Gemini (`gemini-2.0-flash`, `gemini-1.5-flash`).
- `ElearningPackageGeneratorService.php`: memanggil resolver model Groq dan Gemini.

Sesuai instruksi Mandat EAO-002A, **sistem dilarang mengunci model secara permanen sebelum dilakukan pengujian empiris terukur**. Seluruh model yang tercantum pada dokumen ini berstatus sebagai **Kandidat Rekomendasi Awal** yang wajib melalui matriks pengujian sebelum ditetapkan sebagai default produksi.

---

## 2. Matriks Komparatif Provider: Groq vs Google Gemini

| Dimensi Evaluasi | Groq Cloud API | Google Gemini API | Catatan Analisis Arsitektural |
|:---|:---|:---|:---|
| **1. Latensi & Waktu Respon** | **Sangat Cepat (0,8 – 1,8 detik)**<br>Didukung arsitektur chip LPU (Language Processing Unit). | **Sedang (2,0 – 5,5 detik)**<br>Tergantung beban server Google dan routing internasional. | Groq menjadi kandidat terbaik untuk antarmuka chat Command Palette. |
| **2. Standar Protokol API** | **Format Standar OpenAI**<br>`/v1/chat/completions`<br>Header: `Authorization: Bearer <key>`. | **Format REST Spesifik Google**<br>`/v1beta/models/{model}:generateContent`<br>Query parameter: `?key=<key>`. | Format OpenAI pada Groq mempermudah integrasi model lokal/open-source lainnya. |
| **3. Kehandalan Format JSON** | **Tinggi**<br>Mendukung `response_format: {"type": "json_object"}` pada model modern. | **Sangat Tinggi**<br>Mendukung `response_mime_type: "application/json"` bawaan Google. | Keduanya mampu menghasilkan JSON valid untuk pembuatan kartu antarmuka. |
| **4. Kualitas Bahasa Indonesia** | **Baik hingga Sangat Baik** pada varian model Qwen dan GPT-OSS; Cukup pada Llama 3.1 8B. | **Sangat Luwes & Alami**.<br>Memahami nuansa budaya akademik Indonesia dan istilah Kurikulum Merdeka. | Gemini unggul dalam menyusun narasi deskripsi rapor dan sapaan empatik. |
| **5. Batas Penggunaan (Rate Limits)** | **Free Tier Tersedia**<br>30 RPM, ribuan TPM tergantung model.<br>Batas per hari (RPD) stabil. | **Free Tier Tersedia**<br>15 RPM, 1.000.000 TPM.<br>Sensitif terhadap pembatasan IP bersama di shared hosting. | Keduanya saling melengkapi jika dikelola melalui sistem konfigurasi yang fleksibel. |
| **6. Jendela Konteks (Context)** | 8.192 – 131.072 token (tergantung model). | Hingga 1.000.000 token (`gemini-1.5-flash` / `gemini-2.0-flash`). | Gemini unggul jika harus memproses silabus tahunan atau buku materi tebal. |
| **7. Resiliensi Shared Hosting** | **Sangat Tangguh** bila dipaksa menggunakan IPv4 (`CURL_IPRESOLVE_V4`). | **Baik**, namun sesekali rentan terhadap penundaan handshake TLS di provider hosting tertentu. | Keduanya wajib diproteksi dengan timeout koneksi 5 detik dan paksa IPv4. |

---

## 3. Daftar Kandidat Model Awal untuk Diuji

*(Seluruh nama model dapat diubah kapan saja melalui file konfigurasi `app/Config/AiConfig.php` tanpa perlu mengubah kode controller)*

### A. Kandidat Model Groq Cloud (Penyedia Utama)

| Nama Model Kandidat | Status Repositori | Keunggulan Spesifik | Kasus Pengujian Utama |
|:---|:---|:---|:---|
| **`openai/gpt-oss-20b`** | Sudah aktif di `Kokurikuler.php` | Keseimbangan terbaik antara latensi (~1,1 detik), kepatuhan format JSON, dan pemahaman Bahasa Indonesia. | Kandidat #1 untuk default Asis AI Copilot. |
| **`qwen/qwen3.8-27b`** / **`qwen-2.5-32b`** | Sudah aktif di `Kokurikuler.php` | Penalaran kontekstual tinggi, sangat kuat dalam pemetaan silabus kurikulum dan tujuan pembelajaran. | Kandidat #1 untuk modul perencanaan Kokurikuler & Modul Ajar. |
| **`llama-3.1-8b-instant`** | Teruji di ekosistem Groq | Latensi ultra-rendah (< 0,8 detik), hemat token, sangat ringan. | Kandidat untuk klasifikasi intent cepat dan ekstraksi kata kunci navigasi. |

### B. Kandidat Model Google Gemini (Penyedia Cadangan)

| Nama Model Kandidat | Status Repositori | Keunggulan Spesifik | Kasus Pengujian Utama |
|:---|:---|:---|:---|
| **`gemini-2.0-flash`** | Sudah aktif di `Kokurikuler.php` | Kecepatan 2x lipat dibanding Gemini 1.5, penalaran instruksi ketat, bahasa alami sangat halus. | Kandidat #1 untuk cadangan utama Asis AI Copilot. |
| **`gemini-1.5-flash`** | Aktif eksisting di `AiCopilot.php` | Sangat stabil, kompatibilitas REST API teruji di lingkungan produksi sekolah. | Kandidat fallback stabil jika terjadi kendala pada model baru. |
| **`gemini-1.5-pro`** | Terdaftar di SDK Gemini | Jendela konteks raksasa, penalaran dokumen multi-halaman. | Khusus analisis dokumen silabus/buku ajar PDF besar. |

---

## 4. Matriks Kerangka Pengujian Empiris (Benchmarking Protocol)

Sebelum satu model ditetapkan sebagai default permanen, tim AI QA Engineer dan AI Product Analyst akan menjalankan uji benchmarking pada lingkungan localhost development:

```
┌────────────────────────────────────────────────────────────────────────┐
│               MATRIKS PENGUJIAN MODEL ASIS AI (SKALA 1 - 5)           │
├───────────────────┬────────┬────────┬───────┬────────┬────────┬────────┤
│ Kriteria Uji      │ Target │ Bobot  │ Llama │ GPT-OSS│ Qwen   │ Gemini │
│                   │        │        │ 3.1 8B│ 20B    │ 3.8 27B│ 2.0 Fl │
├───────────────────┼────────┼────────┼───────┼────────┼────────┼────────┤
│ 1. Bhs. Indonesia │ $\ge 4$│ 20%    │ [Uji] │ [Uji]  │ [Uji]  │ [Uji]  │
│ 2. Nalar Kurikulum│ $\ge 4$│ 25%    │ [Uji] │ [Uji]  │ [Uji]  │ [Uji]  │
│ 3. Kepatuhan JSON │ = 5    │ 20%    │ [Uji] │ [Uji]  │ [Uji]  │ [Uji]  │
│ 4. Latensi (<2s)  │ $\ge 4$│ 15%    │ [Uji] │ [Uji]  │ [Uji]  │ [Uji]  │
│ 5. Stabilitas cURL│ = 5    │ 10%    │ [Uji] │ [Uji]  │ [Uji]  │ [Uji]  │
│ 6. Konsumsi Token │ $\ge 3$│ 10%    │ [Uji] │ [Uji]  │ [Uji]  │ [Uji]  │
├───────────────────┴────────┴────────┴───────┴────────┴────────┴────────┤
│ HASIL AKHIR & REKOMENDASI DEFINITIF : DITENTUKAN PASCA-PENGUJIAN FASE 2│
└────────────────────────────────────────────────────────────────────────┘
```

### Rincian Skenario Uji:
1. **Skenario 1 (Bahasa Indonesia & Kesantunan Akademik):**
   - Prompt: *"Bagaimana cara menginput nilai sumatif akhir semester?"*
   - Syarat Lolos: Respon menyapa dengan santun, menggunakan istilah resmi ESPATA, dan tanpa tata bahasa kaku terjemahan mesin.
2. **Skenario 2 (Penalaran Kurikulum Merdeka):**
   - Prompt: *"Buatkan 3 indikator ketercapaian tujuan pembelajaran untuk Fase D mapel Informatika."*
   - Syarat Lolos: Indikator selaras dengan tingkat kognitif Fase D (SMP), tidak melompat ke Fase E (SMA).
3. **Skenario 3 (Integritas Output JSON):**
   - Prompt meminta keluaran format kartu tindakan (`action_card`).
   - Syarat Lolos: Parsing `json_decode()` di PHP menghasilkan validitas 100% tanpa karakter escape rusak.

---

## 5. Kebijakan Auto-Failover: Status & Mitigasi Risiko

Sesuai instruksi tegas Mandat EAO-002A:
1. **Auto-Failover Diberlakukan NONAKTIF:**
   - Parameter `AiConfig::$enableAutoFailover = false`.
   - Mengapa? Perpindahan otomatis seketika berisiko memicu:
     - Biaya ganda (*duplicate billing/token cost*).
     - Balasan ganda yang membingungkan peramban jika koneksi pertama sebenarnya masih berjalan lambat.
     - Penutupan sesi yang menggantung (*hanging transaction*).
2. **Mekanisme Graceful Degradation (Penanganan Anggun):**
   - Jika Groq mengalami error jaringan atau rate limit, sistem menangani secara lokal:
     - Mengembalikan pesan santun: *"Asis AI sedang mengalami antrean di server komputasi. Anda dapat menggunakan panduan cepat offline pada tab sebelah kiri."*
     - Mencatat kode kesalahan ke `ai_assistant_logs` untuk evaluasi teknis.
   - Tidak ada pengalihan otomatis ke Gemini sebelum fitur failover diuji secara terisolasi dan disetujui Pemilik Proyek.

---

## 6. Pedoman Resiliensi Jaringan Shared Hosting Indonesia

1. **Paksa Resolusi IPv4:** Seluruh cURL wajib menyertakan `CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4`.
2. **Koneksi Agresif Cepat:** `CURLOPT_CONNECTTIMEOUT = 5` detik. Jika dalam 5 detik server target tidak merespons, koneksi diputus untuk melindungi kenyamanan pengguna.
3. **Pelepasan Kunci Sesi:** Wajib menjalankan `session_write_close()` sebelum cURL dieksekusi.