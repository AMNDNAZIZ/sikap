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
}

