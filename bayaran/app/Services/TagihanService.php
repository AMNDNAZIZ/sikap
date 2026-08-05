<?php

namespace App\Services;

use App\Models\TagihanSiswaModel;
use App\Models\JenisBayarModel;
use App\Models\PembayaranModel;

class TagihanService
{
    protected TagihanSiswaModel $tagihanModel;
    protected PembayaranModel $pembayaranModel;
    protected JenisBayarModel $jenisBayarModel;

    public function __construct()
    {
        $this->tagihanModel    = new TagihanSiswaModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->jenisBayarModel = new JenisBayarModel();
    }

    public function getAktifTagihanBySiswa(int $siswaId, int $tahunId): array
    {
        $tagihan = $this->tagihanModel
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->where('siswa_id', $siswaId)
            ->where('tagihan_siswa.tahun_ajaran_id', $tahunId)
            ->orderBy('jenis_bayar.tipe', 'asc')
            ->orderBy('tagihan_siswa.jenis_bayar_id', 'asc')
            ->orderBy('FIELD(bulan, 7,8,9,10,11,12,1,2,3,4,5,6)', '', false) // urut Juli-Juni
            ->findAll();

        $grouped = [];

        foreach ($tagihan as $row) {
            $tipe    = $row['tipe'];
            $jenisId = $row['jenis_bayar_id'];

            if (!isset($grouped[$jenisId])) {
                $grouped[$jenisId] = [
                    'nama_jenis_bayar' => $row['nama_jenis_bayar'],
                    'tipe'             => $tipe,
                    'total'            => 0,
                    'terbayar'         => 0,
                    'data'             => [],
                ];
            }

            $grouped[$jenisId]['data'][] = $row;
            $grouped[$jenisId]['total'] += (float) $row['jumlah'];
            $grouped[$jenisId]['terbayar'] += (float) $row['terbayar'];
        }

        return $grouped;
    }

    public function applyPembayaran(array $data)
    {
        if (empty($data['tagihan_id']) || empty($data['tanggal']) || empty($data['cara_bayar'])) {
            return 'Data pembayaran tidak lengkap.';
        }

        $tagihanId = $data['tagihan_id'];
        $tagihan = $this->tagihanModel->find($tagihanId);
        if (!$tagihan) return 'Tagihan tidak ditemukan.';

        $sisa = $tagihan['jumlah'] - $tagihan['terbayar'];
        $insert = [
            'tagihan_id'   => $tagihanId,
            'tanggal'      => $data['tanggal'],
            'cara_bayar'   => $data['cara_bayar'],
        ];

        if ($tagihan['tipe'] === 'bebas') {
            $jumlah = isset($data['jumlah_bayar']) ? (int) $data['jumlah_bayar'] : 0;
            if ($jumlah <= 0) return 'Jumlah bayar tidak valid.';
            if (empty($data['angsuran_ke'])) return 'Angsuran ke- harus diisi.';

            // Hindari kelebihan
            $jumlah = min($jumlah, $sisa);

            // Cek duplikat angsuran
            $exists = $this->pembayaranModel
                ->where('tagihan_id', $tagihanId)
                ->where('angsuran_ke', $data['angsuran_ke'])
                ->first();
            if ($exists) return 'Angsuran ke-' . $data['angsuran_ke'] . ' sudah tercatat.';

            $insert['jumlah_bayar'] = $jumlah;
            $insert['angsuran_ke']  = $data['angsuran_ke'];
            $insert['keterangan']   = 'Angsuran ke-' . $data['angsuran_ke'];
        }

        if ($tagihan['tipe'] === 'bulanan') {
            // Tidak pakai jumlah dari frontend
            $insert['jumlah_bayar'] = $sisa;
            $insert['angsuran_ke'] = null;
            $insert['keterangan'] = null;
        }

        if (!$this->pembayaranModel->insert($insert)) {
            log_message('error', 'Insert pembayaran gagal: ' . json_encode($this->pembayaranModel->errors()));
            return 'Gagal menyimpan pembayaran.';
        }

        // Update tagihan
        $terbayarBaru = $tagihan['terbayar'] + $insert['jumlah_bayar'];
        $this->tagihanModel->update($tagihanId, [
            'terbayar' => $terbayarBaru,
            'status'   => $terbayarBaru >= $tagihan['jumlah'] ? 'lunas' : 'belum',
            'cara_bayar' => $insert['cara_bayar'],
        ]);

        $insert['id'] = $this->pembayaranModel->getInsertID();

        return [
            'status'        => 'ok',
            'id'            => $insert['id'],
            'jumlah_bayar'  => $insert['jumlah_bayar'],
            'cara_bayar'    => $insert['cara_bayar'],
            'keterangan'    => $insert['keterangan'] ?? null,
        ];
    }

    public function cancelPembayaran(int $pembayaranId)
    {
        $bayar = $this->pembayaranModel->find($pembayaranId);
        if (!$bayar) return 'Data pembayaran tidak ditemukan.';

        $tagihan = $this->tagihanModel->find($bayar['tagihan_id']);
        if (!$tagihan) return 'Data tagihan tidak ditemukan.';

        $terbayarBaru = max(0, $tagihan['terbayar'] - $bayar['jumlah_bayar']);

        $this->pembayaranModel->delete($pembayaranId);
        // Ambil pembayaran terakhir jika masih ada
        $lastPembayaran = $this->pembayaranModel
            ->where('tagihan_id', $tagihan['id'])
            ->orderBy('tanggal', 'DESC')
            ->first();

        $this->tagihanModel->update($tagihan['id'], [
            'terbayar' => $terbayarBaru,
            'status'   => $terbayarBaru >= $tagihan['jumlah'] ? 'lunas' : 'belum',
            'cara_bayar' => $lastPembayaran['cara_bayar'] ?? null
        ]);

        return true;
    }
}
