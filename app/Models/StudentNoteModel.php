<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentNoteModel extends Model
{
    protected $table      = 'student_notes';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id', 'teacher_id', 'note',
        'academic_year_id', 'semester', 'created_at'
    ];
    protected $useTimestamps = false;

    public function getByStudent($studentId, $yearId = null, $semester = null)
    {
        $builder = $this->select('student_notes.*, teachers.name as teacher_name')
            ->join('teachers', 'teachers.id = student_notes.teacher_id')
            ->where('student_notes.student_id', $studentId);

        if ($yearId) {
            $builder->where('student_notes.academic_year_id', $yearId);
        }

        if ($semester) {
            $builder->where('student_notes.semester', $semester);
        }

        return $builder->orderBy('student_notes.created_at', 'DESC')->findAll();
    }

    public function getWithBehaviors($noteId)
    {
        return $this->select('student_notes.*, GROUP_CONCAT(behaviors.name SEPARATOR ", ") as behaviors')
            ->join('student_note_behaviors', 'student_note_behaviors.note_id = student_notes.id', 'left')
            ->join('behaviors', 'behaviors.id = student_note_behaviors.behavior_id', 'left')
            ->where('student_notes.id', $noteId)
            ->groupBy('student_notes.id')
            ->first();
    }
}
