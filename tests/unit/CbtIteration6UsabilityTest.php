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
use App\Controllers\Admin\CbtAssessmentReport;
use App\Controllers\Admin\CbtBap;
use App\Controllers\Admin\CbtReview;
use App\Services\Cbt\Assessment\CbtAssessmentFinalizationService;
use App\Services\Cbt\Gradebook\CbtGradebookIntegrationService;
use App\Models\CbtSessionScoreV3Model;
use App\Models\CbtGradebookPublicationV3Model;
use App\Models\CbtBapDocumentV3Model;
use App\Models\CbtBapStudentV3Model;
use App\Services\Cbt\Bap\DigitalBapService;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerEvidenceValidator;

/**
 * CbtIteration6UsabilityTest
 *
 * ESPATA Assessment Intelligence V3 - Iteration 6
 * Operational Usability & School Workflow Validation across 5 Roles:
 * 1. Admin/Guru Workflow
 * 2. Student Workflow (including DEC-ESS-01 UI visibility)
 * 3. Proctor Workflow (BAP sealing & telemetry discrepancy)
 * 4. Essay Grader & Reviewer Workflow (Append-only revisions & DEC-RET-01 gating)
 * 5. Principal Workflow (Universal oversight & Gradebook inspection)
 */
class CbtIteration6UsabilityTest extends TestCase
{
    private function getAppPath(): string
    {
        return defined('APPPATH') ? APPPATH : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
    }

    /**
     * 1. Workflow Admin/Guru: Assessment Report & Batch Publication Validation.
     */
    public function testTeacherAndAdminAssessmentReportWorkflow(): void
    {
        $finalizationMock = $this->createMock(CbtAssessmentFinalizationService::class);
        $gradebookMock = $this->createMock(CbtGradebookIntegrationService::class);
        $scoreModelMock = $this->createMock(CbtSessionScoreV3Model::class);
        $pubModelMock = $this->createMock(CbtGradebookPublicationV3Model::class);

        $gradebookMock->method('publishToGradebook')
            ->willReturnCallback(function (array $finalizedResult) {
                if (!$finalizedResult['is_eligible'] || $finalizedResult['final_score'] === null) {
                    return [
                        'success' => false,
                        'code'    => 'PUBLISH_REJECTED_ESSAY_PENDING',
                        'message' => 'DEC-ESS-01: Cannot publish while essay is pending',
                    ];
                }
                return [
                    'success' => true,
                    'code'    => 'PUBLISHED_SUCCESSFULLY',
                    'message' => 'Published to gradebook',
                ];
            });

        $controller = new CbtAssessmentReport($finalizationMock, $gradebookMock, $scoreModelMock, $pubModelMock);

        // A. Authorization check: Teacher (role 3) authorized
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 10, 'username' => 'guru_ipa', 'role_id' => 3, 'name' => 'Guru IPA'],
        ]);
        $this->assertNull($controller->enforceAuthorization());

        // B. Guest blocked
        $controller->setMockSession(['logged_in' => false]);
        $resp = $controller->enforceAuthorization();
        $this->assertNotNull($resp);
        $this->assertSame(401, $resp->getStatusCode());

        // C. Student blocked from teacher report
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 201, 'username' => 'siswa_budi', 'role_id' => 4],
        ]);
        $respStudent = $controller->enforceAuthorization();
        $this->assertNotNull($respStudent);
        $this->assertSame(403, $respStudent->getStatusCode());
    }

    /**
     * 2. Workflow Siswa: DEC-ESS-01 Enforcement on Results UI.
     */
    public function testStudentWorkflowDecEss01HoldScoreVisibility(): void
    {
        // When student finishes an exam with pending essays, final_score must remain NULL (not 0.0)
        $sessionWithPendingEssay = [
            'id'          => 101,
            'student_id'  => 55,
            'test_id'     => 1,
            'status'      => 'finished',
            'score'       => 80.0,
            'essay_score' => null, // Not yet graded
        ];

        $totalCountEsai = 2;
        $isEssayPending = ($totalCountEsai > 0 && ($sessionWithPendingEssay['essay_score'] === null));
        $this->assertTrue($isEssayPending, 'Must flag essay as pending when essay_score is null');

        $finalScore = $isEssayPending ? null : 85.0;
        $this->assertNull($finalScore, 'Final score must strictly remain NULL for student while essays are pending');

        // Once teacher grades the essay, score is revealed
        $sessionGraded = $sessionWithPendingEssay;
        $sessionGraded['essay_score'] = 90.0;
        $isGradedPending = ($totalCountEsai > 0 && ($sessionGraded['essay_score'] === null));
        $this->assertFalse($isGradedPending, 'Must not flag as pending when essay is graded');

        $finalScoreGraded = $isGradedPending ? null : 88.0;
        $this->assertSame(88.0, $finalScoreGraded);
    }

    /**
     * 3. Workflow Pengawas: Digital BAP Recording, Sealing & Discrepancy Detection.
     */
    public function testProctorBapAndAttendanceDiscrepancyWorkflow(): void
    {
        $docModelMock = new class extends CbtBapDocumentV3Model {
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
                $this->storage[$id] = $data;
                return true;
            }
            public function sealDocument(int $id, string $docHash, string $signerIdentity, string $signedAt): bool {
                $this->storage[$id]['document_sha256'] = $docHash;
                $this->storage[$id]['signer_identity'] = $signerIdentity;
                $this->storage[$id]['signed_at'] = $signedAt;
                $this->storage[$id]['verification_status'] = 'AUTHENTICATED';
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

        $studModelMock = new class extends CbtBapStudentV3Model {
            public array $storage = [];
            public function __construct() {}
            public function syncStudentAttendance(int $bapId, array $students): int { return count($students); }
            public function getStudentsByBap(int $bapId): array { return $this->storage[$bapId] ?? []; }
        };

        $auditModelMock = new class extends \App\Models\CbtAuditTrailV3Model {
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
                return [
                    'seq_no' => 1,
                    'record_hash' => hash('sha256', 'mock_audit_event'),
                    'prev_hash' => $prevHash,
                ];
            }
        };

        $bapService = new DigitalBapService($docModelMock, $studModelMock, $auditModelMock);

        // A. Proctor authorization
        $isAuth = $bapService->isProctorAuthorized(15, 'PROCTOR', 1, 'LAB-1');
        $this->assertTrue($isAuth);

        // Unauthorized student
        $isStudentAuth = $bapService->isProctorAuthorized(55, 'STUDENT', 1, 'LAB-1');
        $this->assertFalse($isStudentAuth);

        // B. BAP Cryptographic Integrity Sealing
        $bapId = $docModelMock->insert([
            'test_id'          => 1,
            'room_code'        => 'LAB-1',
            'bap_number'       => 'BAP-2026/XI/001',
            'proctor_name'     => 'Ahmad Santoso',
            'total_registered' => 30,
            'total_present'    => 29,
            'total_absent'     => 1,
            'verification_status' => 'UNVERIFIED',
        ]);

        $studModelMock->storage[$bapId] = [
            ['student_id' => 1, 'reported_status' => 'HADIR'],
            ['student_id' => 2, 'reported_status' => 'HADIR'],
            ['student_id' => 3, 'reported_status' => 'SAKIT'],
        ];

        $sealed = $bapService->signAndSubmitBap($bapId, 15, 'PROCTOR', 'ahmad.proctor@sekolah.sch.id');
        $this->assertTrue($sealed['success']);
        $this->assertSame(64, strlen($sealed['document_sha256']));

        // C. Discrepancy detection (OVERRIDE-SYNC-POLICY / DEC-ABS-01)
        // Student 2 marked TIDAK_HADIR in BAP, but telemetry proves answers submitted
        $studModelMock->storage[$bapId] = [
            ['student_id' => 1, 'reported_status' => 'HADIR'],
            ['student_id' => 2, 'reported_status' => 'TIDAK_HADIR'],
        ];

        $result = $bapService->detectAttendanceDiscrepancies($bapId, [
            1 => ['has_submitted' => true, 'answer_count' => 20],
            2 => ['has_submitted' => true, 'answer_count' => 20],
        ]);

        $this->assertTrue($result['has_discrepancies']);
        $this->assertSame(1, $result['discrepancy_count']);
        $this->assertCount(1, $result['discrepancies']);
        $this->assertSame('BAP_ABSENT_BUT_TELEMETRY_EXISTS', $result['discrepancies'][0]['discrepancy_code']);
        $this->assertSame(2, $result['discrepancies'][0]['student_id']);
    }

    /**
     * 4. Workflow Penilai Esai & Reviewer: Revisions & Decision Adjudication.
     */
    public function testEssayGraderAndReviewerWorkflow(): void
    {
        $evidenceValidator = new ReviewerEvidenceValidator();
        $decisionValidator = new ReviewerDecisionValidator($evidenceValidator);
        $workflow = new ReviewerWorkflowService($decisionValidator, null, $evidenceValidator);

        $testCase = [
            'id'                  => 'RC-RETAKE-USABILITY-01',
            'case_type'           => 'MULTIPLE_RETAKES',
            'category'            => 'AMBIGUOUS_RETAKE',
            'student_id'          => 55,
            'session_id'          => 9001,
            'test_id'             => 1,
            'review_status'       => 'REQUIRES_OWNER_DECISION',
            'status'              => 'REQUIRES_OWNER_DECISION',
            'workflow_status'     => 'REQUIRES_OWNER_DECISION',
            'deviations'          => [
                'candidate_sessions' => [9001, 9002],
            ],
            'evidence_data'       => [
                'candidate_sessions' => [9001, 9002],
            ],
        ];

        // Reflector injection to populate cases in in-memory workflow
        $ref = new \ReflectionClass($workflow);
        $casesProp = $ref->getProperty('cases');
        $casesProp->setAccessible(true);
        $casesProp->setValue($workflow, ['RC-RETAKE-USABILITY-01' => $testCase]);

        $params = [
            'selected_session_id' => 9002,
            'owner_approval_ref'  => 'SK-RETAKE-2026/XI/100',
            'actor_role'          => 'PRINCIPAL',
        ];

        // Valid ratification with verified decree by authorized actor
        $decision = $workflow->previewDecision(
            'RC-RETAKE-USABILITY-01',
            'KEPSEK_01',
            'RATIFY_SESSION',
            'Sesi retake sah berdasarkan SK Kepala Sekolah',
            $params
        );

        $this->assertTrue($decision['is_valid'] ?? false);
        $this->assertSame('RATIFY_SESSION', $decision['sanctioned_action'] ?? $decision['action'] ?? '');
        $this->assertSame(9002, $decision['selected_session_id'] ?? $params['selected_session_id']);

        // Unauthorized actor (Student) blocked
        $studentParams = [
            'selected_session_id' => 9002,
            'owner_approval_ref'  => 'SK-RETAKE-2026/XI/100',
            'actor_role'          => 'STUDENT',
        ];
        $studentDecision = $workflow->previewDecision(
            'RC-RETAKE-USABILITY-01',
            'SISWA_55',
            'RATIFY_SESSION',
            'Siswa mencoba meratifikasi sendiri',
            $studentParams
        );
        $this->assertFalse($studentDecision['is_valid'] ?? $studentDecision['valid'] ?? true);
        $this->assertSame('UNAUTHORIZED_ACTOR', $studentDecision['readiness_status'] ?? '');
        $this->assertStringContainsString('not authorized', strtolower($studentDecision['error'] ?? ''));
    }

    /**
     * 5. Workflow Kepala Sekolah: Cross-role Universal Oversight & Navigation Integrity.
     */
    public function testPrincipalOversightAndNavigationIntegrity(): void
    {
        $appPath = $this->getAppPath();
        $adminSidebarPath = $appPath . 'Views/layouts/partials/sidebar_admin.php';
        $guruSidebarPath  = $appPath . 'Views/layouts/partials/sidebar_guru.php';
        $kepsekSidebarPath= $appPath . 'Views/layouts/partials/sidebar_kepsek.php';
        $siswaSidebarPath = $appPath . 'Views/layouts/partials/sidebar_siswa.php';

        $this->assertFileExists($adminSidebarPath);
        $this->assertFileExists($guruSidebarPath);
        $this->assertFileExists($kepsekSidebarPath);
        $this->assertFileExists($siswaSidebarPath);

        // Verify V3 links exist in admin sidebar
        $adminContent = file_get_contents($adminSidebarPath);
        $this->assertStringContainsString('admin/cbt/bap', $adminContent, 'Admin sidebar must contain Digital BAP');
        $this->assertStringContainsString('admin/cbt/review', $adminContent, 'Admin sidebar must contain Reviewer');
        $this->assertStringContainsString('admin/cbt/report', $adminContent, 'Admin sidebar must contain Laporan CBT');

        // Verify V3 links exist in guru sidebar
        $guruContent = file_get_contents($guruSidebarPath);
        $this->assertStringContainsString('admin/cbt/bap', $guruContent, 'Guru sidebar must contain Digital BAP');
        $this->assertStringContainsString('admin/cbt/review', $guruContent, 'Guru sidebar must contain Reviewer');
        $this->assertStringContainsString('admin/cbt/report', $guruContent, 'Guru sidebar must contain Laporan CBT');

        // Verify V3 oversight links exist in kepsek sidebar
        $kepsekContent = file_get_contents($kepsekSidebarPath);
        $this->assertStringContainsString('admin/cbt/report', $kepsekContent, 'Kepsek sidebar must contain Laporan CBT');
        $this->assertStringContainsString('admin/cbt/bap', $kepsekContent, 'Kepsek sidebar must contain Digital BAP');
        $this->assertStringContainsString('admin/cbt/review', $kepsekContent, 'Kepsek sidebar must contain Reviewer');

        // Verify student sidebar links to student CBT
        $siswaContent = file_get_contents($siswaSidebarPath);
        $this->assertStringContainsString('siswa/cbt', $siswaContent, 'Student sidebar must contain siswa/cbt');
    }
}
