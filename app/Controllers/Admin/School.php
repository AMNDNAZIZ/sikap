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
            // Pastikan direktori ada dan writable sebelum move()
            $logoDir = UPLOAD_PATH . 'logo';
            if (!is_dir($logoDir)) {
                mkdir($logoDir, 0755, true);
            }
            if (!is_writable($logoDir)) {
                return redirect()->back()->with('error', 'Direktori upload logo tidak dapat ditulis. Pastikan permission folder "' . $logoDir . '" adalah 755 atau 777.');
            }
            try {
                $logoName = $logo->getRandomName();
                $logo->move($logoDir, $logoName);
            } catch (\Exception $e) {
                log_message('error', 'Logo upload failed: ' . $e->getMessage() . ' | UPLOAD_PATH=' . UPLOAD_PATH . ' | FCPATH=' . FCPATH);
                return redirect()->back()->with('error', 'Gagal upload logo: ' . $e->getMessage());
            }
        }

        if ($visionImage && $visionImage->isValid() && !$visionImage->hasMoved()) {
            $visionDir = UPLOAD_PATH . 'vision';
            if (!is_dir($visionDir)) {
                mkdir($visionDir, 0755, true);
            }
            if (!is_writable($visionDir)) {
                return redirect()->back()->with('error', 'Direktori upload vision tidak dapat ditulis. Pastikan permission folder "' . $visionDir . '" adalah 755 atau 777.');
            }
            try {
                $visionImageName = $visionImage->getRandomName();
                $visionImage->move($visionDir, $visionImageName);
            } catch (\Exception $e) {
                log_message('error', 'Vision image upload failed: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Gagal upload gambar visi: ' . $e->getMessage());
            }
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
