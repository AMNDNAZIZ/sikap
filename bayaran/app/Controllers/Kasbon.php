<?php

namespace App\Controllers;

use App\Models\KasbonModel;
use App\Models\KasbonBayarModel;

class Kasbon extends BaseController
{
    public function index()
    {
        $keyword = $this->request->getGet('q');
        $model = new KasbonModel();

        if ($keyword) {
            $model->like('nama', $keyword);
        }

        $kasbon = $model->orderBy('tanggal', 'desc')->findAll();

        $totalTunggakan = array_reduce($kasbon, function ($carry, $item) {
            return $carry + ($item['jumlah'] - $item['dibayar']);
        }, 0);

        $session = session();
        $canEdit = in_array($session->get('role'), ['superadmin', 'admin']);

        $data = [
            'title' => 'Data Kasbon',
            'kasbon' => $kasbon,
            'q' => $keyword,
            'totalTunggakan' => $totalTunggakan,
            'canEdit' => $canEdit
        ];

        return view('admin/kasbon/index', $data);
    }


    public function save()
    {
        $post = $this->request->getPost();
        $model = new KasbonModel();

        $model->save([
            'tanggal' => $post['tanggal'],
            'nama'    => $post['nama'],
            'jumlah'  => (int) str_replace('.', '', $post['jumlah']),
        ]);

        return redirect()->back()->with('msg', 'Kasbon ditambahkan');
    }

    public function delete($id)
    {
        $kasbon = (new KasbonModel())->find($id);
        if (!$kasbon || $kasbon['status'] !== 'lunas') {
            return redirect()->back()->with('msg', 'Kasbon belum lunas, tidak bisa dihapus');
        }

        (new KasbonModel())->delete($id);
        (new KasbonBayarModel())->where('kasbon_id', $id)->delete();

        return redirect()->back()->with('msg', 'Kasbon berhasil dihapus');
    }

    public function bayar()
    {
        $post = $this->request->getPost();
        $kasbonId = $post['kasbon_id'];
        $jumlah = (int) str_replace('.', '', $post['jumlah'] ?? 0);
        $tanggal = $post['tanggal'] ?? date('Y-m-d');
        $keterangan = $post['keterangan'] ?? '';

        $kasbonModel = new KasbonModel();
        $bayarModel = new KasbonBayarModel();

        $kasbon = $kasbonModel->find($kasbonId);
        if (!$kasbon) return redirect()->back()->with('msg', 'Kasbon tidak ditemukan');

        $sisa = $kasbon['jumlah'] - $kasbon['dibayar'];
        if ($jumlah > $sisa) {
            return redirect()->back()->with('msg', 'Jumlah pembayaran melebihi sisa pinjaman.');
        }
        
        $bayarModel->insert([
            'kasbon_id' => $kasbonId,
            'tanggal' => $tanggal,
            'jumlah' => $jumlah,
            'keterangan' => $keterangan,
        ]);

        $dibayarBaru = $kasbon['dibayar'] + $jumlah;
        $statusBaru = $dibayarBaru >= $kasbon['jumlah'] ? 'lunas' : 'belum';

        $kasbonModel->update($kasbonId, [
            'dibayar' => $dibayarBaru,
            'status' => $statusBaru,
        ]);

        return redirect()->back()->with('msg', 'Pembayaran berhasil.');
    }


    public function hapusAngsuran($id)
    {
        $model = new KasbonBayarModel();
        $angsuran = $model->find($id);
        if (!$angsuran) return redirect()->back()->with('msg', 'Data tidak ditemukan');

        $kasbonModel = new KasbonModel();
        $kasbon = $kasbonModel->find($angsuran['kasbon_id']);

        $model->delete($id);

        $total = $kasbon['dibayar'] - $angsuran['jumlah'];
        $kasbonModel->update($kasbon['id'], [
            'dibayar' => $total,
            'status'  => $total >= $kasbon['jumlah'] ? 'lunas' : 'belum'
        ]);

        return redirect()->back()->with('msg', 'Angsuran dihapus');
    }

    public function riwayat($id)
    {
        $kasbon = (new KasbonModel())->find($id);
        $bayar = (new KasbonBayarModel())
            ->where('kasbon_id', $id)
            ->orderBy('tanggal', 'asc')
            ->findAll();

        return view('admin/kasbon/riwayat', compact('kasbon', 'bayar'));
    }

    public function laporan()
    {
        $bulan = $this->request->getGet('bulan');
        $guru = $this->request->getGet('nama');

        $kasbonModel = new KasbonModel();
        $builder = $kasbonModel->select('*');

        if ($bulan) {
            $builder->where("MONTH(tanggal)", $bulan);
        }

        if ($guru) {
            $builder->like("nama", $guru);
        }

        $kasbon = $builder->orderBy('tanggal', 'desc')->findAll();

        return view('admin/kasbon/laporan', [
            'title' => 'Laporan Kasbon',
            'kasbon' => $kasbon,
            'bulan' => $bulan,
            'nama' => $guru,
        ]);
    }

}
