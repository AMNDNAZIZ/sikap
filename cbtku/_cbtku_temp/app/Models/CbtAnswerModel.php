<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class CbtAnswerModel extends Model
{
    protected $table         = 'cbt_answers';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['student_id', 'test_id', 'question_id', 'answer', 'version', 'updated_at_micro', 'is_doubtful', 'score'];
    protected $useTimestamps = false;
    protected $returnType    = 'array';

    /**
     * Simpan satu jawaban (insert or update)
     */
    public function saveAnswer(int $studentId, int $testId, int $questionId, $answer, $isDoubtful = null): bool
    {
        $data = [
            'student_id'  => $studentId,
            'test_id'     => $testId,
            'question_id' => $questionId,
            'answer'      => $answer,
        ];
        
        if ($isDoubtful !== null) {
            $data['is_doubtful'] = (int) $isDoubtful;
        }
        
        return $this->saveAnswersBulk([$data]);
    }

    /**
     * Simpan banyak jawaban sekaligus (lebih hemat query)
     * 🔒 SECURITY: Uses optimistic locking to prevent race conditions
     */
    public function saveAnswersBulk(array $answers): bool
    {
        if (empty($answers)) return true;
        
        $fields = ['student_id', 'test_id', 'question_id', 'answer', 'version', 'updated_at_micro', 'is_doubtful'];
        $placeholders = '(' . implode(',', array_fill(0, count($fields), '?')) . ')';
        $valuesSql = implode(',', array_fill(0, count($answers), $placeholders));
        
        // 🔒 SECURITY: Use version for optimistic locking to prevent race conditions
        // Only update if incoming version is >= current version
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(',', $fields) . ') VALUES ' . $valuesSql .
               ' ON DUPLICATE KEY UPDATE 
                 answer = IF(VALUES(version) >= version, VALUES(answer), answer),
                 version = IF(VALUES(version) >= version, VALUES(version), version),
                 updated_at_micro = IF(VALUES(version) >= version, VALUES(updated_at_micro), updated_at_micro),
                 is_doubtful = IF(VALUES(version) >= version, VALUES(is_doubtful), is_doubtful)';
        
        $params = [];
        $microtime = microtime(true);
        
        foreach ($answers as $r) {
            $params[] = $r['student_id'];
            $params[] = $r['test_id'];
            $params[] = $r['question_id'];
            $params[] = $r['answer'];
            $params[] = $r['version'] ?? 1;
            $params[] = $microtime;
            $params[] = $r['is_doubtful'] ?? 0;
        }
        
        try {
            $result = (bool)$this->db->query($sql, $params);
            if ($result) {
                log_message('debug', '[CbtAnswerModel::saveAnswersBulk] Saved ' . count($answers) . ' answers with version control');
            }
            return $result;
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
