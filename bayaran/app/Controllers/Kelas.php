<?php

namespace App\Controllers;

use App\Models\KelasModel;

class Kelas extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new KelasModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Manajemen Kelas',
            'list' => $this->model->orderBy('nama_kelas')->findAll()
        ];

        return view('admin/kelas/index', $data);
    }

    public function save()
    {
        $this->model->save([
            'id' => $this->request->getPost('id'),
            'nama_kelas' => $this->request->getPost('nama_kelas'),
            'keterangan' => $this->request->getPost('keterangan'),
            'aktif' => $this->request->getPost('aktif') ? 1 : 0,
        ]);

        return redirect()->to('/admin/kelas')->with('msg', 'Kelas disimpan.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->back()->with('msg', 'Kelas dihapus.');
    }
}
