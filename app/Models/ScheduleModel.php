<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleModel extends Model
{
    protected $table = 'schedules';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'day_of_week',
        'class_id',
        'subject_id',
        'teacher_id',
        'start_time',
        'end_time',
        'academic_year_id'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getSchedulesByClass($classId, $academicYearId)
    {
        return $this->select('schedules.*, subjects.name as subject_name, teachers.name as teacher_name')
            ->join('subjects', 'subjects.id = schedules.subject_id', 'left')
            ->join('teachers', 'teachers.id = schedules.teacher_id', 'left')
            ->where('schedules.class_id', $classId)
            ->where('schedules.academic_year_id', $academicYearId)
            ->orderBy('schedules.day_of_week', 'ASC')
            ->orderBy('schedules.start_time', 'ASC')
            ->findAll();
    }

    public function getSchedulesByTeacher($teacherId, $academicYearId)
    {
        return $this->select('schedules.*, subjects.name as subject_name, classes.name as class_name')
            ->join('subjects', 'subjects.id = schedules.subject_id', 'left')
            ->join('classes', 'classes.id = schedules.class_id', 'left')
            ->where('schedules.teacher_id', $teacherId)
            ->where('schedules.academic_year_id', $academicYearId)
            ->orderBy('schedules.day_of_week', 'ASC')
            ->orderBy('schedules.start_time', 'ASC')
            ->findAll();
    }

    public function getScheduleForDay($dayOfWeek, $filters = [], $academicYearId = null)
    {
        $builder = $this->select('schedules.*, subjects.name as subject_name, classes.name as class_name, teachers.name as teacher_name')
            ->join('subjects', 'subjects.id = schedules.subject_id', 'left')
            ->join('classes', 'classes.id = schedules.class_id', 'left')
            ->join('teachers', 'teachers.id = schedules.teacher_id', 'left')
            ->where('schedules.day_of_week', $dayOfWeek);

        if ($academicYearId) {
            $builder->where('schedules.academic_year_id', $academicYearId);
        }

        if (!empty($filters['class_id'])) {
            $builder->where('schedules.class_id', $filters['class_id']);
        }

        if (!empty($filters['teacher_id'])) {
            $builder->where('schedules.teacher_id', $filters['teacher_id']);
        }

        return $builder->orderBy('schedules.start_time', 'ASC')->findAll();
    }

    public function getAllSchedules($filters = [], $academicYearId = null)
    {
        $builder = $this->select('schedules.*, subjects.name as subject_name, classes.name as class_name, teachers.name as teacher_name')
            ->join('subjects', 'subjects.id = schedules.subject_id', 'left')
            ->join('classes', 'classes.id = schedules.class_id', 'left')
            ->join('teachers', 'teachers.id = schedules.teacher_id', 'left');

        if ($academicYearId) {
            $builder->where('schedules.academic_year_id', $academicYearId);
        }

        if (!empty($filters['class_id'])) {
            $builder->where('schedules.class_id', $filters['class_id']);
        }

        if (!empty($filters['teacher_id'])) {
            $builder->where('schedules.teacher_id', $filters['teacher_id']);
        }

        return $builder->orderBy('schedules.day_of_week', 'ASC')
            ->orderBy('schedules.start_time', 'ASC')
            ->findAll();
    }

    public function getScheduleForDayDebug($dayOfWeek, $filters = [], $academicYearId = null)
    {
        // Debug version that returns query info
        $builder = $this->select('schedules.*, subjects.name as subject_name, classes.name as class_name, teachers.name as teacher_name')
            ->join('subjects', 'subjects.id = schedules.subject_id', 'left')
            ->join('classes', 'classes.id = schedules.class_id', 'left')
            ->join('teachers', 'teachers.id = schedules.teacher_id', 'left')
            ->where('schedules.day_of_week', $dayOfWeek);

        if ($academicYearId) {
            $builder->where('schedules.academic_year_id', $academicYearId);
        }

        if (!empty($filters['class_id'])) {
            $builder->where('schedules.class_id', $filters['class_id']);
        }

        if (!empty($filters['teacher_id'])) {
            $builder->where('schedules.teacher_id', $filters['teacher_id']);
        }

        $result = $builder->orderBy('schedules.start_time', 'ASC')->findAll();
        
        // Log the query
        log_message('debug', 'Schedule Query - Day: ' . $dayOfWeek . ', Academic Year: ' . $academicYearId . ', Results: ' . count($result));
        
        return $result;
    }

    public function getScheduleForDayWithFallback($dayOfWeek, $filters = [], $academicYearId = null)
    {
        // First try with academic year filter
        $result = $this->getScheduleForDay($dayOfWeek, $filters, $academicYearId);
        
        // If no results and academicYearId is provided, try without it
        if (empty($result) && $academicYearId) {
            log_message('debug', 'No schedules found for day ' . $dayOfWeek . ' with academic year ' . $academicYearId . '. Trying without academic year filter.');
            $result = $this->getScheduleForDay($dayOfWeek, $filters, null);
        }
        
        return $result;
    }
}
