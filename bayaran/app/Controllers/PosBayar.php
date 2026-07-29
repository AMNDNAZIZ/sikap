<?php

namespace App\Controllers;

use App\Models\PosBayarModel;

class PosBayar extends BaseController
{
    protected $posModel;

    public function __construct()
    {
        $this->posModel = new PosBayarModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Manajemen Pos Bayar',
            'list' => $this->posModel->findAll()
        ];
        return view('admin/pos_bayar/index', $data);
    }

    public function save()
    {
        $this->posModel->save([
            'id' => $this->request->getPost('id'), // untuk update
            'nama_pos' => $this->request->getPost('nama_pos'),
            'keterangan' => $this->request->getPost('keterangan'),
            'aktif' => $this->request->getPost('aktif') ? 1 : 0,
        ]);

        return redirect()->to('/admin/pos-bayar')->with('msg', 'Data berhasil disimpan');
    }

    public function delete($id)
    {
        $this->posModel->delete($id);
        return redirect()->back()->with('msg', 'Data dihapus');
    }
}
