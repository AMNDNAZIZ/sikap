<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table = 'teachers';
    protected $primaryKey = 'id';
    protected $useTimestamps = true; // pastikan tabel memiliki kolom created_at dan updated_at
    protected $allowedFields = [
        'name',
        'nip',
        'nuptk',
        'nik',
        'gender',
        'birth_place',
        'birth_date',
        'mother_name',
        'religion',
        'marital_status',
        'phone',
        'email',
        'address',
        'rt_rw',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'employment_status',
        'jenis_ptk',
        'appointing_agency',
        'appointment_sk',
        'appointment_tmt',
        'functional_position',
        'rank_grade',
        'certification_number',
        'certification_field',
        'certification_year',
        'photo',
        'user_id',
        'gemini_api_key',
        'ai_provider',
        'is_active',
    ];

    public function withUser()
    {
        return $this->select('teachers.*, users.username, users.email')
            ->join('users', 'users.id = teachers.user_id', 'left');
    }

    /**
     * Guru aktif untuk halaman publik/front-end.
     * Hanya tampilkan guru dengan is_active = 1.
     * Data subjects_list dan wali_class_name diambil dari tahun ajaran aktif.
     */
    public function getPublicTeachers()
    {
        $db = \Config\Database::connect();

        // Ambil tahun ajaran aktif
        $activeYear = $db->table('academic_years')
            ->where('is_active', 1)
            ->get()->getRowArray();
        $activeYearId = (int)($activeYear['id'] ?? 0);

        // Bangun kondisi join teaching_assignments dengan filter tahun ajaran aktif
        $taJoinCondition = $activeYearId
            ? "teaching_assignments.teacher_id = teachers.id AND teaching_assignments.academic_year_id = {$activeYearId}"
            : 'teaching_assignments.teacher_id = teachers.id';

        return $this->select('teachers.*,
                users.role_id,
                GROUP_CONCAT(DISTINCT subjects.code ORDER BY subjects.code SEPARATOR ", ") as subjects_list,
                GROUP_CONCAT(DISTINCT teaching_assignments.role) as roles_list,
                MAX(CASE WHEN teaching_assignments.role = "guru_kelas" THEN classes.name ELSE NULL END) as wali_class_name')
            ->join('users', 'users.id = teachers.user_id', 'left')
            ->join('teaching_assignments', $taJoinCondition, 'left')
            ->join('subjects', 'subjects.id = teaching_assignments.subject_id', 'left')
            ->join('classes', 'classes.id = teaching_assignments.class_id AND teaching_assignments.role = "guru_kelas"', 'left')
            ->where('teachers.is_active', 1)
            ->groupBy('teachers.id')
            ->findAll();
    }
}
