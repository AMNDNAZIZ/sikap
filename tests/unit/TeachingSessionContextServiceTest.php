<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\TeachingSessionContextService;

class TeachingSessionContextServiceTest extends TestCase
{
    private TeachingSessionContextService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Instantiate service without requiring active DB connection for pure logic tests
        $this->service = new class extends TeachingSessionContextService {
            public function __construct() {
                // Avoid calling BaseService DB connect for isolated unit tests
            }
        };
    }

    public function testWeekOfMonthCalculation()
    {
        // 1st of month -> Week 1
        $this->assertEquals(1, $this->service->getWeekOfMonth('2026-11-01'));
        // 7th of month -> Week 1
        $this->assertEquals(1, $this->service->getWeekOfMonth('2026-11-07'));
        // 8th of month -> Week 2
        $this->assertEquals(2, $this->service->getWeekOfMonth('2026-11-08'));
        // 14th of month -> Week 2
        $this->assertEquals(2, $this->service->getWeekOfMonth('2026-11-14'));
        // 15th of month -> Week 3
        $this->assertEquals(3, $this->service->getWeekOfMonth('2026-11-15'));
        // 22nd of month -> Week 4
        $this->assertEquals(4, $this->service->getWeekOfMonth('2026-11-22'));
        // 29th, 30th, 31st -> Week 5 (capped at max 5)
        $this->assertEquals(5, $this->service->getWeekOfMonth('2026-11-29'));
        $this->assertEquals(5, $this->service->getWeekOfMonth('2026-11-30'));
    }

    public function testSemesterFromMonth()
    {
        // Ganjil: Juli - Desember (7 - 12)
        $this->assertEquals(1, $this->service->getSemesterFromMonth(7));
        $this->assertEquals(1, $this->service->getSemesterFromMonth(10));
        $this->assertEquals(1, $this->service->getSemesterFromMonth(11));
        $this->assertEquals(1, $this->service->getSemesterFromMonth(12));

        // Genap: Januari - Juni (1 - 6)
        $this->assertEquals(2, $this->service->getSemesterFromMonth(1));
        $this->assertEquals(2, $this->service->getSemesterFromMonth(3));
        $this->assertEquals(2, $this->service->getSemesterFromMonth(6));
    }

    public function testDaysAndMonthsConstants()
    {
        $this->assertEquals('Senin', TeachingSessionContextService::DAYS_ID[1]);
        $this->assertEquals('Jumat', TeachingSessionContextService::DAYS_ID[5]);
        $this->assertEquals('Minggu', TeachingSessionContextService::DAYS_ID[7]);

        $this->assertEquals('Januari', TeachingSessionContextService::MONTHS_ID[1]);
        $this->assertEquals('November', TeachingSessionContextService::MONTHS_ID[11]);
        $this->assertEquals('Desember', TeachingSessionContextService::MONTHS_ID[12]);
    }

    public function testLooseCouplingSortingAndDecorationLogic()
    {
        $targetWeek = 1;
        $monthName = 'November';
        $jp = 4;

        // Mock decorated ATPs
        $atps = [
            [
                'id' => 10,
                'lingkup_materi' => 'Materi B (Urutan 2)',
                'semester' => 1,
                'urutan' => 2,
                'is_promes_target' => false,
                'promes_jp' => 0,
                'promes_badge' => null
            ],
            [
                'id' => 20,
                'lingkup_materi' => 'Materi Target Promes',
                'semester' => 1,
                'urutan' => 1,
                'is_promes_target' => true,
                'promes_jp' => $jp,
                'promes_badge' => "⭐ Target Promes Mg {$targetWeek} {$monthName} ({$jp} JP)"
            ],
            [
                'id' => 5,
                'lingkup_materi' => 'Materi Lama',
                'semester' => 1,
                'urutan' => 3,
                'is_promes_target' => false,
                'promes_jp' => 0,
                'promes_badge' => null
            ]
        ];

        // Sort so that is_promes_target is first, but others remain fully available (loose coupling)
        usort($atps, function ($a, $b) {
            $targetA = !empty($a['is_promes_target']) ? 1 : 0;
            $targetB = !empty($b['is_promes_target']) ? 1 : 0;
            if ($targetA !== $targetB) {
                return $targetB - $targetA;
            }
            if (($a['semester'] ?? 0) !== ($b['semester'] ?? 0)) {
                return ($a['semester'] ?? 0) - ($b['semester'] ?? 0);
            }
            return ($a['urutan'] ?? 0) - ($b['urutan'] ?? 0);
        });

        // Assert that the Promes target is prioritized first in the list
        $this->assertEquals(20, $atps[0]['id'], 'Target promes should be sorted to index 0');
        $this->assertTrue($atps[0]['is_promes_target']);
        $this->assertStringContainsString('Target Promes', $atps[0]['promes_badge']);

        // Assert that all other ATPs are still present and accessible (no locking)
        $this->assertCount(3, $atps);
        $this->assertEquals(10, $atps[1]['id']);
        $this->assertEquals(5, $atps[2]['id']);
    }

    public function testBuildAttendanceSummaryAllPresent()
    {
        $students = [
            ['id' => 1, 'name' => 'Ahmad Dani'],
            ['id' => 2, 'name' => 'Budi Santoso'],
            ['id' => 3, 'name' => 'Citra Lestari'],
        ];
        $attMap = []; // default all 'H'

        $summary = $this->service->buildAttendanceSummary($students, $attMap);

        $this->assertEquals(3, $summary['total']);
        $this->assertEquals(3, $summary['hadir']);
        $this->assertEquals(0, $summary['sakit']);
        $this->assertEquals(0, $summary['izin']);
        $this->assertEquals(0, $summary['alpa']);
        $this->assertEquals('Kehadiran: Seluruh siswa (3) hadir lengkap.', $summary['attendance_text']);
    }

    public function testBuildAttendanceSummaryWithAbsentees()
    {
        $students = [
            ['id' => 1, 'name' => 'Ahmad Dani'],
            ['id' => 2, 'name' => 'Budi Santoso'],
            ['id' => 3, 'name' => 'Citra Lestari'],
            ['id' => 4, 'name' => 'Dewi Sartika'],
        ];
        $attMap = [
            2 => 'S',
            4 => 'I',
        ];

        $summary = $this->service->buildAttendanceSummary($students, $attMap);

        $this->assertEquals(4, $summary['total']);
        $this->assertEquals(2, $summary['hadir']);
        $this->assertEquals(1, $summary['sakit']);
        $this->assertEquals(1, $summary['izin']);
        $this->assertEquals(0, $summary['alpa']);
        $this->assertContains('Budi Santoso', $summary['sakit_names']);
        $this->assertContains('Dewi Sartika', $summary['izin_names']);
        $this->assertStringContainsString('2 Hadir', $summary['attendance_text']);
        $this->assertStringContainsString('1 Sakit (Budi Santoso)', $summary['attendance_text']);
        $this->assertStringContainsString('1 Izin (Dewi Sartika)', $summary['attendance_text']);
    }

    public function testGetReflectionSuggestions()
    {
        $materi = 'Sistem Tata Surya';

        // Terlaksana
        $sugTerlaksana = $this->service->getReflectionSuggestions('terlaksana', $materi);
        $this->assertNotEmpty($sugTerlaksana);
        $this->assertStringContainsString('Sistem Tata Surya', $sugTerlaksana[0]['text']);
        $this->assertArrayHasKey('label', $sugTerlaksana[0]);

        // Sebagian
        $sugSebagian = $this->service->getReflectionSuggestions('sebagian', $materi);
        $this->assertNotEmpty($sugSebagian);
        $this->assertStringContainsString('terlaksana sebagian', $sugSebagian[0]['text']);

        // Tertunda
        $sugTertunda = $this->service->getReflectionSuggestions('tertunda', $materi);
        $this->assertNotEmpty($sugTertunda);
        $this->assertStringContainsString('dialihkan', $sugTertunda[0]['text']);

        // Unknown
        $sugEmpty = $this->service->getReflectionSuggestions('unknown_status');
        $this->assertEmpty($sugEmpty);
    }

    public function testCalculateScoreConversionStandard()
    {
        // 3 participants: 90 (max), 70 (mid), 50 (min)
        $rawScores = [
            101 => 90,
            102 => 70,
            103 => 50,
        ];

        // Target: YA = 100, YB = 75
        $conversion = $this->service->calculateScoreConversion($rawScores, 100.0, 75.0);

        $this->assertTrue($conversion['has_scores']);
        $this->assertEquals(90.0, $conversion['xa']);
        $this->assertEquals(50.0, $conversion['xb']);
        $this->assertEquals(100.0, $conversion['ya']);
        $this->assertEquals(75.0, $conversion['yb']);

        // Formula: ((YA - YB) / (XA - XB)) * (NX - XB) + YB
        // Slope = 25 / 40 = 0.625
        // Student 101 (90): 0.625 * (90 - 50) + 75 = 25 + 75 = 100.0
        // Student 102 (70): 0.625 * (70 - 50) + 75 = 12.5 + 75 = 87.5
        // Student 103 (50): 0.625 * (50 - 50) + 75 = 0 + 75 = 75.0
        $this->assertEquals(100.0, $conversion['results'][101]['converted_score']);
        $this->assertEquals(87.5, $conversion['results'][102]['converted_score']);
        $this->assertEquals(75.0, $conversion['results'][103]['converted_score']);
        $this->assertTrue($conversion['results'][101]['ikut_ujian']);
    }

    public function testCalculateScoreConversionIdenticalOrAbsentScores()
    {
        // When all participant scores are identical (XA == XB)
        $identicalScores = [
            201 => 80,
            202 => 80,
            203 => null, // Absent student
        ];

        $conversion = $this->service->calculateScoreConversion($identicalScores, 95.0, 70.0);

        $this->assertTrue($conversion['has_scores']);
        $this->assertEquals(80.0, $conversion['xa']);
        $this->assertEquals(80.0, $conversion['xb']);

        // When XA == XB, all participants receive target YA
        $this->assertEquals(95.0, $conversion['results'][201]['converted_score']);
        $this->assertEquals(95.0, $conversion['results'][202]['converted_score']);
        $this->assertTrue($conversion['results'][201]['ikut_ujian']);

        // Absent student should receive 0.0 and ikut_ujian = false
        $this->assertEquals(0.0, $conversion['results'][203]['converted_score']);
        $this->assertFalse($conversion['results'][203]['ikut_ujian']);
    }
}
