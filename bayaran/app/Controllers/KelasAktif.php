<?php

namespace App\Controllers;

use App\Models\KelasSiswaModel;
use App\Models\KelasModel;
use App\Models\TahunAjaranModel;

class KelasAktif extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new KelasSiswaModel();
    }

    public function index()
    {
        /** @var TahunAjaranModel $tahunAjaranModel */
        $tahunAjaranModel = new TahunAjaranModel();
        /** @var KelasModel $kelasModel */
        $kelasModel = new KelasModel();
    
        $tahun = $tahunAjaranModel->where('aktif', 1)->first();
        $kelas = $kelasModel->where('aktif', 1)->findAll();
        $kelasFilter = $this->request->getGet('kelas_id');
    
        $list = $this->model
            ->withRelations()
            ->where('kelas_siswa.tahun_ajaran_id', $tahun['id']);
    
        if ($kelasFilter) {
            $list->where('kelas_siswa.kelas_id', $kelasFilter);
        }
    
        $list = $list
            ->orderBy('siswa.nama', 'ASC')
            ->findAll();
    
        return view('admin/kelas_aktif/index', [
            'title' => 'Manajemen Kelas Aktif',
            'kelas' => $kelas,
            'tahun' => $tahun,
            'kelasFilter' => $kelasFilter,
            'list' => $list
        ]);
    }

    public function pindah()
    {
        $id = $this->request->getPost('id');
        $kelasBaru = $this->request->getPost('kelas_id');
        $statusBaru = $this->request->getPost('status');

        if (!$id) {
            return redirect()->back()->with('msg', 'Data tidak lengkap');
        }

        $data = [];
        if ($kelasBaru) $data['kelas_id'] = $kelasBaru;
        if ($statusBaru) $data['status'] = $statusBaru;

        if (!$data) {
            return redirect()->back()->with('msg', 'Tidak ada perubahan.');
        }

        $this->model->update($id, $data);
        return redirect()->back()->with('msg', 'Perubahan berhasil disimpan.');
    }


    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->back()->with('msg', 'Penempatan siswa dihapus.');
    }
}
