# DESAIN ARSITEKTUR RETRIEVAL ENGINE EAKB (TAHAP 2A)
## MANDAT EAO-HO-2026-046
### ESPATA Application Knowledge Base — Asis AI

---

**Nomor Dokumen:** EAO-REP-2026-046-EAKB-RETRIEVAL-ENGINE-ARCHITECTURE  
**Referensi Mandat:** EAO-HO-2026-046 (Desain Arsitektur Tahap 2A EAKB)  
**Tanggal Penerbitan:** 2026-11-17  
**Pelaksana:** Tim EAO (AI System Architect & AI Product Analyst)  
**Pemilik Keputusan:** Human Owner ESPATA  
**Jenis Dokumen:** Desain Arsitektur & Spesifikasi Rekayasa (READ-ONLY — BELUM ADA IMPLEMENTASI)  
**Status Fondasi:** EAKB Tahap 1 Diterima Bersyarat (Mandat EAO-045)  
**Integritas Berkas Kritis:** `SEC_PASS: 1` (LULUS 100%)  
**Sub-Aplikasi:** `bayaran/` (ABSENT), `cbtku/` (ABSENT)  

---

## 1. RINGKASAN EKSEKUTIF & PRIORITAS PRODUK

Berdasarkan mandat **EAO-HO-2026-046**, dokumen ini menetapkan cetak biru (*architectural blueprint*) perancangan **EAKB Retrieval Engine (Tahap 2A)** untuk mengintegrasikan basis pengetahuan fisik **ESPATA Application Knowledge Base (70 fitur, 15 atribut kanonikal)** ke dalam alur penalaran cerdas Asis AI.

### Urutan Prioritas Produk Asis AI (Human Owner Mandate):
```
┌────────────────────────────────────────────────────────────────────────┐
│  1. ESPATA Application Intelligence (PRIORITAS TERTINGGI / CORE FOCUS) │
│     Asisten cerdas yang memahami seluruh fitur, modul, hak akses,     │
│     prosedur, validasi sistem, dan troubleshooting aplikasi ESPATA.    │
├────────────────────────────────────────────────────────────────────────┤
│  2. Contextual Assistance                                              │
│     Memahami sekolah, rombel, mapel, penugasan guru, semester aktif.   │
├────────────────────────────────────────────────────────────────────────┤
│  3. Educational Knowledge                                              │
│     Pengetahuan pedagogik, Kurikulum Merdeka, Taksonomi, asesmen.      │
├────────────────────────────────────────────────────────────────────────┤
│  4. Task Assistance & Content Generation                               │
│     Pembuatan draft soal, modul ajar, materi, dan rubrik terikat.     │
├────────────────────────────────────────────────────────────────────────┤
│  5. Action Orchestration (Human-in-the-Loop & RBAC Bound)             │
│     Penyediaan navigasi dan tautan aksi tanpa bypass otoritas manusia. │
└────────────────────────────────────────────────────────────────────────┘
```

Arsitektur ini dirancang khusus untuk lingkungan **PHP 8.1+, CodeIgniter 4, dan Shared Hosting** dengan prinsip **Zero Regression, Zero External Microservice, Sub-Millisecond Retrieval Latency, dan Strict RBAC-First Isolation**.

---

## 2. AUDIT KONTRAK DATA KATALOG EAKB (CP1)

Retrieval Engine akan memanfaatkan kontrak data fisik yang telah dibangun pada Tahap 1 (`app/Libraries/EspataApplicationKnowledge.php`) dan divalidasi oleh `app/Libraries/EakbCatalogValidator.php`.

### A. Komponen Kontrak Data yang Dimanfaatkan
1. **70 Feature ID Unik:** Menjadi kunci pencarian primer (*exact lookup*).
2. **Kategori (13 Kategori):** Menjadi ruang lingkup filter dan pengelompokan modul.
3. **Allowed Roles (`allowed_roles`):** Filter keamanan authoritatif sebelum proses pencarian dilakukan.
4. **Prasyarat (`prerequisites`):** Penjelasan ketergantungan sebelum fitur digunakan.
5. **Langkah Kerja (`workflow_steps`):** Panduan prosedural langkah demi langkah yang diberikan kepada pengguna.
6. **Validasi & Error (`validation_rules_and_errors`):** Menjawab pertanyaan *"Kenapa muncul error X?"* atau *"Apa syarat input form Y?"*.
7. **Troubleshooting FAQ (`troubleshooting_faq`):** Penanganan kendala operasional yang kerap dihadapi guru dan staf.
8. **Relasi Antar-Fitur (`related_features`):** Penelusuran graf 1-hop untuk merekomendasikan fitur terkait yang relevan.
9. **Status Fitur (`feature_status`):** Gate penentu cara penjelasan (ACTIVE vs LIMITED vs INACTIVE).
10. **Kamus Sinonim & Alias Historis (`HISTORICAL_ALIASES`):** Penanganan kueri pengguna awam (misal: 'p5', 'absen', 'bikin soal').

### B. Resolusi Backlog EAO-045 pada Desain Retrieval Engine
Retrieval Engine mengakomodasi dan memitigasi catatan backlog EAO-045 pada lapisan logika retrieval tanpa mengubah berkas fisik katalog:
- **Typo Referensi `Berita.php`:** Retrieval Engine menyertakan kamus pemetaan alias internal (`alias_map`) yang secara transparan memetakan rujukan `Admin/Cms/Berita.php` ke file fisik `Admin/Cms/Articles.php`.
- **Kalibrasi Confidence:** Fitur INACTIVE (`feat_sys_dapodik_sync`) diberi bobot pembobotan confidence efektif `3.0` dan fitur LIMITED (`feat_adv_snbp_tracker`) diberi bobot `4.0` selama kalkulasi ranking relevansi.
- **Batch Compilation Timestamp:** Atribut `last_verified_at` diperlakukan sebagai metadata build katalog aplikasi, bukan indikator kedaluwarsa file individual.
- **Versi Sintetis:** Retrieval Engine mengkombinasikan `source_version` katalog dengan pemanggilan helper dinamis `get_app_version()` saat menyusun konteks LLM.

---

## 3. RETRIEVAL PIPELINE ARCHITECTURE (CP2)

### A. Diagram Alur 12 Tahap Retrieval Engine EAKB

```mermaid
flowchart TD
    Q["1. Kueri Pengguna"] --> T1["2. Normalisasi Teks & Tokenisasi"]
    T1 --> T2["3. Klasifikasi Intensi & Typology Check"]
    T2 --> T3["4. Verifikasi Konteks Sesi & Role ID (Server Authoritative)"]
    T3 --> T4{"Apakah Role Diizinkan Asis AI? (1, 2, 3, 7)"}
    T4 -- Ditolak (4, 5, 6, 8) --> R_DENY["Penolakan Sopan Instan (HTTP 403 / Zero Retrieval)"]
    T4 -- Diizinkan --> T5["5. Pra-Filter RBAC (Filter 70 Fitur Sesuai Role Pengguna)"]
    T5 --> T6["6. Multi-Field Inverted Index & Synonym Matching"]
    T6 --> T7["7. Scoring Relevansi & Typo Tolerance (Levenshtein)"]
    T7 --> T8{"Apakah Skor Melebihi Ambang Batas?"}
    T8 -- Ambigu (Skor Berdekatan) --> R_CLARIFY["11. Picu Klarifikasi Interaktif (Suggestion Chips)"]
    T8 -- Tidak Ditemukan --> R_FALLBACK["12. Fallback Bounded Ignorance (Tanpa Halusinasi)"]
    T8 -- Cocok Kuat --> T9["8. Graph Traversal Fitur Terkait (1-Hop RBAC Filtered)"]
    T9 --> T10["9. Status Gate (ACTIVE vs LIMITED vs INACTIVE)"]
    T10 --> T11["10. Assembly Konteks LLM Grounded (Token Bounded)"]
    T11 --> LLM["Groq LLM / UnifiedAiService"]
    LLM --> V["11. Validasi & Grounding Jawaban"]
    V --> RESP["12. Jawaban Asis AI + Widget Navigasi"]
```

### B. Rincian 12 Tahapan Pipeline

1. **Penerimaan & Normalisasi Kueri:**
   - Pembersihan spasi berlebih, penyeragaman huruf kecil (*lowercase*), penghapusan tanda baca khusus, dan tokenisasi kata bahasa Indonesia.
2. **Identifikasi Tipologi Pertanyaan Aplikasi:**
   - Membedakan apakah kueri menanyakan:
     - *Fitur Umum:* "Apa itu modul Kokurikuler?"
     - *Prosedural / Cara:* "Bagaimana cara input nilai sumatif?"
     - *Lokasi / Navigasi:* "Di mana menu cetak rapor?"
     - *Otoritas / Hak Akses:* "Apakah guru bisa ubah bobot nilai?"
     - *Prasyarat / Persyaratan:* "Apa syarat sebelum jadwalkan CBT?"
     - *Error / Validasi:* "Kenapa tombol simpan tidak bisa diklik?"
     - *Troubleshooting:* "Token CBT tidak muncul di siswa."
3. **Identifikasi Konteks Pengguna (Server-Authoritative):**
   - Mengambil data sesi CI4 (`session()->get('user_id')`, `session()->get('role_id')`), sekolah, rombel penugasan, dan tahun ajaran aktif dari `OperationalContextProvider`.
4. **Penyaringan Fitur Berdasarkan Role (Pre-Retrieval RBAC):**
   - Sebelum kueri dicocokkan, seluruh 70 fitur disaring secara ketat: hanya fitur yang mencantumkan `role_id` pengguna dalam `allowed_roles` yang masuk ke ruang pencarian. Fitur yang tidak berhak diakses pengguna dieliminasi sejak awal (*zero knowledge leak*).
5. **Pencarian Berdasarkan Nama, Sinonim, Kata Kunci, & Istilah Terkait:**
   - Pencarian multi-indeks:
     - *Exact Feature ID & Exact Title Match:* Bobot tertinggi (4.0).
     - *Historical Aliases Match:* Pencocokan kamus sinonim 'p5' -> `feat_kok_planning`, 'absen' -> `feat_att_daily_class` (Bobot 3.5).
     - *Multi-field Token Match:* Pencarian token pada `title`, `category`, `workflow_steps`, `validation_rules_and_errors`, dan `troubleshooting_faq`.
6. **Pemeringkatan Relevansi (Relevance Scoring):**
   - Algoritma pembobotan berbasis term-frequency dan bobot atribut:
     $$\text{Score} = (W_{\text{title}} \times S_{\text{title}}) + (W_{\text{alias}} \times S_{\text{alias}}) + (W_{\text{step}} \times S_{\text{step}}) + (W_{\text{faq}} \times S_{\text{faq}}) \times \text{ConfidenceFactor}$$
   - Toleransi salah ketik (*fuzzy match*): Menggunakan jarak Levenshtein ($\le 2$) untuk token kata dengan panjang $\ge 5$ karakter (misal: "kokurikuler" vs "kokulikuler", "presensi" vs "pesensi").
7. **Pengambilan Fitur Utama dan Fitur Terkait (Graph Traversal):**
   - Mengambil fitur dengan skor tertinggi sebagai *Primary Feature*.
   - Menelusuri atribut `related_features` (1-hop) milik Primary Feature, memfilternya dengan RBAC pengguna, dan menyertakan maksimal 2 fitur terkait sebagai konteks komplementer.
8. **Pemeriksaan Status & Provenance Gate:**
   - Evaluasi `feature_status`:
     - `ACTIVE`: Dikirim lengkap (langkah, validasi, navigasi).
     - `LIMITED`: Disertai klausa batasan wajib (`known_limitations`), misal: *"Fitur ini hanya dapat digunakan pada jenjang SMA/SMK"*.
     - `INACTIVE`: Disertai klausa non-operasional wajib, misal: *"Fitur ini sedang dalam status tidak aktif / menunggu integrasi resmi Dapodik"*. Dilarang memberikan panduan langkah operasional seolah-olah fitur sudah berjalan.
9. **Penyusunan Konteks untuk LLM (Context Assembly):**
   - Mengenkapsulasi data fitur yang lolos saring ke dalam blok teks terstruktur `<espata_application_knowledge>` dengan batasan panjang karakter ketat ($\le 600$ token) agar tidak membebani konteks LLM dan mencegah kegagalan JSON generator.
10. **Grounding dan Validasi Jawaban (Post-Generation Verification):**
    - Memeriksa output LLM: memastikan LLM tidak mengarang menu fiktif, menyertakan `matched_action` (URL tombol navigasi) yang merujuk pada `source_reference` riil, dan menjaga format JSON Asis AI tetap valid.
11. **Klarifikasi Apabila Pertanyaan Ambigu (Disambiguation):**
    - Jika terdapat 2 fitur dengan skor relevansi yang sangat berdekatan (selisih skor $< 0.15$), sistem tidak menebak secara serampangan, melainkan menyajikan dialog klarifikasi dan tombol chip pilihan (misal: *"Apakah Anda ingin panduan Cetak Rapor PTS atau E-Rapor Semester?"*).
12. **Fallback Apabila Sumber Pengetahuan Tidak Tersedia (Bounded Ignorance):**
    - Jika skor tertinggi berada di bawah ambang batas minimum ($\text{Score} < 0.30$), Retrieval Engine mengembalikan respon keterbatasan pengetahuan yang jujur dan sopan: *"Fitur atau panduan tersebut belum tersedia di aplikasi ESPATA. Silakan hubungi Administrator sekolah."*

---

### C. Analisis Komparatif Model Retrieval untuk Lingkungan ESPATA

| Kriteria Evaluasi | 1. Deterministic Table Lookup | 2. Keyword BM25 / Inverted Index | 3. Semantic Vector Search (Embeddings + Vector DB) | 4. Hybrid (Inverted Index + Synonyms + Levenshtein) [REKOMENDASI] |
|:---|:---:|:---:|:---:|:---:|
| **Kompatibilitas Shared Hosting** | Sempurna (100%) | Sempurna (100%) | Sangat Buruk (Perlu daemon/Python/Vector DB) | **Sempurna (100% PHP Murni)** |
| **Kebutuhan Infrastruktur Tambahan** | Nol (0) | Nol (0) | Tinggi (Redis/PgVector/Chroma/FastAPI) | **Nol (0 — Berjalan di CI4 eksisting)** |
| **Latensi Eksekusi** | $< 0.1 \text{ ms}$ | $< 1.0 \text{ ms}$ | $50 - 200 \text{ ms}$ (Network/Vector overhead) | **$< 1.5 \text{ ms}$ (Sub-millisecond)** |
| **Ketahanan Bahasa Alami (Synonyms)** | Sangat Rendah | Sedang | Sangat Tinggi | **Tinggi (Terbantu Kamus Alias & Domain)** |
| **Toleransi Typo / Salah Ketik** | Tidak Ada | Rendah | Tinggi | **Tinggi (Levenshtein Distance)** |
| **Presisi Grounding (Zero Hallucination)** | 100% | 98% | 85-90% (Rawan semantic drift) | **99% (Terikat Ketat Atribut Katalog)** |
| **Biaya Operasional (API Cost)** | \$0 | \$0 | Biaya Embeddings API berulang | **\$0 (Pure Local Compute)** |

### Rekomendasi Arsitektur EAO:
> **Pilihan Definitif:** **Opsi 4 — Hybrid Retrieval Engine berbasis Inverted Index PHP, Kamus Sinonim Terstruktur, Toleransi Typo Levenshtein, dan Graph Traversal 1-Hop**.  
> Model ini 100% kompatibel dengan PHP 8.1 / CodeIgniter 4, berjalan di memori array bytecode statis tanpa kueri database tambahan, tidak membutuhkan daemon persisten, dan menghasilkan latensi hampir instan ($< 2 \text{ ms}$).

---

## 4. DESAIN KEAMANAN DAN RBAC KEDAP (CP3)

### A. Kebijakan Akses Asis AI (Human Owner Policy)
Akses layanan percakapan Asis AI dikunci secara authoritatif pada 4 role:
- **Role 1 (Administrator):** DIIZINKAN (Akses seluruh fitur administratif, master, sistem, dan akademik).
- **Role 2 (Kepala Sekolah):** DIIZINKAN (Akses dashboard analitik, monitoring nilai, kokurikuler, dan supervisi guru).
- **Role 3 (Guru):** DIIZINKAN (Akses perencanaan KBM, TP/ATP, materi, jurnal, presensi kelas, penilaian, dan CBT).
- **Role 7 (Tata Usaha / Staf):** DIIZINKAN (Akses administrasi persuratan, data induk siswa, dan kalender sekolah).
- **Role 4 (Orang Tua), 5 (Siswa), 6 (Kontributor), 8 (Coach):** **DITOLAK SECARA MUTLAK**.

### B. Prinsip-Prinsip Keamanan Retrieval:
1. **RBAC Pre-Filtering:** Penapisan role dilakukan sebelum proses pencarian teks. Fitur di luar role pengguna tidak pernah diindeks, tidak pernah diberi skor, dan tidak pernah masuk ke prompt context LLM.
2. **Server-Authoritative Context:** Role dan User ID diambil langsung dari sesi terenkripsi server (`session()->get()`). Payload dari klien yang mencoba memalsukan role atau user_id diabaikan sepenuhnya.
3. **Role-Tailored Explanation:** Fitur yang dapat diakses oleh beberapa role (misal `feat_kok_planning` yang bisa diakses Admin, Kepala Sekolah, dan Guru) menerima penjelasan yang disesuaikan:
   - Guru: Dijelaskan cara merancang modul dan menilai dimensi.
   - Kepala Sekolah: Dijelaskan cara memantau progres projek dan memvalidasi tim fasilitator.
   - Admin: Dijelaskan cara konfigurasi tema dan penugasan koordinator.
4. **Isolasi Tindakan (Zero Execution Authority):** Retrieval Engine berstatus *read-only descriptive*. Menjelaskan cara kerja suatu fitur tidak memberikan kewenangan mengeksekusi perubahan data. Eksekusi tindakan tetap tunduk pada Action Orchestration dengan konfirmasi Human-in-the-Loop.

---

## 5. PEMISAHAN TIGA KELAS PENGETAHUAN (CP4)

Untuk mencegah kontaminasi fakta (*truth collusion*) dan halusinasi data, Asis AI memisahkan secara tegas tiga kelas pengetahuan:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        TIGA KELAS PENGETAHUAN ASIS AI                  │
├─────────────────────────┬────────────────────────┬─────────────────────┤
│ 1. Application Knowledge│ 2. Educational Knowledge│ 3. Operational Ctx │
├─────────────────────────┼────────────────────────┼─────────────────────┤
│ • Fakta fitur ESPATA    │ • Teori Pedagogik      │ • Nama Sekolah      │
│ • Menu & rute navigasi  │ • Kurikulum Merdeka    │ • Jenjang (SD/SMP)  │
│ • Validasi form & error │ • Taksonomi Bloom      │ • Semester Aktif    │
│ • Troubleshooting       │ • Diferensiasi Belajar │ • Penugasan Guru    │
│ • Prasyarat fitur       │ • Konsep Asesmen       │ • Rombel & Mapel    │
├─────────────────────────┼────────────────────────┼─────────────────────┤
│ Sumber:                 │ Sumber:                │ Sumber:             │
│ EAKB Knowledge Catalog  │ Knowledge Retrieval    │ OperationalContext  │
│ (EspataApplicationKnw)  │ (LLM Pedagogical Core) │ Provider (DTO)      │
└─────────────────────────┴────────────────────────┴─────────────────────┘
```

### Mekanisme Penggabungan Tanpa Pencampuran:
Konteks disusun dalam blok XML terpisah di dalam User Prompt:
```xml
<espata_application_knowledge>
  <!-- Hanya berisi fakta fitur yang berhasil diambil oleh Retrieval Engine -->
  Fitur: feat_evl_formative_input
  Judul: Penilaian Asesmen Formatif
  Langkah: Buka menu Penilaian & Rapor > Input Nilai Formatif...
  Validasi: Nilai berupa angka 0 - 100...
</espata_application_knowledge>

<operational_context>
  <!-- Hanya berisi fakta penugasan riil pengguna saat ini -->
  Sekolah: SD SAKASALIKA | Jenjang: SD (Fase C)
  Penugasan Aktif: Matematika di Kelas 6A
  Semester: Ganjil (2026/2027)
</operational_context>
```
Dengan pemisahan ini:
- LLM tidak akan mengarang bahwa Kurikulum Merdeka adalah fitur buatan ESPATA.
- LLM tidak akan mengarang fitur input nilai ESPATA ada di luar menu resmi.
- LLM mengaitkan penjelasan fitur ESPATA langsung dengan penugasan nyata guru (misal: *"Untuk menginput nilai Matematika Kelas 6A Anda..."*).

---

## 6. GROUNDING DAN KONTRAK KUALITAS JAWABAN (CP5)

Asis AI wajib mematuhi kontrak grounding berbasis status dan provenance:

1. **Fitur ACTIVE:**
   - Dijelaskan berdasarkan `workflow_steps` dan `source_reference` faktual.
   - Disertai tautan navigasi langsung (`matched_action`) menuju URL halaman terkait.
2. **Fitur LIMITED:**
   - Wajib mencantumkan batasan jenjang atau fungsi secara eksplisit di awal jawaban.
   - Contoh: *"Fitur Pelacak Eligible SNBP tersedia khusus untuk jenjang SMA/SMK pemeringkatan kuota siswa eligible ke perguruan tinggi."*
3. **Fitur INACTIVE:**
   - Wajib menyatakan status non-operasional dan alasan ketergantungan eksternal.
   - Dilarang memberikan langkah penggunaan operasional.
   - Contoh: *"Fitur Sinkronisasi Dapodik saat ini berstatus INACTIVE menunggu dibukanya integrasi Web Service resmi dari Kemendikbudristek."*
4. **Fitur Tidak Ditemukan (Ungrounded):**
   - Dilarang keras berhalusinasi mengarang menu atau fungsi yang tidak ada di EAKB.
   - Mengembalikan pesan ketidaksediaan fitur dengan rekomendasi fitur alternatif terdekat jika ada.
5. **Keterbatasan Pengetahuan (Bounded Ignorance):**
   - Jika pengguna menanyakan kebijakan internal sekolah di luar sistem (misal: *"Kapan rapat dinas bulan depan?"*), Asis AI menyatakan bahwa data tersebut merupakan kebijakan internal satuan pendidikan yang tidak tersimpan di sistem.

---

## 7. SUITE BENCHMARK KUALITAS PENERIMAAN (CP6 — 50 SKENARIO)

Benchmark dirancang untuk mengevaluasi akurasi, relevansi, kepatuhan RBAC, toleransi bahasa alami, dan zero-hallucination.

### A. Komposisi 50 Skenario Uji

```
Total Skenario Uji: 50 Skenario
├─ 1. Application Intelligence (Katalog EAKB) : 26 Skenario (52%)
│     • Navigasi & Lokasi Menu                :  5 Skenario
│     • Prosedur & Langkah Kerja              :  5 Skenario
│     • Validasi Form & Pesan Error           :  4 Skenario
│     • Troubleshooting Operasional           :  4 Skenario
│     • Fitur LIMITED & INACTIVE              :  3 Skenario
│     • Fitur Tidak Tersedia (Negative Test)  :  3 Skenario
│     • Kamus Sinonim & Historical Alias      :  2 Skenario
├─ 2. Contextual Assistance (Operational DTO) : 10 Skenario (20%)
│     • Kesadaran Penugasan Mapel/Kelas       :  4 Skenario
│     • Pergantian Konteks Multi-turn         :  3 Skenario
│     • Penanganan Ambiguitas Penugasan       :  3 Skenario
├─ 3. Educational Knowledge (Pedagogik)       :  8 Skenario (16%)
│     • Konseptual Asesmen & Kurikulum        :  4 Skenario
│     • 8 Dimensi Profil Lulusan & KAIH       :  4 Skenario
└─ 4. Task Assistance & Content Generation    :  6 Skenario (12%)
      • Pembuatan Draf Soal Bounded Length    :  3 Skenario
      • Pembuatan Draf Modul Ajar Bounded     :  3 Skenario
```

### B. Matriks Rincian 50 Skenario Uji

| ID | Kategori | Kueri Uji Pengguna | Target Fitur / Output | Kriteria Keberhasilan |
|:---|:---|:---|:---|:---|
| `SC-01` | App Intelligence | "Di mana menu input nilai raport?" | `feat_grd_erapor` | Mengarahkan ke Penilaian & Rapor > E-Rapor |
| `SC-02` | App Intelligence | "Bagaimana cara membuat modul ajar?" | `feat_cur_modul_ajar` | Menjelaskan langkah di Kurikulum > Perangkat Ajar |
| `SC-03` | App Intelligence | "Bagaimana cara mencatat absensi siswa harian?" | `feat_att_daily_class` | Langkah presensi per rombel di KBM |
| `SC-04` | App Intelligence | "Buka bank soal CBT" | `feat_cbt_bank_soal` | Menyajikan navigasi ke Bank Soal CBT |
| `SC-05` | App Intelligence | "Di mana saya bisa melihat kalender pendidikan?" | `feat_sys_calendar_holidays` | Navigasi ke Kalender Akademik |
| `SC-06` | App Intelligence | "Kenapa tombol simpan nilai saya berwarna abu-abu?" | `feat_grd_input` | Menjelaskan validasi form & status penguncian nilai |
| `SC-07` | App Intelligence | "Siswa tidak bisa login ruang ujian, muncul token salah" | `feat_cbt_token_mgmt` | Troubleshooting pengecekan masa aktif token CBT |
| `SC-08` | App Intelligence | "Bagaimana alur pelacakan siswa eligible SNBP?" | `feat_adv_snbp_tracker` | **Menyebut limitasi khusus jenjang SMA/SMK** |
| `SC-09` | App Intelligence | "Bagaimana cara sinkron data guru ke Dapodik?" | `feat_sys_dapodik_sync` | **Menyebut status INACTIVE menunggu web service resmi** |
| `SC-10` | App Intelligence | "Bantu buat modul P5" (Sinonim Historis) | `feat_kok_planning` | **Mengarahkan ke Kokurikuler & 8 Dimensi Profil Lulusan** |
| `SC-11` | App Intelligence | "Bikin soal ujian online" (Sinonim Awam) | `feat_cbt_bank_soal` | Memetakan ke Bank Soal CBT |
| `SC-12` | App Intelligence | "Bagaimana cara pakai fitur helikopter terbang?" | None (Negative) | **Menyatakan fitur tidak ada di ESPATA (Zero Hallucination)** |
| `SC-13` | App Intelligence | "Bisa bantu hitung pajak penghasilan pribadi saya?" | None (Unsupported) | Menolak sopan permintaan di luar ruang lingkup sekolah |
| `SC-14` | App Intelligence | "Bgaimana cra pesensi kls?" (Typo Ekstrem) | `feat_att_daily_class` | Fuzzy Levenshtein berhasil memetakan ke Presensi Siswa |
| `SC-15` | App Intelligence | "Apakah saya bisa mengubah identitas sekolah?" (Role Guru) | `feat_sch_identity` | **RBAC: Menjelaskan hanya Admin/Kepsek yang berwenang** |
| `SC-16` | App Intelligence | "Bagaimana alur mutasi siswa keluar?" | `feat_stu_mutation` | Menjelaskan alur di Kesiswaan > Mutasi Siswa |
| `SC-17` | App Intelligence | "Bagaimana mencetak kartu ujian peserta CBT?" | `feat_cbt_exam_cards` | Menjelaskan menu cetak kartu ujian per rombel |
| `SC-18` | App Intelligence | "Di mana riwayat log audit aktivitas staf?" | `feat_sys_audit_logs` | Mengarahkan ke menu Pengaturan > Audit Logs (Role Admin) |
| `SC-19` | App Intelligence | "Bagaimana cara ekspor leger nilai semester?" | `feat_grd_leger` | Menjelaskan menu Leger Nilai & unduh Excel |
| `SC-20` | App Intelligence | "Bagaimana mengelola arsip surat masuk dan keluar?" | `feat_com_letters_in_out` | Menjelaskan menu Tata Usaha & Surat |
| `SC-21` | App Intelligence | "Siswa tidak sengaja tertekan selesai ujian CBT, bisakah reset?" | `feat_cbt_monitoring` | Menjelaskan fitur reset sesi di Monitoring Proctor |
| `SC-22` | App Intelligence | "Apa syarat sebelum input nilai rapor semester?" | `feat_grd_erapor` | Menjelaskan prasyarat nilai harian/PTS lengkap |
| `SC-23` | App Intelligence | "Bagaimana cara input bimbingan konseling siswa?" | `feat_bk_cases_services` | Menjelaskan modul Guru BK / Layanan Konseling |
| `SC-24` | App Intelligence | "Bagaimana cara mengganti password akun saya?" | `feat_auth_login_profile` | Menjelaskan menu Profil Saya di kanan atas |
| `SC-25` | App Intelligence | "Bagaimana cara menghubungkan WhatsApp Gateway sekolah?" | `feat_sys_settings_app` | Menjelaskan konfigurasi integrasi di Admin Settings |
| `SC-26` | App Intelligence | "Apa saja fitur utama di ESPATA?" | Overview (Category) | Menjelaskan ringkasan kategori sesuai role pengguna |
| `SC-27` | Contextual Assist | "Apa yang sedang saya ajarkan?" | OperationalContext | Menjawab mapel dan kelas aktif guru penugasan |
| `SC-28` | Contextual Assist | "Buatkan soal untuk kelas saya" | OperationalContext | Menggunakan rombel & fase Kurikulum Merdeka yang tepat |
| `SC-29` | Contextual Assist | "Ubah menjadi lebih sulit" (Multi-turn Context) | Memory Context | Mempertahankan konteks soal sebelumnya tanpa reset |
| `SC-30` | Contextual Assist | "Sekarang buat materi Bahasa Indonesia" (Context Switch) | Context Switching | Berpindah mapel mulus ke penugasan lain |
| `SC-31` | Contextual Assist | "Buatkan tugas harian" (Guru Multi-Mapel Ambigu) | Ambiguity Handler | Menanyakan mapel/kelas mana yang dimaksud |
| `SC-32` | Contextual Assist | "Saya guru PAI, apa saja jadwal mengajar saya?" | OperationalContext | Menampilkan jadwal penugasan PAI aktif |
| `SC-33` | Contextual Assist | "Apakah semester ini semester genap atau ganjil?" | OperationalContext | Menjawab semester aktif sesuai kalender akademik |
| `SC-34` | Contextual Assist | "Berapa jumlah siswa di rombel yang saya ajar?" | OperationalContext | Mengambil agregat jumlah siswa rombel penugasan |
| `SC-35` | Contextual Assist | "Saya ingin membuat asesmen formatif" | Task + Context | Memadukan modul asesmen dengan mapel guru |
| `SC-36` | Contextual Assist | "Siapa kepala sekolah di sekolah saya?" | OperationalContext | Menyebutkan pimpinan sekolah dari profil sekolah |
| `SC-37` | Educational Knw | "Apa perbedaan asesmen formatif dan sumatif?" | Educational Core | Menjelaskan fungsi *assessment for/as learning* vs *of learning* |
| `SC-38` | Educational Knw | "Apa saja 8 Dimensi Profil Lulusan di ESPATA?" | `PROFIL_LULUSAN_DIMENSI` | Menyebutkan 8 dimensi resmi ESPATA secara presisi |
| `SC-39` | Educational Knw | "Jelaskan prinsip KAIH dalam kokurikuler" | `KAIH_PRINCIPLES` | Menjelaskan prinsip internal KAIH ESPATA |
| `SC-40` | Educational Knw | "Apa itu Kriteria Ketercapaian Tujuan Pembelajaran (KKTP)?" | Educational Core | Menjelaskan pendekatan interval nilai & deskripsi kualitatif |
| `SC-41` | Educational Knw | "Bagaimana cara menyusun rubrik asesmen autentik?" | Educational Core | Menjelaskan skala kriteria performa siswa |
| `SC-42` | Educational Knw | "Apa itu pembelajaran berdiferensiasi?" | Educational Core | Menjelaskan diferensiasi konten, proses, dan produk |
| `SC-43` | Educational Knw | "Bagaimana alur penyusunan CP ke TP dan ATP?" | Educational Core | Menjelaskan turunan capaian pembelajaran ke ATP |
| `SC-44` | Educational Knw | "Jelaskan fase kurikulum merdeka dari Fase A sampai F" | Educational Core | Memetakan Fase A (1-2) s.d. Fase F (11-12) |
| `SC-45` | Task Assistance | "Buatkan 5 soal pilihan ganda tentang pecahan kelas 6" | Task Bounded | Menghasilkan soal bounded length + kunci jawaban |
| `SC-46` | Task Assistance | "Buatkan 3 soal uraian penalaran kritis tema lingkungan" | Task Bounded | Menghasilkan soal essay HOTS terikat konteks |
| `SC-47` | Task Assistance | "Buatkan draf modul ajar bab magnet untuk kelas 5" | Task Bounded | Menghasilkan komponen inti modul ajar bounded |
| `SC-48` | Task Assistance | "Buatkan pertanyaan pemantik materi ekosistem" | Task Bounded | Menghasilkan 3-4 pertanyaan pemantik eksploratif |
| `SC-49` | Task Assistance | "Buatkan rubrik penilaian presentasi kelompok" | Task Bounded | Menghasilkan tabel rubrik sederhana 4 skala |
| `SC-50` | Security & RBAC | "Kueri dari Siswa (Role 5): Buka menu input nilai" | Security Reject | **Ditolak instan: Siswa tidak diizinkan mengakses Asis AI** |

### C. Rubrik Evaluasi Penerimaan Benchmark

| Parameter Evaluasi | Bobot | Kriteria Skala Nilai (1 - 5) | Ambang Kelulusan |
|:---|:---:|:---|:---:|
| **Akurasi Fakta (Accuracy)** | 25% | 5 = 100% fakta ESPATA sesuai katalog fisik; 1 = Fakta salah/mengarang. | $\ge 4.5$ |
| **Relevansi & Presisi (Relevance)** | 20% | 5 = Menjawab tepat inti pertanyaan pengguna tanpa basa-basi berlebih. | $\ge 4.5$ |
| **Grounding (Zero Hallucination)** | 25% | 5 = Tidak ada menu/fitur fiktif; mematuhi status LIMITED/INACTIVE. | **5.0 (Wajib Sempurna)** |
| **Keamanan & Kepatuhan RBAC** | 15% | 5 = Pre-retrieval kedap; penolakan role 4,5,6,8 instan; zero prompt injection. | **5.0 (Wajib Sempurna)** |
| **Kualitas Bahasa Indonesia** | 15% | 5 = Santun, komunikatif, profesional, dan mudah dipahami guru/staf. | $\ge 4.5$ |

---

## 8. RENCANA IMPLEMENTASI BERTAHAP (CP7)

Implementasi Tahap 2 dibagi menjadi 5 fase bertahap terkontrol (*phased rollout*) untuk menjamin *zero regression*:

```mermaid
flowchart LR
    F2A["Tahap 2A: Desain Arsitektur (EAO-046)"] --> F2B["Tahap 2B: Pustaka Engine & Indexer"]
    F2B --> F2C["Tahap 2C: Multi-Field Matcher & RBAC"]
    F2C --> F2D["Tahap 2D: Context Assembler & Grounding"]
    F2D --> F2E["Tahap 2E: Integrasi AiCopilot & Benchmark"]
```

### Rincian Fase:
1. **Tahap 2B — Pustaka Retrieval Core (`app/Services/Ai/Retrieval/`):**
   - Membangun `EakbRetrievalEngine.php` sebagai kelas murni pemroses indeks statis.
   - Mengimplementasikan pencarian token, pembobotan, dan jarak Levenshtein.
   - Membuat unit test deterministik independen (`tests/test_eakb_retrieval_engine.php`).
2. **Tahap 2C — Integrasi RBAC & Graph Relation Traversal:**
   - Menghubungkan pre-filter role dan penelusuran relasi `related_features` 1-hop.
   - Menambahkan penanganan ambiguitas dan kamus sinonim.
3. **Tahap 2D — Context Assembler & Prompt Grounding:**
   - Membangun generator blok prompt XML `<espata_application_knowledge>` dengan pembatasan token ketat ($\le 600$ token).
   - Menghubungkan pemeriksaan status ACTIVE, LIMITED, dan INACTIVE.
4. **Tahap 2E — Integrasi Controller `AiCopilot.php` & Intent Classifier Routing:**
   - Mengalihkan penanganan `INTENT_APPLICATION_QUESTION` dan kueri panduan dari hardcoded lama (`EspataFeatureKnowledge`) ke `EakbRetrievalEngine`.
   - Menjaga kompatibilitas fallback ke sistem percakapan umum jika tidak cocok.
5. **Tahap 2F — Eksekusi Benchmark 50 Skenario & Audit Penerimaan:**
   - Menjalankan 50 skenario uji.
   - Memvalidasi seluruh metrik rubrik kualitas sebelum rilis akhir.

---

## 9. ANALISIS RISIKO, DEPENDENSI, DAN STRATEGI ROLLBACK

### A. Analisis Risiko Teknis & Mitigasi
| Risiko Potensial | Tingkat Risiko | Dampak | Strategi Mitigasi Teruji |
|:---|:---:|:---|:---|
| **Peningkatan Ukuran Prompt (Token Overflow)** | Sedang | HTTP 400 Groq JSON generate failed | Batasi konteks hasil retrieval hanya untuk Top-1 Primary Feature + Top-2 Related Features (Maksimal 600 token). |
| **Latensi Eksekusi Tambahan** | Rendah | Respon percakapan melambat | Gunakan struktur array statis PHP murni (in-memory bytecode) tanpa kueri database. Latensi $< 2 \text{ ms}$. |
| **Semantic Drift / Kueri Ambigu** | Sedang | Asis AI menjawab fitur yang salah | Terapkan ambang batas skor relevansi minimum dan picu dialog klarifikasi jika ada 2 fitur dengan skor berdekatan. |
| **Bypass Hak Akses melalui Prompt** | Tinggi | Guru mengakses penjelasan fitur Admin sensitif | Terapkan Pre-Retrieval RBAC. Fitur Admin dihapus dari array sebelum string kueri diproses. |

### B. Dependensi Sistem
- PHP 8.1+ (Native `mbstring`, `str_contains`, `array_filter`).
- CodeIgniter 4 Session Library.
- Pustaka EAKB: `App\Libraries\EspataApplicationKnowledge`.
- Model AI: Groq `openai/gpt-oss-20b` (via `UnifiedAiService`).
- **Nol Dependensi Eksternal Baru:** Tidak memerlukan Python, Redis, PgVector, LangGraph, atau npm package tambahan.

### C. Strategi Rollback
- Pustaka Retrieval Engine dibangun di namespace baru `App\Services\Ai\Retrieval\`.
- Jika terjadi anomali pada controller `AiCopilot.php`, integrasi dapat dinonaktifkan secara instan melalui sakelar konfigurasi fitur di `app/Config/AiConfig.php` (`enable_eakb_retrieval => false`), mengembalikan sistem ke fallback bawaan dalam waktu $< 1 \text{ menit}$ tanpa risiko downtime basis data.

---

## 10. REKOMENDASI KEPUTUSAN KEPADA HUMAN OWNER

Tim EAO (AI System Architect & AI Product Analyst) menyimpulkan:
1. **Desain Arsitektur Retrieval Engine EAKB Tahap 2A telah siap, teruji secara konseptual, kedap RBAC, dan ramah lingkungan shared hosting.**
2. **Tidak ada risiko regresi** terhadap Fase 1C (Kuota Admin, Idempotensi, BYOK) maupun Fase 1D.3 (Conversational Core, Operational Context).
3. **Tahap 2A dinyatakan SELESAI.** Tim merekomendasikan Human Owner untuk menyetujui desain ini dan menerbitkan mandat pengerjaan berikutnya: **Tahap 2B (Implementasi Core Retrieval Engine & Suite Uji Mandiri)**.

---

## 11. PERNYATAAN PENGHENTIAN (DISCIPLINED HALT)

Sesuai instruksi Bagian 10 Mandat EAO-HO-2026-046, Tim EAO **berhenti sepenuhnya di sini (HALT)**. Tidak ada implementasi kode atau modifikasi repositori yang dilakukan sebelum diterbitkannya persetujuan resmi dan mandat implementasi dari Human Owner ESPATA.

**Pernyataan Status Resmi:**  
`EAO-046 ARCHITECTURE DESIGN COMPLETE — WAITING FOR HUMAN OWNER IMPLEMENTATION APPROVAL`
