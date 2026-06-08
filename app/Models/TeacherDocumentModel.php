<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherDocumentModel extends Model
{
    protected $table         = 'teacher_documents';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['teacher_id', 'title', 'filename', 'original_name', 'file_type', 'file_size'];
    protected $useTimestamps = true;

    public function getByTeacher($teacherId)
    {
        return $this->where('teacher_id', $teacherId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}
