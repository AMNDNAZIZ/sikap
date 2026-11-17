<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;
use Config\AiConfig;

/**
 * Adapter Provider Google Gemini REST API untuk ESPATA
 * 
 * Mengimplementasikan format Google Generative Language REST API (v1beta)
 * dengan pengiriman Kunci API via Header (x-goog-api-key), dukungan multi-turn,
 * mode JSON, BYOK Guru, dan pencegahan kebocoran secret.
 */
class GeminiAdapter implements AiProviderInterface
{
    protected AiConfig $config;

    /** @var callable|null Hook mock executor untuk pengujian terisolasi */
    protected $httpExecutor = null;

    public function __construct(?AiConfig $config = null)
    {
        $this->config = $config ?: config('AiConfig');
    }

    public function getName(): string
    {
        return 'gemini';
    }

    /**
     * Mengatur custom HTTP executor untuk keperluan pengujian unit terisolasi (mocking)
     */
    public function setHttpExecutor(?callable $executor): void
    {
        $this->httpExecutor = $executor;
    }

    public function isAvailable(?string $customApiKey = null): bool
    {
        $key = ($customApiKey !== null) ? $customApiKey : $this->config->gemini['apiKey'];
        return !empty($key) && strlen(trim($key)) > 10;
    }

    public function generateText(AiRequest $request): AiResponse
    {
        $startTime = microtime(true);

        // 1. Resolusi Kunci API: Prioritas BYOK Guru jika tersedia, beralih ke kunci sekolah di .env
        $apiKey = !empty($request->apiKeyOverride) ? $request->apiKeyOverride : $this->config->gemini['apiKey'];

        $model = $request->model ?: $this->config->gemini['defaultModel'];

        if (empty($apiKey)) {
            return AiResponse::createFailure(
                'Kunci API Gemini belum dikonfigurasi (kunci pribadi guru kosong dan GEMINI_API_KEY sekolah belum diisi di .env).',
                'AUTH_ERROR',
                $this->getName(),
                $model
            );
        }

        // 2. Bangun array contents format spesifik Google GenAI
        $contents = [];

        foreach ($request->conversationHistory as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                // Konversi role 'assistant' menjadi 'model' sesuai spesifikasi Google
                $geminiRole = ($msg['role'] === 'assistant') ? 'model' : 'user';

                $contents[] = [
                    'role'  => $geminiRole,
                    'parts' => [
                        ['text' => (string) $msg['content']]
                    ]
                ];
            }
        }

        // Masukkan giliran prompt saat ini
        $contents[] = [
            'role'  => 'user',
            'parts' => [
                ['text' => $request->prompt]
            ]
        ];

        // 3. Bangun payload request Google GenAI
        $payload = [
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => $request->temperature,
                'maxOutputTokens' => $request->maxTokens,
            ],
        ];

        if (!empty($request->systemInstruction)) {
            $payload['system_instruction'] = [
                'parts' => [
                    ['text' => $request->systemInstruction]
                ]
            ];
        }

        if ($request->jsonMode) {
            $payload['generationConfig']['response_mime_type'] = 'application/json';
        }

        // 4. Bangun endpoint resmi Google (Kunci API dikirim melalui Header, BUKAN URL query string)
        $baseUrl  = rtrim($this->config->gemini['baseUrl'], '/') . '/';
        $endpoint = $baseUrl . urlencode($model) . ':generateContent';
        $timeout  = $request->timeout ?: $this->config->gemini['timeout'];
        $headers  = [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey, // Kunci aman di HTTP Header
        ];

        // 5. Eksekusi HTTP (via mock executor jika diset untuk unit test, atau native cURL)
        if ($this->httpExecutor !== null) {
            $httpRes   = ($this->httpExecutor)($endpoint, $headers, $payload, $timeout, $this->config);
            $rawBody   = $httpRes['rawBody'] ?? '';
            $curlErrno = $httpRes['curlErrno'] ?? 0;
            $curlError = $httpRes['curlError'] ?? '';
            $httpCode  = $httpRes['httpCode'] ?? 200;
        } else {
            $ch = curl_init();
            $curlOptions = [
                CURLOPT_URL            => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_CONNECTTIMEOUT => $this->config->gemini['connectTimeout'],
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ];

            if ($this->config->forceIpv4Resolve) {
                $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }

            curl_setopt_array($ch, $curlOptions);

            $rawBody   = curl_exec($ch);
            $curlErrno = curl_errno($ch);
            $curlError = curl_error($ch);
            $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        $latencyMs = (microtime(true) - $startTime) * 1000;

        // 6. Tangani error level jaringan cURL (pastikan API key tidak bocor ke pesan)
        if ($curlErrno !== 0) {
            $safeError = $this->maskSecret($curlError, $apiKey);
            $errorCode = ($curlErrno === CURLE_OPERATION_TIMEDOUT) ? 'TIMEOUT' : 'CONNECTION_ERROR';
            return AiResponse::createFailure(
                "Koneksi jaringan ke Google Gemini gagal [cURL {$curlErrno}]: {$safeError}",
                $errorCode,
                $this->getName(),
                $model,
                $latencyMs
            );
        }

        // 7. Parse respons JSON dari Google
        $decoded = json_decode($rawBody, true);

        // 8. Tangani status HTTP error
        if ($httpCode === 429) {
            $msg = $decoded['error']['message'] ?? 'Batas kuota panggilan per menit (Rate Limit 429) tercapai pada server Google Gemini.';
            return AiResponse::createFailure($this->maskSecret($msg, $apiKey), 'RATE_LIMIT_429', $this->getName(), $model, $latencyMs);
        }

        if ($httpCode === 400) {
            $msg = $decoded['error']['message'] ?? 'Parameter atau format permintaan Gemini tidak valid (HTTP 400).';
            return AiResponse::createFailure($this->maskSecret($msg, $apiKey), 'INVALID_REQUEST', $this->getName(), $model, $latencyMs);
        }

        if ($httpCode === 401 || $httpCode === 403) {
            $msg = $decoded['error']['message'] ?? "Otentikasi Kunci API Gemini tidak valid atau tidak memiliki izin (HTTP {$httpCode}).";
            return AiResponse::createFailure($this->maskSecret($msg, $apiKey), 'AUTH_ERROR', $this->getName(), $model, $latencyMs);
        }

        if ($httpCode !== 200 || !is_array($decoded)) {
            $msg = $decoded['error']['message'] ?? "Server Gemini mengembalikan status HTTP {$httpCode}.";
            return AiResponse::createFailure($this->maskSecret($msg, $apiKey), 'API_ERROR', $this->getName(), $model, $latencyMs);
        }

        // 9. Ekstraksi teks keluaran secara defensif
        if (!isset($decoded['candidates']) || !is_array($decoded['candidates']) || empty($decoded['candidates'])) {
            $blockReason = $decoded['promptFeedback']['blockReason'] ?? null;
            if ($blockReason) {
                return AiResponse::createFailure(
                    "Respons Gemini ditolak oleh filter keamanan konten: {$blockReason}",
                    'CONTENT_BLOCKED',
                    $this->getName(),
                    $model,
                    $latencyMs
                );
            }

            return AiResponse::createFailure(
                'Respons dari Gemini tidak memuat kandidat (candidates) teks yang valid.',
                'EMPTY_RESPONSE',
                $this->getName(),
                $model,
                $latencyMs
            );
        }

        $candidate = $decoded['candidates'][0] ?? [];
        $text = $candidate['content']['parts'][0]['text'] ?? null;

        if ($text === null) {
            $finishReason = $candidate['finishReason'] ?? 'UNKNOWN';
            if ($finishReason !== 'STOP') {
                return AiResponse::createFailure(
                    "Respons Gemini dihentikan oleh filter model (finishReason: {$finishReason}).",
                    'CONTENT_BLOCKED',
                    $this->getName(),
                    $model,
                    $latencyMs
                );
            }

            return AiResponse::createFailure(
                'Respons dari Gemini tidak memuat konten teks yang valid.',
                'EMPTY_RESPONSE',
                $this->getName(),
                $model,
                $latencyMs
            );
        }

        // Defensif: jika usageMetadata tidak dikembalikan oleh provider
        $promptTokens     = $decoded['usageMetadata']['promptTokenCount'] ?? 0;
        $completionTokens = $decoded['usageMetadata']['candidatesTokenCount'] ?? 0;
        $jsonData         = null;

        if ($request->jsonMode || (str_starts_with(trim($text), '{') && str_ends_with(trim($text), '}'))) {
            $parsed = json_decode($text, true);
            if (is_array($parsed)) {
                $jsonData = $parsed;
            }
        }

        return AiResponse::createSuccess(
            $text,
            $this->getName(),
            $model,
            $promptTokens,
            $completionTokens,
            $latencyMs,
            $jsonData,
            [] // Jangan simpan payload mentah besar ke objek permanen
        );
    }

    public function generateJson(AiRequest $request): AiResponse
    {
        $jsonRequest = clone $request;
        $jsonRequest->jsonMode = true;

        $response = $this->generateText($jsonRequest);

        if ($response->success && empty($response->jsonData)) {
            $text = $response->text ?? '';
            if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
                $parsed = json_decode($matches[1], true);
                if (is_array($parsed)) {
                    $response->jsonData = $parsed;
                    return $response;
                }
            }

            return AiResponse::createFailure(
                'Model Gemini merespons tetapi gagal menghasilkan format JSON yang valid.',
                'INVALID_JSON',
                $this->getName(),
                $response->model,
                $response->latencyMs
            );
        }

        return $response;
    }

    /**
     * Memastikan string API Key tidak pernah bocor ke teks pesan kesalahan
     */
    protected function maskSecret(string $message, string $apiKey): string
    {
        if (empty($apiKey) || strlen($apiKey) < 6) {
            return $message;
        }
        return str_replace($apiKey, '[API_KEY_MASKED]', $message);
    }
}
