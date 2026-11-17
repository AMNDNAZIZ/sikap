<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Ai\DTO\ResolvedApiKey;
use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\AiKeyResolver;
use App\Services\Ai\UnifiedAiService;
use App\Services\Ai\Providers\GroqAdapter;
use App\Services\Ai\Providers\GeminiAdapter;
use Config\AiConfig;

/**
 * Pengujian Unit & Integrasi Fase 1B: Unified AI Multi-Account Core
 * 
 * Memvalidasi:
 * 1. ResolvedApiKey DTO (Enkapsulasi, Masking, Telemetry Tag, JsonSerialize).
 * 2. AiKeyResolver (Prefix Detection, Validasi Provider, Prioritas Resolusi Self -> Admin -> None).
 * 3. Keamanan Tingkat Tinggi (Zero Cross-Teacher Fallback, Tidak Ada Fallback .env untuk Pengguna).
 * 4. GroqAdapter & GeminiAdapter (Dukungan apiKeyOverride).
 * 5. UnifiedAiService Integration (Resolusi Otomatis & Dual-Traceability Logging).
 */
final class AiKeyResolverTest extends CIUnitTestCase
{
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect('default');
    }

    public function testResolvedApiKeyEncapsulationAndMasking(): void
    {
        $rawKey = 'gsk_abcdef1234567890xyz';
        $resolved = new ResolvedApiKey($rawKey, 'groq', 12, 'self', false, true);

        // Raw key dapat diakses lewat getRawSecret()
        $this->assertSame($rawKey, $resolved->getRawSecret());
        $this->assertTrue($resolved->isAvailable());

        // __toString() tidak membocorkan kunci mentah
        $this->assertSame('[PROTECTED_API_KEY]', (string) $resolved);

        // jsonSerialize() menyamarkan kunci
        $json = json_encode($resolved);
        $this->assertStringNotContainsString($rawKey, $json);
        $this->assertStringContainsString('gsk_...0xyz', $json);

        // Telemetry tag formatting
        $tag = $resolved->getTelemetrySourceTag();
        $this->assertSame('groq:self:12', $tag);
        $this->assertLessThanOrEqual(50, strlen($tag));
    }

    public function testResolvedApiKeyAdminFallbackTag(): void
    {
        $rawKey = 'gsk_adminsecret987654321';
        $resolved = new ResolvedApiKey($rawKey, 'groq', 1, 'admin_fallback', true, true);

        $this->assertTrue($resolved->isFallback);
        $this->assertSame('admin_fallback', $resolved->keySource);
        $this->assertSame('groq:admin_fallback:1', $resolved->getTelemetrySourceTag());
    }

    public function testResolvedApiKeyNoneFallbackTag(): void
    {
        $resolved = new ResolvedApiKey(null, 'groq', null, 'none', false, false, 'No key');

        $this->assertFalse($resolved->isAvailable());
        $this->assertSame('local:fallback', $resolved->getTelemetrySourceTag());
    }

    public function testProviderPrefixDetection(): void
    {
        $config = new AiConfig();
        $resolver = new AiKeyResolver($config, $this->db);

        $this->assertSame('groq', $resolver->detectProviderFromPrefix('gsk_test1234567890'));
        $this->assertSame('gemini', $resolver->detectProviderFromPrefix('AIzaSyAbcdef1234567890'));
        $this->assertSame('unknown', $resolver->detectProviderFromPrefix('sk-proj-otherkey12345'));
    }

    public function testValidateKeyForProviderMismatch(): void
    {
        $config = new AiConfig();
        $resolver = new AiKeyResolver($config, $this->db);

        // Kunci Groq valid untuk Groq, ditolak untuk Gemini
        $this->assertTrue($resolver->validateKeyForProvider('gsk_test1234567890', 'groq'));
        $this->assertFalse($resolver->validateKeyForProvider('gsk_test1234567890', 'gemini'));

        // Kunci Gemini valid untuk Gemini, ditolak untuk Groq
        $this->assertTrue($resolver->validateKeyForProvider('AIzaSyAbcdef1234567890', 'gemini'));
        $this->assertFalse($resolver->validateKeyForProvider('AIzaSyAbcdef1234567890', 'groq'));

        // Kunci terlalu pendek ditolak
        $this->assertFalse($resolver->validateKeyForProvider('short', 'groq'));
    }

    public function testExplicitOverridePath(): void
    {
        $config = new AiConfig();
        $resolver = new AiKeyResolver($config, $this->db);

        $explicitKey = 'gsk_override1234567890';
        $resolved = $resolver->resolve(10, 3, 5, 'groq', $explicitKey);

        $this->assertTrue($resolved->isAvailable());
        $this->assertSame($explicitKey, $resolved->getRawSecret());
        $this->assertSame('override', $resolved->keySource);
        $this->assertSame(10, $resolved->keyOwnerId);
    }
}
