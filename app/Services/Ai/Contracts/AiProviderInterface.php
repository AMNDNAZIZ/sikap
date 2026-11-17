<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;

/**
 * Kontrak Standar Penyedia AI ESPATA (Provider Adapter Contract)
 * 
 * Setiap vendor AI (Groq, Gemini, dsb.) wajib mengimplementasikan interface ini
 * agar dapat dihubungkan secara seragam ke dalam UnifiedAiService.
 */
interface AiProviderInterface
{
    /**
     * Mendapatkan nama identitas unik penyedia (contoh: 'groq', 'gemini')
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Memeriksa apakah penyedia siap digunakan (kunci API valid & terkonfigurasi)
     * 
     * @param string|null $customApiKey Kunci API khusus untuk diperiksa (opsional)
     * @return bool
     */
    public function isAvailable(?string $customApiKey = null): bool;

    /**
     * Mengirim permintaan teks/obrolan ke penyedia AI
     * 
     * @param AiRequest $request Objek parameter permintaan terstandar
     * @return AiResponse Objek respons terstandar
     */
    public function generateText(AiRequest $request): AiResponse;

    /**
     * Mengirim permintaan dengan penegasan format luaran berupa JSON terstruktur
     * 
     * @param AiRequest $request Objek parameter permintaan terstandar
     * @return AiResponse Objek respons dengan jsonData yang telah di-decode
     */
    public function generateJson(AiRequest $request): AiResponse;
}
