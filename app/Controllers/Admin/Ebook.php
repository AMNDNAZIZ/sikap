<?php

namespace App\Controllers\Admin;

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
     * Daftar buku digital
     */
    public function index()
    {
        $user = session()->get('user');
        $allowedLevels = $this->accessService->getAllowedLevels($user);

        // If guru and no allowed levels (not wali kelas), deny access
        if ((int) $user['role_id'] === 3 && empty($allowedLevels)) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke Perpustakaan Digital.');
        }

        $search = $this->request->getGet('search');
        $levelFilter = $this->request->getGet('level');
        $subjectFilter = $this->request->getGet('subject_id');

        $builder = $this->ebookModel->withSubject();

        // Wali kelas: filter by allowed levels only
        if ((int) $user['role_id'] === 3) {
            $builder->whereIn('ebooks.level', $allowedLevels);
        }

        if (!empty($search)) {
            $builder->search($search);
        }
        if (!empty($levelFilter)) {
            $builder->filterByLevel($levelFilter);
        }
        if (!empty($subjectFilter)) {
            $builder->filterBySubject($subjectFilter);
        }

        $books = $builder->orderBy('ebooks.created_at', 'DESC')->findAll();

        // Get subjects for filter dropdown
        $subjects = $this->db->table('subjects')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return view('admin/ebook/index', [
            'books' => $books,
            'subjects' => $subjects,
            'search' => $search,
            'levelFilter' => $levelFilter,
            'subjectFilter' => $subjectFilter,
            'allowedLevels' => $allowedLevels,
        ]);
    }

    /**
     * Form upload buku baru
     */
    public function create()
    {
        $user = session()->get('user');
        $allowedLevels = $this->accessService->getAllowedLevels($user);

        if ((int) $user['role_id'] === 3 && empty($allowedLevels)) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke Perpustakaan Digital.');
        }

        $subjects = $this->db->table('subjects')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return view('admin/ebook/create', [
            'subjects' => $subjects,
            'allowedLevels' => $allowedLevels,
        ]);
    }

    /**
     * Proses upload buku
     */
    public function store()
    {
        $user = session()->get('user');

        // Validate file
        $file = $this->request->getFile('pdf_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->withInput()->with('error', 'File PDF wajib diunggah.');
        }

        // Check MIME type
        if ($file->getMIMEType() !== 'application/pdf') {
            return redirect()->back()->withInput()->with('error', 'Format file harus PDF.');
        }

        // Check size (20MB = 20971520 bytes)
        if ($file->getSize() > 20971520) {
            return redirect()->back()->withInput()->with('error', 'Ukuran file melebihi batas maksimum 20MB.');
        }

        // Validate metadata
        $rules = [
            'title' => 'required|max_length[255]',
            'level' => 'required|integer|greater_than[0]|less_than[7]',
            'subject_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $title = $this->request->getPost('title');
        $level = (int) $this->request->getPost('level');
        $subjectId = (int) $this->request->getPost('subject_id');
        $description = $this->request->getPost('description');

        // Check manage permission
        if (!$this->accessService->canManage($user, $level)) {
            return redirect()->back()->withInput()
                ->with('error', 'Anda hanya dapat mengelola buku untuk level kelas yang diwalikan.');
        }

        // Verify subject exists
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();
        if (!$subject) {
            return redirect()->back()->withInput()->with('error', 'Mata pelajaran tidak valid.');
        }

        // Handle religion field
        $religion = null;
        if (!empty($subject['religion'])) {
            $religion = $this->request->getPost('religion');
            if (empty($religion)) {
                return redirect()->back()->withInput()->with('error', 'Field agama wajib diisi untuk mata pelajaran ini.');
            }
            $validReligions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
            if (!in_array($religion, $validReligions)) {
                return redirect()->back()->withInput()->with('error', 'Nilai agama tidak valid.');
            }
        }

        // Generate UUID filename
        $uuid = bin2hex(random_bytes(16));
        $newFilename = $uuid . '.pdf';

        // Move file to storage
        $uploadPath = WRITEPATH . 'uploads/ebooks/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $newFilename);

        // Save record
        $this->ebookModel->insert([
            'title' => $title,
            'level' => $level,
            'subject_id' => $subjectId,
            'religion' => $religion,
            'description' => $description,
            'filename' => $newFilename,
            'original_filename' => $file->getClientName(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $user['id'],
        ]);

        log_message('info', "Ebook uploaded: {$title} by user #{$user['id']}");

        return redirect()->to('/admin/ebook')->with('success', 'Buku berhasil diunggah.');
    }

    /**
     * Form edit metadata buku
     */
    public function edit($id)
    {
        $user = session()->get('user');
        $book = $this->ebookModel->find($id);

        if (!$book) {
            return redirect()->to('/admin/ebook')->with('error', 'Buku tidak ditemukan.');
        }

        if (!$this->accessService->canManage($user, (int) $book['level'])) {
            return redirect()->to('/admin/ebook')
                ->with('error', 'Anda hanya dapat mengelola buku untuk level kelas yang diwalikan.');
        }

        $allowedLevels = $this->accessService->getAllowedLevels($user);
        $subjects = $this->db->table('subjects')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return view('admin/ebook/edit', [
            'book' => $book,
            'subjects' => $subjects,
            'allowedLevels' => $allowedLevels,
        ]);
    }

    /**
     * Update metadata buku
     */
    public function update($id)
    {
        $user = session()->get('user');
        $book = $this->ebookModel->find($id);

        if (!$book) {
            return redirect()->to('/admin/ebook')->with('error', 'Buku tidak ditemukan.');
        }

        if (!$this->accessService->canManage($user, (int) $book['level'])) {
            return redirect()->back()->with('error', 'Anda hanya dapat mengelola buku untuk level kelas yang diwalikan.');
        }

        $rules = [
            'title' => 'required|max_length[255]',
            'level' => 'required|integer|greater_than[0]|less_than[7]',
            'subject_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $title = $this->request->getPost('title');
        $level = (int) $this->request->getPost('level');
        $subjectId = (int) $this->request->getPost('subject_id');
        $description = $this->request->getPost('description');

        // Check manage permission for new level
        if (!$this->accessService->canManage($user, $level)) {
            return redirect()->back()->withInput()
                ->with('error', 'Anda hanya dapat mengelola buku untuk level kelas yang diwalikan.');
        }

        // Verify subject
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();
        if (!$subject) {
            return redirect()->back()->withInput()->with('error', 'Mata pelajaran tidak valid.');
        }

        // Handle religion
        $religion = null;
        if (!empty($subject['religion'])) {
            $religion = $this->request->getPost('religion');
            if (empty($religion)) {
                return redirect()->back()->withInput()->with('error', 'Field agama wajib diisi untuk mata pelajaran ini.');
            }
            $validReligions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
            if (!in_array($religion, $validReligions)) {
                return redirect()->back()->withInput()->with('error', 'Nilai agama tidak valid.');
            }
        }

        $this->ebookModel->update($id, [
            'title' => $title,
            'level' => $level,
            'subject_id' => $subjectId,
            'religion' => $religion,
            'description' => $description,
        ]);

        return redirect()->to('/admin/ebook')->with('success', 'Buku berhasil diperbarui.');
    }

    /**
     * Replace PDF file
     */
    public function replace($id)
    {
        $user = session()->get('user');
        $book = $this->ebookModel->find($id);

        if (!$book) {
            return redirect()->to('/admin/ebook')->with('error', 'Buku tidak ditemukan.');
        }

        if (!$this->accessService->canManage($user, (int) $book['level'])) {
            return redirect()->back()->with('error', 'Anda hanya dapat mengelola buku untuk level kelas yang diwalikan.');
        }

        $file = $this->request->getFile('pdf_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File PDF wajib diunggah.');
        }

        if ($file->getMIMEType() !== 'application/pdf') {
            return redirect()->back()->with('error', 'Format file harus PDF.');
        }

        if ($file->getSize() > 20971520) {
            return redirect()->back()->with('error', 'Ukuran file melebihi batas maksimum 20MB.');
        }

        // Delete old file
        $oldFilePath = WRITEPATH . 'uploads/ebooks/' . $book['filename'];
        if (is_file($oldFilePath)) {
            unlink($oldFilePath);
        }

        // Generate new UUID filename
        $uuid = bin2hex(random_bytes(16));
        $newFilename = $uuid . '.pdf';

        $uploadPath = WRITEPATH . 'uploads/ebooks/';
        $file->move($uploadPath, $newFilename);

        $this->ebookModel->update($id, [
            'filename' => $newFilename,
            'original_filename' => $file->getClientName(),
            'file_size' => $file->getSize(),
        ]);

        log_message('info', "Ebook file replaced: #{$id} by user #{$user['id']}");

        return redirect()->to('/admin/ebook/edit/' . $id)->with('success', 'File PDF berhasil diganti.');
    }

    /**
     * Hapus buku
     */
    public function delete($id)
    {
        $user = session()->get('user');
        $book = $this->ebookModel->find($id);

        if (!$book) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Buku tidak ditemukan.']);
            }
            return redirect()->to('/admin/ebook')->with('error', 'Buku tidak ditemukan.');
        }

        if (!$this->accessService->canManage($user, (int) $book['level'])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)
                    ->setJSON(['status' => 'error', 'message' => 'Anda hanya dapat mengelola buku untuk level kelas yang diwalikan.']);
            }
            return redirect()->to('/admin/ebook')
                ->with('error', 'Anda hanya dapat mengelola buku untuk level kelas yang diwalikan.');
        }

        // Delete file
        $filePath = WRITEPATH . 'uploads/ebooks/' . $book['filename'];
        if (is_file($filePath)) {
            unlink($filePath);
        }

        // Delete record
        $this->ebookModel->delete($id);

        log_message('info', "Ebook deleted: #{$id} '{$book['title']}' by user #{$user['id']}");

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Buku berhasil dihapus.']);
        }

        return redirect()->to('/admin/ebook')->with('success', 'Buku berhasil dihapus.');
    }

    /**
     * AJAX: get religion field for a subject
     */
    public function getSubjectReligion($subjectId)
    {
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();
        if (!$subject) {
            return $this->response->setJSON(['religion' => null]);
        }
        return $this->response->setJSON(['religion' => $subject['religion'] ?? null]);
    }
}
