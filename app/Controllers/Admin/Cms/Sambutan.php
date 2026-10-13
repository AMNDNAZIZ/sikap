<?php

namespace App\Controllers\Admin\Cms;

use App\Controllers\BaseController;
use App\Models\SchoolModel;

class Sambutan extends BaseController
{
    protected $schoolModel;

    public function __construct()
    {
        $this->schoolModel = new SchoolModel();
        helper(['upload', 'form', 'text']);
    }

    /**
     * Tampilan form pengaturan sambutan kepala sekolah
     */
    public function index()
    {
        $school = $this->schoolModel->first() ?: [];

        // Fallback default values
        $defaultFallback = [
            'title'    => 'Sambutan Kepala Sekolah',
            'subtitle' => 'Prakata Pimpinan',
            'quote'    => 'Assalamu’alaikum Warahmatullahi Wabarakatuh, Salam Sejahtera untuk Kita Semua.',
            'isi'      => "Selamat datang di portal resmi " . ($school['name'] ?? 'sekolah kami') . ".\nPendidikan adalah investasi masa depan peradaban. Di era transformasi digital saat ini, kami berkomitmen untuk menghadirkan ekosistem pembelajaran yang tidak hanya unggul secara akademis, namun juga kokoh dalam penanaman budi pekerti, akhlak mulia, dan keterampilan abad 21.\n\nMelalui website dan sistem informasi terintegrasi ini, kami membuka pintu kolaborasi yang seluas-luasnya bagi seluruh peserta didik, tenaga pendidik, orang tua murid, serta masyarakat luas. Mari bersama bergandengan tangan mewujudkan generasi pembelajar yang berprestasi dan berkarakter.",
        ];

        $data = [
            'title'           => 'Pengaturan Sambutan Kepala Sekolah',
            'school'          => $school,
            'defaultFallback' => $defaultFallback,
        ];

        return view('admin/cms/sambutan/index', $data);
    }

    /**
     * Simpan pembaruan sambutan kepala sekolah
     */
    public function update()
    {
        $school = $this->schoolModel->first() ?: [];
        $id     = $school['id'] ?? null;

        if (!$id) {
            return redirect()->back()->with('error', 'Data profil sekolah tidak ditemukan.');
        }

        $rules = [
            'sambutan_title'    => 'permit_empty|max_length[255]',
            'sambutan_subtitle' => 'permit_empty|max_length[255]',
            'sambutan_quote'    => 'permit_empty|max_length[500]',
            'headmaster'        => 'permit_empty|max_length[255]',
            'principal_nip'     => 'permit_empty|max_length[50]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'sambutan_title'    => trim($this->request->getPost('sambutan_title') ?? ''),
            'sambutan_subtitle' => trim($this->request->getPost('sambutan_subtitle') ?? ''),
            'sambutan_quote'    => trim($this->request->getPost('sambutan_quote') ?? ''),
            'sambutan_isi'      => trim($this->request->getPost('sambutan_isi') ?? ''),
        ];

        // Jika nama kepala sekolah atau NIP diubah dari form ini
        $headmaster = trim($this->request->getPost('headmaster') ?? '');
        if ($headmaster !== '') {
            $data['headmaster'] = $headmaster;
        }

        $nip = trim($this->request->getPost('principal_nip') ?? '');
        $data['principal_nip'] = $nip;

        // Handle gambar sambutan
        $imageFile   = $this->request->getFile('sambutan_image');
        $removeImage = $this->request->getPost('remove_sambutan_image');

        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $result = safe_upload_image($imageFile, UPLOAD_PATH . 'sambutan', 3);
            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', 'Gagal mengunggah foto sambutan: ' . $result['error']);
            }

            // Hapus gambar lama jika ada
            if (!empty($school['sambutan_image'])) {
                safe_delete_upload(UPLOAD_PATH . 'sambutan', $school['sambutan_image']);
            }
            $data['sambutan_image'] = $result['name'];
        } elseif ($removeImage === '1') {
            if (!empty($school['sambutan_image'])) {
                safe_delete_upload(UPLOAD_PATH . 'sambutan', $school['sambutan_image']);
            }
            $data['sambutan_image'] = null;
        }

        $this->schoolModel->update($id, $data);

        return redirect()->to('/admin/cms/sambutan')->with('success', 'Sambutan Kepala Sekolah berhasil diperbarui dan ditayangkan pada beranda.');
    }

    /**
     * Kembalikan teks sambutan ke pengaturan bawaan (reset/fallback)
     */
    public function reset()
    {
        $school = $this->schoolModel->first() ?: [];
        $id     = $school['id'] ?? null;

        if (!$id) {
            return redirect()->back()->with('error', 'Data profil sekolah tidak ditemukan.');
        }

        $data = [
            'sambutan_title'    => null,
            'sambutan_subtitle' => null,
            'sambutan_quote'    => null,
            'sambutan_isi'      => null,
        ];

        $this->schoolModel->update($id, $data);

        return redirect()->to('/admin/cms/sambutan')->with('success', 'Teks sambutan telah dikosongkan. Website akan otomatis menggunakan teks sambutan bawaan (fallback).');
    }
}
