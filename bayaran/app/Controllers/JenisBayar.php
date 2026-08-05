<?php

namespace App\Controllers;

use App\Models\JenisBayarModel;
use App\Models\PosBayarModel;
use App\Models\TahunAjaranModel;

class JenisBayar extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new JenisBayarModel();
    }

    public function index()
    {
        $tahunAjaranModel = new TahunAjaranModel();
        $posModel = new PosBayarModel();
    
        $tahunAktif = $tahunAjaranModel->where('aktif', 1)->first();
    
        // Default: tahun array kosong aman
        $tahun = $tahunAktif
            ? ['id' => $tahunAktif['id'], 'nama' => $tahunAktif['nama']]
            : ['id' => null, 'nama' => 'Belum diset'];
    
        $list = [];
        if ($tahun['id'] !== null) {
            // hanya query jika tahun ada
            $list = $this->model->withRelations()
                ->where('jenis_bayar.tahun_ajaran_id', $tahun['id'])
                ->findAll();
        }
    
        $data = [
            'title' => 'Jenis Bayar',
            'list'  => $list,
            'pos'   => $posModel->where('aktif', 1)->findAll(),
            'tahun' => $tahun,
        ];
    
        return view('admin/jenis_bayar/index', $data);
    }


    public function save()
    {
        $this->model->save([
            'id' => $this->request->getPost('id'),
            'pos_bayar_id' => $this->request->getPost('pos_bayar_id'),
            'tahun_ajaran_id' => $this->request->getPost('tahun_ajaran_id'),
            'nama_jenis_bayar' => $this->request->getPost('nama_jenis_bayar'),
            'tipe' => $this->request->getPost('tipe'),
            'tarif_default' => $this->request->getPost('tarif_default'),
            'wajib' => $this->request->getPost('wajib') ? 1 : 0,
        ]);

        return redirect()->to('/admin/jenis-bayar')->with('msg', 'Data berhasil disimpan');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->back()->with('msg', 'Jenis bayar dihapus');
    }
}
