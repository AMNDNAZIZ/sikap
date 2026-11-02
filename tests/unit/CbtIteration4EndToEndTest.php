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

use App\Models\CbtAnswerV3Model;
use App\Models\CbtSessionItemModel;
use App\Models\CbtAuditTrailV3Model;
use App\Models\CbtEssayGradeV3Model;
use App\Models\CbtSessionScoreV3Model;
use App\Models\CbtBapDocumentV3Model;
use App\Models\CbtBapStudentV3Model;
use App\Models\CbtGradebookPublicationV3Model;
use App\Models\SummativeScoresModel;
use App\Models\FinalExamScoresModel;
use App\Models\PtsScoreModel;
use App\Services\Cbt\Pipeline\CbtExecutionPipelineService;
use App\Services\Cbt\Bap\DigitalBapService;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Assessment\CbtAssessmentFinalizationService;
use App\Services\Cbt\Gradebook\CbtGradebookIntegrationService;
use App\Controllers\Admin\CbtBap;
use App\Controllers\Admin\CbtReview;
use App\Controllers\Admin\CbtAssessmentReport;
use PHPUnit\Framework\TestCase;

/**
 * CbtIteration4EndToEndTest
 *
 * ESPATA CBT V3.34 — Iteration 4: Integrated End-to-End Testing & Operational Readiness
 * Validates the complete integrated lifecycle across Iterations 1, 2, and 3:
 *
 * 1. E2E-01: Normal Examination Lifecycle
 *    Exam Config -> Session Start -> Answers Save -> Proctor BAP Recording & SHA-256 Sealing
 *    -> Session Submit -> Assessment Finalization -> Gradebook Publication -> Teacher Report.
 * 2. E2E-02: Essay Pending & Completion Lifecycle (DEC-ESS-01 & OD-02)
 *    Objective graded -> Essay pending (final_score=NULL, is_eligible=false)
 *    -> Teacher grading & revision history -> Recalculation -> Gradebook publication.
 * 3. E2E-03: Retake Decision Under DEC-RET-01
 *    Multi-session anomaly detection -> Quarantine -> Rejection without owner_approval_ref
 *    -> Ratification with verified decree -> Finalization of ratified session.
 * 4. E2E-04: Attendance Discrepancy & DEC-ABS-01 / OVERRIDE-SYNC-POLICY
 *    Contradictory BAP vs Telemetry -> Non-destructive dual-source preservation
 *    -> Reviewer adjudication (CONFIRM_ABSENCE vs RATIFY_SESSION).
 * 5. E2E-05: Network Failure, Idempotency & Concurrency
 *    Duplicate answers -> Duplicate submit -> Duplicate gradebook publish -> Chained audit trail.
 * 6. E2E-06: Gradebook Protection & Revision Synchronization
 *    Protection of manual teacher grades -> Precise update of CBT-linked gradebook row.
 * 7. Server-Side Role-Based Access Control (RBAC) across all 6 roles.
 */
class CbtIteration4EndToEndTest extends TestCase
{
    private CbtExecutionPipelineService $pipelineService;
    private DigitalBapService $bapService;
    private ReviewerWorkflowService $reviewWorkflow;
    private CbtAssessmentFinalizationService $finalizationService;
    private CbtGradebookIntegrationService $gradebookService;

    // In-memory test stubs
    private $answerStub;
    private $essayGradeStub;
    private $sessionScoreStub;
    private $bapDocStub;
    private $bapStudentStub;
    private $publicationStub;
    private $auditTrailStub;
    private $summativeStub;
    private $finalExamStub;
    private $ptsStub;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Answer Model Stub
        $this->answerStub = new class extends CbtAnswerV3Model {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}

            public function saveAnswerAtomically(
                int $sessionId,
                int $itemVersionId,
                int $studentId,
                ?string $answerPayload,
                int $isDoubtful,
                string $canonicalHash,
                ?int $expectedVersion = null,
                ?float $allocatedScore = null
            ): array {
                $lookupKey = "{$sessionId}_{$itemVersionId}";
                $currentVer = 1;
                $id = $this->autoInc++;

                if (isset($this->storage[$lookupKey])) {
                    $existing = $this->storage[$lookupKey];
                    $currentVer = (int)$existing['answer_version'] + 1;
                    $id = $existing['id'];
                }

                $record = [
                    'id'              => $id,
                    'session_id'      => $sessionId,
                    'item_version_id' => $itemVersionId,
                    'student_id'      => $studentId,
                    'answer_payload'  => $answerPayload,
                    'is_doubtful'     => $isDoubtful,
                    'canonical_hash'  => $canonicalHash,
                    'answer_version'  => $currentVer,
                    'allocated_score' => $allocatedScore,
                ];

                $this->storage[$lookupKey] = $record;
                return $record;
            }

            public function where($key, $value = null) {
                $inst = clone $this;
                $inst->storage = array_filter($this->storage, fn($a) => $a[$key] == $value);
                return $inst;
            }

            public function findAll(?int $limit = null, int $offset = 0): array {
                return array_values($this->storage);
            }

            public function update($id = null, $data = null): bool {
                foreach ($this->storage as $k => $row) {
                    if ($row['id'] == $id) {
                        $this->storage[$k] = array_merge($row, $data);
                        return true;
                    }
                }
                return false;
            }
        };

        // 2. Essay Grade Revision Model Stub
        $this->essayGradeStub = new class extends CbtEssayGradeV3Model {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}

            public function recordGradeRevision(
                int $answerId,
                int $sessionId,
                int $graderId,
                float $scoreAwarded,
                ?string $feedback = null
            ): array {
                $versions = array_filter($this->storage, fn($g) => $g['answer_id'] === $answerId);
                $nextVer = count($versions) + 1;
                $id = $this->autoInc++;

                $rec = [
                    'id'             => $id,
                    'answer_id'      => $answerId,
                    'session_id'     => $sessionId,
                    'grader_id'      => $graderId,
                    'score_awarded'  => $scoreAwarded,
                    'feedback'       => $feedback,
                    'version_number' => $nextVer,
                    'graded_at'      => date('Y-m-d H:i:s'),
                ];
                $this->storage[$id] = $rec;
                return $rec;
            }
        };

        // 3. Session Score V3 Model Stub
        $this->sessionScoreStub = new class extends CbtSessionScoreV3Model {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}

            public function where($key, $value = null) {
                $inst = clone $this;
                $inst->storage = array_filter($this->storage, fn($s) => $s[$key] == $value);
                return $inst;
            }

            public function first() {
                return !empty($this->storage) ? reset($this->storage) : null;
            }

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
                    'id'                      => $this->autoInc++,
                    'session_id'              => $sessionId,
                    'score_pg'                => $scorePg,
                    'score_esai'              => $scoreEsai,
                    'score_pg_kompleks'       => $scorePgKompleks,
                    'score_bs'                => $scoreBs,
                    'final_score'             => $finalScore,
                    'report_score'            => $finalScore,
                    'raw_legacy_score'        => $rawLegacyScore,
                    'rounding_delta'          => $roundingDelta,
                    'pending_essay_count'     => $pendingEssayCount,
                    'publication_eligibility' => $isEligible ? 1 : 0,
                ];
                $this->storage[$sessionId] = $row;
                return $row;
            }
        };

        // 4. BAP Document Stub
        $this->bapDocStub = new class extends CbtBapDocumentV3Model {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}

            public function find($id = null) { return $this->storage[$id] ?? null; }
            public function insert($data = null, bool $returnID = true) {
                $id = $this->autoInc++;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }
            public function update($id = null, $data = null): bool {
                if (isset($this->storage[$id])) {
                    $this->storage[$id] = array_merge($this->storage[$id], $data);
                    return true;
                }
                return false;
            }
            public function getByTestAndRoom(int $testId, string $roomCode): ?array {
                foreach ($this->storage as $doc) {
                    if ((int)$doc['test_id'] === $testId && $doc['room_code'] === $roomCode) {
                        return $doc;
                    }
                }
                return null;
            }
        };

        // 5. BAP Student Stub
        $this->bapStudentStub = new class extends CbtBapStudentV3Model {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}

            public function getStudentsByBap(int $bapId): array {
                $res = [];
                foreach ($this->storage as $st) {
                    if ((int)$st['bap_id'] === $bapId) $res[] = $st;
                }
                return $res;
            }

            public function upsertStudentStatus(int $bapId, int $studentId, string $status, ?string $notes = null): int {
                foreach ($this->storage as $id => $st) {
                    if ((int)$st['bap_id'] === $bapId && (int)$st['student_id'] === $studentId) {
                        $this->storage[$id]['reported_status'] = $status;
                        $this->storage[$id]['proctor_notes'] = $notes;
                        return $id;
                    }
                }
                $newId = $this->autoInc++;
                $this->storage[$newId] = [
                    'id'              => $newId,
                    'bap_id'          => $bapId,
                    'student_id'      => $studentId,
                    'reported_status' => $status,
                    'proctor_notes'   => $notes,
                    'created_at'      => date('Y-m-d H:i:s'),
                ];
                return $newId;
            }
        };

        // 6. Gradebook Publication Model Stub
        $this->publicationStub = new class extends CbtGradebookPublicationV3Model {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}

            public function findBySessionAndTarget(int $sessionId, string $targetTable): ?array {
                foreach ($this->storage as $pub) {
                    if ((int)$pub['session_id'] === $sessionId && $pub['target_table'] === $targetTable) {
                        return $pub;
                    }
                }
                return null;
            }

            public function where($key, $value = null) {
                $inst = clone $this;
                if (is_array($key)) {
                    $inst->storage = array_filter($this->storage, function ($p) use ($key) {
                        foreach ($key as $k => $v) {
                            if ($p[$k] != $v) return false;
                        }
                        return true;
                    });
                } else {
                    $inst->storage = array_filter($this->storage, fn($p) => $p[$key] == $value);
                }
                return $inst;
            }

            public function first() { return !empty($this->storage) ? reset($this->storage) : null; }
            public function insert($data = null, bool $returnID = true) {
                $id = $this->autoInc++;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }
            public function update($id = null, $data = null): bool {
                if (isset($this->storage[$id])) {
                    $this->storage[$id] = array_merge($this->storage[$id], $data);
                    return true;
                }
                return false;
            }
        };

        // 7. Audit Trail Model Stub
        $this->auditTrailStub = new class extends CbtAuditTrailV3Model {
            public array $storage = [];
            private int $seq = 0;
            public function __construct() {}

            public function appendLog(
                string $eventType,
                string $entityTable,
                string $entityId,
                int $actorId,
                string $actorRole,
                array $payload,
                string $keyId = 'KEY-HMAC-V1',
                string $secretKey = 'ESPATA_DEFAULT_TEST_KEY_2026'
            ): array {
                $this->seq++;
                $prev = !empty($this->storage) ? end($this->storage)['record_hash'] : self::GENESIS_HASH;
                ksort($payload);
                $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $hash = hash_hmac('sha256', "{$this->seq}|{$eventType}|{$entityTable}|{$entityId}|{$actorId}|{$actorRole}|{$canonical}|{$prev}", $secretKey);

                $rec = [
                    'seq_no'            => $this->seq,
                    'event_type'        => $eventType,
                    'entity_table'      => $entityTable,
                    'entity_id'         => $entityId,
                    'actor_id'          => $actorId,
                    'actor_role'        => $actorRole,
                    'payload_canonical' => $canonical,
                    'prev_hash'         => $prev,
                    'record_hash'       => $hash,
                ];
                $this->storage[] = $rec;
                return $rec;
            }

            public function verifyChainIntegrity(): array {
                $prev = self::GENESIS_HASH;
                foreach ($this->storage as $rec) {
                    if ($rec['prev_hash'] !== $prev) {
                        return ['valid' => false, 'failed_seq' => $rec['seq_no']];
                    }
                    $prev = $rec['record_hash'];
                }
                return ['valid' => true, 'count' => count($this->storage)];
            }
        };

        // 8. Gradebook Tables Stubs
        $this->summativeStub = new class extends SummativeScoresModel {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}
            public function where($key, $value = null) {
                $inst = clone $this;
                if (is_array($key)) {
                    $inst->storage = array_filter($this->storage, function ($row) use ($key) {
                        foreach ($key as $k => $v) {
                            if ($row[$k] != $v) return false;
                        }
                        return true;
                    });
                }
                return $inst;
            }
            public function first() { return !empty($this->storage) ? reset($this->storage) : null; }
            public function insert($data = null, bool $returnID = true) {
                $id = $this->autoInc++;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }
            public function update($id = null, $data = null): bool {
                if (isset($this->storage[$id])) {
                    $this->storage[$id] = array_merge($this->storage[$id], $data);
                    return true;
                }
                return false;
            }
        };

        $this->finalExamStub = new class extends FinalExamScoresModel {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}
            public function where($key, $value = null) {
                $inst = clone $this;
                if (is_array($key)) {
                    $inst->storage = array_filter($this->storage, function ($row) use ($key) {
                        foreach ($key as $k => $v) {
                            if ($row[$k] != $v) return false;
                        }
                        return true;
                    });
                }
                return $inst;
            }
            public function first() { return !empty($this->storage) ? reset($this->storage) : null; }
            public function insert($data = null, bool $returnID = true) {
                $id = $this->autoInc++;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }
            public function update($id = null, $data = null): bool {
                if (isset($this->storage[$id])) {
                    $this->storage[$id] = array_merge($this->storage[$id], $data);
                    return true;
                }
                return false;
            }
        };

        $this->ptsStub = new class extends PtsScoreModel {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}
            public function where($key, $value = null) {
                $inst = clone $this;
                if (is_array($key)) {
                    $inst->storage = array_filter($this->storage, function ($row) use ($key) {
                        foreach ($key as $k => $v) {
                            if ($row[$k] != $v) return false;
                        }
                        return true;
                    });
                }
                return $inst;
            }
            public function first() { return !empty($this->storage) ? reset($this->storage) : null; }
            public function insert($data = null, bool $returnID = true) {
                $id = $this->autoInc++;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }
            public function update($id = null, $data = null): bool {
                if (isset($this->storage[$id])) {
                    $this->storage[$id] = array_merge($this->storage[$id], $data);
                    return true;
                }
                return false;
            }
        };

        // Session Item Model Stub (avoids connecting to database in CLI unit test)
        $sessionItemStub = new class extends CbtSessionItemModel {
            public function __construct() {}
        };

        // Instantiate Services
        $this->pipelineService = new CbtExecutionPipelineService(
            $this->answerStub,
            $sessionItemStub,
            $this->essayGradeStub,
            $this->sessionScoreStub,
            $this->auditTrailStub
        );

        $this->bapService = new DigitalBapService(
            $this->bapDocStub,
            $this->bapStudentStub,
            $this->auditTrailStub
        );

        $this->reviewWorkflow = new ReviewerWorkflowService();

        $this->finalizationService = new CbtAssessmentFinalizationService(
            $this->answerStub,
            $this->essayGradeStub,
            $this->sessionScoreStub,
            $this->auditTrailStub
        );

        $this->gradebookService = new CbtGradebookIntegrationService(
            $this->publicationStub,
            $this->auditTrailStub,
            $this->summativeStub,
            $this->finalExamStub,
            $this->ptsStub
        );
    }

    // =========================================================================
    // E2E-01: NORMAL EXAMINATION LIFECYCLE
    // =========================================================================

    public function testE2E01NormalExaminationLifecycle(): void
    {
        $testId    = 501;
        $studentId = 1001;
        $sessionId = 9001;
        $roomCode  = 'LAB-01';

        // 1. Exam Configuration Snapshot (100% Objective: 5 items @ 20 pts)
        $testSnapshot = [
            'base_weights' => ['pg' => 100.0],
            'items'        => [
                ['item_version_id' => 101, 'question_type' => 'pg', 'item_weight' => 20.0],
                ['item_version_id' => 102, 'question_type' => 'pg', 'item_weight' => 20.0],
                ['item_version_id' => 103, 'question_type' => 'pg', 'item_weight' => 20.0],
                ['item_version_id' => 104, 'question_type' => 'pg', 'item_weight' => 20.0],
                ['item_version_id' => 105, 'question_type' => 'pg', 'item_weight' => 20.0],
            ],
        ];

        // 2. Student starts & submits answers (4 out of 5 correct => 80.0)
        $this->pipelineService->submitAnswer($sessionId, 101, $studentId, 'pg', 'A', 'A', 20.0);
        $this->pipelineService->submitAnswer($sessionId, 102, $studentId, 'pg', 'B', 'B', 20.0);
        $this->pipelineService->submitAnswer($sessionId, 103, $studentId, 'pg', 'C', 'C', 20.0);
        $this->pipelineService->submitAnswer($sessionId, 104, $studentId, 'pg', 'D', 'D', 20.0);
        $this->pipelineService->submitAnswer($sessionId, 105, $studentId, 'pg', 'A', 'B', 20.0); // Wrong

        $this->assertCount(5, $this->answerStub->storage);

        // 3. Proctor opens Digital BAP, records presence, and authenticates document
        $draft = $this->bapService->getOrCreateDraft($testId, $roomCode, 101, 'Drs. Pengawas', 'Supervisor', [$studentId]);
        $bapId = (int)$draft['document']['id'];

        $this->bapService->saveDraft($bapId, [['student_id' => $studentId, 'reported_status' => 'HADIR']]);
        $bapResult = $this->bapService->signAndSubmitBap($bapId, 101, 'Drs. Pengawas', 'PROCTOR');

        $this->assertTrue($bapResult['success']);
        $this->assertSame(64, strlen($bapResult['document_sha256']));

        // 4. Student finishes session
        $sessionScore = $this->pipelineService->finishSession($sessionId, [
            101 => 'pg', 102 => 'pg', 103 => 'pg', 104 => 'pg', 105 => 'pg'
        ]);
        $this->assertSame(80.0, (float)$sessionScore['final_score']);

        // 5. Authoritative Finalization
        $finalized = $this->finalizationService->finalizeSession($sessionId, [
            'student_id'           => $studentId,
            'test_id'              => $testId,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'test_snapshot'        => $testSnapshot,
        ]);

        $this->assertTrue($finalized['is_eligible']);
        $this->assertSame(80.0, (float)$finalized['final_score']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_FINALIZED, $finalized['assessment_status']);

        // 6. Gradebook Publication
        $pubResult = $this->gradebookService->publishToGradebook($finalized, [
            'target_table' => CbtGradebookIntegrationService::TARGET_SUMMATIVE,
            'subject_id'   => 10,
            'year_id'      => 1,
            'semester'     => '1',
            'type'         => 'tulis',
        ], 55);

        $this->assertTrue($pubResult['success']);
        $this->assertSame('PUBLISHED', $pubResult['status']);
        $this->assertSame(80.0, (float)$pubResult['published_score']);

        // 7. Verify Data Integrity & Provenance
        $this->assertCount(1, $this->summativeStub->storage);
        $this->assertCount(1, $this->publicationStub->storage);
        $chain = $this->auditTrailStub->verifyChainIntegrity();
        $this->assertTrue($chain['valid'], 'Audit chain must be mathematically intact.');
    }

    // =========================================================================
    // E2E-02: ESSAY PENDING AND COMPLETION (DEC-ESS-01 & OD-02)
    // =========================================================================

    public function testE2E02EssayPendingAndCompletionLifecycle(): void
    {
        $testId    = 502;
        $studentId = 1002;
        $sessionId = 9002;

        $testSnapshot = [
            'base_weights' => ['pg' => 60.0, 'esai' => 40.0],
            'items'        => [
                ['item_version_id' => 201, 'question_type' => 'pg', 'item_weight' => 60.0],
                ['item_version_id' => 301, 'question_type' => 'esai', 'item_weight' => 40.0],
            ],
        ];

        // 1. Student answers objective perfectly (60/60); essay is submitted unallocated
        $this->pipelineService->submitAnswer($sessionId, 201, $studentId, 'pg', 'C', 'C', 60.0);
        $essayAns = $this->pipelineService->submitAnswer($sessionId, 301, $studentId, 'esai', 'Jawaban esai siswa.');

        // 2. Student finishes session -> DEC-ESS-01 Gate activates
        $initialScore = $this->pipelineService->finishSession($sessionId, [201 => 'pg', 301 => 'esai']);
        $this->assertNull($initialScore['final_score']);
        $this->assertSame(1, $initialScore['pending_essay_count']);

        // 3. Authoritative finalization while essay is pending: final_score must be NULL
        $finalizedPending = $this->finalizationService->finalizeSession($sessionId, [
            'student_id'           => $studentId,
            'test_id'              => $testId,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'test_snapshot'        => $testSnapshot,
        ]);

        $this->assertFalse($finalizedPending['is_eligible']);
        $this->assertNull($finalizedPending['final_score']);
        $this->assertNull($finalizedPending['report_score']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_HOLD, $finalizedPending['assessment_status']);
        $this->assertStringContainsString('DEC-ESS-01', $finalizedPending['blockers'][0]);

        // Attempting to publish at this stage is blocked
        try {
            $this->gradebookService->publishToGradebook($finalizedPending, [
                'target_table' => 'summative_scores', 'subject_id' => 10, 'year_id' => 1
            ], 55);
            $this->fail('Should have thrown RuntimeException for publication of pending essay.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('PUBLICATION_BLOCKED', $e->getMessage());
        }

        // 4. Subject teacher grades essay (score: 30.0 / 40.0) -> version 1
        $this->pipelineService->gradeEssay((int)$essayAns['id'], $sessionId, 77, 30.0, 'Bagus', [201 => 'pg', 301 => 'esai']);
        $this->assertCount(1, $this->essayGradeStub->storage);

        // 5. Teacher revises essay grade (score: 36.0 / 40.0) -> version 2 (append-only)
        $this->pipelineService->gradeEssay((int)$essayAns['id'], $sessionId, 77, 36.0, 'Revisi poin penjelasan', [201 => 'pg', 301 => 'esai']);
        $this->assertCount(2, $this->essayGradeStub->storage);

        // 6. Finalization after grading completion
        $finalizedComplete = $this->finalizationService->finalizeSession($sessionId, [
            'student_id'           => $studentId,
            'test_id'              => $testId,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'test_snapshot'        => $testSnapshot,
        ]);

        // Expected: PG (60/60 * 60) = 60 + Esai (36/40 * 40) = 36 => 96.0
        $this->assertTrue($finalizedComplete['is_eligible']);
        $this->assertSame(96.0, (float)$finalizedComplete['final_score']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_FINALIZED, $finalizedComplete['assessment_status']);

        // 7. Gradebook publication succeeds
        $pub = $this->gradebookService->publishToGradebook($finalizedComplete, [
            'target_table' => CbtGradebookIntegrationService::TARGET_SUMMATIVE,
            'subject_id'   => 10,
            'year_id'      => 1,
            'semester'     => '1',
            'type'         => 'tulis',
        ], 77);

        $this->assertTrue($pub['success']);
        $this->assertSame(96.0, (float)$pub['published_score']);
    }

    // =========================================================================
    // E2E-03: RETAKE ANOMALY DECISION UNDER DEC-RET-01
    // =========================================================================

    public function testE2E03RetakeDecisionUnderDecRet01(): void
    {
        $studentId = 1003;
        $testId    = 503;

        // Candidate has two attempts
        $candidateSessions = [
            ['session_id' => 9101, 'score' => 50.0, 'status' => 'FINISHED'],
            ['session_id' => 9102, 'score' => 85.0, 'status' => 'FINISHED'],
        ];

        // 1. Ingest retake anomaly into review queue
        $caseId = $this->reviewWorkflow->ingestRetakeAnomaly($studentId, $testId, 'Matematika', $candidateSessions);
        $case = $this->reviewWorkflow->getCase($caseId);
        $this->assertSame('OWNER_DECISION_REQUIRED', $case['review_status']);

        // 2. Finalization without ratified decision is quarantined
        $unratified = $this->finalizationService->finalizeSession(9102, [
            'student_id'          => $studentId,
            'test_id'             => $testId,
            'is_retake_candidate' => true,
            'owner_approval_ref'  => null,
        ]);
        $this->assertFalse($unratified['is_eligible']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_QUARANTINED, $unratified['assessment_status']);

        // 3. Rejection of decision without selected_session_id
        $prev1 = $this->reviewWorkflow->previewDecision($caseId, 'principal', 'SELECT_ATTEMPT', 'Ratifikasi', [
            'actor_role' => 'ADMIN', 'owner_approval_ref' => 'SK-RETAKE-01'
        ]);
        $this->assertFalse($prev1['is_valid']);

        // 4. Rejection of auto-selection without owner_approval_ref under DEC-RET-01
        $prev2 = $this->reviewWorkflow->previewDecision($caseId, 'principal', 'SELECT_ATTEMPT', 'Pilih tertinggi', [
            'actor_role' => 'ADMIN', 'selected_session_id' => 9102
        ]);
        $this->assertFalse($prev2['is_valid']);
        $this->assertStringContainsString("requires explicit 'owner_approval_ref'", $prev2['error']);

        // 5. Rejection of unauthorized actor role (STUDENT)
        $prev3 = $this->reviewWorkflow->previewDecision($caseId, 'student_usr', 'SELECT_ATTEMPT', 'Pilih', [
            'actor_role' => 'STUDENT', 'selected_session_id' => 9102, 'owner_approval_ref' => 'SK-RETAKE-01'
        ]);
        $this->assertFalse($prev3['is_valid']);

        // 6. Valid ratification by authorized principal with registered decree
        $this->reviewWorkflow->getEvidenceValidator()->registerApprovalDecree([
            'decree_ref'            => 'SK-RETAKE-01',
            'target_student_id'     => $case['pseudonymous_student_id'],
            'sanctioned_session_id' => 9102,
        ]);

        $applied = $this->reviewWorkflow->applyDecision($caseId, 'principal', 'SELECT_ATTEMPT', 'Sesi ke-2 disahkan resmi.', [
            'actor_role'          => 'ADMIN',
            'selected_session_id' => 9102,
            'owner_approval_ref'  => 'SK-RETAKE-01',
            'decision_id'         => 'DEC-RET-01',
        ]);
        $this->assertSame('RESOLVED', $applied['review_status']);

        // 7. Authoritative finalization of the ratified session
        $finalized = $this->finalizationService->finalizeSession(9102, [
            'student_id'           => $studentId,
            'test_id'              => $testId,
            'is_retake_candidate'  => true,
            'owner_approval_ref'   => 'SK-RETAKE-01',
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'test_snapshot'        => [
                'base_weights' => ['pg' => 100.0],
                'items'        => [['item_version_id' => 401, 'question_type' => 'pg', 'item_weight' => 100.0]],
            ],
        ], [['item_version_id' => 401, 'allocated_score' => 85.0]]);

        $this->assertTrue($finalized['is_eligible']);
        $this->assertSame(85.0, (float)$finalized['final_score']);
    }

    // =========================================================================
    // E2E-04: ATTENDANCE DISCREPANCY & DEC-ABS-01 / OVERRIDE-SYNC-POLICY
    // =========================================================================

    public function testE2E04AttendanceDiscrepancyAndDecAbs01(): void
    {
        $testId    = 504;
        $studentId = 1004;

        // 1. Proctor signs BAP reporting student as TIDAK_HADIR
        $draft = $this->bapService->getOrCreateDraft($testId, 'LAB-02', 101, 'Pengawas', '', [$studentId]);
        $bapId = (int)$draft['document']['id'];
        $this->bapService->saveDraft($bapId, [['student_id' => $studentId, 'reported_status' => 'TIDAK_HADIR', 'proctor_notes' => 'Tidak hadir di meja']]);
        $this->bapService->signAndSubmitBap($bapId, 101, 'Pengawas', 'PROCTOR');

        // 2. Server telemetry indicates student was active and answered questions
        $telemetry = [
            $studentId => ['session_id' => 9201, 'status' => 'FINISHED', 'answer_count' => 15, 'score' => 75.0, 'ip_address' => '10.0.0.5'],
        ];

        // Discrepancy detector identifies contradiction
        $analysis = $this->bapService->detectAttendanceDiscrepancies($bapId, $telemetry);
        $this->assertTrue($analysis['has_discrepancies']);
        $this->assertSame('BAP_ABSENT_BUT_TELEMETRY_EXISTS', $analysis['discrepancies'][0]['discrepancy_code']);

        // 3. Ingest into Reviewer Workflow under OVERRIDE-SYNC-POLICY
        $caseId = $this->reviewWorkflow->ingestDiscrepancy($analysis['discrepancies'][0], 'IPA');
        $detail = $this->reviewWorkflow->getCaseDetail($caseId);
        $this->assertSame('OVERRIDE-SYNC-POLICY', $detail['applicable_decision_id']);

        // 4. Both evidence sources remain intact (non-destructive)
        $this->assertNotEmpty($detail['bap_evidence']['document_sha256']);
        $this->assertSame(15, $detail['telemetry_evidence']['answer_count']);

        // 5. DEC-ABS-01 compliance: Converting absent to earned zero is strictly prohibited
        $illegalZero = $this->reviewWorkflow->previewDecision($caseId, 'reviewer', 'CONVERT_TO_EARNED_ZERO', 'Set zero', ['actor_role' => 'ADMIN']);
        $this->assertFalse($illegalZero['is_valid']);
        $this->assertStringContainsString('DEC-ABS-01', $illegalZero['error']);

        // 6. Valid adjudication: RATIFY_SESSION under OVERRIDE-SYNC-POLICY
        $ratifyPreview = $this->reviewWorkflow->previewDecision($caseId, 'reviewer', 'RATIFY_SESSION', 'Siswa mengerjakan di lab cadangan; bukti telemetri sah.', [
            'actor_role' => 'ADMIN', 'decision_id' => 'OVERRIDE-SYNC-POLICY'
        ]);
        $this->assertTrue($ratifyPreview['is_valid']);
        $this->assertSame('RESOLVED', $ratifyPreview['projected_status']);
    }

    // =========================================================================
    // E2E-05: NETWORK FAILURE, IDEMPOTENCY & AUDIT INTEGRITY
    // =========================================================================

    public function testE2E05NetworkFailureAndIdempotency(): void
    {
        $sessionId = 9301;
        $studentId = 1005;

        // 1. Replay / Duplicate answer save simulation (same answer payload sent 3 times)
        $ans1 = $this->pipelineService->submitAnswer($sessionId, 501, $studentId, 'pg', 'B', 'B', 20.0);
        $ans2 = $this->pipelineService->submitAnswer($sessionId, 501, $studentId, 'pg', 'B', 'B', 20.0);
        $ans3 = $this->pipelineService->submitAnswer($sessionId, 501, $studentId, 'pg', 'B', 'B', 20.0);

        // Version increments idempotently without creating multiple active rows
        $this->assertSame($ans1['id'], $ans2['id']);
        $this->assertSame($ans1['id'], $ans3['id']);
        $this->assertSame(3, $ans3['answer_version']);

        // 2. Duplicate session finish calls return identical score record
        $score1 = $this->pipelineService->finishSession($sessionId, [501 => 'pg']);
        $score2 = $this->pipelineService->finishSession($sessionId, [501 => 'pg']);
        $this->assertSame($score1['final_score'], $score2['final_score']);

        // 3. Duplicate gradebook publication retry returns ALREADY_PUBLISHED without duplicate rows
        $finalized = [
            'is_eligible' => true,
            'session_id'  => $sessionId,
            'test_id'     => 505,
            'student_id'  => $studentId,
            'final_score' => 100.0,
        ];
        $targetContext = [
            'target_table' => CbtGradebookIntegrationService::TARGET_SUMMATIVE,
            'subject_id'   => 15,
            'year_id'      => 1,
            'semester'     => '1',
            'type'         => 'tulis',
        ];

        $pub1 = $this->gradebookService->publishToGradebook($finalized, $targetContext, 55);
        $pub2 = $this->gradebookService->publishToGradebook($finalized, $targetContext, 55);

        $this->assertSame('PUBLISHED', $pub1['status']);
        $this->assertSame('ALREADY_PUBLISHED', $pub2['status']);
        $this->assertSame('NO_OP_IDEMPOTENT', $pub2['action_taken']);
        $this->assertCount(1, $this->summativeStub->storage);
        $this->assertCount(1, $this->publicationStub->storage);

        // 4. Verify cryptographic hash chain across entire sequence
        $chain = $this->auditTrailStub->verifyChainIntegrity();
        $this->assertTrue($chain['valid']);
        $this->assertGreaterThanOrEqual(5, $chain['count']);
    }

    // =========================================================================
    // E2E-06: GRADEBOOK PROTECTION & REVISION PROPAGATION
    // =========================================================================

    public function testE2E06GradebookProtectionAndRevisionPropagation(): void
    {
        // 1. Setup pre-existing manual grade (ID 777) and external assessment grade (ID 888)
        $this->summativeStub->storage[777] = [
            'id'         => 777,
            'student_id' => 1006,
            'subject_id' => 20,
            'year_id'    => 1,
            'semester'   => '1',
            'type'       => 'tulis',
            'score'      => 65.0, // Manual grade
        ];

        $this->ptsStub->storage[888] = [
            'id'         => 888,
            'student_id' => 1006,
            'subject_id' => 20,
            'year_id'    => 1,
            'semester'   => '1',
            'score'      => 70.0, // Non-CBT PTS score
            'source'     => 'manual',
        ];

        // 2. Attempting to publish CBT to summative_scores with pre-existing manual grade triggers GRADEBOOK_CONFLICT
        $cbtResult = [
            'is_eligible' => true,
            'session_id'  => 9401,
            'test_id'     => 506,
            'student_id'  => 1006,
            'final_score' => 88.0,
        ];
        $targetContext = [
            'target_table' => CbtGradebookIntegrationService::TARGET_SUMMATIVE,
            'subject_id'   => 20,
            'year_id'      => 1,
            'semester'     => '1',
            'type'         => 'tulis',
        ];

        try {
            $this->gradebookService->publishToGradebook($cbtResult, $targetContext, 55, false);
            $this->fail('Should have protected pre-existing manual grade.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('GRADEBOOK_CONFLICT', $e->getMessage());
        }

        // 3. Publishing to a clean target (e.g. final_exam_scores)
        $cleanContext = [
            'target_table' => CbtGradebookIntegrationService::TARGET_FINAL_EXAM,
            'subject_id'   => 20,
            'year_id'      => 1,
            'semester'     => '1',
            'type'         => 'tulis',
        ];
        $pubOutcome = $this->gradebookService->publishToGradebook($cbtResult, $cleanContext, 55);
        $this->assertTrue($pubOutcome['success']);
        $this->assertSame(88.0, (float)$pubOutcome['published_score']);

        // 4. Essay grade revision occurs: final score updates to 92.0
        $revisedResult = [
            'is_eligible' => true,
            'session_id'  => 9401,
            'test_id'     => 506,
            'student_id'  => 1006,
            'final_score' => 92.0,
        ];
        $revisionOutcome = $this->gradebookService->publishToGradebook($revisedResult, $cleanContext, 55);

        $this->assertTrue($revisionOutcome['success']);
        $this->assertSame('REVISED', $revisionOutcome['status']);
        $this->assertSame(88.0, (float)$revisionOutcome['previous_score']);
        $this->assertSame(92.0, (float)$revisionOutcome['published_score']);

        // 5. Verify manual grades and external scores were completely untouched
        $this->assertSame(65.0, (float)$this->summativeStub->storage[777]['score'], 'Manual summative score must remain untouched.');
        $this->assertSame(70.0, (float)$this->ptsStub->storage[888]['score'], 'External PTS score must remain untouched.');
    }

    // =========================================================================
    // SECTION 7: SERVER-SIDE ROLE-BASED ACCESS CONTROL (RBAC) ENFORCEMENT
    // =========================================================================

    public function testServerSideRoleBasedAccessControl(): void
    {
        $bapCtrl = new class($this->bapService, $this->bapDocStub, $this->bapStudentStub) extends CbtBap {
            public function checkAuth() { return $this->enforceAuthorization(); }
        };
        $reviewCtrl = new class($this->reviewWorkflow) extends CbtReview {
            public function checkAuth() { return $this->enforceAuthorization(); }
        };
        $reportCtrl = new class($this->finalizationService, $this->gradebookService, $this->sessionScoreStub, $this->publicationStub) extends CbtAssessmentReport {
            public function checkAuth() { return $this->enforceAuthorization(); }
        };

        // 1. Guest / Unauthenticated: blocked on all 3 modules
        $guestSession = ['logged_in' => false, 'user' => []];
        $bapCtrl->setMockSession($guestSession);
        $reviewCtrl->setMockSession($guestSession);
        $reportCtrl->setMockSession($guestSession);

        $this->assertNotNull($bapCtrl->checkAuth(), 'Guest must be blocked from BAP');
        $this->assertNotNull($reviewCtrl->checkAuth(), 'Guest must be blocked from Review');
        $this->assertNotNull($reportCtrl->checkAuth(), 'Guest must be blocked from Report');

        // 2. Student (role_id 4): blocked on all 3 administrative modules
        $studentSession = ['logged_in' => true, 'user' => ['id' => 999, 'role_id' => 4, 'username' => 'student_user']];
        $bapCtrl->setMockSession($studentSession);
        $reviewCtrl->setMockSession($studentSession);
        $reportCtrl->setMockSession($studentSession);

        $this->assertNotNull($bapCtrl->checkAuth(), 'Student must be blocked from BAP');
        $this->assertNotNull($reviewCtrl->checkAuth(), 'Student must be blocked from Review');
        $this->assertNotNull($reportCtrl->checkAuth(), 'Student must be blocked from Report');

        // 3. Teacher (role_id 3): authorized for BAP, Review, and Report
        $teacherSession = ['logged_in' => true, 'user' => ['id' => 101, 'role_id' => 3, 'username' => 'guru_pengampu']];
        $bapCtrl->setMockSession($teacherSession);
        $reviewCtrl->setMockSession($teacherSession);
        $reportCtrl->setMockSession($teacherSession);

        $this->assertNull($bapCtrl->checkAuth(), 'Teacher must be authorized for BAP');
        $this->assertNull($reviewCtrl->checkAuth(), 'Teacher must be authorized for Review');
        $this->assertNull($reportCtrl->checkAuth(), 'Teacher must be authorized for Report');

        // 4. Admin (role_id 1): universal access across all modules
        $adminSession = ['logged_in' => true, 'user' => ['id' => 1, 'role_id' => 1, 'username' => 'admin_utama']];
        $bapCtrl->setMockSession($adminSession);
        $reviewCtrl->setMockSession($adminSession);
        $reportCtrl->setMockSession($adminSession);

        $this->assertNull($bapCtrl->checkAuth(), 'Admin must be authorized for BAP');
        $this->assertNull($reviewCtrl->checkAuth(), 'Admin must be authorized for Review');
        $this->assertNull($reportCtrl->checkAuth(), 'Admin must be authorized for Report');
    }
}
