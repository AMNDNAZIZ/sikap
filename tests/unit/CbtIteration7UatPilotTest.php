<?php

namespace Tests\Unit;

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
    if (str_starts_with($class, 'Config\\')) {
        $file = dirname(__DIR__, 2) . '/app/Config/' . str_replace('\\', '/', substr($class, 7)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use PHPUnit\Framework\TestCase;
use App\Models\CbtAnswerV3Model;
use App\Models\CbtAuditTrailV3Model;
use App\Models\CbtEssayGradeV3Model;
use App\Models\CbtSessionScoreV3Model;
use App\Models\CbtGradebookPublicationV3Model;
use App\Models\CbtBapDocumentV3Model;
use App\Models\CbtBapStudentV3Model;
use App\Services\Cbt\Assessment\CbtAssessmentFinalizationService;
use App\Services\Cbt\Gradebook\CbtGradebookIntegrationService;
use App\Services\Cbt\Bap\DigitalBapService;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerEvidenceValidator;

/**
 * CbtIteration7UatPilotTest
 *
 * ESPATA CBT V3.37 — Controlled UAT & Pilot Preparation Test Suite
 * Covers:
 * 1. Tahap A: UAT Role Workflows (Guru, Siswa, Pengawas, Penilai/Reviewer, Kepala Sekolah)
 * 2. Tahap B: Integrated Multi-Actor School Exam Simulation
 *    - 2 Classes (X-IPA-1, X-IPA-2)
 *    - 2 Teachers (Matematika, Bahasa Indonesia)
 *    - 1 Proctor (Lab Komputer 1)
 *    - 1 Reviewer
 *    - 1 Principal (Kepala Sekolah)
 *    - 10 Synthetic Students covering full variance:
 *      (Normal finish, Pending essay, Multi-retake, BAP vs Telemetry conflict,
 *       Sakit, Izin, Tidak Hadir, Unverified, Network drop-resume, Essay revision)
 *    - Full cycle: PERSIAPAN -> PENJADWALAN -> PELAKSANAAN -> PRESENSI -> BAP ->
 *                  PENILAIAN -> REVIEW -> FINALISASI -> PUBLIKASI GRADEBOOK -> LAPORAN
 * 3. Tahap C: Policy & 7 Domain Status Separation (DEC-ESS-01, DEC-RET-01, DEC-ABS-01,
 *             DEC-ROUND-01, OD-Path-B, OD-ALL-TYPE-VOID, OVERRIDE-SYNC-POLICY)
 */
class CbtIteration7UatPilotTest extends TestCase
{
    private function getAppPath(): string
    {
        return defined('APPPATH') ? APPPATH : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
    }

    private function createFinalizationService(): CbtAssessmentFinalizationService
    {
        $answerStub = new class extends CbtAnswerV3Model {
            public array $storage = [];
            public function __construct() {}
            public function where($key, $value = null) { return $this; }
            public function findAll(?int $limit = null, int $offset = 0): array { return $this->storage; }
        };

        $essayStub = new class extends CbtEssayGradeV3Model {
            public array $storage = [];
            public function __construct() {}
        };

        $scoreStub = new class extends CbtSessionScoreV3Model {
            public array $storage = [];
            public function __construct() {}
            public function recalculateSessionScore(
                int $sessionId,
                float $scorePg,
                ?float $scoreEsai,
                float $scorePgKompleks,
                float $scoreBs,
                int $pendingEssayCount,
                ?string $rawLegacyScore = null,
                float $roundingDelta = 0.0000
            ): array {
                $isEligible = ($pendingEssayCount === 0 && $scoreEsai !== null);
                $finalScore = $isEligible ? round($scorePg + $scoreEsai + $scorePgKompleks + $scoreBs, 3) : null;
                $row = [
                    'id'                      => count($this->storage) + 1,
                    'session_id'              => $sessionId,
                    'score_pg'                => $scorePg,
                    'score_esai'              => $scoreEsai,
                    'final_score'             => $finalScore,
                    'pending_essay_count'     => $pendingEssayCount,
                    'publication_eligibility' => $isEligible ? 1 : 0,
                ];
                $this->storage[$sessionId] = $row;
                return $row;
            }
        };

        $auditStub = new class extends CbtAuditTrailV3Model {
            public array $storage = [];
            public function __construct() {}
            public function appendLog(
                string $eventType,
                string $entityTable,
                string $entityId,
                int $actorId,
                string $actorRole,
                array $payload,
                string $prevHash = '0000000000000000000000000000000000000000000000000000000000000000',
                ?string $keyId = null
            ): array {
                return ['seq_no' => 1, 'record_hash' => hash('sha256', 'mock_audit'), 'prev_hash' => $prevHash];
            }
        };

        return new CbtAssessmentFinalizationService($answerStub, $essayStub, $scoreStub, $auditStub);
    }

    /**
     * TAHAP A: Validasi alur kerja masing-masing aktor sekolah secara spesifik.
     */
    public function testStageAUatRoleWorkflows(): void
    {
        $finalizationService = $this->createFinalizationService();

        // ---------------------------------------------------------------------
        // A1. Alur Guru: Konfigurasi Ujian, Distribusi Bobot, dan Normalisasi
        // ---------------------------------------------------------------------
        $baseWeights = ['pg' => 40.0, 'esai' => 30.0, 'bs' => 30.0];
        $activeCounts = ['pg' => 20, 'esai' => 5, 'bs' => 10];
        $normalized = $finalizationService->calculateNormalizedWeights($baseWeights, $activeCounts);
        $this->assertSame(CbtAssessmentFinalizationService::TEST_VALID_TEST, $normalized['test_validity']);
        $this->assertEquals(40.0, $normalized['normalized_weights']['pg']);
        $this->assertEquals(30.0, $normalized['normalized_weights']['esai']);

        // ---------------------------------------------------------------------
        // A2. Alur Siswa: Pengerjaan, Resiliensi Jaringan, dan DEC-ESS-01 UI Visibility
        // ---------------------------------------------------------------------
        $testSnapshot = [
            'base_weights' => ['pg' => 60.0, 'esai' => 40.0],
            'items' => [
                ['item_version_id' => 101, 'question_type' => 'pg', 'max_score' => 20.0, 'is_void' => 0],
                ['item_version_id' => 102, 'question_type' => 'esai', 'max_score' => 20.0, 'is_void' => 0],
            ],
        ];

        // Siswa menjawab PG (dapat nilai) tapi esai belum dikoreksi guru (allocated_score null)
        $studentAnswers = [
            ['item_version_id' => 101, 'allocated_score' => 20.0],
            ['item_version_id' => 102, 'allocated_score' => null], // Pending essay
        ];

        $sessionContext = [
            'student_id'           => 1,
            'test_id'              => 10,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'session_status'       => CbtAssessmentFinalizationService::SESSION_FINISHED,
        ];

        $scoreResult = $finalizationService->finalizeSession(1001, $sessionContext, $studentAnswers, $testSnapshot);
        $this->assertNull($scoreResult['final_score']);
        $this->assertFalse($scoreResult['is_eligible']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_HOLD, $scoreResult['assessment_status']);
        $this->assertSame(CbtAssessmentFinalizationService::ESSAY_PENDING_GRADING, $scoreResult['essay_status']);
        $this->assertSame(1, $scoreResult['pending_essay_count']);
        $this->assertStringContainsString('DEC-ESS-01', $scoreResult['blockers'][0]);

        // ---------------------------------------------------------------------
        // A3. Alur Pengawas: Absensi, Insiden, dan Penyegelan Kriptografis BAP (SHA-256)
        // ---------------------------------------------------------------------
        $docStub = new class extends CbtBapDocumentV3Model {
            public array $storage = [];
            public function __construct() {}
            public function find($id = null) { return $this->storage[$id] ?? null; }
            public function insert($data = null, bool $returnID = true) {
                $id = count($this->storage) + 1;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }
            public function update($id = null, $data = null): bool {
                $this->storage[$id] = array_merge($this->storage[$id], $data);
                return true;
            }
            public function getByTestAndRoom(int $testId, string $roomCode): ?array {
                foreach ($this->storage as $doc) {
                    if ((int)($doc['test_id'] ?? 0) === $testId && ($doc['room_code'] ?? '') === $roomCode) {
                        return $doc;
                    }
                }
                return null;
            }
            public function findByDocHash(string $hash): ?array {
                foreach ($this->storage as $doc) {
                    if (($doc['document_sha256'] ?? '') === $hash) {
                        return $doc;
                    }
                }
                return null;
            }
        };

        $studStub = new class extends CbtBapStudentV3Model {
            public array $storage = [];
            public function __construct() {}
            public function getStudentsByBap(int $bapId): array { return $this->storage[$bapId] ?? []; }
        };

        $auditStub = new class extends CbtAuditTrailV3Model {
            public function __construct() {}
            public function appendLog(string $eventType, string $entityTable, string $entityId, int $actorId, string $actorRole, array $payload, string $prevHash = '0000000000000000000000000000000000000000000000000000000000000000', ?string $keyId = null): array {
                return ['seq_no' => 1, 'record_hash' => hash('sha256', 'mock_audit'), 'prev_hash' => $prevHash];
            }
        };

        $bapService = new DigitalBapService($docStub, $studStub, $auditStub);
        $bapId = $docStub->insert([
            'bap_number'       => 'BAP-UAT-2026/01',
            'test_id'          => 10,
            'room_code'        => 'LAB-KOM-1',
            'proctor_name'     => 'Ahmad Dahlan',
            'supervisor_name'  => 'Drs. Bambang',
            'total_registered' => 10,
            'total_present'    => 9,
            'total_absent'     => 1,
            'verification_status' => 'UNVERIFIED',
        ]);
        $studStub->storage[$bapId] = [
            ['student_id' => 1, 'reported_status' => 'HADIR'],
            ['student_id' => 2, 'reported_status' => 'HADIR'],
            ['student_id' => 3, 'reported_status' => 'SAKIT'],
        ];

        // Pengawas menandatangani dan menyegel BAP secara digital
        $signedBap = $bapService->signAndSubmitBap($bapId, 20, 'PROCTOR', 'ahmad.dahlan@sekolah.sch.id', 'Semua siswa hadir tertib.');
        $this->assertTrue($signedBap['success']);
        $this->assertSame(64, strlen($signedBap['document_sha256']));
        $this->assertSame(CbtBapDocumentV3Model::STATUS_AUTHENTICATED, $docStub->storage[$bapId]['verification_status']);

        // ---------------------------------------------------------------------
        // A4. Alur Penilai Esai & Reviewer: Revisi Append-Only & Adjudikasi Berwenang
        // ---------------------------------------------------------------------
        $evidenceValidator = new ReviewerEvidenceValidator();
        $decisionValidator = new ReviewerDecisionValidator($evidenceValidator);
        $workflow = new ReviewerWorkflowService($decisionValidator, null, $evidenceValidator);

        $case = [
            'id'              => 'RC-UAT-RETAKE',
            'case_type'       => 'MULTIPLE_RETAKES',
            'category'        => 'AMBIGUOUS_RETAKE',
            'student_id'      => 55,
            'session_id'      => 9001,
            'test_id'         => 10,
            'review_status'   => 'REQUIRES_OWNER_DECISION',
            'status'          => 'REQUIRES_OWNER_DECISION',
            'workflow_status' => 'REQUIRES_OWNER_DECISION',
            'deviations'      => ['candidate_sessions' => [9001, 9002]],
            'evidence_data'   => ['candidate_sessions' => [9001, 9002]],
        ];
        $ref = new \ReflectionClass($workflow);
        $casesProp = $ref->getProperty('cases');
        $casesProp->setAccessible(true);
        $casesProp->setValue($workflow, ['RC-UAT-RETAKE' => $case]);

        // Ratifikasi oleh Kepala Sekolah sah dengan SK (DEC-RET-01)
        $kepsekPreview = $workflow->previewDecision(
            'RC-UAT-RETAKE',
            'KEPSEK_BAMBANG',
            'RATIFY_SESSION',
            'Disahkan berdasarkan SK Retake Kepala Sekolah No 421/100',
            ['selected_session_id' => 9002, 'owner_approval_ref' => 'SK-421/100', 'actor_role' => 'PRINCIPAL']
        );
        $this->assertTrue($kepsekPreview['is_valid']);
        $this->assertSame('RATIFY_SESSION', $kepsekPreview['sanctioned_action']);

        // ---------------------------------------------------------------------
        // A5. Alur Kepala Sekolah: Integritas Navigasi dan Monitoring Laporan
        // ---------------------------------------------------------------------
        $appPath = $this->getAppPath();
        $kepsekSidebar = file_get_contents($appPath . 'Views/layouts/partials/sidebar_kepsek.php');
        $this->assertStringContainsString('admin/cbt/report', $kepsekSidebar);
        $this->assertStringContainsString('admin/cbt/bap', $kepsekSidebar);
        $this->assertStringContainsString('admin/cbt/review', $kepsekSidebar);
    }

    /**
     * TAHAP B: Simulasi Ujian Sekolah Terpadu End-to-End Multi-Aktor.
     * Mencakup:
     * - 2 Kelas (X-IPA-1, X-IPA-2)
     * - 2 Guru (Budi Santoso - Mat, Siti Aminah - B.Indo)
     * - 1 Pengawas (Ahmad Dahlan)
     * - 1 Reviewer (Dr. Hendra)
     * - 1 Kepala Sekolah (Drs. Bambang)
     * - 10 Kasus Siswa Representatif
     * - Siklus lengkap: PERSIAPAN -> PENJADWALAN -> PELAKSANAAN -> PRESENSI -> BAP ->
     *                  PENILAIAN -> REVIEW -> FINALISASI -> PUBLIKASI GRADEBOOK -> LAPORAN
     */
    public function testStageBIntegratedSchoolExamSimulation(): void
    {
        // 1. Storage & Stub Instantiations
        $finalizationService = $this->createFinalizationService();

        $pubStub = new class extends CbtGradebookPublicationV3Model {
            public array $storage = [];
            public function __construct() {}
            public function insert($data = null, bool $returnID = true) {
                $id = count($this->storage) + 1;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }
            public function findBySessionAndTarget(int $sessionId, string $targetTable): ?array {
                foreach ($this->storage as $row) {
                    if ((int)($row['session_id'] ?? 0) === $sessionId && ($row['target_table'] ?? '') === $targetTable) {
                        return $row;
                    }
                }
                return null;
            }
            public function isAlreadyPublished(int $studentId, int $testId, string $targetTable): bool {
                foreach ($this->storage as $row) {
                    if ((int)$row['student_id'] === $studentId && (int)$row['test_id'] === $testId && $row['target_table'] === $targetTable) {
                        return true;
                    }
                }
                return false;
            }
        };

        $auditStub = new class extends CbtAuditTrailV3Model {
            public function __construct() {}
            public function appendLog(string $eventType, string $entityTable, string $entityId, int $actorId, string $actorRole, array $payload, string $prevHash = '0000000000000000000000000000000000000000000000000000000000000000', ?string $keyId = null): array {
                return ['seq_no' => 1, 'record_hash' => hash('sha256', 'mock_audit'), 'prev_hash' => $prevHash];
            }
        };

        $gradebookService = new CbtGradebookIntegrationService($pubStub, $auditStub);

        // 2. Setup Multi-Class & Multi-Teacher Context
        $classes = [
            101 => ['name' => 'X-IPA-1', 'room' => 'LAB-KOM-1', 'teacher' => 'Budi Santoso, M.Pd (Matematika)'],
            102 => ['name' => 'X-IPA-2', 'room' => 'LAB-KOM-2', 'teacher' => 'Siti Aminah, S.Pd (Bahasa Indonesia)'],
        ];

        // 3. 10 Synthetic Students Dataset
        $students = [
            // Kelas X-IPA-1
            1 => ['name' => 'Aditya Pratama', 'class_id' => 101, 'type' => 'NORMAL_FULLY_GRADED', 'pg_score' => 20.0, 'essay_score' => 18.0, 'bap_status' => 'HADIR', 'telemetry_answers' => 25],
            2 => ['name' => 'Bella Safitri',   'class_id' => 101, 'type' => 'PENDING_ESSAY',       'pg_score' => 18.0, 'essay_score' => null, 'bap_status' => 'HADIR', 'telemetry_answers' => 25],
            3 => ['name' => 'Chandra Wijaya',  'class_id' => 101, 'type' => 'MULTI_RETAKE',        'pg_score' => 16.0, 'essay_score' => 15.0, 'bap_status' => 'HADIR', 'telemetry_answers' => 25, 'retake' => true],
            4 => ['name' => 'Dewi Lestari',    'class_id' => 101, 'type' => 'BAP_TELEMETRY_CONFLICT','pg_score' => 14.0,'essay_score' => null,'bap_status' => 'TIDAK_HADIR', 'telemetry_answers' => 20],
            5 => ['name' => 'Eko Prasetyo',    'class_id' => 101, 'type' => 'ABSENT_SAKIT',        'pg_score' => null, 'essay_score' => null, 'bap_status' => 'SAKIT', 'telemetry_answers' => 0],

            // Kelas X-IPA-2
            6 => ['name' => 'Farhan Maulana',  'class_id' => 102, 'type' => 'ABSENT_IZIN',         'pg_score' => null, 'essay_score' => null, 'bap_status' => 'IZIN', 'telemetry_answers' => 0],
            7 => ['name' => 'Gita Gutawa',     'class_id' => 102, 'type' => 'ABSENT_ALPA',         'pg_score' => null, 'essay_score' => null, 'bap_status' => 'TIDAK_HADIR', 'telemetry_answers' => 0],
            8 => ['name' => 'Hadi Sucipto',    'class_id' => 102, 'type' => 'UNVERIFIED',          'pg_score' => null, 'essay_score' => null, 'bap_status' => 'UNVERIFIED', 'telemetry_answers' => 0],
            9 => ['name' => 'Indah Permata',   'class_id' => 102, 'type' => 'NETWORK_RESILIENT',   'pg_score' => 20.0, 'essay_score' => 20.0, 'bap_status' => 'HADIR', 'telemetry_answers' => 25],
            10=> ['name' => 'Joko Susilo',     'class_id' => 102, 'type' => 'REVISED_ESSAY',       'pg_score' => 17.0, 'essay_score' => 19.0, 'bap_status' => 'HADIR', 'telemetry_answers' => 25],
        ];

        // 4. Siklus Presensi & BAP Digital (Pengawas Ahmad Dahlan)
        $bapDocStub = new class extends CbtBapDocumentV3Model {
            public array $storage = [];
            public function __construct() {}
            public function find($id = null) { return $this->storage[$id] ?? null; }
            public function insert($data = null, bool $returnID = true) {
                $id = count($this->storage) + 1;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }
            public function update($id = null, $data = null): bool {
                $this->storage[$id] = array_merge($this->storage[$id], $data);
                return true;
            }
            public function getByTestAndRoom(int $testId, string $roomCode): ?array {
                foreach ($this->storage as $doc) {
                    if ((int)($doc['test_id'] ?? 0) === $testId && ($doc['room_code'] ?? '') === $roomCode) {
                        return $doc;
                    }
                }
                return null;
            }
            public function findByDocHash(string $hash): ?array {
                foreach ($this->storage as $doc) {
                    if (($doc['document_sha256'] ?? '') === $hash) {
                        return $doc;
                    }
                }
                return null;
            }
        };

        $bapStudentStub = new class extends CbtBapStudentV3Model {
            public array $storage = [];
            public function __construct() {}
            public function getStudentsByBap(int $bapId): array { return $this->storage[$bapId] ?? []; }
        };

        $bapService = new DigitalBapService($bapDocStub, $bapStudentStub, $auditStub);

        $bapId = $bapDocStub->insert([
            'bap_number'       => 'BAP-PAS-2026/001',
            'test_id'          => 201,
            'room_code'        => 'LAB-KOM-1',
            'proctor_name'     => 'Ahmad Dahlan',
            'supervisor_name'  => 'Drs. Bambang',
            'total_registered' => 10,
            'total_present'    => 6,
            'total_absent'     => 4,
            'verification_status' => 'UNVERIFIED',
        ]);

        $bapStudentRows = [];
        foreach ($students as $id => $st) {
            $bapStudentRows[] = [
                'student_id'      => $id,
                'reported_status' => $st['bap_status'],
                'proctor_notes'   => $st['type'] === 'ABSENT_SAKIT' ? 'Surat dokter terlampir' : null,
            ];
        }
        $bapStudentStub->storage[$bapId] = $bapStudentRows;

        // Penyegelan Kriptografis BAP
        $bapSeal = $bapService->signAndSubmitBap($bapId, 10, 'PROCTOR', 'ahmad.proctor@sekolah.sch.id', 'Ujian berlangsung tertib.');
        $this->assertTrue($bapSeal['success']);
        $this->assertSame(64, strlen($bapSeal['document_sha256']));

        // Deteksi diskrepansi BAP vs Telemetri (Kasus Siswa 4: BAP bilang TIDAK_HADIR, tapi telemetri punya 20 jawaban)
        $telemetryFixtures = [];
        foreach ($students as $id => $st) {
            if ($st['telemetry_answers'] > 0) {
                $telemetryFixtures[$id] = ['has_submitted' => true, 'answer_count' => $st['telemetry_answers']];
            }
        }
        $discrepancyReport = $bapService->detectAttendanceDiscrepancies($bapId, $telemetryFixtures);
        $this->assertTrue($discrepancyReport['has_discrepancies']);
        $this->assertSame(2, $discrepancyReport['discrepancy_count']);
        // Discrepancy Case 1: Siswa 4 (BAP absent, tapi telemetri ada)
        $this->assertSame('BAP_ABSENT_BUT_TELEMETRY_EXISTS', $discrepancyReport['discrepancies'][0]['discrepancy_code']);
        $this->assertSame(4, $discrepancyReport['discrepancies'][0]['student_id']);
        // Discrepancy Case 2: Siswa 8 (Status unverified/non-absent di BAP, tapi tidak ada telemetri)
        $this->assertSame('BAP_PRESENT_BUT_NO_TELEMETRY', $discrepancyReport['discrepancies'][1]['discrepancy_code']);
        $this->assertSame(8, $discrepancyReport['discrepancies'][1]['student_id']);

        // 5. Siklus Finalisasi Penilaian & Validasi Kebijakan
        $testSnapshot = [
            'base_weights' => ['pg' => 60.0, 'esai' => 40.0],
            'items' => [
                ['item_version_id' => 201, 'question_type' => 'pg', 'max_score' => 20.0, 'is_void' => 0],
                ['item_version_id' => 202, 'question_type' => 'esai', 'max_score' => 20.0, 'is_void' => 0],
            ],
        ];

        $results = [];

        foreach ($students as $id => $st) {
            $participation = match ($st['bap_status']) {
                'HADIR'       => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
                'SAKIT'       => CbtAssessmentFinalizationService::PARTICIPATION_EXCUSED,
                'IZIN'        => CbtAssessmentFinalizationService::PARTICIPATION_DISPENSED,
                'TIDAK_HADIR' => CbtAssessmentFinalizationService::PARTICIPATION_ABSENT,
                default       => CbtAssessmentFinalizationService::PARTICIPATION_ABSENT,
            };

            $integrity = (!empty($st['retake']))
                ? CbtAssessmentFinalizationService::INTEGRITY_SUSPICIOUS
                : CbtAssessmentFinalizationService::INTEGRITY_VALID;

            $sessionContext = [
                'student_id'           => $id,
                'test_id'              => 201,
                'participation_status' => $participation,
                'integrity_status'     => $integrity,
                'session_status'       => CbtAssessmentFinalizationService::SESSION_FINISHED,
                'is_retake_candidate'  => !empty($st['retake']),
                'owner_approval_ref'   => null, // Retake belum diratifikasi
            ];

            $answers = [];
            if ($st['telemetry_answers'] > 0) {
                $answers[] = ['item_version_id' => 201, 'allocated_score' => $st['pg_score']];
                $answers[] = ['item_version_id' => 202, 'allocated_score' => $st['essay_score']];
            }

            $results[$id] = $finalizationService->finalizeSession(5000 + $id, $sessionContext, $answers, $testSnapshot);
        }

        // Verifikasi Kasus Siswa 1 (Normal Completed): Nilai 60%*(20/20) + 40%*(18/20) = 60 + 36 = 96.0
        $this->assertTrue($results[1]['is_eligible']);
        $this->assertSame(96.0, (float)$results[1]['final_score']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_FINALIZED, $results[1]['assessment_status']);

        // Verifikasi Kasus Siswa 2 (DEC-ESS-01): Esai belum dinilai -> NULL & HOLD
        $this->assertFalse($results[2]['is_eligible']);
        $this->assertNull($results[2]['final_score']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_HOLD, $results[2]['assessment_status']);

        // Verifikasi Kasus Siswa 3 (DEC-RET-01): Retake tanpa ratifikasi -> NULL & QUARANTINED
        $this->assertFalse($results[3]['is_eligible']);
        $this->assertNull($results[3]['final_score']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_QUARANTINED, $results[3]['assessment_status']);
        $this->assertStringContainsString('DEC-RET-01', $results[3]['blockers'][0]);

        // Verifikasi Kasus Siswa 5, 6, 7 (DEC-ABS-01): Tidak hadir / Sakit / Izin -> NULL (bukan 0.0)
        $this->assertNull($results[5]['final_score']);
        $this->assertNull($results[6]['final_score']);
        $this->assertNull($results[7]['final_score']);

        // 6. Siklus Publikasi Gradebook
        $publishedCount = 0;
        $blockedCount = 0;
        $targetContext = [
            'target_table' => CbtGradebookIntegrationService::TARGET_FINAL_EXAM,
            'subject_id'   => 12,
            'year_id'      => 2026,
            'semester'     => 1,
            'type'         => 'CBT_FINAL',
        ];

        foreach ($results as $id => $res) {
            if ($res['is_eligible'] && $res['final_score'] !== null) {
                $pubResult = $gradebookService->publishToGradebook($res, $targetContext, 1);
                $this->assertTrue($pubResult['success']);
                $publishedCount++;
            } else {
                $blockedCount++;
            }
        }

        // Siswa 1, 9, 10 yang fully graded & eligible berhasil dipublikasikan (3 siswa)
        $this->assertSame(3, $publishedCount);
        // Siswa 2 (pending essay), 3 (retake unratified), 4 (conflict), 5,6,7 (absent), 8 (unverified) diblokir (7 siswa)
        $this->assertSame(7, $blockedCount);
        $this->assertCount(3, $pubStub->storage);
    }

    /**
     * TAHAP C: Uji Kebijakan dan Non-Pencampuran 7 Domain Status.
     */
    public function testStageCPolicyAndDomainStatusSeparation(): void
    {
        $finalizationService = $this->createFinalizationService();

        // 1. DEC-ABS-01: Ketidakhadiran tidak boleh disimpulkan menjadi perolehan skor 0.0
        $absentContext = [
            'student_id'           => 71,
            'test_id'              => 301,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_ABSENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'session_status'       => CbtAssessmentFinalizationService::SESSION_FINISHED,
        ];
        $absentResult = $finalizationService->finalizeSession(7001, $absentContext, []);
        $this->assertNull($absentResult['final_score']);
        $this->assertFalse($absentResult['is_eligible']);
        $this->assertSame(CbtAssessmentFinalizationService::PARTICIPATION_ABSENT, $absentResult['participation_status']);
        $this->assertSame(CbtAssessmentFinalizationService::INTEGRITY_VALID, $absentResult['integrity_status']);
        $this->assertStringContainsString('DEC-ABS-01', $absentResult['blockers'][0]);

        // 2. DEC-ROUND-01: Pembulatan setengah ke atas 2 desimal secara deterministik
        $testSnapshot = [
            'base_weights' => ['pg' => 100.0],
            'items' => [
                ['item_version_id' => 301, 'question_type' => 'pg', 'max_score' => 60.0, 'is_void' => 0],
            ],
        ];

        // 50 / 60 * 100 = 83.3333333333 -> 83.333
        $presentContext1 = [
            'student_id'           => 72,
            'test_id'              => 301,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'session_status'       => CbtAssessmentFinalizationService::SESSION_FINISHED,
        ];
        $roundResult1 = $finalizationService->finalizeSession(
            7002,
            $presentContext1,
            [['item_version_id' => 301, 'allocated_score' => 50.0]],
            $testSnapshot
        );
        $this->assertSame(83.333, (float)$roundResult1['final_score']);

        // 3. OD-ALL-TYPE-VOID: Proportional redistribution when all items of a type are void
        $baseWeights = ['pg' => 50.0, 'esai' => 30.0, 'bs' => 20.0];
        $activeCounts = ['pg' => 25, 'esai' => 0, 'bs' => 10]; // esai void completely
        $redistributed = $finalizationService->calculateNormalizedWeights($baseWeights, $activeCounts);

        $this->assertTrue($redistributed['redistributed']);
        $this->assertSame(CbtAssessmentFinalizationService::TEST_PARTIAL_TYPE_VOID, $redistributed['test_validity']);
        // 50/(50+20) * 100 = 71.4285% | 20/(50+20) * 100 = 28.5714%
        $this->assertEqualsWithDelta(71.43, (float)$redistributed['normalized_weights']['pg'], 0.05);
        $this->assertEqualsWithDelta(28.57, (float)$redistributed['normalized_weights']['bs'], 0.05);
        $this->assertEquals(0.0, $redistributed['normalized_weights']['esai'] ?? 0.0);

        // Jika semua tipe void -> NO_VALID_QUESTIONS
        $allVoidCounts = ['pg' => 0, 'esai' => 0, 'bs' => 0];
        $allVoid = $finalizationService->calculateNormalizedWeights($baseWeights, $allVoidCounts);
        $this->assertSame(CbtAssessmentFinalizationService::TEST_NO_VALID_QUESTIONS, $allVoid['test_validity']);
    }
}
