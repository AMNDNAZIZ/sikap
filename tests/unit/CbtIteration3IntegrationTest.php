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
use App\Models\CbtAuditTrailV3Model;
use App\Models\CbtEssayGradeV3Model;
use App\Models\CbtSessionScoreV3Model;
use App\Models\CbtGradebookPublicationV3Model;
use App\Models\SummativeScoresModel;
use App\Models\FinalExamScoresModel;
use App\Models\PtsScoreModel;
use App\Services\Cbt\Assessment\CbtAssessmentFinalizationService;
use App\Services\Cbt\Gradebook\CbtGradebookIntegrationService;
use App\Controllers\Admin\CbtAssessmentReport;
use PHPUnit\Framework\TestCase;

/**
 * CbtIteration3IntegrationTest
 *
 * ESPATA CBT V3.33 — Iteration 3 Integration & Security Test Suite
 * Covers:
 * 1. Prioritas A: Assessment Finalization Service & Domain Statuses
 *    - Strict separation of 7 domain statuses.
 *    - Deterministic scoring using BCMath without floating-point drift.
 * 2. Prioritas B: Integrasi Koreksi Esai (DEC-ESS-01 & OD-02)
 *    - Essay Completion Gate: final_score = NULL and is_eligible = false while essays pending.
 *    - Full grading recalculation & deterministic final score.
 *    - OD-02: Essay score separation and TP analytics exclusion.
 * 3. Prioritas C: Validasi Status & Kelayakan (DEC-ABS-01, DEC-RET-01, OD-ALL-TYPE-VOID)
 *    - DEC-ABS-01: Absent student score remains NULL; earned zero prohibited.
 *    - DEC-RET-01: Multi-session candidate blocked without ratified owner_approval_ref.
 *    - OD-ALL-TYPE-VOID: Proportional redistribution when a type is void; NO_VALID_QUESTIONS when all void.
 * 4. Prioritas D: Gradebook Integration & Provenance Protection
 *    - Ineligible results strictly blocked from gradebook publication.
 *    - Idempotent publication & duplicate prevention on retry.
 *    - Protection of manual teacher grades from unintended overwrite.
 *    - Essay revision propagation: updates only the CBT-linked gradebook row.
 * 5. Prioritas E: Teacher Visibility & Controller Authorization
 *    - Strict RBAC: Guest and Student blocked; Teacher and Admin authorized.
 */
class CbtIteration3IntegrationTest extends TestCase
{
    private CbtAssessmentFinalizationService $finalizationService;
    private CbtGradebookIntegrationService $gradebookService;

    private $answerStub;
    private $essayGradeStub;
    private $sessionScoreStub;
    private $auditTrailStub;
    private $publicationStub;
    private $summativeStub;
    private $finalExamStub;
    private $ptsStub;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Answer Model Stub
        $this->answerStub = new class extends CbtAnswerV3Model {
            public array $storage = [];
            public function __construct() {}
            public function where($key, $value = null) {
                $inst = clone $this;
                $inst->storage = array_filter($this->storage, fn($a) => $a[$key] == $value);
                return $inst;
            }
            public function findAll(?int $limit = null, int $offset = 0): array {
                return array_values($this->storage);
            }
        };

        // 2. Essay Grade Stub
        $this->essayGradeStub = new class extends CbtEssayGradeV3Model {
            public array $storage = [];
            public function __construct() {}
        };

        // 3. Session Score Stub
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

        // 4. Audit Trail Stub
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
        };

        // 5. Gradebook Publication Lineage Stub
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

            public function first() {
                return !empty($this->storage) ? reset($this->storage) : null;
            }

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

        // 6. Gradebook Target Table Stubs
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
                } else {
                    $inst->storage = array_filter($this->storage, fn($r) => $r[$key] == $value);
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

        // Services
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
    // SECTION 1: ASSESSMENT FINALIZATION SERVICE & OD-ALL-TYPE-VOID
    // =========================================================================

    public function testWeightRedistributionWhenAQuestionTypeIsVoid(): void
    {
        // Exam snapshot has 3 types: PG 40%, ESAI 40%, BS 20%
        $baseWeights = ['pg' => 40.0, 'esai' => 40.0, 'bs' => 20.0];

        // Scenario 1: All items are active
        $activeCounts = ['pg' => 20, 'esai' => 5, 'bs' => 10];
        $normal = $this->finalizationService->calculateNormalizedWeights($baseWeights, $activeCounts);
        $this->assertSame(CbtAssessmentFinalizationService::TEST_VALID_TEST, $normal['test_validity']);
        $this->assertFalse($normal['redistributed']);
        $this->assertSame('40.0000', $normal['normalized_weights']['pg']);
        $this->assertSame('40.0000', $normal['normalized_weights']['esai']);
        $this->assertSame('20.0000', $normal['normalized_weights']['bs']);

        // Scenario 2: OD-ALL-TYPE-VOID - All BS questions are voided (active count = 0)
        $voidBsCounts = ['pg' => 20, 'esai' => 5, 'bs' => 0];
        $redistributed = $this->finalizationService->calculateNormalizedWeights($baseWeights, $voidBsCounts);
        $this->assertSame(CbtAssessmentFinalizationService::TEST_PARTIAL_TYPE_VOID, $redistributed['test_validity']);
        $this->assertTrue($redistributed['redistributed']);
        $this->assertArrayNotHasKey('bs', $redistributed['normalized_weights']);

        // Proportional redistribution: 40/80 * 100 = 50.0% each
        $this->assertSame('50.0000', $redistributed['normalized_weights']['pg']);
        $this->assertSame('50.0000', $redistributed['normalized_weights']['esai']);
        $sum = bcadd($redistributed['normalized_weights']['pg'], $redistributed['normalized_weights']['esai'], 4);
        $this->assertSame('100.0000', $sum);

        // Scenario 3: All questions void -> NO_VALID_QUESTIONS
        $allVoidCounts = ['pg' => 0, 'esai' => 0, 'bs' => 0];
        $allVoid = $this->finalizationService->calculateNormalizedWeights($baseWeights, $allVoidCounts);
        $this->assertSame(CbtAssessmentFinalizationService::TEST_NO_VALID_QUESTIONS, $allVoid['test_validity']);
        $this->assertEmpty($allVoid['normalized_weights']);
    }

    // =========================================================================
    // SECTION 2: DEC-ESS-01 & OD-02 ESSAY COMPLETION GATE
    // =========================================================================

    public function testDecEss01HoldsFinalScoreWhileEssaysArePending(): void
    {
        $testSnapshot = [
            'base_weights' => ['pg' => 60.0, 'esai' => 40.0],
            'items'        => [
                ['item_version_id' => 101, 'question_type' => 'pg', 'item_weight' => 10.0],
                ['item_version_id' => 102, 'question_type' => 'pg', 'item_weight' => 10.0],
                ['item_version_id' => 201, 'question_type' => 'esai', 'item_weight' => 20.0],
            ],
        ];

        // Student answered both PG questions (score 10 and 10), but Essay 201 is unallocated (null)
        $answers = [
            ['item_version_id' => 101, 'allocated_score' => 10.0],
            ['item_version_id' => 102, 'allocated_score' => 10.0],
            ['item_version_id' => 201, 'allocated_score' => null], // Pending grading
        ];

        $sessionContext = [
            'student_id'           => 1001,
            'test_id'              => 501,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'test_snapshot'        => $testSnapshot,
        ];

        $result = $this->finalizationService->finalizeSession(7001, $sessionContext, $answers, $testSnapshot);

        // DEC-ESS-01 Gate Assertions
        $this->assertFalse($result['is_eligible'], 'Assessment must not be eligible while essay is pending.');
        $this->assertNull($result['final_score'], 'final_score must be NULL under DEC-ESS-01.');
        $this->assertNull($result['report_score'], 'report_score must be NULL under DEC-ESS-01.');
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_HOLD, $result['assessment_status']);
        $this->assertSame(CbtAssessmentFinalizationService::ESSAY_PENDING_GRADING, $result['essay_status']);
        $this->assertSame(1, $result['pending_essay_count']);
        $this->assertNotEmpty($result['blockers']);
        $this->assertStringContainsString('DEC-ESS-01', $result['blockers'][0]);

        // Provisional objective scores are preserved
        $this->assertSame(20.0, (float)$result['score_components']['pg']);
    }

    public function testFinalizationSucceedsWhenAllEssaysAreGraded(): void
    {
        $testSnapshot = [
            'base_weights' => ['pg' => 60.0, 'esai' => 40.0],
            'items'        => [
                ['item_version_id' => 101, 'question_type' => 'pg', 'item_weight' => 10.0],
                ['item_version_id' => 102, 'question_type' => 'pg', 'item_weight' => 10.0],
                ['item_version_id' => 201, 'question_type' => 'esai', 'item_weight' => 20.0],
            ],
        ];

        // All questions graded: PG 20/20 (100%), Esai 15/20 (75%)
        $answers = [
            ['item_version_id' => 101, 'allocated_score' => 10.0],
            ['item_version_id' => 102, 'allocated_score' => 10.0],
            ['item_version_id' => 201, 'allocated_score' => 15.0],
        ];

        $sessionContext = [
            'student_id'           => 1001,
            'test_id'              => 501,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'test_snapshot'        => $testSnapshot,
        ];

        $result = $this->finalizationService->finalizeSession(7001, $sessionContext, $answers, $testSnapshot);

        $this->assertTrue($result['is_eligible'], 'Assessment must be eligible when all essays are graded.');
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_FINALIZED, $result['assessment_status']);
        $this->assertSame(CbtAssessmentFinalizationService::ESSAY_FULLY_GRADED, $result['essay_status']);
        $this->assertSame(0, $result['pending_essay_count']);

        // Expected calculation: PG (20/20 * 60) = 60, Esai (15/20 * 40) = 30 => Final Score = 90.0
        $this->assertSame(90.0, (float)$result['final_score']);
        $this->assertSame(90.0, (float)$result['report_score']);
        $this->assertEmpty($result['blockers']);

        // Verify tamper-evident audit record was logged
        $this->assertNotEmpty($this->auditTrailStub->storage);
        $lastAudit = end($this->auditTrailStub->storage);
        $this->assertSame('ASSESSMENT_FINALIZED', $lastAudit['event_type']);
        $this->assertSame(7001, (int)$lastAudit['actor_id']);
        $this->assertSame('cbt_session_scores_v3', $lastAudit['entity_table']);
    }

    // =========================================================================
    // SECTION 3: DEC-ABS-01 & DEC-RET-01 GOVERNANCE ENFORCEMENTS
    // =========================================================================

    public function testDecAbs01StudentAbsencePreservesNullScore(): void
    {
        $sessionContext = [
            'student_id'           => 1002,
            'test_id'              => 501,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_ABSENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
        ];

        $result = $this->finalizationService->finalizeSession(7002, $sessionContext);

        $this->assertFalse($result['is_eligible']);
        $this->assertNull($result['final_score'], 'DEC-ABS-01 prohibits assigning earned zero score to absent students.');
        $this->assertNull($result['report_score']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_HOLD, $result['assessment_status']);
        $this->assertStringContainsString('DEC-ABS-01', $result['blockers'][0]);
    }

    public function testDecRet01UnratifiedRetakeIsQuarantined(): void
    {
        $sessionContext = [
            'student_id'           => 1003,
            'test_id'              => 501,
            'participation_status' => CbtAssessmentFinalizationService::PARTICIPATION_PRESENT,
            'integrity_status'     => CbtAssessmentFinalizationService::INTEGRITY_VALID,
            'is_retake_candidate'  => true,
            'owner_approval_ref'   => null, // Missing ratification
        ];

        $result = $this->finalizationService->finalizeSession(7003, $sessionContext);

        $this->assertFalse($result['is_eligible']);
        $this->assertSame(CbtAssessmentFinalizationService::ASSESSMENT_QUARANTINED, $result['assessment_status']);
        $this->assertStringContainsString('DEC-RET-01', $result['blockers'][0]);
    }

    // =========================================================================
    // SECTION 4: GRADEBOOK INTEGRATION SERVICE & PROVENANCE
    // =========================================================================

    public function testGradebookRejectsIneligibleAssessment(): void
    {
        $ineligibleResult = [
            'is_eligible' => false,
            'final_score' => null,
            'blockers'    => ['DEC-ESS-01: Essay grading incomplete'],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PUBLICATION_BLOCKED');

        $this->gradebookService->publishToGradebook(
            $ineligibleResult,
            ['target_table' => 'summative_scores', 'subject_id' => 1, 'year_id' => 1],
            99
        );
    }

    public function testGradebookSuccessfulPublicationAndIdempotentRetry(): void
    {
        $eligibleResult = [
            'is_eligible' => true,
            'session_id'  => 8001,
            'test_id'     => 501,
            'student_id'  => 1001,
            'final_score' => 88.5,
        ];

        $targetContext = [
            'target_table' => CbtGradebookIntegrationService::TARGET_SUMMATIVE,
            'subject_id'   => 10,
            'year_id'      => 2,
            'semester'     => '1',
            'type'         => 'tulis',
        ];

        // 1. First publication: inserts into summative_scores and cbt_gradebook_publications_v3
        $outcome1 = $this->gradebookService->publishToGradebook($eligibleResult, $targetContext, 55);

        $this->assertTrue($outcome1['success']);
        $this->assertSame('PUBLISHED', $outcome1['status']);
        $this->assertSame('INSERTED_NEW', $outcome1['action_taken']);
        $this->assertSame(88.5, $outcome1['published_score']);
        $this->assertCount(1, $this->publicationStub->storage);
        $this->assertCount(1, $this->summativeStub->storage);

        // 2. Retry publication with identical score: Idempotent no-op
        $outcome2 = $this->gradebookService->publishToGradebook($eligibleResult, $targetContext, 55);

        $this->assertTrue($outcome2['success']);
        $this->assertSame('ALREADY_PUBLISHED', $outcome2['status']);
        $this->assertSame('NO_OP_IDEMPOTENT', $outcome2['action_taken']);
        $this->assertCount(1, $this->publicationStub->storage, 'Duplicate rows in publication table must be prevented.');
        $this->assertCount(1, $this->summativeStub->storage, 'Duplicate rows in gradebook table must be prevented.');
    }

    public function testGradebookProtectsPreExistingManualGrades(): void
    {
        // Pre-existing manual grade in summative_scores (created by teacher without CBT)
        $this->summativeStub->storage[999] = [
            'id'         => 999,
            'student_id' => 1005,
            'subject_id' => 10,
            'year_id'    => 2,
            'semester'   => '1',
            'type'       => 'tulis',
            'score'      => 70.0,
        ];

        $cbtResult = [
            'is_eligible' => true,
            'session_id'  => 8005,
            'test_id'     => 501,
            'student_id'  => 1005,
            'final_score' => 95.0,
        ];

        $targetContext = [
            'target_table' => CbtGradebookIntegrationService::TARGET_SUMMATIVE,
            'subject_id'   => 10,
            'year_id'      => 2,
            'semester'     => '1',
            'type'         => 'tulis',
        ];

        // Without explicit override consent, publication is rejected to prevent overwriting manual grades
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GRADEBOOK_CONFLICT');

        $this->gradebookService->publishToGradebook($cbtResult, $targetContext, 55, false);
    }

    public function testEssayRevisionSynchronizesUpdatedScoreToGradebookRow(): void
    {
        $initialResult = [
            'is_eligible' => true,
            'session_id'  => 8008,
            'test_id'     => 501,
            'student_id'  => 1008,
            'final_score' => 80.0,
        ];

        $targetContext = [
            'target_table' => CbtGradebookIntegrationService::TARGET_SUMMATIVE,
            'subject_id'   => 12,
            'year_id'      => 2,
            'semester'     => '1',
            'type'         => 'tulis',
        ];

        // 1. Initial publication with score 80.0
        $this->gradebookService->publishToGradebook($initialResult, $targetContext, 55);

        // 2. Teacher revises essay score; new final score is 87.5
        $revisedResult = [
            'is_eligible' => true,
            'session_id'  => 8008,
            'test_id'     => 501,
            'student_id'  => 1008,
            'final_score' => 87.5,
        ];

        $revisionOutcome = $this->gradebookService->publishToGradebook($revisedResult, $targetContext, 55);

        $this->assertTrue($revisionOutcome['success']);
        $this->assertSame('REVISED', $revisionOutcome['status']);
        $this->assertSame('UPDATED_EXISTING', $revisionOutcome['action_taken']);
        $this->assertSame(80.0, $revisionOutcome['previous_score']);
        $this->assertSame(87.5, $revisionOutcome['published_score']);

        // Verify score was updated in target gradebook table without adding rows
        $this->assertCount(1, $this->summativeStub->storage);
        $savedGrade = reset($this->summativeStub->storage);
        $this->assertSame(87.5, (float)$savedGrade['score']);

        // Verify audit log recorded the revision
        $lastAudit = end($this->auditTrailStub->storage);
        $this->assertSame('GRADEBOOK_SCORE_REVISED', $lastAudit['event_type']);
    }

    // =========================================================================
    // SECTION 5: REPORT CONTROLLER RBAC & SECURITY
    // =========================================================================

    public function testCbtAssessmentReportControllerRbac(): void
    {
        $controller = new class($this->finalizationService, $this->gradebookService, $this->sessionScoreStub, $this->publicationStub) extends CbtAssessmentReport {
            public function testAuth() {
                return $this->enforceAuthorization();
            }
        };

        // 1. Unauthenticated guest -> blocked
        $controller->setMockSession(['logged_in' => false, 'user' => []]);
        $this->assertNotNull($controller->testAuth());

        // 2. Unauthorized student role (role_id 4) -> blocked
        $controller->setMockSession(['logged_in' => true, 'user' => ['id' => 99, 'role_id' => 4, 'username' => 'student']]);
        $this->assertNotNull($controller->testAuth());

        // 3. Authorized teacher (role_id 3) -> authorized
        $controller->setMockSession(['logged_in' => true, 'user' => ['id' => 101, 'role_id' => 3, 'username' => 'teacher_grader']]);
        $this->assertNull($controller->testAuth());

        // 4. Authorized admin (role_id 1) -> authorized
        $controller->setMockSession(['logged_in' => true, 'user' => ['id' => 1, 'role_id' => 1, 'username' => 'admin']]);
        $this->assertNull($controller->testAuth());
    }
}
