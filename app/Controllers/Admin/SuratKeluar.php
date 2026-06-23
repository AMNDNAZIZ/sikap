<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OutgoingLetterModel;
use App\Models\IncomingLetterModel;
use App\Models\QrVerificationModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\SettingsModel;
use App\Libraries\PdfGenerator;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use setasign\Fpdi\Fpdi;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SuratKeluar extends BaseController
{
    protected OutgoingLetterModel $model;

    // Label human-readable untuk setiap jenis surat
    public const LETTER_TYPES = [
        'keterangan_mutasi_masuk' => 'Ket. Mutasi Masuk',
        'keterangan_mengajar'     => 'Ket. Mengajar (Guru)',
        'keterangan_aktif'        => 'Ket. Siswa Aktif',
        'keterangan_lomba'        => 'Ket. Siswa Lomba',
        'keterangan_kjp'          => 'Ket. KJP / Dokumen Khusus',
        'surat_tugas'             => 'Surat Tugas',
        'undangan'                => 'Surat Undangan',
        'surat_custom'            => 'Surat Custom / Dinamis',
        'surat_eksternal'         => 'Upload Surat Eksternal',
    ];

    public function __construct()
    {
        $this->model = new OutgoingLetterModel();
    }

    /**
     * Dashboard / Index surat keluar
     */
    public function index()
    {
        $params = [
            'search'      => $this->request->getGet('search') ?? '',
            'date_from'   => $this->request->getGet('date_from') ?? '',
            'date_to'     => $this->request->getGet('date_to') ?? '',
            'letter_type' => $this->request->getGet('letter_type') ?? '',
            'status'      => $this->request->getGet('status') ?? '',
            'page'        => (int) ($this->request->getGet('page') ?? 1),
            'limit'       => 50,
        ];

        $result = $this->model->getFiltered($params);
        $stats  = $this->model->getStats();

        return view('admin/surat/keluar/index', [
            'title'        => 'Surat Keluar',
            'letters'      => $result['data'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'limit'        => $result['limit'],
            'totalPages'   => ceil($result['total'] / $result['limit']),
            'stats'        => $stats,
            'filter'       => $params,
            'letter_types' => self::LETTER_TYPES,
        ]);
    }

    /**
     * Form Buat Surat Keluar
     */
    public function create()
    {
        $students = (new StudentModel())->findAll();
        $teachers = (new TeacherModel())->findAll();
        $settings = new SettingsModel();
        $school   = (new SchoolModel())->first();

        // Ambil data kepala sekolah dari settings
        $principalName = $settings->getValue('principal_name') ?? ($school['principal_name'] ?? '');
        $principalNip  = $settings->getValue('principal_nip')  ?? ($school['principal_nip']  ?? '');

        // Untuk preview client-side
        $pdfGen     = new PdfGenerator();
        $kopBase64  = $pdfGen->getKopBase64();
        $activeYear = $settings->getValue('active_school_year') ?? (date('Y') . '/' . (date('Y') + 1));

        return view('admin/surat/keluar/create', [
            'title'          => 'Buat Surat Keluar',
            'letter_types'   => self::LETTER_TYPES,
            'students'       => $students,
            'teachers'       => $teachers,
            'school'         => $school,
            'principal_name' => $principalName,
            'principal_nip'  => $principalNip,
            'kop_base64'     => $kopBase64,
            'active_year'    => $activeYear,
        ]);
    }

    /**
     * Simpan surat keluar baru
     */
    public function store()
    {
        $session    = session();
        $userId     = $session->get('user')['id'] ?? null;
        $letterType = $this->request->getPost('letter_type');

        // Validasi dasar
        $rules = [
            'letter_type'    => 'required|in_list[' . implode(',', array_keys(self::LETTER_TYPES)) . ']',
            'subject'        => 'required|min_length[5]',
            'issued_at'      => 'required|valid_date',
            'recipient_name' => 'required|min_length[3]',
        ];

        if ($letterType === 'surat_custom') {
            $rules['body_html']    = 'required';
            $rules['header_style'] = 'required|in_list[tengah,kiri_kanan]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $school   = (new SchoolModel())->first();
        $settings = new SettingsModel();

        $principalName = $settings->getValue('principal_name') ?? ($school['principal_name'] ?? '-');
        $principalNip  = $settings->getValue('principal_nip')  ?? ($school['principal_nip']  ?? '-');

        // Nomor surat otomatis
        $year     = (int) date('Y', strtotime($this->request->getPost('issued_at')));
        $seq      = $this->model->getNextSequenceNumber($year);
        $padding  = (int) ($settings->getValue('letter_number_padding') ?? 3);
        $letterNo = OutgoingLetterModel::buildLetterNumber($seq, $year, $padding);

        // UUID
        $qrCodeId = $this->generateUuid4();

        // Kumpulkan letter_data spesifik per jenis surat
        $letterData = $this->extractLetterData($letterType);

        // Multi-recipient (untuk lomba)
        $isMulti    = $letterType === 'keterangan_lomba';
        $recipients = null;
        if ($isMulti) {
            $names      = $this->request->getPost('rec_name')       ?? [];
            $kelas      = $this->request->getPost('rec_kelas')      ?? [];
            $birthDates = $this->request->getPost('rec_birth_date') ?? [];
            $cabang     = $this->request->getPost('rec_cabang')     ?? [];
            $recipients = [];
            foreach ($names as $i => $name) {
                if (!empty($name)) {
                    $recipients[] = [
                        'name'       => $name,
                        'kelas'      => $kelas[$i] ?? '',
                        'birth_date' => $birthDates[$i] ?? '',
                        'cabang'     => $cabang[$i] ?? '',
                    ];
                }
            }
            $recipients = json_encode($recipients);
        }

        // Insert ke DB dulu untuk mendapat ID
        $insertData = [
            'qr_code_id'              => $qrCodeId,
            'sequence_number'         => $seq,
            'letter_number'           => $letterNo,
            'issued_at'               => $this->request->getPost('issued_at'),
            'letter_type'             => $letterType,
            'subject'                 => $this->request->getPost('subject'),
            'sifat'                   => $this->request->getPost('sifat') ?: 'Biasa',
            'recipient_type'          => $this->request->getPost('recipient_type') ?: 'siswa',
            'recipient_ref_id'        => $this->request->getPost('recipient_ref_id') ?: null,
            'recipient_name'          => $this->request->getPost('recipient_name'),
            'recipient_detail'        => json_encode($this->extractRecipientDetail()),
            'is_multi_recipient'      => $isMulti ? 1 : 0,
            'recipients'              => $recipients,
            'letter_data'             => json_encode($letterData),
            'is_external'             => 0,
            'principal_name_snapshot' => $principalName,
            'principal_nip_snapshot'  => $principalNip,
            'status'                  => 'active',
            'created_by'              => $userId,
        ];

        $letterId = $this->model->insert($insertData);

        if (! $letterId) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan surat. Silakan coba lagi.');
        }

        // Generate PDF
        $pdfResult = $this->generateAndSavePdf($letterId, $qrCodeId, $school, $settings);

        if ($pdfResult) {
            $this->model->update($letterId, ['pdf_path' => $pdfResult['path'], 'pdf_url' => $pdfResult['url'], 'file_size_bytes' => $pdfResult['size']]);
        }

        return redirect()->to(base_url('admin/surat-keluar/detail/' . $letterId))
                         ->with('success', 'Surat <strong>' . $letterNo . '</strong> berhasil dibuat!');
    }

    /**
     * Detail surat
     */
    public function detail(int $id)
    {
        $letter = $this->model->find($id);
        if (! $letter) {
            return redirect()->to(base_url('admin/surat-keluar'))->with('error', 'Surat tidak ditemukan.');
        }

        $letter = $this->model->decodeJson($letter);

        $processedBody = '';
        if ($letter['letter_type'] === 'surat_custom') {
            $school   = (new SchoolModel())->first();
            $settings = new SettingsModel();
            $bodyHtml = $letter['letter_data']['body_html'] ?? '';
            $processedBody = $this->processPlaceholders($bodyHtml, $letter, $school, $settings);
        }

        return view('admin/surat/keluar/detail', [
            'title'          => 'Detail Surat — ' . $letter['letter_number'],
            'letter'         => $letter,
            'letter_types'   => self::LETTER_TYPES,
            'processed_body' => $processedBody,
        ]);
    }

    /**
     * Download / regenerate PDF
     */
    public function downloadPdf(int $id)
    {
        $letter = $this->model->find($id);
        if (! $letter) {
            return redirect()->back()->with('error', 'Surat tidak ditemukan.');
        }

        // Jika file PDF sudah ada, langsung streaming
        if (!empty($letter['pdf_path']) && file_exists(WRITEPATH . 'uploads/' . $letter['pdf_path'])) {
            $path = WRITEPATH . 'uploads/' . $letter['pdf_path'];
            $filename = str_replace('/', '_', $letter['letter_number']) . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            readfile($path);
            exit;
        }

        // Regenerate jika belum ada
        $school   = (new SchoolModel())->first();
        $settings = new SettingsModel();
        $result   = $this->generateAndSavePdf($id, $letter['qr_code_id'], $school, $settings);

        if ($result) {
            $this->model->update($id, ['pdf_path' => $result['path'], 'pdf_url' => $result['url'], 'file_size_bytes' => $result['size']]);
            $path     = WRITEPATH . 'uploads/' . $result['path'];
            $filename = str_replace('/', '_', $letter['letter_number']) . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            readfile($path);
            exit;
        }

        return redirect()->back()->with('error', 'Gagal generate PDF.');
    }

    /**
     * Cabut / revoke surat
     */
    public function revoke(int $id)
    {
        $reason = trim($this->request->getPost('revoke_reason') ?? '');

        if (strlen($reason) < 10) {
            return redirect()->back()->with('error', 'Alasan pencabutan minimal 10 karakter.');
        }

        $letter = $this->model->find($id);
        if (! $letter) {
            return redirect()->back()->with('error', 'Surat tidak ditemukan.');
        }

        $this->model->update($id, [
            'status'        => 'revoked',
            'revoked_at'    => date('Y-m-d H:i:s'),
            'revoke_reason' => $reason,
        ]);

        return redirect()->to(base_url('admin/surat-keluar/detail/' . $id))
                         ->with('success', 'Surat berhasil dicabut.');
    }

    /**
     * AJAX — Autocomplete siswa
     */
    public function searchSiswa()
    {
        $q        = $this->request->getGet('q') ?? '';
        $students = (new StudentModel())
            ->select('students.*, classes.name AS class_name')
            ->join('student_records', 'student_records.student_id = students.id AND student_records.status = "aktif"', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->groupStart()
                ->like('students.name', $q)
                ->orLike('students.nisn', $q)
            ->groupEnd()
            ->limit(10)
            ->findAll();

        $result = array_map(fn($s) => [
            'id'         => $s['id'],
            'text'       => $s['name'] . ' — NISN: ' . $s['nisn'] . ' (Kelas: ' . ($s['class_name'] ?? '-') . ')',
            'nisn'       => $s['nisn'],
            'nik'        => $s['nik'] ?? '',
            'ttl'        => ($s['birth_place'] ?? '') . ', ' . ($s['birth_date'] ?? ''),
            'birth_date' => $s['birth_date'] ?? '',
            'kelas'      => $s['class_name'] ?? '',
            'name'       => $s['name'],
            'alamat'     => $s['address'] ?? '',
        ], $students);

        return $this->response->setJSON($result);
    }

    /**
     * AJAX — Autocomplete guru
     */
    public function searchGuru()
    {
        $q        = $this->request->getGet('q') ?? '';
        $teachers = (new TeacherModel())->like('name', $q)->orLike('nip', $q)->limit(10)->findAll();

        $result = array_map(fn($t) => [
            'id'      => $t['id'],
            'text'    => $t['name'] . ' — NIP: ' . ($t['nip'] ?? '-'),
            'nip'     => $t['nip'] ?? '',
            'nik'     => $t['nik'] ?? '',
            'jabatan' => $t['functional_position'] ?: 'Guru',
            'name'    => $t['name'],
            'phone'   => $t['phone'] ?? '',
            'address' => $t['address'] ?? '',
        ], $teachers);

        return $this->response->setJSON($result);
    }

    // ─── Private Helpers ──────────────────────────────────────────────

    private function extractLetterData(string $type): array
    {
        $post = $this->request->getPost();

        return match ($type) {
            'keterangan_mutasi_masuk' => [
                'sekolah_asal'        => $post['sekolah_asal'] ?? '',
                'alamat_sekolah_asal' => $post['alamat_sekolah_asal'] ?? '',
                'kelas_diterima'      => $post['kelas_diterima'] ?? '',
                'semester'            => $post['semester'] ?? '',
            ],
            'keterangan_mengajar' => [
                'nik'                => $post['nik_guru'] ?? $post['nik'] ?? '',
                'satuan_pendidikan'  => $post['satuan_pendidikan'] ?? '',
                'alamat_satuan'      => $post['alamat_satuan'] ?? '',
                'alamat_tinggal'     => $post['alamat_tinggal'] ?? '',
                'no_hp'              => $post['no_hp'] ?? '',
                'kelas_mengajar'     => $post['kelas_mengajar'] ?? '',
            ],
            'keterangan_aktif' => [
                'nisn'               => $post['nisn'] ?? '',
                'ttl'                => $post['ttl'] ?? '',
                'kelas'              => $post['kelas'] ?? '',
                'keperluan_tambahan' => $post['keperluan_tambahan'] ?? '',
            ],
            'keterangan_lomba' => [
                'event_name'       => $post['event_name'] ?? '',
                'event_organizer'  => $post['event_organizer'] ?? '',
            ],
            'keterangan_kjp' => [
                'nisn'             => $post['nisn'] ?? '',
                'nik'              => $post['nik'] ?? '',
                'ttl'              => $post['ttl'] ?? '',
                'alamat_domisili'  => $post['alamat_domisili'] ?? '',
                'kelas'            => $post['kelas'] ?? '',
                'keperluan_detail' => $post['keperluan_detail'] ?? '',
                'lampiran'         => $post['lampiran_keterangan'] ?? '',
            ],
            'surat_tugas' => [
                'nip'                  => $post['nip'] ?? '',
                'jabatan'              => $post['jabatan'] ?? '',
                'activity_name'        => $post['activity_name'] ?? '',
                'activity_date'        => $post['activity_date'] ?? '',
                'activity_time'        => $post['activity_time'] ?? '',
                'activity_venue'       => $post['activity_venue'] ?? '',
                'activity_address'     => $post['activity_address'] ?? '',
                'ref_letter_number'    => $post['ref_letter_number'] ?? '',
                'ref_letter_date'      => $post['ref_letter_date'] ?? '',
                'ref_letter_subject'   => $post['ref_letter_subject'] ?? '',
                'ref_letter_from'      => $post['ref_letter_from'] ?? '',
            ],
            'undangan' => [
                'sifat'        => $post['sifat'] ?? 'Biasa',
                'event_day'    => $post['event_day'] ?? '',
                'event_date'   => $post['event_date'] ?? '',
                'event_time'   => $post['event_time'] ?? '',
                'event_venue'  => $post['event_venue'] ?? '',
                'event_agenda' => $post['event_agenda'] ?? '',
            ],
            'surat_custom' => [
                'header_style' => $post['header_style'] ?? 'tengah',
                'body_html'    => isset($post['body_html']) ? strip_tags($post['body_html'], '<p><a><strong><em><u><s><br><table><thead><tbody><tr><td><th><ul><ol><li><h1><h2><h3><h4><h5><h6><span><div><hr>') : '',
            ],
            'surat_eksternal' => [
                'original_filename' => $post['original_filename'] ?? '',
                'original_path'     => '',
                'final_path'        => '',
                'nomor_manual'      => $post['nomor_surat_manual'] ?? '',
                'catatan'           => $post['catatan_eksternal'] ?? '',
            ],
            default => [],
        };
    }

    private function extractRecipientDetail(): array
    {
        $post = $this->request->getPost();
        return array_filter([
            'nisn'    => $post['nisn'] ?? null,
            'nik'     => $post['nik'] ?? null,
            'ttl'     => $post['ttl'] ?? null,
            'kelas'   => $post['kelas'] ?? null,
            'nip'     => $post['nip'] ?? null,
            'jabatan' => $post['jabatan'] ?? null,
        ]);
    }

    private function generateAndSavePdf(int $letterId, string $qrCodeId, ?array $school, SettingsModel $settings): ?array
    {
        try {
            $letter = $this->model->decodeJson($this->model->find($letterId));
            if (! $letter) {
                return null;
            }

            // Generate QR PNG data URL (base64) agar bisa embed di PDF tanpa remote fetch
            $verifyUrl = base_url('verify/' . $qrCodeId);
            $qrCode    = new QrCode($verifyUrl, size: 160, margin: 0);
            $writer    = new PngWriter();
            $result    = $writer->write($qrCode);
            $qrDataUrl = $result->getDataUri();

            $activeYear = $settings->getValue('active_school_year') ?? (date('Y') . '/' . (date('Y') + 1));

            $viewData = [
                'letter'      => $letter,
                'school'      => $school,
                'qr_data_url' => $qrDataUrl,
                'active_year' => $activeYear,
            ];

            if ($letter['letter_type'] === 'surat_custom') {
                $bodyHtml = $letter['letter_data']['body_html'] ?? '';
                $viewData['processed_body'] = $this->processPlaceholders($bodyHtml, $letter, $school, $settings);
            }

            // Simpan ke file
            $dir      = WRITEPATH . 'uploads/surat_keluar/' . date('Y') . '/' . date('m') . '/';
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $filename    = $letterId . '_' . time() . '.pdf';
            $fullPath    = $dir . $filename;
            $viewName    = 'admin/surat/pdf_templates/' . $letter['letter_type'];

            // Gunakan PdfGenerator (inject KOP otomatis dari settings)
            $pdfGen  = new PdfGenerator();
            $kopB64  = $pdfGen->getKopBase64();
            $viewData['kop_base64'] = $kopB64; // agar template bisa pakai juga jika perlu

            $html = view($viewName, $viewData);

            // Inject KOP ke <body> persis seperti PdfGenerator::stream()
            if ($kopB64) {
                $kopHtml = '<div style="width:100%;text-align:center;margin-bottom:4px;">'
                    . '<img src="' . $kopB64 . '" style="width:100%;height:auto;display:block;margin:0 auto;" />'
                    . '</div>'
                    . '<div style="border-top:2px solid #000;margin-top:5px;margin-bottom:15px;"></div>';
                $html = preg_replace('/(<body[^>]*>)/i', '$1' . $kopHtml, $html, 1);
            }

            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->set_option('margin_left', 10);
            $dompdf->set_option('margin_right', 10);
            $dompdf->set_option('margin_top', 10);
            $dompdf->set_option('margin_bottom', 10);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($fullPath, $dompdf->output());

            $relativePath = 'surat_keluar/' . date('Y') . '/' . date('m') . '/' . $filename;
            return [
                'path' => $relativePath,
                'url'  => base_url('uploads/' . $relativePath),
                'size' => filesize($fullPath),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[SuratKeluar::generatePdf] ' . $e->getMessage());
            return null;
        }
    }

    private function processPlaceholders(string $bodyHtml, array $letter, ?array $school, SettingsModel $settings): string
    {
        $ld = $letter['letter_data'] ?? [];
        $recipientDetail = $letter['recipient_detail'] ?? [];

        $activeYear = $settings->getValue('active_school_year') ?? (date('Y') . '/' . (date('Y') + 1));
        
        $placeholders = [
            '{nama_penerima}'      => $letter['recipient_name'] ?? '-',
            '{nisn}'               => $recipientDetail['nisn'] ?? '-',
            '{kelas}'              => $recipientDetail['kelas'] ?? '-',
            '{ttl}'                => $recipientDetail['ttl'] ?? '-',
            '{nip}'                => $recipientDetail['nip'] ?? '-',
            '{jabatan}'            => $recipientDetail['jabatan'] ?? '-',
            '{nomor_surat}'        => $letter['letter_number'] ?? '-',
            '{tanggal_surat}'      => !empty($letter['issued_at']) ? date('d F Y', strtotime($letter['issued_at'])) : '-',
            '{tahun_pelajaran}'    => $activeYear,
            '{kepala_sekolah}'     => $letter['principal_name_snapshot'] ?? '-',
            '{nip_kepala_sekolah}' => $letter['principal_nip_snapshot'] ?? '-',
            '{nama_sekolah}'       => $school['name'] ?? '-',
            '{alamat_sekolah}'     => $school['address'] ?? '-',
        ];

        // Format Indonesian Date for {tanggal_surat}
        if (!empty($letter['issued_at'])) {
            $months = [
                'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
                'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
            ];
            $engDate = date('d F Y', strtotime($letter['issued_at']));
            $indDate = strtr($engDate, $months);
            $placeholders['{tanggal_surat}'] = $indDate;
        }

        foreach ($placeholders as $placeholder => $value) {
            $bodyHtml = str_replace($placeholder, $value ?? '-', $bodyHtml);
        }

        return $bodyHtml;
    }

    private function overlayQrOnPdf(string $originalPdfPath, string $qrPngPath, string $outputPath): bool
    {
        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($originalPdfPath);

            $qrSizeMm = 18;
            $marginMm = 10;

            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $pdf->importPage($i);
                $size  = $pdf->getTemplateSize($tplId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);

                if ($i === 1) {
                    $x = $marginMm;
                    $y = $size['height'] - $qrSizeMm - $marginMm - 5;
                    $pdf->Image($qrPngPath, $x, $y, $qrSizeMm, $qrSizeMm, 'PNG');
                    $pdf->SetFont('Helvetica', '', 6);
                    $pdf->SetXY($x - 2, $y + $qrSizeMm + 0.5);
                    $pdf->Cell($qrSizeMm + 4, 3, 'Scan untuk verifikasi', 0, 0, 'C');
                }
            }

            $pdf->Output('F', $outputPath);
            return true;
        } catch (\Throwable $e) {
            log_message('error', '[SuratKeluar::overlayQrOnPdf] ' . $e->getMessage());
            return false;
        }
    }

    public function createEksternal()
    {
        $settings = new SettingsModel();
        $school   = (new SchoolModel())->first();

        $principalName = $settings->getValue('principal_name') ?? ($school['principal_name'] ?? '');
        $principalNip  = $settings->getValue('principal_nip')  ?? ($school['principal_nip']  ?? '');
        $activeYear    = $settings->getValue('active_school_year') ?? (date('Y') . '/' . (date('Y') + 1));

        $year  = (int) date('Y');
        $seq   = $this->model->getNextSequenceNumber($year);
        $padding  = (int) ($settings->getValue('letter_number_padding') ?? 3);
        $previewNo = OutgoingLetterModel::buildLetterNumber($seq, $year, $padding);

        return view('admin/surat/keluar/create_eksternal', [
            'title'          => 'Upload Surat Eksternal',
            'school'         => $school,
            'principal_name' => $principalName,
            'principal_nip'  => $principalNip,
            'active_year'    => $activeYear,
            'preview_number' => $previewNo,
        ]);
    }

    public function storeEksternal()
    {
        $session = session();
        $userId  = $session->get('user')['id'] ?? null;

        $rules = [
            'pdf_file'       => 'uploaded[pdf_file]|max_size[pdf_file,5120]|ext_in[pdf_file,pdf]',
            'subject'        => 'required|min_length[5]',
            'issued_at'      => 'required|valid_date',
            'recipient_name' => 'required|min_length[3]',
            'recipient_type' => 'required|in_list[siswa,guru,eksternal,internal]',
            'sifat'          => 'required|in_list[Biasa,Penting,Segera,Rahasia]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('pdf_file');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->back()->withInput()->with('error', 'File PDF tidak valid.');
        }

        $mimeType = $file->getMimeType();
        if ($mimeType !== 'application/pdf') {
            return redirect()->back()->withInput()->with('error', 'File harus berformat PDF.');
        }

        $nomorManual = trim($this->request->getPost('nomor_surat_manual') ?? '');
        if ($nomorManual !== '') {
            $existing = $this->model->where('letter_number', $nomorManual)->first();
            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'Nomor surat <strong>' . esc($nomorManual) . '</strong> sudah digunakan. Gunakan nomor lain atau kosongkan untuk auto-generate.');
            }
        }

        $school   = (new SchoolModel())->first();
        $settings = new SettingsModel();

        $principalName = $settings->getValue('principal_name') ?? ($school['principal_name'] ?? '-');
        $principalNip  = $settings->getValue('principal_nip')  ?? ($school['principal_nip']  ?? '-');

        $issuedAt = $this->request->getPost('issued_at');
        $year     = (int) date('Y', strtotime($issuedAt));

        if ($nomorManual !== '') {
            $letterNo = $nomorManual;
            $seq      = 0;
        } else {
            $seq      = $this->model->getNextSequenceNumber($year);
            $padding  = (int) ($settings->getValue('letter_number_padding') ?? 3);
            $letterNo = OutgoingLetterModel::buildLetterNumber($seq, $year, $padding);
        }

        $qrCodeId = $this->generateUuid4();

        $letterData = [
            'original_filename' => $file->getClientName(),
            'original_path'     => '',
            'final_path'        => '',
            'nomor_manual'      => $nomorManual,
            'catatan'           => trim($this->request->getPost('catatan_eksternal') ?? ''),
        ];

        $insertData = [
            'qr_code_id'              => $qrCodeId,
            'sequence_number'         => $seq,
            'letter_number'           => $letterNo,
            'issued_at'               => $issuedAt,
            'letter_type'             => 'surat_eksternal',
            'subject'                 => $this->request->getPost('subject'),
            'sifat'                   => $this->request->getPost('sifat'),
            'recipient_type'          => $this->request->getPost('recipient_type'),
            'recipient_ref_id'        => $this->request->getPost('recipient_ref_id') ?: null,
            'recipient_name'          => $this->request->getPost('recipient_name'),
            'recipient_detail'        => json_encode($this->extractRecipientDetail()),
            'is_multi_recipient'      => 0,
            'recipients'              => null,
            'letter_data'             => json_encode($letterData),
            'is_external'             => 1,
            'principal_name_snapshot' => $principalName,
            'principal_nip_snapshot'  => $principalNip,
            'status'                  => 'active',
            'created_by'              => $userId,
        ];

        $letterId = $this->model->insert($insertData);
        if (! $letterId) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan surat. Silakan coba lagi.');
        }

        $dir = WRITEPATH . 'uploads/surat_keluar/' . date('Y') . '/' . date('m') . '/';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $originalFilename = $letterId . '_original_' . time() . '.pdf';
        $file->move($dir, $originalFilename);
        $originalRelPath = 'surat_keluar/' . date('Y') . '/' . date('m') . '/' . $originalFilename;

        $verifyUrl  = base_url('verify/' . $qrCodeId);
        $qrCode     = new QrCode($verifyUrl, size: 160, margin: 0);
        $writer     = new PngWriter();
        $qrResult   = $writer->write($qrCode);

        $tmpDir = WRITEPATH . 'tmp/';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        $qrPngPath = $tmpDir . 'qr_' . $letterId . '_' . time() . '.png';
        $qrResult->saveToFile($qrPngPath);

        $finalFilename = $letterId . '_final_' . time() . '.pdf';
        $finalFullPath = $dir . $finalFilename;
        $finalRelPath  = 'surat_keluar/' . date('Y') . '/' . date('m') . '/' . $finalFilename;

        $originalFullPath = $dir . $originalFilename;
        $overlaySuccess   = $this->overlayQrOnPdf($originalFullPath, $qrPngPath, $finalFullPath);

        if (file_exists($qrPngPath)) {
            unlink($qrPngPath);
        }

        $letterData['original_path'] = $originalRelPath;
        $letterData['final_path']    = $finalRelPath;

        $updateData = ['letter_data' => json_encode($letterData)];

        if ($overlaySuccess && file_exists($finalFullPath)) {
            $updateData['pdf_path']        = $finalRelPath;
            $updateData['pdf_url']         = base_url('uploads/' . $finalRelPath);
            $updateData['file_size_bytes'] = filesize($finalFullPath);
        } else {
            $updateData['pdf_path']        = $originalRelPath;
            $updateData['pdf_url']         = base_url('uploads/' . $originalRelPath);
            $updateData['file_size_bytes'] = filesize($originalFullPath);
        }

        $this->model->update($letterId, $updateData);

        return redirect()->to(base_url('admin/surat-keluar/detail/' . $letterId))
                         ->with('success', 'Surat eksternal <strong>' . esc($letterNo) . '</strong> berhasil diupload!');
    }

    public function viewPdf(int $id)
    {
        $letter = $this->model->find($id);
        if (! $letter) {
            return redirect()->back()->with('error', 'Surat tidak ditemukan.');
        }

        if (! empty($letter['pdf_path']) && file_exists(WRITEPATH . 'uploads/' . $letter['pdf_path'])) {
            $path = WRITEPATH . 'uploads/' . $letter['pdf_path'];
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="surat_' . $letter['id'] . '.pdf"');
            readfile($path);
            exit;
        }

        return redirect()->back()->with('error', 'File PDF tidak ditemukan.');
    }

    private function generateUuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
