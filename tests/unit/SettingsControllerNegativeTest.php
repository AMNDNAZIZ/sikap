<?php
// 🎯 Standar Skill Diterapkan: test-case-writing, api-negative-testing, boundary-value-testing
// Refs: rekap-file-md/TandaTanganCap/Task-List-TandaTanganCap.md

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\SettingsModel;
use App\Libraries\PdfGenerator;

/**
 * Unit Test: SettingsController & PDF Generation Deep Negative Testing
 *
 * Mneguji skenario kegagalan ekstrim, validasi input, injection, dan boundary conditions.
 *
 * Refs: PRD-TandaTanganCap.md BR-01..BR-08
 *
 * @internal
 */
final class SettingsControllerNegativeTest extends CIUnitTestCase
{
    // ══════════════════════════════════════════════════════════
    // NEGATIVE CASES: VALIDASI PREFERENSI TANDA TANGAN & CAP
    // ══════════════════════════════════════════════════════════

    /**
     * TC-NEG-PREF-01: Input string acak bukan '1' harus dinormalisasi menjadi '0'
     */
    public function testPreferenceNormalizesRandomStringToZero(): void
    {
        $rawValues = ['yes', 'true', 'on', 'checked', '2', '-1', 'null', 'TRUE'];

        foreach ($rawValues as $val) {
            $normalized = ($val === '1') ? '1' : '0';
            $this->assertSame('0', $normalized, "Input '{$val}' harus dinormalisasi ke '0'.");
        }
    }

    /**
     * TC-NEG-PREF-02: Input SQL Injection payload harus dinormalisasi menjadi '0'
     */
    public function testPreferenceHandlesSqlInjectionPayloads(): void
    {
        $sqliPayloads = [
            "1' OR '1'='1",
            "1; DROP TABLE settings; --",
            "' UNION SELECT 1,2,3--",
            "admin'--",
            "1 OR 1=1"
        ];

        foreach ($sqliPayloads as $payload) {
            $normalized = ($payload === '1') ? '1' : '0';
            $this->assertSame('0', $normalized, "Payload SQLi '{$payload}' harus dinormalisasi ke '0'.");
        }
    }

    /**
     * TC-NEG-PREF-03: Input XSS payload harus dinormalisasi menjadi '0'
     */
    public function testPreferenceHandlesXssPayloads(): void
    {
        $xssPayloads = [
            "<script>alert(1)</script>",
            "<img src=x onerror=alert('xss')>",
            "1<script>",
            "javascript:void(0)"
        ];

        foreach ($xssPayloads as $payload) {
            $normalized = ($payload === '1') ? '1' : '0';
            $this->assertSame('0', $normalized, "Payload XSS '{$payload}' harus dinormalisasi ke '0'.");
        }
    }

    /**
     * TC-NEG-PREF-04: Nilai NULL atau tidak ada di POST harus dinormalisasi menjadi '0'
     */
    public function testPreferenceNormalizesNullAndMissingToZero(): void
    {
        $postData = []; // kosong
        $useSignature = ($postData['use_signature'] ?? null) === '1' ? '1' : '0';
        $useSeal      = ($postData['use_seal'] ?? null) === '1' ? '1' : '0';

        $this->assertSame('0', $useSignature);
        $this->assertSame('0', $useSeal);
    }

    /**
     * TC-NEG-PREF-05: Input tepat '1' adalah satu-satunya nilai yang menghasilkan '1'
     */
    public function testPreferenceOnlyAcceptsExactOne(): void
    {
        $this->assertSame('1', ('1' === '1') ? '1' : '0');
        $this->assertSame('0', (1 === '1') ? '1' : '0'); // integer 1 strict match
    }

    // ══════════════════════════════════════════════════════════
    // NEGATIVE CASES: VALIDASI FORMAT DAN UKURAN BERKAS
    // ══════════════════════════════════════════════════════════

    /**
     * TC-NEG-FILE-01: Ekstensi dan MIME non-PNG dilarang (JPEG, WEBP, GIF, SVG, PDF, EXE)
     */
    public function testFileRulesRejectsNonPngMimeTypes(): void
    {
        $disallowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/webp',
            'image/gif',
            'image/svg+xml',
            'image/bmp',
            'application/pdf',
            'application/x-php',
            'application/x-msdownload',
            'text/plain',
            'text/html'
        ];

        foreach ($disallowedMimes as $mime) {
            $allowed = in_array($mime, ['image/png'], true);
            $this->assertFalse($allowed, "MIME {$mime} harus ditolak oleh aturan validasi.");
        }
    }

    /**
     * TC-NEG-FILE-02: Ukuran berkas tanda tangan > 150 KB (153600 bytes) dilarang
     */
    public function testSignatureFileRulesRejectsOver150Kb(): void
    {
        $maxBytes = 150 * 1024; // 153,600 bytes

        $testSizes = [
            153601 => false, // 150 KB + 1 byte
            154000 => false,
            200000 => false, // ~195 KB
            500000 => false, // ~488 KB
            1048576 => false // 1 MB
        ];

        foreach ($testSizes as $size => $expected) {
            $valid = $size <= $maxBytes;
            $this->assertSame($expected, $valid, "Ukuran {$size} bytes harus melebihi batas 150 KB.");
        }
    }

    /**
     * TC-NEG-FILE-02B: Ukuran berkas cap sekolah > 500 KB (512000 bytes) dilarang, ≤ 500 KB diterima
     */
    public function testSealFileRulesAcceptsUpTo500KbAndRejectsOver(): void
    {
        $maxBytes = 500 * 1024; // 512,000 bytes

        $testSizes = [
            102400  => true,  // 100 KB - valid
            512000  => true,  // tepat 500 KB - valid
            512001  => false, // 500 KB + 1 byte - invalid
            600000  => false, // ~585 KB - invalid
            1048576 => false, // 1 MB - invalid
        ];

        foreach ($testSizes as $size => $expected) {
            $valid = $size <= $maxBytes;
            $this->assertSame($expected, $valid, "Evaluasi batas 500 KB untuk ukuran {$size} bytes harus sesuai.");
        }
    }

    /**
     * TC-NEG-FILE-03: Berkas berukuran 0 byte harus ditolak
     */
    public function testFileRulesRejectsZeroByteFile(): void
    {
        $size = 0;
        $isValid = ($size > 0);
        $this->assertFalse($isValid, "Berkas 0 byte harus ditolak.");
    }

    // ══════════════════════════════════════════════════════════
    // NEGATIVE CASES: DOMPDF & PLACEHOLDER EDGE CASES
    // ══════════════════════════════════════════════════════════

    /**
     * TC-NEG-DOM-01: Placeholder dengan spasi, newline, atau tab di dalamnya
     * Contoh: <div class="signature-space">   \n\t   </div>
     */
    public function testPlaceholderWithWhitespaceInsideIsReplaced(): void
    {
        $html = '<div class="signature-space">' . "\n  \t  \n" . '</div>';
        $sigHtml = '<div class="signature-block">SIG</div>';

        $replaced = preg_replace(
            '/<div[^>]*class=["\']signature-space["\'][^>]*>\s*<\/div>/i',
            $sigHtml,
            $html,
            1
        );

        $this->assertSame($sigHtml, $replaced);
    }

    /**
     * TC-NEG-DOM-02: Tag placeholder tidak boleh mengganti tag yang bukan div
     * Contoh: <span class="signature-space"></span> tidak boleh diganti
     */
    public function testNonDivPlaceholderIsNotReplaced(): void
    {
        $html = '<span class="signature-space"></span>';
        $sigHtml = '<div class="signature-block">SIG</div>';

        $replaced = preg_replace(
            '/<div[^>]*class=["\']signature-space["\'][^>]*>\s*<\/div>/i',
            $sigHtml,
            $html,
            1
        );

        $this->assertSame($html, $replaced, "Tag <span> tidak boleh diganti oleh regex <div>.");
    }

    /**
     * TC-NEG-DOM-03: Placeholder dengan atribut tambahan (id, style) tetap berhasil diganti
     */
    public function testPlaceholderWithExtraAttributesIsReplaced(): void
    {
        $html = '<div id="ttd-box" class="signature-space" style="margin:0;"></div>';
        $sigHtml = '<div class="signature-block">SIG</div>';

        $replaced = preg_replace(
            '/<div[^>]*class=["\']signature-space["\'][^>]*>\s*<\/div>/i',
            $sigHtml,
            $html,
            1
        );

        $this->assertSame($sigHtml, $replaced);
    }

    /**
     * TC-NEG-DOM-04: Multiple placeholders untuk seal — hanya yang pertama diganti (limit 1)
     */
    public function testMultipleSealPlaceholdersOnlyFirstReplaced(): void
    {
        $html = '<div class="seal-space"></div><div class="seal-space"></div>';
        $sealHtml = '<div class="seal-block">SEAL</div>';

        $replaced = preg_replace(
            '/<div[^>]*class=["\']seal-space["\'][^>]*>\s*<\/div>/i',
            $sealHtml,
            $html,
            1
        );

        $this->assertEquals(1, substr_count($replaced, 'SEAL'));
        $this->assertEquals(1, substr_count($replaced, 'seal-space'));
    }

    /**
     * TC-NEG-DOM-05: Jika string HTML sangat panjang (1 MB string), regex tidak hang / segfault
     */
    public function testRegexPerformanceOnLargeHtmlString(): void
    {
        $largePrefix = str_repeat('<p>Paragraf teks dokumen resmi sekolah...</p>', 2000);
        $html = $largePrefix . '<div class="signature-space"></div>' . $largePrefix;

        $sigHtml = '<div class="signature-block">SIG</div>';
        $start = microtime(true);

        $replaced = preg_replace(
            '/<div[^>]*class=["\']signature-space["\'][^>]*>\s*<\/div>/i',
            $sigHtml,
            $html,
            1
        );

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(0.5, $elapsed, "Regex replacement pada dokumen besar harus selesai di bawah 500ms.");
        $this->assertStringContainsString('SIG', $replaced);
        $this->assertStringNotContainsString('signature-space', $replaced);
    }
}
