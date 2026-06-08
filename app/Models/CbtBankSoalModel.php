<?php

namespace App\Models;

use CodeIgniter\Model;

class CbtBankSoalModel extends Model
{
    protected $table = 'cbt_question_banks';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'code',
        'subject_id',
        'teacher_id',
        'level',
        'total_questions',
        'total_pg',
        'total_pg_kompleks',
        'total_esai',
        'total_bs',
        'option_count',
        'is_active',
        'created_at',
        'updated_at',
        'raw_text'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // === Custom Queries ===
    // Masukkan ke dalam class CbtBankSoalModel
    public function getListWithCounts(?string $relatedType = null, ?int $relatedId = null): array
    {
        $db = db_connect();

        // 1) Tentukan nama tabel soal
        $possibleTables = ['cbt_questions', 'questions', 'qeep_soal'];
        $questionTable = null;
        foreach ($possibleTables as $t) {
            $r = $db->query(
                "SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
                [$t]
            )->getRow();
            if ($r && $r->c) {
                $questionTable = $t;
                break;
            }
        }

        // 2) Tentukan filter tambahan untuk guru
        $filterTeacherSQL = '';
        if ($relatedType === 'teacher' && !empty($relatedId)) {
            $filterTeacherSQL = "WHERE b.teacher_id = " . (int) $relatedId;
        }

        // 3) Jika tidak ada tabel soal
        if (!$questionTable) {
            $sql = "SELECT b.*, s.name AS subject_name, t.name AS teacher_name,
                           0 AS total_questions, 0 AS total_pg, 0 AS total_pg_kompleks, 0 AS total_esai, 0 AS total_bs
                    FROM cbt_question_banks b
                    LEFT JOIN subjects s ON s.id = b.subject_id
                    LEFT JOIN teachers t ON t.id = b.teacher_id
                    {$filterTeacherSQL}
                    ORDER BY b.id DESC";
            return $db->query($sql)->getResultArray();
        }

        // 4) Ambil kolom tabel soal
        $colsRes = $db->query(
            "SELECT column_name FROM information_schema.COLUMNS WHERE table_schema = DATABASE() AND table_name = ?",
            [$questionTable]
        )->getResultArray();
        $cols = array_column($colsRes, 'column_name');

        // 5) Buat sub-query sesuai struktur tabel soal
        if (in_array('question_type', $cols)) {
            $inner = "SELECT bank_id, COUNT(*) AS total_questions,
                             SUM(CASE WHEN question_type = 'pg' THEN 1 ELSE 0 END) AS total_pg,
                             SUM(CASE WHEN question_type = 'pg_kompleks' THEN 1 ELSE 0 END) AS total_pg_kompleks,
                             SUM(CASE WHEN question_type = 'benar_salah' THEN 1 ELSE 0 END) AS total_bs,
                             SUM(CASE WHEN question_type = 'esai' THEN 1 ELSE 0 END) AS total_esai
                      FROM {$questionTable}
                      GROUP BY bank_id";
            $joinOn = "q.bank_id = b.id";
            $useBankId = true;
        } elseif (in_array('correct_option', $cols) || in_array('option_a', $cols)) {
            $inner = "SELECT bank_id, COUNT(*) AS total_questions,
                             SUM(CASE WHEN COALESCE(correct_option,'') <> '' THEN 1 ELSE 0 END) AS total_pg,
                             SUM(CASE WHEN COALESCE(correct_option,'') = '' THEN 1 ELSE 0 END) AS total_esai
                      FROM {$questionTable}
                      GROUP BY bank_id";
            $joinOn = "q.bank_id = b.id";
            $useBankId = true;
        } elseif (in_array('XJenisSoal', $cols) && in_array('XKodeSoal', $cols)) {
            $inner = "SELECT XKodeSoal AS bank_code, COUNT(*) AS total_questions,
                             SUM(CASE WHEN XJenisSoal = 1 THEN 1 ELSE 0 END) AS total_pg,
                             SUM(CASE WHEN XJenisSoal <> 1 THEN 1 ELSE 0 END) AS total_esai
                      FROM {$questionTable}
                      GROUP BY XKodeSoal";
            $useBankId = false;
        } else {
            $inner = "SELECT bank_id, COUNT(*) AS total_questions, 0 AS total_pg, 0 AS total_esai
                      FROM {$questionTable}
                      GROUP BY bank_id";
            $joinOn = "q.bank_id = b.id";
            $useBankId = true;
        }

        // 6) Gabungkan hasil akhir
        if ($useBankId) {
            $sql = "SELECT b.*, 
                           s.name AS subject_name,
                           t.name AS teacher_name,
                           COALESCE(q.total_questions,0) AS total_questions,
                           COALESCE(q.total_pg,0) AS total_pg,
                           COALESCE(q.total_pg_kompleks,0) AS total_pg_kompleks,
                           COALESCE(q.total_bs,0) AS total_bs,
                           COALESCE(q.total_esai,0) AS total_esai
                    FROM cbt_question_banks b
                    LEFT JOIN subjects s ON s.id = b.subject_id
                    LEFT JOIN teachers t ON t.id = b.teacher_id
                    LEFT JOIN ({$inner}) q ON {$joinOn}
                    {$filterTeacherSQL}
                    ORDER BY b.id DESC";
        } else {
            $sql = "SELECT b.*, 
                           s.name AS subject_name,
                           t.name AS teacher_name,
                           COALESCE(q.total_questions,0) AS total_questions,
                           COALESCE(q.total_pg,0) AS total_pg,
                           COALESCE(q.total_pg_kompleks,0) AS total_pg_kompleks,
                           COALESCE(q.total_bs,0) AS total_bs,
                           COALESCE(q.total_esai,0) AS total_esai
                    FROM cbt_question_banks b
                    LEFT JOIN subjects s ON s.id = b.subject_id
                    LEFT JOIN teachers t ON t.id = b.teacher_id
                    LEFT JOIN ({$inner}) q ON q.bank_code = b.code
                    {$filterTeacherSQL}
                    ORDER BY b.id DESC";
        }

        return $db->query($sql)->getResultArray();
    }



    public function getWithSubjectTeacher($id)
    {
        return $this->select('cbt_question_banks.*, subjects.name as subject_name, teachers.name as teacher_name')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->join('teachers', 'teachers.id = cbt_question_banks.teacher_id', 'left')
            ->where('cbt_question_banks.id', $id)
            ->first();
    }
}
