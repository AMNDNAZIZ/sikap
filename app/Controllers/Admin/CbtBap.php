<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtBapDocumentV3Model;
use App\Models\CbtBapStudentV3Model;
use App\Models\CbtAuditTrailV3Model;
use App\Models\CbtExamNameModel;
use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Services\Cbt\Bap\DigitalBapService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CbtBap Controller
 *
 * ESPATA Assessment Intelligence V3 - Stage F3H / Iteration 2
 * Proctor Digital BAP (Berita Acara Pelaksanaan Ujian) & Attendance Reconciliation.
 *
 * Implements:
 * 1. Room/Session Proctor Assignment listing.
 * 2. Digital Attendance Recording (HADIR, TIDAK_HADIR, SAKIT, IZIN, DISPENSASI).
 * 3. Non-final Draft Saving & incident reporting.
 * 4. Cryptographic SHA-256 Document Signing & Authentication with audit trail logging.
 * 5. Cross-system Telemetry vs BAP Discrepancy Detection (OVERRIDE-SYNC-POLICY / DEC-ABS-01).
 * 6. Proctor Assignment Role-Based Access Control (Admin/Principal: universal; Teacher: assigned rooms).
 */
class CbtBap extends BaseController
{
    protected DigitalBapService $bapService;
    protected CbtBapDocumentV3Model $documentModel;
    protected CbtBapStudentV3Model $studentModel;
    protected ?array $mockSession = null;

    public function __construct(
        ?DigitalBapService $bapService = null,
        ?CbtBapDocumentV3Model $documentModel = null,
        ?CbtBapStudentV3Model $studentModel = null
    ) {
        $this->documentModel = $documentModel ?? new CbtBapDocumentV3Model();
        $this->studentModel  = $studentModel ?? new CbtBapStudentV3Model();
        $this->bapService    = $bapService ?? new DigitalBapService($this->documentModel, $this->studentModel);
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

    public function getBapService(): DigitalBapService
    {
        return $this->bapService;
    }

    /**
     * Authenticate and authorize session actor.
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
            if ($this->response) {
                return $this->response->setStatusCode(401)->setBody('Sesi Anda telah berakhir. Silakan masuk kembali.');
            }
            return $this->createFallbackResponse(401, 'Sesi Anda telah berakhir. Silakan masuk kembali.');
        }

        $roleId = (int)($user['role_id'] ?? 0);
        // Authorized: 1=Admin, 2=Principal, 3=Teacher/Proctor
        if (!in_array($roleId, [1, 2, 3], true)) {
            $msg = 'Anda tidak memiliki izin untuk mengakses Berita Acara Pelaksanaan (BAP) Ujian.';
            if ($this->request && method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
                if ($this->response) {
                    return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $msg]);
                }
            }
            if (function_exists('redirect')) {
                return redirect()->to(function_exists('base_url') ? base_url('dashboard') : '/dashboard')->with('error', $msg);
            }
            if ($this->response) {
                return $this->response->setStatusCode(403)->setBody($msg);
            }
            return $this->createFallbackResponse(403, $msg);
        }

        return null;
    }

    public function createFallbackResponse(int $statusCode, string $body): ResponseInterface
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
     * Proctor Dashboard / List of BAP Documents.
     */
    public function index()
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $user = $this->getSessionData('user', []);
        $roleId = (int)($user['role_id'] ?? 0);
        $userId = (int)($user['id'] ?? 0);

        $documents = $this->documentModel->listDocuments();

        // Exam list
        $db = \Config\Database::connect();
        $exams = [];
        if ($db->tableExists('cbt_test_status')) {
            $exams = $db->table('cbt_test_status ts')
                ->select('ts.id, COALESCE(en.name, CONCAT("Ujian #", ts.id)) as name, COALESCE(s.name, qb.code, "CBT") as subject')
                ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
                ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
                ->join('subjects s', 's.id = qb.subject_id', 'left')
                ->orderBy('ts.id', 'DESC')
                ->get()->getResultArray();
        }

        if (empty($exams) && $db->tableExists('cbt_exam_names')) {
            $examModel = new CbtExamNameModel();
            $rawExams = $examModel->select('id, name')->orderBy('name', 'ASC')->findAll();
            foreach ($rawExams as $re) {
                $exams[] = [
                    'id'      => $re['id'],
                    'name'    => $re['name'],
                    'subject' => 'CBT',
                ];
            }
        }

        $classModel = new ClassModel();
        $classes = $classModel->select('id, name')->orderBy('name', 'ASC')->findAll();

        $data = [
            'title'     => 'Digital BAP — Pengawasan Ujian CBT',
            'user'      => $user,
            'roleId'    => $roleId,
            'documents' => $documents,
            'exams'     => $exams,
            'classes'   => $classes,
        ];

        return view('admin/cbt/bap/index', $data);
    }

    /**
     * Digital Attendance Sheet / Form for a specific test and room.
     */
    public function form(int $testId, string $roomCode)
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $user = $this->getSessionData('user', []);
        $roleId = (int)($user['role_id'] ?? 0);
        $userId = (int)($user['id'] ?? 0);
        $actorRole = match($roleId) {
            1 => 'ADMIN',
            2 => 'PRINCIPAL',
            3 => 'PROCTOR',
            default => 'UNAUTHORIZED',
        };

        if (!$this->bapService->isProctorAuthorized($userId, $actorRole, $testId, $roomCode)) {
            return redirect()->to(base_url('admin/cbt/bap'))
                ->with('error', "Anda tidak memiliki otorisasi pengawasan untuk Ruang {$roomCode}.");
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('cbt_bap_documents_v3')) {
            return redirect()->to(base_url('admin/cbt/bap'))
                ->with('error', "Tabel Berita Acara Digital (cbt_bap_documents_v3) belum dimigrasi pada database ini.");
        }
        $exam = null;
        if ($db->tableExists('cbt_test_status')) {
            $exam = $db->table('cbt_test_status ts')
                ->select('ts.id, COALESCE(en.name, CONCAT("Ujian #", ts.id)) as name, COALESCE(s.name, qb.code, "Mata Pelajaran") as subject')
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

        // Retrieve student candidates for this room / class
        $studentModel = new StudentModel();
        $classModel = new ClassModel();
        $classData = $classModel->where('name', $roomCode)->first();
        $classId = $classData['id'] ?? null;

        $registeredStudents = [];
        if ($classId) {
            $rawStudents = $studentModel->getByClass($classId);
            foreach ($rawStudents as $st) {
                $registeredStudents[] = [
                    'student_id'   => (int)$st['id'],
                    'nama_lengkap' => $st['nama_lengkap'] ?? $st['name'] ?? ('Siswa #' . $st['id']),
                    'nisn'         => $st['nisn'] ?? '-',
                ];
            }
        }

        $proctorName = $user['nama_lengkap'] ?? $user['name'] ?? $user['username'] ?? 'Pengawas';

        $draft = $this->bapService->getOrCreateDraft(
            $testId,
            $roomCode,
            $userId,
            $proctorName,
            'Pengawas Ruang ' . $roomCode,
            $registeredStudents
        );

        $data = [
            'title'     => "Digital BAP: {$exam['name']} — {$roomCode}",
            'exam'      => $exam,
            'roomCode'  => $roomCode,
            'document'  => $draft['document'],
            'students'  => $draft['students'],
            'user'      => $user,
            'actorRole' => $actorRole,
        ];

        return view('admin/cbt/bap/form', $data);
    }

    /**
     * Save BAP draft updates (AJAX).
     */
    public function saveDraft(): ResponseInterface
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $bapId = (int)$this->request->getPost('bap_id');
        $supervisorName = trim((string)$this->request->getPost('supervisor_name'));
        $incidentNotes = trim((string)$this->request->getPost('incident_notes'));
        $attendanceRaw = $this->request->getPost('attendance');

        $studentAttendance = [];
        if (is_array($attendanceRaw)) {
            foreach ($attendanceRaw as $stId => $info) {
                $studentAttendance[] = [
                    'student_id'      => (int)$stId,
                    'reported_status' => is_array($info) ? ($info['status'] ?? 'HADIR') : (string)$info,
                    'proctor_notes'   => is_array($info) ? ($info['notes'] ?? null) : null,
                ];
            }
        }

        try {
            $updated = $this->bapService->saveDraft($bapId, $studentAttendance, $supervisorName, $incidentNotes);

            return $this->response->setJSON([
                'success'  => true,
                'message'  => 'Draft Berita Acara berhasil disimpan.',
                'document' => $updated,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Digitally sign and authenticate BAP document (AJAX).
     */
    public function submit(): ResponseInterface
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $user = $this->getSessionData('user', []);
        $roleId = (int)($user['role_id'] ?? 0);
        $userId = (int)($user['id'] ?? 0);
        $actorRole = match($roleId) {
            1 => 'ADMIN',
            2 => 'PRINCIPAL',
            3 => 'PROCTOR',
            default => 'UNAUTHORIZED',
        };

        $bapId = (int)$this->request->getPost('bap_id');
        $incidentNotes = trim((string)$this->request->getPost('incident_notes'));
        $signerIdentity = trim((string)($user['nama_lengkap'] ?? $user['name'] ?? $user['username'] ?? 'Pengawas'));

        try {
            $result = $this->bapService->signAndSubmitBap(
                $bapId,
                $userId,
                $signerIdentity,
                $actorRole,
                $incidentNotes
            );

            return $this->response->setJSON([
                'success'         => true,
                'message'         => 'Berita Acara Pelaksanaan Ujian berhasil disahkan secara digital dengan segel SHA-256.',
                'document_sha256' => $result['document_sha256'],
                'signed_at'       => $result['signed_at'],
                'signer_identity' => $result['signer_identity'],
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Detect discrepancies between BAP and telemetry.
     */
    public function discrepancies(int $bapId): ResponseInterface
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        try {
            $analysis = $this->bapService->detectAttendanceDiscrepancies($bapId);
            return $this->response->setJSON([
                'success' => true,
                'data'    => $analysis,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * View authenticated BAP document.
     */
    public function view(int $bapId)
    {
        $authError = $this->enforceAuthorization();
        if ($authError) {
            return $authError;
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('cbt_bap_documents_v3')) {
            return redirect()->to(base_url('admin/cbt/bap'))->with('error', "Tabel Berita Acara Digital (cbt_bap_documents_v3) belum dimigrasi pada database ini.");
        }

        $doc = $this->documentModel->find($bapId);
        if (!$doc) {
            return redirect()->to(base_url('admin/cbt/bap'))->with('error', "Dokumen BAP #{$bapId} tidak ditemukan.");
        }

        $students = $this->studentModel->getStudentsByBap($bapId);

        $exam = null;
        if ($db->tableExists('cbt_test_status')) {
            $ts = $db->table('cbt_test_status ts')
                ->select('ts.id, en.name as exam_name, s.name as subject_name, qb.code as bank_code')
                ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
                ->join('subjects s', 's.id = qb.subject_id', 'left')
                ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
                ->where('ts.id', (int)$doc['test_id'])
                ->get()->getRowArray();
            if ($ts) {
                $exam = [
                    'id'      => $ts['id'],
                    'name'    => $ts['exam_name'] ?? ('Ujian CBT (' . ($ts['bank_code'] ?? $ts['id']) . ')'),
                    'subject' => $ts['subject_name'] ?? 'Mata Pelajaran',
                ];
            }
        }
        if (!$exam && $db->tableExists('cbt_question_banks')) {
            $qb = $db->table('cbt_question_banks qb')
                ->select('qb.id, qb.code as bank_code, s.name as subject_name')
                ->join('subjects s', 's.id = qb.subject_id', 'left')
                ->where('qb.id', (int)$doc['test_id'])
                ->get()->getRowArray();
            if ($qb) {
                $exam = [
                    'id'      => $qb['id'],
                    'name'    => 'Ujian CBT (' . $qb['bank_code'] . ')',
                    'subject' => $qb['subject_name'] ?? 'Mata Pelajaran',
                ];
            }
        }
        if (!$exam) {
            $examModel = new CbtExamNameModel();
            $found = $examModel->find($doc['test_id']);
            $exam = $found ? [
                'id'      => $found['id'],
                'name'    => $found['name'],
                'subject' => $found['subject'] ?? 'Mata Pelajaran',
            ] : [
                'id'      => $doc['test_id'],
                'name'    => 'Ujian #' . $doc['test_id'],
                'subject' => '-',
            ];
        }

        $data = [
            'title'    => "Berita Acara: {$doc['bap_number']}",
            'document' => $doc,
            'students' => $students,
            'exam'     => $exam,
        ];

        return view('admin/cbt/bap/view', $data);
    }
}
