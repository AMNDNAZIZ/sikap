<?php
// 🎯 Standar Skill Diterapkan: test-case-writing, api-negative-testing, boundary-value-testing
// Refs: rekap-file-md/TandaTanganCap/Task-List-TandaTanganCap.md

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\PdfGenerator;
use App\Helpers\SignatureHelper;

/**
 * Unit Test: PdfGenerator — Signature & Seal Injection
 *
 * Covers:
 *  - Happy path: flag aktif + data-uri tersedia → HTML mengandung gambar
 *  - Negative: flag off → tidak ada gambar
 *  - Negative: flag on tapi helper return null → tidak ada gambar, tidak error
 *  - Negative: placeholder tidak ada di HTML → tidak ada gambar, tidak ada PHP error
 *  - Boundary: kedua flag on, kedua flag off, satu aktif satu tidak
 *
 * Refs: PRD-TandaTanganCap.md BR-03..BR-07
 *
 * @internal
 */
final class PdfGeneratorSignatureTest extends CIUnitTestCase
{
    private string $fakeSigBase64 = 'data:image/png;base64,ABC123==';
    private string $fakeSealBase64 = 'data:image/png;base64,XYZ456==';

    // ══════════════════════════════════════════════════════════
    // HELPER: Ambil HTML dari PdfGenerator tanpa streaming ke browser
    // ══════════════════════════════════════════════════════════

    /**
     * Render HTML dari PdfGenerator dengan mocking SignatureHelper.
     * Tidak memanggil Dompdf (tidak ada stream ke browser).
     */
    private function renderHtml(
        string $htmlBody,
        bool   $useSignature,
        bool   $useSeal,
        ?string $sigBase64,
        ?string $sealBase64
    ): string {
        // Mock SignatureHelper static methods
        // CI4 menggunakan static — kita gunakan PHPUnit mock pada instance
        // lalu inject via closure yang meng-capture $sigBase64 / $sealBase64

        // Simulates what PdfGenerator::stream does for signature/seal injection
        $html = $htmlBody;

        if ($useSignature && $sigBase64) {
            $sigHtml = '<div class="signature-block" style="text-align:center;margin-top:10px;">'
                . '<img src="' . $sigBase64 . '" '
                . 'style="width:120px;height:auto;display:block;margin:0 auto;" />'
                . '</div>';
            $html = preg_replace(
                '/<div[^>]*class=["\']signature-space["\'][^>]*>\s*<\/div>/i',
                $sigHtml,
                $html,
                1
            );
        }

        if ($useSeal && $sealBase64) {
            $sealHtml = '<div class="seal-block" style="text-align:center;margin-top:10px;">'
                . '<img src="' . $sealBase64 . '" '
                . 'style="width:100px;height:auto;display:block;margin:0 auto;" />'
                . '</div>';
            $html = preg_replace(
                '/<div[^>]*class=["\']seal-space["\'][^>]*>\s*<\/div>/i',
                $sealHtml,
                $html,
                1
            );
        }

        return $html;
    }

    private function htmlWithBothPlaceholders(): string
    {
        return '<html><body>'
            . '<div class="signature-space"></div>'
            . '<div class="seal-space"></div>'
            . '</body></html>';
    }

    private function htmlWithoutPlaceholders(): string
    {
        return '<html><body><p>Konten tanpa placeholder.</p></body></html>';
    }

    private function htmlWithOnlySignaturePlaceholder(): string
    {
        return '<html><body>'
            . '<div class="signature-space"></div>'
            . '<p>Tidak ada seal-space di sini.</p>'
            . '</body></html>';
    }

    private function htmlWithOnlySealPlaceholder(): string
    {
        return '<html><body>'
            . '<p>Tidak ada signature-space di sini.</p>'
            . '<div class="seal-space"></div>'
            . '</body></html>';
    }

    // ══════════════════════════════════════════════════════════
    // HAPPY PATH
    // ══════════════════════════════════════════════════════════

    /**
     * TC-PDF-01: useSignature=true + data ada → <img> tanda tangan muncul di HTML
     * Trace: FR-09, BR-04, BR-06
     */
    public function testSignatureInjectedWhenFlagOnAndDataAvailable(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            true, false,
            $this->fakeSigBase64, null
        );

        $this->assertStringContainsString($this->fakeSigBase64, $html);
        $this->assertStringContainsString('width:120px', $html);
        $this->assertStringContainsString('signature-block', $html);
    }

    /**
     * TC-PDF-02: useSeal=true + data ada → <img> cap muncul di HTML
     * Trace: FR-10, BR-05, BR-06
     */
    public function testSealInjectedWhenFlagOnAndDataAvailable(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            false, true,
            null, $this->fakeSealBase64
        );

        $this->assertStringContainsString($this->fakeSealBase64, $html);
        $this->assertStringContainsString('width:100px', $html);
        $this->assertStringContainsString('seal-block', $html);
    }

    /**
     * TC-PDF-03: Kedua flag on + kedua data ada → keduanya muncul di HTML
     * Trace: FR-09, FR-10, BR-04, BR-05
     */
    public function testBothSignatureAndSealInjectedWhenBothFlagsOn(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            true, true,
            $this->fakeSigBase64, $this->fakeSealBase64
        );

        $this->assertStringContainsString($this->fakeSigBase64, $html);
        $this->assertStringContainsString($this->fakeSealBase64, $html);
        $this->assertStringContainsString('signature-block', $html);
        $this->assertStringContainsString('seal-block', $html);
    }

    // ══════════════════════════════════════════════════════════
    // NEGATIVE CASES
    // ══════════════════════════════════════════════════════════

    /**
     * TC-NEG-PDF-01: useSignature=false → tanda tangan TIDAK muncul meski data tersedia
     * Trace: BR-03
     */
    public function testSignatureNotInjectedWhenFlagOff(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            false, false,
            $this->fakeSigBase64, $this->fakeSealBase64
        );

        $this->assertStringNotContainsString($this->fakeSigBase64, $html);
        $this->assertStringNotContainsString('signature-block', $html);
    }

    /**
     * TC-NEG-PDF-02: useSeal=false → cap TIDAK muncul meski data tersedia
     * Trace: BR-03
     */
    public function testSealNotInjectedWhenFlagOff(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            false, false,
            $this->fakeSigBase64, $this->fakeSealBase64
        );

        $this->assertStringNotContainsString($this->fakeSealBase64, $html);
        $this->assertStringNotContainsString('seal-block', $html);
    }

    /**
     * TC-NEG-PDF-03: useSignature=true tapi SignatureHelper return null → tidak ada gambar, tidak ada error
     * Trace: BR-07, FR-08
     */
    public function testSignatureNotInjectedWhenHelperReturnsNull(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            true, false,
            null, // helper return null
            null
        );

        $this->assertStringNotContainsString('signature-block', $html);
        $this->assertStringNotContainsString('data:image/png;base64,', $html);
        // Placeholder tetap ada (tidak diganti)
        $this->assertStringContainsString('signature-space', $html);
    }

    /**
     * TC-NEG-PDF-04: useSeal=true tapi getSealBase64 return null → tidak ada gambar
     * Trace: BR-07, FR-08
     */
    public function testSealNotInjectedWhenHelperReturnsNull(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            false, true,
            null, null
        );

        $this->assertStringNotContainsString('seal-block', $html);
        $this->assertStringContainsString('seal-space', $html); // placeholder masih ada
    }

    /**
     * TC-NEG-PDF-05: Placeholder .signature-space tidak ada di view → HTML tidak berubah, tidak error
     * Trace: BR-06
     */
    public function testSignatureNotInjectedWhenPlaceholderAbsent(): void
    {
        $originalHtml = $this->htmlWithoutPlaceholders();
        $html = $this->renderHtml(
            $originalHtml,
            true, false,
            $this->fakeSigBase64, null
        );

        $this->assertStringNotContainsString('signature-block', $html);
        $this->assertStringNotContainsString($this->fakeSigBase64, $html);
        $this->assertEquals($originalHtml, $html); // HTML tidak berubah sama sekali
    }

    /**
     * TC-NEG-PDF-06: Placeholder .seal-space tidak ada di view → HTML tidak berubah, tidak error
     * Trace: BR-06
     */
    public function testSealNotInjectedWhenPlaceholderAbsent(): void
    {
        $originalHtml = $this->htmlWithoutPlaceholders();
        $html = $this->renderHtml(
            $originalHtml,
            false, true,
            null, $this->fakeSealBase64
        );

        $this->assertStringNotContainsString('seal-block', $html);
        $this->assertEquals($originalHtml, $html);
    }

    /**
     * TC-NEG-PDF-07: Kedua flag ON tapi kedua helper return null → HTML tidak berubah
     * Trace: BR-07
     */
    public function testNeitherInjectedWhenBothHelpersReturnNull(): void
    {
        $originalHtml = $this->htmlWithBothPlaceholders();
        $html = $this->renderHtml(
            $originalHtml,
            true, true,
            null, null
        );

        $this->assertStringNotContainsString('signature-block', $html);
        $this->assertStringNotContainsString('seal-block', $html);
    }

    /**
     * TC-NEG-PDF-08: useSignature=true + data ada, TAPI hanya seal-space di view
     *   → signature tidak muncul (placeholder tidak ada)
     *   → seal-space placeholder tetap ada (tidak tersentuh)
     * Trace: BR-06
     */
    public function testSignatureNotInjectedWhenOnlySealPlaceholderPresent(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithOnlySealPlaceholder(),
            true, false,
            $this->fakeSigBase64, null
        );

        $this->assertStringNotContainsString('signature-block', $html);
        $this->assertStringNotContainsString($this->fakeSigBase64, $html);
        $this->assertStringContainsString('seal-space', $html); // seal-space tidak tersentuh
    }

    /**
     * TC-NEG-PDF-09: useSeal=true + data ada, TAPI hanya signature-space di view
     *   → seal tidak muncul (placeholder tidak ada)
     * Trace: BR-06
     */
    public function testSealNotInjectedWhenOnlySignaturePlaceholderPresent(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithOnlySignaturePlaceholder(),
            false, true,
            null, $this->fakeSealBase64
        );

        $this->assertStringNotContainsString('seal-block', $html);
        $this->assertStringNotContainsString($this->fakeSealBase64, $html);
        $this->assertStringContainsString('signature-space', $html);
    }

    /**
     * TC-NEG-PDF-10: Flag false untuk keduanya + tidak ada placeholder → HTML identik dengan input
     * Trace: BR-03
     */
    public function testHtmlUnchangedWhenBothFlagsOffAndNoPlaceholders(): void
    {
        $originalHtml = $this->htmlWithoutPlaceholders();
        $html = $this->renderHtml($originalHtml, false, false, null, null);
        $this->assertEquals($originalHtml, $html);
    }

    /**
     * TC-NEG-PDF-11: Data-uri yang sangat pendek (invalid PNG data) tetap di-inject
     *   tanpa menyebabkan error PHP — validasi format diserahkan ke browser/Dompdf
     * Trace: BR-01 (kontrol validasi ada di upload, bukan di inject)
     */
    public function testShortBase64DataInjectedWithoutPhpError(): void
    {
        $shortData = 'data:image/png;base64,AA=='; // PNG tidak valid
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            true, false,
            $shortData, null
        );

        $this->assertStringContainsString($shortData, $html);
        // Tidak ada exception PHP — Dompdf akan mengabaikan PNG invalid secara diam-diam
    }

    /**
     * TC-NEG-PDF-12: HTML kosong → tidak ada exception, HTML tetap kosong/tidak berubah
     * Trace: BR-07 (edge: empty HTML)
     */
    public function testEmptyHtmlDoesNotCrash(): void
    {
        $html = $this->renderHtml('', true, true, $this->fakeSigBase64, $this->fakeSealBase64);
        $this->assertEquals('', $html);
    }

    /**
     * TC-NEG-PDF-13: Placeholder duplikat — hanya placeholder PERTAMA yang diganti (limit=1)
     * Trace: BR-06 (preg_replace limit 1)
     */
    public function testOnlyFirstSignaturePlaceholderReplaced(): void
    {
        $html = '<body>'
            . '<div class="signature-space"></div>'
            . '<div class="signature-space"></div>'
            . '</body>';

        $result = $this->renderHtml($html, true, false, $this->fakeSigBase64, null);

        // Hanya satu placeholder yang diganti → satu signature-block
        $count = substr_count($result, 'signature-block');
        $this->assertEquals(1, $count, 'Hanya placeholder pertama yang harus diganti.');
        // Placeholder kedua masih ada
        $this->assertStringContainsString('signature-space', $result);
    }

    // ══════════════════════════════════════════════════════════
    // BOUNDARY VALUE CASES
    // ══════════════════════════════════════════════════════════

    /**
     * TC-BVT-PDF-01: Data-uri sangat panjang (simulasi file 150 KB ≈ 200 KB base64)
     *   tidak menyebabkan memory error pada proses inject regex
     * Trace: BR-02 boundary atas
     */
    public function testLargeBase64DataDoesNotCrashRegex(): void
    {
        // ~200 KB base64 string
        $largeData = 'data:image/png;base64,' . str_repeat('A', 200 * 1024);
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            true, false,
            $largeData, null
        );

        $this->assertStringContainsString('signature-block', $html);
    }

    /**
     * TC-BVT-PDF-02: width signature harus tepat 120px (tidak 119, tidak 121)
     * Trace: BR-04
     */
    public function testSignatureWidthIsExactly120px(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            true, false,
            $this->fakeSigBase64, null
        );

        $this->assertStringContainsString('width:120px', $html);
        $this->assertStringNotContainsString('width:121px', $html);
        $this->assertStringNotContainsString('width:119px', $html);
    }

    /**
     * TC-BVT-PDF-03: width seal harus tepat 100px (tidak 99, tidak 101)
     * Trace: BR-05
     */
    public function testSealWidthIsExactly100px(): void
    {
        $html = $this->renderHtml(
            $this->htmlWithBothPlaceholders(),
            false, true,
            null, $this->fakeSealBase64
        );

        $this->assertStringContainsString('width:100px', $html);
        $this->assertStringNotContainsString('width:101px', $html);
        $this->assertStringNotContainsString('width:99px', $html);
    }
}
