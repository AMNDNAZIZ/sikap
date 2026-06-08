<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OutgoingLetterModel;
use App\Models\IncomingLetterModel;
use App\Models\SchoolModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AgendaSurat extends BaseController
{
    protected OutgoingLetterModel $outgoing;
    protected IncomingLetterModel $incoming;

    public function __construct()
    {
        $this->outgoing = new OutgoingLetterModel();
        $this->incoming = new IncomingLetterModel();
    }

    /**
     * Dashboard Buku Agenda (tab Keluar & Masuk)
     */
    public function index()
    {
        $tab    = $this->request->getGet('tab') ?? 'keluar';
        $search = $this->request->getGet('search') ?? '';
        $from   = $this->request->getGet('date_from') ?? '';
        $to     = $this->request->getGet('date_to') ?? '';
        $type   = $this->request->getGet('letter_type') ?? '';
        $status = $this->request->getGet('status') ?? '';
        $page   = (int) ($this->request->getGet('page') ?? 1);

        $params = compact('search', 'from', 'to', 'type', 'status', 'page') + ['limit' => 50, 'date_from' => $from, 'date_to' => $to, 'letter_type' => $type];

        $outStats = $this->outgoing->getStats();
        $inStats  = $this->incoming->getStats();

        $outResult = $this->outgoing->getFiltered($params);
        $inResult  = $this->incoming->getFiltered($params);

        return view('admin/surat/agenda/index', [
            'title'        => 'Buku Agenda Surat',
            'tab'          => $tab,
            'outgoing'     => $outResult['data'],
            'incoming'     => $inResult['data'],
            'out_total'    => $outResult['total'],
            'in_total'     => $inResult['total'],
            'out_stats'    => $outStats,
            'in_stats'     => $inStats,
            'filter'       => $params,
            'totalPages'   => ceil(($tab === 'keluar' ? $outResult['total'] : $inResult['total']) / 50),
            'letter_types' => SuratKeluar::LETTER_TYPES,
        ]);
    }

    /**
     * Export Excel — Buku Agenda Surat Keluar & Masuk
     */
    public function exportExcel()
    {
        $params = [
            'date_from'   => $this->request->getGet('date_from') ?? '',
            'date_to'     => $this->request->getGet('date_to') ?? '',
            'letter_type' => $this->request->getGet('letter_type') ?? '',
            'status'      => $this->request->getGet('status') ?? '',
            'limit'       => 2000,
        ];

        $outResult = $this->outgoing->getFiltered($params);
        $inResult  = $this->incoming->getFiltered($params);

        $outgoing = $outResult['data'];
        $incoming = $inResult['data'];

        $spreadsheet = new Spreadsheet();

        // ─── Sheet 1: Surat Keluar ───────────────────────────────────
        $sh = $spreadsheet->getActiveSheet();
        $sh->setTitle('Surat Keluar');

        $headers = ['No', 'Nomor Surat', 'Tanggal', 'Jenis Surat', 'Nama Penerima', 'Keperluan / Perihal', 'Status'];
        foreach ($headers as $col => $h) {
            $cell = chr(65 + $col) . '1';
            $sh->setCellValue($cell, $h);
            $sh->getStyle($cell)->getFont()->setBold(true);
            $sh->getStyle($cell)->getFill()
               ->setFillType(Fill::FILL_SOLID)
               ->getStartColor()->setRGB('DBEAFE');
        }

        foreach ($outgoing as $i => $r) {
            $row = $i + 2;
            $sh->setCellValue('A' . $row, $i + 1);
            $sh->setCellValue('B' . $row, $r['letter_number']);
            $sh->setCellValue('C' . $row, date('d/m/Y', strtotime($r['issued_at'])));
            $sh->setCellValue('D' . $row, SuratKeluar::LETTER_TYPES[$r['letter_type']] ?? $r['letter_type']);
            $sh->setCellValue('E' . $row, $r['recipient_name']);
            $sh->setCellValue('F' . $row, $r['subject']);
            $sh->setCellValue('G' . $row, ucfirst($r['status']));
        }

        foreach (range('A', 'G') as $col) {
            $sh->getColumnDimension($col)->setAutoSize(true);
        }

        if (count($outgoing) > 0) {
            $sh->getStyle('A1:G' . (count($outgoing) + 1))
               ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // ─── Sheet 2: Surat Masuk ────────────────────────────────────
        $spreadsheet->createSheet();
        $sh2 = $spreadsheet->getSheet(1);
        $sh2->setTitle('Surat Masuk');

        $headers2 = ['No', 'Tgl Diterima', 'Nomor Surat', 'Pengirim', 'Instansi', 'Perihal', 'Kategori'];
        foreach ($headers2 as $col => $h) {
            $cell = chr(65 + $col) . '1';
            $sh2->setCellValue($cell, $h);
            $sh2->getStyle($cell)->getFont()->setBold(true);
            $sh2->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D1FAE5');
        }

        foreach ($incoming as $i => $r) {
            $row = $i + 2;
            $sh2->setCellValue('A' . $row, $i + 1);
            $sh2->setCellValue('B' . $row, date('d/m/Y', strtotime($r['received_at'])));
            $sh2->setCellValue('C' . $row, $r['letter_number'] ?? '-');
            $sh2->setCellValue('D' . $row, $r['sender_name']);
            $sh2->setCellValue('E' . $row, $r['sender_agency'] ?? '-');
            $sh2->setCellValue('F' . $row, $r['subject']);
            $sh2->setCellValue('G' . $row, SuratMasuk::CATEGORIES[$r['letter_category'] ?? ''] ?? ($r['letter_category'] ?? '-'));
        }

        foreach (range('A', 'G') as $col) {
            $sh2->getColumnDimension($col)->setAutoSize(true);
        }

        if (count($incoming) > 0) {
            $sh2->getStyle('A1:G' . (count($incoming) + 1))
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $spreadsheet->setActiveSheetIndex(0);

        // Build filename
        $period = '';
        if (!empty($params['date_from']) && !empty($params['date_to'])) {
            $period = '_' . date('dmY', strtotime($params['date_from'])) . '-' . date('dmY', strtotime($params['date_to']));
        } else {
            $period = '_' . date('Y');
        }

        $filename = 'Agenda_Surat' . $period . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
