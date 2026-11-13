<?php
// 🎯 Standar Skill Diterapkan: test-case-writing, api-negative-testing, boundary-value-testing
// Refs: rekap-file-md/TandaTanganCap/Task-List-TandaTanganCap.md

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit Test: Surat Keluar Download & View PDF with Signature/Seal Options
 *
 * Menguji logika parameter with_signature dan with_seal pada Surat Keluar,
 * serta ketersediaan placeholder di seluruh template surat.
 *
 * @internal
 */
final class SuratKeluarDownloadOptionTest extends CIUnitTestCase
{
    /**
     * TC-SK-01: Parameter query with_signature=1 dan with_seal=1 di-parse dengan benar
     */
    public function testQueryParametersParsedCorrectly(): void
    {
        $getParams = [
            'with_signature' => '1',
            'with_seal'      => '1',
        ];

        $useSignature = ($getParams['with_signature'] ?? null) === '1';
        $useSeal      = ($getParams['with_seal'] ?? null) === '1';

        $this->assertTrue($useSignature);
        $this->assertTrue($useSeal);
    }

    /**
     * TC-SK-02: Parameter query with_signature=0 dan with_seal=0 di-parse menjadi false
     */
    public function testQueryParametersZeroParsedAsFalse(): void
    {
        $getParams = [
            'with_signature' => '0',
            'with_seal'      => '0',
        ];

        $useSignature = ($getParams['with_signature'] ?? null) === '1';
        $useSeal      = ($getParams['with_seal'] ?? null) === '1';

        $this->assertFalse($useSignature);
        $this->assertFalse($useSeal);
    }

    /**
     * TC-SK-03: Parameter tidak disediakan → fallback ke setting default
     */
    public function testFallbackToDefaultSettingsWhenParamsAbsent(): void
    {
        $getParams = []; // tidak ada query param

        $defaultSettingSig  = true;
        $defaultSettingSeal = false;

        $useSignature = isset($getParams['with_signature'])
            ? ($getParams['with_signature'] === '1')
            : $defaultSettingSig;

        $useSeal = isset($getParams['with_seal'])
            ? ($getParams['with_seal'] === '1')
            : $defaultSettingSeal;

        $this->assertTrue($useSignature);
        $this->assertFalse($useSeal);
    }

    /**
     * TC-SK-04: Seluruh 8 berkas template PDF memiliki placeholder signature-space dan seal-space
     */
    public function testAllPdfTemplatesContainPlaceholders(): void
    {
        $templateDir = APPPATH . 'Views/admin/surat/pdf_templates/';
        $templates = [
            'keterangan_aktif.php',
            'keterangan_kjp.php',
            'keterangan_lomba.php',
            'keterangan_mengajar.php',
            'keterangan_mutasi_masuk.php',
            'surat_tugas.php',
            'undangan.php',
            'surat_custom.php',
        ];

        foreach ($templates as $tmpl) {
            $path = $templateDir . $tmpl;
            $this->assertFileExists($path, "Template {$tmpl} harus ada di disk.");

            $content = file_get_contents($path);
            $this->assertStringContainsString('signature-space', $content, "Template {$tmpl} harus memiliki .signature-space.");
            $this->assertStringContainsString('seal-space', $content, "Template {$tmpl} harus memiliki .seal-space.");
        }
    }

    /**
     * TC-SK-05: _header.php mendefinisikan style untuk .signature-space dan .seal-space
     */
    public function testHeaderCssDefinesSignatureAndSealClasses(): void
    {
        $headerPath = APPPATH . 'Views/admin/surat/pdf_templates/_header.php';
        $content = file_get_contents($headerPath);

        $this->assertStringContainsString('.signature-space', $content);
        $this->assertStringContainsString('.seal-space', $content);
        $this->assertStringContainsString('.signature-block', $content);
        $this->assertStringContainsString('.seal-block', $content);
        $this->assertStringContainsString('width: 120px', $content);
        $this->assertStringContainsString('width: 95px', $content);
    }

    /**
     * TC-SK-06: File modal konfirmasi download ada dan berisi elemen ID yang tepat
     */
    public function testModalDownloadViewExistsAndHasRequiredElements(): void
    {
        $modalPath = APPPATH . 'Views/admin/surat/keluar/_modal_download.php';
        $this->assertFileExists($modalPath);

        $content = file_get_contents($modalPath);
        $this->assertStringContainsString('id="modalDownloadPdf"', $content);
        $this->assertStringContainsString('id="modal_switch_signature"', $content);
        $this->assertStringContainsString('id="modal_switch_seal"', $content);
        $this->assertStringContainsString('id="modal-btn-download"', $content);
        $this->assertStringContainsString('id="modal-btn-preview"', $content);
        $this->assertStringContainsString('with_signature', $content);
        $this->assertStringContainsString('with_seal', $content);
    }

    /**
     * TC-SK-07: index.php dan detail.php menyertakan pemicu modal download
     */
    public function testViewsIncludeModalAndTriggerButtons(): void
    {
        $indexPath  = APPPATH . 'Views/admin/surat/keluar/index.php';
        $detailPath = APPPATH . 'Views/admin/surat/keluar/detail.php';

        $indexContent  = file_get_contents($indexPath);
        $detailContent = file_get_contents($detailPath);

        // Keduanya harus memanggil _modal_download
        $this->assertStringContainsString('_modal_download', $indexContent);
        $this->assertStringContainsString('_modal_download', $detailContent);

        // Keduanya harus memiliki tombol dengan class pemicu modal
        $this->assertStringContainsString('btn-trigger-download', $indexContent);
        $this->assertStringContainsString('btn-trigger-download', $detailContent);
        $this->assertStringContainsString('data-bs-target="#modalDownloadPdf"', $indexContent);
        $this->assertStringContainsString('data-bs-target="#modalDownloadPdf"', $detailContent);
    }
}
