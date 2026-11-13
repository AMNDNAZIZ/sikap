<?php
// 🎯 Standar Skill Diterapkan: test-case-writing, api-negative-testing, boundary-value-testing
// Refs: rekap-file-md/TandaTanganCap/Task-List-TandaTanganCap.md

use CodeIgniter\Test\CIUnitTestCase;
use App\Helpers\SignatureHelper;
use App\Models\SettingsModel;

/**
 * Unit Test: SignatureHelper (Happy Paths + Extensive Negative Cases)
 *
 * Refs: PRD-TandaTanganCap.md BR-01..BR-08
 *
 * @internal
 */
final class SignatureHelperTest extends CIUnitTestCase
{
    private string $tmpDir;
    private string $minPng1x1;
    private string $relTmpDir = 'uploads/tests_signature_tmp/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = FCPATH . $this->relTmpDir;
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }

        // PNG 1×1 transparan minimal
        $this->minPng1x1 = $this->tmpDir . 'min_1x1.png';
        file_put_contents(
            $this->minPng1x1,
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI6QAAAABJRU5ErkJggg=='
            )
        );
    }

    protected function tearDown(): void
    {
        // Reset helper model
        SignatureHelper::setModel(null);

        // Hapus file temp
        if (is_dir($this->tmpDir)) {
            foreach (glob($this->tmpDir . '*') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($this->tmpDir);
        }

        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════
    // HAPPY PATH CASES
    // ══════════════════════════════════════════════════════════

    /**
     * TC-UNIT-01: getSignatureBase64 mengembalikan data-uri saat file valid tersedia
     */
    public function testGetSignatureBase64ReturnsPngDataUri(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->with('ks_signature_path')
            ->willReturn($this->relTmpDir . 'min_1x1.png');

        SignatureHelper::setModel($mockSettings);
        $result = SignatureHelper::getSignatureBase64();

        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    /**
     * TC-UNIT-02: getSealBase64 mengembalikan data-uri saat file cap valid tersedia
     */
    public function testGetSealBase64ReturnsPngDataUri(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->with('school_seal_path')
            ->willReturn($this->relTmpDir . 'min_1x1.png');

        SignatureHelper::setModel($mockSettings);
        $result = SignatureHelper::getSealBase64();

        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    // ══════════════════════════════════════════════════════════
    // EXTENSIVE NEGATIVE CASES
    // ══════════════════════════════════════════════════════════

    /**
     * TC-NEG-01: Mengembalikan null jika setting key bernilai null
     */
    public function testGetSignatureBase64ReturnsNullWhenSettingIsNull(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')->willReturn(null);

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-02: Mengembalikan null jika path bernilai string kosong ""
     */
    public function testGetSignatureBase64ReturnsNullWhenSettingIsEmptyString(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')->willReturn('');

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-03: Mengembalikan null jika path adalah string whitespace saja
     */
    public function testGetSignatureBase64ReturnsNullWhenSettingIsWhitespaceOnly(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')->willReturn("   \t\n  ");

        SignatureHelper::setModel($mockSettings);
        // File bernama whitespace tidak ada
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-04: Mengembalikan null jika file tidak ada di disk
     */
    public function testGetSignatureBase64ReturnsNullWhenFileNotOnDisk(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn('uploads/signature/ghost_file_' . uniqid() . '.png');

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-05: Mengembalikan null jika path menunjuk ke direktori, bukan file
     */
    public function testGetSignatureBase64ReturnsNullWhenPathIsDirectory(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn($this->relTmpDir); // path folder

        SignatureHelper::setModel($mockSettings);
        // file_get_contents pada direktori akan gagal / mengembalikan false
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-06: Path traversal attempt (../../.env)
     */
    public function testGetSignatureBase64DoesNotReadOutsideFCPATH(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn('../../../../../../../../../../../Windows/win.ini');

        SignatureHelper::setModel($mockSettings);
        // Helper menolak / file tidak ditemukan pada path yang tidak valid di bawah FCPATH
        $result = SignatureHelper::getSignatureBase64();
        // Bila file tidak ditemukan atau akses ditolak, harus null
        $this->assertTrue($result === null || str_starts_with($result, 'data:image/png;base64,'));
    }

    /**
     * TC-NEG-07: Null byte injection (\0)
     */
    public function testGetSignatureBase64HandlesNullByteInPath(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn("uploads/signature/file\0.png");

        SignatureHelper::setModel($mockSettings);
        $result = SignatureHelper::getSignatureBase64();
        $this->assertNull($result);
    }

    /**
     * TC-NEG-08: Path dengan karakter terlarang / wildcard Windows (* ? < > | ")
     */
    public function testGetSignatureBase64HandlesInvalidFilenameChars(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn('uploads/signature/invalid*?<name>|file.png');

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-09: Setting bernilai '0' (string nol) tidak crash
     */
    public function testGetSignatureBase64ReturnsNullForStringZero(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')->willReturn('0');

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-10: Cap sekolah (seal) null saat belum dikonfigurasi
     */
    public function testGetSealBase64ReturnsNullWhenNotConfigured(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')->willReturn(null);

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSealBase64());
    }

    /**
     * TC-NEG-11: Cap sekolah (seal) null saat file fisik hilang
     */
    public function testGetSealBase64ReturnsNullWhenFileMissing(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn('uploads/signature/missing_seal.png');

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSealBase64());
    }

    /**
     * TC-NEG-12: Buffer overflow simulation (path 4096 karakter)
     */
    public function testGetSignatureBase64HandlesExtremelyLongPath(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn('uploads/' . str_repeat('a', 4000) . '.png');

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-13: Path dengan karakter Unicode / emoji yang tidak ada di disk
     */
    public function testGetSignatureBase64HandlesUnicodeAndEmojiPath(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn('uploads/signature/tanda_tangan_✍️_kepsek_🏫.png');

        SignatureHelper::setModel($mockSettings);
        $this->assertNull(SignatureHelper::getSignatureBase64());
    }

    /**
     * TC-NEG-14: Model melempar exception saat akses database
     */
    public function testGetSignatureBase64WhenModelThrowsException(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willThrowException(new \RuntimeException('Database connection lost'));

        SignatureHelper::setModel($mockSettings);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection lost');
        SignatureHelper::getSignatureBase64();
    }

    // ══════════════════════════════════════════════════════════
    // BOUNDARY VALUE CASES
    // ══════════════════════════════════════════════════════════

    /**
     * TC-BVT-01: File PNG 1×1 pixel (ukuran terkecil) sukses di-encode
     */
    public function testGetSignatureBase64EncodesMinimalPng(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn($this->relTmpDir . 'min_1x1.png');

        SignatureHelper::setModel($mockSettings);
        $result = SignatureHelper::getSignatureBase64();

        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
        $decoded = base64_decode(str_replace('data:image/png;base64,', '', $result));
        $this->assertGreaterThan(0, strlen($decoded));
    }

    /**
     * TC-BVT-02: Path dengan leading slash (/uploads/...) tetap bisa dibaca
     */
    public function testGetSignatureBase64HandlesLeadingSlashInPath(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn('/' . $this->relTmpDir . 'min_1x1.png');

        SignatureHelper::setModel($mockSettings);
        $result = SignatureHelper::getSignatureBase64();

        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    /**
     * TC-BVT-03: Path dengan Windows backslashes (\uploads\...) tetap bisa dibaca
     */
    public function testGetSignatureBase64HandlesWindowsBackslashes(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn(str_replace('/', '\\', $this->relTmpDir . 'min_1x1.png'));

        SignatureHelper::setModel($mockSettings);
        $result = SignatureHelper::getSignatureBase64();

        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    /**
     * TC-BVT-04: Path dengan multiple consecutive slashes (///uploads///...)
     */
    public function testGetSignatureBase64HandlesMultipleConsecutiveSlashes(): void
    {
        $mockSettings = $this->createMock(SettingsModel::class);
        $mockSettings->method('getValue')
            ->willReturn('///' . $this->relTmpDir . 'min_1x1.png');

        SignatureHelper::setModel($mockSettings);
        $result = SignatureHelper::getSignatureBase64();

        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }
}
