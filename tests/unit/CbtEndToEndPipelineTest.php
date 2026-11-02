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
use App\Models\CbtSessionItemModel;
use App\Models\CbtSessionScoreV3Model;
use App\Services\Cbt\Pipeline\CbtExecutionPipelineService;
use PHPUnit\Framework\TestCase;

/**
 * CbtEndToEndPipelineTest
 *
 * Pragmatic end-to-end integration test for CBT V3 execution pipeline.
 */
class CbtEndToEndPipelineTest extends TestCase
{
    private CbtExecutionPipelineService $pipeline;
    private $answerStub;
    private $scoreStub;
    private $essayStub;
    private $auditStub;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. In-memory Answer Model Stub
        $this->answerStub = new class extends CbtAnswerV3Model {
            public array $storage = [];
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
                $id = count($this->storage) + 1;
                $record = [
                    'id'                => $id,
                    'session_id'        => $sessionId,
                    'item_version_id'   => $itemVersionId,
                    'student_id'        => $studentId,
                    'answer_payload'    => $answerPayload,
                    'allocated_score'   => $allocatedScore,
                    'is_doubtful'       => $isDoubtful,
                    'answer_version'    => 1,
                    'provenance_status' => 'OPERATIONAL_V3',
                    'canonical_hash'    => $canonicalHash,
                ];
                $this->storage[$id] = $record;
                return $record;
            }

            public function where($key, $value = null) {
                $storage = &$this->storage;
                return new class($storage, $key, $value) {
                    private array $storage;
                    private string $key;
                    private $value;

                    public function __construct(array &$storage, string $key, $value) {
                        $this->storage = &$storage;
                        $this->key = $key;
                        $this->value = $value;
                    }

                    public function findAll(): array {
                        $res = [];
                        foreach ($this->storage as $r) {
                            if ($r[$this->key] == $this->value) {
                                $res[] = $r;
                            }
                        }
                        return $res;
                    }
                };
            }

            public function update($id = null, $data = null): bool {
                if (isset($this->storage[$id])) {
                    $this->storage[$id] = array_merge($this->storage[$id], $data);
                    return true;
                }
                return false;
            }
        };

        // 2. In-memory Session Score Stub
        $this->scoreStub = new class extends CbtSessionScoreV3Model {
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
                float $roundingDelta = 0.0
            ): array {
                $isEligible = ($pendingEssayCount === 0);
                $finalScore = null;
                if ($isEligible) {
                    $finalScore = $scorePg + ($scoreEsai ?? 0.0) + $scorePgKompleks + $scoreBs;
                }

                $record = [
                    'id'                      => 1,
                    'session_id'              => $sessionId,
                    'score_pg'                => $scorePg,
                    'score_esai'              => $scoreEsai,
                    'score_pg_kompleks'       => $scorePgKompleks,
                    'score_bs'                => $scoreBs,
                    'final_score'             => $finalScore,
                    'report_score'            => $finalScore,
                    'pending_essay_count'     => $pendingEssayCount,
                    'publication_eligibility' => $isEligible ? 1 : 0,
                ];
                $this->storage[$sessionId] = $record;
                return $record;
            }
        };

        // 3. In-memory Essay Grade Stub
        $this->essayStub = new class extends CbtEssayGradeV3Model {
            public array $storage = [];
            public function __construct() {}

            public function recordGradeRevision(
                int $answerId,
                int $sessionId,
                int $graderId,
                float $scoreAwarded,
                ?string $feedback = null
            ): array {
                $maxVer = 0;
                foreach ($this->storage as &$r) {
                    if ($r['answer_id'] === $answerId) {
                        if ($r['version_number'] > $maxVer) {
                            $maxVer = $r['version_number'];
                        }
                        $r['is_current_confirmed'] = 0;
                    }
                }
                unset($r);

                $newVer = $maxVer + 1;
                $id = count($this->storage) + 1;
                $record = [
                    'id'                  => $id,
                    'answer_id'           => $answerId,
                    'session_id'          => $sessionId,
                    'grader_id'           => $graderId,
                    'version_number'      => $newVer,
                    'score_awarded'       => $scoreAwarded,
                    'feedback'            => $feedback,
                    'is_current_confirmed'=> 1,
                    'graded_at'           => date('Y-m-d H:i:s'),
                ];
                $this->storage[$id] = $record;
                return $record;
            }
        };

        // 4. In-memory Audit Trail Stub
        $this->auditStub = new class extends CbtAuditTrailV3Model {
            public array $storage = [];
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
                $seq = count($this->storage) + 1;
                $prevHash = $seq === 1 ? self::GENESIS_HASH : end($this->storage)['record_hash'];
                ksort($payload);
                $canon = json_encode($payload, JSON_UNESCAPED_SLASHES);
                $hashMaterial = "{$seq}|{$eventType}|{$entityTable}|{$entityId}|{$actorId}|{$actorRole}|{$canon}|{$prevHash}";
                $recordHash = hash_hmac('sha256', $hashMaterial, $secretKey);

                $record = [
                    'id'                => $seq,
                    'seq_no'            => $seq,
                    'event_type'        => $eventType,
                    'entity_table'      => $entityTable,
                    'entity_id'         => $entityId,
                    'actor_id'          => $actorId,
                    'actor_role'        => $actorRole,
                    'payload_canonical' => $canon,
                    'key_id'            => $keyId,
                    'prev_hash'         => $prevHash,
                    'record_hash'       => $recordHash,
                    'created_at'        => date('Y-m-d H:i:s'),
                ];
                $this->storage[$seq] = $record;
                return $record;
            }

            public function verifyChain(string $secretKey = 'ESPATA_DEFAULT_TEST_KEY_2026'): array {
                $expectedPrev = self::GENESIS_HASH;
                $expectedSeq = 1;
                foreach ($this->storage as $row) {
                    if ($row['seq_no'] !== $expectedSeq) {
                        return ['valid' => false, 'error' => "Sequence gap at seq {$row['seq_no']}"];
                    }
                    if ($row['prev_hash'] !== $expectedPrev) {
                        return ['valid' => false, 'error' => "Prev hash mismatch at seq {$row['seq_no']}"];
                    }
                    $canon = $row['payload_canonical'];
                    $material = "{$row['seq_no']}|{$row['event_type']}|{$row['entity_table']}|{$row['entity_id']}|{$row['actor_id']}|{$row['actor_role']}|{$canon}|{$row['prev_hash']}";
                    $calc = hash_hmac('sha256', $material, $secretKey);
                    if ($calc !== $row['record_hash']) {
                        return ['valid' => false, 'error' => "HMAC mismatch at seq {$row['seq_no']}"];
                    }
                    $expectedPrev = $row['record_hash'];
                    $expectedSeq++;
                }
                return ['valid' => true, 'total_records' => count($this->storage)];
            }
        };

        $itemStub = new class extends CbtSessionItemModel {
            public function __construct() {}
        };

        $this->pipeline = new CbtExecutionPipelineService(
            $this->answerStub,
            $itemStub,
            $this->essayStub,
            $this->scoreStub,
            $this->auditStub
        );
    }

    public function testCompleteCbtEndToEndExecutionPipeline(): void
    {
        $sessionId = 101;
        $studentId = 501;

        $itemTypesMap = [
            1 => 'pg',
            2 => 'bs',
            3 => 'esai',
        ];

        // 1. Student submits answers
        // Item 1 (PG): Correct ('B'), Weight 40.0
        $ans1 = $this->pipeline->submitAnswer($sessionId, 1, $studentId, 'pg', 'B', 'B', 40.0);
        $this->assertSame(40.0, $ans1['allocated_score']);

        // Item 2 (BS): Wrong ('FALSE' vs 'TRUE'), Weight 20.0
        $ans2 = $this->pipeline->submitAnswer($sessionId, 2, $studentId, 'bs', 'FALSE', 'TRUE', 20.0);
        $this->assertSame(0.0, $ans2['allocated_score']);

        // Item 3 (Esai): Student writes text, Weight 40.0 -> Score must be NULL initially
        $ans3 = $this->pipeline->submitAnswer($sessionId, 3, $studentId, 'esai', 'Fotosintesis menghasilkan oksigen.', null, 40.0);
        $this->assertNull($ans3['allocated_score']);

        // 2. Student finishes exam session (Enforce DEC-ESS-01)
        $initialScore = $this->pipeline->finishSession($sessionId, $itemTypesMap);

        // DEC-ESS-01 Strict Assertions:
        $this->assertSame(1, $initialScore['pending_essay_count'], 'Must count 1 pending essay');
        $this->assertNull($initialScore['final_score'], 'final_score MUST be NULL when essays are pending (DEC-ESS-01)');
        $this->assertNull($initialScore['report_score'], 'report_score MUST be NULL when essays are pending (DEC-ESS-01)');
        $this->assertSame(0, $initialScore['publication_eligibility'], 'publication_eligibility MUST be 0 when essays are pending');
        $this->assertSame(40.0, $initialScore['score_pg']);
        $this->assertSame(0.0, $initialScore['score_bs']);

        // 3. Teacher grades essay (Revision v1)
        $graderId = 901;
        $gradeResult1 = $this->pipeline->gradeEssay(
            $ans3['id'],
            $sessionId,
            $graderId,
            35.0,
            'Penjelasan cukup lengkap.',
            $itemTypesMap
        );

        $revision1 = $gradeResult1['revision'];
        $this->assertSame(1, $revision1['version_number'], 'Initial grade is version 1');
        $this->assertSame(1, $revision1['is_current_confirmed']);
        $this->assertSame(35.0, $revision1['score_awarded']);

        // Score unlocked upon 0 pending essays:
        $updatedScore1 = $gradeResult1['session_score'];
        $this->assertSame(0, $updatedScore1['pending_essay_count']);
        $this->assertSame(75.0, $updatedScore1['final_score'], 'Final score must unlock: 40 + 0 + 35 = 75.0');
        $this->assertSame(1, $updatedScore1['publication_eligibility'], 'Publication eligibility must unlock to 1');

        // 4. Teacher revises essay grading (Append-Only Revision v2)
        $gradeResult2 = $this->pipeline->gradeEssay(
            $ans3['id'],
            $sessionId,
            $graderId,
            38.5,
            'Nilai disesuaikan setelah verifikasi kata kunci.',
            $itemTypesMap
        );

        $revision2 = $gradeResult2['revision'];
        $this->assertSame(2, $revision2['version_number'], 'Revision is version 2');
        $this->assertSame(1, $revision2['is_current_confirmed']);
        $this->assertSame(38.5, $revision2['score_awarded']);

        // Version 1 must now be superseded:
        $this->assertSame(0, $this->essayStub->storage[1]['is_current_confirmed']);

        // Final score updated: 40 + 0 + 38.5 = 78.5
        $updatedScore2 = $gradeResult2['session_score'];
        $this->assertSame(78.5, $updatedScore2['final_score']);
        $this->assertSame(1, $updatedScore2['publication_eligibility']);

        // 5. Audit Trail Verification
        $this->assertCount(8, $this->auditStub->storage, 'Must record 8 audit events (3 answers, 3 session scoring updates, 2 essay grades)');
        $chainCheck = $this->auditStub->verifyChain();
        $this->assertTrue($chainCheck['valid'], 'HMAC hash chain must be cryptographically valid');
        $this->assertSame(8, $chainCheck['total_records']);

        // Sequence numbers must be strictly sequential (1 to 8)
        $seqNos = array_column($this->auditStub->storage, 'seq_no');
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], $seqNos);

        // 6. Adversarial Tamper Detection Test
        $this->auditStub->storage[2]['payload_canonical'] = '{"hacked": true}';
        $tamperedCheck = $this->auditStub->verifyChain();
        $this->assertFalse($tamperedCheck['valid'], 'Chain verification must detect payload tampering');
    }
}
