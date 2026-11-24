<?php

namespace App\Models;

use CodeIgniter\Model;

class BkAppointmentModel extends Model
{
    protected $table            = 'bk_appointments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    // Status: Pending, Approved, Rejected, Completed (sesuai ENUM di migration)
    protected $allowedFields    = [
        'student_id', 'topic', 'description', 'requested_date', 'requested_time',
        'status', 'counselor_id'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get appointments with student & counselor details
     */
    public function getAppointmentsWithDetails($counselor_id = null, $status = null)
    {
        $activeYearId = null;
        try {
            $ay = (new \App\Models\AcademicYearModel())->getActiveYear();
            $activeYearId = $ay['id'] ?? null;
        } catch (\Throwable $e) {}

        $builder = $this->select('bk_appointments.*, students.name as student_name, students.nis, COALESCE(c_active.name, c_rec.name, c_direct.name) as class_name, users.fullname as counselor_name')
                        ->join('students', 'students.id = bk_appointments.student_id')
                        ->join('student_records sr_active', 'sr_active.student_id = students.id AND sr_active.status = "aktif"' . ($activeYearId ? ' AND sr_active.academic_year_id = ' . (int)$activeYearId : ''), 'left')
                        ->join('classes c_active', 'c_active.id = sr_active.class_id', 'left')
                        ->join('student_records sr_rec', 'sr_rec.student_id = students.id AND sr_rec.status = "aktif"', 'left')
                        ->join('classes c_rec', 'c_rec.id = sr_rec.class_id', 'left')
                        ->join('classes c_direct', 'c_direct.id = students.class_id', 'left')
                        ->join('users', 'users.id = bk_appointments.counselor_id', 'left')
                        ->groupBy('bk_appointments.id');

        if ($counselor_id) {
            $builder->where('bk_appointments.counselor_id', $counselor_id);
        }

        if ($status) {
            if ($status == 'active') {
                $builder->whereIn('bk_appointments.status', ['Pending', 'Approved']);
            } else {
                $builder->where('bk_appointments.status', $status);
            }
        }

        return $builder->orderBy('bk_appointments.created_at', 'DESC')->findAll();
    }

    /**
     * Get appointments for a specific student
     */
    public function getStudentAppointments($student_id)
    {
        return $this->select('bk_appointments.*, users.fullname as counselor_name')
                    ->join('users', 'users.id = bk_appointments.counselor_id', 'left')
                    ->where('bk_appointments.student_id', $student_id)
                    ->orderBy('bk_appointments.created_at', 'DESC')
                    ->findAll();
    }
}
