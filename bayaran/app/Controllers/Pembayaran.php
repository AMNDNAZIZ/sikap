<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SiswaModel;
use App\Models\KelasSiswaModel;
use App\Models\TagihanSiswaModel;
use App\Models\PembayaranModel;
use App\Services\TagihanService;
use App\Models\SettingModel;
use CodeIgniter\HTTP\ResponseInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class Pembayaran extends BaseController
{
    protected TagihanService $tagihanService;
    protected $pembayaranModel;
    protected $session;
    protected $role;

    public function __construct()
    {
        $this->tagihanService = new TagihanService();
        $this->pembayaranModel = new PembayaranModel();
        $this->session = session();
        $this->role = $this->session->get('role');
        helper(['format']);

    }

    public function index()
    {
        $siswaId = $this->request->getGet('siswa_id');
        $data = ['title' => 'Pembayaran Siswa', 'siswa_id' => $siswaId];

        // Cek role
        $role = session('role');
        if ($role === 'staf') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (!$siswaId)
            return view('admin/pembayaran/index', $data);

        $tahunAktif = model('TahunAjaranModel')->where('aktif', 1)->first();

        $siswa = (new SiswaModel())
            ->select('siswa.*,
                kelas.nama_kelas,
                kelas_siswa.status as status_kelas')
            ->join(
                'kelas_siswa',
                'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = ' . $tahunAktif['id'],
                'left'
            )
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('siswa.id', $siswaId)
            ->orderBy('kelas_siswa.id', 'desc')
            ->first();

        if (!$siswa) {
            $data['notfound'] = true;
            return view('admin/pembayaran/index', $data);
        }

        // Ambil pembayaran & tagihan
        $tagihanModel = new TagihanSiswaModel();
        $pembayaranModel = new PembayaranModel();

        $pembayaranAll = $pembayaranModel
            ->select('id, tagihan_id, tanggal')
            ->whereIn('tagihan_id', function ($builder) use ($siswaId) {
                return $builder->select('id')
                    ->from('tagihan_siswa')
                    ->where('siswa_id', $siswaId);
            })
            ->findAll();

        $pembayaranMap = [];
        foreach ($pembayaranAll as $p) {
            $pembayaranMap[$p['tagihan_id']] = [
                'id' => $p['id'],
                'tanggal' => $p['tanggal']
            ];
        }

        $tagihan = $tagihanModel
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe, kelas.nama_kelas AS kelas_tagihan')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = tagihan_siswa.siswa_id AND kelas_siswa.tahun_ajaran_id = jenis_bayar.tahun_ajaran_id', 'left')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('tagihan_siswa.siswa_id', $siswaId)
            ->orderBy('jenis_bayar.tipe')
            ->orderBy('jenis_bayar.tahun_ajaran_id')
            ->orderBy('tagihan_siswa.jenis_bayar_id')
            ->findAll();
        $grouped = ['bulanan' => [], 'bebas' => []];
        $summary = ['total' => 0, 'dibayar' => 0];

        foreach ($tagihan as &$t) {
            $id = $t['jenis_bayar_id'];
            $tipe = $t['tipe'];

            // Filter by role
            if (
                ($role === 'admin' && $tipe !== 'bulanan') ||
                ($role === 'operator' && $tipe !== 'bebas')
            ) {
                continue;
            }

            if ($t['status'] === 'lunas' && isset($pembayaranMap[$t['id']])) {
                $t['tanggal'] = $pembayaranMap[$t['id']]['tanggal'];
                $t['pembayaran_id'] = $pembayaranMap[$t['id']]['id'];
            } else {
                $t['tanggal'] = null;
                $t['pembayaran_id'] = null;
            }

            if (!isset($grouped[$tipe][$id])) {
                $grouped[$tipe][$id] = [
                    'id' => $id,
                    'nama' => $t['nama_jenis_bayar'],
                    'kelas_tagihan' => $t['kelas_tagihan'] ?? '-',
                    'total' => 0,
                    'dibayar' => 0,
                    'sisa' => 0,
                    'items' => [],
                ];
            }

            $grouped[$tipe][$id]['total'] += $t['jumlah'];
            $grouped[$tipe][$id]['dibayar'] += $t['terbayar'];
            $grouped[$tipe][$id]['sisa'] += $t['jumlah'] - $t['terbayar'];
            $grouped[$tipe][$id]['items'][] = $t;

            $summary['total'] += $t['jumlah'];
            $summary['dibayar'] += $t['terbayar'];
        }

        $summary['sisa'] = $summary['total'] - $summary['dibayar'];

        // Urutkan bulan hanya untuk bulanan
        foreach ($grouped['bulanan'] as &$b) {
            usort($b['items'], fn($a, $b) => (int) $a['bulan'] <=> (int) $b['bulan']);
        }

        $data['summary'] = $summary;
        $data['siswa'] = $siswa;

        $data['bulanan'] = ($role === 'operator') ? [] : array_values($grouped['bulanan']);
        $data['bebas'] = ($role === 'admin') ? [] : array_values($grouped['bebas']);

        return view('admin/pembayaran/index', $data);
    }



    public function searchSiswa()
    {
        $q = $this->request->getGet('q');

        $results = (new SiswaModel())
            ->like('nama', $q)
            ->orLike('nis', $q)
            ->select('id, nama, nis')
            ->limit(10)
            ->findAll();

        return $this->response->setJSON([
            'results' => array_map(fn($r) => [
                'id' => $r['id'],
                'text' => "{$r['nis']} - {$r['nama']}"
            ], $results)
        ]);
    }

    public function bayar()
    {
        $post = $this->request->getPost();

        $data = [
            'tagihan_id' => $post['tagihan_id'] ?? $post['bulan'] ?? null,
            'siswa_id' => $post['siswa_id'],
            'tanggal' => $post['tanggal'] ?? date('Y-m-d'),
            'cara_bayar' => $post['cara_bayar'] ?? 'Tunai',
            'jumlah_bayar' => (int) str_replace('.', '', $post['jumlah_bayar'] ?? 0),
            'jenis_bayar_id' => $post['jenis_bayar_id'] ?? null,
            'angsuran_ke' => $post['angsuran_ke'] ?? null,
        ];

        $result = $this->tagihanService->applyPembayaran($data);

        if (!is_array($result)) {
            log_message('error', 'Bayar gagal: ' . json_encode($result));
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => $result,
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'id' => $result['id'],
            'jumlah_bayar' => $data['jumlah_bayar'],
            'cara_bayar' => $data['cara_bayar'],
            'keterangan' => $result['keterangan'] ?? '-',
            'csrf_name' => csrf_token(),
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function deleteBayar($id)
    {
        $result = $this->tagihanService->cancelPembayaran((int) $id);

        if ($result !== true) {
            log_message('error', "Hapus pembayaran gagal: $result");
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => $result,
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'csrf_name' => csrf_token(),
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function cetak($id)
    {
        $pembayaranModel = new PembayaranModel();
        $tagihanModel = new TagihanSiswaModel();
        $siswaModel = new SiswaModel();
        $kelasModel = new KelasSiswaModel();

        $pembayaran = $pembayaranModel->find($id);
        if (!$pembayaran)
            return $this->failNotFound('Pembayaran tidak ditemukan');

        $tagihan = $tagihanModel
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->where('tagihan_siswa.id', $pembayaran['tagihan_id'])
            ->first();

        $siswa = $siswaModel->find($tagihan['siswa_id']);
        $kelas = $kelasModel
            ->withRelations()
            ->where('siswa_id', $siswa['id'])
            ->where('tahun_ajaran_id', $tagihan['tahun_ajaran_id'])
            ->first();

        $data = [
            'nama_siswa' => $siswa['nama'] ?? '-',
            'nis' => $siswa['nis'] ?? '-',
            'nama_kelas' => $kelas['nama_kelas'] ?? '-',
            'nama_jenis_bayar' => $tagihan['nama_jenis_bayar'],
            'tipe' => $tagihan['tipe'],
            'bulan' => $tagihan['tipe'] === 'bulanan' ? $tagihan['bulan'] : null,
            'tanggal' => $pembayaran['tanggal'],
            'cara_bayar' => $pembayaran['cara_bayar'],
            'jumlah_bayar' => $pembayaran['jumlah_bayar'],
            'keterangan' => $pembayaran['keterangan'] ?? '',
            'total_tagihan' => $tagihan['jumlah'],
            'terbayar' => $tagihan['terbayar'],
            'status_tagihan' => $tagihan['status'],
        ];

        $qrLines = [
            'Bukti Pembayaran',
            "Nama: {$data['nama_siswa']}",
            "NIS: {$data['nis']}",
            "Kelas: {$data['nama_kelas']}",
            "Jenis Bayar: {$data['nama_jenis_bayar']}",
            "Tanggal: " . date('d-m-Y', strtotime($data['tanggal'])),
            "Jumlah: Rp " . number_format($data['jumlah_bayar'], 0, ',', '.'),
            "Cara Bayar: {$data['cara_bayar']}",
        ];

        if ($data['tipe'] === 'bulanan' && $data['bulan']) {
            $qrLines[] = 'Bulan: ' . bulanIndo($data['bulan']);
        }

        if ($data['tipe'] === 'bebas') {
            $qrLines[] = 'Keterangan: ' . ($data['keterangan'] ?: '-');
            $qrLines[] = 'Sisa: Rp ' . number_format($data['total_tagihan'] - $data['terbayar'], 0, ',', '.');
        }

        $qrCode = (new QrCode(implode("\n", $qrLines)))
            ->setSize(120)
            ->setMargin(10);
        $qrImage = base64_encode((new SvgWriter())->write($qrCode)->getString());
        $setting = model('SettingModel')->first();
        return view('admin/pembayaran/cetak', [
            'data' => $data,
            'qrCode' => $qrImage,
            'setting' => $setting,
        ]);
    }


    public function cetakBanyak()
    {
        $ids = $this->request->getPost('pembayaran_ids');
        if (!$ids)
            return redirect()->back()->with('error', 'Tidak ada pembayaran dipilih.');

        $idArray = explode(',', $ids);

        $pembayaranModel = new PembayaranModel();
        $tagihanModel = new TagihanSiswaModel();
        $siswaModel = new SiswaModel();
        $kelasModel = new KelasSiswaModel();

        $pembayaranList = $pembayaranModel
            ->whereIn('id', $idArray)
            ->orderBy('tanggal', 'asc')
            ->findAll();

        $transaksi = [];
        $siswaData = null;

        foreach ($pembayaranList as $pembayaran) {
            $tagihan = $tagihanModel
                ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
                ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
                ->where('tagihan_siswa.id', $pembayaran['tagihan_id'])
                ->first();

            $siswa = $siswaModel->find($tagihan['siswa_id']);
            $kelas = $kelasModel
                ->withRelations()
                ->where('siswa_id', $siswa['id'])
                ->where('tahun_ajaran_id', $tagihan['tahun_ajaran_id'])
                ->first();

            // Set hanya sekali untuk info siswa
            if (!$siswaData) {
                $siswaData = [
                    'nama' => $siswa['nama'] ?? '-',
                    'nis' => $siswa['nis'] ?? '-',
                    'kelas' => $kelas['nama_kelas'] ?? '-',
                ];
            }

            $transaksi[] = [
                'tanggal' => $pembayaran['tanggal'],
                'bulan' => $tagihan['bulan'] ?? null,
                'jumlah' => $pembayaran['jumlah_bayar'],
                'cara_bayar' => $pembayaran['cara_bayar'],
                'status' => 'Lunas',
                'nama_jenis_bayar' => $tagihan['nama_jenis_bayar'],
            ];
        }

        // Buat QR code gabungan
        $qrLines = [
            "Bukti Pembayaran SPP {$siswaData['nama']}",
            "NIS: {$siswaData['nis']}",
            "Kelas: {$siswaData['kelas']}",
        ];

        foreach ($transaksi as $t) {
            $qrLines[] = date('d-m-Y', strtotime($t['tanggal'])) . ' - ' . bulanIndo($t['bulan']) . ' - Rp ' . number_format($t['jumlah'], 0, ',', '.');
        }

        $qrCode = (new QrCode(implode("\n", $qrLines)))
            ->setSize(150)
            ->setMargin(10);
        $qrImage = base64_encode((new SvgWriter())->write($qrCode)->getString());
        $setting = model('SettingModel')->first();

        return view('admin/pembayaran/cetak_banyak', [
            'siswa' => $siswaData,
            'transaksi' => $transaksi,
            'qrCode' => $qrImage,
            'setting' => $setting,
            'nama_jenis_bayar' => $transaksi[0]['nama_jenis_bayar'] ?? 'Pembayaran',

        ]);
    }

    public function cetakBukti()
    {
        $siswaId = $this->request->getGet('siswa_id');
        $tanggal = $this->request->getGet('tanggal') ?? date('Y-m-d');

        if (!$siswaId) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        $pembayaranModel = new PembayaranModel();
        $tagihanModel = new TagihanSiswaModel();
        $siswaModel = new SiswaModel();
        $kelasModel = new KelasSiswaModel();

        $pembayaranList = $pembayaranModel
            ->where('tanggal', $tanggal)
            ->orderBy('id', 'asc')
            ->findAll();

        $bulanan = [];
        $bebas = [];
        $total = 0;
        $siswaData = null;

        foreach ($pembayaranList as $pembayaran) {
            $tagihan = $tagihanModel
                ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
                ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
                ->where('tagihan_siswa.id', $pembayaran['tagihan_id'])
                ->first();

            if (!$tagihan || $tagihan['siswa_id'] != $siswaId)
                continue;

            $siswa = $siswaModel->find($tagihan['siswa_id']);
            $kelas = $kelasModel
                ->withRelations()
                ->where('siswa_id', $siswa['id'])
                ->where('tahun_ajaran_id', $tagihan['tahun_ajaran_id'])
                ->first();

            if (!$siswaData) {
                $siswaData = [
                    'nama' => $siswa['nama'],
                    'nis' => $siswa['nis'],
                    'kelas' => $kelas['nama_kelas'] ?? '-',
                ];
            }

            $total += $pembayaran['jumlah_bayar'];

            if ($tagihan['tipe'] === 'bulanan') {
                $bulanan[] = [
                    'nama_jenis' => $tagihan['nama_jenis_bayar'],
                    'bulan' => $tagihan['bulan'],
                    'tanggal' => $pembayaran['tanggal'],
                    'jumlah' => $pembayaran['jumlah_bayar'],
                ];
            } else {
                $bebas[] = [
                    'nama_jenis' => $tagihan['nama_jenis_bayar'],
                    'tanggal' => $pembayaran['tanggal'],
                    'jumlah' => $pembayaran['jumlah_bayar'],
                    'angsuran_ke' => $pembayaran['angsuran_ke'] ?? '-',
                ];
            }
        }

        if (!$siswaData) {
            return redirect()->back()->with('error', 'Tidak ada transaksi pada tanggal tersebut.');
        }

        // 🧾 Buat QR code ringkasan transaksi
        $qrLines = [
            "Bukti Pembayaran {$siswaData['nama']}",
            "NIS: {$siswaData['nis']}",
            "Kelas: {$siswaData['kelas']}",
            "Tanggal: " . date('d-m-Y', strtotime($tanggal)),
        ];
        foreach ($bulanan as $b) {
            $qrLines[] = "- " . esc($b['nama_jenis']) . " - Bulan " . bulanIndo($b['bulan']) . ": Rp " . number_format($b['jumlah'], 0, ',', '.');
        }
        foreach ($bebas as $b) {
            $qrLines[] = "- " . esc($b['nama_jenis']) . ": Rp " . number_format($b['jumlah'], 0, ',', '.') . " (Angsuran ke-{$b['angsuran_ke']})";
        }
        $qrLines[] = "Total: Rp " . number_format($total, 0, ',', '.');

        $qrCode = new QrCode(implode("\n", $qrLines));
        $qrCode->setSize(150)->setMargin(10);
        $qrImage = base64_encode((new SvgWriter())->write($qrCode)->getString());
        $qrImage = 'data:image/svg+xml;base64,' . $qrImage;


        $setting = model('SettingModel')->first();

        return view('admin/pembayaran/bukti_cetak', [
            'siswa' => $siswaData,
            'tanggal' => $tanggal,
            'bulanan' => $bulanan,
            'bebas' => $bebas,
            'total' => $total,
            'qr' => $qrImage,
            'setting' => $setting,
        ]);
    }

    private function failNotFound($message)
    {
        return $this->response
            ->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
            ->setBody($message);
    }
}
