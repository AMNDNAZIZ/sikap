<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingsModel;

class SettingsController extends BaseController
{
    protected $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
    }

    public function kopSurat()
    {
        $data = [
            'title' => 'Pengaturan Kop Surat',
            'kop_surat' => $this->settingsModel->getValue('kop_surat')
        ];

        return view('admin/settings/kop_surat', $data);
    }

    public function uploadKopSurat()
    {
        $file = $this->request->getFile('kop_file');

        if (!$file->isValid()) {
            return redirect()->back()->with('error', $file->getErrorString());
        }

        $rules = [
            'kop_file' => 'uploaded[kop_file]|is_image[kop_file]|mime_in[kop_file,image/png,image/jpeg,image/jpg]|max_size[kop_file,5120]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'File tidak valid. Pastikan format PNG/JPG dan ukuran maksimal 5MB.');
        }

        $targetDir  = FCPATH . 'uploads/settings/';
        $targetName = 'kop_surat_active.' . $file->getExtension();

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if ($file->move($targetDir, $targetName, true)) {
            $relativePath = 'uploads/settings/' . $targetName;
            $this->settingsModel->setValue('kop_surat', $relativePath);
            return redirect()->back()->with('success', 'Kop surat berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah file.');
    }
}
