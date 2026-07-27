<?php

namespace App\Models;

use CodeIgniter\Model;

class EbookModel extends Model
{
    protected $table = 'ebooks';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'title', 'level', 'subject_id', 'religion',
        'description', 'filename', 'original_filename',
        'file_size', 'uploaded_by'
    ];

    /**
     * Join subjects table to get subject name
     */
    public function withSubject()
    {
        $this->select('ebooks.*, subjects.name as subject_name')
             ->join('subjects', 'subjects.id = ebooks.subject_id', 'left');
        return $this;
    }

    /**
     * Search by title LIKE
     */
    public function searchByTitle($keyword)
    {
        $this->like('ebooks.title', $keyword);
        return $this;
    }

    /**
     * Search by title OR subject name LIKE
     */
    public function search($keyword)
    {
        $this->groupStart()
             ->like('ebooks.title', $keyword)
             ->orLike('subjects.name', $keyword)
             ->groupEnd();
        return $this;
    }

    /**
     * Filter by level
     */
    public function filterByLevel($level)
    {
        $this->where('ebooks.level', $level);
        return $this;
    }

    /**
     * Filter by subject
     */
    public function filterBySubject($subjectId)
    {
        $this->where('ebooks.subject_id', $subjectId);
        return $this;
    }
}
