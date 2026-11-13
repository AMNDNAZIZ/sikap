<?php
// 🎯 Standar Skill Diterapkan: api-design-quality-review (Backend)
// Refs: rekap-file-md/TandaTanganCap/Implementation-Plan-TandaTanganCap.md

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\SettingsModel;
use App\Helpers\SignatureHelper;

class PdfGenerator
{
    protected $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
    }

    /**
     * Ambil kop surat sebagai data-uri base64 (PNG/JPG).
     * Dikembalikan null jika setting kosong atau file tidak ditemukan.
     */
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

        $ext  = pathinfo($absolutePath, PATHINFO_EXTENSION);
        $mime = 'image/' . ($ext === 'jpg' || $ext === 'jpeg' ? 'jpeg' : 'png');
        $data = file_get_contents($absolutePath);
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Generate dan stream PDF ke browser.
     *
     * @param string       $viewName      Nama view untuk di-render
     * @param array        $data          Data array yang akan dilempar ke view
     * @param string       $filename      Nama file unduhan
     * @param string       $orientation   'portrait' / 'landscape'
     * @param bool         $useKop        Apakah menyuntikkan kop surat?
     * @param bool         $attachment    true = download, false = inline preview
     * @param string|array $paper         Ukuran kertas ('A4', 'F4', dll.)
     * @param bool         $useSignature  Apakah menyertakan tanda tangan Kepala Sekolah?
     * @param bool         $useSeal       Apakah menyertakan cap sekolah?
     */
    public function stream(
        string       $viewName,
        array        $data,
        string       $filename,
        string       $orientation  = 'portrait',
        bool         $useKop       = true,
        bool         $attachment   = false,
                     $paper        = 'A4',
        bool         $useSignature = false,
        bool         $useSeal      = false
    ): void {
        // ── 1. Naikkan memory limit sementara ───────────────────────────────────
        $prevMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        // ── 2. Siapkan kop_base64 (pola yang sudah ada) ─────────────────────────
        // Hanya set kop_base64 jika belum disediakan caller
        // (misal: template multi-halaman yang inject sendiri)
        if (!array_key_exists('kop_base64', $data)) {
            $kopBase64          = $useKop ? $this->getKopBase64() : null;
            $data['kop_base64'] = $kopBase64;
        } else {
            $kopBase64 = $data['kop_base64'];
        }

        // ── 3. Siapkan signature_base64 dan seal_base64 untuk view ──────────────
        // Dikirim ke view agar template bisa menampilkan sesuai flag checkbox.
        $data['signature_base64'] = $useSignature ? SignatureHelper::getSignatureBase64() : null;
        $data['seal_base64']      = $useSeal      ? SignatureHelper::getSealBase64()      : null;
        $data['use_signature']    = $useSignature;
        $data['use_seal']         = $useSeal;

        // ── 4. Render view konten utama (dokumen HTML lengkap) ──────────────────
        $html = view($viewName, $data);

        // ── 5. Inject kop surat tepat setelah <body> ────────────────────────────
        // Menghindari double-nesting HTML yang menyebabkan Dompdf menghabiskan
        // ratusan MB memory.
        if ($kopBase64 && $useKop) {
            $kopHtml = '<div style="width:100%;text-align:center;margin-bottom:4px;">'
                . '<img src="' . $kopBase64 . '" style="width:100%;height:auto;display:block;margin:0 auto;" />'
                . '</div>'
                . '<div style="border-top:2px solid #000;margin-top:5px;margin-bottom:15px;"></div>';

            $html = preg_replace('/(<body[^>]*>)/i', '$1' . $kopHtml, $html, 1);
        }

        // ── 6. Inject tanda tangan KS ke placeholder .signature-space ───────────
        // Placeholder di view: <div class="signature-space"></div>
        // Ukuran: max 120 px lebar, PNG transparan
        if ($useSignature && !empty($data['signature_base64'])) {
            $sigHtml = '<div class="signature-block" style="text-align:center;margin-top:10px;">'
                . '<img src="' . $data['signature_base64'] . '" '
                . 'style="width:120px;height:auto;display:block;margin:0 auto;" />'
                . '</div>';
            $html = preg_replace(
                '/<div[^>]*class=["\']signature-space["\'][^>]*>\s*<\/div>/i',
                $sigHtml,
                $html,
                1
            );
        }

        // ── 7. Inject cap sekolah ke placeholder .seal-space ───────────────────
        // Placeholder di view: <div class="seal-space"></div>
        // Ukuran: 100 px lebar agar proporsional dengan asli, PNG transparan
        if ($useSeal && !empty($data['seal_base64'])) {
            $sealHtml = '<div class="seal-block" style="text-align:center;margin-top:10px;">'
                . '<img src="' . $data['seal_base64'] . '" '
                . 'style="width:100px;height:auto;display:block;margin:0 auto;" />'
                . '</div>';
            $html = preg_replace(
                '/<div[^>]*class=["\']seal-space["\'][^>]*>\s*<\/div>/i',
                $sealHtml,
                $html,
                1
            );
        }

        // ── 8. Konfigurasi Dompdf ───────────────────────────────────────────────
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false); // hemat memori, tidak fetch URL eksternal
        $options->set('defaultFont', 'DejaVu Sans');

        // Set writable paths untuk menghindari masalah permission di hosting
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

        // ── 9. Render PDF ───────────────────────────────────────────────────────
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        // Kembalikan memory limit ke semula setelah render selesai
        ini_set('memory_limit', $prevMemoryLimit);

        // Matikan error reporting agar tidak mencemari binary stream PDF
        error_reporting(0);
        ini_set('display_errors', '0');

        // Nonaktifkan zlib output compression jika aktif
        if (!headers_sent() && ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        // Bersihkan output buffer agar tidak ada whitespace/BOM sebelum PDF
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream($filename, ['Attachment' => $attachment]);
        exit;
    }
}
