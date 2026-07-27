<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\EbookModel;
use App\Libraries\EbookAccessService;

class Ebook extends BaseController
{
    protected $ebookModel;
    protected $accessService;
    protected $db;

    public function __construct()
    {
        $this->ebookModel = new EbookModel();
        $this->accessService = new EbookAccessService();
        $this->db = \Config\Database::connect();
    }

    /**
     * Daftar buku digital siswa (filtered by level + religion, plus umum books)
     */
    public function index()
    {
        $user = session()->get('user');
        $studentData = $this->getStudentData($user);

        // If student has no active class
        if (empty($studentData)) {
            return view('siswa/ebook/index', [
                'books' => [],
                'subjects' => [],
                'noActiveClass' => true,
                'search' => '',
                'subjectFilter' => '',
            ]);
        }

        $search = $this->request->getGet('search');
        $subjectFilter = $this->request->getGet('subject_id');

        $builder = $this->ebookModel->withSubject();

        // Filter: student's level OR umum books
        $builder->filterByLevelOrUmum($studentData['level']);

        // Filter out religion books that don't match student's religion
        // But umum books skip religion filter entirely
        $builder->groupStart()
            ->where('ebooks.book_type', 'umum')
            ->orGroupStart()
                ->where('ebooks.religion IS NULL')
                ->orWhere('ebooks.religion', '')
                ->orWhere('ebooks.religion', $studentData['religion'] ?? '')
            ->groupEnd()
        ->groupEnd();

        if (!empty($search)) {
            $builder->search($search);
        }
        if (!empty($subjectFilter)) {
            $builder->filterBySubject($subjectFilter);
        }

        $books = $builder->orderBy('ebooks.title', 'ASC')->findAll();

        // Subjects for filter
        $subjects = $this->db->table('subjects')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return view('siswa/ebook/index', [
            'books' => $books,
            'subjects' => $subjects,
            'noActiveClass' => false,
            'search' => $search,
            'subjectFilter' => $subjectFilter,
        ]);
    }

    /**
     * PDF viewer page
     */
    public function read($id)
    {
        $user = session()->get('user');
        $book = $this->ebookModel->withSubject()->find($id);

        if (!$book) {
            return redirect()->to('/siswa/ebook')->with('error', 'Buku tidak ditemukan.');
        }

        if (!$this->accessService->canAccess($user, $book)) {
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        return view('siswa/ebook/read', [
            'book' => $book,
        ]);
    }

    /**
     * Download file - redirect to EbookFile::download
     */
    public function download($id)
    {
        $user = session()->get('user');
        $book = $this->ebookModel->find($id);

        if (!$book) {
            return redirect()->to('/siswa/ebook')->with('error', 'Buku tidak ditemukan.');
        }

        if (!$this->accessService->canAccess($user, $book)) {
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        return redirect()->to('/admin/ebook/file/download/' . $id);
    }

    /**
     * Get student data (level + religion)
     */
    protected function getStudentData(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);

        $result = $this->db->table('students')
            ->select('students.religion, classes.level')
            ->join('student_records', 'student_records.student_id = students.id', 'inner')
            ->join('classes', 'classes.id = student_records.class_id', 'inner')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'inner')
            ->where('students.user_id', $userId)
            ->where('student_records.status', 'aktif')
            ->where('academic_years.is_active', 1)
            ->get()
            ->getRowArray();

        return $result ?? [];
    }
}
