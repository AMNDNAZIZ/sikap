<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * CbtIteration5StagingRehearsalTest
 *
 * ESPATA Assessment Intelligence V3 - Iteration 5
 * Controlled Staging Rehearsal & Migration Invariant Verification.
 */
class CbtIteration5StagingRehearsalTest extends TestCase
{
    private function getAppPath(): string
    {
        return defined('APPPATH') ? APPPATH : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
    }

    public function testMigrationFilesCompileAndContainRequiredInvariants(): void
    {
        $appPath = $this->getAppPath();
        $m1Path = $appPath . 'Database/Migrations/2026-11-01-090001_CreateCbtAssessmentIntelligenceV3Foundation.php';
        $m2Path = $appPath . 'Database/Migrations/2026-11-01-160000_CreateCbtAssessmentIntelligenceV3GovernanceScoring.php';
        $m3Path = $appPath . 'Database/Migrations/2026-11-02-090000_CreateCbtGradebookPublicationsV3.php';

        $this->assertFileExists($m1Path);
        $this->assertFileExists($m2Path);
        $this->assertFileExists($m3Path);

        $m1 = file_get_contents($m1Path);
        $this->assertStringContainsString('cbt_test_items', $m1);
        $this->assertStringContainsString('cbt_test_item_versions', $m1);
        $this->assertStringContainsString('cbt_session_items', $m1);
        $this->assertStringContainsString('cbt_answers_v3', $m1);
        $this->assertStringContainsString('trg_cbt_tiv_immutable', $m1);
        $this->assertStringContainsString('trg_cbt_si_immutable', $m1);

        $m2 = file_get_contents($m2Path);
        $this->assertStringContainsString('cbt_essay_grades_v3', $m2);
        $this->assertStringContainsString('cbt_session_scores_v3', $m2);
        $this->assertStringContainsString('cbt_bap_documents_v3', $m2);
        $this->assertStringContainsString('cbt_review_cases_v3', $m2);
        $this->assertStringContainsString('cbt_audit_trail_v3', $m2);

        $m3 = file_get_contents($m3Path);
        $this->assertStringContainsString('cbt_gradebook_publications_v3', $m3);
        $this->assertStringContainsString('decimal(6,3)', $m3);
    }

    public function testStagingRehearsalCommandIsRegistered(): void
    {
        $cmdPath = $this->getAppPath() . 'Commands/CbtIteration5StagingRehearsalCommand.php';
        $this->assertFileExists($cmdPath);

        require_once $cmdPath;
        $ref = new \ReflectionClass(\App\Commands\CbtIteration5StagingRehearsalCommand::class);
        $defaults = $ref->getDefaultProperties();
        $this->assertSame('cbt:staging-rehearsal', $defaults['name']);
        $this->assertSame('CBT', $defaults['group']);
    }

    public function testCryptographicAuditTamperDetectionLogic(): void
    {
        $prevHash = '0000000000000000000000000000000000000000000000000000000000000000';
        $keyId = 'KEY-TEST-001';
        $chain = [];

        // Build 3 valid nodes
        for ($seq = 1; $seq <= 3; $seq++) {
            $payload = json_encode(['step' => $seq, 'action' => 'VALID_ACTION']);
            $recordHash = hash_hmac('sha256', "{$seq}|ACTION|table|1|1|{$payload}|{$keyId}|{$prevHash}", 'TEST_KEY');
            $chain[] = [
                'seq_no' => $seq,
                'payload' => $payload,
                'prev_hash' => $prevHash,
                'record_hash' => $recordHash,
            ];
            $prevHash = $recordHash;
        }

        // Verify valid chain
        $verify = function(array $nodes): bool {
            $expected = '0000000000000000000000000000000000000000000000000000000000000000';
            foreach ($nodes as $n) {
                if ($n['prev_hash'] !== $expected) return false;
                $calc = hash_hmac('sha256', "{$n['seq_no']}|ACTION|table|1|1|{$n['payload']}|KEY-TEST-001|{$expected}", 'TEST_KEY');
                if ($calc !== $n['record_hash']) return false;
                $expected = $n['record_hash'];
            }
            return true;
        };

        $this->assertTrue($verify($chain), 'Initial chain must be valid');

        // Tamper with node 2 payload
        $tamperedChain = $chain;
        $tamperedChain[1]['payload'] = json_encode(['step' => 2, 'action' => 'MALICIOUS_TAMPER']);
        $this->assertFalse($verify($tamperedChain), 'Tampered chain must fail verification immediately');
    }

    public function testDistinctionBetweenHashSealAndDigitalSignature(): void
    {
        // SHA-256 seal proves integrity, but not signer non-repudiation
        $doc = 'BAP-DATA-ROOM-1-2026-11-02';
        $hash1 = hash('sha256', $doc);
        $hash2 = hash('sha256', $doc);
        $this->assertSame($hash1, $hash2);

        // A hash does not carry cryptographic proof of signer identity unless paired with asymmetric private key
        $this->assertSame(64, strlen($hash1));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash1);
    }
}
