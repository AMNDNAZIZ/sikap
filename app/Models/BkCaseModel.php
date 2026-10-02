<?php

namespace App\Models;

use CodeIgniter\Model;

class BkCaseModel extends Model
{
    protected $table            = 'bk_cases';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'case_code',
        'student_id',
        'reporter_type',
        'reporter_id',
        'related_parties',
        'source',
        'category',
        'severity',
        'incident_date',
        'description',
        'confidential_notes',
        'status',
        'workflow_step',
        'is_confidential',
        'closed_at',
        'closed_notes',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil data kasus dengan relasi & pemisahan kerahasiaan data konseling rahasia.
     */
    public function getCasesWithDetails($filters = [], $isGuruBkOrAdmin = true)
    {
        // Mendukung argumen legacy ($studentId, $status, $limit)
        if (!is_array($filters)) {
            $studentId = func_get_arg(0) ?? null;
            $status    = func_num_args() > 1 ? func_get_arg(1) : null;
            $limit     = func_num_args() > 2 ? func_get_arg(2) : null;
            $filters   = [
                'student_id' => $studentId,
                'status'     => $status,
                'limit'      => $limit,
            ];
        }

        $builder = $this->db->table('bk_cases c')
            ->select('c.*, s.name as student_name, s.nisn, s.gender, cl.id as class_id, cl.name as class_name, t.name as reporter_name')
            ->join('students s', 's.id = c.student_id', 'left')
            ->join('classes cl', 'cl.id = s.class_id', 'left')
            ->join('teachers t', 't.id = c.reporter_id', 'left');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('s.name', $search)
                ->orLike('s.nisn', $search)
                ->orLike('c.case_code', $search)
                ->orLike('c.description', $search)
                ->groupEnd();
        }

        if (!empty($filters['student_id'])) {
            $builder->where('c.student_id', $filters['student_id']);
        }

        if (!empty($filters['class_id'])) {
            $builder->where('s.class_id', $filters['class_id']);
        }

        // Batasi ke daftar kelas tertentu (scope guru kelas SD yang merangkap BK).
        // Nilai array kosong → tidak ada kelas yang cocok → kembalikan nol baris.
        if (isset($filters['class_ids']) && is_array($filters['class_ids'])) {
            $builder->whereIn('s.class_id', $filters['class_ids'] ?: [0]);
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder->whereIn('c.status', $filters['status']);
            } else {
                $builder->where('c.status', $filters['status']);
            }
        }

        if (!empty($filters['severity'])) {
            $builder->where('c.severity', $filters['severity']);
        }

        if (!empty($filters['category'])) {
            $builder->where('c.category', $filters['category']);
        }

        if (!empty($filters['date'])) {
            $builder->where('c.incident_date', $filters['date']);
        }

        $builder->orderBy('c.created_at', 'DESC');

        if (!empty($filters['limit'])) {
            $builder->limit($filters['limit']);
        }

        $cases = $builder->get()->getResultArray();

        // Sensor catatan rahasia jika pengguna bukan Guru BK / Admin
        if (!$isGuruBkOrAdmin) {
            foreach ($cases as &$case) {
                if ($case['is_confidential'] == 1) {
                    $case['confidential_notes'] = '🔒 [CATATAN RAHASIA GURU BK - Hanya Dapat Diakses Oleh Konselor / Guru BK]';
                }
            }
        }

        return $cases;
    }

    /**
     * Get Case Statistics untuk Dashboard & Rekapitulasi
     */
    public function getStatistics()
    {
        $db = $this->db;
        $totalCases = $db->table('bk_cases')->countAllResults();

        $activeCases = $db->table('bk_cases')
            ->whereIn('status', ['REPORTED', 'VERIFIED', 'IN_ASSESSMENT', 'IN_PROGRESS', 'MONITORING'])
            ->countAllResults();

        $closedCases = $db->table('bk_cases')
            ->whereIn('status', ['RESOLVED', 'CLOSED'])
            ->countAllResults();

        $referralCases = $db->table('bk_cases')
            ->where('status', 'REFERRED')
            ->countAllResults();

        $severityCounts = [
            'Ringan' => $db->table('bk_cases')->where('severity', 'Ringan')->countAllResults(),
            'Sedang' => $db->table('bk_cases')->where('severity', 'Sedang')->countAllResults(),
            'Berat'  => $db->table('bk_cases')->where('severity', 'Berat')->countAllResults(),
        ];

        return [
            'total'     => $totalCases,
            'active'    => $activeCases,
            'closed'    => $closedCases,
            'referral'  => $referralCases,
            'severity'  => $severityCounts,
        ];
    }
}
