<?php

namespace App\Controllers;

use App\Models\SettingModel;

class Setting extends BaseController
{
    public function index()
    {
        $model = new SettingModel();
        $data = [
            'title' => 'Setting Sekolah',
            'setting' => $model->first()
        ];
        return view('admin/setting/index', $data);
    }

    public function save()
    {
        $model = new SettingModel();

        // Validasi input
        $rules = [
            'nama_aplikasi' => 'required',
            'nama_sekolah' => 'required',
            'logo' => 'permit_empty|uploaded[logo]|is_image[logo]|ext_in[logo,png,jpg,jpeg]|max_size[logo,2048]',
        ];

        if (!$this->validate($rules) && $this->request->getFile('logo')->isValid()) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();
        $logoFile = $this->request->getFile('logo');

        if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
            $newName = $logoFile->getRandomName();
            $logoFile->move('uploads/logo', $newName);
            $data['logo'] = 'uploads/logo/' . $newName;
        }

        if ($model->first()) {
            $model->update(1, $data);
        } else {
            $model->insert($data);
        }

        return redirect()->to('/admin/setting')->with('msg', 'Pengaturan berhasil disimpan.');
    }
}
