<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\JenjangMasterModel;

class JenjangMaster extends BaseController
{
    protected $jenjangModel;

    public function __construct()
    {
        $this->jenjangModel = new JenjangMasterModel();
    }

    public function index()
    {
        $data['title'] = 'Jenjang Master';
        $data['jenjang'] = $this->jenjangModel->findAll();
        return view('admin/jenjang_master/index', $data);
    }

    public function store()
    {
        $id = $this->request->getPost('id');
        $data = [
            'kode' => $this->request->getPost('kode'),
            'nama' => $this->request->getPost('nama'),
        ];

        if ($id) {
            $this->jenjangModel->update($id, $data);
            $msg = 'Jenjang berhasil diperbarui.';
        } else {
            $this->jenjangModel->insert($data);
            $msg = 'Jenjang berhasil ditambahkan.';
        }

        return redirect()->to(base_url('admin/jenjang-master'))->with('success', $msg);
    }

    public function delete($id)
    {
        $this->jenjangModel->delete($id);
        return redirect()->to(base_url('admin/jenjang-master'))->with('success', 'Jenjang berhasil dihapus.');
    }
}
