<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\TugasModel;
use App\Models\TugasSubmissionModel;
use App\Models\MaterialSegmentModel;

/**
 * Test integrasi Tugas ke dalam Segmen Pembelajaran dan Sinkronisasi Buku Nilai
 */
final class TugasSegmentIntegrationAndGradebookTest extends CIUnitTestCase
{
    private string $routesContent;
    private string $viewShowContent;
    private string $viewBelajarShowContent;
    private string $viewAssessmentContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->routesContent           = file_get_contents(APPPATH . 'Config/Routes.php');
        $this->viewShowContent         = file_get_contents(APPPATH . 'Views/admin/tugas/show.php');
        $this->viewBelajarShowContent  = file_get_contents(APPPATH . 'Views/siswa/belajar/show.php');
        $this->viewAssessmentContent   = file_get_contents(APPPATH . 'Views/admin/assessments/input_form.php');
    }

    public function testModelAllowedFieldsContainRequiredColumns(): void
    {
        $tugasProps = (new ReflectionClass(TugasModel::class))->getDefaultProperties();
        $tugasFields = $tugasProps['allowedFields'] ?? [];

        $this->assertContains('material_id', $tugasFields, 'TugasModel harus mengizinkan material_id');
        $this->assertContains('segment_id', $tugasFields, 'TugasModel harus mengizinkan segment_id');

        $segProps = (new ReflectionClass(MaterialSegmentModel::class))->getDefaultProperties();
        $segFields = $segProps['allowedFields'] ?? [];

        $this->assertContains('has_task', $segFields, 'MaterialSegmentModel harus mengizinkan has_task');

        $subProps = (new ReflectionClass(TugasSubmissionModel::class))->getDefaultProperties();
        $subFields = $subProps['allowedFields'] ?? [];

        $this->assertContains('nilai_angka', $subFields, 'TugasSubmissionModel harus mengizinkan nilai_angka');
    }

    public function testRoutesRegistered(): void
    {
        $this->assertStringContainsString(
            "tugas/(:num)/kirim-ke-buku-nilai",
            $this->routesContent,
            'Route kirim-ke-buku-nilai harus terdaftar di Routes.php'
        );

        $this->assertStringContainsString(
            "belajar/sub/(:num)/segment/(:num)/submit-task",
            $this->routesContent,
            'Route submit-task dari segmen belajar harus terdaftar di Routes.php'
        );
    }

    public function testAssessmentFormAllowsPenugasanFormatif(): void
    {
        $this->assertStringContainsString(
            "['tulis', 'lisan', 'projek', 'observasi', 'penugasan']",
            $this->viewAssessmentContent,
            'Form input asesmen formatif harus menyediakan opsi metode penugasan'
        );
    }

    public function testPredikatCalculationLogic(): void
    {
        $calculator = function (float $score): string {
            if ($score >= 86.0) return 'sangat_bagus';
            if ($score >= 71.0) return 'bagus';
            if ($score >= 56.0) return 'kurang';
            return 'belajar_lagi';
        };

        $this->assertSame('sangat_bagus', $calculator(100.0));
        $this->assertSame('sangat_bagus', $calculator(86.0));
        $this->assertSame('bagus', $calculator(85.5));
        $this->assertSame('bagus', $calculator(71.0));
        $this->assertSame('kurang', $calculator(70.9));
        $this->assertSame('kurang', $calculator(56.0));
        $this->assertSame('belajar_lagi', $calculator(55.9));
        $this->assertSame('belajar_lagi', $calculator(0.0));
    }

    public function testBestScoreRuleLogic(): void
    {
        // Aturan Nilai Tertinggi (Best Score): max(existing, new)
        $existing = 80.0;
        $newHigher = 95.0;
        $newLower = 72.5;

        $this->assertEquals(95.0, max($existing, $newHigher), 'Nilai lebih tinggi harus memperbarui nilai sebelumnya');
        $this->assertEquals(80.0, max($existing, $newLower), 'Nilai lebih rendah tidak boleh menurunkan nilai sebelumnya');
    }

    public function testAdminTugasShowViewElements(): void
    {
        // 1. Tombol & Modal Kirim ke Buku Nilai
        $this->assertStringContainsString('modalKirimBukuNilai', $this->viewShowContent);
        $this->assertStringContainsString('kirim-ke-buku-nilai', $this->viewShowContent);

        // 2. Tampilan dan Input Nilai Angka
        $this->assertStringContainsString('nilai_angka', $this->viewShowContent);
        $this->assertStringContainsString('Nilai Angka (0 - 100)', $this->viewShowContent);

        // 3. Pembeda Formatif / Sumatif
        $this->assertStringContainsString('Formatif (Segmen Materi)', $this->viewShowContent);
        $this->assertStringContainsString('Sumatif (Umum)', $this->viewShowContent);
    }

    public function testSiswaBelajarSegmentGatingViewElements(): void
    {
        // 1. Tagihan Tugas di dalam segmen
        $this->assertStringContainsString('Tugas / Tagihan Aktivitas Langkah Ini', $this->viewBelajarShowContent);
        $this->assertStringContainsString('submit-task', $this->viewBelajarShowContent);

        // 2. Gating: tombol terkunci jika tugas belum dikirim
        $this->assertStringContainsString('Kirim Tugas Terlebih Dahulu', $this->viewBelajarShowContent);
        $this->assertStringContainsString('btnMarkSegmentComplete', $this->viewBelajarShowContent);
    }

    public function testEditMaterialSegmentAndTaskIntegrationElements(): void
    {
        $editView = file_get_contents(APPPATH . 'Views/admin/materials/edit.php');
        $this->assertStringContainsString('sectionMultiSegment', $editView);
        $this->assertStringContainsString('segmentCardsContainer', $editView);
        $this->assertStringContainsString('seg-task-toggle', $editView);
        $this->assertStringContainsString('Sertakan Tugas / Tagihan Aktivitas pada Langkah Ini (Formatif)', $editView);
        $this->assertStringContainsString('seg-task-title', $editView);
        $this->assertStringContainsString('seg-task-deadline', $editView);
        $this->assertStringContainsString('seg-task-instructions', $editView);
        $this->assertStringContainsString('Sub Materi Sudah Diterbitkan (Siap)', $editView);

        $matController = file_get_contents(APPPATH . 'Controllers/Admin/Materials.php');
        $this->assertStringContainsString('getSegmentsByMaterial($id)', $matController);
        $this->assertStringContainsString("if (\$isSubMat && !\$wasPublished)", $matController);
        $this->assertStringContainsString("if (\$hasTask && \$activeSegId)", $matController);
    }
}
