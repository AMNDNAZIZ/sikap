<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerAuditService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CbtReview Controller
 *
 * ESPATA Assessment Intelligence V3 - Stage F3C
 * Interactive Reviewer Workflow & Verification Gate Interface.
 *
 * Implements:
 * 1. Review Queue Dashboard with multi-dimensional filtering, searching, and pagination.
 * 2. Case Detail inspection with provenance, evidence breakdown, and PODR policy mappings.
 * 3. Strictly non-mutating Decision Preview with server-side governance enforcement.
 * 4. Read-only Audit Trail Viewer with cryptographic SHA-256 hash chain verification.
 * 5. Strict server-side RBAC: Authorized roles (Admin=1, Principal=2, Teacher=3); Student/Guest blocked.
 */
class CbtReview extends BaseController
{
    protected ReviewerWorkflowService $workflow;
    protected ReviewerAuditService $auditService;
    protected ?array $mockSession = null;

    public function __construct(
        ?ReviewerWorkflowService $workflow = null,
        ?ReviewerAuditService $auditService = null
    ) {
        $this->auditService = $auditService ?? new ReviewerAuditService();
        $this->workflow = $workflow ?? new ReviewerWorkflowService(null, $this->auditService);

        // Load queue from shadow deviations artifact if empty
        if (count($this->workflow->getQueue()) === 0) {
            $this->workflow->loadFromShadowDeviations();
        }
    }

    public function getWorkflow(): ReviewerWorkflowService
    {
        return $this->workflow;
    }

    public function getAuditService(): ReviewerAuditService
    {
        return $this->auditService;
    }

    public function setMockSession(?array $sessionData): void
    {
        $this->mockSession = $sessionData;
    }

    public function getSessionData(string $key, mixed $default = null): mixed
    {
        if ($this->mockSession !== null) {
            return $this->mockSession[$key] ?? $default;
        }
        if (function_exists('session')) {
            try {
                return session()->get($key) ?? $default;
            } catch (\Throwable) {
                return $_SESSION[$key] ?? $default;
            }
        }
        return $_SESSION[$key] ?? $default;
    }

    public function setResponse(?ResponseInterface $response): void
    {
        $this->response = $response;
    }

    public function setRequest(\CodeIgniter\HTTP\RequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Enforce server-side role and session authentication.
     *
     * @return ResponseInterface|null Returns error response if unauthorized, or null if authorized
     */
    public function enforceAuthorization(): ?ResponseInterface
    {
        $user = $this->getSessionData('user', []);
        $isLoggedIn = $this->getSessionData('logged_in', false);

        if (!$isLoggedIn || empty($user)) {
            if ($this->request && method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
                if ($this->response) {
                    return $this->response
                        ->setStatusCode(401)
                        ->setJSON([
                            'success'  => false,
                            'message'  => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
                            'redirect' => function_exists('base_url') ? base_url('login') : '/login',
                        ]);
                }
            }
            if (function_exists('redirect')) {
                return redirect()->to(function_exists('base_url') ? base_url('login') : '/login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses menu Peninjauan Ujian.');
            }
            if ($this->response) {
                return $this->response->setStatusCode(401)->setBody('Sesi Anda telah berakhir. Silakan masuk kembali.');
            }
            return $this->createFallbackResponse(401, 'Sesi Anda telah berakhir. Silakan masuk kembali.');
        }

        $roleId = (int)($user['role_id'] ?? 0);
        // Authorized roles: 1 = Admin, 2 = Kepala Sekolah / Principal, 3 = Guru / Teacher
        $authorizedRoleIds = [1, 2, 3];

        if (!in_array($roleId, $authorizedRoleIds, true)) {
            if ($this->request && method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
                if ($this->response) {
                    return $this->response
                        ->setStatusCode(403)
                        ->setJSON([
                            'success' => false,
                            'message' => "GOVERNANCE BREACH: Role ID '{$roleId}' tidak memiliki otorisasi untuk mengakses Reviewer CBT.",
                        ]);
                }
            }
            if (function_exists('redirect')) {
                return redirect()->to(function_exists('base_url') ? base_url('dashboard') : '/dashboard')->with('error', 'Anda tidak memiliki hak akses ke modul Reviewer CBT.');
            }
            if ($this->response) {
                return $this->response->setStatusCode(403)->setBody("Forbidden: Role ID '{$roleId}' is not authorized.");
            }
            return $this->createFallbackResponse(403, "Forbidden: Role ID '{$roleId}' is not authorized.");
        }

        return null;
    }

    private function createFallbackResponse(int $statusCode, string $body): ResponseInterface
    {
        return new class($statusCode, $body) extends \CodeIgniter\HTTP\Response {
            public function __construct(private int $code, private string $content) {
                // Do not invoke parent::__construct to bypass service('csp') outside full CI4 boot
            }
            public function getStatusCode(): int { return $this->code; }
            public function setStatusCode(int $code, string $reason = ''): static { $this->code = $code; return $this; }
            public function getBody() { return $this->content; }
            public function setBody($data): static { $this->content = (string)$data; return $this; }
        };
    }

    /**
     * Review Queue Dashboard.
     */
    public function index()
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $filters = [
            'category'          => $this->request->getGet('category') ?: null,
            'review_status'     => $this->request->getGet('status') ?: ($this->request->getGet('review_status') ?: null),
            'responsible_party' => $this->request->getGet('responsible_party') ?: null,
            'decision_id'       => $this->request->getGet('decision_id') ?: null,
            'search'            => $this->request->getGet('q') ?: ($this->request->getGet('search') ?: null),
        ];

        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = 10;

        $queueData = $this->workflow->getFilteredQueue($filters, $page, $perPage);
        $summary = $this->workflow->getSummary();

        $data = [
            'title'      => 'Reviewer Workflow — Assessment Intelligence V3',
            'summary'    => $summary,
            'queue'      => $queueData['items'],
            'pagination' => [
                'total_items' => $queueData['total_items'],
                'page'        => $queueData['page'],
                'per_page'    => $queueData['per_page'],
                'total_pages' => $queueData['total_pages'],
            ],
            'filters'    => $filters,
        ];

        return view('admin/cbt/review/index', $data);
    }

    /**
     * Inspect a specific review case.
     */
    public function detail(string $caseId)
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $caseDetail = $this->workflow->getCaseDetail($caseId);
        if (!$caseDetail) {
            return redirect()->to(base_url('admin/cbt/review'))->with('error', "Kasus review '{$caseId}' tidak ditemukan.");
        }

        $user = $this->getSessionData('user', []);
        $roleId = (int)($user['role_id'] ?? 0);
        $actorRole = match($roleId) {
            1 => 'ADMIN',
            2 => 'PRINCIPAL',
            3 => 'TEACHER',
            default => 'UNAUTHORIZED',
        };

        $data = [
            'title'       => "Detail Kasus: {$caseId} — CBT Review",
            'case'        => $caseDetail,
            'currentUser' => [
                'username'   => $user['username'] ?? 'operator',
                'role_id'    => $roleId,
                'actor_role' => $actorRole,
            ],
        ];

        return view('admin/cbt/review/detail', $data);
    }

    /**
     * Preview decision outcomes (STRICTLY NON-MUTATING).
     *
     * Validates governance rules, checks audit integrity, simulates state transitions,
     * and displays the exact audit payload without mutating state or database.
     */
    public function preview(): ResponseInterface
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Method Not Allowed. POST request required.',
            ]);
        }

        $caseId = trim((string)$this->request->getPost('case_id'));
        $action = trim((string)$this->request->getPost('action'));
        $reason = trim((string)$this->request->getPost('reason'));

        $user = $this->getSessionData('user', []);
        $roleId = (int)($user['role_id'] ?? 0);
        $authActorRole = match($roleId) {
            1 => 'ADMIN',
            2 => 'PRINCIPAL',
            3 => 'TEACHER',
            default => 'UNAUTHORIZED',
        };

        // Server-side authentic session identity
        $authReviewerId = trim((string)($user['username'] ?? ''));
        if ($authReviewerId === '') {
            $authReviewerId = 'REV_' . ($user['id'] ?? 'USER');
        }

        // Anti-spoofing Check 1: Reviewer ID cannot contradict authenticated session
        $postedReviewerId = trim((string)$this->request->getPost('reviewer_id'));
        if ($postedReviewerId !== '' && $postedReviewerId !== $authReviewerId) {
            $msg = "GOVERNANCE BREACH: Reviewer identity spoofing detected. Submitted reviewer_id does not match authenticated session.";
            if ($this->response) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => $msg,
                ]);
            }
            return $this->createFallbackResponse(403, json_encode(['success' => false, 'message' => $msg]));
        }

        // Anti-spoofing Check 2: Actor role cannot contradict authenticated session role
        $postedActorRole = trim((string)$this->request->getPost('actor_role'));
        if ($postedActorRole !== '' && strtoupper($postedActorRole) !== $authActorRole) {
            $msg = "GOVERNANCE BREACH: Actor role spoofing detected. Submitted role does not match authenticated session role.";
            if ($this->response) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => $msg,
                ]);
            }
            return $this->createFallbackResponse(403, json_encode(['success' => false, 'message' => $msg]));
        }

        // Granular subject teacher authority check
        if ($authActorRole === 'TEACHER') {
            $case = $this->workflow->getCase($caseId);
            if ($case && !$this->workflow->getEvidenceValidator()->isTeacherAssignedToTest($authReviewerId, $case['test_id'])) {
                $msg = "GOVERNANCE BREACH: Teacher '{$authReviewerId}' is not authorized to review Test ID {$case['test_id']} ({$case['subject_name']}).";
                if ($this->response) {
                    return $this->response->setStatusCode(403)->setJSON([
                        'success' => false,
                        'message' => $msg,
                    ]);
                }
                return $this->createFallbackResponse(403, json_encode(['success' => false, 'message' => $msg]));
            }
        }

        // Additional governance parameters - strictly bind actor_role to session authority
        $params = [
            'actor_role'                => $authActorRole,
            'actor_role_id'             => $roleId,
            'decision_id'               => $this->request->getPost('decision_id') ?: null,
            'selected_session_id'       => $this->request->getPost('selected_session_id') ?: null,
            'owner_approval_ref'        => $this->request->getPost('owner_approval_ref') ?: null,
            // Client evidence_verified and authoritative claims are explicitly blocked/ignored
            'evidence_verified'         => false,
            'is_authoritative_verified' => false,
            'trust_level'               => null,
            'enforce_strict_registry'   => true,
        ];

        // Decimal precision parameters if provided
        if ($this->request->getPost('legacy_score') !== null && $this->request->getPost('legacy_score') !== '') {
            $params['legacy_score'] = (string)$this->request->getPost('legacy_score');
        }
        if ($this->request->getPost('v3_score') !== null && $this->request->getPost('v3_score') !== '') {
            $params['v3_score'] = (string)$this->request->getPost('v3_score');
        }
        if ($this->request->getPost('delta') !== null && $this->request->getPost('delta') !== '') {
            $params['delta'] = (string)$this->request->getPost('delta');
        }

        $previewResult = $this->workflow->previewDecision(
            $caseId,
            $authReviewerId,
            $action,
            $reason,
            $params
        );

        $statusCode = $previewResult['is_valid'] ? 200 : 422;

        $responseData = [
            'success'  => $previewResult['is_valid'],
            'preview'  => $previewResult,
            'message'  => $previewResult['is_valid'] ? 'Simulasi keputusan berhasil dievaluasi.' : ($previewResult['error'] ?? 'Validasi keputusan gagal.'),
        ];

        if ($this->response) {
            return $this->response->setStatusCode($statusCode)->setJSON($responseData);
        }
        return $this->createFallbackResponse($statusCode, json_encode($responseData));
    }

    /**
     * Apply decision outcomes (MUTATING IN-MEMORY WORKFLOW STATE).
     *
     * Independently re-validates actor authorization, reviewer identity, and teacher assignment (Stage F3G-R6-R2-R3 Audit C).
     * Prevents stale preview reuse, session role drift, or identity spoofing.
     */
    public function apply(): ResponseInterface
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            $data = ['success' => false, 'message' => 'Method Not Allowed. POST request required.'];
            if ($this->response) {
                return $this->response->setStatusCode(405)->setJSON($data);
            }
            return $this->createFallbackResponse(405, json_encode($data));
        }

        $caseId = trim((string)$this->request->getPost('case_id'));
        $action = trim((string)$this->request->getPost('action'));
        $reason = trim((string)$this->request->getPost('reason'));

        $user = $this->getSessionData('user', []);
        $roleId = (int)($user['role_id'] ?? 0);
        $authActorRole = match($roleId) {
            1 => 'ADMIN',
            2 => 'PRINCIPAL',
            3 => 'TEACHER',
            default => 'UNAUTHORIZED',
        };

        if ($authActorRole === 'UNAUTHORIZED') {
            $data = ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menetapkan keputusan ini.'];
            if ($this->response) {
                return $this->response->setStatusCode(403)->setJSON($data);
            }
            return $this->createFallbackResponse(403, json_encode($data));
        }

        // Server-side authentic session identity
        $authReviewerId = trim((string)($user['username'] ?? ''));
        if ($authReviewerId === '') {
            $authReviewerId = 'REV_' . ($user['id'] ?? 'USER');
        }

        // Anti-spoofing Check 1: Reviewer ID cannot contradict authenticated session
        $postedReviewerId = trim((string)$this->request->getPost('reviewer_id'));
        if ($postedReviewerId !== '' && $postedReviewerId !== $authReviewerId) {
            $msg = "GOVERNANCE BREACH: Reviewer identity spoofing detected. Submitted reviewer_id does not match authenticated session.";
            if ($this->response) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $msg]);
            }
            return $this->createFallbackResponse(403, json_encode(['success' => false, 'message' => $msg]));
        }

        // Anti-spoofing Check 2: Actor role cannot contradict authenticated session role
        $postedActorRole = trim((string)$this->request->getPost('actor_role'));
        if ($postedActorRole !== '' && strtoupper($postedActorRole) !== $authActorRole) {
            $msg = "GOVERNANCE BREACH: Actor role spoofing detected. Submitted role does not match authenticated session role.";
            if ($this->response) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $msg]);
            }
            return $this->createFallbackResponse(403, json_encode(['success' => false, 'message' => $msg]));
        }

        // Granular subject teacher authority check independently verified on apply
        if ($authActorRole === 'TEACHER') {
            $case = $this->workflow->getCase($caseId);
            if ($case && !$this->workflow->getEvidenceValidator()->isTeacherAssignedToTest($authReviewerId, $case['test_id'])) {
                $msg = "GOVERNANCE BREACH: Teacher '{$authReviewerId}' is not authorized to review Test ID {$case['test_id']} ({$case['subject_name']}).";
                if ($this->response) {
                    return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $msg]);
                }
                return $this->createFallbackResponse(403, json_encode(['success' => false, 'message' => $msg]));
            }
        }

        // Additional governance parameters - strictly bind actor_role to session authority
        $params = [
            'actor_role'                => $authActorRole,
            'actor_role_id'             => $roleId,
            'decision_id'               => $this->request->getPost('decision_id') ?: null,
            'selected_session_id'       => $this->request->getPost('selected_session_id') ?: null,
            'owner_approval_ref'        => $this->request->getPost('owner_approval_ref') ?: null,
            'expected_status'           => $this->request->getPost('expected_status') ?: null,
            // Client evidence_verified and authoritative claims are strictly ignored/blocked
            'evidence_verified'         => false,
            'is_authoritative_verified' => false,
            'trust_level'               => null,
            'enforce_strict_registry'   => true,
        ];

        // Decimal precision parameters if provided
        if ($this->request->getPost('legacy_score') !== null && $this->request->getPost('legacy_score') !== '') {
            $params['legacy_score'] = (string)$this->request->getPost('legacy_score');
        }
        if ($this->request->getPost('v3_score') !== null && $this->request->getPost('v3_score') !== '') {
            $params['v3_score'] = (string)$this->request->getPost('v3_score');
        }
        if ($this->request->getPost('delta') !== null && $this->request->getPost('delta') !== '') {
            $params['delta'] = (string)$this->request->getPost('delta');
        }

        try {
            $applyResult = $this->workflow->applyDecision(
                $caseId,
                $authReviewerId,
                $action,
                $reason,
                $params
            );

            $responseData = [
                'success' => true,
                'result'  => $applyResult,
                'message' => 'Keputusan review berhasil diterapkan.',
            ];
            if ($this->response) {
                return $this->response->setStatusCode(200)->setJSON($responseData);
            }
            return $this->createFallbackResponse(200, json_encode($responseData));
        } catch (\Throwable $e) {
            $isGovBreach = str_contains($e->getMessage(), 'GOVERNANCE BREACH') || str_contains($e->getMessage(), 'not authorized');
            $statusCode = $isGovBreach ? 403 : 422;
            $responseData = [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Gagal menerapkan keputusan review: ' . $e->getMessage(),
            ];
            if ($this->response) {
                return $this->response->setStatusCode($statusCode)->setJSON($responseData);
            }
            return $this->createFallbackResponse($statusCode, json_encode($responseData));
        }
    }

    /**
     * Read-only Audit Trail Viewer.
     */
    public function auditTrail()
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $trail = $this->auditService->getTrail();
        $integrity = $this->auditService->verifyAuditIntegrity();

        $data = [
            'title'     => 'Audit Trail Reviewer CBT — Cryptographic Provenance',
            'trail'     => $trail,
            'integrity' => $integrity,
        ];

        return view('admin/cbt/review/audit_trail', $data);
    }
}
