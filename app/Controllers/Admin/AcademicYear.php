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

        // Prepare data
        $data = [
            'year' => $this->request->getPost('year'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date' => $this->request->getPost('end_date'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'formatif_weight' => $this->request->getPost('formatif_weight') ?: 60,
            'sumatif_weight' => $this->request->getPost('sumatif_weight') ?: 40,
        ];
        
        // Check if school_days column exists before adding it
        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('academic_years');
        if (in_array('school_days', $fields)) {
            $data['school_days'] = $this->request->getPost('school_days') ?: 5;
        }

        $model->insert($data);

        // jika is_active = 1, nonaktifkan yang lain
        if ($this->request->getPost('is_active')) {
            $model->where('id !=', $model->getInsertID())->set(['is_active' => 0])->update();
        }

        return redirect()->to('/admin/academic-year')->with('success', 'Tahun ajaran berhasil ditambahkan');
    }

    public function setActive($id)
    {
        $model = new AcademicYearModel();
        
        // Nonaktifkan semua tahun ajaran menggunakan query builder langsung
        $db = \Config\Database::connect();
        $db->table('academic_years')->set(['is_active' => 0])->update();
        
        // Aktifkan yang dipilih
        $model->update($id, ['is_active' => 1]);

        return redirect()->to('/admin/academic-year')->with('success', 'Tahun ajaran aktif berhasil diubah');
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

        // Prepare data
        $data = [
            'year' => $this->request->getPost('year'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date' => $this->request->getPost('end_date'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'formatif_weight' => $this->request->getPost('formatif_weight') ?: 60,
            'sumatif_weight' => $this->request->getPost('sumatif_weight') ?: 40,
        ];
        
        // Check if school_days column exists before adding it
        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('academic_years');
        if (in_array('school_days', $fields)) {
            $data['school_days'] = $this->request->getPost('school_days') ?: 5;
        }

        $model->update($id, $data);

        // jika is_active diubah ke 1, nonaktifkan lainnya
        if ($this->request->getPost('is_active')) {
            $model->where('id !=', $id)->set(['is_active' => 0])->update();
        }

        return redirect()->to('/admin/academic-year')->with('success', 'Tahun ajaran berhasil diperbarui');
    }

}
