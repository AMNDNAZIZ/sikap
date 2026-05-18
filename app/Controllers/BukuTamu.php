<?php

namespace App\Controllers;

use App\Models\BukuTamuModel;
use App\Models\TeacherModel;
use App\Models\SchoolModel;

class BukuTamu extends BaseController
{
    protected BukuTamuModel $bukuTamuModel;
    protected TeacherModel  $teacherModel;

    public function __construct()
    {
        $this->bukuTamuModel = new BukuTamuModel();
        $this->teacherModel  = new TeacherModel();
    }

    /**
     * Halaman utama — pilih jenis tamu
     */
    public function index()
    {
        $school = (new SchoolModel())->first();

        return view('buku-tamu/index', [
            'title'  => 'Buku Tamu Digital',
            'school' => $school,
        ]);
    }

    /**
     * Form Tamu Umum
     */
    public function formUmum()
    {
        $school   = (new SchoolModel())->first();
        $teachers = $this->teacherModel->orderBy('name', 'ASC')->findAll();

        return view('buku-tamu/form-umum', [
            'title'    => 'Form Tamu Umum',
            'school'   => $school,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Simpan data Tamu Umum
     */
    public function storeUmum()
    {
        $rules = [
            'nama'   => 'required|max_length[100]',
            'tujuan' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $isOrtu    = $this->request->getPost('is_ortu_siswa') === '1' ? 1 : 0;
        $ipAddress = $this->request->getIPAddress();

        $data = [
            'guest_type'     => 'umum',
            'nama'           => esc($this->request->getPost('nama')),
            'no_hp'          => esc($this->request->getPost('no_hp')),
            'is_ortu_siswa'  => $isOrtu,
            'instansi'       => $isOrtu ? null : esc($this->request->getPost('instansi')),
            'alamat'         => esc($this->request->getPost('alamat')),
            'tujuan'         => esc($this->request->getPost('tujuan')),
            'bertemu_dengan' => esc($this->request->getPost('bertemu_dengan')),
            'ip_address'     => $ipAddress,
            // created_at diset otomatis oleh model (useTimestamps = true)
        ];

        $this->bukuTamuModel->insert($data);

        return redirect()->to(base_url('buku-tamu/sukses'))->with('sukses_nama', $data['nama']);
    }

    /**
     * Form Tamu Dinas
     */
    public function formDinas()
    {
        $school   = (new SchoolModel())->first();
        $teachers = $this->teacherModel->orderBy('name', 'ASC')->findAll();

        return view('buku-tamu/form-dinas', [
            'title'    => 'Form Tamu Dinas',
            'school'   => $school,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Simpan data Tamu Dinas
     */
    public function storeDinas()
    {
        $rules = [
            'nama'     => 'required|max_length[100]',
            'instansi' => 'required|max_length[150]',
            'tujuan'   => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $ipAddress = $this->request->getIPAddress();

        $data = [
            'guest_type'     => 'dinas',
            'nama'           => esc($this->request->getPost('nama')),
            'nip'            => esc($this->request->getPost('nip')),
            'instansi'       => esc($this->request->getPost('instansi')),
            'no_hp'          => esc($this->request->getPost('no_hp')),
            'tujuan'         => esc($this->request->getPost('tujuan')),
            'bertemu_dengan' => esc($this->request->getPost('bertemu_dengan')),
            'ip_address'     => $ipAddress,
            // created_at diset otomatis oleh model (useTimestamps = true)
        ];

        $this->bukuTamuModel->insert($data);

        return redirect()->to(base_url('buku-tamu/sukses'))->with('sukses_nama', $data['nama']);
    }

    /**
     * Halaman sukses setelah submit
     */
    public function sukses()
    {
        $school    = (new SchoolModel())->first();
        $namaGuest = session()->getFlashdata('sukses_nama');

        return view('buku-tamu/sukses', [
            'title'      => 'Pendaftaran Berhasil',
            'school'     => $school,
            'nama_guest' => $namaGuest,
        ]);
    }
}
