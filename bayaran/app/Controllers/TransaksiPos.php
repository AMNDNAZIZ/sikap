<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PosBayarModel;
use App\Models\TransaksiPosModel;
use App\Models\JenisBayarModel;
use App\Models\PembayaranModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TransaksiPos extends BaseController
{
    protected $trxModel;
    protected $pembayaranModel;
    protected $transaksiPosModel;
    protected $jenisBayarModel;

    public function __construct()
    {
        $this->trxModel          = new TransaksiPosModel();
        $this->pembayaranModel   = new PembayaranModel();
        $this->transaksiPosModel = new TransaksiPosModel();
        $this->jenisBayarModel   = new JenisBayarModel();
    }

    /* =========================================================================
       INDEX
       ========================================================================= */
    public function index()
    {
        $role = session('role');
        $jenisBayarModel = new JenisBayarModel();
        $posModel = new PosBayarModel();

        // Ambil POS sesuai role
        $bulananPosIds = $jenisBayarModel->where('tipe', 'bulanan')->groupBy('pos_bayar_id')->findColumn('pos_bayar_id') ?? [];
        $bebasPosIds   = $jenisBayarModel->where('tipe', 'bebas')->groupBy('pos_bayar_id')->findColumn('pos_bayar_id') ?? [];

        if ($role === 'superadmin' || $role === 'staf') {
            $posList = $posModel->findAll();
        } elseif ($role === 'admin') {
            $posList = $posModel->whereIn('id', $bulananPosIds)->findAll();
        } elseif ($role === 'operator') {
            $posList = $posModel->whereIn('id', $bebasPosIds)->findAll();
        } else {
            $posList = [];
        }

        $data = [];
        foreach ($posList as $pos) {
            $summary = $this->getPosSummary($pos['id']);

            $isBulanan = in_array($pos['id'], $bulananPosIds);
            $isBebas   = in_array($pos['id'], $bebasPosIds);
            $canManage = $role === 'superadmin'
                      || ($role === 'admin' && $isBulanan)
                      || ($role === 'operator' && $isBebas);

            $unsynced = $this->getUnsycnedTotalByPos($pos['id']);

            $data[] = [
                'id'           => $pos['id'],
                'nama'         => $pos['nama_pos'],
                'masuk'        => $summary['masuk'],
                'keluar'       => $summary['keluar'],
                'saldo'        => $summary['saldo'],
                'isBebas'      => $isBebas,
                'isBulanan'    => $isBulanan,
                'canManage'    => $canManage,
                'unsynced'     => $unsynced,
            ];
        }

        return view('admin/transaksi_pos/index', [
            'posList'        => $data,
            'role'           => $role,
            'bulananPosIds'  => $bulananPosIds,
            'bebasPosIds'    => $bebasPosIds
        ]);
    }

    /* =========================================================================
       SINKRON (Versi Stabil 100%)
       ========================================================================= */
    public function sinkron($posId)
    {
        $role = session('role');

        // Hak akses: admin & superadmin boleh semua pos
        $bebasPosIds = $this->jenisBayarModel
            ->where('tipe', 'bebas')
            ->findColumn('pos_bayar_id') ?? [];

        $isBebas = in_array($posId, $bebasPosIds);

        if (!in_array($role, ['superadmin', 'admin']) && !($role === 'operator' && $isBebas)) {
            return redirect()->back()->with('msg', 'Anda tidak punya akses sinkronisasi.');
        }

        // Ambil jenis bayar terkait POS
        $jenisBayarIds = $this->jenisBayarModel
            ->where('pos_bayar_id', $posId)
            ->findColumn('id');

        if (!$jenisBayarIds) {
            return redirect()->back()->with('msg', 'Tidak ada jenis bayar untuk pos ini.');
        }

        // 1. Ambil ID tagihan siswa
        $tagihanIds = db_connect()->table('tagihan_siswa')
            ->whereIn('jenis_bayar_id', $jenisBayarIds)
            ->select('id')
            ->get()
            ->getResultArray();

        $tagihanIds = array_column($tagihanIds, 'id');

        if (!$tagihanIds) {
            return redirect()->back()->with('msg', 'Tidak ada tagihan siswa.');
        }

        // 2. Ambil pembayaran yang belum disinkron
        $pembayaran = $this->pembayaranModel
            ->whereIn('tagihan_id', $tagihanIds)
            ->where('sinkron_id IS NULL', null, false)
            ->findAll();

        if (!$pembayaran) {
            return redirect()->back()->with('msg', 'Tidak ada pembayaran baru untuk disinkron.');
        }

        $total = array_sum(array_column($pembayaran, 'jumlah_bayar'));

        // 3. Insert transaksi sinkron POS
        $sinkronId = $this->trxModel->insert([
            'pos_bayar_id' => $posId,
            'tanggal'      => date('Y-m-d'),
            'tipe'         => 'masuk',
            'jumlah'       => $total,
            'keterangan'   => 'Sinkronisasi otomatis dari pembayaran siswa',
        ]);

        // 4. Tandai transaksi_pos sinkron_id = id sendiri
        $this->trxModel->update($sinkronId, ['sinkron_id' => $sinkronId]);

        // 5. Update semua pembayaran
        $idsPembayaran = array_column($pembayaran, 'id');
        $this->pembayaranModel->update($idsPembayaran, [
            'sinkron_id' => $sinkronId,
        ]);

        return redirect()
            ->to(site_url("admin/transaksi-pos/detail/{$posId}"))
            ->with('msg', 'Sinkronisasi berhasil.');
    }


    /* =========================================================================
       PENGELUARAN / PEMASUKAN MANUAL
       ========================================================================= */
    public function keluar()
    {
        $data = $this->request->getPost();
        $data['tipe'] = 'keluar';
        $data['sinkron_id'] = null;

        $this->transaksiPosModel->insert($data);
        return redirect()->back()->with('msg', 'Pengeluaran tercatat.');
    }

    public function masuk()
    {
        $data = $this->request->getPost();
        $data['tipe'] = 'masuk';
        $data['sinkron_id'] = null;

        $this->transaksiPosModel->insert($data);
        return redirect()->back()->with('msg', 'Pemasukan manual tercatat.');
    }

    /* =========================================================================
       DETAIL POS
       ========================================================================= */
    public function detail($posId)
    {
        $model        = $this->trxModel;
        $posModel     = new \App\Models\PosBayarModel();
        $jenisModel   = $this->jenisBayarModel;
    
        $page    = (int) ($this->request->getGet('page') ?? 1);
        $perPage = 20;
    
        // Ambil filter dasar dari request
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');
        $search = $this->request->getGet('search');
    
        // Ambil daftar jenis bayar per POS untuk kebutuhan view (bulanan/bebas)
        $bulananPosIds = $jenisModel->where('tipe', 'bulanan')->groupBy('pos_bayar_id')->findColumn('pos_bayar_id') ?? [];
        $bebasPosIds   = $jenisModel->where('tipe', 'bebas')->groupBy('pos_bayar_id')->findColumn('pos_bayar_id') ?? [];
    
        // Ambil info POS
        $pos = $posModel->find($posId);
        if (!$pos) return redirect()->back()->with('error', 'POS tidak ditemukan.');
    
        $tipe = $jenisModel->where('pos_bayar_id', $posId)->limit(1)->first()['tipe'] ?? '-';
        $pos['tipe'] = $tipe;
    
        $summary = $this->getPosSummary($posId);
    
        $db = db_connect();
    
        /*
         *  Buat daftar bulan yang ada pada transaksi_pos untuk POS ini
         *  Format: YYYY-MM
         */
        $bulanRows = $db->table('transaksi_pos')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') AS bulan", false)
            ->where('pos_bayar_id', $posId)
            ->groupBy("DATE_FORMAT(tanggal, '%Y-%m')")
            ->orderBy('bulan', 'desc')
            ->get()
            ->getResultArray();
    
        $bulanList = array_column($bulanRows, 'bulan');
    
        // Ambil pilihan bulan dari request
        $filterBulan = $this->request->getGet('bulan');
    
        // Jika filter bulan dipilih, override start/end
        if (!empty($filterBulan)) {
            $start = $filterBulan . '-01';
            $end   = date('Y-m-t', strtotime($start)); // last day of month
        }
    
        // Ringkasan bulanan (semua bulan untuk POS) — dapat ditampilkan pada view
        $ringkasanBulanan = $db->table('transaksi_pos')
            ->select("
                DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
                SUM(IF(tipe='masuk', jumlah, 0)) AS total_masuk,
                SUM(IF(tipe='keluar', jumlah, 0)) AS total_keluar
            ", false)
            ->where('pos_bayar_id', $posId)
            ->groupBy("DATE_FORMAT(tanggal, '%Y-%m')")
            ->orderBy('bulan', 'asc')
            ->get()
            ->getResultArray();
    
        // Ambil semua transaksi ASC untuk hitung saldo berjalan (menggunakan start/end/search)
        $saldoQuery = $model->where('pos_bayar_id', $posId);
        if ($start) $saldoQuery->where('tanggal >=', $start);
        if ($end)   $saldoQuery->where('tanggal <=', $end);
        if ($search) $saldoQuery->like('keterangan', $search);
    
        $semuaTransaksi = $saldoQuery->orderBy('tanggal', 'asc')->orderBy('id', 'asc')->findAll();
    
        $saldoBerjalan = 0;
        $saldoList = [];
        foreach ($semuaTransaksi as $t) {
            $jumlah = (int) $t['jumlah'];
            $saldoBerjalan += $t['tipe'] === 'masuk' ? $jumlah : -$jumlah;
            $saldoList[$t['id']] = $saldoBerjalan;
        }
    
        // Ambil transaksi untuk tampilan (DESC + paginasi)
        $transaksiQuery = $model->where('pos_bayar_id', $posId);
        if ($start) $transaksiQuery->where('tanggal >=', $start);
        if ($end)   $transaksiQuery->where('tanggal <=', $end);
        if ($search) $transaksiQuery->like('keterangan', $search);
    
        $transaksi = $transaksiQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage, 'default');
    
        $pager = $model->pager;
    
        // Kirim semua variabel yang dibutuhkan view
        return view('admin/transaksi_pos/detail', [
            'pos'              => $pos,
            'summary'          => $summary,
            'transaksi'        => $transaksi,
            'saldoList'        => $saldoList,
            'pager'            => $pager,
            'start'            => $start,
            'end'              => $end,
            'page'             => $page,
            'search'           => $search,
    
            // Tambahan untuk view bulan & ringkasan
            'bulanList'        => $bulanList,
            'filterBulan'      => $filterBulan,
            'ringkasanBulanan' => $ringkasanBulanan,
    
            // Untuk hak akses/view logic
            'bulananPosIds'    => $bulananPosIds,
            'bebasPosIds'      => $bebasPosIds,
        ]);
    }

    /* =========================================================================
       EDIT + DELETE
       ========================================================================= */
    public function edit($id)
    {
        $trx = $this->transaksiPosModel->find($id);
        if (!$trx) return redirect()->back()->with('error', 'Data tidak ditemukan.');

        return view('admin/transaksi_pos/edit', [
            'transaksi' => $trx,
            'pos'       => (new PosBayarModel())->find($trx['pos_bayar_id'])
        ]);
    }

    public function update($id)
    {
        $data = $this->request->getPost();

        // Hanya transaksi manual yang boleh diedit
        $this->transaksiPosModel->update($id, [
            'tanggal'    => $data['tanggal'],
            'jumlah'     => (int)$data['jumlah'],
            'keterangan' => $data['keterangan']
        ]);

        return redirect()->to("admin/transaksi-pos/detail/{$data['pos_bayar_id']}")->with('msg', 'Transaksi diperbarui.');
    }

    public function delete($id)
    {
        $trx = $this->transaksiPosModel->find($id);
        if (!$trx) return redirect()->back()->with('error', 'Data tidak ditemukan.');

        // Jika transaksi sinkron → restore pembayaran
        if (!empty($trx['sinkron_id'])) {
            db_connect()->table('pembayaran')
                ->where('sinkron_id', $trx['sinkron_id'])
                ->update(['sinkron_id' => null]);
        }

        $this->transaksiPosModel->delete($id);

        return redirect()->to("admin/transaksi-pos/detail/{$trx['pos_bayar_id']}")->with('msg', 'Transaksi dihapus.');
    }

    /* =========================================================================
       HELPER
       ========================================================================= */
    private function getPosSummary($posId): array
    {
        $model = $this->transaksiPosModel;

        $masuk = $model->where('pos_bayar_id', $posId)->where('tipe', 'masuk')->selectSum('jumlah')->first()['jumlah'] ?? 0;
        $keluar = $model->where('pos_bayar_id', $posId)->where('tipe', 'keluar')->selectSum('jumlah')->first()['jumlah'] ?? 0;

        return [
            'masuk' => (int)$masuk,
            'keluar'=> (int)$keluar,
            'saldo' => (int)$masuk - (int)$keluar
        ];
    }

    private function getUnsycnedTotalByPos($posId): int
    {
        $jenisBayarIds = $this->jenisBayarModel
            ->where('pos_bayar_id', $posId)
            ->findColumn('id');

        if (empty($jenisBayarIds)) return 0;

        $tagihanIds = db_connect()->table('tagihan_siswa')
            ->whereIn('jenis_bayar_id', $jenisBayarIds)
            ->select('id')->get()->getResultArray();

        $tagihanIds = array_column($tagihanIds, 'id');
        if (empty($tagihanIds)) return 0;

        $total = $this->pembayaranModel
            ->whereIn('tagihan_id', $tagihanIds)
            ->where('sinkron_id IS NULL', null, false)
            ->selectSum('jumlah_bayar','total')
            ->first()['total'] ?? 0;

        return (int)$total;
    }
}
