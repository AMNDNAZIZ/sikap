<?php

namespace App\Controllers;

use App\Models\KelasSiswaModel;
use App\Models\TahunAjaranModel;
use App\Models\SiswaModel;
use App\Models\KelasModel;

class Penempatan extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new KelasSiswaModel();
    }

    public function index()
    {
        $tahunAktif = (new TahunAjaranModel())->where('aktif', 1)->first();
    
        // Pasti array format tetap konsisten
        $tahun = $tahunAktif
            ? ['id' => $tahunAktif['id'], 'nama' => $tahunAktif['nama']]
            : ['id' => null, 'nama' => 'Belum diset'];
    
        $kelas = (new KelasModel())->where('aktif', 1)->findAll();
    
        // Pakai paginasi CI4
        $siswaModel = new SiswaModel();
        $perPage = 20; // Ganti sesuai kebutuhan
    
        $siswaBaru = $siswaModel
            ->whereNotIn('id', function ($builder) {
                return $builder->select('siswa_id')->from('kelas_siswa');
            })
            ->paginate($perPage, 'siswa'); // gunakan group 'siswa'
    
        $pager = $siswaModel->pager;
    
        return view('admin/penempatan/index', [
            'title' => 'Penempatan Siswa Baru',
            'tahun' => $tahun,
            'kelas' => $kelas,
            'siswa' => $siswaBaru,
            'pager' => $pager, // kirim pager
        ]);
    }

    public function save()
    {
        $siswaIds = $this->request->getPost('siswa_ids');
        $kelasId = $this->request->getPost('kelas_id');
        $tahunId = $this->request->getPost('tahun_ajaran_id');

        if (!$siswaIds || !$kelasId || !$tahunId) {
            return redirect()->back()->with('msg', 'Mohon pilih siswa dan kelas.');
        }

        $insertData = [];

        foreach ($siswaIds as $siswaId) {
            $insertData[] = [
                'siswa_id'        => $siswaId,
                'kelas_id'        => $kelasId,
                'tahun_ajaran_id' => $tahunId,
                'status'          => 'aktif',
                'tanggal_masuk'   => date('Y-m-d'),
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];
        }

        $this->model->insertBatch($insertData);

        return redirect()->to('/admin/penempatan')->with('msg', 'Siswa berhasil ditempatkan.');
    }



    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->back()->with('msg', 'Data penempatan dihapus.');
    }
}
