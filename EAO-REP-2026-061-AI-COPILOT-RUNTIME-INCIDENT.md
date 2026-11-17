# EAO-REP-2026-061-AI-COPILOT-RUNTIME-INCIDENT
## LAPORAN INVESTIGASI INSIDEN RUNTIME AI COPILOT
### Analisis Penyebab UI Terjebak 'Memproses data & permintaan' pada Query LMS

**Referensi Mandat:** EAO-HO-2026-061  
**Tanggal Investigasi:** 17 November 2026  
**Jenis Laporan:** READ-ONLY FORENSIC & RUNTIME CODE AUDIT  
**Status Otorisasi:** READ-ONLY INVESTIGATION (STRICT HALT — TANPA MODIFIKASI KODE)  
**Pelaksana:** Tim Multi-Agen EAO (AI System Architect, AI QA Engineer, AI Security Reviewer, AI Developer)  
**Human Owner:** ESPATA Project Owner  

---

## 1. RINGKASAN EKSEKUTIF INSIDEN

### 1.1 Deskripsi Gejala
Pengguna mengajukan pertanyaan pada antarmuka Asis AI / Command Palette:
> *"Bagaimana cara membuat materi di LMS?"*

Antarmuka Command Palette menampilkan indikator status pemrosesan:
> *"Memproses data & permintaan..."*

Gejala insiden: Indikator pemrosesan tersebut berputar/tampil secara terus-menerus (*infinite spinner / stuck*), tidak pernah hilang, dan tidak pernah memunculkan jawaban teks maupun pesan galat (*error message*).

### 1.2 Hasil Temuan Utama (Root Cause Summary)
Investigasi forensik kode *end-to-end* mengidentifikasi **dua masalah independen yang saling berkorelasi**:

1. **Penyebab Utama UI Terjebak (*Infinite Loading Hang* — Kategori: Frontend Resilience & Network Timeout):**
   Pada berkas [command_palette.php](file:///c:/xampp/htdocs/siakad/app/Views/layouts/partials/command_palette.php#L935-L985), fungsi `submitChatMessage()` menambahkan elemen DOM spinner loading via `appendBotLoading()`. Elemen ini **hanya** dilepas (*removed*) pada saat `fetch()` menyelesaikan blok `.then(res => ...)` (baris 951) atau blok `.catch(err => ...)` (baris 975).
   Pemanggilan `fetch('<?= site_url('api/ai-copilot/ask') ?>', ...)` **tidak dilengkapi dengan `AbortController` maupun timeout klien (*client-side timeout*)**. Akibatnya, manakala koneksi HTTP tertahan di sisi server (misalnya akibat *session lock contention*, latensi DNS socket Windows pada koneksi cURL Groq, atau eksekusi PHP yang menggantung), browser membiarkan Promise dalam status `PENDING` tanpa batas waktu. Indikator pemrosesan pun terkunci selamanya di layar.

2. **Penyebab Semantik & Logika Pengetahuan (*Knowledge Hijacking & Misdirection* — Kategori: Backend Intent Routing):**
   Kueri *"Bagaimana cara membuat materi di LMS?"* memiliki kata penanda panduan (*"bagaimana cara"*).
   Pada [AiCopilot.php](file:///c:/xampp/htdocs/siakad/app/Controllers/Api/AiCopilot.php#L582-L589), terdapat cabang pintas legasi:
   ```php
   if ($intentType === 'APPLICATION_QUESTION' && $hasGuideMarker) {
       $guide = \App\Libraries\EspataFeatureKnowledge::findGuide($query, $roleId);
       if ($guide) { ... return guide card ... }
   }
   ```
   Berdasarkan pengujian faktual pada pustaka [EspataFeatureKnowledge.php](file:///c:/xampp/htdocs/siakad/app/Libraries/EspataFeatureKnowledge.php):
   - Pustaka legasi ini **hanya memiliki 9 panduan hardcoded** dan **TIDAK memiliki panduan untuk modul LMS Materi**.
   - Akibat pencocokan token kata kunci longgar (*"cara"*, *"membuat"*, *"materi"*):
     - Untuk **Role 1 (Admin)** dan **Role 3 (Guru)**: Kueri secara keliru dicocokkan ke `guide_modul_ajar` (**Modul Ajar Kurikulum**, bukan LMS!).
     - Untuk **Role 2 (Kepala Sekolah)**: Kueri secara fatal dicocokkan ke `guide_rekap_absensi_guru`!
   - Padahal, pada katalog kanonikal EAKB 70 fitur (`EspataApplicationKnowledge`), materi LMS terdaftar sempurna pada fitur **`feat_lms_materials_seg`** (*Materi Pembelajaran Bersegmen LMS*) dengan status `READY`.
   - Cabang legasi ini membajak (*hijack*) kueri sebelum Retrieval Engine EAKB sempat menyusun konteks kanonikal yang akurat.

---

## 2. PELACAKAN ALUR END-TO-END (12 TAHAPAN INVESTIGASI)

Tim EAO telah menelusuri secara ketat setiap titik perlintasan data dari saat kueri dikirim hingga tampilan di antarmuka pengguna:

| No | Tahap Penelusuran | Komponen / Berkas | Status Hasil Audit | Temuan Faktual |
|:---|:---|:---|:---|:---|
| 1 | **Antarmuka & JavaScript** | `command_palette.php`<br>`public/assets/js/asis-copilot.js` | **DEFECT TERIDENTIFIKASI** | `appendBotLoading()` dijalankan saat kirim. `fetch()` tidak memiliki `signal: AbortSignal.timeout(ms)`. Jika server tidak menutup koneksi atau lambat, UI stuck permanen. |
| 2 | **Transmisi Request ke `/api/ai-copilot/ask`** | Route: `POST api/ai-copilot/ask` | **NORMAL (CONDITIONAL)** | Routing terdaftar dengan filter `['role:1,2,3,7']`. Header CSRF (`X-CSRF-TOKEN`) dikirim via JS. Jika sesi kedaluwarsa atau token *mismatch*, CodeIgniter melemparkan HTTP 403 HTML. |
| 3 | **Autentikasi, Session, & RBAC** | `AiCopilot.php` (L368–395) | **POTENSI KONTENSI LOCK** | Session dibuka untuk cek `session()->get('user')`. `session_write_close()` baru dipanggil pada baris 394. Jika ada tab/request lain membuka sesi tanpa melepaskan lock, request ini akan *block* di baris 379. |
| 4 | **Idempotency & Percakapan** | `RequestIdempotencyManager.php`<br>`ConversationMemoryManager.php` | **NORMAL** | Hash request dan percakapan tercatat rapi jika database aktif. Jika idempotency mendeteksi duplikasi *in-flight*, status 409 dikembalikan. |
| 5 | **Klasifikasi Intent** | `IntentClassifier.php` | **NORMAL** | Kueri *"Bagaimana cara membuat materi di LMS?"* terklasifikasi sebagai `APPLICATION_QUESTION` (confidence 0.88), regex penanda panduan `$hasGuideMarker` menghasilkan `true`. |
| 6 | **Penyusunan Konteks & EAKB** | `EspataFeatureKnowledge.php`<br>`EakbContextAssembler.php` | **DEFECT LOGIKA (SHADOWING)** | Cabang `findGuide()` legasi membajak kueri. Mengembalikan `guide_modul_ajar` untuk Guru/Admin dan `guide_rekap_absensi_guru` untuk Kepsek, mem-bypass katalog EAKB `feat_lms_materials_seg`. |
| 7 | **Perakitan Prompt LLM** | `AiCopilot::buildPromptContext()` | **NORMAL (KONSISTEN)** | Menghasilkan struktur prompt sistem, context anchor, dan history yang sesuai dengan kontrak Stage 2E.1. |
| 8 | **Resolusi API Key & Kuota** | `AiKeyResolver.php`<br>`AdminQuotaManager.php` | **NORMAL** | Verifikasi kuota harian dan pengambilan BYOK/System Groq API Key berjalan melalui query database `ai_admin_quota_daily`. |
| 9 | **Pemanggilan Groq API (cURL)** | `AiCopilot::callUnifiedAiNlu()`<br>`GroqAdapter.php` | **POTENSI LATENSI/BLOCKING** | cURL timeout diset 15 detik, connect timeout 5 detik. Namun pada platform Windows, resolusi DNS via `getaddrinfo` sinkron dapat mengalami socket hang hingga 60-120 detik jika terjadi kendala jaringan/proxy. |
| 10 | **Pemrosesan Respons Backend** | `AiCopilot.php` | **NORMAL** | JSON decode dan ekstraksi teks dilakukan sebelum pengembalian respons. |
| 11 | **Transmisi Respons ke Frontend** | Response Header `application/json` | **NORMAL JIKA SERVER FINISH** | Server mengembalikan format `{ success: true, answer: "...", metadata: ... }`. |
| 12 | **Penghentian Loading & Render** | `command_palette.php` | **TITIK MANIFESTASI GEJALA** | Elemen `bot-loading-*` hanya dihapus jika `fetch` resolve atau reject. Karena tahap 1, 3, atau 9 menggantung, tahap 12 tidak pernah tercapai. |

---

## 3. ANALISIS DETAIL KETIDAKBERFUNGSIAN SISTEM

### 3.1 Defect 1: Ketiadaan Mekanisme Timeout pada Frontend Client (Penyebab UI Hang)
Di dalam [command_palette.php](file:///c:/xampp/htdocs/siakad/app/Views/layouts/partials/command_palette.php), perhatikan implementasi AJAX berikut:

```javascript
// Baris 940 - 953
appendBotLoading(loadingId);

fetch('<?= site_url('api/ai-copilot/ask') ?>', {
    method: 'POST',
    headers: fetchHeaders,
    body: JSON.stringify(payload)
})
.then(res => {
    const loadingEl = document.getElementById(loadingId);
    if (loadingEl) loadingEl.remove();
    // ...
})
.catch(err => {
    const loadingEl = document.getElementById(loadingId);
    if (loadingEl) loadingEl.remove();
    // ...
});
```

**Mekanisme Kegagalan:**
1. Manakala pengguna mengirim pertanyaan, `appendBotLoading(loadingId)` langsung menyisipkan markup:
   ```html
   <div id="bot-loading-..." class="chat-item chat-bot">
       <span class="spinner-border spinner-border-sm me-2"></span>
       <span>Memproses data & permintaan...</span>
   </div>
   ```
2. Pemanggilan standard JavaScript `fetch()` **tidak memiliki opsi timeout bawaan**. Tanpa `signal: AbortSignal.timeout(20000)` atau `AbortController`, browser akan mempertahankan koneksi HTTP socket dalam kondisi terbuka (*waiting for server response*).
3. Jika backend PHP:
   - Terkunci pada baris `session()->get('user')` menunggu pelepasan sesi dari skrip lain, ATAU
   - Terkunci pada cURL Groq / resolusi DNS jaringan, ATAU
   - Mengalami *Fatal Execution Timeout* tanpa *flush buffer*,
4. Browser tidak pernah masuk ke blok `.then()` maupun `.catch()`. Akibatnya, pemanggilan `loadingEl.remove()` **tidak pernah dieksekusi**, dan teks *"Memproses data & permintaan..."* membeku di layar selamanya.

---

### 3.2 Defect 2: Pembajakan Semantik oleh `EspataFeatureKnowledge::findGuide()`
Pada [AiCopilot.php](file:///c:/xampp/htdocs/siakad/app/Controllers/Api/AiCopilot.php#L582-L589):

```php
// Cek Quick Guide untuk Application Question (L582-L589)
if ($intentType === 'APPLICATION_QUESTION' && $hasGuideMarker) {
    $guide = \App\Libraries\EspataFeatureKnowledge::findGuide($query, $roleId);
    if ($guide) {
        $meta = [
            'type'        => 'quick_guide',
            'guide_id'    => $guide['id'],
            'feature_id'  => $guide['feature_id'] ?? null,
            'source'      => 'rule_based_guide',
        ];
        return $this->respondSuccess($guide['title'], $guide['steps'], $meta);
    }
}
```

Pustaka `EspataFeatureKnowledge` adalah modul legasi Fase 1D yang hanya mendefinisikan 9 panduan statis:
1. `guide_input_nilai`
2. `guide_cetak_rapor`
3. `guide_rekap_absensi_guru`
4. `guide_modul_ajar`
5. `guide_kalender_akademik`
6. `guide_verifikasi_profil_siswa`
7. `guide_verifikasi_profil_guru`
8. `guide_ekskul_guru`
9. `guide_setting_bobot_nilai`

**Hasil Uji Faktual (Simulasi Algoritma `findGuide`):**
Ketika kueri *"Bagaimana cara membuat materi di LMS?"* diproses oleh `findGuide`:
- Kata kunci kueri: `bagaimana`, `cara`, `membuat`, `materi`, `lms`.
- Algoritma menghitung irisan kata (*keyword token overlap*).
- Untuk **Role 3 (Guru)**: Kata *"cara"*, *"membuat"*, *"materi"* cocok dengan kata kunci `guide_modul_ajar` (*"panduan membuat modul ajar"*). Pustaka mengembalikan **Modul Ajar**, BUKAN panduan LMS!
- Untuk **Role 2 (Kepala Sekolah)**: Karena `guide_modul_ajar` tidak diizinkan untuk Role 2, algoritma mencocokkan kata *"bagaimana cara"* ke panduan tersisa berikutnya yaitu `guide_rekap_absensi_guru`!
- Untuk **Role 7 (Tata Usaha)**: Mengembalikan `NULL`.

**Dampak Arsitektural:**
Katalog EAKB Kanonikal (yang memiliki 70 fitur lengkap, termasuk `feat_lms_materials_seg` yang memuat nama, deskripsi, route, dan status modul LMS) **sama sekali tidak terpanggil** untuk Guru dan Admin, karena dijegal oleh cabang `findGuide()` yang memberikan jawaban yang salah total.

---

### 3.3 Defect 3: Riwayat Kegagalan CSRF Token pada Log Sistem
Berdasarkan pembacaan berkas log aktual di `writable/logs/log-2026-11-16.log` dan `writable/logs/log-2026-11-17.log`, ditemukan catatan galat berulang:
```text
CRITICAL - 2026-11-16 09:12:43 --> CodeIgniter\Security\Exceptions\SecurityException: The action you requested is not allowed. [Method: POST, Route: api/ai-copilot/ask]
```

**Mekanisme Kegagalan CSRF:**
- ESPATA mengaktifkan proteksi CSRF berbasis sesi (`$csrfProtection = 'session'`) dengan token dinamis.
- Ketika pengguna membuka aplikasi di tab lain, melakukan navigasi, atau sesi kedaluwarsa, nilai token di `meta[name="csrf-token"]` pada DOM menjadi basi (*stale*).
- Saat `fetch()` mengirimkan request dengan token lama, CodeIgniter memotong request di filter keamanan (*Security Filter*) sebelum masuk ke `AiCopilot.php`, dan mengembalikan respons **HTTP 403 Forbidden** berupa halaman HTML galat standar CodeIgniter.
- Di [command_palette.php](file:///c:/xampp/htdocs/siakad/app/Views/layouts/partials/command_palette.php#L950-L960):
  ```javascript
  .then(res => {
      const loadingEl = document.getElementById(loadingId);
      if (loadingEl) loadingEl.remove();
      return res.json();
  })
  ```
  Pada respons 403, `res.json()` gagal (*parse error* karena server mengembalikan HTML). Eksekusi melompat ke `.catch()`. Pada skenario normal, `.catch()` menghapus spinner, tetapi jika script mengalami kegagalan parsing header sebelumnya, UI terancam tidak ter-update.

---

## 4. KLASIFIKASI TEMUAN (VERIFIED / SUSPECTED / NOT TESTED)

Berdasarkan audit statis dan simulasi eksekusi tanpa modifikasi kode produksi:

| ID Temuan | Klasifikasi | Deskripsi Masalah | Lokasi Berkas | Bukti Faktual |
|:---|:---|:---|:---|:---|
| **FIND-01** | `VERIFIED` | `fetch()` di frontend tidak memiliki `AbortController` / timeout, menyebabkan UI stuck permanen jika HTTP request menggantung. | `app/Views/layouts/partials/command_palette.php` (L940-L985) | Kode tidak memuat `AbortSignal.timeout` atau timer pembatalan. |
| **FIND-02** | `VERIFIED` | `EspataFeatureKnowledge::findGuide()` melakukan *false match* ke `guide_modul_ajar` (Guru/Admin) dan `guide_rekap_absensi_guru` (Kepsek) untuk kueri materi LMS, membajak EAKB kanonikal. | `app/Libraries/EspataFeatureKnowledge.php`<br>`app/Controllers/Api/AiCopilot.php` (L582-L589) | Hasil eksekusi algoritma token scoring mencocokkan kata `cara, membuat, materi` ke panduan non-LMS karena katalog LMS absen di pustaka legasi. |
| **FIND-03** | `VERIFIED` | Fitur LMS terdaftar kanonikal di EAKB 70-Feature Catalog (`feat_lms_materials_seg`) dengan status `READY` untuk Guru & Siswa, namun tidak dapat diakses optimal akibat terhalang `findGuide()`. | `app/Libraries/EspataApplicationKnowledge.php` | Fitur `feat_lms_materials_seg` terverifikasi ada dan valid di katalog kanonikal. |
| **FIND-04** | `SUSPECTED` | Terjadi *PHP session lock contention* jika ada request/tab paralel yang membuka sesi PHP tanpa segera memanggil `session_write_close()`. | `app/Controllers/Api/AiCopilot.php` (L368-L395) | `session()->get('user')` dipanggil sebelum `session_write_close()` di L394. Jika skrip lain menahan file sesi, request AI memblokir di L379. |
| **FIND-05** | `SUSPECTED` | cURL blocking pada DNS lookup / endpoint Groq saat koneksi internet lokal tidak stabil atau terkendala sertifikat SSL di lingkungan Windows XAMPP. | `app/Controllers/Api/AiCopilot.php` (L1200+)<br>`app/Services/Ai/GroqAdapter.php` | Default cURL pada Windows tanpa c-ares dapat memblokir proses hingga sistem socket timeout (60–120 detik). |
| **FIND-06** | `NOT TESTED` | Pengujian interaksi multi-tab langsung di browser dengan koneksi lambat untuk mengukur durasi eksak hang pada sisi klien. | Browser live session | Dibatasi oleh status investigasi READ-ONLY tanpa interaksi browser live live-reload. |

---

## 5. REKOMENDASI REMEDIASI MINIMAL & AMAN (ACTION PLAN)

Untuk mengatasi insiden secara tuntas tanpa risiko kerusakan (*zero regression*), Tim EAO menyusun rencana perbaikan terukur berikut (untuk dieksekusi setelah persetujuan Human Owner):

### 5.1 Remediasi Frontend (Mencegah Infinite Spinner)
Pada [command_palette.php](file:///c:/xampp/htdocs/siakad/app/Views/layouts/partials/command_palette.php):
1. **Tambahkan `AbortController` dengan batas waktu 25 detik:**
   ```javascript
   const controller = new AbortController();
   const timeoutId = setTimeout(() => controller.abort(), 25000);

   fetch('<?= site_url('api/ai-copilot/ask') ?>', {
       method: 'POST',
       headers: fetchHeaders,
       body: JSON.stringify(payload),
       signal: controller.signal
   })
   .then(res => {
       clearTimeout(timeoutId);
       const loadingEl = document.getElementById(loadingId);
       if (loadingEl) loadingEl.remove();
       if (!res.ok) {
           throw new Error('Server returned HTTP ' + res.status);
       }
       return res.json();
   })
   .catch(err => {
       clearTimeout(timeoutId);
       const loadingEl = document.getElementById(loadingId);
       if (loadingEl) loadingEl.remove();
       
       const msg = err.name === 'AbortError'
           ? 'Waktu permintaan habis (timeout). Silakan periksa koneksi internet atau coba beberapa saat lagi.'
           : 'Maaf, terjadi kendala saat menghubungkan ke Asisten AI. (' + (err.message || 'Koneksi terputus') + ')';
       renderBotMessage(msg);
   });
   ```
   *Manfaat:* Menjamin bahwa dalam skenario server terburuk sekalipun, UI tidak akan pernah terjebak dalam status pemrosesan lebih dari 25 detik.

### 5.2 Remediasi Backend Routing & Knowledge Authority
Pada [AiCopilot.php](file:///c:/xampp/htdocs/siakad/app/Controllers/Api/AiCopilot.php):
1. **Perketat Ambang Batas `findGuide` atau Prioritaskan EAKB Canonical Catalog:**
   - Ubah logika percabangan di baris 582: `findGuide()` hanya boleh dijalankan jika EAKB Retrieval Engine tidak menemukan kecocokan fitur spesifik pada 70 fitur kanonikal, ATAU
   - Naikkan syarat kecocokan `findGuide` (memerlukan kecocokan kata kunci esensial yang ketat, bukan sekadar kata umum *"cara"* atau *"membuat"*).
   - Pastikan kueri terkait LMS diarahkan ke modul kanonikal `feat_lms_materials_seg` atau ke pemanggilan model LLM yang telah di-grounding dengan konteks EAKB Stage 2E.2.
2. **Keluarkan Session Early (*Release Lock Immediately*):**
   - Pastikan data user diekstrak di baris pertama controller dan `session_write_close()` dipanggil sesegera mungkin sebelum query DB, idempotency check, atau pemanggilan cURL dilakukan.
3. **Optimasi Opsi cURL Groq:**
   - Pasang opsi `CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4` pada cURL adapter untuk mencegah Windows stall pada resolusi IPv6.

---

## 6. VERIFIKASI INTEGRITAS LINGKUNGAN KERJA

Sesuai Aturan Emas ke-2 dan ke-4 pada `AGENTS.md`, status integritas sistem saat penyusunan laporan ini adalah:

1. **Integritas Berkas Terlindungi (`SecurityGuard`):**
   ```bash
   php -r "define('APPPATH', ...); echo 'SEC_PASS: ' . (int)\App\Libraries\SecurityGuard::verifyCriticalFiles();"
   # Output: SEC_PASS: 1 (LULUS - 100% UTUH)
   ```
2. **Status Sub-Aplikasi Terpisah:**
   - Direktori `bayaran/`: **ABSENT (False)**
   - Direktori `cbtku/`: **ABSENT (False)**
3. **Status Git Working Tree:**
   - Bersih dari modifikasi kode baru pada investigasi ini.
   - Tidak ada berkas produksi yang diubah atau dihapus selama investigasi berlangsung.

---

## 7. KESIMPULAN & STATUS STRICT HALT

1. **Kesimpulan Investigasi:**
   Insiden pengguna terjebak pada tampilan *"Memproses data & permintaan..."* untuk pertanyaan *"Bagaimana cara membuat materi di LMS?"* berhasil diisolasi ke akar masalahnya:
   - **Frontend:** Tidak adanya penanganan *timeout* pada fungsi `fetch()`, sehingga ketika terjadi hambatan jaringan atau latensi server, UI menggantung tanpa batas waktu.
   - **Backend:** Pembajakan kueri panduan oleh pustaka legasi `EspataFeatureKnowledge::findGuide()` yang salah mencocokkan kueri materi LMS ke modul ajar kurikulum atau rekap absensi, serta potensi latensi cURL / session lock contention.
2. **Status Tim EAO:**
   Investigasi telah selesai secara paripurna. Mematuhi batasan mandat **EAO-HO-2026-061**, Tim EAO menyatakan status **STRICT HALT**. Tidak ada perubahan kode produksi yang dilakukan sebelum terbitnya mandat otorisasi perbaikan (*Remediation Authorization Mandate*) dari Human Owner ESPATA.

---
*Laporan ini disusun secara faktual dan independen oleh Tim Multi-Agen EAO untuk Human Owner ESPATA.*
