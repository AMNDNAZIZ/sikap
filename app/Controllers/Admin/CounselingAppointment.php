<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BkAppointmentModel;

class CounselingAppointment extends BaseController
{
    protected $appointmentModel;

    public function __construct()
    {
        $this->appointmentModel = new BkAppointmentModel();
    }

    public function index()
    {
        $data = [
            'title'        => 'Manajemen Janji Temu Konseling',
            'appointments' => $this->appointmentModel->getAppointmentsWithDetails()
        ];

        return view('admin/counseling_appointment/index', $data);
    }

    public function updateStatus($id)
    {
        $status     = $this->request->getPost('status');
        $datetimeRaw = $this->request->getPost('proposed_date');

        $updateData = [
            'status'       => $status,
            'counselor_id' => session()->get('user')['id'],
        ];

        // Split datetime-local input ke requested_date + requested_time
        if ($datetimeRaw) {
            $updateData['requested_date'] = date('Y-m-d', strtotime($datetimeRaw));
            $updateData['requested_time'] = date('H:i:s', strtotime($datetimeRaw));
        }

        $appointment = $this->appointmentModel->find($id);

        $this->appointmentModel->update($id, $updateData);

        // Notifikasi ke Siswa dan Orang Tua
        if ($appointment) {
            try {
                helper('notification');
                $dateStr = !empty($updateData['requested_date']) ? date('d M Y', strtotime($updateData['requested_date'])) : (!empty($appointment['requested_date']) ? date('d M Y', strtotime($appointment['requested_date'])) : '');
                $timeStr = !empty($updateData['requested_time']) ? substr($updateData['requested_time'], 0, 5) : (!empty($appointment['requested_time']) ? substr($appointment['requested_time'], 0, 5) : '');
                $jadwalStr = $dateStr ? " · Jadwal: {$dateStr} {$timeStr} WIB" : '';

                notify_student_and_parents(
                    (int) $appointment['student_id'],
                    'info',
                    'Janji Temu BK: ' . ucfirst($status),
                    "Topik: {$appointment['topic']}{$jadwalStr} · Status: {$status}",
                    base_url('siswa/counseling'),
                    'bk_appointment',
                    (int) $id
                );
            } catch (\Throwable $e) {
                log_message('error', '[CounselingAppointment::updateStatus] Gagal kirim notif: ' . $e->getMessage());
            }
        }

        return redirect()->to(base_url('admin/counseling-appointment'))->with('success', 'Status janji temu berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->appointmentModel->delete($id);
        return redirect()->to(base_url('admin/counseling-appointment'))->with('success', 'Janji temu berhasil dihapus.');
    }
}
