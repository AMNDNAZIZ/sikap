<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\BkAppointmentModel;
use App\Models\BkCareerModel;
use App\Models\StudentModel;

class Counseling extends BaseController
{
    protected $appointmentModel;
    protected $studentModel;
    protected $careerModel;

    public function __construct()
    {
        $this->appointmentModel = new BkAppointmentModel();
        $this->studentModel     = new StudentModel();
        $this->careerModel      = new BkCareerModel();
    }

    /**
     * Ambil student_id dari session — ikuti pola controller siswa lainnya di ESPATA.
     */
    private function getStudentId(): ?int
    {
        $user = session()->get('user');
        return $user['student_id'] ?? $user['related_id'] ?? null;
    }

    public function index()
    {
        $studentId = $this->getStudentId();

        if (!$studentId) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan. Silakan login ulang.');
        }

        $student       = $this->studentModel->find($studentId);
        $careerProfile = $this->careerModel->getByStudent($studentId);

        // Ambil daftar pembina konseling (Wali Kelas & Guru BK) yang tersedia untuk siswa ini
        $counselors = [];
        try {
            $resolver = new \App\Libraries\NotificationRecipientResolver();
            $classId  = $resolver->getActiveStudentClassId($studentId);
            $db       = \Config\Database::connect();

            if ($classId) {
                // 1. Wali Kelas dari kelas siswa
                $cls = $db->table('classes c')
                    ->select('t.id as teacher_id, t.name as teacher_name')
                    ->join('teachers t', 't.id = c.teacher_id')
                    ->where('c.id', $classId)
                    ->where('t.is_active', 1)
                    ->get()->getRowArray();
                if (!empty($cls)) {
                    $counselors[] = [
                        'teacher_id' => (int) $cls['teacher_id'],
                        'name'       => $cls['teacher_name'],
                        'role_label' => 'Wali Kelas'
                    ];
                }

                // 2. Guru BK yang diploting untuk kelas ini
                if ($db->tableExists('bk_counselor_assignments')) {
                    $bkRows = $db->table('bk_counselor_assignments bca')
                        ->select('t.id as teacher_id, t.name as teacher_name')
                        ->join('teachers t', 't.id = bca.teacher_id')
                        ->where('bca.class_id', $classId)
                        ->where('t.is_active', 1)
                        ->get()->getResultArray();
                    foreach ($bkRows as $r) {
                        $tId = (int) $r['teacher_id'];
                        if (!in_array($tId, array_column($counselors, 'teacher_id'))) {
                            $counselors[] = [
                                'teacher_id' => $tId,
                                'name'       => $r['teacher_name'],
                                'role_label' => 'Guru BK'
                            ];
                        }
                    }
                }
            }

            // 3. Fallback: jika belum ada guru BK ploting kelas, tampilkan seluruh guru BK sekolah
            if (empty($counselors) && $db->tableExists('bk_counselor_assignments')) {
                $allBk = $db->table('bk_counselor_assignments bca')
                    ->select('t.id as teacher_id, t.name as teacher_name')
                    ->join('teachers t', 't.id = bca.teacher_id')
                    ->where('t.is_active', 1)
                    ->groupBy('t.id')
                    ->get()->getResultArray();
                foreach ($allBk as $r) {
                    $counselors[] = [
                        'teacher_id' => (int) $r['teacher_id'],
                        'name'       => $r['teacher_name'],
                        'role_label' => 'Guru BK'
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[Siswa\Counseling::index] Gagal memuat daftar konselor: ' . $e->getMessage());
        }

        $data = [
            'title'         => 'Layanan Bimbingan Konseling',
            'appointments'  => $this->appointmentModel->getStudentAppointments($studentId),
            'student'       => $student,
            'careerProfile' => $careerProfile,
            'counselors'    => $counselors,
        ];

        return view('siswa/counseling/index', $data);
    }

    public function store()
    {
        $studentId = $this->getStudentId();

        if (!$studentId) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan. Silakan login ulang.');
        }

        $datetimeRaw   = $this->request->getPost('proposed_date');
        $requestedDate = $datetimeRaw ? date('Y-m-d', strtotime($datetimeRaw)) : null;
        $requestedTime = $datetimeRaw ? date('H:i:s', strtotime($datetimeRaw)) : null;
        $preferredTeacherId = (int) $this->request->getPost('counselor_teacher_id');

        $counselorUserId = null;
        if ($preferredTeacherId > 0) {
            $db = \Config\Database::connect();
            $tUser = $db->table('teachers')
                ->select('user_id')
                ->where('id', $preferredTeacherId)
                ->get()->getRowArray();
            $counselorUserId = !empty($tUser['user_id']) ? (int) $tUser['user_id'] : null;
        }

        $data = [
            'student_id'     => $studentId,
            'counselor_id'   => $counselorUserId,
            'topic'          => $this->request->getPost('topic'),
            'requested_date' => $requestedDate,
            'requested_time' => $requestedTime,
            'status'         => 'Pending'
        ];

        $this->appointmentModel->insert($data);
        $insertId = $this->appointmentModel->getInsertID();

        // Notifikasi ke Wali Kelas & Guru BK yang sah (Anti-Broadcast, topik dirahasiakan, DILARANG ke Admin)
        try {
            helper('notification');
            $resolver = new \App\Libraries\NotificationRecipientResolver();
            $recipients = $resolver->resolveCounselingRecipients(
                $studentId,
                null,
                $preferredTeacherId > 0 ? $preferredTeacherId : null
            );

            // Filter ketat: pastikan Admin (role_id = 1) TIDAK PERNAH menerima notifikasi ini
            if (!empty($recipients)) {
                $db = \Config\Database::connect();
                $teacherUsers = $db->table('users')
                    ->select('id')
                    ->whereIn('id', $recipients)
                    ->where('role_id', 3) // Hanya akun Guru sah
                    ->where('is_active', 1)
                    ->get()->getResultArray();
                $recipients = array_values(array_unique(array_filter(array_map('intval', array_column($teacherUsers, 'id')))));
            }

            $student = (new \App\Models\StudentModel())->find($studentId);
            $studentName = $student['name'] ?? 'Siswa';
            $title = 'Pengajuan Janji Temu BK: ' . $studentName;
            $msg   = 'Siswa telah mengajukan permohonan janji temu konseling. Silakan buka menu Konseling untuk memverifikasi jadwal.';
            $url   = base_url('admin/counseling-appointment');

            if (!empty($recipients)) {
                notify_users($recipients, 'info', $title, $msg, $url, 'bk_appointment', $insertId ?: null);
            } else {
                // Jangan kirim notifikasi ke Admin. Privasi siswa harus dijaga; catat hanya di log sistem.
                log_message('warning', "[Siswa\Counseling::store] Belum ada Wali Kelas atau Guru BK terkonfigurasi untuk siswa ID {$studentId}. Notifikasi tidak dikirim.");
            }
        } catch (\Throwable $e) {
            log_message('error', '[Siswa\Counseling::store] Gagal kirim notif: ' . $e->getMessage());
        }

        return redirect()->to(base_url('siswa/counseling'))->with('success', 'Pengajuan janji temu berhasil dikirim. Menunggu konfirmasi dari Guru BK.');
    }

    public function cancel($id)
    {
        $studentId   = $this->getStudentId();
        $appointment = $this->appointmentModel->find($id);

        if ($appointment && (int)$appointment['student_id'] === (int)$studentId && $appointment['status'] == 'Pending') {
            $this->appointmentModel->delete($id);
            return redirect()->to(base_url('siswa/counseling'))->with('success', 'Pengajuan berhasil dibatalkan.');
        }

        return redirect()->to(base_url('siswa/counseling'))->with('error', 'Pengajuan tidak dapat dibatalkan.');
    }

    // ─── Angket Karir ────────────────────────────────────────
    public function career()
    {
        $studentId     = $this->getStudentId();
        $careerProfile = $this->careerModel->getByStudent($studentId);

        return view('siswa/counseling/career', [
            'title'         => 'Angket Penelusuran Karir & Minat Bakat',
            'careerProfile' => $careerProfile,
        ]);
    }

    public function saveCareer()
    {
        $studentId = $this->getStudentId();

        if (!$studentId) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        $data = [
            'student_id'          => $studentId,
            'post_graduate_plan'  => $this->request->getPost('post_graduate_plan'),
            'university_target'   => $this->request->getPost('university_target'),
            'major_interest'      => $this->request->getPost('major_interest'),
            'hobby'               => $this->request->getPost('hobby'),
            'talent'              => $this->request->getPost('talent'),
            'favorite_subject'    => $this->request->getPost('favorite_subject'),
            'dream_job'           => $this->request->getPost('dream_job'),
            'family_income'       => $this->request->getPost('family_income'),
            'family_support'      => $this->request->getPost('family_support') ? 1 : 0,
            'motivation'          => $this->request->getPost('motivation'),
            'filled_at'           => date('Y-m-d H:i:s'),
        ];

        $existing = $this->careerModel->getByStudent($studentId);

        if ($existing) {
            $this->careerModel->update($existing['id'], $data);
        } else {
            $this->careerModel->insert($data);
        }

        return redirect()->to(base_url('siswa/counseling'))->with('success', 'Angket penelusuran karir berhasil disimpan. Terima kasih!');
    }
}
