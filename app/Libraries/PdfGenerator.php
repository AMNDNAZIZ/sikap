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

    public function getKopBase64(): ?string
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
     * @param string|array $paper Ukuran kertas (contoh 'A4' atau array [0,0,612.28,935.43] untuk F4)
     */
    public function stream(string $viewName, array $data, string $filename, string $orientation = 'portrait', bool $useKop = true, bool $attachment = false, $paper = 'A4'): void
    {
        // Naikkan memory limit sementara hanya untuk proses PDF ini
        $prevMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        // Hanya set kop_base64 jika belum disediakan caller (misal: template multi-halaman yang inject sendiri)
        if (!array_key_exists('kop_base64', $data)) {
            $kopBase64 = $useKop ? $this->getKopBase64() : null;
            $data['kop_base64'] = $kopBase64;
        } else {
            // Caller sudah menyiapkan kop_base64, ambil nilainya untuk logika inject di bawah
            $kopBase64 = $data['kop_base64'];
        }

        // Render view konten utama (view ini adalah dokumen HTML lengkap)
        $html = view($viewName, $data);

        // Inject kop surat langsung ke dalam <body> view yang sudah di-render.
        // Ini menghindari double-nesting HTML (<html> di dalam <html>) yang
        // menyebabkan Dompdf HTML5 parser menghabiskan ratusan MB memory.
        if ($kopBase64 && $useKop) {
            $kopHtml = '<div style="width:100%;text-align:center;margin-bottom:4px;">'
                . '<img src="' . $kopBase64 . '" style="width:100%;height:auto;display:block;margin:0 auto;" />'
                . '</div>'
                . '<div style="border-top:2px solid #000;margin-top:5px;margin-bottom:15px;"></div>';

            // Sisipkan tepat setelah tag <body ...>
            $html = preg_replace('/(<body[^>]*>)/i', '$1' . $kopHtml, $html, 1);
        }

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false); // false = lebih hemat memori (tidak fetch URL eksternal)
        $options->set('defaultFont', 'DejaVu Sans');

        // Set writable paths for Dompdf to avoid write/permission issues on hosting
        $tempDir = WRITEPATH . 'tmp';
        $fontDir = WRITEPATH . 'fonts';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        if (!is_dir($fontDir)) {
            @mkdir($fontDir, 0777, true);
        }
        $options->set('tempDir', $tempDir);
        $options->set('fontDir', $fontDir);
        $options->set('fontCache', $fontDir);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        // Kembalikan memory limit ke semula setelah selesai
        ini_set('memory_limit', $prevMemoryLimit);

        // Turn off error reporting to prevent deprecation warnings or PHP notices from polluting the PDF binary stream
        error_reporting(0);
        ini_set('display_errors', '0');

        // Disable zlib output compression if it is enabled
        if (!headers_sent() && ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        // Clean any active output buffers to ensure no pre-existing whitespace, BOM, or warnings are prepended to the PDF
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream($filename, ['Attachment' => $attachment]);
        exit;
    }
}
