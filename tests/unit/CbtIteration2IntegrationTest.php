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

use App\Models\CbtBapDocumentV3Model;
use App\Models\CbtBapStudentV3Model;
use App\Models\CbtAuditTrailV3Model;
use App\Services\Cbt\Bap\DigitalBapService;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\InvalidReviewActionException;
use PHPUnit\Framework\TestCase;

/**
 * CbtIteration2IntegrationTest
 *
 * ESPATA CBT V3.32 — Iteration 2 Integration & Security Test Suite
 * Covers:
 * 1. Prioritas A: Reviewer/Proctor Dashboard Anomaly Handling
 *    - RETAKE_ANOMALY: Multi-session candidate timeline, explicit selection under DEC-RET-01.
 *    - Strict rejection of retake resolution without explicit selected_session_id.
 *    - Strict rejection of retake resolution without verified owner_approval_ref.
 *    - Strict rejection of unauthorized actors (e.g. STUDENT, GUEST).
 *    - ATTENDANCE_DISCREPANCY: Side-by-side evidence inspection (Proctor BAP vs Server Telemetry).
 *    - DEC-ABS-01 compliance: Preserves NULL score; rejects converting absence to earned zero.
 *    - OVERRIDE-SYNC-POLICY compliance: Preserves both sources non-destructively; supports CONFIRM_ABSENCE & RATIFY_SESSION.
 * 2. Prioritas B: Digital BAP (Berita Acara Pelaksanaan Ujian)
 *    - Draft creation, update, and room authorization boundaries.
 *    - Proctor assignment enforcement: Proctor restricted to assigned room; Admin/Principal universal.
 *    - Deterministic canonical payload generation & cryptographic SHA-256 seal.
 *    - Tamper-evident append-only audit trail logging (BAP_AUTHENTICATED).
 *    - Immutability guard: Authenticated BAP documents are locked against further mutation.
 *    - Telemetry cross-referencing and discrepancy detection.
 */
class CbtIteration2IntegrationTest extends TestCase
{
    private DigitalBapService $bapService;
    private ReviewerWorkflowService $reviewWorkflow;
    private $bapDocStub;
    private $bapStudentStub;
    private $auditStub;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. In-memory BAP Document Model Stub
        $this->bapDocStub = new class extends CbtBapDocumentV3Model {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}

            public function find($id = null)
            {
                return $this->storage[$id] ?? null;
            }

            public function insert($data = null, bool $returnID = true)
            {
                $id = $this->autoInc++;
                $data['id'] = $id;
                $this->storage[$id] = $data;
                return $id;
            }

            public function update($id = null, $data = null): bool
            {
                if (isset($this->storage[$id])) {
                    $this->storage[$id] = array_merge($this->storage[$id], $data);
                    return true;
                }
                return false;
            }

            public function getByTestAndRoom(int $testId, string $roomCode): ?array
            {
                foreach ($this->storage as $doc) {
                    if ((int)$doc['test_id'] === $testId && $doc['room_code'] === $roomCode) {
                        return $doc;
                    }
                }
                return null;
            }

            public function findByDocHash(string $hash): ?array
            {
                foreach ($this->storage as $doc) {
                    if ($doc['document_sha256'] === $hash) {
                        return $doc;
                    }
                }
                return null;
            }
        };

        // 2. In-memory BAP Student Model Stub
        $this->bapStudentStub = new class extends CbtBapStudentV3Model {
            public array $storage = [];
            private int $autoInc = 1;
            public function __construct() {}

            public function getStudentsByBap(int $bapId): array
            {
                $res = [];
                foreach ($this->storage as $st) {
                    if ((int)$st['bap_id'] === $bapId) {
                        $res[] = $st;
                    }
                }
                return $res;
            }

            public function upsertStudentStatus(int $bapId, int $studentId, string $status, ?string $notes = null): int
            {
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

        // 3. In-memory Audit Trail Stub
        $this->auditStub = new class extends CbtAuditTrailV3Model {
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
                $prevHash = !empty($this->storage) ? end($this->storage)['record_hash'] : self::GENESIS_HASH;

                ksort($payload);
                $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $material = "{$this->seq}|{$eventType}|{$entityTable}|{$entityId}|{$actorId}|{$actorRole}|{$canonical}|{$prevHash}";
                $recordHash = hash_hmac('sha256', $material, $secretKey);

                $rec = [
                    'id'                => $this->seq,
                    'seq_no'            => $this->seq,
                    'event_type'        => $eventType,
                    'entity_table'      => $entityTable,
                    'entity_id'         => $entityId,
                    'actor_id'          => $actorId,
                    'actor_role'        => $actorRole,
                    'payload_canonical' => $canonical,
                    'key_id'            => $keyId,
                    'prev_hash'         => $prevHash,
                    'record_hash'       => $recordHash,
                    'created_at'        => date('Y-m-d H:i:s'),
                ];
                $this->storage[$this->seq] = $rec;
                return $rec;
            }

            public function verifyChainIntegrity(): array
            {
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

        // Initialize Services
        $this->bapService = new DigitalBapService(
            $this->bapDocStub,
            $this->bapStudentStub,
            $this->auditStub
        );

        $this->reviewWorkflow = new ReviewerWorkflowService();
    }

    // =========================================================================
    // SECTION 1: DIGITAL BAP LIFECYCLE & AUTHORIZATION
    // =========================================================================

    public function testProctorAuthorizationBoundaries(): void
    {
        $this->bapService->setMockData([
            'proctor_assignments' => [
                ['proctor_id' => 101, 'test_id' => 501, 'room_code' => 'LAB-01'],
                ['proctor_id' => 102, 'test_id' => 501, 'room_code' => 'LAB-02'],
            ],
        ]);

        // Proctor 101 is authorized for LAB-01
        $this->assertTrue($this->bapService->isProctorAuthorized(101, 'PROCTOR', 501, 'LAB-01'));

        // Proctor 101 is NOT authorized for LAB-02
        $this->assertFalse($this->bapService->isProctorAuthorized(101, 'PROCTOR', 501, 'LAB-02'));

        // Admin has universal authority across all rooms
        $this->assertTrue($this->bapService->isProctorAuthorized(1, 'ADMIN', 501, 'LAB-02'));

        // Principal has universal authority
        $this->assertTrue($this->bapService->isProctorAuthorized(2, 'PRINCIPAL', 501, 'LAB-02'));

        // Student is strictly unauthorized
        $this->assertFalse($this->bapService->isProctorAuthorized(999, 'STUDENT', 501, 'LAB-01'));
    }

    public function testGetOrCreateDraftInitializesUnverifiedBap(): void
    {
        $registeredStudents = [
            ['student_id' => 1001, 'name' => 'Ahmad'],
            ['student_id' => 1002, 'name' => 'Budi'],
            ['student_id' => 1003, 'name' => 'Citra'],
        ];

        $draft = $this->bapService->getOrCreateDraft(
            501,
            'LAB-01',
            101,
            'Pak Guru Pengawas',
            'Ibu Supervisor',
            $registeredStudents
        );

        $this->assertTrue($draft['is_new']);
        $this->assertNotNull($draft['document']['id']);
        $this->assertSame('UNVERIFIED', $draft['document']['verification_status']);
        $this->assertSame('DRAFT', $draft['document']['signer_identity']);
        $this->assertStringStartsWith('BAP/501/LAB-01/', $draft['document']['bap_number']);
        $this->assertCount(3, $draft['students']);

        // Idempotent: retrieving again returns existing document
        $secondFetch = $this->bapService->getOrCreateDraft(
            501,
            'LAB-01',
            101,
            'Pak Guru Pengawas'
        );
        $this->assertFalse($secondFetch['is_new']);
        $this->assertSame($draft['document']['id'], $secondFetch['document']['id']);
    }

    public function testSaveDraftUpdatesAttendanceCountsAndProbableStatus(): void
    {
        $registeredStudents = [1001, 1002, 1003, 1004];
        $draft = $this->bapService->getOrCreateDraft(501, 'LAB-01', 101, 'Pengawas', '', $registeredStudents);
        $bapId = (int)$draft['document']['id'];

        // Save attendance: 2 Hadir, 1 Sakit, 1 Tidak Hadir
        $attendance = [
            ['student_id' => 1001, 'reported_status' => 'HADIR', 'proctor_notes' => 'Tepat waktu'],
            ['student_id' => 1002, 'reported_status' => 'HADIR', 'proctor_notes' => null],
            ['student_id' => 1003, 'reported_status' => 'SAKIT', 'proctor_notes' => 'Surat dokter terlampir'],
            ['student_id' => 1004, 'reported_status' => 'TIDAK_HADIR', 'proctor_notes' => 'Tanpa keterangan'],
        ];

        $updated = $this->bapService->saveDraft($bapId, $attendance, 'Ibu Supervisor');

        $this->assertSame(4, $updated['total_registered']);
        $this->assertSame(2, $updated['total_present']);
        $this->assertSame(2, $updated['total_absent']);
        $this->assertSame('PROBABLE', $updated['verification_status']);
    }

    public function testSignAndSubmitBapComputesSha256AndLogsAudit(): void
    {
        $registeredStudents = [1001, 1002];
        $draft = $this->bapService->getOrCreateDraft(501, 'LAB-01', 101, 'Pengawas', '', $registeredStudents);
        $bapId = (int)$draft['document']['id'];

        $attendance = [
            ['student_id' => 1001, 'reported_status' => 'HADIR', 'proctor_notes' => 'Hadir'],
            ['student_id' => 1002, 'reported_status' => 'TIDAK_HADIR', 'proctor_notes' => 'Absen'],
        ];
        $this->bapService->saveDraft($bapId, $attendance);

        // Sign and submit BAP
        $result = $this->bapService->signAndSubmitBap($bapId, 101, 'Drs. Supriyadi (Pengawas)', 'PROCTOR', 'Ujian lancar tanpa insiden.');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['document_sha256']);
        $this->assertSame(64, strlen($result['document_sha256']));
        $this->assertSame('Drs. Supriyadi (Pengawas)', $result['signer_identity']);

        // Check document stored in DB
        $doc = $this->bapDocStub->find($bapId);
        $this->assertSame('AUTHENTICATED', $doc['verification_status']);
        $this->assertSame($result['document_sha256'], $doc['document_sha256']);

        // Verify tamper-evident audit record was logged
        $this->assertCount(1, $this->auditStub->storage);
        $auditRec = reset($this->auditStub->storage);
        $this->assertSame('BAP_AUTHENTICATED', $auditRec['event_type']);
        $this->assertSame((string)$bapId, $auditRec['entity_id']);
        $this->assertSame(101, $auditRec['actor_id']);

        // Verify audit chain
        $chainIntegrity = $this->auditStub->verifyChainIntegrity();
        $this->assertTrue($chainIntegrity['valid']);
    }

    public function testAuthenticatedBapIsImmutableAgainstMutation(): void
    {
        $registeredStudents = [1001];
        $draft = $this->bapService->getOrCreateDraft(501, 'LAB-01', 101, 'Pengawas', '', $registeredStudents);
        $bapId = (int)$draft['document']['id'];

        $this->bapService->saveDraft($bapId, [['student_id' => 1001, 'reported_status' => 'HADIR']]);
        $this->bapService->signAndSubmitBap($bapId, 101, 'Pengawas', 'PROCTOR');

        // Attempting to mutate authenticated BAP must throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IMMUTABLE DOCUMENT');

        $this->bapService->saveDraft($bapId, [['student_id' => 1001, 'reported_status' => 'TIDAK_HADIR']]);
    }

    // =========================================================================
    // SECTION 2: ATTENDANCE DISCREPANCY DETECTION & RECONCILIATION
    // =========================================================================

    public function testDetectAttendanceDiscrepanciesFlagsContradictions(): void
    {
        $draft = $this->bapService->getOrCreateDraft(501, 'LAB-01', 101, 'Pengawas', '', [2001, 2002, 2003]);
        $bapId = (int)$draft['document']['id'];

        // BAP: Student 2001 reported TIDAK_HADIR, Student 2002 reported HADIR, Student 2003 reported HADIR
        $this->bapService->saveDraft($bapId, [
            ['student_id' => 2001, 'reported_status' => 'TIDAK_HADIR', 'proctor_notes' => 'Kursi kosong'],
            ['student_id' => 2002, 'reported_status' => 'HADIR', 'proctor_notes' => null],
            ['student_id' => 2003, 'reported_status' => 'HADIR', 'proctor_notes' => null],
        ]);
        $this->bapService->signAndSubmitBap($bapId, 101, 'Pengawas', 'PROCTOR');

        // Injected Telemetry Fixtures:
        // - Student 2001 has active CBT session and 15 answers recorded! (CONTRADICTION: absent in BAP but active in telemetry)
        // - Student 2002 has active session and 20 answers. (MATCH)
        // - Student 2003 has NO telemetry! (CONTRADICTION: marked present in BAP but 0 answers)
        $telemetryFixtures = [
            2001 => ['session_id' => 8801, 'status' => 'FINISHED', 'answer_count' => 15, 'score' => 75.0, 'ip_address' => '192.168.1.101'],
            2002 => ['session_id' => 8802, 'status' => 'FINISHED', 'answer_count' => 20, 'score' => 85.0, 'ip_address' => '192.168.1.102'],
            // 2003 has null telemetry
        ];

        $analysis = $this->bapService->detectAttendanceDiscrepancies($bapId, $telemetryFixtures);

        $this->assertTrue($analysis['has_discrepancies']);
        $this->assertSame(2, $analysis['discrepancy_count']);
        $this->assertSame(1, $analysis['matched_count']);

        // Check Discrepancy 1: Student 2001 (BAP Absent, Telemetry Exists)
        $disc1 = $analysis['discrepancies'][0];
        $this->assertSame(2001, $disc1['student_id']);
        $this->assertSame('BAP_ABSENT_BUT_TELEMETRY_EXISTS', $disc1['discrepancy_code']);
        $this->assertSame('TIDAK_HADIR', $disc1['bap_evidence']['reported_status']);
        $this->assertSame(15, $disc1['telemetry_evidence']['answer_count']);
        $this->assertStringContainsString('OVERRIDE-SYNC-POLICY', $disc1['governance_note']);

        // Check Discrepancy 2: Student 2003 (BAP Present, No Telemetry)
        $disc2 = $analysis['discrepancies'][1];
        $this->assertSame(2003, $disc2['student_id']);
        $this->assertSame('BAP_PRESENT_BUT_NO_TELEMETRY', $disc2['discrepancy_code']);
    }

    public function testAttendanceDiscrepancyIngestionIntoReviewWorkflow(): void
    {
        $discrepancyFixture = [
            'student_id'        => 2001,
            'anomaly_type'      => 'ATTENDANCE_DISCREPANCY',
            'discrepancy_code'  => 'BAP_ABSENT_BUT_TELEMETRY_EXISTS',
            'bap_evidence'      => [
                'bap_id'          => 1,
                'bap_number'      => 'BAP/501/LAB-01/20261102',
                'room_code'       => 'LAB-01',
                'proctor_name'    => 'Pengawas Ruang',
                'reported_status' => 'TIDAK_HADIR',
                'proctor_notes'   => 'Tidak terlihat di ruang',
                'document_sha256' => hash('sha256', 'VALID_BAP_DOC'),
                'signed_at'       => date('Y-m-d H:i:s'),
            ],
            'telemetry_evidence' => [
                'session_id'   => 8801,
                'status'       => 'FINISHED',
                'answer_count' => 20,
                'score'        => 82.5,
                'ip_address'   => '192.168.1.55',
            ],
        ];

        $caseId = $this->reviewWorkflow->ingestDiscrepancy($discrepancyFixture, 'Matematika');
        $this->assertNotEmpty($caseId);

        $case = $this->reviewWorkflow->getCase($caseId);
        $this->assertNotNull($case);
        $this->assertSame('ATTENDANCE_DISCREPANCY', $case['category']);
        $this->assertSame('REVIEWER_ACTION_REQUIRED', $case['review_status']);

        // Inspect case details: maps to OVERRIDE-SYNC-POLICY
        $detail = $this->reviewWorkflow->getCaseDetail($caseId);
        $this->assertSame('OVERRIDE-SYNC-POLICY', $detail['applicable_decision_id']);
        $this->assertContains('CONFIRM_ABSENCE', $detail['allowed_actions']);
        $this->assertContains('RATIFY_SESSION', $detail['allowed_actions']);
        $this->assertContains('QUARANTINE_DATA', $detail['allowed_actions']);

        // Preview resolution: CONFIRM_ABSENCE
        $previewAbsence = $this->reviewWorkflow->previewDecision(
            $caseId,
            'reviewer_lead',
            'CONFIRM_ABSENCE',
            'BAP fisik pengawas mengonfirmasi siswa tidak berada di ruang ujian.',
            ['actor_role' => 'ADMIN', 'decision_id' => 'DEC-ABS-01']
        );
        $this->assertTrue($previewAbsence['is_valid']);
        $this->assertSame('RESOLVED', $previewAbsence['projected_status']);

        // Rejection of invalid action: Converting absence to earned zero is strictly forbidden under DEC-ABS-01
        $previewIllegal = $this->reviewWorkflow->previewDecision(
            $caseId,
            'reviewer_lead',
            'CONVERT_TO_EARNED_ZERO',
            'Ubah nilai jadi 0',
            ['actor_role' => 'ADMIN']
        );
        $this->assertFalse($previewIllegal['is_valid']);
        $this->assertStringContainsString('DEC-ABS-01', $previewIllegal['error']);

        // Preview resolution: RATIFY_SESSION under OVERRIDE-SYNC-POLICY
        $previewRatify = $this->reviewWorkflow->previewDecision(
            $caseId,
            'reviewer_lead',
            'RATIFY_SESSION',
            'Telemetri diverifikasi: siswa mengerjakan di ruang terpisah/darurat.',
            ['actor_role' => 'ADMIN', 'decision_id' => 'OVERRIDE-SYNC-POLICY']
        );
        $this->assertTrue($previewRatify['is_valid']);
        $this->assertSame('RESOLVED', $previewRatify['projected_status']);
    }

    // =========================================================================
    // SECTION 3: MULTI-SESSION RETAKE ANOMALY HANDLING (DEC-RET-01)
    // =========================================================================

    public function testRetakeAnomalyIngestionAndDecRet01Enforcement(): void
    {
        $candidateSessions = [
            ['session_id' => 1011, 'start_time' => '2026-11-01 08:00:00', 'submit_time' => '2026-11-01 08:45:00', 'duration' => '45 min', 'status' => 'FINISHED', 'score' => 60.0],
            ['session_id' => 1012, 'start_time' => '2026-11-01 09:15:00', 'submit_time' => '2026-11-01 10:00:00', 'duration' => '45 min', 'status' => 'FINISHED', 'score' => 88.0],
        ];

        $caseId = $this->reviewWorkflow->ingestRetakeAnomaly(
            3001,
            502,
            'Bahasa Indonesia',
            $candidateSessions
        );
        $this->assertNotEmpty($caseId);

        $case = $this->reviewWorkflow->getCase($caseId);
        $this->assertSame('RETAKE_ANOMALY', $case['category']);
        $this->assertSame('OWNER_DECISION_REQUIRED', $case['review_status']);

        $detail = $this->reviewWorkflow->getCaseDetail($caseId);
        $this->assertSame('DEC-RET-01', $detail['applicable_decision_id']);
        $this->assertCount(2, $detail['retake_sessions']);

        // Violation 1: Missing selected_session_id
        $previewMissingSession = $this->reviewWorkflow->previewDecision(
            $caseId,
            'principal_01',
            'SELECT_ATTEMPT',
            'Pilih sesi kedua',
            ['actor_role' => 'ADMIN', 'owner_approval_ref' => 'SK-KUR-2026-009']
        );
        $this->assertFalse($previewMissingSession['is_valid']);
        $this->assertStringContainsString('Selected session ID must be provided', $previewMissingSession['error']);

        // Violation 2: Missing owner_approval_ref (Automatic selection prohibited under DEC-RET-01)
        $previewMissingRef = $this->reviewWorkflow->previewDecision(
            $caseId,
            'principal_01',
            'SELECT_ATTEMPT',
            'Pilih sesi nilai tertinggi 1012 secara otomatis',
            ['actor_role' => 'ADMIN', 'selected_session_id' => 1012]
        );
        $this->assertFalse($previewMissingRef['is_valid']);
        $this->assertStringContainsString("requires explicit 'owner_approval_ref'", $previewMissingRef['error']);

        // Violation 3: Unauthorized actor (e.g. Student)
        $previewStudent = $this->reviewWorkflow->previewDecision(
            $caseId,
            'student_user',
            'SELECT_ATTEMPT',
            'Saya pilih nilai terbaik saya',
            ['actor_role' => 'STUDENT', 'selected_session_id' => 1012, 'owner_approval_ref' => 'SK-KUR-2026-009']
        );
        $this->assertFalse($previewStudent['is_valid']);
        $this->assertStringContainsString('not authorized', $previewStudent['error']);

        // Register formal decree in evidence validator for valid resolution
        $this->reviewWorkflow->getEvidenceValidator()->registerApprovalDecree([
            'decree_ref'            => 'SK-KUR-2026-009',
            'target_student_id'     => $case['pseudonymous_student_id'],
            'sanctioned_session_id' => 1012,
            'approved_by'           => 'Product Owner',
            'issued_at'             => '2026-11-01',
        ]);

        // Valid Resolution: Authorized Actor with explicit session_id and verified owner_approval_ref
        $previewValid = $this->reviewWorkflow->previewDecision(
            $caseId,
            'principal_01',
            'SELECT_ATTEMPT',
            'Sesi ke-2 disahkan berdasarkan SK Pengulangan Resmi akibat kendala teknis.',
            [
                'actor_role'         => 'ADMIN',
                'selected_session_id' => 1012,
                'owner_approval_ref' => 'SK-KUR-2026-009',
                'decision_id'        => 'DEC-RET-01',
            ]
        );
        $this->assertTrue($previewValid['is_valid']);
        $this->assertSame('RESOLVED', $previewValid['projected_status']);
        $this->assertSame('DEC-RET-01', $previewValid['decision_id']);
        $this->assertStringContainsString('1012', $previewValid['notes']);

        // Apply decision and verify in-memory case state update
        $applied = $this->reviewWorkflow->applyDecision(
            $caseId,
            'principal_01',
            'SELECT_ATTEMPT',
            'Sesi ke-2 disahkan berdasarkan SK Pengulangan Resmi akibat kendala teknis.',
            [
                'actor_role'         => 'ADMIN',
                'selected_session_id' => 1012,
                'owner_approval_ref' => 'SK-KUR-2026-009',
                'decision_id'        => 'DEC-RET-01',
            ]
        );
        $this->assertSame('RESOLVED', $applied['review_status']);
        $this->assertSame('SELECT_ATTEMPT', $applied['decision']);
        $this->assertSame('DEC-RET-01', $applied['decision_id']);
    }

    // =========================================================================
    // SECTION 4: CBT BAP CONTROLLER AUTHORIZATION & SECURITY
    // =========================================================================

    public function testCbtBapControllerAuthorizationAndSecurity(): void
    {
        $controller = new class($this->bapService, $this->bapDocStub, $this->bapStudentStub) extends \App\Controllers\Admin\CbtBap {
            public function testAuth() {
                return $this->enforceAuthorization();
            }
        };

        // 1. Unauthenticated guest -> blocked
        $controller->setMockSession(['logged_in' => false, 'user' => []]);
        $resGuest = $controller->testAuth();
        $this->assertNotNull($resGuest);

        // 2. Unauthorized student role (role_id 4) -> blocked
        $controller->setMockSession(['logged_in' => true, 'user' => ['id' => 99, 'role_id' => 4, 'username' => 'student']]);
        $resStudent = $controller->testAuth();
        $this->assertNotNull($resStudent);

        // 3. Authorized teacher / proctor (role_id 3) -> authorized (returns null)
        $controller->setMockSession(['logged_in' => true, 'user' => ['id' => 101, 'role_id' => 3, 'username' => 'teacher_proctor']]);
        $resTeacher = $controller->testAuth();
        $this->assertNull($resTeacher);

        // 4. Authorized admin (role_id 1) -> authorized
        $controller->setMockSession(['logged_in' => true, 'user' => ['id' => 1, 'role_id' => 1, 'username' => 'admin']]);
        $resAdmin = $controller->testAuth();
        $this->assertNull($resAdmin);
    }
}
