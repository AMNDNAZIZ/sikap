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

    // Cek apakah user adalah admin atau kepsek (role 1/2)
    private function isAdmin(): bool
    {
        $user = session()->get('user');
        return in_array((int)($user['role_id'] ?? 0), [1, 2]);
    }

    public function index()
    {
        $data['examNames'] = $this->examNameModel->orderBy('id', 'DESC')->findAll();
        $data['title']     = 'Daftar Nama Ujian';
        $data['isAdmin']   = $this->isAdmin();
        return view('admin/cbt/exam_name/index', $data);
    }

    public function store()
    {
        if (!$this->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya admin yang dapat menambah nama ujian.');
        }

        $name = trim($this->request->getPost('name') ?? '');
        if (empty($name)) {
            return redirect()->back()->with('error', 'Nama ujian tidak boleh kosong.');
        }

        $context = get_cbt_user_context();
        $this->examNameModel->save([
            'name'       => $name,
            'created_by' => $context['user_id'],
        ]);

        return redirect()->back()->with('success', 'Nama ujian berhasil ditambahkan.');
    }

    public function update($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya admin yang dapat mengubah nama ujian.');
        }

        $name = trim($this->request->getPost('name') ?? '');
        if (empty($name)) {
            return redirect()->back()->with('error', 'Nama ujian tidak boleh kosong.');
        }

        $this->examNameModel->update($id, ['name' => $name]);
        return redirect()->back()->with('success', 'Nama ujian berhasil diperbarui.');
    }

    public function delete($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya admin yang dapat menghapus nama ujian.');
        }

        $this->examNameModel->delete($id);
        return redirect()->back()->with('success', 'Nama ujian berhasil dihapus.');
    }
}
