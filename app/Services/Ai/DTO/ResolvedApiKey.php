<?php

namespace App\Services\Ai\DTO;

/**
 * Data Transfer Object (DTO) untuk Hasil Resolusi Kunci API AI
 * 
 * Menampung hasil resolusi API Key terpadu berdasarkan kebijakan resmi Human Owner:
 * 1. API Key pribadi pengguna aktif (self).
 * 2. API Key Administrator sebagai fallback bersama yang sah (admin_fallback).
 * 3. Fallback lokal / non-AI jika kunci tidak tersedia (none).
 * 
 * KEAMANAN TINGKAT TINGGI:
 * - Kunci API mentah bersifat privat dan TIDAK PERNAH terekspos via print_r, var_dump,
 *   __toString(), atau json_encode().
 * - Digunakan untuk pelacakan akuntabilitas ganda (dual-traceability: requester vs key owner).
 */
class ResolvedApiKey implements \JsonSerializable
{
    /**
     * Kunci API mentah (Protected Secret)
     */
    private ?string $rawSecret;

    /**
     * Nama provider yang cocok dengan format kunci ('groq', 'gemini', 'unknown')
     */
    public string $provider;

    /**
     * ID Pengguna pemilik kunci (ID di tabel users / pemegang kuota)
     */
    public ?int $keyOwnerId;

    /**
     * Sumber resolusi kunci ('self', 'admin_fallback', 'none', 'override')
     */
    public string $keySource;

    /**
     * Penanda apakah kunci berasal dari fallback administrator
     */
    public bool $isFallback;

    /**
     * Penanda apakah kunci valid secara sintaksis dan siap digunakan
     */
    public bool $isValid;

    /**
     * Pesan kesalahan jika validasi kunci gagal
     */
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
        $this->rawSecret       = $rawSecret !== null ? trim($rawSecret) : null;
        $this->provider        = strtolower(trim($provider ?: 'unknown'));
        $this->keyOwnerId      = $keyOwnerId;
        $this->keySource       = trim($keySource ?: 'none');
        $this->isFallback      = $isFallback;
        $this->isValid         = $isValid;
        $this->validationError = $validationError;
    }

    /**
     * Mengambil kunci mentah untuk disuntikkan ke HTTP header provider adapter
     */
    public function getRawSecret(): ?string
    {
        return $this->rawSecret;
    }

    /**
     * Mengecek apakah kunci valid dan siap melakukan panggilan jaringan
     */
    public function isAvailable(): bool
    {
        return $this->isValid && !empty($this->rawSecret);
    }

    /**
     * Mengambil representasi kunci yang disamarkan demi keamanan log/tampilan UI
     * Contoh: gsk_...89ab atau AIza...wxyz
     */
    public function getMaskedKey(): string
    {
        if (empty($this->rawSecret)) {
            return 'none';
        }

        $len = strlen($this->rawSecret);
        if ($len < 8) {
            return '***';
        }

        return substr($this->rawSecret, 0, 4) . '...' . substr($this->rawSecret, -4);
    }

    /**
     * Menghasilkan string tag terstruktur untuk kolom source pada tabel ai_assistant_logs
     * Maksimal panjang <= 50 karakter sesuai konstrain schema VARCHAR(50).
     * Contoh:
     * - groq:self:12
     * - groq:admin_fallback:1
     * - gemini:self:4
     * - local:fallback
     */
    public function getTelemetrySourceTag(?string $actualProvider = null): string
    {
        $prov = $actualProvider ? strtolower(trim($actualProvider)) : $this->provider;

        if (!$this->isAvailable()) {
            return 'local:fallback';
        }

        $owner = $this->keyOwnerId !== null ? (string) $this->keyOwnerId : '0';
        $tag = "{$prov}:{$this->keySource}:{$owner}";

        // Pastikan tidak melebihi batas VARCHAR(50)
        return substr($tag, 0, 50);
    }

    /**
     * Mencegah kebocoran kunci jika objek dicetak secara tidak sengaja
     */
    public function __toString(): string
    {
        return '[PROTECTED_API_KEY]';
    }

    /**
     * Serialisasi JSON aman: Kunci mentah tidak pernah disertakan
     */
    public function jsonSerialize(): mixed
    {
        return [
            'provider'         => $this->provider,
            'key_source'       => $this->keySource,
            'key_owner_id'     => $this->keyOwnerId,
            'is_fallback'      => $this->isFallback,
            'is_valid'         => $this->isValid,
            'masked_key'       => $this->getMaskedKey(),
            'validation_error' => $this->validationError,
        ];
    }
}
