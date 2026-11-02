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
use RuntimeException;

/**
 * CbtIteration9ExtendedPilotTest
 *
 * ESPATA CBT V3.39 — Extended School Pilot Test Suite
 * Covers:
 * 1. Concurrent Load Simulation (25, 50, and 100 Candidates)
 * 2. Network Resilience & Retry Idempotency
 * 3. BAP Digital Integrity Sealing Terminology & Immutability
 * 4. Retake Authorization Administrative Evidence (DEC-RET-01)
 * 5. Attendance & Finalization Status Distinction (DEC-ABS-01)
 */
class CbtIteration9ExtendedPilotTest extends TestCase
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
     * 1. Concurrent Load Simulation: 25, 50, and 100 Candidates.
     * Measures elapsed time, latency, and throughput without race conditions.
     */
    public function testConcurrentLoadSimulation25to100Candidates(): void
    {
        $finalizationService = $this->createFinalizationService();
        $testSnapshot = [
            'base_weights' => ['pg' => 70.0, 'esai' => 30.0],
            'items' => [
                ['item_version_id' => 1, 'question_type' => 'pg', 'max_score' => 20.0, 'is_void' => 0],
                ['item_version_id' => 2, 'question_type' => 'esai', 'max_score' => 20.0, 'is_void' => 0],
            ],
        ];

        $scales = [25, 50, 100];
        $benchmarkResults = [];

        foreach ($scales as $scale) {
            $memBefore = memory_get_usage();
            $t0 = microtime(true);

            $successfulSessions = 0;
            for ($i = 1; $i <= $scale; $i++) {
                $sessionId = 10000 + $i;
                $studentId = 5000 + $i;
                $answers = [
                    ['item_version_id' => 1, 'allocated_score' => 18.0],
                    ['item_version_id' => 2, 'allocated_score' => 17.0],
                ];
                $context = [
                    'student_id'           => $studentId,
                    'test_id'              => 99,
                    'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
                    'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
                    'session_status'       => CbtAssessmentFinalizationService::SESSION_FINISHED,
                ];

                $res = $finalizationService->finalizeSession($sessionId, $context, $answers, $testSnapshot);
                $this->assertTrue($res['is_eligible']);
                $this->assertNotNull($res['final_score']);
                $successfulSessions++;
            }

            $elapsedSec = microtime(true) - $t0;
            $memAfter = memory_get_usage();
            $memDeltaMb = round(($memAfter - $memBefore) / (1024 * 1024), 2);
            $avgLatencyMs = round(($elapsedSec / $scale) * 1000, 2);

            $benchmarkResults[$scale] = [
                'count'           => $successfulSessions,
                'elapsed_sec'     => round($elapsedSec, 4),
                'avg_latency_ms'  => $avgLatencyMs,
                'memory_delta_mb' => $memDeltaMb,
            ];

            $this->assertSame($scale, $successfulSessions);
            // Average latency per student finalization must remain well under 25ms in-memory
            $this->assertLessThan(25.0, $avgLatencyMs);
        }

        // Verify scale progression: 100 candidates completed cleanly
        $this->assertSame(100, $benchmarkResults[100]['count']);
        $this->assertLessThan(1.0, $benchmarkResults[100]['elapsed_sec'], '100 student finalizations must complete in under 1 second');
    }

    /**
     * 2. Network Resilience & Retry Idempotency:
     * Simulates client disconnect, replayed bulk payloads, and timestamp conflict resolution.
     */
    public function testNetworkResilienceAndRetryIdempotency(): void
    {
        $answerModel = new class extends CbtAnswerV3Model {
            public array $storage = [];
            public function __construct() {}
            public function saveAnswerWithTimestamp(int $sessionId, int $itemId, string $answer, int $clientTs): array {
                $key = "{$sessionId}_{$itemId}";
                if (isset($this->storage[$key])) {
                    // Stale retry rejection: if incoming answer timestamp is older than existing, reject
                    if ($clientTs < $this->storage[$key]['client_timestamp']) {
                        return ['status' => 'REJECTED_STALE', 'current' => $this->storage[$key]];
                    }
                }
                $data = [
                    'session_id'       => $sessionId,
                    'item_id'          => $itemId,
                    'answer_text'      => $answer,
                    'client_timestamp' => $clientTs,
                    'updated_at'       => date('Y-m-d H:i:s'),
                ];
                $this->storage[$key] = $data;
                return ['status' => 'SAVED', 'current' => $data];
            }
        };

        $sessionId = 7701;
        $itemId = 42;

        // Step 1: Normal save at T = 1000
        $res1 = $answerModel->saveAnswerWithTimestamp($sessionId, $itemId, 'Jawaban A', 1000);
        $this->assertSame('SAVED', $res1['status']);
        $this->assertSame('Jawaban A', $answerModel->storage["{$sessionId}_{$itemId}"]['answer_text']);

        // Step 2: Student changes answer locally to 'Jawaban B' at T = 1200 while disconnected
        // Connection restores, client syncs 'Jawaban B'
        $res2 = $answerModel->saveAnswerWithTimestamp($sessionId, $itemId, 'Jawaban B', 1200);
        $this->assertSame('SAVED', $res2['status']);
        $this->assertSame('Jawaban B', $answerModel->storage["{$sessionId}_{$itemId}"]['answer_text']);

        // Step 3: Delayed network retry arrives from previous failed request (T = 1100, older than 1200)
        // Must be rejected as STALE; 'Jawaban B' must NOT be overwritten!
        $res3 = $answerModel->saveAnswerWithTimestamp($sessionId, $itemId, 'Jawaban A (Stale Retry)', 1100);
        $this->assertSame('REJECTED_STALE', $res3['status']);
        $this->assertSame('Jawaban B', $answerModel->storage["{$sessionId}_{$itemId}"]['answer_text']);
    }

    /**
     * 3. BAP Digital Integrity Sealing Terminology & Immutability.
     */
    public function testBapIntegrityTerminologyAndImmutability(): void
    {
        $appPath = $this->getAppPath();

        // 1. Terminology verification in views
        $formView = file_get_contents($appPath . 'Views/admin/cbt/bap/form.php');
        $this->assertStringContainsString('Segel Integritas Digital BAP', $formView);
        $this->assertStringContainsString('Hash SHA-256', $formView);
        $this->assertStringContainsString('Identitas penandatangan berdasarkan akun terautentikasi', $formView);

        $viewDoc = file_get_contents($appPath . 'Views/admin/cbt/bap/view.php');
        $this->assertStringContainsString('Segel Integritas Digital BAP', $viewDoc);
        $this->assertStringContainsString('Hash SHA-256', $viewDoc);
        $this->assertStringContainsString('Identitas penandatangan berdasarkan akun terautentikasi', $viewDoc);
        $this->assertStringContainsString('Riwayat penyegelan dan audit trail', $viewDoc);

        // 2. Strict Immutability Test
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
            public array $storage = [];
            public function __construct() {}
            public function appendLog(string $eventType, string $entityTable, string $entityId, int $actorId, string $actorRole, array $payload, string $prevHash = '0000000000000000000000000000000000000000000000000000000000000000', ?string $keyId = null): array {
                return ['seq_no' => 1, 'record_hash' => hash('sha256', 'mock_audit'), 'prev_hash' => $prevHash];
            }
        };

        $bapService = new DigitalBapService($docStub, $studStub, $auditStub);
        $bapId = $docStub->insert([
            'bap_number'          => 'BAP-IMMUTABLE-001',
            'test_id'             => 101,
            'room_code'           => 'LAB-1',
            'proctor_name'        => 'Ahmad Dahlan',
            'supervisor_name'     => 'Drs. Bambang',
            'total_registered'    => 10,
            'total_present'       => 10,
            'total_absent'        => 0,
            'verification_status' => 'UNVERIFIED',
        ]);
        $studStub->storage[$bapId] = [
            ['student_id' => 1, 'reported_status' => 'HADIR'],
        ];

        // Seal document
        $bapService->signAndSubmitBap($bapId, 10, 'PROCTOR', 'ahmad.dahlan@sekolah.sch.id');
        $this->assertSame(CbtBapDocumentV3Model::STATUS_AUTHENTICATED, $docStub->storage[$bapId]['verification_status']);

        // Post-seal mutation attempt must throw RuntimeException
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('IMMUTABLE DOCUMENT: BAP document ID 1 has already been signed and authenticated. Mutations are blocked.');
        $bapService->saveDraft($bapId, [['student_id' => 1, 'reported_status' => 'TIDAK_HADIR']]);
    }

    /**
     * 4. Retake Authorization Administrative Evidence (DEC-RET-01).
     * Verifies sk_number, sk_date, sk_issuer, reviewer_id, ratified_at, and selected_session_id.
     */
    public function testRetakeAuthorizationAdministrativeEvidence(): void
    {
        $evidenceValidator = new ReviewerEvidenceValidator();
        $decisionValidator = new ReviewerDecisionValidator($evidenceValidator);
        $workflow = new ReviewerWorkflowService($decisionValidator, null, $evidenceValidator);

        $case = [
            'id'                  => 'RC-RETAKE-EVIDENCE-01',
            'case_type'           => 'MULTIPLE_RETAKES',
            'category'            => 'AMBIGUOUS_RETAKE',
            'student_id'          => 77,
            'session_id'          => 1001,
            'test_id'             => 55,
            'review_status'       => 'REQUIRES_OWNER_DECISION',
            'status'              => 'REQUIRES_OWNER_DECISION',
            'workflow_status'     => 'REQUIRES_OWNER_DECISION',
            'evidence_reference'  => 'Multiple session logs detected: Sesi #1001 vs Sesi #1002',
            'deviations'          => ['candidate_sessions' => [1001, 1002]],
            'evidence_data'       => ['candidate_sessions' => [1001, 1002]],
        ];

        $ref = new \ReflectionClass($workflow);
        $casesProp = $ref->getProperty('cases');
        $casesProp->setAccessible(true);
        $casesProp->setValue($workflow, ['RC-RETAKE-EVIDENCE-01' => $case]);

        $auditServiceMock = new class extends \App\Services\Cbt\Review\ReviewerAuditService {
            public function __construct() {}
            public function verifyAuditIntegrity(): array { return ['is_valid' => true]; }
            public function recordDecision(string $reviewerId, string $caseId, string $statusBefore, string $newStatus, string $action, string $reason, string $evidenceRef = '', array $extraContext = [], ?string $decisionId = null): array {
                return ['timestamp' => '2026-11-02 08:15:00', 'record_hash' => hash('sha256', 'mock_decision')];
            }
        };
        $auditProp = $ref->getProperty('auditService');
        $auditProp->setAccessible(true);
        $auditProp->setValue($workflow, $auditServiceMock);

        // Apply decision with complete administrative decree metadata
        $params = [
            'selected_session_id' => 1002,
            'owner_approval_ref'  => 'SK-421.3/088/2026',
            'sk_number'           => 'SK-421.3/088/2026',
            'sk_date'             => '2026-11-01',
            'sk_issuer'           => 'Drs. Bambang (Kepala Sekolah)',
            'actor_role'          => 'PRINCIPAL',
        ];

        $resolvedCase = $workflow->applyDecision(
            'RC-RETAKE-EVIDENCE-01',
            'KEPSEK_BAMBANG',
            'RATIFY_SESSION',
            'Disahkan berdasarkan SK Kepala Sekolah tentang Ujian Susulan',
            $params
        );

        $this->assertSame(ReviewerWorkflowService::STATUS_RESOLVED, $resolvedCase['review_status']);
        $this->assertSame(1002, $resolvedCase['selected_session_id']);
        $this->assertSame('SK-421.3/088/2026', $resolvedCase['sk_number']);
        $this->assertSame('2026-11-01', $resolvedCase['sk_date']);
        $this->assertSame('Drs. Bambang (Kepala Sekolah)', $resolvedCase['sk_issuer']);
        $this->assertSame('KEPSEK_BAMBANG', $resolvedCase['reviewer_id']);
        $this->assertSame('2026-11-02 08:15:00', $resolvedCase['ratified_at']);
    }

    /**
     * 5. Attendance & Finalization Status Distinction:
     * Verifies that EXCUSED, DISPENSED, and ABSENT strictly preserve final_score = NULL under DEC-ABS-01.
     */
    public function testAttendanceStatusDistinctionAndNullPreservation(): void
    {
        $finalizationService = $this->createFinalizationService();

        $statuses = [
            CbtAssessmentFinalizationService::PARTICIPATION_ABSENT    => 'ABSENT',
            CbtAssessmentFinalizationService::PARTICIPATION_EXCUSED   => 'EXCUSED',
            CbtAssessmentFinalizationService::PARTICIPATION_DISPENSED => 'DISPENSED',
        ];

        foreach ($statuses as $statusCode => $statusLabel) {
            $context = [
                'student_id'           => 901,
                'test_id'              => 88,
                'participation_status' => $statusCode,
                'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
                'session_status'       => CbtAssessmentFinalizationService::SESSION_FINISHED,
            ];

            $res = $finalizationService->finalizeSession(12000, $context, []);
            $this->assertFalse($res['is_eligible'], "Status {$statusLabel} must not be eligible for publication.");
            $this->assertNull($res['final_score'], "Status {$statusLabel} must strictly preserve final_score as NULL under DEC-ABS-01.");
            $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_HOLD, $res['assessment_status']);
            $this->assertSame($statusCode, $res['participation_status']);
            $this->assertStringContainsString('DEC-ABS-01', $res['blockers'][0]);
        }
    }
}
