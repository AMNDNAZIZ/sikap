<?php

namespace App\Services\Ai;

use App\Services\BaseService;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\DTO\ResolvedApiKey;
use App\Services\Ai\AiKeyResolver;
use App\Services\Ai\Memory\ConversationMemoryManager;
use App\Services\Ai\Providers\GroqAdapter;
use App\Services\Ai\Providers\GeminiAdapter;
use Config\AiConfig;

/**
 * Unified AI Service Layer ESPATA (UnifiedAiService)
 * 
 * Pintu gerbang terpadu komunikasi seluruh fitur ESPATA dengan vendor AI.
 * Bertanggung jawab atas:
 * 1. Resolusi provider dan pemilahan kunci API (BYOK Guru vs Kunci Sekolah).
 * 2. Pembersihan otomatis data pribadi sensitif (Zero PII Scrubber 4-Lapis).
 * 3. Pelepasan kunci sesi PHP (session_write_close) sebelum eksekusi jaringan,
 *    setelah seluruh data autentikasi sesi ditangkap dengan aman.
 * 4. Pengelolaan batas waktu (timeout), penanganan galat, dan telemetri audit minimalis.
 * 5. Kepatuhan kebijakan auto-failover (nonaktif secara default).
 */
class UnifiedAiService extends BaseService
{
    protected AiConfig $config;
    protected AiKeyResolver $keyResolver;
    protected AdminQuotaManager $quotaManager;
    protected RequestIdempotencyManager $idempManager;
    protected ConversationMemoryManager $memoryManager;
    /** @var array<string, AiProviderInterface> */
    protected array $providers = [];

    public function __construct(
        ?AiConfig $config = null,
        ?GroqAdapter $groqAdapter = null,
        ?GeminiAdapter $geminiAdapter = null,
        $db = null,
        ?AiKeyResolver $keyResolver = null,
        ?AdminQuotaManager $quotaManager = null,
        ?RequestIdempotencyManager $idempManager = null,
        ?ConversationMemoryManager $memoryManager = null
    ) {
        parent::__construct($db);

        $this->config = $config ?: config('AiConfig');
        $this->keyResolver = $keyResolver ?: new AiKeyResolver($this->config, $this->db);
        $this->quotaManager = $quotaManager ?: new AdminQuotaManager($this->db);
        $this->idempManager = $idempManager ?: new RequestIdempotencyManager($this->db);
        $this->memoryManager = $memoryManager ?: new ConversationMemoryManager($this->db);

        // Daftarkan adapter resmi bawaan sistem
        $groq = $groqAdapter ?: new GroqAdapter($this->config);
        $gemini = $geminiAdapter ?: new GeminiAdapter($this->config);

        $this->registerProvider($groq);
        $this->registerProvider($gemini);
    }

    public function getQuotaManager(): AdminQuotaManager
    {
        return $this->quotaManager;
    }

    public function setQuotaManager(AdminQuotaManager $quotaManager): void
    {
        $this->quotaManager = $quotaManager;
    }

    public function getIdempotencyManager(): RequestIdempotencyManager
    {
        return $this->idempManager;
    }

    public function setIdempotencyManager(RequestIdempotencyManager $idempManager): void
    {
        $this->idempManager = $idempManager;
    }

    public function getMemoryManager(): ConversationMemoryManager
    {
        return $this->memoryManager;
    }

    public function setMemoryManager(ConversationMemoryManager $memoryManager): void
    {
        $this->memoryManager = $memoryManager;
    }

    public function getKeyResolver(): AiKeyResolver
    {
        return $this->keyResolver;
    }

    public function setKeyResolver(AiKeyResolver $resolver): void
    {
        $this->keyResolver = $resolver;
    }

    /**
     * Mendaftarkan adapter provider ke dalam katalog engine
     */
    public function registerProvider(AiProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Mendapatkan objek provider berdasarkan nama
     */
    public function getProvider(string $name): ?AiProviderInterface
    {
        return $this->providers[strtolower(trim($name))] ?? null;
    }

    /**
     * Mendapatkan daftar seluruh provider yang terdaftar beserta status kesiapannya
     */
    public function getRegisteredProviders(): array
    {
        $status = [];
        foreach ($this->providers as $name => $adapter) {
            $status[$name] = [
                'name'      => $name,
                'available' => $adapter->isAvailable(),
                'class'     => get_class($adapter),
            ];
        }
        return $status;
    }

    /**
     * Menentukan provider aktif yang akan digunakan
     */
    public function resolveActiveProvider(?string $preferredProvider = null, ?string $customApiKey = null): AiProviderInterface
    {
        $target = $preferredProvider ?: $this->config->defaultProvider;
        $target = strtolower(trim($target));

        $provider = $this->getProvider($target);

        if ($provider !== null && $provider->isAvailable($customApiKey)) {
            return $provider;
        }

        // Jika provider yang diminta tidak tersedia, coba default provider
        $default = $this->getProvider($this->config->defaultProvider);
        if ($default !== null && $default->isAvailable($customApiKey)) {
            return $default;
        }

        // Jika default juga tidak tersedia, fallback ke provider cadangan jika terdaftar
        $fallback = $this->getProvider($this->config->fallbackProvider);
        if ($fallback !== null && $fallback->isAvailable($customApiKey)) {
            return $fallback;
        }

        // Jika semua tidak ada kunci yang valid, kembalikan provider default untuk memicu pesan error terstandar
        return $provider ?: ($default ?: reset($this->providers));
    }

    /**
     * Mengeksekusi permintaan teks/chat AI terpadu
     * Mendukung multi-account BYOK dan fallback resmi Administrator
     */
    public function execute(
        AiRequest $request,
        ?string $preferredProvider = null,
        ?array $userContext = null
    ): AiResponse {
        // 1. Tangkap seluruh data konteks sesi pengguna SEBELUM kunci sesi dilepaskan
        $sessionUserId = $userContext['user_id'] ?? (session_status() === PHP_SESSION_ACTIVE ? (session()->get('user_id') ?: null) : null);
        $sessionRoleId = $userContext['role_id'] ?? (session_status() === PHP_SESSION_ACTIVE ? (session()->get('role_id') ?: null) : null);
        $sessionTeacherId = $userContext['teacher_id'] ?? $userContext['related_id'] ?? (session_status() === PHP_SESSION_ACTIVE ? (session()->get('teacher_id') ?: session()->get('related_id') ?: null) : null);

        $clientMessageId = $userContext['client_message_id'] ?? null;
        $workerToken = $userContext['worker_token'] ?? null;

        $targetProviderName = $preferredProvider ?: $this->config->defaultProvider;

        // 2. Resolusi Kunci Terpadu via AiKeyResolver
        $resolvedKey = null;
        if (!empty($request->apiKeyOverride)) {
            $resolvedKey = $this->keyResolver->resolve(
                $sessionUserId,
                $sessionRoleId,
                $sessionTeacherId,
                $targetProviderName,
                $request->apiKeyOverride
            );
        } else {
            $resolvedKey = $this->keyResolver->resolve(
                $sessionUserId,
                $sessionRoleId,
                $sessionTeacherId,
                $targetProviderName
            );
        }

        // 3. Sanitasi Zero PII pada prompt, system instruction, dan riwayat sebelum keluar sistem
        $sanitizedRequest = $this->sanitizeZeroPii($request);

        // Pasang kunci hasil resolusi ke request
        if ($resolvedKey && $resolvedKey->isAvailable()) {
            $sanitizedRequest->apiKeyOverride = $resolvedKey->getRawSecret();
            if ($resolvedKey->provider !== 'unknown' && $resolvedKey->provider !== $targetProviderName) {
                $targetProviderName = $resolvedKey->provider;
            }
        }

        // 4. Tentukan provider yang akan memproses
        $provider = $this->resolveActiveProvider($targetProviderName, $sanitizedRequest->apiKeyOverride);

        // FASE 1C: Pengelolaan Kuota Admin jika sumber kunci adalah admin_fallback
        $quotaAcquired = false;
        $keySource = $resolvedKey ? $resolvedKey->keySource : 'none';
        if ($clientMessageId && $workerToken && $keySource === 'admin_fallback') {
            $acquireRes = $this->quotaManager->acquireSlot($clientMessageId, $workerToken, 'admin_fallback');
            if (!$acquireRes['success']) {
                $quotaFailure = AiResponse::createFailure(
                    'Kuota harian Asis AI sekolah telah mencapai batas maksimum (20 kueri/hari). Silakan gunakan kunci API pribadi Anda di profil.',
                    'QUOTA_EXHAUSTED',
                    $provider->getName(),
                    $sanitizedRequest->model ?: 'default'
                );
                $this->recordAuditTelemetry($sanitizedRequest, $quotaFailure, $sessionUserId, $sessionRoleId, $resolvedKey);
                return $quotaFailure;
            }
            $quotaAcquired = true;
        }

        // FASE 1C: Gerbang Pre-Dispatch (Worker Fencing & Point of No Return)
        if ($clientMessageId && $workerToken) {
            $gateOk = $this->idempManager->preDispatchGate($clientMessageId, $workerToken);
            if (!$gateOk) {
                if ($quotaAcquired) {
                    $this->quotaManager->executeAtomicRefund($clientMessageId, 'Pre-dispatch gate failed (worker fenced out)');
                }
                $fencedFailure = AiResponse::createFailure(
                    'Permintaan dibatalkan karena masa sewa (lease) telah berakhir atau diambil alih proses lain.',
                    'WORKER_FENCED_OUT',
                    $provider->getName(),
                    $sanitizedRequest->model ?: 'default'
                );
                return $fencedFailure;
            }
        }

        // 5. Protokol Pelepasan Kunci Sesi PHP (session_write_close)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // 6. Jika kunci tidak tersedia sama sekali, kembalikan respons kegagalan aman tanpa cURL
        if (empty($sanitizedRequest->apiKeyOverride) && !$provider->isAvailable()) {
            if ($quotaAcquired && $clientMessageId) {
                $this->quotaManager->executeAtomicRefund($clientMessageId, 'Auth error before cURL');
            }
            $failureResponse = AiResponse::createFailure(
                $resolvedKey ? $resolvedKey->validationError : ('Kunci API tidak tersedia untuk provider ' . $provider->getName()),
                'AUTH_ERROR',
                $provider->getName(),
                $sanitizedRequest->model ?: 'default'
            );
            $this->recordAuditTelemetry($sanitizedRequest, $failureResponse, $sessionUserId, $sessionRoleId, $resolvedKey);
            return $failureResponse;
        }

        // 7. Eksekusi pemanggilan ke provider terpilih
        $response = $provider->generateText($sanitizedRequest);

        // 8. Tangani Failover Terkendali (HANYA JIKA Diaktifkan secara Eksplisit di Config)
        if (!$response->success && $this->config->enableAutoFailover) {
            $response = $this->handleControlledFailover($sanitizedRequest, $provider->getName(), $response);
        }

        // FASE 1C: Persistensi Respon Sebelum Klien (Persist-Before-Respond)
        if ($clientMessageId && $workerToken) {
            if ($response->success) {
                $finalQuotaStatus = ($keySource === 'admin_fallback') ? 'COMMITTED' : 'NONE';
                $this->idempManager->persistResponseBeforeClient(
                    $clientMessageId,
                    $workerToken,
                    ['reply' => $response->content],
                    'COMPLETED',
                    $finalQuotaStatus
                );
            } else {
                $finalQuotaStatus = ($keySource === 'admin_fallback') ? 'COMMITTED' : 'NONE';
                $errStatus = ($response->errorCode === 'TIMEOUT') ? 'TIMEOUT' : 'PROVIDER_ERROR';
                $this->idempManager->recordFailure($clientMessageId, $workerToken, $errStatus, $finalQuotaStatus);
            }
        }

        // 9. Catat telemetri penggunaan ke log audit operasional (Dual-Traceability)
        $this->recordAuditTelemetry($sanitizedRequest, $response, $sessionUserId, $sessionRoleId, $resolvedKey);

        return $response;
    }

    /**
     * Mengeksekusi permintaan dengan penegasan format luaran JSON valid
     */
    public function executeJson(
        AiRequest $request,
        ?string $preferredProvider = null,
        ?array $userContext = null
    ): AiResponse {
        $jsonRequest = clone $request;
        $jsonRequest->jsonMode = true;

        $sessionUserId = $userContext['user_id'] ?? (session_status() === PHP_SESSION_ACTIVE ? (session()->get('user_id') ?: null) : null);
        $sessionRoleId = $userContext['role_id'] ?? (session_status() === PHP_SESSION_ACTIVE ? (session()->get('role_id') ?: null) : null);
        $sessionTeacherId = $userContext['teacher_id'] ?? $userContext['related_id'] ?? (session_status() === PHP_SESSION_ACTIVE ? (session()->get('teacher_id') ?: session()->get('related_id') ?: null) : null);

        $clientMessageId = $userContext['client_message_id'] ?? null;
        $workerToken = $userContext['worker_token'] ?? null;

        $targetProviderName = $preferredProvider ?: $this->config->defaultProvider;

        $resolvedKey = null;
        if (!empty($jsonRequest->apiKeyOverride)) {
            $resolvedKey = $this->keyResolver->resolve(
                $sessionUserId,
                $sessionRoleId,
                $sessionTeacherId,
                $targetProviderName,
                $jsonRequest->apiKeyOverride
            );
        } else {
            $resolvedKey = $this->keyResolver->resolve(
                $sessionUserId,
                $sessionRoleId,
                $sessionTeacherId,
                $targetProviderName
            );
        }

        $sanitizedRequest = $this->sanitizeZeroPii($jsonRequest);

        if ($resolvedKey && $resolvedKey->isAvailable()) {
            $sanitizedRequest->apiKeyOverride = $resolvedKey->getRawSecret();
            if ($resolvedKey->provider !== 'unknown' && $resolvedKey->provider !== $targetProviderName) {
                $targetProviderName = $resolvedKey->provider;
            }
        }

        $provider = $this->resolveActiveProvider($targetProviderName, $sanitizedRequest->apiKeyOverride);

        // FASE 1C: Pengelolaan Kuota Admin jika sumber kunci adalah admin_fallback
        $quotaAcquired = false;
        $keySource = $resolvedKey ? $resolvedKey->keySource : 'none';
        if ($clientMessageId && $workerToken && $keySource === 'admin_fallback') {
            $acquireRes = $this->quotaManager->acquireSlot($clientMessageId, $workerToken, 'admin_fallback');
            if (!$acquireRes['success']) {
                $quotaFailure = AiResponse::createFailure(
                    'Kuota harian Asis AI sekolah telah mencapai batas maksimum (20 kueri/hari). Silakan gunakan kunci API pribadi Anda di profil.',
                    'QUOTA_EXHAUSTED',
                    $provider->getName(),
                    $sanitizedRequest->model ?: 'default'
                );
                $this->recordAuditTelemetry($sanitizedRequest, $quotaFailure, $sessionUserId, $sessionRoleId, $resolvedKey);
                return $quotaFailure;
            }
            $quotaAcquired = true;
        }

        // FASE 1C: Gerbang Pre-Dispatch (Worker Fencing & Point of No Return)
        if ($clientMessageId && $workerToken) {
            $gateOk = $this->idempManager->preDispatchGate($clientMessageId, $workerToken);
            if (!$gateOk) {
                if ($quotaAcquired) {
                    $this->quotaManager->executeAtomicRefund($clientMessageId, 'Pre-dispatch gate failed (worker fenced out)');
                }
                $fencedFailure = AiResponse::createFailure(
                    'Permintaan dibatalkan karena masa sewa (lease) telah berakhir atau diambil alih proses lain.',
                    'WORKER_FENCED_OUT',
                    $provider->getName(),
                    $sanitizedRequest->model ?: 'default'
                );
                return $fencedFailure;
            }
        }

        // Pelepasan kunci sesi PHP sebelum cURL
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Jika kunci tidak tersedia sama sekali, kembalikan respons kegagalan aman tanpa cURL
        if (empty($sanitizedRequest->apiKeyOverride) && !$provider->isAvailable()) {
            if ($quotaAcquired && $clientMessageId) {
                $this->quotaManager->executeAtomicRefund($clientMessageId, 'Auth error before cURL');
            }
            $failureResponse = AiResponse::createFailure(
                $resolvedKey ? $resolvedKey->validationError : ('Kunci API tidak tersedia untuk provider ' . $provider->getName()),
                'AUTH_ERROR',
                $provider->getName(),
                $sanitizedRequest->model ?: 'default'
            );
            $this->recordAuditTelemetry($sanitizedRequest, $failureResponse, $sessionUserId, $sessionRoleId, $resolvedKey);
            return $failureResponse;
        }

        // Eksekusi cURL HTTP dispatch ke vendor AI
        $response = $provider->generateJson($sanitizedRequest);

        if (!$response->success && $this->config->enableAutoFailover) {
            $fallbackName = ($provider->getName() === 'groq') ? 'gemini' : 'groq';
            $fallbackProvider = $this->getProvider($fallbackName);
            if ($fallbackProvider && $fallbackProvider->isAvailable()) {
                $response = $fallbackProvider->generateJson($sanitizedRequest);
            }
        }

        // FASE 1C: Persistensi Respon Sebelum Klien (Persist-Before-Respond)
        if ($clientMessageId && $workerToken) {
            if ($response->success) {
                $finalQuotaStatus = ($keySource === 'admin_fallback') ? 'COMMITTED' : 'NONE';
                $this->idempManager->persistResponseBeforeClient(
                    $clientMessageId,
                    $workerToken,
                    $response->jsonData ?: ['reply' => $response->content],
                    'COMPLETED',
                    $finalQuotaStatus
                );
            } else {
                $finalQuotaStatus = ($keySource === 'admin_fallback') ? 'COMMITTED' : 'NONE';
                $errStatus = ($response->errorCode === 'TIMEOUT') ? 'TIMEOUT' : 'PROVIDER_ERROR';
                $this->idempManager->recordFailure($clientMessageId, $workerToken, $errStatus, $finalQuotaStatus);
            }
        }

        $this->recordAuditTelemetry($sanitizedRequest, $response, $sessionUserId, $sessionRoleId, $resolvedKey);

        return $response;
    }

    /**
     * Lapisan 3 & 4 Zero PII Scrubber:
     * Menyaring NIK (berbagai format pemisah), NISN, nomor telepon (+62/08), nama orang dalam teks bebas,
     * quasi-identifiers (kombinasi kelas+usia+gender+nilai ekstrem), kondisi medis, tokoh lokal, dan alamat.
     */
    public function sanitizeZeroPii(AiRequest $request): AiRequest
    {
        $cloned = clone $request;

        $scrubText = function (string $text): string {
            // 1. Samarkan NIK 16 digit (termasuk variasi pemisah spasi atau tanda hubung: 3201-0123-4567-0001 / 3201 0123 4567 0001)
            $text = preg_replace('/\b\d{4}[ -]?\d{4}[ -]?\d{4}[ -]?\d{4}\b/', '[NIK_DISAMARKAN]', $text);

            // 2. Samarkan NISN 10 digit (termasuk variasi pemisah: 008-123-4567 / 008 123 4567 / 0081234567)
            $text = preg_replace('/\b\d{3}[ -]?\d{3}[ -]?\d{4}\b/', '[NISN_DISAMARKAN]', $text);
            $text = preg_replace('/\b\d{10}\b/', '[NISN_DISAMARKAN]', $text);

            // 3. Samarkan Nomor Telepon/Ponsel (+62, 62, 08xx dengan berbagai variasi spasi/strip, misal +62 812-3456-7890)
            $text = preg_replace('/(?:\+62|62|0)[ -]?8[0-9]{1,3}[ -]?[0-9]{3,4}[ -]?[0-9]{3,5}\b/', '[NO_HP_DISAMARKAN]', $text);

            // 4. Samarkan Kombinasi Quasi-Identifiers Demografis + Nilai Ekstrem (Jalankan sebelum nama)
            // Contoh: Kelas 8B, 12 tahun, anak perempuan dengan nilai sangat rendah 15
            $text = preg_replace(
                '/(?:kelas\s+[0-9]{1,2}[A-Za-z]?),?\s*(?:usia\s+[0-9]{1,2}\s*tahun|berumur\s+[0-9]{1,2}\s*tahun|[0-9]{1,2}\s*tahun),?\s*(?:anak\s+)?(?:jenis\s+kelamin\s+)?(?:anak\s+)?(laki-laki|perempuan|pria|wanita),?\s*(?:dengan\s+)?nilai\s+(?:hanya\s+|ekstrem\s+|sangat\s+rendah\s+|sangat\s+tinggi\s+)?([0-9]{1,2}(?:\.[0-9]+)?)/ui',
                '[KELOMPOK_BELAJAR_DIAGREGASIKAN]',
                $text
            );

            // 5. Samarkan Sapaan Orang Tua / Wali (anak Bu [Nama], anak Pak [Nama], orang tua dari [Nama])
            $text = preg_replace_callback(
                '/(?:anak\s+(?:dari\s+)?)?(?:Pak|Bu|Bapak|Ibu)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/u',
                function ($matches) {
                    return '[WALI_DISAMARKAN]';
                },
                $text
            );

            // 6. Samarkan Nama Siswa/Anak dalam Teks Bebas (Siswa bernama [Nama], Murid [Nama], Anak bernama [Nama])
            // Menggunakan (?i:...) untuk prefiks agar insensitif huruf besar/kecil, tetapi nama tetap wajib Title Case
            $text = preg_replace_callback(
                '/(?i:siswa\s+(?:bernama\s+)?|murid\s+(?:bernama\s+)?|anak\s+bernama\s+)([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,3})/u',
                function ($matches) {
                    return '[SISWA_DISAMARKAN]';
                },
                $text
            );

            // 7. Samarkan Riwayat / Kondisi Medis Pribadi Siswa
            $text = preg_replace(
                '/(?:mengidap|menderita|didiagnosis|sakit|riwayat|terkena)\s+(?:penyakit\s+)?(typus|tipes|tifus|tbc|tuberculosis|autis|autisme|adhd|epilepsi|ayan|hiv|aids|kanker|tumor|hepatitis|depresi|skizofrenia|bipolar|gangguan mental|anemia berat|asma akut)\b/ui',
                '[KONDISI_KESEHATAN_DISAMARKAN]',
                $text
            );

            // 8. Samarkan Jabatan / Status Tokoh Publik Lokal (Quasi-Identifier Sosial)
            $text = preg_replace(
                '/(?:ayahnya|ibunya|bapaknya|orang\s*tuanya|anak\s+(?:dari\s+)?)\s+(?:seorang\s+)?(camat|lurah|kades|kepala\s+desa|bupati|walikota|wali\s+kota|kapolsek|danramil|babinsa|bhabinkamtibmas|kadis|kepala\s+dinas|anggota\s+dpr|anggota\s+dprd|ketua\s+komite|ketua\s+rt|ketua\s+rw)\b/ui',
                '[WALI_TOKOH_PUBLIK]',
                $text
            );

            // 9. Samarkan Rujukan Alamat Spesifik (Jalan, RT/RW, Dusun)
            $text = preg_replace('/\b(RT\s*\d+|RW\s*\d+|Jl\.\s*[\w\s]+|Jalan\s*[\w\s]+|Dusun\s*[\w\s]+|Gg\.\s*[\w\s]+)/ui', '[ALAMAT_DISAMARKAN]', $text);

            return $text;
        };

        $cloned->prompt = $scrubText($cloned->prompt);

        if (!empty($cloned->systemInstruction)) {
            $cloned->systemInstruction = $scrubText($cloned->systemInstruction);
        }

        if (!empty($cloned->conversationHistory)) {
            $cleanedHistory = [];
            foreach ($cloned->conversationHistory as $turn) {
                if (isset($turn['content'])) {
                    $turn['content'] = $scrubText((string) $turn['content']);
                }
                $cleanedHistory[] = $turn;
            }
            $cloned->conversationHistory = $cleanedHistory;
        }

        return $cloned;
    }

    /**
     * Resolusi Kunci API Guru (BYOK) dari database eksisting tabel teachers
     * (Hanya berlaku untuk provider Gemini)
     */
    protected function resolveCurrentTeacherApiKey(?int $userId = null, ?int $roleId = null): ?string
    {
        try {
            // Guru adalah Role 3
            if ($userId && $roleId == 3 && $this->db) {
                $teacher = $this->db->table('teachers')
                    ->select('gemini_api_key')
                    ->where('user_id', $userId)
                    ->get()
                    ->getRowArray();

                if (!empty($teacher['gemini_api_key'])) {
                    $key = trim($teacher['gemini_api_key']);
                    if (strlen($key) > 10) {
                        return $key;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Jangan gagalkan alur jika pembacaan sesi DB mengalami masalah
        }

        return null;
    }

    /**
     * Menangani failover terkendali jika auto-failover diaktifkan
     */
    protected function handleControlledFailover(AiRequest $request, string $failedProvider, AiResponse $failedResponse): AiResponse
    {
        $fallbackName = ($failedProvider === 'groq') ? 'gemini' : 'groq';
        $fallbackProvider = $this->getProvider($fallbackName);

        if (!$fallbackProvider || !$fallbackProvider->isAvailable()) {
            return $failedResponse;
        }

        log_message('warning', "[UnifiedAiService] Failover dipicu dari {$failedProvider} ke {$fallbackName}. Error: {$failedResponse->errorMessage}");

        return $fallbackProvider->generateText($request);
    }

    /**
     * Mencatat telemetri penggunaan ke log sistem/tabel ai_assistant_logs
     * Menggunakan data sesi yang telah ditangkap SEBELUM session_write_close().
     * CATATAN KEAMANAN: Kunci API dan raw response TIDAK PERNAH dicatat ke database.
     * Dual-Traceability: Format source 'provider:source:owner_id' (maks 50 karakter).
     */
    protected function recordAuditTelemetry(
        AiRequest $request,
        AiResponse $response,
        ?int $userId = null,
        ?int $roleId = null,
        ?ResolvedApiKey $resolvedKey = null
    ): void {
        try {
            if ($this->db && $this->db->tableExists('ai_assistant_logs')) {
                // Catat hanya kueri yang sudah disanitasi dan dibatasi 250 karakter
                $safeQuery = mb_substr($request->prompt, 0, 250);

                // Tag telemetri dual-traceability (maks <= 50 karakter)
                $telemetrySource = $resolvedKey
                    ? $resolvedKey->getTelemetrySourceTag($response->provider)
                    : ($response->provider ?: 'unknown');

                $this->db->table('ai_assistant_logs')->insert([
                    'user_id'    => $userId,
                    'role_id'    => $roleId,
                    'query'      => $safeQuery,
                    'intent'     => $response->success ? 'AI_COMPLETION' : ('ERR_' . ($response->errorCode ?: 'UNKNOWN')),
                    'matched_id' => null,
                    'source'     => substr($telemetrySource, 0, 50),
                    'ip_address' => service('request')->getIPAddress() ?? '127.0.0.1',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', '[UnifiedAiService] Gagal mencatat telemetri audit: ' . $e->getMessage());
        }
    }

    /**
     * Mendapatkan status kesehatan engine AI
     */
    public function getEngineStatus(): array
    {
        return [
            'default_provider'     => $this->config->defaultProvider,
            'fallback_provider'    => $this->config->fallbackProvider,
            'auto_failover_active' => $this->config->enableAutoFailover,
            'force_ipv4_resolve'   => $this->config->forceIpv4Resolve,
            'registered_providers' => $this->getRegisteredProviders(),
            'token_budget'         => $this->config->tokenBudget,
        ];
    }
}
