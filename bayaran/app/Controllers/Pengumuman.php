<?php

namespace App\Controllers;

use App\Models\PengumumanModel;

class Pengumuman extends BaseController
{
    public function index()
    {
        $model = new PengumumanModel();
        return view('admin/pengumuman/index', [
            'title' => 'Manajemen Pengumuman',
            'list' => $model->orderBy('created_at', 'desc')->findAll()
        ]);
    }

    public function save()
    {
        $model = new PengumumanModel();
        $data = $this->request->getPost();
        $model->save($data);

        return redirect()->to('/admin/pengumuman')->with('msg', 'Pengumuman disimpan.');
    }

    public function delete($id)
    {
        $model = new PengumumanModel();
        $model->delete($id);

        return redirect()->to('/admin/pengumuman')->with('msg', 'Pengumuman dihapus.');
    }

    public function edit($id)
    {
        $model = new PengumumanModel();
        $data = [
            'title' => 'Edit Pengumuman',
            'pengumuman' => $model->find($id),
        ];
        return view('admin/pengumuman/edit', $data);
    }

    public function update($id)
    {
        $model = new PengumumanModel();
        $data = $this->request->getPost();
        $data['id'] = $id;
        $model->save($data);

        return redirect()->to('/admin/pengumuman')->with('msg', 'Pengumuman diperbarui.');
    }
}
