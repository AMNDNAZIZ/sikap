<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SchoolModel;

class School extends BaseController
{
    public function index()
    {
        $model = new SchoolModel();
        $data['school'] = $model->first() ?: []; // hanya 1 baris
        return view('admin/school/index', $data);
    }

    public function update()
    {
        $model = new SchoolModel();
        $id = $this->request->getPost('id');

        $logo = $this->request->getFile('logo');
        $visionImage = $this->request->getFile('vision_image');
        $logoName = null;
        $visionImageName = null;

        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            $logoName = $logo->getRandomName();
            $logo->move(UPLOAD_PATH . 'logo', $logoName);
        }

        if ($visionImage && $visionImage->isValid() && !$visionImage->hasMoved()) {
            $visionImageName = $visionImage->getRandomName();
            $visionImage->move(UPLOAD_PATH . 'vision', $visionImageName);
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'address' => $this->request->getPost('address'),
            'city_regency' => $this->request->getPost('city_regency'),
            'phone' => $this->request->getPost('phone'),
            'email' => $this->request->getPost('email'),
            'headmaster' => $this->request->getPost('headmaster'),
            'principal_nip' => $this->request->getPost('principal_nip'),
            'level' => $this->request->getPost('level'),
            'vision' => $this->request->getPost('vision'),
            'mission' => $this->request->getPost('mission'),
            'facebook' => $this->request->getPost('facebook'),
            'instagram' => $this->request->getPost('instagram'),
            'youtube' => $this->request->getPost('youtube'),
            'tiktok' => $this->request->getPost('tiktok'),
            'twitter' => $this->request->getPost('twitter'),
            'latitude' => $this->request->getPost('latitude') ?: null,
            'longitude' => $this->request->getPost('longitude') ?: null,
        ];

        if ($logoName) {
            $data['logo'] = $logoName;
        }

        if ($visionImageName) {
            $data['vision_image'] = $visionImageName;
        }

        if ($id) {
            // Jika update, pastikan level tidak null jika ada nilai lama
            $existing = $model->find($id);
            if (empty($data['level']) && !empty($existing['level'])) {
                $data['level'] = $existing['level']; // Pertahankan nilai lama jika tidak diubah
            }
            $model->update($id, $data);
        } else {
            // Jika insert baru, level wajib diisi (bisa tambah validasi jika perlu)
            if (empty($data['level'])) {
                $data['level'] = 1; // Default ke SD jika kosong
            }
            $model->insert($data);
        }

        return redirect()->to('/admin/school')->with('success', 'Identitas sekolah berhasil disimpan');
    }
}
