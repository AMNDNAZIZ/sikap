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
        helper('cbt');
    }

    public function index()
    {
        // Semua guru bisa melihat semua nama ujian (tidak diisolasi)
        $data['examNames'] = $this->examNameModel->orderBy('id', 'DESC')->findAll();
        $data['title'] = 'Daftar Nama Ujian';
        return view('admin/cbt/exam_name/index', $data);
    }

    public function store()
    {
        $context = get_cbt_user_context();
        $name = $this->request->getPost('name');

        if (empty($name)) {
            return redirect()->back()->with('error', 'Nama ujian tidak boleh kosong.');
        }

        // Set created_by untuk tracking (opsional, tidak untuk isolasi)
        $data = [
            'name' => $name,
            'created_by' => $context['user_id']
        ];

        $this->examNameModel->save($data);
        return redirect()->back()->with('success', 'Nama ujian berhasil ditambahkan.');
    }

    public function update($id)
    {
        // Tidak ada validasi ownership - semua guru bisa edit
        $name = $this->request->getPost('name');
        $this->examNameModel->update($id, ['name' => $name]);
        return redirect()->back()->with('success', 'Nama ujian berhasil diperbarui.');
    }

    public function delete($id)
    {
        // Tidak ada validasi ownership - semua guru bisa delete
        $this->examNameModel->delete($id);
        return redirect()->back()->with('success', 'Nama ujian berhasil dihapus.');
    }
}
