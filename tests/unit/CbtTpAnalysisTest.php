<?php

namespace Tests\Unit;

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use PHPUnit\Framework\TestCase;
use App\Services\Cbt\Analysis\CbtTpAnalysisService;
use App\Services\Cbt\Assessment\AssessmentStatusResolver;

/**
 * CbtTpAnalysisTest
 *
 * ESPATA CBT V3 — Verifikasi Komprehensif Analisis Hasil Ujian Berbasis TP:
 * 1. Siswa yang menguasai seluruh TP (All Mastered / Pengayaan / Tuntas)
 * 2. Siswa yang hanya menguasai sebagian TP (Partial Mastery / Remedial)
 * 3. Siswa yang belum mencapai ketuntasan (None Mastered / Remedial)
 * 4. Siswa yang belum mengikuti ujian (Not Attempted / Tidak Dihitung 0 / Ujian Susulan)
 * 5. Ujian dengan koreksi esai yang belum selesai (Menunggu Koreksi Esai)
 * 6. TP tanpa butir soal valid / bukti kurang (Smax < 10 atau N < 2 -> Belum cukup data)
 * 7. Soal yang dibatalkan (is_void = true -> tidak dihitung)
 * 8. Konsistensi & rekonsiliasi antara Analisis Per Siswa dan Analisis Per Kelas (CR Murni)
 */
class CbtTpAnalysisTest extends TestCase
{
    private CbtTpAnalysisService $analysisService;

    protected function setUp(): void
    {
        parent::setUp();
        // Gunakan instance terisolasi tanpa koneksi DB untuk unit test murni
        $this->analysisService = new CbtTpAnalysisService();
    }

    /**
     * Skenario Lengkap:
     * - TP 1: Diukur oleh Item 1 & 2 (Valid, bobot cukup >= 10, N=2 >= 2)
     * - TP 2: Diukur oleh Item 3 & 4 (Valid, bobot cukup >= 10, N=2 >= 2)
     * - TP 3: Diukur oleh Item 5 saja (N=1 < 2 -> Bukti Tidak Cukup / Insufficient)
     * - Item 6: Soal yang dibatalkan (is_void = true -> tidak dihitung)
     * - Item 7: Soal esai (OD-PATH-B isolation)
     */
    private function getSampleFixture(): array
    {
        $testConfig = [
            'test_id'        => 701,
            'name'           => 'Sumatif Matematika Kelas VII - TP Berbasis CBT',
            'subject_name'   => 'Matematika',
            'bank_code'      => 'MAT-VII-S1',
            'passing_grade'  => 75.0,
            'tp_definitions' => [
                [
                    'tp_id'     => 1,
                    'tp_code'   => 'TP 1.1',
                    'deskripsi' => 'Operasi bilangan bulat positif dan negatif',
                    'threshold' => 75.0,
                ],
                [
                    'tp_id'     => 2,
                    'tp_code'   => 'TP 1.2',
                    'deskripsi' => 'Pecahan dan desimal dalam kehidupan sehari-hari',
                    'threshold' => 75.0,
                ],
                [
                    'tp_id'     => 3,
                    'tp_code'   => 'TP 1.3',
                    'deskripsi' => 'Persamaan linier satu variabel (Hanya 1 soal valid)',
                    'threshold' => 75.0,
                ],
            ],
        ];

        $items = [
            // TP 1
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 1, 'key' => 'A', 'is_void' => false],
            ['item_id' => 2, 'type' => 'pg', 'tp_id' => 1, 'key' => 'B', 'is_void' => false],
            // TP 2
            ['item_id' => 3, 'type' => 'pg', 'tp_id' => 2, 'key' => 'C', 'is_void' => false],
            ['item_id' => 4, 'type' => 'pg', 'tp_id' => 2, 'key' => 'D', 'is_void' => false],
            // TP 3 (hanya 1 item valid -> bukti tidak cukup N < 2)
            ['item_id' => 5, 'type' => 'pg', 'tp_id' => 3, 'key' => 'A', 'is_void' => false],
            // Soal dibatalkan (is_void)
            ['item_id' => 6, 'type' => 'pg', 'tp_id' => 1, 'key' => 'B', 'is_void' => true],
            // Soal Esai (OD-PATH-B isolation)
            ['item_id' => 7, 'type' => 'esai', 'tp_id' => 2, 'key' => '', 'is_void' => false],
        ];

        $sessions = [
            // Siswa 1: Menguasai TP 1 (100%) dan TP 2 (100%) -> Tuntas / Pengayaan
            [
                'id'                  => 101,
                'student_id'          => 1,
                'student_name'        => 'Ahmad Fauzi',
                'nisn'                => '0011223344',
                'class_name'          => 'VII A',
                'status'              => 'SUBMITTED',
                'started_at'          => '2026-11-02 08:00:00',
                'finished_at'         => '2026-11-02 08:45:00',
                'pending_essay_count' => 0,
            ],
            // Siswa 2: Menguasai TP 1 (100%), Gagal TP 2 (0%) -> Remedial TP 1.2
            [
                'id'                  => 102,
                'student_id'          => 2,
                'student_name'        => 'Budi Pratama',
                'nisn'                => '0011223345',
                'class_name'          => 'VII A',
                'status'              => 'SUBMITTED',
                'started_at'          => '2026-11-02 08:00:00',
                'finished_at'         => '2026-11-02 08:50:00',
                'pending_essay_count' => 0,
            ],
            // Siswa 3: Belum mencapai ketuntasan di semua TP (TP 1: 0%, TP 2: 0%) -> Remedial
            [
                'id'                  => 103,
                'student_id'          => 3,
                'student_name'        => 'Citra Lestari',
                'nisn'                => '0011223346',
                'class_name'          => 'VII A',
                'status'              => 'SUBMITTED',
                'started_at'          => '2026-11-02 08:00:00',
                'finished_at'         => '2026-11-02 08:55:00',
                'pending_essay_count' => 0,
            ],
            // Siswa 4: Belum mengikuti ujian (NOT_ATTEMPTED / ABSENT) -> Tidak dihitung nol
            [
                'id'                  => 104,
                'student_id'          => 4,
                'student_name'        => 'Doni Firmansyah',
                'nisn'                => '0011223347',
                'class_name'          => 'VII A',
                'status'              => 'ABSENT',
                'started_at'          => null,
                'finished_at'         => null,
                'pending_essay_count' => 0,
            ],
            // Siswa 5: Ujian selesai tapi esai belum dikoreksi (pending_essay_count = 1) -> Menunggu Koreksi Esai
            [
                'id'                  => 105,
                'student_id'          => 5,
                'student_name'        => 'Eka Rahmawati',
                'nisn'                => '0011223348',
                'class_name'          => 'VII A',
                'status'              => 'SUBMITTED',
                'started_at'          => '2026-11-02 08:00:00',
                'finished_at'         => '2026-11-02 08:40:00',
                'pending_essay_count' => 1,
            ],
        ];

        $answers = [
            // Siswa 1 answers (Benar semua: 1=A, 2=B, 3=C, 4=D, 5=A, 6=B)
            ['session_id' => 101, 'question_id' => 1, 'answer' => 'A'],
            ['session_id' => 101, 'question_id' => 2, 'answer' => 'B'],
            ['session_id' => 101, 'question_id' => 3, 'answer' => 'C'],
            ['session_id' => 101, 'question_id' => 4, 'answer' => 'D'],
            ['session_id' => 101, 'question_id' => 5, 'answer' => 'A'],
            ['session_id' => 101, 'question_id' => 6, 'answer' => 'B'], // void

            // Siswa 2 answers (Benar TP 1, Salah TP 2)
            ['session_id' => 102, 'question_id' => 1, 'answer' => 'A'],
            ['session_id' => 102, 'question_id' => 2, 'answer' => 'B'],
            ['session_id' => 102, 'question_id' => 3, 'answer' => 'X'], // salah
            ['session_id' => 102, 'question_id' => 4, 'answer' => 'X'], // salah
            ['session_id' => 102, 'question_id' => 5, 'answer' => 'A'],

            // Siswa 3 answers (Salah semua)
            ['session_id' => 103, 'question_id' => 1, 'answer' => 'X'],
            ['session_id' => 103, 'question_id' => 2, 'answer' => 'X'],
            ['session_id' => 103, 'question_id' => 3, 'answer' => 'X'],
            ['session_id' => 103, 'question_id' => 4, 'answer' => 'X'],
            ['session_id' => 103, 'question_id' => 5, 'answer' => 'X'],

            // Siswa 4 (Tidak ada jawaban)

            // Siswa 5 answers (Benar TP 1 & TP 2, esai pending)
            ['session_id' => 105, 'question_id' => 1, 'answer' => 'A'],
            ['session_id' => 105, 'question_id' => 2, 'answer' => 'B'],
            ['session_id' => 105, 'question_id' => 3, 'answer' => 'C'],
            ['session_id' => 105, 'question_id' => 4, 'answer' => 'D'],
            ['session_id' => 105, 'question_id' => 5, 'answer' => 'A'],
        ];

        return [$testConfig, $items, $sessions, $answers];
    }

    public function testStudentMasteringAllTps(): void
    {
        [$testConfig, $items, $sessions, $answers] = $this->getSampleFixture();
        $result = $this->analysisService->analyzeFromData($testConfig, $items, $sessions, $answers);

        $students = $result['student_analysis'];
        $s1 = $students[0]; // Ahmad Fauzi

        $this->assertEquals('Ahmad Fauzi', $s1['nama_siswa']);
        $this->assertTrue($s1['has_attempted']);
        $this->assertFalse($s1['is_essay_pending']);

        // TP 1: 100% >= 75% -> Dikuasai
        $tp1 = $s1['tp_evaluations'][1];
        $this->assertEquals(100.0, $tp1['student_percentage']);
        $this->assertEquals('Dikuasai', $tp1['status_label']);
        $this->assertTrue($tp1['is_mastered']);

        // TP 2: 100% >= 75% -> Dikuasai
        $tp2 = $s1['tp_evaluations'][2];
        $this->assertEquals(100.0, $tp2['student_percentage']);
        $this->assertEquals('Dikuasai', $tp2['status_label']);
        $this->assertTrue($tp2['is_mastered']);

        // Daftar TP Dikuasai memuat TP 1.1 dan TP 1.2
        $this->assertContains('TP 1.1', $s1['mastered_tps']);
        $this->assertContains('TP 1.2', $s1['mastered_tps']);
        $this->assertEmpty($s1['needs_improvement_tps']);

        // Rekomendasi: Pengayaan (karena rata-rata >= 85%)
        $this->assertEquals('Pengayaan', $s1['recommendation']);
    }

    public function testStudentPartialMasteryRequiresRemedial(): void
    {
        [$testConfig, $items, $sessions, $answers] = $this->getSampleFixture();
        $result = $this->analysisService->analyzeFromData($testConfig, $items, $sessions, $answers);

        $students = $result['student_analysis'];
        $s2 = $students[1]; // Budi Pratama

        $this->assertEquals('Budi Pratama', $s2['nama_siswa']);

        // TP 1 Dikuasai (100%)
        $this->assertEquals('Dikuasai', $s2['tp_evaluations'][1]['status_label']);
        // TP 2 Perlu Peningkatan (0%)
        $this->assertEquals('Perlu Peningkatan', $s2['tp_evaluations'][2]['status_label']);
        $this->assertEquals(0.0, $s2['tp_evaluations'][2]['student_percentage']);

        $this->assertContains('TP 1.1', $s2['mastered_tps']);
        $this->assertContains('TP 1.2', $s2['needs_improvement_tps']);

        // Tindak lanjut menyebutkan remedial pada TP yang belum tuntas
        $this->assertStringContainsString('Remedial', $s2['recommendation']);
        $this->assertStringContainsString('TP 1.2', $s2['recommendation']);
    }

    public function testStudentFailingAllTpsRequiresRemedial(): void
    {
        [$testConfig, $items, $sessions, $answers] = $this->getSampleFixture();
        $result = $this->analysisService->analyzeFromData($testConfig, $items, $sessions, $answers);

        $students = $result['student_analysis'];
        $s3 = $students[2]; // Citra Lestari

        $this->assertEquals('Citra Lestari', $s3['nama_siswa']);
        $this->assertEquals('Perlu Peningkatan', $s3['tp_evaluations'][1]['status_label']);
        $this->assertEquals('Perlu Peningkatan', $s3['tp_evaluations'][2]['status_label']);

        $this->assertEmpty($s3['mastered_tps']);
        $this->assertContains('TP 1.1', $s3['needs_improvement_tps']);
        $this->assertContains('TP 1.2', $s3['needs_improvement_tps']);

        $this->assertStringContainsString('Remedial', $s3['recommendation']);
    }

    public function testStudentNotAttemptedIsNotCountedAsZero(): void
    {
        [$testConfig, $items, $sessions, $answers] = $this->getSampleFixture();
        $result = $this->analysisService->analyzeFromData($testConfig, $items, $sessions, $answers);

        $students = $result['student_analysis'];
        $s4 = $students[3]; // Doni Firmansyah (ABSENT)

        $this->assertEquals('Doni Firmansyah', $s4['nama_siswa']);
        $this->assertFalse($s4['has_attempted']);

        // Persentase tidak boleh berupa 0.0 (harus null)
        $this->assertNull($s4['tp_evaluations'][1]['student_percentage']);
        $this->assertNull($s4['tp_evaluations'][2]['student_percentage']);
        $this->assertEquals('Belum Mengerjakan', $s4['tp_evaluations'][1]['status_label']);

        $this->assertEquals('Ujian Susulan', $s4['recommendation']);
    }

    public function testPendingEssayGradingIsClearlyFlagged(): void
    {
        [$testConfig, $items, $sessions, $answers] = $this->getSampleFixture();
        $result = $this->analysisService->analyzeFromData($testConfig, $items, $sessions, $answers);

        $students = $result['student_analysis'];
        $s5 = $students[4]; // Eka Rahmawati (pending_essay_count = 1)

        $this->assertEquals('Eka Rahmawati', $s5['nama_siswa']);
        $this->assertTrue($s5['is_essay_pending']);
        $this->assertEquals(1, $s5['pending_essay_count']);

        // Rekomendasi wajib menyatakan "Menunggu Koreksi Esai"
        $this->assertEquals('Menunggu Koreksi Esai', $s5['recommendation']);

        // Analisis kelas juga menandai adanya esai yang belum selesai
        $this->assertTrue($result['class_analysis']['has_pending_essays']);
        $this->assertEquals(1, $result['class_analysis']['pending_essay_student_count']);
    }

    public function testTpWithInsufficientEvidenceDisplaysBelumCukupData(): void
    {
        [$testConfig, $items, $sessions, $answers] = $this->getSampleFixture();
        $result = $this->analysisService->analyzeFromData($testConfig, $items, $sessions, $answers);

        // TP 3 hanya memiliki 1 butir soal valid (N=1 < 2)
        // Menurut aturan konjungtif: Status harus INSUFFICIENT_EVIDENCE
        $s1 = $result['student_analysis'][0];
        $tp3Eval = $s1['tp_evaluations'][3];

        $this->assertFalse($tp3Eval['is_sufficient_evidence']);
        $this->assertEquals('Belum cukup data untuk dianalisis', $tp3Eval['status_label']);
        $this->assertEquals(AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE, $tp3Eval['status']);

        // Pada analisis kelas, TP 3 juga berstatus BELUM_CUKUP_DATA
        $classMetrics = $result['class_analysis']['tp_metrics'][3];
        $this->assertEquals('BELUM_CUKUP_DATA', $classMetrics['status']);
        $this->assertEquals('Belum cukup data untuk dianalisis', $classMetrics['status_label']);
    }

    public function testVoidQuestionIsExcludedFromAnalysis(): void
    {
        [$testConfig, $items, $sessions, $answers] = $this->getSampleFixture();
        $result = $this->analysisService->analyzeFromData($testConfig, $items, $sessions, $answers);

        // Item 6 adalah soal is_void = true pada TP 1
        // Jumlah soal valid TP 1 harus tetap 2 (Item 1 & 2), bukan 3
        $tp1Metrics = $result['class_analysis']['tp_metrics'][1];
        $this->assertEquals(2, $tp1Metrics['valid_question_count']);
    }

    public function testClassAnalysisMetricsAndReconciliation(): void
    {
        [$testConfig, $items, $sessions, $answers] = $this->getSampleFixture();
        $result = $this->analysisService->analyzeFromData($testConfig, $items, $sessions, $answers);

        $classAnalysis = $result['class_analysis'];

        // Total 5 siswa terdaftar
        $this->assertEquals(5, $classAnalysis['total_registered']);
        // 3 siswa selesai dinilai lengkap (Siswa 1, 2, 3). Siswa 4 belum ujian, Siswa 5 pending esai
        $this->assertEquals(3, $classAnalysis['total_evaluated']);

        // Rekonsiliasi TP 1:
        // Siswa dinilai lengkap: S1 (Tuntas), S2 (Tuntas), S3 (Gagal) -> 2 Tuntas, 1 Belum
        // CR_murni = 2 / 3 = 66.67%
        $tp1 = $classAnalysis['tp_metrics'][1];
        $this->assertEquals(2, $tp1['mastered_count']);
        $this->assertEquals(1, $tp1['not_mastered_count']);
        $this->assertEquals(66.67, $tp1['cr_murni']);
        // Karena KKM = 75%, 66.67% < 75% -> Perlu Penguatan Pembelajaran
        $this->assertEquals('PERLU_PENGUATAN', $tp1['status']);
        $this->assertEquals('Perlu Penguatan Pembelajaran', $tp1['status_label']);

        // Rekonsiliasi TP 2:
        // S1 (Tuntas), S2 (Gagal), S3 (Gagal) -> 1 Tuntas, 2 Belum
        // CR_murni = 1 / 3 = 33.33%
        $tp2 = $classAnalysis['tp_metrics'][2];
        $this->assertEquals(1, $tp2['mastered_count']);
        $this->assertEquals(2, $tp2['not_mastered_count']);
        $this->assertEquals(33.33, $tp2['cr_murni']);

        // TP Tertinggi: TP 1.1 (66.7%)
        $this->assertNotNull($classAnalysis['top_tp']);
        $this->assertEquals('TP 1.1', $classAnalysis['top_tp']['tp_code']);

        // TP Terendah: TP 1.2 (33.3%)
        $this->assertNotNull($classAnalysis['lowest_tp']);
        $this->assertEquals('TP 1.2', $classAnalysis['lowest_tp']['tp_code']);

        // Keduanya perlu penguatan dalam pembelajaran
        $this->assertCount(2, $classAnalysis['tps_needing_reinforcement']);

        // Catatan dasar perhitungan tampil jelas
        $this->assertStringContainsString('3 siswa dengan hasil penilaian sah dan lengkap', $classAnalysis['calculation_base_note']);
    }
}

