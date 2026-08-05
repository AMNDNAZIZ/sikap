<?php

namespace App\Controllers;

use App\Models\KelasSiswaModel;
use App\Models\TahunAjaranModel;
use App\Models\KelasModel;

class Kenaikan extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new KelasSiswaModel();
    }

    public function index()
    {
        $tahunAktif = (new TahunAjaranModel())->where('aktif', 1)->first();
        if (!$tahunAktif) {
            return redirect()->back()->with('msg', 'Tahun ajaran aktif belum diset.');
        }

        $tahunSebelumnya = (new TahunAjaranModel())
            ->where('id <', $tahunAktif['id'])
            ->orderBy('id', 'desc')
            ->first();

        if (!$tahunSebelumnya) {
            return redirect()->back()->with('msg', 'Belum ada tahun ajaran sebelumnya.');
        }

        $kelas = (new KelasModel())->where('aktif', 1)->findAll();

        $kelasFilter  = $this->request->getGet('kelas_id');
        $statusFilter = $this->request->getGet('status') ?? 'aktif';
        $siswa        = [];

        if ($kelasFilter) {
            // Siswa yang sudah punya entri di tahun aktif
            $sudahNaik = $this->model
                ->select('siswa_id')
                ->where('tahun_ajaran_id', $tahunAktif['id'])
                ->findColumn('siswa_id') ?? [];

            // Siswa yang sudah lulus di tahun sebelumnya
            $sudahLulus = $this->model
                ->select('siswa_id')
                ->where('tahun_ajaran_id', $tahunSebelumnya['id'])
                ->where('status', 'lulus')
                ->findColumn('siswa_id') ?? [];

            // Gabungkan untuk dikecualikan
            $excludeIds = array_unique(array_merge($sudahNaik, $sudahLulus));

            $siswa = $this->model
                ->getByStatus($tahunSebelumnya['id'], $kelasFilter, $statusFilter);

            if ($excludeIds) {
                $siswa = array_filter($siswa, fn($s) => !in_array($s['siswa_id'], $excludeIds));
            }
        }

        // Cek apakah ada siswa tanpa kelas di tahun aktif (untuk tampilkan peringatan)
        $jumlahTanpaKelas = $this->model
            ->where('tahun_ajaran_id', $tahunAktif['id'])
            ->where('status', 'aktif')
            ->groupStart()
                ->where('kelas_id', 0)
                ->orWhere('kelas_id IS NULL')
            ->groupEnd()
            ->countAllResults();

        return view('admin/kenaikan/index', [
            'title'             => 'Kenaikan/Kelulusan Siswa',
            'tahunSebelumnya'   => $tahunSebelumnya,
            'tahunSekarang'     => $tahunAktif,
            'kelas'             => $kelas,
            'kelasFilter'       => $kelasFilter,
            'statusFilter'      => $statusFilter,
            'siswa'             => $siswa,
            'jumlahTanpaKelas'  => $jumlahTanpaKelas,
        ]);
    }

    public function proses()
    {
        $siswaIds    = $this->request->getPost('siswa_ids');
        $kelasTujuan = $this->request->getPost('kelas_id');
        $tahunId     = $this->request->getPost('tahun_ajaran_id');

        // Hanya proses lulus_ids yang juga ada di siswa_ids
        $lulusIds = array_intersect(
            $this->request->getPost('lulus_ids') ?? [],
            $siswaIds ?? []
        );

        if (!$siswaIds || !$tahunId) {
            return redirect()->back()->with('msg_error', 'Mohon pilih siswa terlebih dahulu.');
        }

        // Siswa yang akan naik kelas (bukan lulus)
        $siswaНaikKelas = array_values(array_diff($siswaIds, $lulusIds));

        // Validasi: ada siswa naik kelas tapi kelas tujuan kosong → tolak
        if (count($siswaНaikKelas) > 0 && empty($kelasTujuan)) {
            return redirect()->back()->with('msg_error',
                'Kelas tujuan wajib dipilih karena ada <strong>' . count($siswaНaikKelas) .
                ' siswa</strong> yang akan naik kelas. ' .
                'Jika semua siswa lulus, centang kolom "Lulus" untuk setiap siswa.'
            );
        }

        $data      = [];
        $now       = date('Y-m-d');
        $timestamp = date('Y-m-d H:i:s');
        $jumlahLulus = 0;
        $jumlahNaik  = 0;

        foreach ($siswaIds as $id) {
            $isLulus = in_array($id, $lulusIds);
            $isLulus ? $jumlahLulus++ : $jumlahNaik++;

            $data[] = [
                'siswa_id'        => $id,
                'kelas_id'        => $isLulus ? null : (int)$kelasTujuan,
                'tahun_ajaran_id' => $tahunId,
                'status'          => $isLulus ? 'lulus' : 'aktif',
                'tanggal_masuk'   => $now,
                'created_at'      => $timestamp,
                'updated_at'      => $timestamp,
            ];
        }

        if ($data) {
            $this->model->insertBatch($data);
        }

        $bagian = [];
        if ($jumlahNaik > 0)  $bagian[] = "<strong>{$jumlahNaik} siswa</strong> berhasil naik kelas";
        if ($jumlahLulus > 0) $bagian[] = "<strong>{$jumlahLulus} siswa</strong> berhasil dinyatakan lulus";
        $pesan = 'Proses selesai: ' . implode(' dan ', $bagian) . '.';

        return redirect()->to('/admin/kenaikan')->with('msg_success', $pesan);
    }

    /**
     * Halaman untuk memperbaiki siswa aktif yang tidak punya kelas
     */
    public function perbaiki()
    {
        $tahunAktif = (new TahunAjaranModel())->where('aktif', 1)->first();
        if (!$tahunAktif) {
            return redirect()->back()->with('msg_error', 'Tahun ajaran aktif belum diset.');
        }

        $kelas = (new KelasModel())->where('aktif', 1)->findAll();

        $siswa = $this->model
            ->select('kelas_siswa.id, kelas_siswa.siswa_id, siswa.nis, siswa.nama')
            ->join('siswa', 'siswa.id = kelas_siswa.siswa_id')
            ->where('kelas_siswa.tahun_ajaran_id', $tahunAktif['id'])
            ->where('kelas_siswa.status', 'aktif')
            ->groupStart()
                ->where('kelas_siswa.kelas_id', 0)
                ->orWhere('kelas_siswa.kelas_id IS NULL')
            ->groupEnd()
            ->orderBy('siswa.nama', 'ASC')
            ->findAll();

        return view('admin/kenaikan/perbaiki', [
            'title'      => 'Perbaiki Siswa Tanpa Kelas',
            'siswa'      => $siswa,
            'kelas'      => $kelas,
            'tahunAktif' => $tahunAktif,
        ]);
    }

    public function perbaikiSimpan()
    {
        $items = $this->request->getPost('perbaiki') ?? [];

        if (empty($items)) {
            return redirect()->back()->with('msg_error', 'Tidak ada data yang diperbaiki.');
        }

        $berhasil = 0;
        foreach ($items as $ksId => $kelasId) {
            if (empty($kelasId)) continue;
            $this->model->update((int)$ksId, [
                'kelas_id'   => (int)$kelasId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $berhasil++;
        }

        return redirect()->to('/admin/kenaikan/perbaiki')
            ->with('msg_success', "<strong>{$berhasil} siswa</strong> berhasil diperbaiki kelasnya.");
    }
}
