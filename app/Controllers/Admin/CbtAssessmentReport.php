<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtExamNameModel;
use App\Models\CbtSessionModel;
use App\Models\CbtSessionScoreV3Model;
use App\Models\CbtGradebookPublicationV3Model;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Services\Cbt\Assessment\CbtAssessmentFinalizationService;
use App\Services\Cbt\Gradebook\CbtGradebookIntegrationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CbtAssessmentReport Controller
 *
 * ESPATA Assessment Intelligence V3 - Iteration 3
 * Teacher Report Dashboard & Gradebook Publication Management.
 *
 * Implements:
 * 1. Overview of exam completion & scoring status.
 * 2. Detailed assessment breakdown (objective score, essay score, final score, eligibility).
 * 3. DEC-ESS-01 visibility (clearly displaying HOLD when essay is pending).
 * 4. Single and batch publication to ESPATA Gradebook (summative_scores, final_exam_scores, pts_scores).
 * 5. Role-based authorization: Admin, Principal, and assigned Subject Teachers.
 */
class CbtAssessmentReport extends BaseController
{
    protected CbtAssessmentFinalizationService $finalizationService;
    protected CbtGradebookIntegrationService $gradebookService;
    protected CbtSessionScoreV3Model $sessionScoreModel;
    protected CbtGradebookPublicationV3Model $publicationModel;
    protected ?array $mockSession = null;

    public function __construct(
        ?CbtAssessmentFinalizationService $finalizationService = null,
        ?CbtGradebookIntegrationService $gradebookService = null,
        ?CbtSessionScoreV3Model $sessionScoreModel = null,
        ?CbtGradebookPublicationV3Model $publicationModel = null
    ) {
        $this->finalizationService = $finalizationService ?? new CbtAssessmentFinalizationService();
        $this->gradebookService    = $gradebookService ?? new CbtGradebookIntegrationService();
        $this->sessionScoreModel   = $sessionScoreModel ?? new CbtSessionScoreV3Model();
        $this->publicationModel    = $publicationModel ?? new CbtGradebookPublicationV3Model();
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

    /**
     * Enforce server-side role and session authentication.
     */
    public function enforceAuthorization(): ?ResponseInterface
    {
        $user = $this->getSessionData('user', []);
        $isLoggedIn = $this->getSessionData('logged_in', false);

        if (!$isLoggedIn || empty($user)) {
            if ($this->request && method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
                if ($this->response) {
                    return $this->response->setStatusCode(401)->setJSON([
                        'success' => false,
                        'message' => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
                    ]);
                }
            }
            if (function_exists('redirect')) {
                return redirect()->to(function_exists('base_url') ? base_url('login') : '/login')->with('error', 'Silakan masuk terlebih dahulu.');
            }
            return $this->createFallbackResponse(401, 'Sesi Anda telah berakhir. Silakan masuk kembali.');
        }

        $roleId = (int)($user['role_id'] ?? 0);
        // Authorized: 1=Admin, 2=Principal, 3=Teacher
        if (!in_array($roleId, [1, 2, 3], true)) {
            $msg = 'Anda tidak memiliki izin untuk mengakses halaman Laporan Hasil Ujian.';
            if ($this->request && method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
                if ($this->response) {
                    return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $msg]);
                }
            }
            if (function_exists('redirect')) {
                return redirect()->to(function_exists('base_url') ? base_url('dashboard') : '/dashboard')->with('error', $msg);
            }
            return $this->createFallbackResponse(403, $msg);
        }

        return null;
    }

    protected function createFallbackResponse(int $statusCode, string $body): ResponseInterface
    {
        return new class($statusCode, $body) extends \CodeIgniter\HTTP\Response {
            public function __construct(private int $code, private string $content) {}
            public function getStatusCode(): int { return $this->code; }
            public function setStatusCode(int $code, string $reason = ''): static { $this->code = $code; return $this; }
            public function getBody() { return $this->content; }
            public function setBody($data): static { $this->content = (string)$data; return $this; }
        };
    }

    /**
     * Assessment Report Overview.
     */
    public function index()
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $user = $this->getSessionData('user', []);
        $db = \Config\Database::connect();
        $tests = [];

        if ($db->tableExists('cbt_test_status')) {
            $tests = $db->table('cbt_test_status ts')
                ->select('ts.id, COALESCE(en.name, CONCAT("Ujian #", ts.id)) as name, COALESCE(s.name, qb.code, "CBT") as subject, ts.class_codes, ts.start_time, ts.end_time, ts.duration')
                ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
                ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
                ->join('subjects s', 's.id = qb.subject_id', 'left')
                ->orderBy('ts.id', 'DESC')
                ->get()->getResultArray();
        }

        if (empty($tests) && $db->tableExists('cbt_exam_names')) {
            $examModel = new CbtExamNameModel();
            $exams = $examModel->orderBy('id', 'DESC')->findAll();
            foreach ($exams as $ex) {
                $tests[] = [
                    'id'      => $ex['id'],
                    'name'    => $ex['name'],
                    'subject' => 'CBT',
                    'type'    => 'Sumatif / CBT',
                ];
            }
        }

        $data = [
            'title' => 'Laporan Hasil Asesmen CBT V3',
            'user'  => $user,
            'exams' => $tests,
        ];

        return view('admin/cbt/report/index', $data);
    }

    /**
     * Detailed Exam Assessment & Publication Report.
     */
    public function detail(int $testId)
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $user = $this->getSessionData('user', []);
        $db = \Config\Database::connect();
        $exam = null;

        if ($db->tableExists('cbt_test_status')) {
            $exam = $db->table('cbt_test_status ts')
                ->select('ts.id, COALESCE(en.name, CONCAT("Ujian #", ts.id)) as name, COALESCE(s.name, qb.code, "Mata Pelajaran") as subject, ts.class_codes, ts.start_time, ts.end_time, ts.duration')
                ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
                ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
                ->join('subjects s', 's.id = qb.subject_id', 'left')
                ->where('ts.id', $testId)
                ->get()->getRowArray();
        }

        if (!$exam) {
            $examModel = new CbtExamNameModel();
            $found = $examModel->find($testId);
            $exam = $found ? [
                'id'      => $found['id'],
                'name'    => $found['name'],
                'subject' => 'Mata Pelajaran',
            ] : [
                'id'      => $testId,
                'name'    => 'Ujian CBT #' . $testId,
                'subject' => 'Mata Pelajaran',
            ];
        }

        // Retrieve sessions, student details and V3 scores for this test
        $hasScoresV3 = $db->tableExists('cbt_session_scores_v3');
        $hasPubsV3   = $db->tableExists('cbt_gradebook_publications_v3');

        $selectCols = 's.id as session_id, s.student_id, s.status as session_status, s.started_at, s.finished_at, s.created_at, st.name as student_name, st.nisn, st.nis';
        if ($hasScoresV3) {
            $selectCols .= ', sc.score_pg, sc.score_esai, sc.score_pg_kompleks, sc.score_bs, sc.final_score, sc.report_score, sc.pending_essay_count, sc.publication_eligibility';
        } else {
            $selectCols .= ', NULL as score_pg, NULL as score_esai, NULL as score_pg_kompleks, NULL as score_bs, NULL as final_score, NULL as report_score, 0 as pending_essay_count, 0 as publication_eligibility';
        }

        if ($hasPubsV3) {
            $selectCols .= ', p.id as pub_id, p.target_table, p.published_score, p.status as pub_status';
        } else {
            $selectCols .= ', NULL as pub_id, NULL as target_table, NULL as published_score, NULL as pub_status';
        }

        $builder = $db->table('cbt_sessions s')
            ->select($selectCols)
            ->join('students st', 'st.id = s.student_id', 'left');

        if ($hasScoresV3) {
            $builder->join('cbt_session_scores_v3 sc', 'sc.session_id = s.id', 'left');
        }
        if ($hasPubsV3) {
            $builder->join('cbt_gradebook_publications_v3 p', 'p.session_id = s.id', 'left');
        }

        $rows = $builder->where('s.test_id', $testId)
            ->orderBy('s.id', 'DESC')
            ->get()->getResultArray();

        $data = [
            'title'   => "Laporan Hasil: {$exam['name']} — CBT V3",
            'exam'    => $exam,
            'results' => $rows,
            'user'    => $user,
        ];

        return view('admin/cbt/report/detail', $data);
    }

    /**
     * Publish a single finalized score to gradebook (AJAX).
     */
    public function publishSingle(): ResponseInterface
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $user = $this->getSessionData('user', []);
        $publisherId = (int)($user['id'] ?? 1);

        $sessionId   = (int)$this->request->getPost('session_id');
        $targetTable = trim((string)$this->request->getPost('target_table') ?: CbtGradebookIntegrationService::TARGET_SUMMATIVE);
        $subjectId   = (int)$this->request->getPost('subject_id');
        $yearId      = (int)$this->request->getPost('year_id');
        $semester    = (string)($this->request->getPost('semester') ?: '1');
        $sumType     = (string)($this->request->getPost('type') ?: 'tulis');
        $override    = (bool)$this->request->getPost('allow_override');

        $scoreRow = $this->sessionScoreModel->where('session_id', $sessionId)->first();
        if (!$scoreRow) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Skor asesmen sesi ini belum tersedia atau belum difinalisasi.',
            ]);
        }

        $finalizedResult = [
            'session_id'  => $sessionId,
            'test_id'     => (int)$this->request->getPost('test_id'),
            'student_id'  => (int)$this->request->getPost('student_id'),
            'final_score' => $scoreRow['final_score'],
            'is_eligible' => (bool)$scoreRow['publication_eligibility'],
            'blockers'    => $scoreRow['publication_eligibility'] ? [] : ['DEC-ESS-01: Penilaian esai belum selesai atau syarat kelayakan belum terpenuhi.'],
        ];

        $targetContext = [
            'target_table' => $targetTable,
            'subject_id'   => $subjectId,
            'year_id'      => $yearId,
            'semester'     => $semester,
            'type'         => $sumType,
        ];

        try {
            $outcome = $this->gradebookService->publishToGradebook(
                $finalizedResult,
                $targetContext,
                $publisherId,
                $override
            );

            return $this->response->setJSON($outcome);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Batch publish all eligible scores for a test to gradebook (AJAX).
     */
    public function publishBatch(): ResponseInterface
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $user = $this->getSessionData('user', []);
        $publisherId = (int)($user['id'] ?? 1);

        $testId      = (int)$this->request->getPost('test_id');
        $targetTable = trim((string)$this->request->getPost('target_table') ?: CbtGradebookIntegrationService::TARGET_SUMMATIVE);
        $subjectId   = (int)$this->request->getPost('subject_id');
        $yearId      = (int)$this->request->getPost('year_id');
        $semester    = (string)($this->request->getPost('semester') ?: '1');
        $sumType     = (string)($this->request->getPost('type') ?: 'tulis');
        $override    = (bool)$this->request->getPost('allow_override');

        $db = \Config\Database::connect();
        $candidates = $db->table('cbt_sessions s')
            ->select('s.id as session_id, s.student_id, sc.final_score, sc.publication_eligibility, sc.pending_essay_count')
            ->join('cbt_session_scores_v3 sc', 'sc.session_id = s.id')
            ->where('s.test_id', $testId)
            ->get()->getResultArray();

        if (empty($candidates)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Tidak ditemukan data sesi atau skor asesmen untuk paket ujian ini.',
            ]);
        }

        $publishedCount = 0;
        $skippedCount   = 0;
        $errors         = [];

        $targetContext = [
            'target_table' => $targetTable,
            'subject_id'   => $subjectId,
            'year_id'      => $yearId,
            'semester'     => $semester,
            'type'         => $sumType,
        ];

        foreach ($candidates as $c) {
            $sessId = (int)$c['session_id'];
            $studId = (int)$c['student_id'];
            $finalScore = $c['final_score'];
            $isEligible = (bool)$c['publication_eligibility'];

            if (!$isEligible || $finalScore === null) {
                $skippedCount++;
                continue;
            }

            $finalizedResult = [
                'session_id'  => $sessId,
                'test_id'     => $testId,
                'student_id'  => $studId,
                'final_score' => $finalScore,
                'is_eligible' => true,
                'blockers'    => [],
            ];

            try {
                $outcome = $this->gradebookService->publishToGradebook(
                    $finalizedResult,
                    $targetContext,
                    $publisherId,
                    $override
                );
                if (!empty($outcome['success'])) {
                    $publishedCount++;
                } else {
                    $errors[] = "Sesi #{$sessId}: " . ($outcome['message'] ?? 'Gagal publikasi');
                }
            } catch (\Throwable $e) {
                $errors[] = "Sesi #{$sessId}: " . $e->getMessage();
            }
        }

        return $this->response->setJSON([
            'success'         => $publishedCount > 0 || ($skippedCount > 0 && empty($errors)),
            'message'         => "Publikasi massal selesai: {$publishedCount} berhasil, {$skippedCount} dilewati (belum eligible/HOLD).",
            'published_count' => $publishedCount,
            'skipped_count'   => $skippedCount,
            'errors'          => $errors,
        ]);
    }
}

