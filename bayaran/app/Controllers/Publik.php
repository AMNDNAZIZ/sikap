<?php

namespace App\Controllers;

use App\Models\SiswaModel;
use App\Models\TagihanSiswaModel;
use App\Models\JenisBayarModel;
use App\Models\PembayaranModel;
use CodeIgniter\Controller;

class Publik extends Controller
{
    public function __construct()
    {
        helper('format');
    }
    public function index()
    {
        $nis = $this->request->getGet('nis');
        $data = ['title' => 'Informasi Keuangan', 'nis' => $nis];

        // ✅ Ambil setting & pengumuman
        $setting = model('SettingModel')->first();
        $data['logo'] = $setting['logo'] ?? 'default-logo.png';
        $data['app_name'] = strtoupper(trim($setting['nama_aplikasi'] ?? ''));
        $data['school_name'] = strtoupper(trim($setting['nama_sekolah'] ?? ''));

        $pengumuman = model('PengumumanModel')->first();
        $data['pengumuman'] = $pengumuman['isi'] ?? null;

        if (!$nis) return view('publik/index', $data);

        $tahunAktif = model('TahunAjaranModel')->where('aktif', 1)->first();

        // ✅ Join kelas_siswa terbaru apapun statusnya
        $siswa = model('SiswaModel')
            ->select('siswa.*, kelas.nama_kelas, kelas_siswa.status as status_kelas')
            ->join('kelas_siswa', 
                'kelas_siswa.siswa_id = siswa.id 
                 AND kelas_siswa.tahun_ajaran_id = ' . $tahunAktif['id'],
                 'left')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('siswa.nis', $nis)
            ->orderBy('kelas_siswa.id', 'desc')
            ->first();

        if (!$siswa) {
            return view('publik/index', [...$data, 'notfound' => true]);
        }

        // ✅ Ambil tagihan siswa
        $tagihanModel = model('TagihanSiswaModel');
        $tagihan = $tagihanModel
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe, jenis_bayar.tahun_ajaran_id, kelas.nama_kelas AS kelas_tagihan')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = tagihan_siswa.siswa_id AND kelas_siswa.tahun_ajaran_id = jenis_bayar.tahun_ajaran_id', 'left')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('tagihan_siswa.siswa_id', $siswa['id'])
            ->orderBy('jenis_bayar.tipe, jenis_bayar.tahun_ajaran_id, tagihan_siswa.jenis_bayar_id')
            ->findAll();

        // ✅ Kelompokkan tagihan
        $grouped = ['bulanan' => [], 'bebas' => []];
        $summary = ['total' => 0, 'dibayar' => 0, 'sisa' => 0];

        foreach ($tagihan as $t) {
            $id = $t['jenis_bayar_id'];
            $tipe = $t['tipe'];

            $grouped[$tipe][$id] ??= [
                'id' => $id,
                'nama' => $t['nama_jenis_bayar'],
                'kelas_tagihan' => $t['kelas_tagihan'] ?? '-',
                'total' => 0,
                'dibayar' => 0,
                'sisa' => 0,
                'items' => [],
            ];

            $grouped[$tipe][$id]['total'] += $t['jumlah'];
            $grouped[$tipe][$id]['dibayar'] += $t['terbayar'];
            $grouped[$tipe][$id]['sisa'] += $t['jumlah'] - $t['terbayar'];
            $grouped[$tipe][$id]['items'][] = $t;

            $summary['total'] += $t['jumlah'];
            $summary['dibayar'] += $t['terbayar'];
        }

        $summary['sisa'] = $summary['total'] - $summary['dibayar'];

        // ✅ Urut bulan hanya untuk bulanan
        foreach ($grouped['bulanan'] as &$b) {
            usort($b['items'], fn($a, $b) => $a['bulan'] <=> $b['bulan']);
        }
        
        $pembayaranMap = [];
        $tagihanIds = [];
        
        foreach ($grouped['bulanan'] as $byr) {
            foreach ($byr['items'] as $item) {
                $tagihanIds[] = $item['id'];
            }
        }
        
        if ($tagihanIds) {
            $pembayaranList = model('PembayaranModel')
                ->whereIn('tagihan_id', $tagihanIds)
                ->orderBy('tanggal', 'desc')
                ->findAll();
        
            foreach ($pembayaranList as $p) {
                $pembayaranMap[$p['tagihan_id']] ??= [];
                $pembayaranMap[$p['tagihan_id']][] = $p;
            }
        
            foreach ($grouped['bulanan'] as &$byr) {
                foreach ($byr['items'] as &$item) {
                    $item['tanggal_pembayaran'] = $pembayaranMap[$item['id']][0]['tanggal'] ?? null;
                }
            }
        }


        return view('publik/index', [
            ...$data,
            'siswa' => $siswa,
            'summary' => $summary,
            'bulanan' => array_values($grouped['bulanan']),
            'bebas' => array_values($grouped['bebas']),
        ]);
    }

    public function rincian($jenisBayarId)
    {
        $pembayaranModel = new PembayaranModel();
        $tagihanModel = new TagihanSiswaModel();

        $tagihan = $tagihanModel
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->where('jenis_bayar_id', $jenisBayarId)
            ->findAll();

        if (!$tagihan) {
            return redirect()->to('/')->with('msg', 'Data tidak ditemukan.');
        }

        $first = $tagihan[0];

        $pembayaran = $pembayaranModel
            ->whereIn('tagihan_id', array_column($tagihan, 'id'))
            ->orderBy('tanggal', 'asc')
            ->findAll();

        return view('publik/rincian', [
            'title' => 'Rincian Pembayaran',
            'tagihan' => $tagihan,
            'pembayaran' => $pembayaran,
            'nama_jenis_bayar' => $first['nama_jenis_bayar'],
            'tipe' => $first['tipe']
        ]);
    }
}
