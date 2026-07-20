<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Libraries\PdfGenerator;

class TataUsaha extends BaseController
{
    protected $classModel;
    protected $studentModel;
    protected $schoolModel;

    public function __construct()
    {
        $this->classModel   = new ClassModel();
        $this->studentModel = new StudentModel();
        $this->schoolModel  = new SchoolModel();
    }

    public function cetakDaftarHadir()
    {
        $kelasId = $this->request->getGet('kelas_id');
        $classes = $this->classModel->orderBy('name', 'ASC')->findAll();

        return view('admin/tata_usaha/cetak_daftar_hadir', [
            'title'   => 'Cetak Daftar Hadir',
            'kelasId' => $kelasId,
            'classes' => $classes,
        ]);
    }

    public function generatePDF()
    {
        $kelasId      = $this->request->getPost('kelas_id');
        $kolom        = $this->request->getPost('kolom') ?? [];
        $kertas       = $this->request->getPost('kertas') ?? 'A4';
        $useKop       = (bool) $this->request->getPost('kop_surat');
        $namaKegiatan = trim($this->request->getPost('nama_kegiatan') ?? '');
        $tampilTtd    = $this->request->getPost('tampil_ttd') == '1';
        $tanggalRaw   = $this->request->getPost('tanggal');
        // Format tanggal Bahasa Indonesia untuk PDF (misal: 24 Juni 2026)
        if (!empty($tanggalRaw)) {
            $bulanId = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $ts = strtotime($tanggalRaw);
            $tanggalPdf = date('d', $ts) . ' ' . $bulanId[(int) date('n', $ts)] . ' ' . date('Y', $ts);
        } else {
            $bulanId = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tanggalPdf = date('d') . ' ' . $bulanId[(int) date('n')] . ' ' . date('Y');
        }
        
        // Ukuran kertas custom untuk F4 (215.9 mm x 330.2 mm / 8.5 x 13 in)
        // Jika tidak dikenali, default ke A4
        $paperSize = ($kertas === 'F4') ? [0, 0, 612.28, 935.43] : 'A4';

        if (empty($kolom)) {
            $kolom = ['no', 'name', 'Tanda Tangan'];
        }

        // Ambil data sekolah
        $sekolah = $this->schoolModel->first();

        // ── Kelompokkan siswa per kelas
        if ($kelasId) {
            // Satu kelas saja
            $kelas      = $this->classModel->find($kelasId);
            $namaKelas  = $kelas ? $kelas['name'] : '-';
            $siswaKelas = $this->studentModel->getByClass($kelasId);

            $siswaPerKelas = [
                ['namaKelas' => $namaKelas, 'siswa' => $siswaKelas],
            ];
        } else {
            // Semua kelas — kelompokkan dari student_records
            $semuaKelas = $this->classModel->orderBy('name', 'ASC')->findAll();
            $siswaPerKelas = [];
            foreach ($semuaKelas as $kls) {
                $s = $this->studentModel->getByClass($kls['id']);
                if (!empty($s)) {
                    $siswaPerKelas[] = ['namaKelas' => $kls['name'], 'siswa' => $s];
                }
            }
        }

        $fileTitle = $kelasId
            ? 'daftar-hadir-' . url_title($siswaPerKelas[0]['namaKelas'], '-', true)
            : 'daftar-hadir-semua-kelas';

        $pdfGenerator = new PdfGenerator();

        // Ambil kop_base64 secara manual agar bisa di-render di SETIAP halaman kelas
        // PdfGenerator hanya inject sekali di awal <body>, tidak cocok untuk multi-halaman
        $kopBase64 = $useKop ? $pdfGenerator->getKopBase64() : null;

        $data = [
            'siswaPerKelas' => $siswaPerKelas,
            'kolom'         => $kolom,
            'sekolah'       => $sekolah,
            'kertas'        => $kertas,
            'namaKegiatan'  => $namaKegiatan,
            'kop_base64'    => $kopBase64,
            'tampilTtd'     => $tampilTtd,
            'tanggalPdf'    => $tanggalPdf,
        ];

        // useKop = false karena kop sudah di-handle langsung di dalam template
        $pdfGenerator->stream(
            'admin/tata_usaha/pdf/daftar_hadir_template',
            $data,
            $fileTitle . '.pdf',
            'portrait',
            false,   // <-- matikan injeksi otomatis
            false,
            $paperSize
        );
        exit;
    }
}
