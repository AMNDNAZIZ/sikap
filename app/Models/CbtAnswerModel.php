<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class CbtAnswerModel extends Model
{
    protected $table = 'cbt_answers';
    protected $primaryKey = 'id';
    protected $allowedFields = ['student_id', 'test_id', 'question_id', 'answer', 'score'];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    /**
     * Simpan satu jawaban (insert or update)
     */
    public function saveAnswer(int $studentId, int $testId, int $questionId, $answer): bool
    {
        return $this->saveAnswersBulk([
            [
                'student_id' => $studentId,
                'test_id' => $testId,
                'question_id' => $questionId,
                'answer' => $answer,
            ]
        ]);
    }

    /**
     * Simpan banyak jawaban sekaligus (lebih hemat query)
     */
    public function saveAnswersBulk(array $answers): bool
    {
        if (empty($answers))
            return true;
        $fields = ['student_id', 'test_id', 'question_id', 'answer'];
        $placeholders = '(' . implode(',', array_fill(0, count($fields), '?')) . ')';
        $valuesSql = implode(',', array_fill(0, count($answers), $placeholders));
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(',', $fields) . ') VALUES ' . $valuesSql .
            ' ON DUPLICATE KEY UPDATE answer = VALUES(answer)';
        $params = [];
        foreach ($answers as $r) {
            foreach ($fields as $f)
                $params[] = $r[$f];
        }
        try {
            return (bool) $this->db->query($sql, $params);
        } catch (\Throwable $e) {
            log_message('error', '[CbtAnswerModel::saveAnswersBulk] ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Ambil semua jawaban siswa
     */
    public function getStudentAnswers(int $studentId, int $testId): array
    {
        $rows = $this->where(['student_id' => $studentId, 'test_id' => $testId])->findAll();
        return array_column($rows, 'answer', 'question_id');
    }
}
