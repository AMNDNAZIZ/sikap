<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectMaterialModel extends Model
{
    protected $table            = 'subject_materials';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'subject_id',
        'year_id',
        'semester',
        'title',
        'description',
    ];

    /**
     * Ambil semua materi beserta nama mapel dan tahun ajaran
     */
    public function getAllBySubject($subjectId)
    {
        return $this->select('subject_materials.*, subjects.name as subject_name, academic_years.year as year_name')
            ->join('subjects', 'subjects.id = subject_materials.subject_id')
            ->join('academic_years', 'academic_years.id = subject_materials.year_id')
            ->where('subject_materials.subject_id', $subjectId)
            ->orderBy('semester', 'ASC')
            ->findAll();
    }

    /**
     * Ambil satu materi lengkap beserta nama mapel dan tahun ajaran
     */
    public function getDetail($id)
    {
        return $this->select('subject_materials.*, subjects.name as subject_name, academic_years.year as year_name')
            ->join('subjects', 'subjects.id = subject_materials.subject_id')
            ->join('academic_years', 'academic_years.id = subject_materials.year_id')
            ->where('subject_materials.id', $id)
            ->first();
    }
}
