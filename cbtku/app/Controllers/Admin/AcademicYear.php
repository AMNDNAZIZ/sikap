<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;

class AcademicYear extends BaseController
{
    public function index()
    {
        $model = new AcademicYearModel();
        $data['years'] = $model->orderBy('start_date', 'DESC')->findAll();
        return view('admin/academic_year/index', $data);
    }

    public function create()
    {
        return view('admin/academic_year/create');
    }

    public function store()
    {
        $model = new AcademicYearModel();

        $model->insert([
            'year'       => $this->request->getPost('year'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date'   => $this->request->getPost('end_date'),
            'is_active'  => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        // jika is_active = 1, nonaktifkan yang lain
        if ($this->request->getPost('is_active')) {
            $model->where('id !=', $model->getInsertID())->set(['is_active' => 0])->update();
        }

        return redirect()->to('/admin/academic-year')->with('success','Tahun ajaran berhasil ditambahkan');
    }

    public function setActive($id)
    {
        $model = new AcademicYearModel();
        $model->set(['is_active' => 0])->update(); // nonaktifkan semua
        $model->update($id, ['is_active' => 1]);   // aktifkan yang dipilih

        return redirect()->to('/admin/academic-year')->with('success','Tahun ajaran aktif berhasil diubah');
    }

    public function edit($id)
    {
        $model = new AcademicYearModel();
        $data['year'] = $model->find($id);

        if (!$data['year']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Tahun ajaran tidak ditemukan");
        }

        return view('admin/academic_year/edit', $data);
    }

    public function update($id)
    {
        $model = new AcademicYearModel();

        $model->update($id, [
            'year'       => $this->request->getPost('year'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date'   => $this->request->getPost('end_date'),
            'is_active'  => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        // jika is_active diubah ke 1, nonaktifkan lainnya
        if ($this->request->getPost('is_active')) {
            $model->where('id !=', $id)->set(['is_active' => 0])->update();
        }

        return redirect()->to('/admin/academic-year')->with('success','Tahun ajaran berhasil diperbarui');
    }

}
