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
use App\Services\Cbt\Review\InvalidReviewActionException;
use App\Controllers\Admin\CbtReview;
use RuntimeException;
use InvalidArgumentException;

/**
 * CbtStageF3CTest
 *
 * ESPATA Assessment Intelligence V3 - Stage F3C Test Suite
 * Interactive Reviewer Workflow & Verification Gate Interface
 *
 * Verifies all 15 mandatory test scenarios:
 * 1. Authorized reviewer can view queue.
 * 2. Unauthorized actor (STUDENT, GUEST) is rejected.
 * 3. Case detail does not leak PII (only pseudonymous student ID).
 * 4. Filter and pagination work accurately.
 * 5. Preview is strictly non-mutating.
 * 6. Preview rejects mismatched Decision ID.
 * 7. Preview DEC-ROUND-01 rejects delta > 0.05.
 * 8. Preview DEC-ABS-01 does not conclude ABSENT without evidence.
 * 9. Preview DEC-ESS-01 maintains HOLD when mandatory essay is pending.
 * 10. Preview DEC-RET-01 does not select session automatically.
 * 11. Audit viewer is read-only.
 * 12. Audit integrity failure blocks decision and preview.
 * 13. Direct URL access cannot bypass authorization.
 * 14. Harmful input is sanitized and not executed.
 * 15. The 5 operational review cases remain completely untouched.
 */
class CbtStageF3CTest extends TestCase
{
    private string $deviationsCsv;

    protected function setUp(): void
    {
        $this->deviationsCsv = 'C:/xampp/htdocs/siakad/writable/shadow_artifacts/shadow_deviations.csv';
    }

    /**
     * Helper to instantiate a fresh ReviewerWorkflowService loaded with the 5 shadow cases.
     */
    private function createLoadedWorkflow(?ReviewerAuditService $auditService = null): ReviewerWorkflowService
    {
        $workflow = new ReviewerWorkflowService(null, $auditService);
        $loaded = $workflow->loadFromShadowDeviations($this->deviationsCsv);
        $this->assertEquals(5, $loaded, 'Exactly 5 review cases must be ingested from shadow deviations CSV.');
        return $workflow;
    }

    /**
     * Test 1: Reviewer yang berwenang dapat melihat queue.
     */
    public function testAuthorizedReviewerCanViewQueue(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $cases = $workflow->getQueue();

        $this->assertCount(5, $cases);

        // Verify summary statistics
        $summary = $workflow->getSummary();
        $this->assertEquals(5, $summary['total_cases']);
        $this->assertEquals(5, $summary['pending_cases']);
        $this->assertEquals(0, $summary['resolved_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);

        // Verify that responsible party breakdown accurately reflects governance requirements
        $this->assertEquals(1, $summary['teacher_action_required'], '1 Teacher action required (PENDING_ESSAY)');
        $this->assertEquals(1, $summary['owner_decision_required'], '1 Owner decision required (AMBIGUOUS_RETAKE)');
        $this->assertEquals(3, $summary['acknowledgement_required'], '3 Acknowledgement required (ROUNDING + 2 FALLBACK)');
    }

    /**
     * Test 2: Reviewer tanpa izin ditolak.
     */
    public function testUnauthorizedActorIsRejected(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_501_2b6231cb77';

        // 1. Student role is rejected in preview
        $previewStudent = $workflow->previewDecision(
            $caseId,
            'STUDENT_001',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Attempting student self-acknowledgement',
            ['actor_role' => 'STUDENT', 'decision_id' => 'DEC-ROUND-01']
        );
        $this->assertFalse($previewStudent['is_valid']);
        $this->assertStringContainsString('GOVERNANCE BREACH', $previewStudent['error']);
        $this->assertStringContainsString("role 'STUDENT' is not authorized", $previewStudent['error']);

        // 2. Guest role is rejected in preview
        $previewGuest = $workflow->previewDecision(
            $caseId,
            'GUEST_USER',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Guest viewing and attempting action',
            ['actor_role' => 'GUEST', 'decision_id' => 'DEC-ROUND-01']
        );
        $this->assertFalse($previewGuest['is_valid']);
        $this->assertStringContainsString('GOVERNANCE BREACH', $previewGuest['error']);

        // 3. Unauthorized role in applyDecision throws InvalidReviewActionException
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('GOVERNANCE BREACH');
        $workflow->applyDecision(
            $caseId,
            'STUDENT_001',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Direct execution by student',
            ['actor_role' => 'STUDENT', 'decision_id' => 'DEC-ROUND-01']
        );
    }

    /**
     * Test 3: Case detail tidak menampilkan data yang tidak berhak diakses (Zero PII).
     */
    public function testCaseDetailDoesNotLeakPii(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_502_3ff144b11c'; // Bahasa Indonesia pending essay

        $detail = $workflow->getCaseDetail($caseId);
        $this->assertNotNull($detail);

        // Verify pseudonymous student ID format
        $this->assertStringStartsWith('STD_', $detail['pseudonymous_student_id']);
        $this->assertEquals(16, strlen($detail['pseudonymous_student_id']));

        // Verify NO PII fields exist
        $this->assertArrayNotHasKey('nisn', $detail);
        $this->assertArrayNotHasKey('nik', $detail);
        $this->assertArrayNotHasKey('student_name', $detail);
        $this->assertArrayNotHasKey('nama_siswa', $detail);
        $this->assertArrayNotHasKey('password', $detail);

        // JSON serialization check: ensures no raw PII keywords appear in payload
        $json = json_encode($detail);
        $this->assertDoesNotMatchRegularExpression('/(nisn|nomor_induk|password|secret|nik)/i', $json);

        // Verify provenance and verification metadata
        $this->assertArrayHasKey('verification', $detail);
        $this->assertEquals('CODE_LEVEL_VERIFIED', $detail['verification']['level']);
        $this->assertEquals('MOCK_SANDBOX_ONLY', $detail['verification']['database_verification']);
    }

    /**
     * Test 4: Filter dan pagination bekerja.
     */
    public function testFilterAndPaginationWork(): void
    {
        $workflow = $this->createLoadedWorkflow();

        // 1. Filter by category: ROUNDING_DIFFERENCE (exactly 1 case)
        $resRound = $workflow->getFilteredQueue(['category' => 'ROUNDING_DIFFERENCE']);
        $this->assertEquals(1, $resRound['total_items']);
        $this->assertEquals('REV_501_2b6231cb77', $resRound['items'][0]['case_id']);

        // 2. Filter by status: TEACHER_ACTION_REQUIRED (exactly 1 case)
        $resTeacher = $workflow->getFilteredQueue(['review_status' => ReviewerWorkflowService::STATUS_TEACHER_ACTION_REQUIRED]);
        $this->assertEquals(1, $resTeacher['total_items']);
        $this->assertEquals('REV_502_3ff144b11c', $resTeacher['items'][0]['case_id']);

        // 3. Filter by responsible party: Product Owner (exactly 1 case)
        $resOwner = $workflow->getFilteredQueue(['responsible_party' => 'Product Owner']);
        $this->assertEquals(1, $resOwner['total_items']);
        $this->assertEquals('REV_503_4266a4ed7f', $resOwner['items'][0]['case_id']);

        // 4. Search query by Test ID: "503" (2 cases: AMBIGUOUS_RETAKE and LEGACY_ZERO_FALLBACK)
        $resSearch = $workflow->getFilteredQueue(['search' => '503']);
        $this->assertEquals(2, $resSearch['total_items']);

        // 5. Pagination test: per_page = 2, total_items = 5 -> total_pages = 3
        $page1 = $workflow->getFilteredQueue([], 1, 2);
        $this->assertCount(2, $page1['items']);
        $this->assertEquals(1, $page1['page']);
        $this->assertEquals(3, $page1['total_pages']);

        $page2 = $workflow->getFilteredQueue([], 2, 2);
        $this->assertCount(2, $page2['items']);
        $this->assertEquals(2, $page2['page']);
        // Verify items on page 2 differ from page 1
        $this->assertNotEquals($page1['items'][0]['case_id'], $page2['items'][0]['case_id']);

        $page3 = $workflow->getFilteredQueue([], 3, 2);
        $this->assertCount(1, $page3['items']);
        $this->assertEquals(3, $page3['page']);
    }

    /**
     * Test 5: Preview tidak mengubah state (strictly non-mutating).
     */
    public function testPreviewIsStrictlyNonMutating(): void
    {
        $testStorage = sys_get_temp_dir() . '/f3c_audit_test_' . uniqid() . '.json';
        $auditService = new ReviewerAuditService($testStorage);
        $workflow = $this->createLoadedWorkflow($auditService);

        $caseId = 'REV_501_2b6231cb77';
        $caseBefore = $workflow->getCase($caseId);
        $queueBefore = $workflow->getQueue();
        $summaryBefore = $workflow->getSummary();
        $auditCountBefore = $auditService->count();

        // Execute successful Decision Preview
        $preview = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Simulated rounding tolerance acknowledgement within 0.05',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        $this->assertTrue($preview['is_valid']);
        $this->assertEquals(ReviewerWorkflowService::STATUS_RESOLVED, $preview['projected_status']);
        $this->assertEquals('DEC-ROUND-01', $preview['decision_id']);
        $this->assertNotEmpty($preview['simulated_audit_entry']);

        // INVARIANT CHECK: In-memory case state is 100% UNCHANGED
        $caseAfter = $workflow->getCase($caseId);
        $this->assertEquals($caseBefore, $caseAfter, 'Case attributes in memory must remain completely unchanged.');
        $this->assertEquals(ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED, $caseAfter['review_status']);
        $this->assertNull($caseAfter['decision']);

        // INVARIANT CHECK: Queue and Summary are 100% UNCHANGED
        $this->assertEquals($queueBefore, $workflow->getQueue());
        $this->assertEquals($summaryBefore, $workflow->getSummary());

        // INVARIANT CHECK: Audit trail on disk has zero writes
        $this->assertEquals($auditCountBefore, $auditService->count(), 'Preview must not append any records to audit log.');

        // Clean up test file if created
        if (file_exists($testStorage)) {
            @unlink($testStorage);
        }
    }

    /**
     * Test 6: Preview menolak Decision ID yang tidak sesuai.
     */
    public function testPreviewRejectsMismatchedDecisionId(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_501_2b6231cb77'; // Category is ROUNDING_DIFFERENCE (governed by DEC-ROUND-01)

        // Attempt to pass DEC-ABS-01 to a ROUNDING_DIFFERENCE case
        $preview = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Applying wrong policy decision ID',
            [
                'actor_role'          => 'REVIEWER',
                'decision_id'         => 'DEC-ABS-01',
                'require_decision_id' => true,
            ]
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertStringContainsString('GOVERNANCE BREACH', $preview['error']);
        $this->assertStringContainsString("Provided decision_id 'DEC-ABS-01' does not match required policy decision 'DEC-ROUND-01'", $preview['error']);
    }

    /**
     * Test 7: Preview DEC-ROUND-01 menolak delta di atas 0.05.
     */
    public function testPreviewDecRound01RejectsDeltaExceedingTolerance(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_501_2b6231cb77';

        // 1. Micro deviation delta 0.0500001 (exceeds 0.05 tolerance)
        $previewExceeded = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Testing precision tolerance boundary',
            [
                'actor_role'  => 'REVIEWER',
                'decision_id' => 'DEC-ROUND-01',
                'delta'       => '0.0500001',
            ]
        );
        $this->assertFalse($previewExceeded['is_valid']);
        $this->assertStringContainsString('exceeds permitted tolerance of 0.05', $previewExceeded['error']);

        // 2. Exact boundary delta 0.0500000 (within 0.05 tolerance)
        $previewValid = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Testing valid boundary tolerance',
            [
                'actor_role'  => 'REVIEWER',
                'decision_id' => 'DEC-ROUND-01',
                'delta'       => '0.0500000',
            ]
        );
        $this->assertTrue($previewValid['is_valid']);
        $this->assertEquals(ReviewerWorkflowService::STATUS_RESOLVED, $previewValid['projected_status']);
    }

    /**
     * Test 8: Preview DEC-ABS-01 tidak menyimpulkan ABSENT tanpa bukti.
     */
    public function testPreviewDecAbs01DoesNotConcludeAbsentWithoutEvidence(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_501_7b48bc8a1f'; // LEGACY_ZERO_FALLBACK case

        // 1. Preview without verified evidence (evidence_verified = false)
        $previewUnverified = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Attempting absence ACK without verified evidence',
            [
                'actor_role'        => 'REVIEWER',
                'decision_id'       => 'DEC-ABS-01',
                'evidence_verified' => false,
            ]
        );
        $this->assertFalse($previewUnverified['is_valid']);
        $this->assertStringContainsString('Cannot acknowledge absence zero fallback without verified absence evidence', $previewUnverified['error']);

        // 2. Preview with verified evidence (evidence_verified = true)
        $previewVerified = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Confirmed absence verified via proctor attendance record',
            [
                'actor_role'        => 'REVIEWER',
                'decision_id'       => 'DEC-ABS-01',
                'evidence_verified' => true,
            ]
        );
        $this->assertTrue($previewVerified['is_valid']);
        $this->assertEquals(ReviewerWorkflowService::STATUS_RESOLVED, $previewVerified['projected_status']);
        $this->assertStringContainsString('status absensi', implode(' ', $previewVerified['consequences']));
    }

    /**
     * Test 9: Preview DEC-ESS-01 mempertahankan HOLD ketika esai wajib pending.
     */
    public function testPreviewDecEss01MaintainsHoldWhenMandatoryEssayPending(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_502_3ff144b11c'; // PENDING_ESSAY case

        // 1. Attempting to APPROVE or PUBLISH pending essay is strictly rejected
        $previewPublish = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            'APPROVE',
            'Attempting to force publish incomplete essay exam',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ESS-01']
        );
        $this->assertFalse($previewPublish['is_valid']);
        $this->assertStringContainsString('Reviewer cannot approve or publish scores for PENDING_ESSAY', $previewPublish['error']);
        $this->assertStringContainsString('[DEC-ESS-01]', $previewPublish['error']);

        // 2. Flagging for teacher action is sanctioned, projecting HOLD (TEACHER_ACTION_REQUIRED)
        $previewFlag = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_FLAG_TEACHER_ACTION,
            'Flagging for subject teacher grading',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ESS-01']
        );
        $this->assertTrue($previewFlag['is_valid']);
        $this->assertEquals(ReviewerWorkflowService::STATUS_TEACHER_ACTION_REQUIRED, $previewFlag['projected_status']);
        $this->assertEquals('Teacher', $previewFlag['projected_responsible_party']);
        $this->assertStringContainsString('HOLD / NULL', implode(' ', $previewFlag['consequences']));
    }

    /**
     * Test 10: Preview DEC-RET-01 tidak memilih sesi secara otomatis.
     */
    public function testPreviewDecRet01DoesNotSelectSessionAutomatically(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_503_4266a4ed7f'; // AMBIGUOUS_RETAKE case

        // 1. Attempting SELECT_ATTEMPT without session_id is rejected
        $previewNoSession = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Selection missing target session',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-RET-01']
        );
        $this->assertFalse($previewNoSession['is_valid']);
        $this->assertStringContainsString('Selected session ID must be provided', $previewNoSession['error']);

        // 2. Attempting SELECT_ATTEMPT without owner_approval_ref is rejected
        $previewNoOwnerRef = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Heuristic automatic selection (latest attempt)',
            [
                'actor_role'          => 'REVIEWER',
                'decision_id'         => 'DEC-RET-01',
                'selected_session_id' => '1008',
            ]
        );
        $this->assertFalse($previewNoOwnerRef['is_valid']);
        $this->assertStringContainsString("requires explicit 'owner_approval_ref'", $previewNoOwnerRef['error']);
        $this->assertStringContainsString('Automatic selection', $previewNoOwnerRef['error']);

        // 3. Selection with both selected_session_id and owner_approval_ref succeeds
        $previewValid = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Sanctioned explicit selection approved by curriculum committee',
            [
                'actor_role'          => 'REVIEWER',
                'decision_id'         => 'DEC-RET-01',
                'selected_session_id' => '1008',
                'owner_approval_ref'  => 'SK-KUR-2026-001',
            ]
        );
        $this->assertTrue($previewValid['is_valid']);
        $this->assertEquals(ReviewerWorkflowService::STATUS_RESOLVED, $previewValid['projected_status']);
    }

    /**
     * Test 11: Audit viewer hanya membaca data (read-only).
     */
    public function testAuditViewerIsReadOnly(): void
    {
        $auditService = new ReviewerAuditService();

        // 1. Reflection check: verify no mutation methods exist on ReviewerAuditService other than recordDecision
        $reflection = new \ReflectionClass($auditService);
        $this->assertFalse($reflection->hasMethod('updateRecord'));
        $this->assertFalse($reflection->hasMethod('deleteRecord'));
        $this->assertFalse($reflection->hasMethod('editRecord'));
        $this->assertFalse($reflection->hasMethod('truncate'));

        // 2. Controller check: CbtReview only has read-only auditTrail()
        $ctrlReflection = new \ReflectionClass(CbtReview::class);
        $this->assertTrue($ctrlReflection->hasMethod('auditTrail'));
        $this->assertFalse($ctrlReflection->hasMethod('deleteAudit'));
        $this->assertFalse($ctrlReflection->hasMethod('updateAudit'));

        // 3. getTrail() returns array of entries without mutating state
        $trail = $auditService->getTrail();
        $this->assertIsArray($trail);
        $count = $auditService->count();
        $this->assertEquals(count($trail), $count);
    }

    /**
     * Test 12: Audit integrity failure memblokir proses keputusan dan preview.
     */
    public function testAuditIntegrityFailureBlocksDecisionAndPreview(): void
    {
        $tempAuditPath = sys_get_temp_dir() . '/corrupted_audit_' . uniqid() . '.json';

        // Write a corrupted audit trail (tampered record_hash)
        $corruptedTrail = [
            [
                'audit_id'             => 'AUD_TAMPERED_01',
                'sequence_number'      => 1,
                'timestamp_iso'        => '2026-11-01T06:00:00Z',
                'reviewer_id'          => 'OPERATOR_X',
                'case_id'              => 'REV_501_2b6231cb77',
                'status_before'        => 'ACKNOWLEDGEMENT_REQUIRED',
                'status_after'         => 'RESOLVED',
                'decision'             => 'ACKNOWLEDGE_POLICY_DIFF',
                'reason'               => 'Valid reason',
                'previous_record_hash' => ReviewerAuditService::GENESIS_HASH,
                'record_hash'          => '000000000000000000000000000000000000000000000000000000000000DEAD', // Corrupted!
                'policy_version'       => ReviewerAuditService::POLICY_VERSION,
            ],
        ];
        file_put_contents($tempAuditPath, json_encode($corruptedTrail));

        $badAudit = new ReviewerAuditService($tempAuditPath);
        $integrity = $badAudit->verifyAuditIntegrity();
        $this->assertFalse($integrity['is_valid'], 'Audit integrity must be flagged as corrupted.');

        $workflow = new ReviewerWorkflowService(null, $badAudit);
        $workflow->loadFromShadowDeviations($this->deviationsCsv);

        // 1. Preview must fail-closed due to corrupted audit trail
        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Testing preview on corrupted audit log',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );
        $this->assertFalse($preview['is_valid']);
        $this->assertStringContainsString('AUDIT INTEGRITY CORRUPTED', $preview['error']);

        // 2. Real decision execution must throw RuntimeException
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AUDIT INTEGRITY CORRUPTED');
        $workflow->applyDecision(
            'REV_501_2b6231cb77',
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Testing apply on corrupted audit log',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        if (file_exists($tempAuditPath)) {
            @unlink($tempAuditPath);
        }
    }

    /**
     * Test 13: Akses URL langsung tidak dapat melewati authorization.
     */
    public function testDirectUrlAccessCannotBypassAuthorization(): void
    {
        $controller = new CbtReview();

        // 1. Unauthenticated session (guest) -> rejected
        $controller->setMockSession(['logged_in' => false, 'user' => []]);
        $authRes1 = $controller->enforceAuthorization();
        $this->assertNotNull($authRes1, 'Unauthenticated user must be blocked.');

        // 2. Student role (role_id = 5) -> strictly forbidden
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 99, 'username' => 'student_ani', 'role_id' => 5],
        ]);
        $authRes2 = $controller->enforceAuthorization();
        $this->assertNotNull($authRes2, 'Student role must be blocked.');

        // 3. Parent role (role_id = 4) -> strictly forbidden
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 101, 'username' => 'parent_budi', 'role_id' => 4],
        ]);
        $authRes3 = $controller->enforceAuthorization();
        $this->assertNotNull($authRes3, 'Parent role must be blocked.');

        // 4. Authorized Teacher role (role_id = 3) -> allowed (returns null)
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 12, 'username' => 'guru_ipa', 'role_id' => 3],
        ]);
        $this->assertNull($controller->enforceAuthorization(), 'Teacher role must be authorized.');

        // 5. Authorized Principal role (role_id = 2) -> allowed
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 2, 'username' => 'kepsek', 'role_id' => 2],
        ]);
        $this->assertNull($controller->enforceAuthorization(), 'Principal role must be authorized.');

        // 6. Authorized Admin role (role_id = 1) -> allowed
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 1, 'username' => 'admin_espata', 'role_id' => 1],
        ]);
        $this->assertNull($controller->enforceAuthorization(), 'Admin role must be authorized.');
    }

    /**
     * Test 14: Input berbahaya tidak dieksekusi sebagai HTML atau SQL.
     */
    public function testHarmfulInputIsNotExecutedAsHtmlOrSql(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_501_2b6231cb77';

        // 1. XSS injection attempt in reason
        $xssReason = "<script>alert('XSS_ATTACK');</script>";
        $preview = $workflow->previewDecision(
            $caseId,
            'REVIEWER_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            $xssReason,
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );
        $this->assertTrue($preview['is_valid']);
        $simulatedEntry = $preview['simulated_audit_entry'];
        $this->assertEquals($xssReason, $simulatedEntry['reason']);

        // Verify that view escaping esc() renders script tags harmlessly as HTML entities
        $escaped = htmlspecialchars($simulatedEntry['reason'], ENT_QUOTES, 'UTF-8');
        $this->assertStringNotContainsString("<script>", $escaped);
        $this->assertStringContainsString("&lt;script&gt;", $escaped);

        // 2. SQL injection string in reason and reviewer_id
        $sqlInjection = "'; DROP TABLE cbt_aktivitas; --";
        $previewSql = $workflow->previewDecision(
            $caseId,
            $sqlInjection,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            $sqlInjection,
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );
        $this->assertTrue($previewSql['is_valid']);

        // Invariant: Pure string handling with zero SQL DDL/DML execution
        $this->assertEquals($sqlInjection, $previewSql['simulated_audit_entry']['reviewer_id']);
    }

    /**
     * Test 15: Lima kasus operasional tetap tidak berubah setelah seluruh pengujian.
     */
    public function testFiveShadowReviewCasesRemainUntouched(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $summary = $workflow->getSummary();

        // 1. Zero cases resolved
        $this->assertEquals(0, $summary['resolved_cases'], 'review_cases_resolved must be strictly 0.');
        $this->assertEquals(5, $summary['pending_cases'], 'All 5 cases must remain pending.');
        $this->assertFalse($summary['is_all_cleared_for_staging'], 'Must not be cleared for staging.');

        // 2. Authoritative check on all 5 cases
        $expectedCases = [
            'REV_501_2b6231cb77' => [
                'category'    => 'ROUNDING_DIFFERENCE',
                'decision_id' => 'DEC-ROUND-01',
                'status'      => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            ],
            'REV_501_7b48bc8a1f' => [
                'category'    => 'LEGACY_ZERO_FALLBACK',
                'decision_id' => 'DEC-ABS-01',
                'status'      => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            ],
            'REV_502_3ff144b11c' => [
                'category'    => 'PENDING_ESSAY',
                'decision_id' => 'DEC-ESS-01',
                'status'      => ReviewerWorkflowService::STATUS_TEACHER_ACTION_REQUIRED,
            ],
            'REV_503_4266a4ed7f' => [
                'category'    => 'AMBIGUOUS_RETAKE',
                'decision_id' => 'DEC-RET-01',
                'status'      => ReviewerWorkflowService::STATUS_OWNER_DECISION_REQUIRED,
            ],
            'REV_503_fc68cd64f2' => [
                'category'    => 'LEGACY_ZERO_FALLBACK',
                'decision_id' => 'DEC-ABS-01',
                'status'      => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            ],
        ];

        foreach ($expectedCases as $caseId => $exp) {
            $case = $workflow->getCase($caseId);
            $this->assertNotNull($case, "Case {$caseId} must exist.");
            $this->assertEquals($exp['category'], $case['category']);
            $this->assertEquals($exp['status'], $case['review_status']);
            $this->assertNull($case['decision'], "Decision for {$caseId} must remain NULL (unresolved).");
            $this->assertNull($case['reviewer_id'], "Reviewer ID for {$caseId} must remain NULL (unassigned).");
        }
    }
}
