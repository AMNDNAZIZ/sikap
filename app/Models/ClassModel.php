<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassModel extends Model
{
    protected $table = 'classes';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'level', 'teacher_id', 'description', 'is_active'];

    /**
     * Ambil daftar kelas yang aktif (is_active = 1), diurutkan level → nama.
     */
    public function getActiveClasses(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('level', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Ambil kelas aktif yang benar-benar digunakan pada tahun ajaran tertentu
     * (ada di teaching_assignments atau student_records untuk tahun ajaran tsb).
     * Jika $academicYearId null/0, fallback ke getActiveClasses().
     */
    public function getClassesByAcademicYear(int $academicYearId): array
    {
        if (!$academicYearId) {
            return $this->getActiveClasses();
        }

        $db = \Config\Database::connect();

        // Ambil class_id unik yang muncul di teaching_assignments tahun ajaran tsb
        $taClassIds = $db->table('teaching_assignments')
            ->select('class_id')
            ->where('academic_year_id', $academicYearId)
            ->groupBy('class_id')
            ->get()->getResultArray();

        // Ambil class_id unik dari student_records tahun ajaran tsb
        $srClassIds = $db->table('student_records')
            ->select('class_id')
            ->where('academic_year_id', $academicYearId)
            ->groupBy('class_id')
            ->get()->getResultArray();

        $classIds = array_unique(array_merge(
            array_column($taClassIds, 'class_id'),
            array_column($srClassIds, 'class_id')
        ));

        if (empty($classIds)) {
            return $this->getActiveClasses();
        }

        return $this->whereIn('id', $classIds)
            ->where('is_active', 1)
            ->orderBy('level', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
