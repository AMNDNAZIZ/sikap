<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Models\SettingsModel;

class ExcelGenerator
{
    protected $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
    }

    /**
     * @param string $filename Nama file output (tanpa extensi)
     * @param callable $writeDataCallback fungsi($sheet, $startRow) dipanggil controller untuk mengisi tabel datanya
     * @param bool $useKop Apakah menggunakan injeksi baris Kop Surat gambar di sheet?
     */
    public function download(string $filename, callable $writeDataCallback, bool $useKop = true): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $startRow = 1;

        if ($useKop) {
            $relativePath = $this->settingsModel->getValue('kop_surat');
            if ($relativePath) {
                $absolutePath = FCPATH . $relativePath;
                if (file_exists($absolutePath)) {
                    $drawing = new Drawing();
                    $drawing->setName('Kop Surat');
                    $drawing->setDescription('Kop Surat Identitas');
                    $drawing->setPath($absolutePath);
                    $drawing->setCoordinates('A1');
                    $drawing->setHeight(100);
                    $drawing->setWorksheet($sheet);

                    // Set tinggi baris 1 agar gambar tidak tumpang tindih dengan data di bawahnya
                    $sheet->getRowDimension('1')->setRowHeight(80);
                    $startRow = 5; 
                }
            }
        }

        // Panggil fungsi anonymous callback yang di definisikan oleh controller untuk injeksi logic cell report-nya
        call_user_func($writeDataCallback, $sheet, $startRow);

        // Keluarkan sebagai object Response Xlsx
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
