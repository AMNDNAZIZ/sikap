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
     * Ambil student_id dari session — ikuti pola controller siswa lainnya di SIAKAD.
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
