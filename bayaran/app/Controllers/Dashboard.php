<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SiswaModel;
use App\Models\KelasSiswaModel;
use App\Models\TahunAjaranModel;
use App\Models\PembayaranModel;
use App\Models\KasbonModel;
use App\Models\KasbonBayarModel;
use App\Models\TagihanSiswaModel;
use App\Models\JenisBayarModel;
use App\Models\PosBayarModel;
use App\Models\TransaksiPosModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $tahunAjaranModel = new TahunAjaranModel();
        $tahunAktif       = $tahunAjaranModel->where('aktif', 1)->first();
        $tahunId          = $tahunAktif['id'] ?? null;

        // ✅ Jumlah siswa aktif tahun ini
        $tahunAktif = model('TahunAjaranModel')->where('aktif', 1)->first();
        $tahunId = $tahunAktif['id'] ?? null;
        
        $siswaAktif = 0;
        if ($tahunId) {
            $siswaAktif = (new KelasSiswaModel())
                ->where('tahun_ajaran_id', $tahunId)
                ->where('status', 'aktif')
                ->distinct()
                ->countAllResults('siswa_id');
        }


        // ✅ Total penerimaan hari ini
        $penerimaanHariIni = (new PembayaranModel())
            ->where('tanggal', date('Y-m-d'))
            ->selectSum('jumlah_bayar')
            ->first()['jumlah_bayar'] ?? 0;

        // ✅ Saldo per pos
        $transaksiModel = new TransaksiPosModel();
        $posList        = (new PosBayarModel())->findAll();
        $saldoPerPos    = [];

        foreach ($posList as $pos) {
            $masuk = $transaksiModel
                ->where('pos_bayar_id', $pos['id'])
                ->where('tipe', 'masuk')
                ->selectSum('jumlah')
                ->first()['jumlah'] ?? 0;

            $keluar = $transaksiModel
                ->where('pos_bayar_id', $pos['id'])
                ->where('tipe', 'keluar')
                ->selectSum('jumlah')
                ->first()['jumlah'] ?? 0;

            $saldoPerPos[] = [
                'id'    => $pos['id'],
                'nama'  => $pos['nama_pos'],
                'saldo' => (int)$masuk - (int)$keluar
            ];
        }

        // ✅ Hitung kasbon belum lunas
        $kasbonModel   = new KasbonModel();
        $angsuranModel = new KasbonBayarModel();
        $kasbonList    = $kasbonModel->findAll();
        $totalKasbon   = 0;

        foreach ($kasbonList as $kasbon) {
            $dibayar = $angsuranModel
                ->where('kasbon_id', $kasbon['id'])
                ->selectSum('jumlah')
                ->first()['jumlah'] ?? 0;

            $sisa = $kasbon['jumlah'] - $dibayar;
            if ($sisa > 0) {
                $totalKasbon += $sisa;
            }
        }

        // ✅ Saldo pos bulanan dan kas real
        $jenisBayarModel = new JenisBayarModel();
        $posBayarModel   = new PosBayarModel();

        $bulananPosIds = array_unique(
            $jenisBayarModel->where('tipe', 'bulanan')->findColumn('pos_bayar_id') ?? []
        );

        $saldoBulanan = 0;
        foreach ($saldoPerPos as $pos) {
            if (in_array($pos['id'], $bulananPosIds)) {
                $saldoBulanan += $pos['saldo'];
            }
        }

        $kasReal = $saldoBulanan - $totalKasbon;

        // ✅ Total tagihan per pos tahun ini
        $jenisList = $jenisBayarModel
            ->where('tahun_ajaran_id', $tahunId)
            ->findAll();

        $tagihanPerPos = [];
        $tagihanModel  = new TagihanSiswaModel();

        foreach ($jenisList as $jb) {
            $totalTagihan = $tagihanModel
                ->where('jenis_bayar_id', $jb['id'])
                ->selectSum('jumlah')
                ->first()['jumlah'] ?? 0;

            $posId = $jb['pos_bayar_id'];
            if (!isset($tagihanPerPos[$posId])) {
                $posData = $posBayarModel->find($posId);
                $tagihanPerPos[$posId] = [
                    'nama'   => $posData['nama_pos'] ?? 'Tidak Diketahui',
                    'jumlah' => 0,
                ];
            }

            $tagihanPerPos[$posId]['jumlah'] += $totalTagihan;
        }

        return view('admin/dashboard', [
            'username'           => session('username'),
            'siswaAktif'         => $siswaAktif,
            'penerimaanHariIni'  => $penerimaanHariIni,
            'saldoPos'           => $saldoPerPos,
            'totalKasbon'        => $totalKasbon,
            'kasReal'            => $kasReal,
            'tagihanPerPos'      => array_values($tagihanPerPos),
        ]);
    }
}
