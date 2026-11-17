<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi Layanan AI Terpusat ESPATA (Unified AI Engine)
 * 
 * Mengelola preferensi penyedia, model kandidat, batas waktu (timeout),
 * batas anggaran token, dan opsi resiliensi jaringan shared hosting.
 */
class AiConfig extends BaseConfig
{
    /**
     * Provider default untuk interaksi umum (Asis AI Copilot)
     * Pilihan: 'groq' | 'gemini'
     */
    public string $defaultProvider = 'groq';

    /**
     * Provider cadangan resmi
     */
    public string $fallbackProvider = 'gemini';

    /**
     * Menandai apakah perpindahan provider otomatis aktif saat error.
     * CATATAN: Wajib dinonaktifkan (false) sampai lulus uji stabilitas empiris.
     */
    public bool $enableAutoFailover = false;

    /**
     * Konfigurasi Provider Groq Cloud API (Kandidat Utama Chat Cepat)
     * Catatan: Groq tidak mendukung BYOK (hanya kunci terpusat sekolah)
     */
    public array $groq = [
        'apiKey'         => '',
        'baseUrl'        => 'https://api.groq.com/openai/v1/chat/completions',
        'defaultModel'   => 'openai/gpt-oss-20b',
        'fastModel'      => 'llama-3.1-8b-instant',
        'complexModel'   => 'qwen/qwen3.8-27b',
        'timeout'        => 25,
        'connectTimeout' => 5,
        'temperature'    => 0.2,
        'maxTokens'      => 1096,
    ];

    /**
     * Konfigurasi Provider Google Gemini API (Kandidat Cadangan & Dokumen Panjang)
     * Catatan: Mendukung BYOK Guru dari teachers.gemini_api_key
     */
    public array $gemini = [
        'apiKey'         => '',
        'baseUrl'        => 'https://generativelanguage.googleapis.com/v1beta/models/',
        'defaultModel'   => 'gemini-2.0-flash',
        'fallbackModel'  => 'gemini-1.5-flash',
        'timeout'        => 30,
        'connectTimeout' => 5,
        'temperature'    => 0.2,
        'maxTokens'      => 1096,
    ];

    /**
     * Resiliensi Jaringan Shared Hosting (cPanel / LiteSpeed)
     */
    public bool $forceIpv4Resolve = true; // CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4

    /**
     * Alokasi Anggaran Token Presisi (Sesuai Desain Mandat EAO-002B)
     */
    public array $tokenBudget = [
        'systemPrompt'    => 600,
        'schoolContext'   => 800,
        'chatHistory'     => 1000,
        'userQuery'       => 400,
        'framingOverhead' => 200,
        'maxInput'        => 3000,
        'maxOutputChat'   => 1096,
        'maxOutputDoc'    => 2048,
        'hardCapChat'     => 4096,
        'hardCapDoc'      => 5048,
    ];

    public function __construct()
    {
        parent::__construct();

        // Resolusi konfigurasi dari environment (.env) jika tersedia
        $this->defaultProvider   = env('AI_DEFAULT_PROVIDER', $this->defaultProvider);
        $this->fallbackProvider  = env('AI_FALLBACK_PROVIDER', $this->fallbackProvider);
        $this->enableAutoFailover = (bool) env('AI_ENABLE_AUTO_FAILOVER', false);

        $this->groq['apiKey']   = env('GROQ_API_KEY', '');
        $this->gemini['apiKey'] = env('GEMINI_API_KEY', '');
    }
}
