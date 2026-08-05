<?php

namespace App\Models;

use CodeIgniter\Model;

class EbookModel extends Model
{
    protected $table = 'ebooks';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'title', 'book_type', 'level', 'subject_id', 'religion',
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
     * Filter by level (also includes umum books with NULL level)
     */
    public function filterByLevel($level)
    {
        $this->where('ebooks.level', $level);
        return $this;
    }

    /**
     * Filter by level OR umum books (for student view)
     */
    public function filterByLevelOrUmum($level)
    {
        $this->groupStart()
             ->where('ebooks.level', $level)
             ->orWhere('ebooks.book_type', 'umum')
             ->groupEnd();
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

    /**
     * Filter by book type
     */
    public function filterByType($type)
    {
        if (in_array($type, ['mapel', 'umum'])) {
            $this->where('ebooks.book_type', $type);
        }
        return $this;
    }
}
