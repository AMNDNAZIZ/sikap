<?php

namespace App\Controllers;

use App\Models\TahunAjaranModel;
use App\Models\KelasModel;
use App\Models\KelasSiswaModel;
use App\Models\JenisBayarModel;
use App\Models\TagihanSiswaModel;

class Tagihan extends BaseController
{
    // 1. Menampilkan form seting tarif per kelas
    public function setingTarif($jenisId)
    {
        helper('form');

        $tahun = (new TahunAjaranModel())->where('aktif', 1)->first();
        $jenis = (new JenisBayarModel())->find($jenisId);
        $kelas = (new KelasModel())->where('aktif', 1)->findAll();
        $kelasFilter = $this->request->getGet('kelas_id');
        $siswaList = [];
        $tagihanMap = [];

        if ($kelasFilter) {
            $siswaList = (new KelasSiswaModel())
                ->withRelations()
                ->where('kelas_siswa.kelas_id', $kelasFilter)
                ->where('kelas_siswa.tahun_ajaran_id', $tahun['id'])
                ->orderBy('siswa.nama', 'ASC')
                ->findAll();

            $siswaIds = array_column($siswaList, 'siswa_id');
            
            $tagihan = [];
            if (!empty($siswaIds)) {
                $tagihan = (new TagihanSiswaModel())
                    ->whereIn('siswa_id', $siswaIds)
                    ->where('jenis_bayar_id', $jenisId)
                    ->where('tahun_ajaran_id', $tahun['id'])
                    ->orderBy('bulan', 'asc')
                    ->findAll();
            }


            // Buat map siswa_id -> tagihan[]
            foreach ($tagihan as $t) {
                $tagihanMap[$t['siswa_id']][] = $t;
            }
        }
        $hasTagihan = [];
        foreach ($siswaList as $siswa) {
            $hasTagihan[$siswa['siswa_id']] = isset($tagihanMap[$siswa['siswa_id']]);
        }

        return view('admin/tagihan/seting', [
            'title'       => 'Seting Tarif & Tagihan',
            'tahun'       => $tahun,
            'jenis'       => $jenis,
            'kelas'       => $kelas,
            'kelasFilter' => $kelasFilter,
            'siswaList'   => $siswaList,
            'tagihanMap'  => $tagihanMap,
            'hasTagihan'  => $hasTagihan,
        ]);
    }


    // 2. Generate tagihan dari form tarif + siswa
    public function generateWithTarif($jenisId)
    {
        $tahun = (new TahunAjaranModel())->where('aktif', 1)->first();
        $kelasId = $this->request->getPost('kelas_id');
        $tarifDefault = str_replace('.', '', $this->request->getPost('tarif'));
        $custom = $this->request->getPost('custom') ?? [];

        $jenis = (new JenisBayarModel())->find($jenisId);
        if (!$kelasId || !$jenis || !$tahun) {
            return redirect()->back()->with('msg', 'Data tidak lengkap atau jenis bayar tidak ditemukan.');
        }

        // Validasi tarif default wajib ada
        if ($tarifDefault === '' || !is_numeric($tarifDefault)) {
            return redirect()->back()->with('msg', 'Tarif default harus diisi.');
        }

        $tarifDefault = (float) $tarifDefault;

        $siswaList = (new KelasSiswaModel())
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran_id', $tahun['id'])
            ->findAll();

        if (!$siswaList) {
            return redirect()->back()->with('msg', 'Tidak ada siswa dalam kelas ini.');
        }

        $siswaIds = array_column($siswaList, 'siswa_id');

        // Cegah generate ulang
        $existingIds = (new TagihanSiswaModel())
            ->select('siswa_id')
            ->whereIn('siswa_id', $siswaIds)
            ->where('jenis_bayar_id', $jenisId)
            ->where('tahun_ajaran_id', $tahun['id'])
            ->groupBy('siswa_id')
            ->findAll();
        
        $existingSiswaIds = array_column($existingIds, 'siswa_id');
        
        $siswaBaru = array_filter($siswaList, fn($s) => !in_array($s['siswa_id'], $existingSiswaIds));
        
        if (!$siswaBaru) {
            return redirect()->back()->with('msg', 'Semua siswa sudah memiliki tagihan. Gunakan edit atau reset untuk mengubah.');
        }
        

        $insert = [];

        foreach ($siswaBaru as $siswa) {
            $id = $siswa['siswa_id'];
            $jumlah = isset($custom[$id]) && is_numeric(str_replace('.', '', $custom[$id]))
                ? (float) str_replace('.', '', $custom[$id])
                : $tarifDefault;

            if ($jumlah <= 0) continue; // Skip jika jumlah 0

            if ($jenis['tipe'] === 'bulanan') {
                for ($i = 7; $i <= 18; $i++) {
                    $bulan = $i > 12 ? $i - 12 : $i;
                    $insert[] = [
                        'siswa_id'        => $id,
                        'jenis_bayar_id'  => $jenisId,
                        'tahun_ajaran_id' => $tahun['id'],
                        'tipe'            => 'bulanan',
                        'bulan'           => $bulan,
                        'jumlah'          => $jumlah,
                        'terbayar'        => 0,
                        'status'          => 'belum',
                        'created_at'      => date('Y-m-d H:i:s'),
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ];
                }
            } else {
                $insert[] = [
                    'siswa_id'        => $id,
                    'jenis_bayar_id'  => $jenisId,
                    'tahun_ajaran_id' => $tahun['id'],
                    'tipe'            => 'bebas',
                    'bulan'           => null,
                    'jumlah'          => $jumlah,
                    'terbayar'        => 0,
                    'status'          => 'belum',
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
            }
        }

        if (!$insert) {
            return redirect()->back()->with('msg', 'Semua jumlah tagihan kosong atau tidak valid.');
        }

        (new TagihanSiswaModel())->insertBatch($insert);

        return redirect()->to("/admin/tagihan/seting/{$jenisId}?kelas_id={$kelasId}")
            ->with('msg', 'Tagihan berhasil digenerate.');

    }

    public function generatePerSiswa($jenisId, $siswaId)
    {
        $kelasId = $this->request->getPost('kelas_id');
        $jumlah = str_replace('.', '', $this->request->getPost('jumlah'));
        $tahun  = (new TahunAjaranModel())->where('aktif', 1)->first();
        $jenis  = (new JenisBayarModel())->find($jenisId);
    
        $isAjax = $this->request->isAJAX();
    
        // Validasi input
        if (!$kelasId || !$tahun || !$jenis || !is_numeric($jumlah) || $jumlah <= 0) {
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Input tidak valid.'
                ]);
            }
            return redirect()->back()->with('msg', 'Input tidak valid.');
        }
    
        // Cek jika sudah ada tagihan
        $exists = (new TagihanSiswaModel())
            ->where('siswa_id', $siswaId)
            ->where('jenis_bayar_id', $jenisId)
            ->where('tahun_ajaran_id', $tahun['id'])
            ->first();
    
        if ($exists) {
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Siswa sudah memiliki tagihan.'
                ]);
            }
            return redirect()->back()->with('msg', 'Siswa sudah memiliki tagihan.');
        }
    
        $insert = [];
        $jumlah = (float) $jumlah;
    
        // Generate tagihan
        if ($jenis['tipe'] === 'bulanan') {
            for ($i = 7; $i <= 18; $i++) {
                $bulan = $i > 12 ? $i - 12 : $i;
                $insert[] = [
                    'siswa_id'        => $siswaId,
                    'jenis_bayar_id'  => $jenisId,
                    'tahun_ajaran_id' => $tahun['id'],
                    'tipe'            => 'bulanan',
                    'bulan'           => $bulan,
                    'jumlah'          => $jumlah,
                    'terbayar'        => 0,
                    'status'          => 'belum',
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
            }
        } else {
            $insert[] = [
                'siswa_id'        => $siswaId,
                'jenis_bayar_id'  => $jenisId,
                'tahun_ajaran_id' => $tahun['id'],
                'tipe'            => 'bebas',
                'bulan'           => null,
                'jumlah'          => $jumlah,
                'terbayar'        => 0,
                'status'          => 'belum',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];
        }
    
        (new TagihanSiswaModel())->insertBatch($insert);
    
        if ($isAjax) {
            return $this->response->setJSON(['success' => true]);
        }
    
        return redirect()->back()->with('msg', 'Tagihan siswa berhasil dibuat.');
    }


    public function generate()
    {
        $data = [
            'kelas'      => (new KelasModel())->where('aktif', 1)->findAll(),
            'jenisBayar' => (new JenisBayarModel())->findAll(),
        ];
        return view('admin/tagihan/generate', $data);
    }


    public function updateTagihan()
    {
        $post = $this->request->getPost();
        $tagihanModel = new TagihanSiswaModel();
    
        if (!isset($post['jumlah']) || !is_array($post['jumlah'])) {
            return redirect()->back()->with('msg', 'Tidak ada data tagihan yang dikirim.');
        }
    
        foreach ($post['jumlah'] as $tagihanId => $jumlah) {
            $tagihanModel->update($tagihanId, ['jumlah' => (float) $jumlah]);
        }
    
        return redirect()->back()->with('msg', 'Tagihan berhasil diperbarui.');
    }

    public function deleteBySiswa($jenisId, $siswaId)
    {
        $tahun = (new TahunAjaranModel())->where('aktif', 1)->first();
        $tagihanModel = new TagihanSiswaModel();

        $tagihanModel
            ->where('jenis_bayar_id', $jenisId)
            ->where('siswa_id', $siswaId)
            ->where('tahun_ajaran_id', $tahun['id'])
            ->set(['jumlah' => 0])
            ->update();

    return redirect()->back()->with('msg', 'Tagihan siswa berhasil direset ke 0.');

    }
    
    public function handleGenerateRedirect()
    {
        $jenisId = $this->request->getPost('jenis_bayar_id');
        return $this->generateWithTarif($jenisId);
    }


}
