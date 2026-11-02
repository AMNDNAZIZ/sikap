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
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\ReviewerEvidenceValidator;
use App\Services\Cbt\Review\ReviewerSourceVerifier;
use App\Services\Cbt\Review\HistoricalSourceAdapter;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\CbtSessionModel;
use App\Models\CbtAnswerModel;
use PDO;

/**
 * CbtStageF3G_R2Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R2 Test Suite
 * Governance Evidence Reconciliation & Test Baseline Integrity
 */
class CbtStageF3G_R2Test extends TestCase
{
    /**
     * Test 1: Verify the 5 canonical Case IDs generated from shadow deviations.
     */
    public function testCanonicalCaseIdentityFromShadowDeviations(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $queue = $workflow->getQueue();

        $expectedIds = [
            'REV_501_2b6231cb77',
            'REV_501_7b48bc8a1f',
            'REV_502_3ff144b11c',
            'REV_503_4266a4ed7f',
            'REV_503_fc68cd64f2',
        ];

        $actualIds = array_column($queue, 'case_id');
        sort($expectedIds);
        sort($actualIds);

        $this->assertEquals($expectedIds, $actualIds, 'Canonical Case IDs must match expected hashes exactly.');
    }

    /**
     * Test 2: Verify Evidence Validator registers evidence for all 5 canonical Case IDs.
     */
    public function testEvidenceValidatorBindsToCanonicalCaseIds(): void
    {
        $evidenceValidator = new ReviewerEvidenceValidator();
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();

        foreach ($workflow->getQueue() as $case) {
            $caseId = $case['case_id'];
            $evidenceList = $evidenceValidator->getEvidenceForCase($caseId);
            $this->assertNotEmpty($evidenceList, "Evidence must be registered for canonical case ID: {$caseId}");
            $this->assertEquals($caseId, $evidenceList[0]['related_case_id']);
        }
    }

    /**
     * Test 3: SCORE_PRESENT_UNVERIFIED state when item scores exist but teacher provenance unproven.
     * Enforces DEC-ESS-01: Scores cannot be published without verifiable grading provenance.
     */
    public function testScorePresentUnverifiedWhenGraderProvenanceUnproven(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $answers = [
            ['id' => 1, 'score' => '85.00'],
            ['id' => 2, 'score' => '90.00'],
        ];

        // Provenance unverified (provenanceVerified = false)
        $res = $adapter->evaluateEssayGradingState(87.5, $answers, 2, false, false);

        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_SCORE_PRESENT_UNVERIFIED, $res['state']);
        $this->assertTrue($res['is_hold_enforced']);
        $this->assertTrue($res['final_score_withheld']);
        $this->assertStringContainsString('provenance', $res['discrepancy']);
    }

    /**
     * Test 4: UNANSWERED_ESSAY granular differentiation (RECORD_ABSENT vs PROVEN_BLANK).
     */
    public function testUnansweredEssayDifferentiatesRecordAbsentVsBlankSubmission(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // Case A: 0 answer records found (possible data loss, reset, or omitted)
        $resAbsent = $adapter->evaluateEssayGradingState(null, [], 2);
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_UNANSWERED, $resAbsent['state']);
        $this->assertEquals('RECORD_ABSENT_POSSIBLE_DATA_LOSS', $resAbsent['unanswered_subreason']);
        $this->assertTrue($resAbsent['is_hold_enforced']);

        // Case B: Answer records exist, but student submitted confirmed blank text
        $blankAnswers = [
            ['id' => 1, 'answer' => '', 'score' => null],
            ['id' => 2, 'answer' => '   ', 'score' => null],
        ];
        $resBlank = $adapter->evaluateEssayGradingState(null, $blankAnswers, 2, false, null, [
            'attendance_verified'        => true,
            'session_ownership_verified' => true,
        ]);
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_PENDING, $resBlank['state']);
        $this->assertEquals('PROVEN_BLANK_SUBMISSION', $resBlank['unanswered_subreason']);
    }

    /**
     * Test 5: Verify Student and Teacher model schema columns against DBA runbook assumptions.
     */
    public function testModelColumnsReflectActualSchema(): void
    {
        // 1. StudentModel checks via Reflection
        $studentRef = new \ReflectionClass(StudentModel::class);
        $studentProps = $studentRef->getDefaultProperties();
        $studentFields = $studentProps['allowedFields'] ?? [];

        $this->assertContains('name', $studentFields, 'Column is "name", NOT "nama".');
        $this->assertNotContains('nama', $studentFields);
        $this->assertContains('class_id', $studentFields, 'Column is "class_id", NOT "kelas_id".');
        $this->assertNotContains('kelas_id', $studentFields);
        $this->assertNotContains('status', $studentFields, 'students table has no "status" column.');

        // 2. TeacherModel checks via Reflection
        $teacherRef = new \ReflectionClass(TeacherModel::class);
        $teacherProps = $teacherRef->getDefaultProperties();
        $teacherFields = $teacherProps['allowedFields'] ?? [];

        $this->assertContains('name', $teacherFields, 'Column is "name", NOT "nama".');
        $this->assertNotContains('nama', $teacherFields);
        $this->assertContains('nip', $teacherFields);
        $this->assertContains('is_active', $teacherFields, 'Column is "is_active", NOT "status".');
        $this->assertNotContains('status', $teacherFields, 'teachers table has no "status" column.');

        // 3. CbtAnswerModel checks (proves lack of session_id and grader_id)
        $answerRef = new \ReflectionClass(CbtAnswerModel::class);
        $answerProps = $answerRef->getDefaultProperties();
        $answerFields = $answerProps['allowedFields'] ?? [];

        $this->assertNotContains('session_id', $answerFields, 'cbt_answers physically lacks session_id.');
        $this->assertNotContains('grader_id', $answerFields, 'cbt_answers physically lacks grader_id.');
        $this->assertNotContains('graded_by', $answerFields, 'cbt_answers physically lacks graded_by.');
    }

    /**
     * Test 6: Gate status invariant: STRICTLY BLOCKED UNTIL EXPLICIT AUTHORIZATION.
     */
    public function testGateStatusInvariantStrictlyBlocked(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $summary = $workflow->getSummary();

        $this->assertEquals(0, $summary['resolved_cases']);
        $this->assertEquals(5, $summary['pending_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);
        $this->assertFalse($summary['production_cutover_authorized'] ?? false);
    }
}
