<?php

namespace App\Controllers;

use App\Models\KelasSiswaModel;
use App\Models\TahunAjaranModel;

class ArsipSiswa extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new KelasSiswaModel();
    }

    public function index()
    {
        $tahunAktif = (new TahunAjaranModel())->where('aktif', 1)->first();
        $status  = $this->request->getGet('status') ?? 'lulus';
        $perPage = 20;
        $page    = (int) ($this->request->getGet('page') ?? 1);
        $offset  = ($page - 1) * $perPage;
    
        // Jika BELUM ADA tahun ajaran aktif → tampilkan view kosong
        if (!$tahunAktif) {
            return view('admin/arsip_siswa/index', [
                'title'      => 'Arsip Siswa',
                'status'     => $status,
                'siswa'      => [],
                'pager'      => null,
                'tahun'      => 'Belum diset',
                'currentPage'=> 1,
                'totalPages' => 1,
                'perPage'    => $perPage,
            ]);
        }
    
        // Gunakan getArsip agar kelas terakhir siswa lulus/pindah/do tampil
        $semuaSiswa = $this->model->getArsip($tahunAktif['id'], $status);
        $total      = count($semuaSiswa);
        $totalPages = (int) ceil($total / $perPage);
        $page       = max(1, min($page, max(1, $totalPages)));
        $siswa      = array_slice($semuaSiswa, ($page - 1) * $perPage, $perPage);
    
        return view('admin/arsip_siswa/index', [
            'title'      => 'Arsip Siswa',
            'status'     => $status,
            'siswa'      => $siswa,
            'pager'      => null,
            'tahun'      => $tahunAktif['nama'] ?? '-',
            'currentPage'=> $page,
            'totalPages' => $totalPages,
            'totalData'  => $total,
            'perPage'    => $perPage,
        ]);
    }

    public function aktifkan($id)
    {
        $row = $this->model->find($id);
        if (!$row) {
            return redirect()->back()->with('msg', 'Data tidak ditemukan.');
        }

        $this->model->update($id, [
            'status' => 'aktif',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('msg', 'Status siswa berhasil diaktifkan kembali.');
    }

}
