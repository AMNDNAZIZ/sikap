<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\SiswaModel;
use App\Models\KelasSiswaModel;
use App\Models\JenisBayarModel;
use App\Models\TagihanSiswaModel;
use App\Models\TahunAjaranModel;
use App\Models\KelasModel;

class Laporan extends BaseController
{
    protected $tahunAjaranModel;
    protected $kelasModel;
    protected $tagihanModel;
    protected $jenisBayarModel;

    public function __construct()
    {
        $this->tahunAjaranModel = new TahunAjaranModel();
        $this->kelasModel = new KelasModel();
        $this->tagihanModel = new TagihanSiswaModel();
        $this->jenisBayarModel = new JenisBayarModel();
    }

    public function harian()
    {
        $tanggal = $this->request->getGet('tanggal') ?? date('Y-m-d');

        $pembayaranModel = new \App\Models\PembayaranModel();

        $pembayaran = $pembayaranModel
            ->select('pembayaran.*, siswa.nama, siswa.nis, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
            ->join('tagihan_siswa', 'tagihan_siswa.id = pembayaran.tagihan_id')
            ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->where('pembayaran.tanggal', $tanggal)
            ->findAll();

        // Gabungkan pembayaran bulanan berdasarkan siswa + jenis_bayar
        $data = [];

        foreach ($pembayaran as $p) {
            $key = $p['nis'] . '|' . $p['nama_jenis_bayar'];

            if ($p['tipe'] === 'bulanan') {
                if (!isset($data[$key])) {
                    $data[$key] = [
                        'nis' => $p['nis'],
                        'nama' => $p['nama'],
                        'jenis' => $p['nama_jenis_bayar'],
                        'jumlah' => 0,
                        'tipe' => $p['tipe']
                    ];
                }

                $data[$key]['jumlah'] += $p['jumlah_bayar'];
            } else {
                // Bebas tampil per transaksi
                $data[uniqid()] = [
                    'nis' => $p['nis'],
                    'nama' => $p['nama'],
                    'jenis' => $p['nama_jenis_bayar'],
                    'jumlah' => $p['jumlah_bayar'],
                    'tipe' => $p['tipe'],
                    'keterangan' => $p['keterangan'] ?? '-'
                ];
            }
        }

        return view('admin/laporan/harian', [
            'tanggal' => $tanggal,
            'data' => $data
        ]);
    }

    public function periode()
    {
        $start = $this->request->getGet('start') ?? date('Y-m-01');
        $end = $this->request->getGet('end') ?? date('Y-m-d');
        $kelasId = $this->request->getGet('kelas_id');

        $kelasList = (new \App\Models\KelasSiswaModel())
            ->withRelations()
            ->select('kelas.id as kelas_id, kelas.nama_kelas')
            ->groupBy('kelas.id')
            ->orderBy('kelas.nama_kelas')
            ->findAll();

        $query = (new \App\Models\PembayaranModel())
            ->select('pembayaran.*, siswa.nama, siswa.nis, kelas.nama_kelas, kelas.id as kelas_id, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
            ->join('tagihan_siswa', 'tagihan_siswa.id = pembayaran.tagihan_id')
            ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = tagihan_siswa.tahun_ajaran_id', 'left')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('pembayaran.tanggal >=', $start)
            ->where('pembayaran.tanggal <=', $end);

        if ($kelasId) {
            $query->where('kelas.id', $kelasId);
        }

        $result = $query
            ->orderBy('kelas.nama_kelas')
            ->orderBy('siswa.nama')
            ->findAll();

        $data = [];

        foreach ($result as $row) {
            $kelas = $row['nama_kelas'];
            $siswa = "{$row['nis']} - {$row['nama']}";
            $jenis = $row['nama_jenis_bayar'];
            $tipe = $row['tipe'];

            if (!isset($data[$kelas][$siswa])) {
                $data[$kelas][$siswa] = [];
            }

            // Jika bulanan → akumulasi berdasarkan jenis
            if ($tipe === 'bulanan') {
                if (!isset($data[$kelas][$siswa][$jenis])) {
                    $data[$kelas][$siswa][$jenis] = [
                        'tipe' => $tipe,
                        'jumlah' => 0,
                        'keterangan' => '-'
                    ];
                }
                $data[$kelas][$siswa][$jenis]['jumlah'] += $row['jumlah_bayar'];
            } else {
                // Bebas → tampilkan tiap transaksi
                $data[$kelas][$siswa][] = [
                    'tipe' => $tipe,
                    'jenis' => $jenis,
                    'jumlah' => $row['jumlah_bayar'],
                    'keterangan' => $row['keterangan'] ?? '-'
                ];
            }
        }

        return view('admin/laporan/periode', [
            'kelasList' => $kelasList,
            'kelasId' => $kelasId,
            'start' => $start,
            'end' => $end,
            'data' => $data
        ]);
    }

    public function yayasan()
    {
        $tahunAjaranId = $this->request->getGet('tahun_ajaran_id');

        $tahunAjaranModel = new \App\Models\TahunAjaranModel();
        $semuaTahunAjaran = $tahunAjaranModel->orderBy('urutan', 'DESC')->findAll();

        // Ambil tahun ajaran terpilih atau default ke aktif
        if ($tahunAjaranId) {
            $tahunTerpilih = $tahunAjaranModel->find($tahunAjaranId);
        } else {
            $tahunTerpilih = $tahunAjaranModel->where('aktif', 1)->first();
        }

        // Ambil filter bulan (angka 1-12)
        $bulanFilter = $this->request->getGet('bulan');
        if (!$bulanFilter) {
            $bulanFilter = date('n');
        }
        $bulanFilterInt = (int) $bulanFilter;

        // Ambil tahun dari format "2023/2024"
        $parts = explode('/', $tahunTerpilih['nama']);
        if (count($parts) == 2) {
            $tahunAwal = (int) $parts[0];
            $tahunAkhir = (int) $parts[1];
        } else {
            $tahunAwal = (int) date('Y');
            $tahunAkhir = $tahunAwal + 1;
        }

        // Juli - Desember ikut tahun pertama, Januari - Juni ikut tahun kedua
        if ($bulanFilterInt >= 7 && $bulanFilterInt <= 12) {
            $tahunBulan = $tahunAwal;
        } else {
            $tahunBulan = $tahunAkhir;
        }

        $bulanStr = str_pad($bulanFilterInt, 2, '0', STR_PAD_LEFT);
        $start = date('Y-m-01', strtotime("$tahunBulan-$bulanStr-01"));
        $end = date('Y-m-t', strtotime("$tahunBulan-$bulanStr-01"));

        $setting = (new \App\Models\SettingModel())->first();
        $persentase = $setting['persentase_yayasan'] ?? 0;

        $query = (new \App\Models\PembayaranModel())
            ->select('pembayaran.*, siswa.nama, siswa.nis, kelas.nama_kelas, kelas.id as kelas_id, jenis_bayar.nama_jenis_bayar, tagihan_siswa.bulan as tagihan_bulan')
            ->join('tagihan_siswa', 'tagihan_siswa.id = pembayaran.tagihan_id')
            ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = tagihan_siswa.tahun_ajaran_id', 'left')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left')
            ->where('jenis_bayar.tipe', 'bulanan')
            ->where('pembayaran.tanggal >=', $start)
            ->where('pembayaran.tanggal <=', $end);

        if ($tahunTerpilih) {
            $query->where('tagihan_siswa.tahun_ajaran_id', $tahunTerpilih['id']);
        }

        $result = $query->orderBy('pembayaran.tanggal', 'DESC')->findAll();

        $totalPenerimaan = 0;
        foreach ($result as $row) {
            $totalPenerimaan += $row['jumlah_bayar'];
        }

        $totalYayasan = ($totalPenerimaan * $persentase) / 100;

        return view('admin/laporan/yayasan', [
            'bulan' => $bulanFilterInt,
            'tahunBulan' => $tahunBulan,
            'data' => $result,
            'totalPenerimaan' => $totalPenerimaan,
            'persentase' => $persentase,
            'totalYayasan' => $totalYayasan,
            'tahunTerpilih' => $tahunTerpilih,
            'semuaTahunAjaran' => $semuaTahunAjaran,
            'tahunAjaranId' => $tahunAjaranId ?: ($tahunTerpilih['id'] ?? '')
        ]);
    }

    public function tagihan()
    {
        $kelasId = $this->request->getGet('kelas_id');

        // Ambil tahun ajaran aktif
        $tahunAktif = (new \App\Models\TahunAjaranModel())->where('aktif', 1)->first();

        // Ambil daftar kelas aktif dari tahun ajaran ini
        $kelasList = (new \App\Models\KelasSiswaModel())
            ->select('kelas.id as kelas_id, kelas.nama_kelas')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id')
            ->where('kelas_siswa.tahun_ajaran_id', $tahunAktif['id'])
            ->groupBy('kelas.id')
            ->orderBy('kelas.nama_kelas')
            ->findAll();


        $data = [];

        if ($kelasId) {
            $siswaList = (new \App\Models\SiswaModel())
                ->select('siswa.*, kelas_siswa.kelas_id, kelas_siswa.status as status_kelas_siswa')
                ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id')
                ->where('kelas_siswa.kelas_id', $kelasId)
                ->where('kelas_siswa.tahun_ajaran_id', $tahunAktif['id'])
                ->findAll();

            $tagihanModel = new \App\Models\TagihanSiswaModel();

            foreach ($siswaList as $siswa) {
                $tagihan = $tagihanModel
                    ->where('siswa_id', $siswa['id'])
                    ->findAll();

                $total = array_sum(array_column($tagihan, 'jumlah'));
                $dibayar = array_sum(array_column($tagihan, 'terbayar'));
                $sisa = $total - $dibayar;

                $data[] = [
                    'siswa' => [
                        'siswa_id' => $siswa['id'],
                        'nama' => $siswa['nama'],
                        'nis' => $siswa['nis'],
                        'status' => $siswa['status_kelas_siswa'],
                    ],
                    'total' => $total,
                    'dibayar' => $dibayar,
                    'sisa' => $sisa,
                    'status' => $dibayar == 0 ? 'Belum Bayar' : ($sisa > 0 ? 'Kurang Bayar' : 'Lunas')
                ];
            }
        }

        return view('admin/laporan/tagihan', [
            'kelasList' => $kelasList,
            'kelasId' => $kelasId,
            'data' => $data,
            'tahun' => $tahunAktif['nama'] ?? '-'
        ]);
    }

    public function cetakTagihan($siswaId)
    {
        $siswa = (new SiswaModel())->find($siswaId);

        $tagihan = (new TagihanSiswaModel())
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->where('siswa_id', $siswaId)
            ->findAll();

        return view('admin/laporan/print_tagihan', compact('siswa', 'tagihan'));
    }

    public function tagihanDetail($siswaId)
    {
        $siswa = (new SiswaModel())->find($siswaId);
        if (!$siswa) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan');
        }

        $tagihanModel = new TagihanSiswaModel();
        $tahunModel = new TahunAjaranModel();
        $kelasModel = new KelasSiswaModel();

        // Ambil kelas aktif/terbaru siswa
        $kelasAktif = $kelasModel
            ->withRelations()
            ->where('siswa_id', $siswaId)
            ->orderBy('tahun_ajaran_id', 'desc')
            ->first();

        // Ambil tahun ajaran aktif
        $tahunAktif = $tahunModel->where('aktif', 1)->first();

        // Ambil semua tagihan siswa
        $tagihanList = $tagihanModel
            ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe, jenis_bayar.tahun_ajaran_id')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->where('tagihan_siswa.siswa_id', $siswaId)
            ->orderBy('jenis_bayar.tahun_ajaran_id')
            ->orderBy('jenis_bayar.nama_jenis_bayar')
            ->findAll();

        // Tentukan batas bulan untuk tahun ajaran aktif
        $bulanAkademik = [7 => 1, 8 => 2, 9 => 3, 10 => 4, 11 => 5, 12 => 6, 1 => 7, 2 => 8, 3 => 9, 4 => 10, 5 => 11, 6 => 12];
        $bulanSekarang = (int) date('n');
        $bulanAkhirAktif = $bulanAkademik[$bulanSekarang] ?? 12;

        // Rekap berdasarkan jenis bayar
        $rekap = [];

        foreach ($tagihanList as $row) {
            $tipe = $row['tipe'];
            $tahunId = $row['tahun_ajaran_id'];
            $key = $row['nama_jenis_bayar'];

            if (!isset($rekap[$key])) {
                $rekap[$key] = [
                    'nama_jenis_bayar' => $row['nama_jenis_bayar'],
                    'jumlah' => 0,
                    'terbayar' => 0,
                    'tipe' => $tipe,
                    'tahun_id' => $tahunId
                ];
            }

            // Bulanan tahun ajaran aktif → batasi sampai bulan sekarang
            if ($tipe === 'bulanan' && $tahunAktif && $tahunId == $tahunAktif['id']) {
                if ((int) $row['bulan'] <= $bulanAkhirAktif) {
                    $rekap[$key]['jumlah'] += $row['jumlah'];
                    $rekap[$key]['terbayar'] += $row['terbayar'];
                }
            } else {
                $rekap[$key]['jumlah'] += $row['jumlah'];
                $rekap[$key]['terbayar'] += $row['terbayar'];
            }
        }

        // Susun tunggakan: urutkan bulanan dulu, lalu bebas
        $tunggakan = [];
        $totalTunggakan = 0;
        $bulanan = [];
        $bebas = [];

        foreach ($rekap as $r) {
            $sisa = $r['jumlah'] - $r['terbayar'];
            if ($sisa > 0) {
                $item = [
                    'jenis' => $r['nama_jenis_bayar'],
                    'tagihan' => $r['jumlah'],
                    'dibayar' => $r['terbayar'],
                    'sisa' => $sisa,
                ];
                if ($r['tipe'] === 'bulanan') {
                    $bulanan[] = $item;
                } else {
                    $bebas[] = $item;
                }

                $totalTunggakan += $sisa;
            }
        }

        $tunggakan = array_merge($bulanan, $bebas);

        // Info bulan jika ada pemotongan berdasarkan bulan berjalan
        $infoBulan = null;
        if ($tahunAktif) {
            $namaBulan = bulanIndo($bulanAkhirAktif);
            $tahunini = date("Y");
            $infoBulan = "Dihitung sampai dengan bulan <strong>{$namaBulan} {$tahunini}</strong>";
        }

        return view('admin/laporan/tagihan_detail', [
            'siswa' => $siswa,
            'kelas' => $kelasAktif['nama_kelas'] ?? '-',
            'tunggakan' => $tunggakan,
            'totalTunggakan' => $totalTunggakan,
            'infoBulan' => $infoBulan,
        ]);
    }

    public function tagihanDetailKelas($kelasId)
    {
        $kelasModel = new KelasModel();
        $kelas = $kelasModel->find($kelasId);
        if (!$kelas) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan');
        }

        $tahunModel = new TahunAjaranModel();
        $tahunAktif = $tahunModel->where('aktif', 1)->first();
        if (!$tahunAktif) {
            return redirect()->back()->with('error', 'Tahun ajaran aktif tidak ditemukan');
        }

        $siswaList = (new SiswaModel())
            ->select('siswa.*')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id')
            ->where('kelas_siswa.kelas_id', $kelasId)
            ->where('kelas_siswa.tahun_ajaran_id', $tahunAktif['id'])
            ->orderBy('siswa.nama')
            ->findAll();

        $tagihanModel = new TagihanSiswaModel();
        $bulanAkademik = [7 => 1, 8 => 2, 9 => 3, 10 => 4, 11 => 5, 12 => 6, 1 => 7, 2 => 8, 3 => 9, 4 => 10, 5 => 11, 6 => 12];
        $bulanSekarang = (int) date('n');
        $bulanAkhirAktif = $bulanAkademik[$bulanSekarang] ?? 12;

        $reportData = [];

        foreach ($siswaList as $siswa) {
            $tagihanList = $tagihanModel
                ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe, jenis_bayar.tahun_ajaran_id')
                ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
                ->where('tagihan_siswa.siswa_id', $siswa['id'])
                ->orderBy('jenis_bayar.tahun_ajaran_id')
                ->orderBy('jenis_bayar.nama_jenis_bayar')
                ->findAll();

            $rekap = [];
            foreach ($tagihanList as $row) {
                $tipe = $row['tipe'];
                $tahunId = $row['tahun_ajaran_id'];
                $key = $row['nama_jenis_bayar'];

                if (!isset($rekap[$key])) {
                    $rekap[$key] = [
                        'nama_jenis_bayar' => $row['nama_jenis_bayar'],
                        'jumlah' => 0,
                        'terbayar' => 0,
                        'tipe' => $tipe,
                        'tahun_id' => $tahunId
                    ];
                }

                if ($tipe === 'bulanan' && $tahunId == $tahunAktif['id']) {
                    if ((int) $row['bulan'] <= $bulanAkhirAktif) {
                        $rekap[$key]['jumlah'] += $row['jumlah'];
                        $rekap[$key]['terbayar'] += $row['terbayar'];
                    }
                } else {
                    $rekap[$key]['jumlah'] += $row['jumlah'];
                    $rekap[$key]['terbayar'] += $row['terbayar'];
                }
            }

            $tunggakan = [];
            $totalTunggakan = 0;
            $bulanan = [];
            $bebas = [];

            foreach ($rekap as $r) {
                $sisa = $r['jumlah'] - $r['terbayar'];
                if ($sisa > 0) {
                    $item = [
                        'jenis' => $r['nama_jenis_bayar'],
                        'tagihan' => $r['jumlah'],
                        'dibayar' => $r['terbayar'],
                        'sisa' => $sisa,
                    ];
                    if ($r['tipe'] === 'bulanan') {
                        $bulanan[] = $item;
                    } else {
                        $bebas[] = $item;
                    }
                    $totalTunggakan += $sisa;
                }
            }

            if ($totalTunggakan > 0) {
                $reportData[] = [
                    'siswa' => $siswa,
                    'tunggakan' => array_merge($bulanan, $bebas),
                    'totalTunggakan' => $totalTunggakan
                ];
            }
        }

        $infoBulan = null;
        $namaBulan = bulanIndo($bulanAkhirAktif);
        $tahunini = date("Y");
        $infoBulan = "Dihitung sampai dengan bulan <strong>{$namaBulan} {$tahunini}</strong>";

        return view('admin/laporan/tagihan_detail_kelas', [
            'kelas' => $kelas['nama_kelas'],
            'reportData' => $reportData,
            'infoBulan' => $infoBulan,
        ]);
    }

    public function rekapTunggakan()
    {
        $kelasId = $this->request->getGet('kelas_id');
        $sampaiBulan = $this->request->getGet('sampai_bulan');

        // Batas bulan default untuk tahun akademik saat ini
        $bulanAkademik = [7 => 1, 8 => 2, 9 => 3, 10 => 4, 11 => 5, 12 => 6, 1 => 7, 2 => 8, 3 => 9, 4 => 10, 5 => 11, 6 => 12];
        if (!$sampaiBulan) {
            $sampaiBulan = $bulanAkademik[(int) date('n')] ?? 12;
        }

        $kelas = $this->kelasModel->orderBy('nama_kelas')->findAll();

        $data = [];
        if ($kelasId) {
            $data = $this->_getRekapData($kelasId, $sampaiBulan);
        }

        return view('admin/laporan/rekap_tunggakan', [
            'kelas' => $kelas,
            'data' => $data,
            'filter' => [
                'kelas_id' => $kelasId,
                'sampai_bulan' => $sampaiBulan
            ]
        ]);
    }

    public function rekapTunggakanPrint()
    {
        $kelasId = $this->request->getGet('kelas_id');
        $sampaiBulan = $this->request->getGet('sampai_bulan');

        if (!$kelasId)
            return redirect()->back();

        $bulanAkademik = [7 => 1, 8 => 2, 9 => 3, 10 => 4, 11 => 5, 12 => 6, 1 => 7, 2 => 8, 3 => 9, 4 => 10, 5 => 11, 6 => 12];
        if (!$sampaiBulan) {
            $sampaiBulan = $bulanAkademik[(int) date('n')] ?? 12;
        }

        // Cari nama bulan berdasarkan index 1-12
        $namaBulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
        $labelBulan = $namaBulan[(int) $sampaiBulan - 1] ?? '';

        $kelas = $this->kelasModel->find($kelasId);
        $data = $this->_getRekapData($kelasId, $sampaiBulan);

        return view('admin/laporan/rekap_tunggakan_print', [
            'kelas' => $kelas['nama_kelas'],
            'data' => $data,
            'labelBulan' => $labelBulan
        ]);
    }

    public function rekapTunggakanSiswaPrint()
    {
        $siswaId = $this->request->getGet('id');
        $sampaiBulan = $this->request->getGet('sampai_bulan');

        if (!$siswaId)
            return redirect()->back();

        $tahunAktif = $this->tahunAjaranModel->where('aktif', 1)->first();
        if (!$tahunAktif)
            return redirect()->back();

        $kelasSiswaModel = new \App\Models\KelasSiswaModel();
        $kelasSiswa = $kelasSiswaModel
            ->where('siswa_id', $siswaId)
            ->where('tahun_ajaran_id', $tahunAktif['id'])
            ->first();

        if (!$kelasSiswa)
            return redirect()->back()->with('error', 'Siswa tidak aktif di kelas manapun.');

        $kelasId = $kelasSiswa['kelas_id'];

        $bulanAkademik = [7 => 1, 8 => 2, 9 => 3, 10 => 4, 11 => 5, 12 => 6, 1 => 7, 2 => 8, 3 => 9, 4 => 10, 5 => 11, 6 => 12];
        if (!$sampaiBulan) {
            $sampaiBulan = $bulanAkademik[(int) date('n')] ?? 12;
        }

        $namaBulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
        $labelBulan = $namaBulan[(int) $sampaiBulan - 1] ?? '';

        $kelas = $this->kelasModel->find($kelasId);
        $data = $this->_getRekapData($kelasId, $sampaiBulan, $siswaId);

        return view('admin/laporan/rekap_tunggakan_siswa_print', [
            'kelas' => $kelas['nama_kelas'],
            'data' => $data[0] ?? null,
            'labelBulan' => $labelBulan
        ]);
    }

    private function _getRekapData($kelasId, $limitBulanAktif = 12, $siswaId = null)
    {
        // Ambil tahun ajaran aktif untuk menentukan siswa yang sedang aktif di kelas tersebut
        $tahunAktif = $this->tahunAjaranModel->where('aktif', 1)->first();
        if (!$tahunAktif)
            return [];

        $siswaQuery = (new SiswaModel())
            ->select('siswa.*')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id')
            ->where('kelas_siswa.kelas_id', $kelasId)
            ->where('kelas_siswa.tahun_ajaran_id', $tahunAktif['id']);

        if ($siswaId) {
            $siswaQuery->where('siswa.id', $siswaId);
        }

        $siswaList = $siswaQuery->orderBy('siswa.nama')
            ->findAll();

        $tagihanModel = new TagihanSiswaModel();

        // Batas bulan untuk tahun akademik saat ini
        $bulanAkademik = [7 => 1, 8 => 2, 9 => 3, 10 => 4, 11 => 5, 12 => 6, 1 => 7, 2 => 8, 3 => 9, 4 => 10, 5 => 11, 6 => 12];

        $report = [];
        foreach ($siswaList as $siswa) {
            // Ambil SEMUA tagihan siswa (sepanjang masa)
            $tagihan = $tagihanModel
                ->select('tagihan_siswa.*, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe, tahun_ajaran.urutan as ta_urutan')
                ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
                ->join('tahun_ajaran', 'tahun_ajaran.id = tagihan_siswa.tahun_ajaran_id')
                ->where('tagihan_siswa.siswa_id', $siswa['id'])
                ->findAll();

            $spp_total = 0;
            $spp_bulan = 0;
            $ppdb = 0;
            $kegiatan = 0;
            $akhir = 0;

            foreach ($tagihan as $t) {
                $sisa = $t['jumlah'] - $t['terbayar'];
                if ($sisa <= 0)
                    continue;

                $nama = strtoupper($t['nama_jenis_bayar']);

                if ($t['tipe'] === 'bulanan') {
                    if (strpos($nama, 'SPP') !== false) {
                        // Jika tahun ajaran lampau (urutan < urutan aktif) → hitung semua
                        // Jika tahun ajaran aktif → hitung sampai bulan sekarang
                        if ($t['ta_urutan'] < $tahunAktif['urutan']) {
                            $spp_total += $sisa;
                            $spp_bulan++;
                        } elseif ($t['ta_urutan'] == $tahunAktif['urutan']) {
                            if ((int) $t['bulan'] <= $limitBulanAktif) {
                                $spp_total += $sisa;
                                $spp_bulan++;
                            }
                        }
                    }
                } else {
                    if (strpos($nama, 'PPDB') !== false) {
                        $ppdb += $sisa;
                    } elseif (strpos($nama, 'KEGIATAN') !== false) {
                        $kegiatan += $sisa;
                    } elseif (strpos($nama, 'AKHIR') !== false || strpos($nama, 'KELULUSAN') !== false) {
                        $akhir += $sisa;
                    } else {
                        // Uncategorized
                        $kegiatan += $sisa;
                    }
                }
            }

            $total = $spp_total + $ppdb + $kegiatan + $akhir;
            if ($total > 0) {
                $report[] = [
                    'id' => $siswa['id'],
                    'nis' => $siswa['nis'],
                    'nama' => $siswa['nama'],
                    'spp' => $spp_total,
                    'bln' => $spp_bulan,
                    'ppdb' => $ppdb,
                    'kegiatan' => $kegiatan,
                    'akhir' => $akhir,
                    'total' => $total
                ];
            }
        }
        return $report;
    }

    public function harianPrint()
    {
        $tanggal = $this->request->getGet('date') ?? date('Y-m-d');
        $data = $this->getHarianData($tanggal);
        return view('admin/laporan/harian_print', $data);
    }

    public function harianExport()
    {
        $tanggal = $this->request->getGet('date') ?? date('Y-m-d');
        $data = $this->getHarianData($tanggal);

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=laporan_harian_{$tanggal}.xls");
        echo view('admin/laporan/harian_export', $data);
    }

    public function periodePrint()
    {
        $start = $this->request->getGet('start') ?? date('Y-m-01');
        $end = $this->request->getGet('end') ?? date('Y-m-d');
        $kelasId = $this->request->getGet('kelas_id');

        $data = $this->getPeriodeData($start, $end, $kelasId);
        return view('admin/laporan/periode_print', $data);
    }

    public function periodeExport()
    {
        $start = $this->request->getGet('start') ?? date('Y-m-01');
        $end = $this->request->getGet('end') ?? date('Y-m-d');
        $kelasId = $this->request->getGet('kelas_id');

        $data = $this->getPeriodeData($start, $end, $kelasId);

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=laporan_periode_{$start}_{$end}.xls");

        echo view('admin/laporan/periode_export', $data);
        exit;
    }

    private function getPeriodeData($start, $end, $kelasId)
    {
        $kelasModel = new \App\Models\KelasSiswaModel();
        $kelasList = $kelasModel
            ->withRelations()
            ->groupBy('kelas.id')
            ->orderBy('kelas.nama_kelas')
            ->findAll();

        $pembayaran = (new \App\Models\PembayaranModel())
            ->select('pembayaran.*, siswa.nama, siswa.nis, kelas.nama_kelas, jenis_bayar.nama_jenis_bayar')
            ->join('tagihan_siswa', 'tagihan_siswa.id = pembayaran.tagihan_id')
            ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id')
            ->where('tanggal >=', $start)
            ->where('tanggal <=', $end);

        if ($kelasId) {
            $pembayaran->where('kelas.id', $kelasId);
        }

        $result = $pembayaran
            ->orderBy('kelas.nama_kelas')
            ->orderBy('siswa.nama')
            ->findAll();

        $data = [];
        foreach ($result as $row) {
            $kelas = $row['nama_kelas'];
            $siswa = $row['nama'];

            $key = $kelas . '|' . $siswa . '|' . $row['nama_jenis_bayar'];

            if (!isset($data[$kelas][$siswa][$row['nama_jenis_bayar']])) {
                $data[$kelas][$siswa][$row['nama_jenis_bayar']] = 0;
            }

            $data[$kelas][$siswa][$row['nama_jenis_bayar']] += $row['jumlah_bayar'];
        }

        return [
            'start' => $start,
            'end' => $end,
            'kelasId' => $kelasId,
            'kelasList' => $kelasList,
            'data' => $data
        ];
    }


    public function tagihanPrint()
    {
        $kelasId = $this->request->getGet('kelas_id');
        $tahunAktif = (new TahunAjaranModel())->where('aktif', 1)->first();

        if (!$kelasId || !$tahunAktif)
            return redirect()->back();

        $kelas = (new KelasModel())->find($kelasId);
        $siswaList = (new SiswaModel())
            ->select('siswa.*, kelas_siswa.kelas_id')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id')
            ->where('kelas_siswa.kelas_id', $kelasId)
            ->where('kelas_siswa.tahun_ajaran_id', $tahunAktif['id'])
            ->findAll();

        $tagihanModel = new TagihanSiswaModel();
        $data = [];

        foreach ($siswaList as $siswa) {
            $tagihan = $tagihanModel
                ->where('siswa_id', $siswa['id'])
                ->findAll();

            $total = array_sum(array_column($tagihan, 'jumlah'));
            $dibayar = array_sum(array_column($tagihan, 'terbayar'));
            $sisa = $total - $dibayar;

            $data[] = [
                'siswa' => [
                    'siswa_id' => $siswa['id'],
                    'nama' => $siswa['nama'],
                    'nis' => $siswa['nis'],
                ],
                'total' => $total,
                'dibayar' => $dibayar,
                'sisa' => $sisa,
                'status' => $dibayar == 0 ? 'Belum Bayar' : ($sisa > 0 ? 'Kurang Bayar' : 'Lunas')
            ];
        }

        return view('admin/laporan/tagihan_print', [
            'kelas' => $kelas,
            'data' => $data
        ]);
    }

    public function tagihanExport()
    {
        $kelasId = $this->request->getGet('kelas_id');
        $tahunAktif = (new TahunAjaranModel())->where('aktif', 1)->first();

        if (!$kelasId || !$tahunAktif)
            return redirect()->back();

        $kelas = (new KelasModel())->find($kelasId);
        $siswaList = (new SiswaModel())
            ->select('siswa.*, kelas_siswa.kelas_id')
            ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id')
            ->where('kelas_siswa.kelas_id', $kelasId)
            ->where('kelas_siswa.tahun_ajaran_id', $tahunAktif['id'])
            ->findAll();

        $tagihanModel = new TagihanSiswaModel();
        $data = [];

        foreach ($siswaList as $siswa) {
            $tagihan = $tagihanModel
                ->where('siswa_id', $siswa['id'])
                ->findAll();

            $total = array_sum(array_column($tagihan, 'jumlah'));
            $dibayar = array_sum(array_column($tagihan, 'terbayar'));
            $sisa = $total - $dibayar;

            $data[] = [
                'siswa' => [
                    'siswa_id' => $siswa['id'],
                    'nama' => $siswa['nama'],
                    'nis' => $siswa['nis'],
                ],
                'total' => $total,
                'dibayar' => $dibayar,
                'sisa' => $sisa,
                'status' => $dibayar == 0 ? 'Belum Bayar' : ($sisa > 0 ? 'Kurang Bayar' : 'Lunas')
            ];
        }

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=laporan_tagihan_kelas_{$kelas['nama_kelas']}.xls");

        echo view('admin/laporan/tagihan_export', ['data' => $data, 'kelas' => $kelas]);
        exit;
    }


    private function getHarianData($tanggal)
    {
        $pembayaranModel = new \App\Models\PembayaranModel();

        $data = $pembayaranModel
            ->select('pembayaran.*, siswa.nama, siswa.nis, jenis_bayar.nama_jenis_bayar, jenis_bayar.tipe')
            ->join('tagihan_siswa', 'tagihan_siswa.id = pembayaran.tagihan_id')
            ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
            ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
            ->where('tanggal', $tanggal)
            ->orderBy('siswa.nama')
            ->findAll();

        $rekap = [];

        foreach ($data as $row) {
            $key = $row['nama'] . '-' . $row['nama_jenis_bayar'];

            if (!isset($rekap[$key])) {
                $rekap[$key] = [
                    'nama' => $row['nama'],
                    'nis' => $row['nis'],
                    'jenis' => $row['nama_jenis_bayar'],
                    'tipe' => $row['tipe'],
                    'jumlah' => 0
                ];
            }

            $rekap[$key]['jumlah'] += $row['jumlah_bayar'];
        }

        // Total keseluruhan
        $total = array_sum(array_column($rekap, 'jumlah'));

        return [
            'tanggal' => $tanggal,
            'list' => array_values($rekap),
            'total' => $total
        ];
    }


    public function filterTagihan()
    {
        // Ambil filter dari query string
        $tahunAjaranId = $this->request->getGet('tahun_ajaran_id');
        $kelasId = $this->request->getGet('kelas_id');
        $tipe = $this->request->getGet('tipe'); // bulanan/bebas
        $jenisBayarId = $this->request->getGet('jenis_bayar_id'); // optional

        // Data dropdown
        $tahunAjaran = $this->tahunAjaranModel->orderBy('urutan', 'DESC')->findAll();
        $kelas = $this->kelasModel->orderBy('nama_kelas')->findAll();
        $jenisBayar = $this->jenisBayarModel->orderBy('nama_jenis_bayar')->findAll();

        // Builder utama
        if ($tipe === 'bulanan') {
            // 🔑 Bulanan: GROUP BY per siswa, jenis bayar
            $builder = $this->tagihanModel
                ->select('
                    siswa.nis,
                    siswa.nama as siswa_nama,
                    kelas.nama_kelas as kelas_nama,
                    tahun_ajaran.nama as tahun_ajaran_nama,
                    jenis_bayar.nama_jenis_bayar as jenis_bayar_nama,
                    SUM(tagihan_siswa.jumlah) as total_jumlah,
                    SUM(tagihan_siswa.terbayar) as total_terbayar,
                    (SUM(tagihan_siswa.jumlah) - SUM(tagihan_siswa.terbayar)) as sisa,
                    tagihan_siswa.tipe
                ')
                ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
                ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = tagihan_siswa.tahun_ajaran_id')
                ->join('kelas', 'kelas.id = kelas_siswa.kelas_id')
                ->join('tahun_ajaran', 'tahun_ajaran.id = tagihan_siswa.tahun_ajaran_id')
                ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
                ->where('tagihan_siswa.tipe', 'bulanan')
                ->groupBy('tagihan_siswa.siswa_id, kelas.id, tagihan_siswa.tahun_ajaran_id, jenis_bayar.id')
                ->orderBy('siswa.nama');
        } else {
            // 🔑 Bebas: detail per baris
            $builder = $this->tagihanModel
                ->select('
                    tagihan_siswa.*,
                    siswa.nis,
                    siswa.nama as siswa_nama,
                    kelas.nama_kelas as kelas_nama,
                    tahun_ajaran.nama as tahun_ajaran_nama,
                    jenis_bayar.nama_jenis_bayar as jenis_bayar_nama,
                    (tagihan_siswa.jumlah - tagihan_siswa.terbayar) as sisa
                ')
                ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
                ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = tagihan_siswa.tahun_ajaran_id')
                ->join('kelas', 'kelas.id = kelas_siswa.kelas_id')
                ->join('tahun_ajaran', 'tahun_ajaran.id = tagihan_siswa.tahun_ajaran_id')
                ->join('jenis_bayar', 'jenis_bayar.id = tagihan_siswa.jenis_bayar_id')
                ->where('tagihan_siswa.tipe', 'bebas')
                ->orderBy('siswa.nama');
        }

        // 🔑 Filter dinamis
        if ($tahunAjaranId) {
            $builder->where('tagihan_siswa.tahun_ajaran_id', $tahunAjaranId);
        }
        if ($kelasId) {
            $builder->where('kelas_siswa.kelas_id', $kelasId);
        }
        if ($jenisBayarId) {
            $builder->where('jenis_bayar.id', $jenisBayarId);
        }

        // Ambil data dengan paginate
        $tagihan = $builder->paginate(20, 'default');

        // Kirim ke view
        $data = [
            'title' => 'Filter Tagihan Siswa',
            'tahunAjaran' => $tahunAjaran,
            'kelas' => $kelas,
            'jenisBayar' => $jenisBayar,
            'tagihan' => $tagihan,
            'pager' => $this->tagihanModel->pager,
            'filter' => [
                'tahun_ajaran_id' => $tahunAjaranId,
                'kelas_id' => $kelasId,
                'tipe' => $tipe,
                'jenis_bayar_id' => $jenisBayarId
            ]
        ];

        return view('admin/laporan/filter_tagihan', $data);
    }


    public function printFilterTagihan()
    {
        $tahunAjaranId = $this->request->getGet('tahun_ajaran_id');
        $kelasId = $this->request->getGet('kelas_id');
        $tipe = $this->request->getGet('tipe');

        $data = $this->_getTagihanData($tahunAjaranId, $kelasId, $tipe, false);

        return view('admin/laporan/print_tagihan', $data);
    }

    public function excelTagihan()
    {
        $tahunAjaranId = $this->request->getGet('tahun_ajaran_id');
        $kelasId = $this->request->getGet('kelas_id');
        $tipe = $this->request->getGet('tipe');

        $data = $this->_getTagihanData($tahunAjaranId, $kelasId, $tipe, false);

        // Buat spreadsheet pakai PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->fromArray([
            ['NIS', 'Nama', 'Kelas', 'Tahun Ajaran', 'Jenis', 'Jumlah Tagihan', 'Sudah Dibayar', 'Belum Dibayar', 'Status']
        ]);

        $rows = 2;
        foreach ($data['tagihan'] as $row) {
            $jumlah = $row['total_jumlah'] ?? $row['jumlah'];
            $terbayar = $row['total_terbayar'] ?? $row['terbayar'];
            $sisa = $row['sisa'];
            $status = ($sisa <= 0) ? 'Lunas' : 'Belum';

            $sheet->fromArray([
                [
                    $row['nis'],
                    $row['siswa_nama'],
                    $row['kelas_nama'],
                    $row['tahun_ajaran_nama'],
                    ucfirst($row['tipe']),
                    $jumlah,
                    $terbayar,
                    $sisa,
                    $status
                ]
            ], null, "A{$rows}");
            $rows++;
        }

        // Output
        $filename = 'Tagihan_Siswa.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    private function _getTagihanData($tahunAjaranId, $kelasId, $tipe, $usePagination = true)
    {
        if ($tipe === 'bulanan') {
            $builder = $this->tagihanModel
                ->select('
                    siswa.nis,
                    siswa.nama as siswa_nama,
                    kelas.nama_kelas as kelas_nama,
                    tahun_ajaran.nama as tahun_ajaran_nama,
                    SUM(tagihan_siswa.jumlah) as total_jumlah,
                    SUM(tagihan_siswa.terbayar) as total_terbayar,
                    (SUM(tagihan_siswa.jumlah) - SUM(tagihan_siswa.terbayar)) as sisa,
                    tagihan_siswa.tipe
                ')
                ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
                ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = tagihan_siswa.tahun_ajaran_id')
                ->join('kelas', 'kelas.id = kelas_siswa.kelas_id')
                ->join('tahun_ajaran', 'tahun_ajaran.id = tagihan_siswa.tahun_ajaran_id')
                ->where('tagihan_siswa.tipe', 'bulanan')
                ->groupBy('tagihan_siswa.siswa_id, kelas.id, tagihan_siswa.tahun_ajaran_id')
                ->orderBy('siswa.nama');
        } else {
            $builder = $this->tagihanModel
                ->select('
                    tagihan_siswa.*,
                    siswa.nis,
                    siswa.nama as siswa_nama,
                    kelas.nama_kelas as kelas_nama,
                    tahun_ajaran.nama as tahun_ajaran_nama,
                    (tagihan_siswa.jumlah - tagihan_siswa.terbayar) as sisa
                ')
                ->join('siswa', 'siswa.id = tagihan_siswa.siswa_id')
                ->join('kelas_siswa', 'kelas_siswa.siswa_id = siswa.id AND kelas_siswa.tahun_ajaran_id = tagihan_siswa.tahun_ajaran_id')
                ->join('kelas', 'kelas.id = kelas_siswa.kelas_id')
                ->join('tahun_ajaran', 'tahun_ajaran.id = tagihan_siswa.tahun_ajaran_id')
                ->where('tagihan_siswa.tipe', 'bebas')
                ->orderBy('siswa.nama');
        }

        if ($tahunAjaranId) {
            $builder->where('tagihan_siswa.tahun_ajaran_id', $tahunAjaranId);
        }
        if ($kelasId) {
            $builder->where('kelas_siswa.kelas_id', $kelasId);
        }

        return [
            'tagihan' => $usePagination ? $builder->paginate(20, 'default') : $builder->findAll(),
            'filter' => [
                'tahun_ajaran_id' => $tahunAjaranId,
                'kelas_id' => $kelasId,
                'tipe' => $tipe
            ],
            'tahunAjaran' => $this->tahunAjaranModel->orderBy('urutan', 'DESC')->findAll(),
            'kelas' => $this->kelasModel->orderBy('nama_kelas')->findAll(),
            'pager' => $usePagination ? $this->tagihanModel->pager : null,
            'title' => 'Filter Tagihan Siswa'
        ];
    }




}
