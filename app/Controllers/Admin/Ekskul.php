<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EkskulModel;
use App\Models\EkskulPembinaModel;
use App\Models\UserModel;
use App\Models\AcademicYearModel;

class Ekskul extends BaseController
{
    protected $ekskulModel;
    protected $pembinaModel;

    public function __construct()
    {
        $this->ekskulModel = new EkskulModel();
        $this->pembinaModel = new EkskulPembinaModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Master Data Ekstrakurikuler',
            'ekskul' => $this->ekskulModel->findAll()
        ];

        return view('admin/ekskul/index', $data);
    }

    public function store()
    {
        $data = [
            'kode' => $this->request->getPost('kode'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'category' => $this->request->getPost('category'),
            'is_active' => $this->request->getPost('is_active') ?? 1,
        ];

        $this->ekskulModel->insert($data);
        return redirect()->to('/admin/ekskul')->with('success', 'Data ekstrakurikuler berhasil ditambahkan.');
    }

    public function update($id)
    {
        $data = [
            'kode' => $this->request->getPost('kode'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'category' => $this->request->getPost('category'),
            'is_active' => $this->request->getPost('is_active') ?? 0,
        ];

        $this->ekskulModel->update($id, $data);
        return redirect()->to('/admin/ekskul')->with('success', 'Data ekstrakurikuler berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->ekskulModel->delete($id);
        return redirect()->to('/admin/ekskul')->with('success', 'Data ekstrakurikuler berhasil dihapus.');
    }
    public function pembina($ekskul_id)
    {
        $ekskul = $this->ekskulModel->find($ekskul_id);
        if (!$ekskul) {
            return redirect()->to('/admin/ekskul')->with('error', 'Data ekstrakurikuler tidak ditemukan.');
        }

        $userModel = new UserModel();
        // Ambil guru (role 3) atau staf/lainnya yang bisa jadi pembina. Kita load semua active users yg bukan siswa (asumsi role_id > 1)
        // Atau kita sediakan select all teacher & external. 
        $potentialPembina = $userModel->whereIn('role_id', [2, 3, 4, 6, 7, 8])->where('is_active', 1)->orderBy('fullname', 'ASC')->findAll(); 
        
        $academicYearModel = new AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        
        // Ambil pembina yg sdh ditugaskan
        $pembinaList = [];
        if ($activeYear) {
            $pembinaList = $this->pembinaModel->select('ekskul_pembina.*, users.fullname as pembina_name, users.username, users.role_id')
                ->join('users', 'users.id = ekskul_pembina.user_id')
                ->where('ekskul_id', $ekskul_id)
                ->where('academic_year_id', $activeYear['id'])
                ->findAll();
        }

        $data = [
            'title' => 'Kelola Pembina: ' . $ekskul['name'],
            'ekskul' => $ekskul,
            'potentialPembina' => $potentialPembina,
            'pembinaList' => $pembinaList,
            'activeYear' => $activeYear
        ];

        return view('admin/ekskul/pembina', $data);
    }
    
    public function storePembina($ekskul_id)
    {
        $academicYearModel = new AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        
        if (!$activeYear) {
            return redirect()->to('/admin/ekskul/pembina/' . $ekskul_id)->with('error', 'Tahun ajaran aktif belum diset.');
        }

        $user_id = $this->request->getPost('user_id');
        
        // Check if already assigned
        $existing = $this->pembinaModel->where('ekskul_id', $ekskul_id)
            ->where('user_id', $user_id)
            ->where('academic_year_id', $activeYear['id'])
            ->first();
            
        if ($existing) {
            return redirect()->to('/admin/ekskul/pembina/' . $ekskul_id)->with('error', 'Pembina tersebut sudah ditugaskan untuk ekskul ini.');
        }

        // check is external (optional logic, based on role)
        $userModel = new UserModel();
        $user = $userModel->find($user_id);
        $is_external = ($user && $user['role_id'] != 3) ? 1 : 0; 

        $this->pembinaModel->insert([
            'ekskul_id' => $ekskul_id,
            'user_id' => $user_id,
            'academic_year_id' => $activeYear['id'],
            'is_external' => $is_external
        ]);

        return redirect()->to('/admin/ekskul/pembina/' . $ekskul_id)->with('success', 'Pembina berhasil ditugaskan.');
    }
    
    public function deletePembina($id)
    {
        $pembina = $this->pembinaModel->find($id);
        if ($pembina) {
            $this->pembinaModel->delete($id);
            return redirect()->to('/admin/ekskul/pembina/' . $pembina['ekskul_id'])->with('success', 'Penugasan pembina dibatalkan.');
        }
        return redirect()->back()->with('error', 'Data tidak ditemukan.');
    }
}
