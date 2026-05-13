<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\SettingsModel;

class PdfGenerator
{
    protected $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
    }

    protected function getKopBase64(): ?string
    {
        $relativePath = $this->settingsModel->getValue('kop_surat');
        if (!$relativePath) {
            return null;
        }

        // FCPATH menunjuk ke direktori public/ CodeIgniter 4
        $absolutePath = FCPATH . $relativePath;
        if (!file_exists($absolutePath)) {
            log_message('warning', 'File kop surat tidak ditemukan di disk: ' . $absolutePath);
            return null;
        }

        $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
        $mime = 'image/' . ($ext === 'jpg' || $ext === 'jpeg' ? 'jpeg' : 'png');
        $data = file_get_contents($absolutePath);
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * @param string $viewName   Nama view untuk di-render
     * @param array  $data       Data array yang akan dilempar ke view utama
     * @param string $filename   Nama file unduhan
     * @param string $orientation 'portrait' / 'landscape'
     * @param bool   $useKop     Apakah menyuntikkan kop surat?
     * @param bool   $attachment True jika file akan didownload langsung (attachment)
     */
    public function stream(string $viewName, array $data, string $filename, string $orientation = 'portrait', bool $useKop = true, bool $attachment = false): void
    {
        $data['kop_base64'] = $useKop ? $this->getKopBase64() : null;
        
        // Membungkus view konten dengan base template PDF
        $htmlContent = view($viewName, $data);
        
        // Menggunakan library string base rendering supaya base layout nya menyatu
        $html = view('exports/pdf_base_template', [
            'kop_base64' => $data['kop_base64'],
            'content'    => $htmlContent
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); 
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        $dompdf->stream($filename, ['Attachment' => $attachment]);
    }
}
