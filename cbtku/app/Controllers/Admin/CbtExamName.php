<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtExamNameModel;

class CbtExamName extends BaseController
{
    protected $examNameModel;

    public function __construct()
    {
        $this->examNameModel = new CbtExamNameModel();
    }

    public function index()
    {
        $data['examNames'] = $this->examNameModel->orderBy('id', 'DESC')->findAll();
        $data['title'] = 'Daftar Nama Ujian';
        return view('admin/cbt/exam_name/index', $data);
    }

    public function store()
    {
        $name = $this->request->getPost('name');

        if (empty($name)) {
            return redirect()->back()->with('error', 'Nama ujian tidak boleh kosong.');
        }

        $this->examNameModel->save(['name' => $name]);
        return redirect()->back()->with('success', 'Nama ujian berhasil ditambahkan.');
    }

    public function update($id)
    {
        $name = $this->request->getPost('name');
        $this->examNameModel->update($id, ['name' => $name]);
        return redirect()->back()->with('success', 'Nama ujian berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->examNameModel->delete($id);
        return redirect()->back()->with('success', 'Nama ujian berhasil dihapus.');
    }
}
