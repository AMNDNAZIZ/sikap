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
use App\Services\Cbt\Analysis\CbtTpMappingService;

/**
 * CbtTpMappingTest
 *
 * Verifikasi Unit Service Penyelarasan Materi & Pemetaan Butir Soal ke TP (Berbantuan AI).
 */
class CbtTpMappingTest extends TestCase
{
    private CbtTpMappingService $mappingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mappingService = new CbtTpMappingService();
    }

    /**
     * Uji 1: Fallback pemetaan semantik kata kunci mencocokkan soal ke TP yang sesuai
     */
    public function testFallbackSemanticMatcherAssignsRelevantTp()
    {
        $candidateTps = [
            101 => [
                'id'             => 101,
                'kode_tp'        => 'TP 1.1',
                'lingkup_materi' => 'Bilangan Berpangkat dan Bentuk Akar',
                'deskripsi'      => 'Peserta didik mampu menerapkan sifat-sifat eksponen dan menyederhanakan bentuk akar perkalian.'
            ],
            102 => [
                'id'             => 102,
                'kode_tp'        => 'TP 1.2',
                'lingkup_materi' => 'Persamaan Kuadrat',
                'deskripsi'      => 'Peserta didik mampu menentukan akar-akar persamaan kuadrat dengan faktorisasi dan rumus abc.'
            ],
            103 => [
                'id'             => 103,
                'kode_tp'        => 'TP 2.1',
                'lingkup_materi' => 'Trigonometri Dasar',
                'deskripsi'      => 'Peserta didik mampu menghitung nilai sinus cosinus tangen pada segitiga siku-siku.'
            ],
        ];

        $questions = [
            [
                'id'            => 1,
                'question_text' => 'Bentuk sederhana dari perkalian eksponen 2^3 x 2^5 dalam bentuk akar adalah...',
                'question_type' => 'pg',
            ],
            [
                'id'            => 2,
                'question_text' => 'Tentukan akar-akar persamaan kuadrat x^2 - 5x + 6 = 0 menggunakan metode faktorisasi!',
                'question_type' => 'pg',
            ],
            [
                'id'            => 3,
                'question_text' => 'Diketahui segitiga siku-siku ABC di B dengan panjang AB=3 dan BC=4. Nilai sinus sudut A adalah...',
                'question_type' => 'pg',
            ],
        ];

        $mappings = $this->mappingService->fallbackSemanticMatch($questions, $candidateTps);

        $this->assertCount(3, $mappings);
        $this->assertEquals(101, $mappings[1]['tp_id'], 'Soal 1 harus dipetakan ke TP 1.1 (Eksponen / Bentuk Akar)');
        $this->assertEquals(102, $mappings[2]['tp_id'], 'Soal 2 harus dipetakan ke TP 1.2 (Persamaan Kuadrat)');
        $this->assertEquals(103, $mappings[3]['tp_id'], 'Soal 3 harus dipetakan ke TP 2.1 (Trigonometri)');

        $this->assertGreaterThanOrEqual(70.0, $mappings[1]['confidence']);
        $this->assertNotEmpty($mappings[1]['reasoning']);
        $this->assertEquals('semantic_engine', $mappings[1]['mapping_source']);
    }

    /**
     * Uji 2: Soal tanpa kata kunci spesifik tetap terpetakan secara proporsional tanpa error
     */
    public function testFallbackSemanticMatcherHandlesGeneralQuestionsSafely()
    {
        $candidateTps = [
            201 => [
                'id'             => 201,
                'kode_tp'        => 'TP 1',
                'lingkup_materi' => 'Teks Deskripsi',
                'deskripsi'      => 'Memahami gagasan utama dalam teks deskripsi.'
            ],
            202 => [
                'id'             => 202,
                'kode_tp'        => 'TP 2',
                'lingkup_materi' => 'Teks Narasi',
                'deskripsi'      => 'Menganalisis alur dan tokoh pada cerita narasi.'
            ],
        ];

        $questions = [
            ['id' => 11, 'question_text' => 'Pilihlah salah satu jawaban yang paling tepat!', 'question_type' => 'pg'],
            ['id' => 12, 'question_text' => 'Manakah pernyataan yang sesuai di bawah ini?', 'question_type' => 'pg'],
        ];

        $mappings = $this->mappingService->fallbackSemanticMatch($questions, $candidateTps);

        $this->assertCount(2, $mappings);
        $this->assertContains($mappings[11]['tp_id'], [201, 202]);
        $this->assertContains($mappings[12]['tp_id'], [201, 202]);
    }

    /**
     * Uji 3: Ekstraksi penjadwalan ujian (Level Kelas & Semester) dari data cbt_test_status
     */
    public function testResolveTestSchedulingExtractsLevelAndSemesterAccurately()
    {
        // Kasus A: SD dengan format "Kelas 6A, Kelas 6B", semester 1
        $testA = [
            'id'          => 1,
            'class_codes' => 'Kelas 6A, Kelas 6B',
            'semester'    => 1,
            'bank_level'  => 'SD',
        ];
        $resA = $this->mappingService->resolveTestScheduling($testA);
        $this->assertEquals(1, $resA['semester']);
        $this->assertEquals('Semester 1', $resA['semester_label']);
        $this->assertContains(6, $resA['target_levels']);
        $this->assertEquals('Kelas 6', $resA['level_label']);

        // Kasus B: SMP Romawi VII, semester "Ganjil"
        $testB = [
            'id'          => 2,
            'class_codes' => json_encode(['VII A', 'VII B']),
            'semester'    => 'Ganjil',
            'bank_level'  => 'SMP',
        ];
        $resB = $this->mappingService->resolveTestScheduling($testB);
        $this->assertEquals(1, $resB['semester']);
        $this->assertContains(7, $resB['target_levels']);

        // Kasus C: Semester Genap (2)
        $testC = [
            'id'          => 3,
            'class_codes' => 'Kelas 4',
            'semester'    => 'Genap',
            'bank_level'  => 4,
        ];
        $resC = $this->mappingService->resolveTestScheduling($testC);
        $this->assertEquals(2, $resC['semester']);
        $this->assertContains(4, $resC['target_levels']);
    }

    /**
     * Uji 4: Penilaian tipe soal kompleks (PGK & BS) tidak gagal karena variasi urutan / trailing comma
     */
    public function testCalculateNormalizedScoreHandlesComplexTypesAndOrderInvariance()
    {
        // 1. PG biasa: 'B' vs 'B' => 1.0, '2' vs 'B' => 1.0
        $this->assertEquals(1.0, \App\Services\Cbt\Analysis\CbtTpAnalysisService::calculateNormalizedScore('pg', 'B', 'B'));
        $this->assertEquals(1.0, \App\Services\Cbt\Analysis\CbtTpAnalysisService::calculateNormalizedScore('pg', '2', 'B'));
        $this->assertEquals(0.0, \App\Services\Cbt\Analysis\CbtTpAnalysisService::calculateNormalizedScore('pg', 'A', 'B'));

        // 2. PG Kompleks: 'B,A,D' vs 'A,B,D' (urutan acak siswa tetap 100%)
        $this->assertEquals(1.0, \App\Services\Cbt\Analysis\CbtTpAnalysisService::calculateNormalizedScore('pg_kompleks', 'B,A,D', 'A,B,D'));
        $this->assertEquals(1.0, \App\Services\Cbt\Analysis\CbtTpAnalysisService::calculateNormalizedScore('pgk', 'D,B,C', 'B,C,D'));

        // 3. Benar-Salah: 'S,B,,,' vs 'S,B' (trailing comma pada jawaban CBT tetap 100%)
        $this->assertEquals(1.0, \App\Services\Cbt\Analysis\CbtTpAnalysisService::calculateNormalizedScore('benar_salah', 'S,B,,,', 'S,B'));
        $this->assertEquals(1.0, \App\Services\Cbt\Analysis\CbtTpAnalysisService::calculateNormalizedScore('bs', 'B,S,S,B,', 'B,S,S,B'));
    }

    /**
     * Uji 5: Rekonsiliasi matematis penguasaan TP siswa selaras dengan perolehan skor ujian objektif
     */
    public function testAnalyzeFromDataProducesConsistentMasteryMatchingExamScore()
    {
        $service = new \App\Services\Cbt\Analysis\CbtTpAnalysisService();

        $testConfig = [
            'test_id'       => 10,
            'name'          => 'Ujian Formatif Matematika',
            'subject_name'  => 'Matematika',
            'passing_grade' => 75.0,
            'tp_definitions' => [
                ['tp_id' => 1, 'tp_code' => 'TP 1', 'threshold' => 75.0],
                ['tp_id' => 2, 'tp_code' => 'TP 2', 'threshold' => 75.0],
            ],
        ];

        // 4 soal objektif: 2 untuk TP 1, 2 untuk TP 2
        $items = [
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 1, 'key' => 'A', 'score' => 1.0],
            ['item_id' => 2, 'type' => 'pg', 'tp_id' => 1, 'key' => 'B', 'score' => 1.0],
            ['item_id' => 3, 'type' => 'pg_kompleks', 'tp_id' => 2, 'key' => 'A,B,D', 'score' => 1.0],
            ['item_id' => 4, 'type' => 'benar_salah', 'tp_id' => 2, 'key' => 'B,S', 'score' => 1.0],
        ];

        // Sesi siswa: Siswa 1 menjawab semua benar dengan variasi urutan jawaban
        $sessions = [
            [
                'id'             => 101,
                'student_id'     => 1,
                'student_name'   => 'Siswa Unggul',
                'nisn'           => '12345',
                'class_name'     => 'Kelas 6A',
                'status'         => 'FINISHED',
                'question_order' => json_encode([1, 2, 3, 4]),
                'score'          => 80.0,
                'final_score'    => 80.0,
            ]
        ];

        $answers = [
            ['session_id' => 101, 'student_id' => 1, 'question_id' => 1, 'answer' => 'A'],
            ['session_id' => 101, 'student_id' => 1, 'question_id' => 2, 'answer' => 'B'],
            ['session_id' => 101, 'student_id' => 1, 'question_id' => 3, 'answer' => 'B,D,A'],
            ['session_id' => 101, 'student_id' => 1, 'question_id' => 4, 'answer' => 'B,S,'],
        ];

        $result = $service->analyzeFromData($testConfig, $items, $sessions, $answers);

        $st = $result['student_analysis'][0];
        $this->assertEquals('Siswa Unggul', $st['nama_siswa']);
        $this->assertEquals(80.0, $st['exam_score']);

        // Capaian TP 1 harus 100% dan TP 2 harus 100% (tidak boleh < 70% padahal nilai ujian 80)
        $this->assertEquals(100.0, $st['tp_evaluations'][1]['student_percentage']);
        $this->assertEquals('MASTERED', $st['tp_evaluations'][1]['status']);

        $this->assertEquals(100.0, $st['tp_evaluations'][2]['student_percentage']);
        $this->assertEquals('MASTERED', $st['tp_evaluations'][2]['status']);

        $this->assertContains('TP 1', $st['mastered_tps']);
        $this->assertContains('TP 2', $st['mastered_tps']);
        $this->assertEmpty($st['needs_improvement_tps']);
    }
}

