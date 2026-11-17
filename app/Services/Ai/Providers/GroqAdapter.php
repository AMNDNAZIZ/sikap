<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;
use Config\AiConfig;

/**
 * Adapter Provider Groq Cloud API untuk ESPATA
 * 
 * Mengimplementasikan format OpenAI-compatible Chat Completions API
 * dengan optimasi latensi rendah, format JSON, resiliensi cURL IPv4 shared hosting,
 * serta pencegahan kebocoran secret (API key masking).
 */
class GroqAdapter implements AiProviderInterface
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
        return 'groq';
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
        $key = ($customApiKey !== null) ? $customApiKey : ($this->config->groq['apiKey'] ?? '');
        return !empty($key) && strlen(trim($key)) > 10;
    }

    public function generateText(AiRequest $request): AiResponse
    {
        $startTime = microtime(true);
        // Dukungan Multi-Account / BYOK: Prioritaskan apiKeyOverride dari sesi/resolver
        $apiKey    = !empty($request->apiKeyOverride) ? $request->apiKeyOverride : ($this->config->groq['apiKey'] ?? '');

        if (empty($apiKey)) {
            return AiResponse::createFailure(
                'Kunci API Groq belum dikonfigurasi atau tidak tersedia.',
                'AUTH_ERROR',
                $this->getName(),
                $request->model ?: $this->config->groq['defaultModel']
            );
        }

        $model = $request->model ?: $this->config->groq['defaultModel'];

        // 1. Bangun array messages format standar OpenAI
        $messages = [];

        if (!empty($request->systemInstruction)) {
            $messages[] = [
                'role'    => 'system',
                'content' => $request->systemInstruction,
            ];
        }

        foreach ($request->conversationHistory as $msg) {
            if (isset($msg['role'], $msg['content']) && in_array($msg['role'], ['user', 'assistant', 'system'])) {
                $messages[] = [
                    'role'    => $msg['role'],
                    'content' => (string) $msg['content'],
                ];
            }
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $request->prompt,
        ];

        // 2. Bangun payload request
        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $request->temperature,
            'max_tokens'  => $request->maxTokens,
        ];

        if ($request->jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $endpoint = $this->config->groq['baseUrl'];
        $timeout  = $request->timeout ?: $this->config->groq['timeout'];
        $headers  = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];

        // 3. Eksekusi HTTP (via mock executor jika diset untuk unit test, atau native cURL)
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
                CURLOPT_CONNECTTIMEOUT => $this->config->groq['connectTimeout'],
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

        // 4. Tangani error level jaringan cURL (pastikan API key tidak bocor ke pesan)
        if ($curlErrno !== 0) {
            $safeError = $this->maskSecret($curlError, $apiKey);
            $errorCode = ($curlErrno === CURLE_OPERATION_TIMEDOUT) ? 'TIMEOUT' : 'CONNECTION_ERROR';
            $curlDiag = [
                'http_code'     => 0,
                'curl_errno'    => $curlErrno,
                'provider'      => $this->getName(),
                'model'         => $model,
                'json_mode'     => $request->jsonMode,
                'latency_ms'    => round($latencyMs, 2),
                'failure_class' => $errorCode,
            ];
            return AiResponse::createFailure(
                "Koneksi jaringan ke Groq gagal [cURL {$curlErrno}]: {$safeError}",
                $errorCode,
                $this->getName(),
                $model,
                $latencyMs,
                $curlDiag
            );
        }

        // 5. Parse respons JSON dari server
        $decoded = json_decode($rawBody, true);

        // 6. Tangani status HTTP spesifik (dengan penyaringan secret dan telemetri diagnostik aman)
        $providerCode = isset($decoded['error']['code']) ? (string) $decoded['error']['code'] : null;
        $providerType = isset($decoded['error']['type']) ? (string) $decoded['error']['type'] : null;

        $diagnostic = [
            'http_code'     => $httpCode,
            'provider'      => $this->getName(),
            'model'         => $model,
            'json_mode'     => $request->jsonMode,
            'latency_ms'    => round($latencyMs, 2),
            'provider_code' => $providerCode,
            'provider_type' => $providerType,
        ];

        if ($httpCode === 429) {
            $msg = $decoded['error']['message'] ?? 'Batas kuota panggilan per menit (Rate Limit 429) tercapai pada server Groq.';
            $diagnostic['failure_class'] = 'RATE_LIMIT_429';
            return AiResponse::createFailure($this->maskSecret($msg, $apiKey), 'RATE_LIMIT_429', $this->getName(), $model, $latencyMs, $diagnostic);
        }

        if ($httpCode === 401) {
            $msg = $decoded['error']['message'] ?? 'Otentikasi Kunci API Groq ditolak (HTTP 401).';
            $diagnostic['failure_class'] = 'AUTH_ERROR';
            return AiResponse::createFailure($this->maskSecret($msg, $apiKey), 'AUTH_ERROR', $this->getName(), $model, $latencyMs, $diagnostic);
        }

        if ($httpCode !== 200 || !is_array($decoded)) {
            $msg = $decoded['error']['message'] ?? "Server Groq mengembalikan status HTTP {$httpCode}.";
            $diagnostic['failure_class'] = 'API_ERROR';
            return AiResponse::createFailure($this->maskSecret($msg, $apiKey), 'API_ERROR', $this->getName(), $model, $latencyMs, $diagnostic);
        }

        // 7. Ekstraksi teks dan penggunaan token (defensif jika choices kosong)
        if (!isset($decoded['choices']) || !is_array($decoded['choices']) || empty($decoded['choices'])) {
            return AiResponse::createFailure(
                'Respons dari Groq tidak memuat pilihan (choices) teks yang valid.',
                'EMPTY_RESPONSE',
                $this->getName(),
                $model,
                $latencyMs
            );
        }

        $text = $decoded['choices'][0]['message']['content'] ?? null;
        if ($text === null) {
            return AiResponse::createFailure(
                'Respons dari Groq tidak memuat konten teks yang valid.',
                'EMPTY_RESPONSE',
                $this->getName(),
                $model,
                $latencyMs
            );
        }

        // Defensif: jika provider tidak mengembalikan usage
        $promptTokens     = $decoded['usage']['prompt_tokens'] ?? 0;
        $completionTokens = $decoded['usage']['completion_tokens'] ?? 0;
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
            [] // Jangan simpan rawResponse berukuran besar ke memori permanen
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
                'Model AI merespons tetapi gagal menghasilkan format JSON yang valid.',
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
