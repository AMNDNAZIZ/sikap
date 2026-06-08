<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SubjectMaterialModel;
use App\Models\SubjectModel;
use App\Models\AcademicYearModel;

class Materials extends BaseController
{
    protected $materialsModel;
    protected $subjectModel;
    protected $yearModel;

    public function __construct()
    {
        $this->materialsModel = new SubjectMaterialModel();
        $this->subjectModel   = new SubjectModel();
        $this->yearModel      = new AcademicYearModel();
    }

    public function index($subjectId)
    {
        $subject = $this->subjectModel->find($subjectId);
        $materials = $this->materialsModel
            ->select('subject_materials.*, academic_years.year as year_name')
            ->join('academic_years', 'academic_years.id = subject_materials.year_id', 'left')
            ->where('subject_materials.subject_id', $subjectId)
            ->orderBy('semester', 'ASC')
            ->findAll();

        // Ambil return URL dari query string (kalau ada)
        $returnUrl = $this->request->getGet('return');

        return view('admin/materials/index', [
            'subject'    => $subject,
            'materials'  => $materials,
            'returnUrl'  => $returnUrl, // dilempar ke view
        ]);
    }


    public function create($subjectId)
    {
        $subject    = $this->subjectModel->find($subjectId);
        $activeYear = $this->yearModel->where('is_active', 1)->first();

        // Ambil return URL dari query string (kalau ada)
        $returnUrl = $this->request->getGet('return');

        return view('admin/materials/create', [
            'subject'    => $subject,
            'activeYear' => $activeYear,
            'returnUrl'  => $returnUrl, // dilempar ke view supaya bisa ditanam di form
        ]);
    }

    public function store()
    {
        $data = $this->request->getPost();

        $this->materialsModel->insert([
            'subject_id'  => $data['subject_id'],
            'year_id'     => $data['year_id'],
            'semester'    => $data['semester'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        // Ambil return URL dari hidden input
        $returnUrl = $this->request->getPost('return');

        if (!empty($returnUrl)) {
            return redirect()->to($returnUrl)
                             ->with('success', 'Materi berhasil ditambahkan');
        }

        // fallback kalau return kosong → kembali ke materials index
        return redirect()->to('/admin/materials/'.$data['subject_id'])
                         ->with('success', 'Materi berhasil ditambahkan');
    }

    public function edit($id)
    {
        $material   = $this->materialsModel->find($id);

        // Ambil mapel dari material
        $subject    = $this->subjectModel->find($material['subject_id']);

        // Ambil tahun ajaran aktif
        $activeYear = $this->yearModel->where('is_active', 1)->first();

        return view('admin/materials/edit', [
            'material'   => $material,
            'subject'    => $subject,
            'activeYear' => $activeYear
        ]);
    }


    public function update($id)
    {
        $data = $this->request->getPost();
        $this->materialsModel->update($id, [
            'semester'   => $data['semester'],
            'title'      => $data['title'],
            'description'=> $data['description'] ?? null,
        ]);

        return redirect()->to('/admin/materials/'.$data['subject_id'])
                         ->with('success', 'Materi berhasil diperbarui');
    }

    public function delete($id)
    {
        $material = $this->materialsModel->find($id);
        if (!$material) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Materi tidak ditemukan']);
        }

        $subject = $this->subjectModel->find($material['subject_id']);
        if (!$subject) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Mata pelajaran tidak ditemukan']);
        }

        if ($this->request->getMethod() === 'POST') {
            if ($this->materialsModel->delete($id)) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Materi berhasil dihapus',
                    'subjectId' => $subject['id']
                ]);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menghapus materi']);
            }
        }

        // Tidak perlu view delete.php lagi
        return $this->response->setStatusCode(405)->setJSON([
            'status' => 'error',
            'message' => 'Method tidak diizinkan'
        ]);
    }

}
