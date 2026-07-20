<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BukuTamuModel;
use App\Models\SchoolModel;
use App\Models\SettingsModel;
use App\Libraries\PdfGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class BukuTamuAdmin extends BaseController
{
    protected BukuTamuModel $model;

    public function __construct()
    {
        $this->model = new BukuTamuModel();
    }

    /**
     * Dashboard Buku Tamu Admin
     */
    public function index()
    {
        $type   = $this->request->getGet('type')   ?? '';
        $month  = $this->request->getGet('month')  ?? '';
        $year   = $this->request->getGet('year')   ?? date('Y');
        $search = $this->request->getGet('search') ?? '';

        $data_tamu = $this->model->getFiltered($type, $month, $year, $search);
        $stats     = $this->model->getStats();
        $school    = (new SchoolModel())->first();

        return view('admin/buku_tamu/index', [
            'title'      => 'Buku Tamu Digital',
            'school'     => $school,
            'data_tamu'  => $data_tamu,
            'stats'      => $stats,
            'filter'     => compact('type', 'month', 'year', 'search'),
        ]);
    }

    /**
     * Export PDF
     */
    public function exportPdf()
    {
        $type   = $this->request->getGet('type')   ?? '';
        $month  = $this->request->getGet('month')  ?? '';
        $year   = $this->request->getGet('year')   ?? date('Y');
        $search = $this->request->getGet('search') ?? '';
        $use_kop= $this->request->getGet('use_kop') == 1;

        $data_tamu = $this->model->getFiltered($type, $month, $year, $search);
        $school    = (new SchoolModel())->first();

        // Periode label
        $periodeLabel = $this->buildPeriodeLabel($month, $year, $type);

        $data = [
            'school'       => $school,
            'data_tamu'    => $data_tamu,
            'periodeLabel' => $periodeLabel,
            'printDate'    => date('d F Y'),
            'filter_type'  => $type,
        ];

        // Build filename
        $typeLabel = match($type) {
            'umum'  => '_Umum',
            'dinas' => '_Dinas',
            default => '',
        };
        $monthLabel = $month ? '_' . date('F', mktime(0,0,0,(int)$month,1)) : '';
        $filename = 'Buku_Tamu' . $typeLabel . $monthLabel . '_' . $year . '.pdf';

        $pdfGenerator = new PdfGenerator();
        $pdfGenerator->stream('admin/buku_tamu/exports/pdf_template', $data, $filename, 'landscape', $use_kop, false);
        exit;
    }

    /**
     * Export Excel
     */
    public function exportExcel()
    {
        $type   = $this->request->getGet('type')   ?? '';
        $month  = $this->request->getGet('month')  ?? '';
        $year   = $this->request->getGet('year')   ?? date('Y');
        $search = $this->request->getGet('search') ?? '';

        $data_tamu     = $this->model->getFiltered($type, $month, $year, $search);
        $periodeLabel  = $this->buildPeriodeLabel($month, $year, $type);
        $school        = (new SchoolModel())->first();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Buku Tamu');

        $lastCol = $type === 'umum' ? 'H' : 'I';

        // Header info
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'BUKU TAMU DIGITAL — ' . strtoupper($school['name'] ?? 'SEKOLAH'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'Periode: ' . $periodeLabel . ' | Dicetak: ' . date('d F Y'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        // Table header — row 4
        $headers = $type === 'umum' 
            ? ['No', 'Tanggal', 'Jenis', 'Nama', 'Instansi / Keterangan', 'Tujuan', 'Bertemu Dengan', 'No HP']
            : ['No', 'Tanggal', 'Jenis', 'Nama', 'NIP', 'Instansi / Keterangan', 'Tujuan', 'Bertemu Dengan', 'No HP'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '4';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DBEAFE');
        }

        // Data rows
        $row = 5;
        foreach ($data_tamu as $i => $tamu) {
            $instansiKet = $tamu['guest_type'] === 'dinas'
                ? ($tamu['instansi'] ?? '-')
                : ($tamu['is_ortu_siswa'] ? 'Orang Tua Siswa' : ($tamu['instansi'] ?? '-'));

            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, date('d/m/Y H:i', strtotime($tamu['created_at'])));
            $sheet->setCellValue('C' . $row, ucfirst($tamu['guest_type']));
            $sheet->setCellValue('D' . $row, $tamu['nama']);
            
            if ($type === 'umum') {
                $sheet->setCellValue('E' . $row, $instansiKet);
                $sheet->setCellValue('F' . $row, $tamu['tujuan']);
                $sheet->setCellValue('G' . $row, $tamu['bertemu_dengan'] ?? '-');
                $sheet->setCellValue('H' . $row, $tamu['no_hp'] ?? '-');
            } else {
                // Kolom NIP diset sebagai string agar tidak berubah ke notasi saintifik
                $sheet->setCellValueExplicit('E' . $row, $tamu['nip'] ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('F' . $row, $instansiKet);
                $sheet->setCellValue('G' . $row, $tamu['tujuan']);
                $sheet->setCellValue('H' . $row, $tamu['bertemu_dengan'] ?? '-');
                $sheet->setCellValue('I' . $row, $tamu['no_hp'] ?? '-');
            }
            $row++;
        }

        // Auto size columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Border for data area
        if (count($data_tamu) > 0) {
            $lastRow = 4 + count($data_tamu);
            $sheet->getStyle("A4:{$lastCol}" . $lastRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        // Build filename
        $typeLabel = match($type) {
            'umum'  => '_Umum',
            'dinas' => '_Dinas',
            default => '',
        };
        $monthLabel = $month ? '_' . date('F', mktime(0,0,0,(int)$month,1)) : '';
        $filename = 'Buku_Tamu' . $typeLabel . $monthLabel . '_' . $year . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Hapus data tamu
     */
    public function delete(int $id)
    {
        $this->model->delete($id);
        return redirect()->to(base_url('admin/buku-tamu'))->with('success', 'Data tamu berhasil dihapus.');
    }

    /**
     * Print halaman QR Code statis
     */
    public function printQr()
    {
        $school = (new SchoolModel())->first();
        $url    = base_url('buku-tamu');

        return view('admin/buku_tamu/print_qr', [
            'title'  => 'Print QR Code Buku Tamu',
            'school' => $school,
            'url'    => $url,
        ]);
    }

    /**
     * Build label periode untuk judul laporan
     */
    private function buildPeriodeLabel(string $month, string $year, string $type): string
    {
        $parts = [];
        if ($month) {
            $parts[] = date('F', mktime(0, 0, 0, (int) $month, 1));
        }
        if ($year) {
            $parts[] = $year;
        }
        $label = $parts ? implode(' ', $parts) : 'Semua Periode';

        if ($type && $type !== 'semua') {
            $label .= ' — Tamu ' . ucfirst($type);
        }

        return $label;
    }
}
