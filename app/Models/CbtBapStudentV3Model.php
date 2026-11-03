<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtBapStudentV3Model
 *
 * ESPATA Assessment Intelligence V3 - Digital BAP Student Attendance Records.
 * Binds individual student physical attendance claims to an authenticated BAP document.
 */
class CbtBapStudentV3Model extends Model
{
    protected $table            = 'cbt_bap_students_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'bap_id',
        'student_id',
        'reported_status',
        'proctor_notes',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    public const STATUS_HADIR       = 'HADIR';
    public const STATUS_TIDAK_HADIR = 'TIDAK_HADIR';
    public const STATUS_SAKIT       = 'SAKIT';
    public const STATUS_IZIN        = 'IZIN';
    public const STATUS_DISPENSASI  = 'DISPENSASI';

    /**
     * Get all student records for a BAP document.
     */
    public function getStudentsByBap(int $bapId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            return [];
        }

        $builder = $this->builder();
        if ($db->tableExists('students')) {
            $builder->select('cbt_bap_students_v3.*, students.name as student_name, students.nisn, students.nis')
                ->join('students', 'students.id = cbt_bap_students_v3.student_id', 'left');
            return $builder->where('cbt_bap_students_v3.bap_id', $bapId)
                ->orderBy('students.name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $this->where('bap_id', $bapId)
            ->orderBy('student_id', 'ASC')
            ->findAll();
    }

    /**
     * Upsert student attendance status in BAP.
     */
    public function upsertStudentStatus(int $bapId, int $studentId, string $status, ?string $notes = null): int
    {
        $existing = $this->where('bap_id', $bapId)
            ->where('student_id', $studentId)
            ->first();

        $data = [
            'bap_id'          => $bapId,
            'student_id'      => $studentId,
            'reported_status' => $status,
            'proctor_notes'   => $notes,
        ];

        if ($existing) {
            $this->update($existing['id'], $data);
            return (int)$existing['id'];
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return (int)$this->insert($data, true);
    }
}

