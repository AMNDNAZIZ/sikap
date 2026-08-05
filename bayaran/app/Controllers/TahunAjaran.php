<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TahunAjaranModel;

class TahunAjaran extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new TahunAjaranModel();
    }

    public function index()
    {
        return view('admin/tahun_ajaran/index', [
            'title' => 'Tahun Ajaran',
            'list'  => $this->model->orderBy('urutan', 'desc')->findAll(),
        ]);
    }

    public function save()
    {
        $this->model->insert([
            'nama'    => $this->request->getPost('nama'),
            'mulai'   => $this->request->getPost('mulai'),
            'selesai' => $this->request->getPost('selesai'),
            'urutan'  => (int) $this->request->getPost('urutan')
        ]);

        return redirect()->to(site_url('admin/tahun-ajaran'))
                         ->with('msg', 'Tahun ajaran baru ditambahkan.');
    }

    public function edit($id)
    {
        $item = $this->model->find($id);
        if (!$item) {
            return redirect()->to(site_url('admin/tahun-ajaran'))
                             ->with('msg', 'Data tidak ditemukan.');
        }

        return view('admin/tahun_ajaran/edit', [
            'title' => 'Edit Tahun Ajaran',
            'item'  => $item
        ]);
    }

    public function update($id)
    {
        $this->model->update($id, [
            'nama'    => $this->request->getPost('nama'),
            'mulai'   => $this->request->getPost('mulai'),
            'selesai' => $this->request->getPost('selesai'),
            'urutan'  => (int) $this->request->getPost('urutan')
        ]);

        return redirect()->to(site_url('admin/tahun-ajaran'))
                         ->with('msg', 'Tahun ajaran berhasil diperbarui.');
    }

    public function setAktif($id)
    {
        $this->model->where('aktif', 1)->set(['aktif' => 0])->update();
        $this->model->update($id, ['aktif' => 1]);

        return redirect()->back()->with('msg', 'Tahun ajaran aktif diperbarui.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->back()->with('msg', 'Tahun ajaran dihapus.');
    }
}
