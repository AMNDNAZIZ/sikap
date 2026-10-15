<?php

namespace App\Models;

use CodeIgniter\Model;

class RaporNoteModel extends Model
{
    protected $table            = 'rapor_notes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'student_id',
        'academic_year_id',
        'semester',
        'class_id',
        'teacher_note',
        'promotion_status',
        'promotion_class_name',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil catatan wali kelas untuk seluruh siswa di suatu kelas & semester
     */
    public function getNotesByClass(int $classId, int $yearId, string $semester): array
    {
        $rows = $this->where('class_id', $classId)
            ->where('academic_year_id', $yearId)
            ->where('semester', (string) $semester)
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['student_id']] = $row;
        }
        return $map;
    }

    /**
     * Upsert catatan wali kelas per siswa
     */
    public function saveNote(array $data): bool
    {
        $existing = $this->where('student_id', (int) $data['student_id'])
            ->where('academic_year_id', (int) $data['academic_year_id'])
            ->where('semester', (string) $data['semester'])
            ->first();

        if ($existing) {
            return (bool) $this->update($existing['id'], $data);
        }

        return (bool) $this->insert($data);
    }
}
