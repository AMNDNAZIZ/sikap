<?php

namespace App\Models;

use CodeIgniter\Model;

class TeachingJournalModel extends Model
{
    protected $table = 'teaching_journals';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'date',
        'teacher_id',
        'class_id',
        'subject_id',
        'atp_id',
        'notes'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getJournals($filters = [])
    {
        $builder = $this->select('teaching_journals.*, classes.name as class_name, subjects.name as subject_name, alur_tujuan_pembelajaran.lingkup_materi, cp_master.elemen')
            ->join('classes', 'classes.id = teaching_journals.class_id')
            ->join('subjects', 'subjects.id = teaching_journals.subject_id')
            ->join('alur_tujuan_pembelajaran', 'alur_tujuan_pembelajaran.id = teaching_journals.atp_id', 'left')
            ->join('cp_master', 'cp_master.id = alur_tujuan_pembelajaran.cp_master_id', 'left');

        if (!empty($filters['teacher_id'])) {
            $builder->where('teaching_journals.teacher_id', $filters['teacher_id']);
        }

        if (!empty($filters['class_id'])) {
            $builder->where('teaching_journals.class_id', $filters['class_id']);
        }

        if (!empty($filters['subject_id'])) {
            $builder->where('teaching_journals.subject_id', $filters['subject_id']);
        }

        // Filter tanggal
        if (!empty($filters['date_from'])) {
            $builder->where('teaching_journals.date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('teaching_journals.date <=', $filters['date_to']);
        }

        return $builder->orderBy('teaching_journals.date', 'DESC')
            ->orderBy('teaching_journals.created_at', 'DESC')
            ->findAll();
    }
}
