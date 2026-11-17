<?php

namespace App\Services\Ai\DTO;

/**
 * Data Transfer Object (DTO) untuk Respons Standar dari Provider AI
 * 
 * Menstandarkan format respons dari berbagai vendor AI (Groq / Gemini)
 * agar konsumen aplikasi menerima struktur data yang identik.
 */
class AiResponse
{
    public bool $success;
    public ?string $text;
    public ?array $jsonData;
    public string $provider;
    public string $model;
    public int $promptTokens;
    public int $completionTokens;
    public float $latencyMs;
    public ?string $errorMessage;
    public ?string $errorCode;
    public array $rawResponse;

    public function __construct(
        bool $success,
        ?string $text = null,
        ?array $jsonData = null,
        string $provider = 'unknown',
        string $model = 'unknown',
        int $promptTokens = 0,
        int $completionTokens = 0,
        float $latencyMs = 0.0,
        ?string $errorMessage = null,
        ?string $errorCode = null,
        array $rawResponse = []
    ) {
        $this->success          = $success;
        $this->text             = $text !== null ? trim($text) : null;
        $this->jsonData         = $jsonData;
        $this->provider         = $provider;
        $this->model            = $model;
        $this->promptTokens     = max(0, $promptTokens);
        $this->completionTokens = max(0, $completionTokens);
        $this->latencyMs        = max(0.0, round($latencyMs, 2));
        $this->errorMessage     = $errorMessage;
        $this->errorCode        = $errorCode;
        $this->rawResponse      = $rawResponse;
    }

    /**
     * Factory method untuk respons sukses
     */
    public static function createSuccess(
        string $text,
        string $provider,
        string $model,
        int $promptTokens = 0,
        int $completionTokens = 0,
        float $latencyMs = 0.0,
        ?array $jsonData = null,
        array $rawResponse = []
    ): self {
        return new self(
            true,
            $text,
            $jsonData,
            $provider,
            $model,
            $promptTokens,
            $completionTokens,
            $latencyMs,
            null,
            null,
            $rawResponse
        );
    }

    /**
     * Factory method untuk respons gagal
     */
    public static function createFailure(
        string $errorMessage,
        string $errorCode,
        string $provider = 'unknown',
        string $model = 'unknown',
        float $latencyMs = 0.0,
        array $rawResponse = []
    ): self {
        return new self(
            false,
            null,
            null,
            $provider,
            $model,
            0,
            0,
            $latencyMs,
            $errorMessage,
            $errorCode,
            $rawResponse
        );
    }

    /**
     * Mengubah DTO menjadi array terstruktur
     */
    public function toArray(): array
    {
        return [
            'success'           => $this->success,
            'text'              => $this->text,
            'has_json'          => !empty($this->jsonData),
            'provider'          => $this->provider,
            'model'             => $this->model,
            'prompt_tokens'     => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens'      => $this->promptTokens + $this->completionTokens,
            'latency_ms'        => $this->latencyMs,
            'error_message'     => $this->errorMessage,
            'error_code'        => $this->errorCode,
        ];
    }
}
