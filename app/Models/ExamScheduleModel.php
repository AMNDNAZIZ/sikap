<?php

namespace App\Models;

use CodeIgniter\Model;

class ExamScheduleModel extends Model
{
    protected $table            = 'exam_schedules';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'subject_id',
        'class_id',
        'exam_date',
        'start_time',
        'end_time',
        'description'
    ];

    /**
     * Ambil jadwal dengan relasi ke mata pelajaran dan kelas.
     * Otomatis urut berdasarkan tanggal & waktu.
     */
    public function withRelations()
    {
        return $this->select(
                'exam_schedules.*, 
                 subjects.name AS subject_name, 
                 classes.name AS class_name'
            )
            ->join('subjects', 'subjects.id = exam_schedules.subject_id', 'left')
            ->join('classes', 'classes.id = exam_schedules.class_id', 'left')
            ->orderBy('exam_date', 'ASC')
            ->orderBy('start_time', 'ASC');
    }

    /**
     * Ambil semua jadwal dengan format siap tampil (sudah join relasi)
     */
    public function getAll()
    {
        return $this->withRelations()->findAll();
    }

    /**
     * Ambil satu jadwal berdasarkan ID dengan relasi
     */
    public function getById($id)
    {
        return $this->withRelations()
                    ->where('exam_schedules.id', $id)
                    ->first();
    }

    /**
     * Ambil jadwal ujian berdasarkan kelas tertentu
     */
    public function getByClass($classId)
    {
        return $this->withRelations()
                    ->where('exam_schedules.class_id', $classId)
                    ->findAll();
    }

    /**
     * Ambil jadwal ujian untuk rentang tanggal tertentu
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->withRelations()
                    ->where('exam_date >=', $startDate)
                    ->where('exam_date <=', $endDate)
                    ->findAll();
    }
}
