<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LaporanModel;
use App\Models\TahunAjaranModel;
use App\Models\PosBayarModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanLain extends BaseController
{
    protected LaporanModel $laporanModel;
    protected TahunAjaranModel $tahunModel;
    protected PosBayarModel $posModel;

    public function __construct()
    {
        $this->laporanModel = new LaporanModel();
        $this->tahunModel = new TahunAjaranModel();
        $this->posModel = new PosBayarModel();
    }

    public function index()
    {
        return view('admin/laporanlain/index');
    }

    public function terimaPerTahun()
    {
        $tahun = $this->request->getGet('tahun');
        $pos = $this->request->getGet('pos');
        $export = $this->request->getGet('export');

        $result = $this->laporanModel->terima_per_tahun($tahun, $pos);
        if ($export && $result) {
            $this->exportExcel([[$result['tahun_ajaran'], $result['total']]], ['Tahun Ajaran', 'Total'], 'laporan_per_tahun.xlsx');
        }

        return view('admin/laporanlain/per_tahun', [
            'data' => $result,
            'tahunList' => $this->tahunModel->orderBy('urutan', 'desc')->findAll(),
            'posList' => $this->posModel->where('aktif', 1)->findAll(),
            'tahun' => $tahun,
            'pos' => $pos,
        ]);
    }

    public function terimaBulanan()
    {
        $tahun = $this->request->getGet('tahun');
        $export = $this->request->getGet('export');

        $rows = $this->laporanModel->terima_bulanan($tahun);

        if ($export) {
            $data = array_map(fn($r) => [$r['bulan'], $r['total']], $rows);
            $this->exportExcel($data, ['Bulan', 'Total'], "laporan_bulanan_$tahun.xlsx");
        }

        return view('admin/laporanlain/per_bulan', [
            'data' => $rows,
            'tahunList' => $this->tahunModel->orderBy('urutan', 'desc')->findAll(),
            'tahun' => $tahun,
        ]);
    }

    public function terimaRentang()
    {
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');
        $pos   = $this->request->getGet('pos');
    
        // Validasi awal: jika belum pilih semua input, langsung tampilkan form kosong
        if (!$start || !$end || !$pos) {
            return view('admin/laporanlain/rentang', [
                'data'    => null,
                'posList' => $this->posModel->where('aktif', 1)->findAll(),
                'start'   => $start,
                'end'     => $end,
                'pos'     => $pos,
            ]);
        }
    
        // Ambil data dari model
        $result = $this->laporanModel->terima_rentang($start, $end, $pos);
    
        return view('admin/laporanlain/rentang', [
            'data'    => $result,
            'posList' => $this->posModel->where('aktif', 1)->findAll(),
            'start'   => $start,
            'end'     => $end,
            'pos'     => $pos,
        ]);
    }


    private function exportExcel(array $rows, array $heading, string $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($heading, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');
    
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"$filename\"");
        header('Cache-Control: max-age=0');
    
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit; 
    }
    
    public function exportPerTahun()
    {
        $tahun = $this->request->getGet('tahun');
        $pos = $this->request->getGet('pos');
    
        if (!$tahun) {
            return redirect()->back()->with('error', 'Tahun ajaran wajib dipilih.');
        }
    
        $result = $this->laporanModel->terima_per_tahun($tahun, $pos);
        if (!$result) {
            return redirect()->back()->with('error', 'Tidak ada data untuk diekspor.');
        }
    
        $rows = [[$result['tahun_ajaran'], $result['total']]];
        $this->exportExcel($rows, ['Tahun Ajaran', 'Total'], "laporan_per_tahun_$tahun.xlsx");
    }

    public function exportPerBulan()
    {
        $tahun = $this->request->getGet('tahun');
        if (!$tahun) {
            return redirect()->back()->with('error', 'Tahun ajaran wajib diisi.');
        }
    
        $rows = $this->laporanModel->terima_bulanan($tahun);
        $grouped = array_column($rows, 'total', 'bulan');
    
        $bulanUrut = [7,8,9,10,11,12,1,2,3,4,5,6];
        $bulanMap = [
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober',
            11 => 'November', 12 => 'Desember',
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni'
        ];
    
        $final = [];
        foreach ($bulanUrut as $bulan) {
            $final[] = [$bulanMap[$bulan], $grouped[$bulan] ?? 0];
        }
    
        $this->exportExcel($final, ['Bulan', 'Total'], "laporan_bulanan_$tahun.xlsx");
    }

    public function exportRentang()
    {
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');
        $pos   = $this->request->getGet('pos');
    
        if (!$start || !$end || !$pos) {
            return redirect()->back()->with('error', 'Silakan isi tanggal dan pilih Pos Bayar terlebih dahulu.');
        }
    
        $result = $this->laporanModel->terima_rentang($start, $end, $pos);
        if (!$result) {
            return redirect()->back()->with('error', 'Tidak ada data untuk diekspor.');
        }
    
        $rows = array_map(fn($r) => [$r['tahun_ajaran'], $r['total']], $result);
        $this->exportExcel($rows, ['Tahun Ajaran', 'Total'], "laporan_rentang.xlsx");
    }
    

}
