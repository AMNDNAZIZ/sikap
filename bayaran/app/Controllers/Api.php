<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

class Api extends Controller
{
    use ResponseTrait;

    /**
     * Validasi API Key (opsional — jika setting api_key diisi)
     */
    private function validateApiKey(): bool
    {
        $setting = model('SettingModel')->first();
        $expectedKey = $setting['api_key'] ?? '';

        // Jika api_key kosong di setting, izinkan tanpa key
        if (empty($expectedKey)) {
            return true;
        }

        $providedKey = $this->request->getHeaderLine('X-API-Key');
        return $providedKey === $expectedKey;
    }

    /**
     * GET /api/payment-info?nis=xxx
     * 
     * Endpoint untuk SIAKAD mengambil informasi pembayaran siswa.
     * Identifikasi via NIS.
     */
    public function paymentInfo()
    {
        // Validasi API Key
        if (!$this->validateApiKey()) {
            return $this->failUnauthorized('API Key tidak valid.');
        }

        $nis = $this->request->getGet('nis');
        if (empty($nis)) {
            return $this->fail('Parameter nis diperlukan.', 400);
        }

        // Cari siswa berdasarkan NIS
        $tahunAktif = model('TahunAjaranModel')->where('aktif', 1)->first();
        if (!$tahunAktif) {
            return $this->fail('Tahun ajaran aktif tidak ditemukan.', 500);
        }

        $siswa = model('SiswaModel')
            ->select('siswa.*, kelas.nama_kelas, kelas_siswa.status as status_kelas')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = ' . $tahunAktif['id'], 'left')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('siswa.nis', $nis)
            ->orderBy('kelas_siswa.id', 'desc')
            ->first();

        if (!$siswa) {
            return $this->failNotFound('Siswa dengan NIS ' . $nis . ' tidak ditemukan.');
        }

        // Ambil semua tagihan siswa (sama dengan Publik::index)
        $tagihan = model('TagihanSiswaModel')
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe, jenis_bayar.tahun_ajaran_id, kelas.nama_kelas AS kelas_tagihan')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = tagihan_siswa.siswa_id AND kelas_siswa.tahun_ajaran_id = jenis_bayar.tahun_ajaran_id', 'left')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('tagihan_siswa.siswa_id', $siswa['id'])
            ->orderBy('jenis_bayar.tipe, jenis_bayar.tahun_ajaran_id, tagihan_siswa.jenis_bayar_id')
            ->findAll();

        // Kelompokkan tagihan (sama persis dengan Publik::index)
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

            $grouped[$tipe][$id]['total'] += (float) $t['jumlah'];
            $grouped[$tipe][$id]['dibayar'] += (float) $t['terbayar'];
            $grouped[$tipe][$id]['sisa'] += (float) $t['jumlah'] - (float) $t['terbayar'];
            $grouped[$tipe][$id]['items'][] = $t;

            $summary['total'] += (float) $t['jumlah'];
            $summary['dibayar'] += (float) $t['terbayar'];
        }
        $summary['sisa'] = $summary['total'] - $summary['dibayar'];

        // Urut bulan untuk bulanan
        foreach ($grouped['bulanan'] as &$b) {
            usort($b['items'], fn($a, $bx) => ($a['bulan'] ?? 0) <=> ($bx['bulan'] ?? 0));
        }
        unset($b);

        // Ambil pembayaran untuk bulanan items (tanggal bayar)
        $tagihanIds = [];
        foreach ($grouped['bulanan'] as $byr) {
            foreach ($byr['items'] as $item) {
                $tagihanIds[] = $item['id'];
            }
        }
        foreach ($grouped['bebas'] as $byr) {
            foreach ($byr['items'] as $item) {
                $tagihanIds[] = $item['id'];
            }
        }

        $pembayaranMap = [];
        if (!empty($tagihanIds)) {
            $pembayaranList = model('PembayaranModel')
                ->whereIn('tagihan_id', $tagihanIds)
                ->orderBy('tanggal', 'desc')
                ->findAll();

            foreach ($pembayaranList as $p) {
                $pembayaranMap[$p['tagihan_id']][] = $p;
            }
        }

        // Tambahkan info tanggal pembayaran ke items bulanan
        foreach ($grouped['bulanan'] as &$byr) {
            foreach ($byr['items'] as &$item) {
                $item['tanggal_pembayaran'] = $pembayaranMap[$item['id']][0]['tanggal'] ?? null;
                $item['cara_bayar_detail'] = $pembayaranMap[$item['id']][0]['cara_bayar'] ?? null;
            }
        }
        unset($byr, $item);

        // Untuk bebas, sertakan riwayat pembayaran per jenis
        foreach ($grouped['bebas'] as &$byr) {
            $bebasTagihanIds = array_column($byr['items'], 'id');
            $byr['pembayaran'] = [];
            foreach ($bebasTagihanIds as $tid) {
                if (isset($pembayaranMap[$tid])) {
                    foreach ($pembayaranMap[$tid] as $p) {
                        $byr['pembayaran'][] = $p;
                    }
                }
            }
            // Urutkan dari terlama
            usort($byr['pembayaran'], fn($a, $bx) => strtotime($a['tanggal']) - strtotime($bx['tanggal']));
        }
        unset($byr);

        return $this->respond([
            'success' => true,
            'data' => [
                'siswa' => [
                    'nis'   => $siswa['nis'],
                    'nama'  => $siswa['nama'],
                    'kelas' => $siswa['nama_kelas'] ?? '-',
                ],
                'summary' => $summary,
                'bulanan' => array_values($grouped['bulanan']),
                'bebas'   => array_values($grouped['bebas']),
            ],
        ]);
    }
}
