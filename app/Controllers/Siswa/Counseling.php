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

        $data = [
            'title'         => 'Layanan Bimbingan Konseling',
            'appointments'  => $this->appointmentModel->getStudentAppointments($studentId),
            'student'       => $student,
            'careerProfile' => $careerProfile,
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

        $data = [
            'student_id'     => $studentId,
            'topic'          => $this->request->getPost('topic'),
            'requested_date' => $requestedDate,
            'requested_time' => $requestedTime,
            'status'         => 'Pending'
        ];

        $this->appointmentModel->insert($data);
        $insertId = $this->appointmentModel->getInsertID();

        // Notifikasi ke Wali Kelas & Guru BK yang sah (Anti-Broadcast, topik dirahasiakan)
        try {
            helper('notification');
            $resolver = new \App\Libraries\NotificationRecipientResolver();
            $recipients = $resolver->resolveCounselingRecipients($studentId);

            $student = (new \App\Models\StudentModel())->find($studentId);
            $studentName = $student['name'] ?? 'Siswa';
            $title = 'Pengajuan Janji Temu BK: ' . $studentName;
            $msg   = 'Siswa telah mengajukan permohonan janji temu konseling. Silakan buka menu Konseling untuk memverifikasi jadwal.';
            $url   = base_url('admin/counseling-appointment');

            if (!empty($recipients)) {
                notify_users($recipients, 'info', $title, $msg, $url, 'bk_appointment', $insertId ?: null);
            } else {
                // Fail-safe: jika pembina belum dikonfigurasi, kirim peringatan konfigurasi sistem ke Admin tanpa identitas siswa
                $adminUserIds = $resolver->getAdminUserIds();
                if (!empty($adminUserIds)) {
                    $adminTitle = '[Pemberitahuan Sistem] Penugasan Pembina Siswa Belum Diatur';
                    $adminMsg   = 'Terdapat permohonan konseling baru, namun belum ada Wali Kelas atau Guru BK yang dikonfigurasi untuk kelas terkait. Mohon atur penugasan di menu Kelas dan Penugasan BK.';
                    notify_users($adminUserIds, 'warning', $adminTitle, $adminMsg, base_url('admin/classes'), 'bk_appointment', $insertId ?: null);
                }
                log_message('warning', "[Siswa\Counseling::store] Tidak ada wali kelas atau guru BK terdaftar untuk siswa ID {$studentId}. Peringatan sistem dikirim ke Admin.");
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
