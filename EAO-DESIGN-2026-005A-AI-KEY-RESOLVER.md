# DOKUMEN SPESIFIKASI DESAIN TEKNIS
## AI KEY RESOLVER & MULTI-ACCOUNT GOVERNANCE SPECIFICATION

**Nomor Dokumen:** EAO-DESIGN-2026-005A-AI-KEY-RESOLVER  
**Nomor Mandat:** EAO-MANDATE-2026-005  
**Tanggal:** 15 November 2026  
**Status:** DRAFT DESAIN TEKNIS — MENUNGGU PERSETUJUAN HUMAN OWNER  
**Komponen Utama:** `App\Services\Ai\AiKeyResolver` & `App\Services\Ai\DTO\ResolvedApiKey`  
**File Produksi:** DILARANG DIUBAH (Zero Production Modification)

---

## 1. PENDAHULUAN & PRINSIP DESAIN

Untuk mendukung kebijakan Human Owner mengenai **model API Key per akun (BYOK)**, ESPATA memerlukan komponen terpusat yang bertanggung jawab memetakan identitas pengguna, menemukan kunci yang sah, dan menegakkan batasan fallback secara aman.

Komponen ini dirancang dengan pola *Single Responsibility Principle (SRP)* dalam bentuk service terdedikasi:
```
App\Services\Ai\AiKeyResolver
```

### Prinsip Utama:
1. **Zero Secret Leak:** Objek kunci tidak pernah mengekspos nilai mentah string kunci melalui `__toString()`, `json_encode()`, `var_dump()`, atau exception message.
2. **Deterministic Role-Based Fallback:** Administrator ditentukan melalui verifikasi peran (`role_id = 1` dan `is_active = 1`), bukan sekadar mengasumsikan angka `id = 1`.
3. **Strict Teacher Isolation:** Tidak ada logika rekursif atau query fallback yang menyentuh tabel `teachers` selain baris milik guru yang sedang login.
4. **Dual-Traceability Ready:** Menyediakan metadata akuntabilitas: siapa yang meminta (*requester*) dan siapa pemilik kunci (*key owner*).

---

## 2. JAWABAN LENGKAP ATAS 8 PERTANYAAN KUNCI MANDAT

### Pertanyaan 1: Bagaimana identitas pengguna aktif diperoleh secara aman?
- **Mekanisme:** Identitas diperoleh melalui sesi server CodeIgniter 4 yang telah diautentikasi:
  ```php
  $user = session()->get('user');
  ```
- **Validasi Integritas:**
  - Memeriksa `session_status() === PHP_SESSION_ACTIVE` dan `$user !== null`.
  - Mengambil `user_id = (int) ($user['id'] ?? 0)`.
  - Mengambil `role_id = (int) ($user['role_id'] ?? 0)`.
  - Mengambil `teacher_id = (int) ($user['related_id'] ?? 0)` jika `role_id === 3`.
- **Anti-IDOR:** Resolver menolak pembacaan kunci jika parameter `user_id` tidak cocok dengan sesi aktif, mencegah manipulasi identitas (*tampering*).

---

### Pertanyaan 2: Bagaimana API Key milik pengguna ditemukan?
- **Jika Pengguna adalah Guru (`role_id === 3`):**
  Resolver melakukan query spesifik pada tabel `teachers` dengan filter ketat:
  ```php
  $teacher = $this->db->table('teachers')
      ->select('id, name, gemini_api_key, ai_provider')
      ->where('id', $teacherId)
      ->get()->getRowArray();
  ```
- **Jika Pengguna adalah Admin atau Peran Lain (`role_id !== 3`):**
  Resolver melakukan query pada tabel `users`:
  ```php
  $userData = $this->db->table('users')
      ->select('id, username, gemini_api_key, ai_provider')
      ->where('id', $userId)
      ->get()->getRowArray();
  ```
- Kunci dibersihkan (`trim()`) dan diperiksa validitasnya.

---

### Pertanyaan 3: Bagaimana API Key Administrator ditentukan tanpa asumsi ID yang tidak tervalidasi?
- **Masalah Kode Lama:** Modul lama mengasumsikan `id = 1` atau sekadar `ORDER BY id ASC LIMIT 1` tanpa memvalidasi apakah baris tersebut benar-benar seorang Administrator aktif.
- **Rancangan Aman `AiKeyResolver`:**
  Resolver melakukan query terverifikasi pada tabel `users` dengan kriteria ketat:
  ```php
  $adminUser = $this->db->table('users')
      ->select('id, username, gemini_api_key, ai_provider')
      ->where('role_id', 1)            // Wajib berstatus Administrator Resmi
      ->where('is_active', 1)          // Wajib berstatus akun aktif
      ->where('gemini_api_key !=', '') // Wajib memiliki kunci
      ->where('gemini_api_key IS NOT NULL')
      ->orderBy('id', 'ASC')           // Mengambil Super Admin utama jika ada > 1
      ->get()->getRowArray();
  ```
- Pendekatan ini menjamin bahwa akun fallback adalah Administrator yang sah, aktif, dan terdaftar di tabel `roles`.

---

### Pertanyaan 4: Bagaimana memastikan tidak ada pencarian API Key guru lain?
- **Jaminan Arsitektural:**
  - Metode `resolveTeacherKey()` **HANYA** memuat satu parameter: `$teacherId`.
  - Query ke tabel `teachers` menggunakan `->where('id', $teacherId)`.
  - Jika query mengembalikan nilai kosong atau tidak valid, resolver **SEGERA KELUAR DARI LOGIKA TABEL TEACHERS**.
  - Resolver **DILARANG KERAS** memuat query fallback yang mengurutkan atau memindai tabel `teachers` secara global.
  - Skenario pengujian otomatis REG-05 memvalidasi bahwa query sembarang guru bernilai 0.

---

### Pertanyaan 5: Bagaimana membedakan sumber dan pemilik kunci?
Resolver mengembalikan objek DTO `ResolvedApiKey` dengan penandaan eksplisit:
1. **Kunci Akun Sendiri:**
   - `keyOwnerId = $requesterUserId`
   - `keySource = 'self'`
   - `isFallback = false`
2. **Kunci Fallback Administrator:**
   - `keyOwnerId = $adminUser['id']`
   - `keySource = 'admin_fallback'`
   - `isFallback = true`
3. **Kunci Global Server (.env):**
   - `keyOwnerId = null`
   - `keySource = 'env_global'`
   - `isFallback = true`
4. **Tidak Ada Kunci (Offline):**
   - `keyOwnerId = null`
   - `keySource = 'none'`
   - `isFallback = false`

---

### Pertanyaan 6: Bagaimana menangani API Key kosong atau tidak valid?
- **Pemeriksaan Sintaksis Kunci:**
  - Panjang kunci wajib $> 10$ karakter.
  - Prefiks kunci diperiksa:
    - Kunci Groq: wajib diawali dengan `gsk_`.
    - Kunci Gemini: wajib diawali dengan `AIzaSy`.
- **Penanganan Ketidaksesuaian (*Provider Mismatch*):**
  - Jika pengguna memilih provider `groq` tetapi kuncinya diawali `AIzaSy` (atau sebaliknya):
    Resolver melakukan normalisasi cerdas (*smart auto-detection*) berdasarkan prefiks kunci aktual, atau menandai `isValid = false` jika terjadi konflik eksplisit.
- **Jika Seluruh Sumber Kosong:**
  - Resolver mengembalikan `ResolvedApiKey` dengan status `isValid = false`.
  - `UnifiedAiService` tidak akan melakukan panggilan cURL jaringan, melainkan langsung mengembalikan `AiResponse::createFailure('Kunci API belum diatur...', 'AUTH_ERROR')`, memicu fallback offline lokal (Smart Local Matcher / Curated Matrix) tanpa menimbulkan galat HTTP 500.

---

### Pertanyaan 7: Bagaimana menangani provider yang mengalami rate limit?
- **Kepatuhan Kebijakan:**
  Sesuai arahan Human Owner pada Mandat 2.2:
  *"Provider rate limit: Tidak otomatis menggunakan kunci guru lain."*
- **Sikap Sistem:**
  - Jika kunci guru terkena HTTP 429 (`RATE_LIMIT_429`), sistem **TIDAK BOLEH** secara diam-diam memindahkan tagihan ke kunci guru lain atau admin.
  - Sistem mengembalikan pesan informatif:
    *"Batas kuota panggilan per menit (Rate Limit) pada API Key Anda telah tercapai. Harap tunggu beberapa saat sebelum mencoba kembali."*
  - Modul beralih ke mode offline lokal jika tersedia.

---

### Pertanyaan 8: Bagaimana mencegah kebocoran kunci melalui exception atau logging?
- Objek `ResolvedApiKey` mengimplementasikan proteksi enkapsulasi:
  ```php
  public function __toString(): string
  {
      return '[PROTECTED_API_KEY]';
  }
  public function jsonSerialize(): mixed
  {
      return [
          'provider'   => $this->provider,
          'key_source' => $this->keySource,
          'is_valid'   => $this->isValid,
          'masked_key' => $this->getMaskedKey(), // Contoh: gsk_...3df
      ];
  }
  ```
- String kunci asli hanya dapat diakses melalui metode privat/terbatas `getRawSecret()`, yang hanya dipanggil oleh adapter cURL saat memasang header HTTP `Authorization: Bearer <key>`.
- Pada `GroqAdapter` dan `GeminiAdapter`, fungsi `maskSecret()` menggantikan string kunci asli menjadi `[API_KEY_MASKED]` sebelum exception atau error log dicatat.

---

## 3. RANCANGAN STRUKTUR DTO & KELAS RESOLVER

### A. Desain DTO: `App\Services\Ai\DTO\ResolvedApiKey`
```php
namespace App\Services\Ai\DTO;

class ResolvedApiKey implements \JsonSerializable
{
    private ?string $rawSecret;
    public string $provider;
    public ?int $keyOwnerId;
    public string $keySource;
    public bool $isFallback;
    public bool $isValid;
    public ?string $validationError;

    public function __construct(
        ?string $rawSecret,
        string $provider = 'unknown',
        ?int $keyOwnerId = null,
        string $keySource = 'none',
        bool $isFallback = false,
        bool $isValid = false,
        ?string $validationError = null
    ) {
        $this->rawSecret       = $rawSecret;
        $this->provider        = strtolower(trim($provider));
        $this->keyOwnerId      = $keyOwnerId;
        $this->keySource       = $keySource;
        $this->isFallback      = $isFallback;
        $this->isValid         = $isValid;
        $this->validationError = $validationError;
    }

    public function getRawSecret(): ?string
    {
        return $this->rawSecret;
    }

    public function isAvailable(): bool
    {
        return $this->isValid && !empty($this->rawSecret);
    }

    public function getMaskedKey(): string
    {
        if (empty($this->rawSecret)) return 'none';
        $len = strlen($this->rawSecret);
        if ($len < 8) return '***';
        return substr($this->rawSecret, 0, 4) . '...' . substr($this->rawSecret, -4);
    }

    public function __toString(): string
    {
        return '[PROTECTED_API_KEY]';
    }

    public function jsonSerialize(): mixed
    {
        return [
            'provider'   => $this->provider,
            'key_source' => $this->keySource,
            'owner_id'   => $this->keyOwnerId,
            'is_fallback'=> $this->isFallback,
            'is_valid'   => $this->isValid,
            'masked_key' => $this->getMaskedKey(),
        ];
    }
}
```

### B. Desain Service: `App\Services\Ai\AiKeyResolver`
```php
namespace App\Services\Ai;

use App\Services\BaseService;
use App\Services\Ai\DTO\ResolvedApiKey;
use Config\AiConfig;

class AiKeyResolver extends BaseService
{
    protected AiConfig $config;

    public function __construct(?AiConfig $config = null, $db = null)
    {
        parent::__construct($db);
        $this->config = $config ?: config('AiConfig');
    }

    /**
     * Resolusi Kunci API Terpadu Sesuai Kebijakan Human Owner
     */
    public function resolve(
        ?int $requesterUserId = null,
        ?int $requesterRoleId = null,
        ?int $teacherId = null,
        ?string $targetProvider = null
    ): ResolvedApiKey {
        $provider = strtolower(trim($targetProvider ?: $this->config->defaultProvider));

        // 1. Prioritas 1: Kunci Akun Pribadi Pengguna
        $selfKey = $this->resolveUserSelfKey($requesterUserId, $requesterRoleId, $teacherId, $provider);
        if ($selfKey && $selfKey->isAvailable()) {
            return $selfKey;
        }

        // 2. Prioritas 2: Kunci Administrator sebagai Fallback Resmi Sekolah
        $adminFallback = $this->resolveAdminFallbackKey($provider);
        if ($adminFallback && $adminFallback->isAvailable()) {
            return $adminFallback;
        }

        // 3. Prioritas 3: Kunci Global Server dari file .env (Jika Diizinkan)
        $envKey = $this->resolveEnvKey($provider);
        if ($envKey && $envKey->isAvailable()) {
            return $envKey;
        }

        // 4. Jika Seluruh Sumber Kosong
        return new ResolvedApiKey(
            null,
            $provider,
            null,
            'none',
            false,
            false,
            'Kunci API belum dikonfigurasi di akun pengguna, akun Admin, maupun .env.'
        );
    }

    protected function resolveUserSelfKey(?int $userId, ?int $roleId, ?int $teacherId, string $targetProvider): ?ResolvedApiKey
    {
        if (!$userId || !$this->db) return null;

        // Guru (Role 3): Baca tabel teachers secara spesifik
        if ($roleId === 3 && $teacherId) {
            $t = $this->db->table('teachers')
                ->select('id, gemini_api_key, ai_provider')
                ->where('id', $teacherId)
                ->get()->getRowArray();

            if (!empty($t['gemini_api_key'])) {
                $raw = trim($t['gemini_api_key']);
                $provider = !empty($t['ai_provider']) ? $t['ai_provider'] : $this->detectProviderFromPrefix($raw);
                if (strlen($raw) > 10) {
                    return new ResolvedApiKey($raw, $provider, $userId, 'self', false, true);
                }
            }
        } else {
            // Admin (Role 1) atau Peran Lain: Baca tabel users secara spesifik
            $u = $this->db->table('users')
                ->select('id, gemini_api_key, ai_provider')
                ->where('id', $userId)
                ->get()->getRowArray();

            if (!empty($u['gemini_api_key'])) {
                $raw = trim($u['gemini_api_key']);
                $provider = !empty($u['ai_provider']) ? $u['ai_provider'] : $this->detectProviderFromPrefix($raw);
                if (strlen($raw) > 10) {
                    return new ResolvedApiKey($raw, $provider, $userId, 'self', false, true);
                }
            }
        }

        return null;
    }

    protected function resolveAdminFallbackKey(string $targetProvider): ?ResolvedApiKey
    {
        if (!$this->db) return null;

        $admin = $this->db->table('users')
            ->select('id, gemini_api_key, ai_provider')
            ->where('role_id', 1)
            ->where('is_active', 1)
            ->where('gemini_api_key !=', '')
            ->where('gemini_api_key IS NOT NULL')
            ->orderBy('id', 'ASC')
            ->get()->getRowArray();

        if ($admin && !empty($admin['gemini_api_key'])) {
            $raw = trim($admin['gemini_api_key']);
            $provider = !empty($admin['ai_provider']) ? $admin['ai_provider'] : $this->detectProviderFromPrefix($raw);
            if (strlen($raw) > 10) {
                return new ResolvedApiKey($raw, $provider, (int)$admin['id'], 'admin_fallback', true, true);
            }
        }

        return null;
    }

    protected function resolveEnvKey(string $targetProvider): ?ResolvedApiKey
    {
        $raw = '';
        if ($targetProvider === 'groq') {
            $raw = $this->config->groq['apiKey'] ?? '';
        } elseif ($targetProvider === 'gemini') {
            $raw = $this->config->gemini['apiKey'] ?? '';
        }

        $raw = trim((string)$raw);
        if (strlen($raw) > 10) {
            return new ResolvedApiKey($raw, $targetProvider, null, 'env_global', true, true);
        }

        return null;
    }

    protected function detectProviderFromPrefix(string $key): string
    {
        if (str_starts_with($key, 'gsk_')) return 'groq';
        if (str_starts_with($key, 'AIzaSy')) return 'gemini';
        return $this->config->defaultProvider;
    }
}
```

---

## 4. DUKUNGAN GROQ BYOK PADA GROQADAPTER

### Perubahan Desain yang Diperlukan pada `GroqAdapter.php`:
Pada baris 51, ubah pembacaan kunci dari yang semula statis:
```php
// SEBELUMNYA:
$apiKey = $this->config->groq['apiKey'];
```
Menjadi hierarkis dengan memprioritaskan override kunci:
```php
// SESUDAHNYA (DIREKOMENDASIKAN UNTUK FASE 1B):
$apiKey = !empty($request->apiKeyOverride) 
    ? $request->apiKeyOverride 
    : $this->config->groq['apiKey'];
```
Dengan perubahan satu baris ini:
- `GroqAdapter` langsung mendukung kunci pribadi guru (`BYOK`).
- `GroqAdapter` langsung mendukung kunci fallback Administrator (`admin_fallback`).
- Kompatibilitas terhadap mock unit test tetap terjaga 100%.

---

## 5. DESAIN TELEMETRI AUDIT PENGGUNAAN (DUAL-TRACEABILITY)

Untuk memenuhi Ketentuan #6 Human Owner (*Penggunaan API Key harus dapat ditelusuri berdasarkan pengguna yang melakukan permintaan dan pemilik API Key yang digunakan*):

### A. Strategi Kompatibilitas Tanpa Migrasi Database (Fase 1 Segera)
Untuk menjaga aturan **DILARANG MENGUBAH DATABASE**, telemetri akuntabilitas ganda disematkan pada kolom `source` dan `intent` yang sudah ada pada tabel `ai_assistant_logs`:
- Kolom `user_id`: Menyimpan `requester_id` (pengguna yang mengklik/meminta).
- Kolom `source`: Menyimpan format terstruktur:
  `{provider}:{key_source}:{key_owner_id}`  
  *Contoh Riil:*
  - `groq:self:12` (Guru ID 12 menggunakan kunci pribadinya sendiri).
  - `groq:admin_fallback:1` (Guru tanpa kunci menggunakan subsidi Admin ID 1).
  - `groq:env_global:0` (Permintaan sistem menggunakan kunci server .env).
- Kolom `intent`: Menyimpan metadata model dan token:
  `AI_COMPLETION:{model}:{total_tokens}tok:{latency_ms}ms`

### B. Usulan Skema Migrasi Database Masa Depan (Untuk Persetujuan Human Owner)
Ketika Human Owner mengizinkan migrasi basis data, tabel `ai_assistant_logs` disarankan diperkaya dengan kolom formal:

```sql
-- DRAF MIGRASI MASA DEPAN (TIDAK DIJALANKAN DI FASE 1A):
ALTER TABLE `ai_assistant_logs`
    ADD COLUMN `key_owner_id` INT(11) NULL AFTER `user_id`,
    ADD COLUMN `key_source` VARCHAR(30) NOT NULL DEFAULT 'unknown' AFTER `key_owner_id`,
    ADD COLUMN `provider` VARCHAR(30) NOT NULL DEFAULT 'groq' AFTER `source`,
    ADD COLUMN `model` VARCHAR(50) NULL AFTER `provider`,
    ADD COLUMN `prompt_tokens` INT(11) NOT NULL DEFAULT 0 AFTER `model`,
    ADD COLUMN `completion_tokens` INT(11) NOT NULL DEFAULT 0 AFTER `prompt_tokens`,
    ADD COLUMN `total_tokens` INT(11) NOT NULL DEFAULT 0 AFTER `completion_tokens`,
    ADD COLUMN `latency_ms` INT(11) NOT NULL DEFAULT 0 AFTER `total_tokens`,
    ADD COLUMN `error_code` VARCHAR(50) NULL AFTER `latency_ms`,
    ADD INDEX `idx_requester_time` (`user_id`, `created_at`),
    ADD INDEX `idx_keyowner_time` (`key_owner_id`, `created_at`),
    ADD CONSTRAINT `fk_ai_logs_key_owner` FOREIGN KEY (`key_owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
```

---

> ### 🛑 KONDISI BERHENTI (STOP CONDITION)
> Dokumen ini adalah rancangan spesifikasi teknis. Tidak ada file produksi atau skrip migrasi yang dieksekusi.  
> Pembuatan berkas `AiKeyResolver.php` baru dapat dilakukan setelah persetujuan resmi tertulis dari Human Owner pada Fase 1B.
