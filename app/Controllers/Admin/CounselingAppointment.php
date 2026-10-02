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

        $this->appointmentModel->update($id, $updateData);

        return redirect()->to(base_url('admin/counseling-appointment'))->with('success', 'Status janji temu berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->appointmentModel->delete($id);
        return redirect()->to(base_url('admin/counseling-appointment'))->with('success', 'Janji temu berhasil dihapus.');
    }
}
