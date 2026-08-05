<?php

namespace App\Models;

use CodeIgniter\Model;

class KktpModel extends Model
{
    protected $table = 'kktp';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'class_id', 'subject_id', 'tp_id', 'tujuan_pembelajaran',
        'kriteria_1_interval', 'kriteria_1_label', 'kriteria_1_intervensi',
        'kriteria_2_interval', 'kriteria_2_label', 'kriteria_2_intervensi',
        'kriteria_3_interval', 'kriteria_3_label', 'kriteria_3_intervensi',
        'kriteria_4_interval', 'kriteria_4_label', 'kriteria_4_intervensi',
    ];
    protected $useTimestamps = true;

    /**
     * Ambil KKTP untuk kelas + mapel tertentu
     */
    public function getByClassSubject(int $classId, int $subjectId): array
    {
        return $this->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
