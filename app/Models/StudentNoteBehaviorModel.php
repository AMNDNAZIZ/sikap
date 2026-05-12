<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentNoteBehaviorModel extends Model
{
    protected $table      = 'student_note_behaviors';
    protected $primaryKey = 'id';
    protected $allowedFields = ['note_id', 'behavior_id'];

    public function getBehaviorsByNote($noteId)
    {
        return $this->select('behaviors.*')
            ->join('behaviors', 'behaviors.id = student_note_behaviors.behavior_id')
            ->where('student_note_behaviors.note_id', $noteId)
            ->findAll();
    }

    public function getAccumulatedPoints($studentId, $yearId = null, $semester = null)
    {
        $builder = $this->select('SUM(behaviors.points) as total_points')
            ->join('student_notes', 'student_notes.id = student_note_behaviors.note_id')
            ->join('behaviors', 'behaviors.id = student_note_behaviors.behavior_id')
            ->where('student_notes.student_id', $studentId);

        if ($yearId) {
            $builder->where('student_notes.academic_year_id', $yearId);
        }

        if ($semester) {
            $builder->where('student_notes.semester', $semester);
        }

        $result = $builder->first();
        return $result['total_points'] ?? 0;
    }
}
