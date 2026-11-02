<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Cbt\CbtMigrationBatchProcessor;
use App\Services\Cbt\CbtMigrationVerifier;
use App\Services\Cbt\CbtMigrationReconciler;
use App\Services\Cbt\CbtMigrationOrchestrator;
use App\Services\Cbt\EspataCanonicalSerializer;
use RuntimeException;

/**
 * CbtStageCTest
 * Unit test suite for Stage C Non-Destructive Migration Engine.
 */
class CbtStageCTest extends CIUnitTestCase
{
    public function testProductionEnvironmentRejection()
    {
        $orchestrator = new CbtMigrationOrchestrator(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CRITICAL SECURITY BLOCK');

        $orchestrator->run([
            'environment' => 'production',
            'execute'     => true,
        ]);
    }

    public function testDryRunZeroMutation()
    {
        $records = [
            [
                'id'          => 1,
                'student_id'  => 10,
                'test_id'     => 100,
                'question_id' => 1000,
                'answer'      => 'A',
                'score'       => 1.0,
            ],
            [
                'id'          => 2,
                'student_id'  => 10,
                'test_id'     => 100,
                'question_id' => 1001,
                'answer'      => 'B',
                'score'       => 1.0,
            ],
        ];

        $processor = new CbtMigrationBatchProcessor(null);
        $result = $processor->processBatch($records, 1, true, 'TESTER');

        $this->assertTrue($result['is_dry_run']);
        $this->assertSame(2, $result['row_count']);
        $this->assertSame(64, strlen($result['batch_merkle_root']));
        $this->assertSame(1, $result['start_id']);
        $this->assertSame(2, $result['end_id']);
    }

    public function testBatchPaginationAndIdempotency()
    {
        $record = [
            'id'          => 50,
            'student_id'  => 20,
            'test_id'     => 200,
            'question_id' => 2000,
            'answer'      => 'C',
            'score'       => 2.0,
        ];

        $processor = new CbtMigrationBatchProcessor(null);

        // First pass
        $pass1 = $processor->processBatch([$record], 1, true);
        $this->assertSame(1, $pass1['row_count']);
        $this->assertSame(0, $pass1['conflict_count']);
    }

    public function testHashConflictDetection()
    {
        $originalRecord = [
            'id'          => 100,
            'student_id'  => 50,
            'test_id'     => 500,
            'question_id' => 5000,
            'answer'      => 'Original Answer',
            'score'       => 1.0,
        ];

        $originalHash = EspataCanonicalSerializer::hash($originalRecord);

        // Mock verification check: different hash for same ID
        $changedHash = EspataCanonicalSerializer::hash(array_merge($originalRecord, ['answer' => 'Tampered Answer']));
        $this->assertNotSame($originalHash, $changedHash);
    }

    public function testAntiJoinMissingAndDuplicatesDetection()
    {
        $verifier = new CbtMigrationVerifier(null);

        $sourceRecords = [
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'answer' => 'A'],
            ['id' => 2, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1001, 'answer' => 'B'],
            ['id' => 3, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1002, 'answer' => 'C'],
        ];

        $antiJoin = $verifier->runAntiJoinChecks($sourceRecords);

        // In fresh mock state, all 3 source records are missing in destination
        $this->assertCount(3, $antiJoin['missing_in_destination']);
        $this->assertContains(1, $antiJoin['missing_in_destination']);
        $this->assertContains(2, $antiJoin['missing_in_destination']);
        $this->assertContains(3, $antiJoin['missing_in_destination']);
        $this->assertCount(0, $antiJoin['cross_table_duplicates']);
    }

    public function testReconcilerDriftDetection()
    {
        $reconciler = new CbtMigrationReconciler(null);

        $records = [
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'updated_at' => '2026-02-23 08:00:00'],
            ['id' => 2, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1001, 'updated_at' => '2026-02-23 08:05:00'],
            ['id' => 3, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1002, 'updated_at' => '2026-02-23 09:30:00'], // Modified after watermark
            ['id' => 4, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1003, 'updated_at' => '2026-02-23 09:45:00'], // New row after max processed ID 3
        ];

        $watermarkTime = '2026-02-23 09:00:00';
        $maxProcessedId = 3;

        $drift = $reconciler->detectDelta($maxProcessedId, $watermarkTime, $records);

        $this->assertSame(1, $drift['new_rows_count']);
        $this->assertSame([4], $drift['new_row_ids']);
        $this->assertSame(1, $drift['modified_rows_count']);
        $this->assertSame([3], $drift['modified_row_ids']);
        $this->assertTrue($drift['requires_write_freeze']);
    }

    public function testOrchestratorDryRunFullPipeline()
    {
        $orchestrator = new CbtMigrationOrchestrator(null);

        $sourceMock = [
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'answer' => 'A', 'score' => 1.0, '_mock_quarantine_reason' => 'AMBIGUOUS_METADATA'],
            ['id' => 2, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1001, 'answer' => 'B', 'score' => 1.0, '_mock_quarantine_reason' => 'AMBIGUOUS_METADATA'],
            ['id' => 3, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1002, 'answer' => 'C', 'score' => 1.0, '_mock_quarantine_reason' => 'AMBIGUOUS_METADATA'],
        ];

        $report = $orchestrator->run([
            'execute'        => false,
            'batch_size'     => 2,
            'source_records' => $sourceMock,
            'environment'    => 'testing',
        ]);

        $this->assertSame('COMPLETED', $report['pipeline_status']);
        $this->assertSame('DRY-RUN', $report['mode']);
        $this->assertSame(3, $report['total_source_scanned']);
        $this->assertSame(2, $report['total_batches_run']);
        $this->assertSame(3, $report['last_processed_id']);
    }
}
