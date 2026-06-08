<?php

namespace App\Models;

use CodeIgniter\Model;

class CbtTestStatusModel extends Model
{
    protected $table = 'cbt_test_status';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'bank_id',
        'exam_name_id',
        'class_codes',
        'semester',
        'subject_type',
        'religion',
        'show_pg_count',
        'show_esai_count',
        'show_pg_kompleks_count',
        'show_bs_count',
        'bobot_pg',
        'bobot_esai',
        'bobot_pg_kompleks',
        'bobot_bs',
        'shuffle_question',
        'shuffle_option',
        'finish_button_lock',
        'start_time',
        'duration',
        'end_time',
        'show_token',
        'show_score',
        'token',
        'anti_cheat',
        'audio_limit',
        'is_active',
        'is_paused',
        'is_visible',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;

    public function getAvailableExams($className)
    {
        $now = date('Y-m-d H:i:s');

        return $this->select('
                cbt_test_status.*, 
                subjects.name AS subject_name, 
                cbt_exam_names.name AS exam_name
            ')
            ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
            ->where('cbt_test_status.is_visible', 1)
            ->where('cbt_test_status.is_active', 1)
            ->where("cbt_test_status.start_time <=", $now)
            ->where("cbt_test_status.end_time >=", $now)
            ->groupStart()
            ->like('cbt_test_status.class_codes', $className)
            ->groupEnd()
            ->orderBy('cbt_test_status.start_time', 'ASC')
            ->findAll();
    }
}
