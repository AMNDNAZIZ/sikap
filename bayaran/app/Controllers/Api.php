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
            ->select('siswa.*, kelas.nama_kelas')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = ' . $tahunAktif['id'], 'left')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('siswa.nis', $nis)
            ->orderBy('kelas_siswa.id', 'desc')
            ->first();

        if (!$siswa) {
            return $this->failNotFound('Siswa dengan NIS ' . $nis . ' tidak ditemukan.');
        }

        // Ambil semua tagihan siswa
        $tagihan = model('TagihanSiswaModel')
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->where('tagihan_siswa.siswa_id', $siswa['id'])
            ->findAll();

        // Hitung summary
        $totalTagihan = 0;
        $totalDibayar = 0;
        foreach ($tagihan as $t) {
            $totalTagihan += (float) $t['jumlah'];
            $totalDibayar += (float) $t['terbayar'];
        }
        $sisaTunggakan = $totalTagihan - $totalDibayar;

        // Status
        if ($sisaTunggakan <= 0) {
            $status = 'lunas';
        } elseif ($totalDibayar > 0) {
            $status = 'sebagian';
        } else {
            $status = 'belum';
        }

        // Ambil riwayat pembayaran terbaru
        $tagihanIds = array_column($tagihan, 'id');
        $payments = [];

        if (!empty($tagihanIds)) {
            $pembayaranList = model('PembayaranModel')
                ->select('pembayaran.*, tagihan_siswa.jenis_bayar_id, jenis_bayar.nama_jenis_bayar')
                ->join('tagihan_siswa', 'tagihan_siswa.id = pembayaran.tagihan_id')
                ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
                ->whereIn('pembayaran.tagihan_id', $tagihanIds)
                ->orderBy('pembayaran.tanggal', 'DESC')
                ->limit(50)
                ->findAll();

            foreach ($pembayaranList as $p) {
                $payments[] = [
                    'tanggal'     => $p['tanggal'],
                    'keterangan'  => $p['nama_jenis_bayar'] . ($p['keterangan'] ? ' - ' . $p['keterangan'] : ''),
                    'jumlah'      => 'Rp ' . number_format((float) $p['jumlah_bayar'], 0, ',', '.'),
                    'cara_bayar'  => $p['cara_bayar'] ?? 'Tunai',
                    'status'      => 'lunas',
                ];
            }
        }

        // Daftar tagihan per jenis
        $tagihanDetail = [];
        foreach ($tagihan as $t) {
            $sisa = (float) $t['jumlah'] - (float) $t['terbayar'];
            $tagihanDetail[] = [
                'jenis'     => $t['nama_jenis_bayar'],
                'tipe'      => $t['tipe'],
                'bulan'     => $t['bulan'] ?? null,
                'jumlah'    => 'Rp ' . number_format((float) $t['jumlah'], 0, ',', '.'),
                'terbayar'  => 'Rp ' . number_format((float) $t['terbayar'], 0, ',', '.'),
                'sisa'      => 'Rp ' . number_format($sisa, 0, ',', '.'),
                'status'    => $t['status'],
            ];
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'siswa' => [
                    'nis'   => $siswa['nis'],
                    'nama'  => $siswa['nama'],
                    'kelas' => $siswa['nama_kelas'] ?? '-',
                ],
                'summary' => [
                    'total_tagihan'  => 'Rp ' . number_format($totalTagihan, 0, ',', '.'),
                    'total_dibayar'  => 'Rp ' . number_format($totalDibayar, 0, ',', '.'),
                    'sisa_tunggakan' => 'Rp ' . number_format($sisaTunggakan, 0, ',', '.'),
                    'status'         => $status,
                ],
                'tagihan'  => $tagihanDetail,
                'payments' => $payments,
            ],
        ]);
    }
}
