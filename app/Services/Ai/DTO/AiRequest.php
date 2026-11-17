<?php

namespace App\Services\Ai\DTO;

/**
 * Data Transfer Object (DTO) untuk Permintaan Pemanggilan AI
 * 
 * Menstandarkan parameter masukan dari seluruh controller/service ESPATA
 * sebelum diteruskan ke provider adapter (Groq / Gemini).
 */
class AiRequest
{
    public string $prompt;
    public ?string $systemInstruction;
    public array $conversationHistory;
    public ?string $model;
    public float $temperature;
    public int $maxTokens;
    public int $timeout;
    public bool $jsonMode;
    public ?string $apiKeyOverride;
    public ?string $clientMessageId;
    public ?string $conversationId;

    /**
     * @param string $prompt Teks instruksi utama / pertanyaan pengguna
     * @param string|null $systemInstruction Instruksi sistem / peran
     * @param array $conversationHistory Riwayat percakapan [['role' => 'user'|'assistant', 'content' => '...']]
     * @param string|null $model Override model tertentu
     * @param float $temperature Derajat variasi/kreativitas (default 0.2)
     * @param int $maxTokens Batas maksimal token keluaran (default 1096)
     * @param int $timeout Batas waktu koneksi cURL dalam detik (default 25)
     * @param bool $jsonMode Wajib format respons berupa JSON valid
     * @param string|null $apiKeyOverride Kunci API khusus (misal BYOK guru)
     * @param string|null $clientMessageId ID unik pesan dari klien (idempotency)
     * @param string|null $conversationId ID sesi percakapan UUIDv4
     */
    public function __construct(
        string $prompt,
        ?string $systemInstruction = null,
        array $conversationHistory = [],
        ?string $model = null,
        float $temperature = 0.2,
        int $maxTokens = 1096,
        int $timeout = 25,
        bool $jsonMode = false,
        ?string $apiKeyOverride = null,
        ?string $clientMessageId = null,
        ?string $conversationId = null
    ) {
        $this->prompt              = trim($prompt);
        $this->systemInstruction  = $systemInstruction ? trim($systemInstruction) : null;
        $this->conversationHistory = $conversationHistory;
        $this->model               = $model;
        $this->temperature         = max(0.0, min(1.0, $temperature));
        $this->maxTokens           = max(64, min(4096, $maxTokens));
        $this->timeout             = max(5, min(120, $timeout));
        $this->jsonMode            = $jsonMode;
        $this->apiKeyOverride      = $apiKeyOverride ? trim($apiKeyOverride) : null;
        $this->clientMessageId     = $clientMessageId;
        $this->conversationId      = $conversationId;
    }

    /**
     * Factory method untuk membuat request chat cepat standar
     */
    public static function createQuickPrompt(string $prompt, ?string $systemInstruction = null): self
    {
        return new self($prompt, $systemInstruction);
    }

    /**
     * Mengubah DTO menjadi array representasi
     */
    public function toArray(): array
    {
        return [
            'prompt'              => $this->prompt,
            'system_instruction' => $this->systemInstruction,
            'history_count'       => count($this->conversationHistory),
            'model'               => $this->model,
            'temperature'         => $this->temperature,
            'max_tokens'          => $this->maxTokens,
            'timeout'             => $this->timeout,
            'json_mode'           => $this->jsonMode,
            'has_api_key_override'=> !empty($this->apiKeyOverride),
            'client_message_id'   => $this->clientMessageId,
            'conversation_id'     => $this->conversationId,
        ];
    }
}
