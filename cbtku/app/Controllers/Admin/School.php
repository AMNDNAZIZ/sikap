<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SchoolModel;

class School extends BaseController
{
    public function index()
    {
        $model = new SchoolModel();
        $data['school'] = $model->first();
        return view('admin/school/index', $data);
    }

    public function update()
    {
        $model = new SchoolModel();
        $id = $this->request->getPost('id');

        // Upload Logo dengan validasi keamanan
        $logo = $this->request->getFile('logo');
        $logoName = null;
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            // Validasi MIME type
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($logo->getMimeType(), $allowedMimes)) {
                return redirect()->back()->with('error', 'Format logo harus JPG, PNG, GIF, atau WebP');
            }
            
            // Validasi ukuran (max 2MB)
            if ($logo->getSize() > 2048000) {
                return redirect()->back()->with('error', 'Ukuran logo maksimal 2MB');
            }
            
            $logoName = $logo->getRandomName();
            // FIXED: Use FCPATH to ensure upload goes to public/uploads/logo
            $logo->move(FCPATH . 'uploads/logo', $logoName);
        }

        // Upload Gambar Hero dengan validasi keamanan
        $image = $this->request->getFile('image');
        $imageName = null;
        if ($image && $image->isValid() && !$image->hasMoved()) {
            // Validasi MIME type
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($image->getMimeType(), $allowedMimes)) {
                return redirect()->back()->with('error', 'Format gambar harus JPG, PNG, GIF, atau WebP');
            }
            
            // Validasi ukuran (max 5MB)
            if ($image->getSize() > 5120000) {
                return redirect()->back()->with('error', 'Ukuran gambar maksimal 5MB');
            }
            
            $imageName = $image->getRandomName();
            // FIXED: Use FCPATH to ensure upload goes to public/uploads
            $image->move(FCPATH . 'uploads', $imageName);
        }
        // === Ambil data dasar ===
        $mapEmbed = $this->request->getPost('map_embed');

        // Jika admin menempelkan kode <iframe>, ambil hanya nilai src-nya
        $mapEmbed = (string) $mapEmbed;
        if (preg_match('/src="([^"]+)"/', $mapEmbed, $matches)) {
            $mapEmbed = $matches[1];
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'tagline' => $this->request->getPost('tagline'),
            'description' => $this->request->getPost('description'),
            'address' => $this->request->getPost('address'),
            'phone' => $this->request->getPost('phone'),
            'email' => $this->request->getPost('email'),
            'headmaster' => $this->request->getPost('headmaster'),
            'level' => $this->request->getPost('level'),
            'map_embed' => $mapEmbed,
        ];

        if ($logoName)
            $data['logo'] = $logoName;
        if ($imageName)
            $data['image'] = $imageName;

        if ($id) {
            $model->update($id, $data);
        } else {
            $model->insert($data);
        }

        cache()->delete('school_profile'); // refresh cache
        return redirect()->to('/admin/school')->with('success', 'Profil sekolah berhasil diperbarui!');
    }
}
