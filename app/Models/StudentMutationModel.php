<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentMutationModel extends Model
{
    protected $table = 'student_mutations';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'student_id', 'type', 'from_school', 'to_school',
        'from_class_id', 'to_class_id', 'mutation_date',
        'reason', 'letter_number', 'attachment',
        'status', 'approved_by', 'approved_at', 'note'
    ];

    public function getFiltered(array $params = [])
    {
        $builder = $this->select('student_mutations.*, students.nisn, students.nis, students.name as student_name,
                                  fc.name as from_class_name, tc.name as to_class_name')
                        ->join('students', 'students.id = student_mutations.student_id', 'left')
                        ->join('classes fc', 'fc.id = student_mutations.from_class_id', 'left')
                        ->join('classes tc', 'tc.id = student_mutations.to_class_id', 'left');

        if (!empty($params['type'])) {
            $builder->where('student_mutations.type', $params['type']);
        }
        if (!empty($params['status'])) {
            $builder->where('student_mutations.status', $params['status']);
        }
        if (!empty($params['date_from'])) {
            $builder->where('student_mutations.mutation_date >=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $builder->where('student_mutations.mutation_date <=', $params['date_to']);
        }
        if (!empty($params['search'])) {
            $builder->groupStart()
                ->like('students.name', $params['search'])
                ->orLike('students.nis', $params['search'])
                ->orLike('students.nisn', $params['search'])
                ->orLike('student_mutations.from_school', $params['search'])
                ->orLike('student_mutations.to_school', $params['search'])
                ->groupEnd();
        }

        return $builder->orderBy('student_mutations.mutation_date', 'DESC')
                       ->orderBy('student_mutations.created_at', 'DESC');
    }

    public function getWithStudent($id)
    {
        return $this->select('student_mutations.*, students.nisn, students.nis, students.name as student_name,
                              students.gender, students.birth_place, students.birth_date,
                              fc.name as from_class_name, tc.name as to_class_name,
                              u.username as approved_by_name')
                    ->join('students', 'students.id = student_mutations.student_id', 'left')
                    ->join('classes fc', 'fc.id = student_mutations.from_class_id', 'left')
                    ->join('classes tc', 'tc.id = student_mutations.to_class_id', 'left')
                    ->join('users u', 'u.id = student_mutations.approved_by', 'left')
                    ->find($id);
    }
}
