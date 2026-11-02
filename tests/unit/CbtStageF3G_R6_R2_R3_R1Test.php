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
use App\Services\Cbt\Review\ReviewerEvidenceValidator;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\TrustedServerVerificationResult;
use App\Services\Cbt\Review\InvalidReviewActionException;
use App\Controllers\Admin\CbtReview;

/**
 * CbtStageF3G_R6_R2_R3_R1Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R6-R2-R3-R1
 * CRYPTOGRAPHIC CANONICALIZATION, STATE CONSISTENCY & EVIDENCE OWNERSHIP HARDENING AUDIT.
 */
class CbtStageF3G_R6_R2_R3_R1Test extends TestCase
{
    private ReviewerEvidenceValidator $validator;
    private ReviewerDecisionValidator $decisionValidator;
    private ReviewerWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ReviewerEvidenceValidator();
        $this->decisionValidator = new ReviewerDecisionValidator($this->validator);
        $this->workflow = new ReviewerWorkflowService(
            $this->decisionValidator,
            null,
            $this->validator
        );
        $this->workflow->loadFromShadowDeviations();
    }

    // -------------------------------------------------------------------------
    // GROUP 1: CANONICAL HMAC ENCODING & COLLISION RESISTANCE (AUDIT A)
    // -------------------------------------------------------------------------

    public function testDelimiterInjectionDoesNotCauseHmacCollision(): void
    {
        // Case A: normal fields
        $payloadA = $this->validator->encodeCanonicalSecurityPayload(
            sourceType: 'BAP',
            referenceId: 'BAP-001',
            sourceTable: 'cbt_bap',
            status: 'VALIDATION_PASSED',
            authSemanticLevel: 'AUTHENTICATION_NOT_VERIFIED',
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_001',
            boundTestId: '501'
        );

        // Case B: malicious referenceId attempting delimiter collision by injecting pipes and field names
        $payloadB = $this->validator->encodeCanonicalSecurityPayload(
            sourceType: 'BAP',
            referenceId: "BAP-001|sourceTable:cbt_bap",
            sourceTable: '',
            status: 'VALIDATION_PASSED',
            authSemanticLevel: 'AUTHENTICATION_NOT_VERIFIED',
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_001',
            boundTestId: '501'
        );

        // Payloads must be strictly different
        $this->assertNotSame($payloadA, $payloadB);

        $sigA = $this->validator->computeIssuerSignature('BAP', 'BAP-001', 'cbt_bap', 'VALIDATION_PASSED', 'AUTHENTICATION_NOT_VERIFIED', false, false, 'REV_501_2b6231cb77', 'STD_001', '501');
        $sigB = $this->validator->computeIssuerSignature('BAP', "BAP-001|sourceTable:cbt_bap", '', 'VALIDATION_PASSED', 'AUTHENTICATION_NOT_VERIFIED', false, false, 'REV_501_2b6231cb77', 'STD_001', '501');

        $this->assertNotSame($sigA, $sigB);
    }

    public function testNullVsEmptyStringAmbiguityEliminated(): void
    {
        $payloadWithNull = $this->validator->encodeCanonicalSecurityPayload(
            sourceType: 'BAP',
            referenceId: 'BAP-001',
            sourceTable: 'cbt_bap',
            status: 'VALIDATION_PASSED',
            authSemanticLevel: 'AUTHENTICATION_NOT_VERIFIED',
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: null,
            boundStudentId: 'STD_001',
            boundTestId: '501'
        );

        $payloadWithEmptyStr = $this->validator->encodeCanonicalSecurityPayload(
            sourceType: 'BAP',
            referenceId: 'BAP-001',
            sourceTable: 'cbt_bap',
            status: 'VALIDATION_PASSED',
            authSemanticLevel: 'AUTHENTICATION_NOT_VERIFIED',
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: '',
            boundStudentId: 'STD_001',
            boundTestId: '501'
        );

        $this->assertStringContainsString('11:boundCaseId:NULL:0:', $payloadWithNull);
        $this->assertStringContainsString('11:boundCaseId:STR:0:', $payloadWithEmptyStr);
        $this->assertNotSame($payloadWithNull, $payloadWithEmptyStr);
    }

    public function testTypeConfusionBetweenIntegerAndStringResolved(): void
    {
        $payloadInt = $this->validator->encodeCanonicalSecurityPayload(
            sourceType: 'BAP',
            referenceId: 'BAP-001',
            sourceTable: 'cbt_bap',
            status: 'VALIDATION_PASSED',
            authSemanticLevel: 'AUTHENTICATION_NOT_VERIFIED',
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_001',
            boundStudentId: 1001, // integer
            boundTestId: 501
        );

        $payloadStr = $this->validator->encodeCanonicalSecurityPayload(
            sourceType: 'BAP',
            referenceId: 'BAP-001',
            sourceTable: 'cbt_bap',
            status: 'VALIDATION_PASSED',
            authSemanticLevel: 'AUTHENTICATION_NOT_VERIFIED',
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_001',
            boundStudentId: '1001', // string
            boundTestId: 501
        );

        $this->assertStringContainsString('14:boundStudentId:INT:4:1001', $payloadInt);
        $this->assertStringContainsString('14:boundStudentId:STR:4:1001', $payloadStr);
        $this->assertNotSame($payloadInt, $payloadStr);
    }

    // -------------------------------------------------------------------------
    // GROUP 2: OBJECT IMMUTABILITY & CLONING DEFENSE (AUDIT B)
    // -------------------------------------------------------------------------

    public function testCloningTrustedVerificationResultIsStrictlyDisallowed(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-TEST-001',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('TrustedServerVerificationResult is strictly immutable and cannot be cloned.');

        $clone = clone $result;
    }

    public function testReadonlyPropertiesCannotBeMutated(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-TEST-001',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        // Malicious property write attempt
        $result->isAuthoritativeVerified = true;
    }

    // -------------------------------------------------------------------------
    // GROUP 3: AUTHORIZATION STATE CONSISTENCY & CONCURRENCY (AUDIT C)
    // -------------------------------------------------------------------------

    public function testDuplicateApplyOnAlreadyResolvedCaseIsRejected(): void
    {
        // 1. First valid apply
        $this->workflow->applyDecision(
            'REV_501_2b6231cb77',
            'admin',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Minor rounding difference (0.02) within tolerance of 0.05',
            [
                'actor_role'   => 'ADMIN',
                'decision_id'  => 'DEC-ROUND-01',
                'legacy_score' => '49.98',
                'v3_score'     => '50.00',
                'delta'        => '0.02',
            ]
        );

        $case = $this->workflow->getCase('REV_501_2b6231cb77');
        $this->assertSame('RESOLVED', $case['review_status']);

        // 2. Second apply on same case MUST throw InvalidReviewActionException
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage("Case 'REV_501_2b6231cb77' is already resolved. Duplicate apply rejected.");

        $this->workflow->applyDecision(
            'REV_501_2b6231cb77',
            'admin_second',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Attempting duplicate apply',
            [
                'actor_role'   => 'ADMIN',
                'decision_id'  => 'DEC-ROUND-01',
            ]
        );
    }

    public function testOptimisticConcurrencyConflictFailsClosed(): void
    {
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage("CONCURRENCY CONFLICT: Case 'REV_501_2b6231cb77' status changed from 'STALE_STATUS' to 'ACKNOWLEDGEMENT_REQUIRED' since preview. Apply rejected.");

        $this->workflow->applyDecision(
            'REV_501_2b6231cb77',
            'admin',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Valid reason',
            [
                'actor_role'      => 'ADMIN',
                'decision_id'     => 'DEC-ROUND-01',
                'expected_status' => 'STALE_STATUS', // Concurrency mismatch
                'legacy_score'    => '49.98',
                'v3_score'        => '50.00',
                'delta'           => '0.02',
            ]
        );
    }

    public function testTeacherAssignmentRevocationBetweenPreviewAndApplyFailsClosed(): void
    {
        // 1. Assign teacher
        $this->validator->registerTeacherAssignment('teacher_math', [501], ['MATEMATIKA']);

        // 2. Preview succeeds
        $preview = $this->workflow->previewDecision(
            'REV_501_2b6231cb77',
            'teacher_math',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Valid reason',
            [
                'actor_role'   => 'TEACHER',
                'decision_id'  => 'DEC-ROUND-01',
                'legacy_score' => '49.98',
                'v3_score'     => '50.00',
                'delta'        => '0.02',
            ]
        );
        $this->assertTrue($preview['is_valid']);

        // 3. Assignment is revoked by assigning to a different test only
        $this->validator->registerTeacherAssignment('teacher_math', [999], ['OTHER']);

        // 4. Apply MUST fail closed with GOVERNANCE BREACH
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage("Teacher 'teacher_math' is not authorized to review Test ID 501");

        $this->workflow->applyDecision(
            'REV_501_2b6231cb77',
            'teacher_math',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Valid reason',
            [
                'actor_role'   => 'TEACHER',
                'decision_id'  => 'DEC-ROUND-01',
                'legacy_score' => '49.98',
                'v3_score'     => '50.00',
                'delta'        => '0.02',
            ]
        );
    }

    // -------------------------------------------------------------------------
    // GROUP 4: INPUT & OUTPUT MEMORY BOUNDING (AUDIT D)
    // -------------------------------------------------------------------------

    public function testOversizedInputStringTruncatedBeforeEncodingCheck(): void
    {
        // 20KB massive string
        $huge = str_repeat('X', 20000);
        $sanitized = $this->validator->sanitizeDiagnosticPayload($huge);

        $this->assertLessThanOrEqual(250, mb_strlen($sanitized));
    }

    public function testCumulativeOutputBytesCapped(): void
    {
        // Wide and nested array to exceed 4KB cumulative output
        $wideArray = [];
        for ($i = 0; $i < 10; $i++) {
            $wideArray["group_{$i}"] = [];
            for ($j = 0; $j < 10; $j++) {
                $wideArray["group_{$i}"]["item_{$j}"] = str_repeat('M', 200);
            }
        }

        $cumulative = 0;
        $sanitized = $this->validator->sanitizeDiagnosticPayload($wideArray, 2, 0, $cumulative, 500);

        $this->assertIsArray($sanitized);
        $json = json_encode($sanitized);
        $this->assertStringContainsString('OUTPUT_SIZE_LIMIT_REACHED', $json);
    }

    public function testNullBytesFilteredAsBinary(): void
    {
        $withNullByte = "ValidPrefix\0MaliciousPayload";
        $sanitized = $this->validator->sanitizeDiagnosticPayload($withNullByte);

        $this->assertSame('[BINARY_DATA_FILTERED]', $sanitized);
    }

    public function testSensitiveKeysRedacted(): void
    {
        $payload = [
            'username'     => 'admin',
            'password'     => 'supersecret123',
            'auth_token'   => 'jwt.token.here',
            'api_key'      => 'secret_key_value',
            'public_field' => 'visible',
        ];

        $sanitized = $this->validator->sanitizeDiagnosticPayload($payload);

        $this->assertSame('admin', $sanitized['username']);
        $this->assertSame('[REDACTED_SECRET]', $sanitized['password']);
        $this->assertSame('[REDACTED_SECRET]', $sanitized['auth_token']);
        $this->assertSame('[REDACTED_SECRET]', $sanitized['api_key']);
        $this->assertSame('visible', $sanitized['public_field']);
    }

    // -------------------------------------------------------------------------
    // GROUP 5: HISTORICAL ANSWER OWNERSHIP & LINEAGE (AUDIT E)
    // -------------------------------------------------------------------------

    public function testAmbiguousRetakeLackingSessionForeignKeyCannotProveAnswerOwnership(): void
    {
        // REV_503_4266a4ed7f has category AMBIGUOUS_RETAKE and requires DEC-RET-01
        $case = $this->workflow->getCase('REV_503_4266a4ed7f');
        $this->assertNotNull($case);
        $this->assertSame('AMBIGUOUS_RETAKE', $case['category']);

        // Without verified owner_approval_ref, answer ownership is unprovable
        $preview = $this->workflow->previewDecision(
            'REV_503_4266a4ed7f',
            'admin',
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Attempting selection without owner decree',
            [
                'decision_id'         => 'DEC-RET-01',
                'selected_session_id' => '1008',
                'owner_approval_ref'  => '', // Missing approval
            ]
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertStringContainsString('Multiple retake selection requires explicit', $preview['error']);
    }

    // -------------------------------------------------------------------------
    // GROUP 6: CANONICAL CASE IDENTITIES & GATE INVARIANTS (AUDIT F & G)
    // -------------------------------------------------------------------------

    public function testAllFiveCanonicalCasesRetainIdentitiesAndGoverningDecisions(): void
    {
        $queue = $this->workflow->getQueue();
        $this->assertCount(5, $queue);

        $expectedMapping = [
            'REV_501_2b6231cb77' => ['cat' => 'ROUNDING_DIFFERENCE', 'decision' => 'DEC-ROUND-01', 'party' => 'Reviewer'],
            'REV_501_7b48bc8a1f' => ['cat' => 'LEGACY_ZERO_FALLBACK', 'decision' => 'DEC-ABS-01',   'party' => 'Reviewer'],
            'REV_502_3ff144b11c' => ['cat' => 'PENDING_ESSAY',       'decision' => 'DEC-ESS-01',   'party' => 'Teacher'],
            'REV_503_4266a4ed7f' => ['cat' => 'AMBIGUOUS_RETAKE',    'decision' => 'DEC-RET-01',   'party' => 'Product Owner'],
            'REV_503_fc68cd64f2' => ['cat' => 'LEGACY_ZERO_FALLBACK', 'decision' => 'DEC-ABS-01',   'party' => 'Reviewer'],
        ];

        foreach ($expectedMapping as $caseId => $meta) {
            $case = $this->workflow->getCase($caseId);
            $this->assertNotNull($case, "Case {$caseId} must exist in canonical queue.");
            $this->assertSame($meta['cat'], $case['category']);
            $this->assertSame($meta['party'], $case['responsible_party']);
        }
    }

    public function testGateStatusRemainsRequiresReview(): void
    {
        $summary = $this->workflow->getSummary();
        $this->assertSame(5, $summary['pending_cases']);
        $this->assertSame(0, $summary['resolved_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);

        $case = $this->workflow->getCase('REV_501_2b6231cb77');
        $readiness = $this->validator->evaluateReadiness(
            $case,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            ['role' => 'REVIEWER', 'username' => 'auditor']
        );
        $this->assertSame(ReviewerEvidenceValidator::READINESS_READY_FOR_AUTHORIZED_REVIEW, $readiness['readiness_status']);
        $this->assertFalse($this->validator->isAuthoritativeInfrastructureAvailable());
    }
}
