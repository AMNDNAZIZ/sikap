# ASIS AI TECHNICAL DESIGN
**Spesifikasi Desain Teknis Arsitektur Unified AI Service Layer ESPATA**
*Dokumen Arsitektur Mandat EAO-002B — Disempurnakan oleh AI System Architect & AI Security Reviewer*

---

| Parameter | Keterangan Teknis |
|:---|:---|
| **Nama Komponen** | `App\Services\Ai\UnifiedAiService` & Provider Adapters |
| **Pola Arsitektur** | Service Layer + Adapter Pattern + Fast Triage Gateway |
| **Namespace Inti** | `App\Services\Ai\` |
| **Lokasi Konfigurasi** | `app/Config/AiConfig.php` |
| **Status Dokumen** | **Rancangan Teknis Final Tervalidasi (Menunggu Otorisasi Pemilik Proyek)** |
| **Status Auto-Failover**| **NONAKTIF MUTLAK (Manual / Graceful Degradation Only)** |
| **Anggaran Token Presisi**| 3.000 Input (inc. 200 Overhead) + 1.096 Output (Chat) = 4.096 Hard Cap |
| **Kepatuhan Hosting** | PHP 8.1+, Shared Hosting (cPanel/LiteSpeed/Apache), IPv4 DNS Resilient |

---

## 1. Latar Belakang & Analisis Masalah Faktual

Berdasarkan audit faktual pada repositori ESPATA, implementasi pemanggilan AI saat ini mengalami **fragmentasi struktural (*siloed implementations*)**:

1. **`app/Controllers/Api/AiCopilot.php`:**
   - Memanggil langsung Google Gemini API (`gemini-1.5-flash`) via cURL native di dalam controller.
   - Belum terhubung ke Groq API.
   - Menggabungkan logika routing, otentikasi, pencocokan intent kueri lokal, dan koneksi HTTP pihak ketiga dalam satu file monolitik.

2. **`app/Controllers/Admin/Kokurikuler.php`:**
   - Memiliki method mandiri `callAIPlanningAPI()` yang mengimplementasikan pemanggilan Groq (`openai/gpt-oss-20b`, `qwen/qwen3.8-27b`) dan Gemini (`gemini-2.0-flash`, `gemini-1.5-flash`).
   - Menerapkan penanganan timeout IPv6 via `CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4` dan `CURLOPT_SSL_VERIFYHOST => 0`.

3. **`app/Services/ElearningPackageGeneratorService.php`:**
   - Memiliki resolver API Key dan cURL tersendiri untuk memproses pembuatan paket materi ajar.

4. **`app/Controllers/Admin/AdministrasiGuru.php`:**
   - Memiliki fungsi pembuatan modul ajar mandiri via pemanggilan API Gemini.

### Dampak Buruk Fragmentasi:
- **Duplikasi Kode:** Kode konfigurasi cURL, header otorisasi, penanganan timeout, dan parsing respon terduplikasi di sedikitnya 4 file berbeda.
- **Inkonsistensi Penanganan Error:** Jika salah satu API down atau limit tercapai, mekanisme tanggapan error berbeda-beda di setiap controller.
- **Kesulitan Rotasi Model:** Perubahan versi model mengharuskan modifikasi manual di banyak berkas.

---

## 2. Arsitektur Unified AI Service Layer

Untuk menyelesaikan fragmentasi tersebut tanpa mengganggu kestabilan sistem yang sedang berjalan, dirancang arsitektur **Unified AI Service Layer** berbasis antarmuka (*Interface*) dan adaptor (*Adapter Pattern*).

### A. Diagram Komponen Arsitektur

```
┌────────────────────────────────────────────────────────────────────────┐
│                        KLIEN / KONSUMEN APLIKASI                       │
│  (Tahap 1: AiCopilot.php ──► Tahap 2: Kokurikuler, Modul Ajar, CBT)    │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│               App\Services\Ai\UnifiedAiService (Pintu Utama)           │
│  - Membaca konfigurasi (AiConfig.php) & Resolver Kunci API (BYOK/Env) │
│  - Menjamin Pembersihan Data Pribadi (Zero PII Scrubber)              │
│  - Pelepasan Kunci Sesi PHP (session_write_close)                     │
│  - Pemulihan Pesan Pending Kedaluwarsa (> 60 Detik)                   │
│  - Pencatatan Penggunaan Token & Metrik Waktu Respon                   │
└───────────────────┬────────────────────────────────┬───────────────────┘
                    │                                │
                    ▼                                ▼
   ┌────────────────────────────────┐ ┌────────────────────────────────┐
   │ App\Services\Ai\Providers\     │ │ App\Services\Ai\Providers\     │
   │ GroqAdapter (Kandidat Utama)   │ │ GeminiAdapter (Kandidat Cadang)│
   │ (Implements AiProviderInterface│ │ (Implements AiProviderInterface│
   │ - Format: OpenAI-compatible    │ │ - Format: Google GenAI REST    │
   │ - Kandidat: openai/gpt-oss-20b │ │ - Kandidat: gemini-2.0-flash   │
   │ - cURL IPv4 Resilient          │ │ - cURL IPv4 Resilient          │
   └────────────────────────────────┘ └────────────────────────────────┘
                    │                                │
                    ▼                                ▼
             [ GROQ REST API ]              [ GEMINI REST API ]
```

---

## 3. Spesifikasi Kontrak Interface & Data Transfer Object (DTO)

### A. Interface Kontrak: `App\Services\Ai\Contracts\AiProviderInterface`

Setiap provider AI wajib mengimplementasikan interface baku:

```php
namespace App\Services\Ai\Contracts;

use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;

interface AiProviderInterface
{
    /**
     * Mendapatkan nama identitas provider ('groq', 'gemini')
     */
    public function getName(): string;

    /**
     * Memeriksa ketersediaan provider (API key valid dan terkonfigurasi)
     */
    public function isAvailable(): bool;

    /**
     * Mengirim prompt teks / chat ke provider AI
     */
    public function generateText(AiRequest $request): AiResponse;

    /**
     * Mengirim permintaan dengan ekspektasi keluaran JSON terstruktur
     */
    public function generateJson(AiRequest $request): AiResponse;
}
```

### B. Data Transfer Object (DTO): `AiRequest` & `AiResponse`

1. **`App\Services\Ai\DTO\AiRequest`:**
   - `string $prompt`: Instruksi utama atau pertanyaan pengguna yang sudah disanitasi.
   - `?string $systemInstruction`: Instruksi sistem (aturan peran, batasan Zero PII, format respons).
   - `array $conversationHistory`: Riwayat pesan multi-turn format standar: `[['role' => 'user'|'assistant', 'content' => '...']]`.
   - `?string $model`: Override nama model opsional (jika kosong, menggunakan default provider).
   - `float $temperature`: Derajat kreativitas (default: 0.2 untuk konsistensi data, 0.7 untuk narasi modul).
   - `int $maxTokens`: Batas maksimal token keluaran (default: 1.096 untuk chat copilot, 2.048 untuk generator dokumen).
   - `int $timeout`: Batas waktu HTTP request dalam detik (default: 25s).
   - `bool $jsonMode`: Menandai apakah respons harus dipaksa berupa JSON valid.
   - `?string $apiKeyOverride`: Kunci API spesifik (untuk mendukung mode BYOK guru pada Gemini).

2. **`App\Services\Ai\DTO\AiResponse`:**
   - `bool $success`: Status keberhasilan request (`true`/`false`).
   - `?string $text`: Teks hasil keluaran AI.
   - `?array $jsonData`: Hasil keluaran yang sudah di-parse ke array PHP (jika mode JSON).
   - `string $provider`: Nama provider yang memproses (`groq` atau `gemini`).
   - `string $model`: Nama model yang sebenarnya digunakan.
   - `int $promptTokens`: Jumlah token masukan (termasuk protokol framing overhead).
   - `int $completionTokens`: Jumlah token keluaran.
   - `float $latencyMs`: Durasi respon jaringan dalam milidetik.
   - `?string $errorMessage`: Pesan kesalahan jika terjadi kegagalan.
   - `?string $errorCode`: Kode error internal (`TIMEOUT`, `RATE_LIMIT`, `AUTH_ERROR`, `API_ERROR`).

---

## 4. Struktur Konfigurasi: `App\Config\AiConfig.php`

Konfigurasi disimpan terpusat di `app/Config/AiConfig.php` dengan fallback ke `.env` tanpa menulis langsung kunci sensitif di dalam kode:

```php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class AiConfig extends BaseConfig
{
    /**
     * Provider default untuk interaksi umum (Asis AI Copilot)
     * Pilihan: 'groq' | 'gemini'
     */
    public string $defaultProvider = 'groq';

    /**
     * Provider cadangan jika provider utama mengalami kendala
     */
    public string $fallbackProvider = 'gemini';

    /**
     * Mengizinkan perpindahan otomatis saat provider utama error
     * CATATAN: Sesuai mandat, dinonaktifkan secara default (false)
     */
    public bool $enableAutoFailover = false;

    /**
     * Pengaturan Provider Groq (Kandidat Model Awal)
     * Catatan: Groq tidak mendukung BYOK (hanya kunci terpusat sekolah)
     */
    public array $groq = [
        'apiKey'          => '', // Diisi dari env('GROQ_API_KEY')
        'baseUrl'         => 'https://api.groq.com/openai/v1/chat/completions',
        'defaultModel'    => 'openai/gpt-oss-20b',
        'fastModel'       => 'llama-3.1-8b-instant',
        'complexModel'    => 'qwen/qwen3.8-27b',
        'timeout'         => 25,
        'connectTimeout'  => 5,
        'temperature'     => 0.2,
        'maxTokens'       => 1096, // Sesuai anggaran chat presisi
    ];

    /**
     * Pengaturan Provider Google Gemini (Kandidat Model Cadangan)
     * Catatan: Mendukung BYOK Guru dari teachers.gemini_api_key
     */
    public array $gemini = [
        'apiKey'          => '', // Diisi dari env('GEMINI_API_KEY')
        'baseUrl'         => 'https://generativelanguage.googleapis.com/v1beta/models/',
        'defaultModel'    => 'gemini-2.0-flash',
        'fallbackModel'   => 'gemini-1.5-flash',
        'timeout'         => 30,
        'connectTimeout'  => 5,
        'temperature'     => 0.2,
        'maxTokens'       => 1096,
    ];

    /**
     * Resiliensi Jaringan Shared Hosting
     */
    public bool $forceIpv4Resolve = true; // CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4

    public function __construct()
    {
        parent::__construct();

        $this->defaultProvider = env('AI_DEFAULT_PROVIDER', $this->defaultProvider);
        $this->fallbackProvider = env('AI_FALLBACK_PROVIDER', $this->fallbackProvider);
        $this->enableAutoFailover = (bool) env('AI_ENABLE_AUTO_FAILOVER', false); // Mutlak false bawaan

        $this->groq['apiKey'] = env('GROQ_API_KEY', '');
        $this->gemini['apiKey'] = env('GEMINI_API_KEY', '');
    }
}
```

---

## 5. Tata Kelola Kunci API (API Key Governance & Lifecycle)

Sistem menetapkan hierarki resolusi kunci secara ketat tanpa mengubah skema basis data eksisting:

```
                  ┌───────────────────────────────┐
                  │ Evaluasi Permintaan Pemanggilan│
                  └──────────────┬────────────────┘
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
1. **Kebijakan Pembatasan Provider BYOK:**
   - **BYOK HANYA BERLAKU UNTUK GOOGLE GEMINI.**
   - Guru diperbolehkan memasukkan kunci Gemini pribadi mereka pada tabel `teachers` (kolom `gemini_api_key`).
   - **Groq Cloud API TIDAK MENDUKUNG BYOK.** Akses Groq dikelola eksklusif melalui kunci institusi sekolah di `.env` (`GROQ_API_KEY`) guna menjamin konsistensi kuota, kecepatan, dan pengawasan sistem.
2. **Aturan Prioritas Penggunaan:**
   - *Tingkat 1:* Jika pemanggilan ditujukan ke Gemini dan akun guru yang login memiliki `gemini_api_key` valid di basis data, sistem memprioritaskan kunci BYOK guru tersebut.
   - *Tingkat 2:* Jika kunci guru kosong, sistem beralih menggunakan kunci bersama sekolah di `.env`.
   - *Tingkat 3:* Untuk Groq, sistem selalu menggunakan kunci sekolah di `.env`.
3. **Perlindungan Akses & Sanitasi:**
   - Kunci API **tidak pernah dikirimkan** ke browser pengguna via AJAX/HTML data attribute.
   - Pada form profil akun guru, nilai kunci di-masking secara visual (contoh: `AIzaSy...4xQ1`).
4. **Penyimpanan Eksisting Tanpa Perubahan:**
   - Tidak ada modifikasi atau migrasi baru terhadap tabel `teachers` dan `users` untuk urusan kunci API. Sistem memanfaatkan kolom `gemini_api_key` yang sudah eksis di database saat ini.

---

## 6. Protokol Resiliensi Shared Hosting & Pelepasan Session Lock

### A. Pelepasan Kunci Sesi (`session_write_close`)
Untuk mencegah pembekuan tab browser (*session locking*) selama cURL berjalan:
```php
public function execute(AiRequest $request): AiResponse
{
    // Lepaskan kunci sesi file sebelum memulai koneksi jaringan eksternal
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $response = $this->getActiveProvider()->generateText($request);
    return $response;
}
```

### B. Opsi cURL IPv4 Baku:
```php
curl_setopt_array($ch, [
    CURLOPT_URL            => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_CONNECTTIMEOUT => 5,                   // Batas sambung 5 detik
    CURLOPT_TIMEOUT        => $config->timeout,    // Batas respon 25 detik
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,   // Wajib IPv4 cegah hang DNS cPanel
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
```

---

## 7. Penanganan Kegagalan, Stale Pending, dan Status Auto-Failover

1. **Auto-Failover Tetap Dinonaktifkan:**
   - Parameter `enableAutoFailover = false`.
   - Jika Groq mengalami kendala jaringan atau rate limit, sistem **TIDAK** otomatis memanggil Gemini secara diam-diam.
2. **Pemulihan Stale Pending (> 60 Detik):**
   - Sebelum mengeksekusi request baru, sistem secara otomatis membersihkan pesan yang menggantung dengan mengubah statusnya dari `pending` $\rightarrow$ `timeout` (`error_code = 'STALE_PENDING_EXPIRED'`) sehingga pengguna tidak terkunci selamanya.
3. **Soft Tombstone Deletion Guard:**
   - Jika pengguna menghapus sesi saat cURL masih aktif, sesi ditandai `is_active = 0`. Begitu cURL selesai, hasilnya langsung dibuang tanpa ditulis ke basis data.
4. **Penanganan Anggun (Graceful Error Degradation):**
   - Sistem mengembalikan pesan santun kepada guru:
     *"Asis AI sedang mengalami antrean pemrosesan di server komputasi. Silakan gunakan menu panduan cepat offline di tab sebelah kiri."*
   - Kesalahan dicatat pada `ai_assistant_logs` (kolom `intent = 'ERROR_TIMEOUT'` atau `'ERROR_RATE_LIMIT'`).

---

## 8. Metrik Kinerja & Metode Pengukuran Nyata (Non-Asumtif)

Untuk mencegah klaim efisiensi yang tidak berdasar, ditetapkan metodologi pengukuran empiris berikut:

| Metrik Kinerja | Parameter Pengukuran | Metode Pengukuran Faktual | Target Realistis |
|:---|:---|:---|:---|
| **Latensi Jaringan** | Waktu pulang-pergi (*Round Trip Time*) HTTP request ke vendor AI. | Dihitung dengan fungsi `microtime(true)` di awal dan akhir koneksi cURL, dicatat dalam satuan milidetik pada `latency_ms`. | Rata-rata < 2.000 ms (Groq) |
| **Keberhasilan Respons** | Rasio pemanggilan sukses terhadap total permintaan. | $\frac{\sum \text{Status Completed}}{\sum \text{Total Permintaan}} \times 100\%$ terhitung dari tabel `ai_messages`. | Target: $\ge 98,5\%$ |
| **Konsumsi Token & Biaya** | Volume penggunaan token masukan dan keluaran. | Akumulasi `tokens_in` dan `tokens_out` per hari/bulan untuk evaluasi kapasitas kuota gratis vs berbayar. | Terpantau harian di dashboard admin |
| **Penghematan Waktu Guru** | Durasi riil penyusunan administrasi KBM (Modul Ajar, Rubrik CBT). | **Studi Waktu Kerja (Time-Motion Study)**: Mengukur waktu guru menyusun modul ajar secara manual vs menggunakan Asis AI melalui kuesioner terstandar. | Target Eksplorasi: Pengurangan waktu kerja signifikan |

---

## 9. Rencana Refactoring Bertahap: Tahap 1 pada `AiCopilot.php`

Konsolidasi kode dilakukan secara terkontrol:
1. **Langkah 1:** Pertahankan seluruh logika triage lokal (Live Query & Offline Knowledge Base) di `AiCopilot.php`.
2. **Langkah 2:** Ganti blok privat cURL Gemini di `AiCopilot.php` dengan pemanggilan `UnifiedAiService::execute()`.
3. **Langkah 3:** Verifikasi bahwa respons JSON dan sapaan suara VTTS tetap bekerja 100% normal tanpa regresi.
4. **Langkah 4:** Jalankan `SecurityGuard::verifyCriticalFiles()` untuk memastikan status lisensi tetap `SEC_PASS`.