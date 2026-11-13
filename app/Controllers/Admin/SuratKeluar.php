<?php
// 🎯 Standar Skill Diterapkan: api-design-quality-review (Backend), high-end-visual-design (UI)
// Refs: rekap-file-md/TandaTanganCap/Implementation-Plan-TandaTanganCap.md

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OutgoingLetterModel;
use App\Models\IncomingLetterModel;
use App\Models\QrVerificationModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\SettingsModel;
use App\Models\ClassModel;
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
            'sort_by'     => $this->request->getGet('sort_by') ?? 'issued_at',
            'order'       => $this->request->getGet('order') ?? 'desc',
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

        // Ambil data kepala sekolah dari settings / school / teacher secara robust
        $pInfo         = $this->getPrincipalInfo($school, $settings);
        $principalName = $pInfo['name'];
        $principalNip  = $pInfo['nip'];

        // Untuk preview client-side
        $pdfGen     = new PdfGenerator();
        $kopBase64  = $pdfGen->getKopBase64();
        $activeYear = $settings->getValue('active_school_year') ?? (date('Y') . '/' . (date('Y') + 1));

        // Kelas aktif untuk pilihan penerima Surat Undangan (Wali Murid)
        $activeClasses = (new ClassModel())->where('is_active', 1)->orderBy('name', 'ASC')->findAll();

        // Estimasi nomor surat berikutnya (untuk preview auto-generate)
        $year = (int) date('Y');
        $padding = (int) ($settings->getValue('letter_number_padding') ?? 3);
        $defaultCode = $settings->getValue('default_classification_code') ?? 'PK.01.01';
        $seqRow = $this->model->db->table('letter_number_sequences')->where('year', $year)->get()->getRowArray();
        $maxRow = $this->model->db->table('outgoing_letters')
                     ->selectMax('sequence_number')
                     ->where('YEAR(issued_at)', $year)
                     ->get()->getRowArray();
        $curLastSeq = max((int)($seqRow['last_sequence'] ?? 0), (int)($maxRow['sequence_number'] ?? 0));
        $nextSeq = $curLastSeq + 1;
        $nextLetterNumber = OutgoingLetterModel::buildLetterNumber($nextSeq, $year, $padding, $defaultCode);

        // Ambil bank template surat dinamis
        $templateModel = new \App\Models\LetterTemplateModel();
        $templates     = $templateModel->getActiveTemplates();
        $duplicatedLetter = session()->getFlashdata('duplicated_letter');
        if (!$duplicatedLetter && $this->request->getGet('duplicated_from')) {
            $src = $this->model->find((int)$this->request->getGet('duplicated_from'));
            if ($src) {
                $duplicatedLetter = $this->model->decodeJson($src);
            }
        }

        return view('admin/surat/keluar/create', [
            'title'              => 'Buat Surat Keluar',
            'letter_types'       => self::LETTER_TYPES,
            'students'           => $students,
            'teachers'           => $teachers,
            'school'             => $school,
            'principal_name'     => $principalName,
            'principal_nip'      => $principalNip,
            'kop_base64'         => $kopBase64,
            'active_year'        => $activeYear,
            'active_classes'     => $activeClasses,
            'next_letter_number' => $nextLetterNumber,
            'templates'          => $templates,
            'duplicated_letter'  => $duplicatedLetter,
        ]);
    }

    /**
     * Simpan surat keluar baru
     */
    public function store()
    {
        $session    = session();
        $userSession = $session->get('user');
        $userId     = is_array($userSession) ? ($userSession['id'] ?? '1') : ($session->get('user_id') ?? '1');
        $letterType = $this->request->getPost('letter_type');

        $school   = (new SchoolModel())->first();
        $settings = new SettingsModel();

        // ── 1. Robust Fallback untuk Nama Penerima & Data Input ─────────────────
        $recipientName = trim((string)($this->request->getPost('recipient_name') ?? ''));
        $schoolName    = $school['name'] ?? 'Sekolah';

        if (empty($recipientName)) {
            if ($letterType === 'undangan') {
                $undanganMode = $this->request->getPost('undangan_recipient_mode') ?? 'all';
                if ($undanganMode === 'all') {
                    $recipientName = 'Guru & Tenaga Kependidikan ' . $schoolName;
                } elseif ($undanganMode === 'wali_murid') {
                    $kelas = $this->request->getPost('undangan_wali_class') ?? 'semua';
                    $recipientName = ($kelas === 'semua')
                        ? 'Orang Tua / Wali Murid ' . $schoolName
                        : 'Orang Tua / Wali Murid Kelas ' . $kelas . ' ' . $schoolName;
                } elseif (!empty($this->request->getPost('undangan_search_guru'))) {
                    $recipientName = trim((string)$this->request->getPost('undangan_search_guru'));
                } elseif (!empty($this->request->getPost('undangan_search_pegawai'))) {
                    $recipientName = trim((string)$this->request->getPost('undangan_search_pegawai'));
                }
            } elseif ($letterType === 'keterangan_lomba') {
                $recipientName = 'Daftar Terlampir';
            } elseif (in_array($letterType, ['keterangan_aktif', 'keterangan_mutasi_masuk', 'keterangan_kjp'])) {
                // Coba cari dari ref siswa atau nama siswa yang diketik
                $sSearch = trim((string)($this->request->getPost('recipient_search_siswa') ?? ''));
                if (!empty($sSearch)) {
                    $recipientName = $sSearch;
                } elseif (!empty($this->request->getPost('recipient_ref_id'))) {
                    $st = (new StudentModel())->find($this->request->getPost('recipient_ref_id'));
                    if ($st && !empty($st['name'])) {
                        $recipientName = $st['name'];
                    }
                }
            } elseif (in_array($letterType, ['keterangan_mengajar', 'surat_tugas'])) {
                $gSearch = trim((string)($this->request->getPost('recipient_search_guru') ?? ''));
                if (!empty($gSearch)) {
                    $recipientName = $gSearch;
                } elseif (!empty($this->request->getPost('recipient_ref_id'))) {
                    $tc = (new TeacherModel())->find($this->request->getPost('recipient_ref_id'));
                    if ($tc && !empty($tc['name'])) {
                        $recipientName = $tc['name'];
                    }
                }
            } elseif ($letterType === 'surat_custom') {
                $ekst = trim((string)($this->request->getPost('recipient_name_eksternal') ?? ''));
                if (!empty($ekst)) {
                    $recipientName = $ekst;
                }
            }
        }

        // Siapkan dataset lengkap yang akan divalidasi
        $postData = (array) $this->request->getPost();
        $postData['recipient_name'] = $recipientName;

        if (empty($postData['subject'])) {
            $postData['subject'] = self::LETTER_TYPES[$letterType] ?? 'Surat Dinas Keluar';
        }
        if (empty($postData['issued_at'])) {
            $postData['issued_at'] = date('Y-m-d');
        }

        // ── 2. Validasi Input ──────────────────────────────────────────────────
        $rules = [
            'letter_type'    => 'required|in_list[' . implode(',', array_keys(self::LETTER_TYPES)) . ']',
            'subject'        => 'required|min_length[5]',
            'issued_at'      => 'required|valid_date',
            'recipient_name' => 'required|min_length[2]',
        ];

        if ($letterType === 'surat_custom') {
            $rules['body_html']    = 'required';
            $rules['header_style'] = 'required|in_list[tengah,kiri_kanan]';
        }

        if (! $this->validateData($postData, $rules)) {
            $validationErrors = $this->validator->getErrors();
            log_message('warning', '[SuratKeluar::store] Validation failed: ' . json_encode($validationErrors));
            $summaryMsg = 'Validasi gagal: ' . implode(', ', $validationErrors);
            return redirect()->back()->withInput()->with('errors', $validationErrors)->with('error', $summaryMsg);
        }

        // Robust principal name & NIP fallback
        $pInfo         = $this->getPrincipalInfo($school, $settings);
        $principalName = $pInfo['name'];
        $principalNip  = $pInfo['nip'];

        // Opsi Nomor Surat: Auto-Generate vs Input Manual
        $numberMode  = $this->request->getPost('number_mode') ?? 'auto';
        $nomorManual = trim($this->request->getPost('nomor_surat_manual') ?? '');
        $year        = (int) date('Y', strtotime($this->request->getPost('issued_at')));
        $classificationCode = trim($this->request->getPost('classification_code') ?? '');

        if ($numberMode === 'manual' || !empty($nomorManual)) {
            if (empty($nomorManual)) {
                return redirect()->back()->withInput()->with('error', 'Nomor surat manual wajib diisi jika memilih opsi input manual.');
            }
            // Cek duplikasi nomor surat
            $existing = $this->model->where('letter_number', $nomorManual)->first();
            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'Nomor surat <strong>' . esc($nomorManual) . '</strong> sudah digunakan. Gunakan nomor lain atau pilih nomor otomatis.');
            }
            $letterNo = $nomorManual;

            // Smart auto-detection urutan nomor dari input manual (misal: 101/PK.01.01/2026 -> sequence = 101)
            $extractedSeq = 0;
            if (preg_match('/^(\d+)/', $nomorManual, $match)) {
                $extractedSeq = (int) $match[1];
            }
            $seq = $extractedSeq;

            // Sinkronkan ke tabel letter_number_sequences jika sequence ini lebih besar atau sama
            if ($extractedSeq > 0) {
                $this->model->db->query("INSERT INTO letter_number_sequences (year, last_sequence, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        last_sequence = GREATEST(last_sequence, ?),
                        updated_at    = NOW()", [$year, $extractedSeq, $extractedSeq]);
            }
        } else {
            $seq      = $this->model->getNextSequenceNumber($year);
            $padding  = (int) ($settings->getValue('letter_number_padding') ?? 3);
            $letterNo = OutgoingLetterModel::buildLetterNumber($seq, $year, $padding, $classificationCode ?: null);
        }

        // UUID
        $qrCodeId = $this->generateUuid4();

        // Kumpulkan letter_data spesifik per jenis surat
        $letterData = $this->extractLetterData($letterType);
        $useKop     = (int) ($this->request->getPost('use_kop') ?? 1);
        $templateId = $this->request->getPost('template_id') ?: null;

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
            'classification_code'     => $classificationCode ?: null,
            'subject'                 => $this->request->getPost('subject'),
            'sifat'                   => $this->request->getPost('sifat') ?: 'Biasa',
            'recipient_type'          => $this->request->getPost('recipient_type') ?: 'siswa',
            'recipient_ref_id'        => $this->request->getPost('recipient_ref_id') ?: null,
            'recipient_name'          => $recipientName ?: $this->request->getPost('recipient_name'),
            'recipient_detail'        => json_encode($this->extractRecipientDetail()),
            'is_multi_recipient'      => $isMulti ? 1 : 0,
            'recipients'              => $recipients,
            'letter_data'             => json_encode($letterData),
            'use_kop'                 => $useKop,
            'template_id'             => $templateId,
            'is_external'             => 0,
            'principal_name_snapshot' => $principalName,
            'principal_nip_snapshot'  => $principalNip,
            'status'                  => 'active',
            'created_by'              => $userId,
        ];

        try {
            $letterId = $this->model->insert($insertData);
        } catch (\Throwable $e) {
            log_message('error', '[SuratKeluar::store] Exception on insert: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan surat ke database: ' . $e->getMessage());
        }

        if (! $letterId) {
            $dbErr  = $this->model->db->error();
            $errMsg = !empty($dbErr['message']) ? $dbErr['message'] : 'Gagal menyimpan surat ke database. Silakan coba lagi.';
            log_message('error', '[SuratKeluar::store] Insert returned false: ' . $errMsg);
            return redirect()->back()->withInput()->with('error', $errMsg);
        }

        // Generate PDF
        $useSig = (bool) $settings->getValue('use_signature');
        $useSeal = (bool) $settings->getValue('use_seal');
        $pdfResult = $this->generateAndSavePdf($letterId, $qrCodeId, $school, $settings, $useSig, $useSeal);

        if ($pdfResult) {
            $this->model->update($letterId, ['pdf_path' => $pdfResult['path'], 'pdf_url' => $pdfResult['url'], 'file_size_bytes' => $pdfResult['size']]);
        }

        return redirect()->to(base_url('admin/surat-keluar/detail/' . $letterId))
                         ->with('success', 'Surat <strong>' . $letterNo . '</strong> berhasil dibuat!');
    }

    /**
     * Form Edit Surat Keluar
     */
    public function edit(int $id)
    {
        $letter = $this->model->find($id);
        if (!$letter) {
            return redirect()->to(base_url('admin/surat-keluar'))->with('error', 'Surat tidak ditemukan.');
        }

        if ($letter['status'] === 'revoked') {
            return redirect()->to(base_url('admin/surat-keluar/detail/' . $id))
                             ->with('error', 'Surat yang sudah dicabut tidak dapat diedit.');
        }

        $letter = $this->model->decodeJson($letter);

        $students = (new StudentModel())->findAll();
        $teachers = (new TeacherModel())->findAll();
        $settings = new SettingsModel();
        $school   = (new SchoolModel())->first();
        $pInfo    = $this->getPrincipalInfo($school, $settings);

        $pdfGen        = new PdfGenerator();
        $kopBase64     = $pdfGen->getKopBase64();
        $activeYear    = $settings->getValue('active_school_year') ?? (date('Y') . '/' . (date('Y') + 1));
        $activeClasses = (new ClassModel())->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
        $templates     = (new \App\Models\LetterTemplateModel())->getActiveTemplates();

        return view('admin/surat/keluar/edit', [
            'title'          => 'Edit Surat — ' . $letter['letter_number'],
            'letter'         => $letter,
            'letter_types'   => self::LETTER_TYPES,
            'students'       => $students,
            'teachers'       => $teachers,
            'school'         => $school,
            'principal_name' => $pInfo['name'],
            'principal_nip'  => $pInfo['nip'],
            'kop_base64'     => $kopBase64,
            'active_year'    => $activeYear,
            'active_classes' => $activeClasses,
            'templates'      => $templates,
        ]);
    }

    /**
     * Simpan Perubahan Edit Surat Keluar
     */
    public function update(int $id)
    {
        $letter = $this->model->find($id);
        if (!$letter) {
            return redirect()->to(base_url('admin/surat-keluar'))->with('error', 'Surat tidak ditemukan.');
        }

        if ($letter['status'] === 'revoked') {
            return redirect()->to(base_url('admin/surat-keluar/detail/' . $id))
                             ->with('error', 'Surat yang sudah dicabut tidak dapat diedit.');
        }

        $letter = $this->model->decodeJson($letter);
        $letterType = $letter['letter_type'];

        $rules = [
            'subject'        => 'required|min_length[5]',
            'issued_at'      => 'required|valid_date',
            'recipient_name' => 'required|min_length[3]',
        ];

        if ($letterType === 'surat_custom') {
            $rules['body_html']    = 'required';
            $rules['header_style'] = 'required|in_list[tengah,kiri_kanan]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $session     = session();
        $userSession = $session->get('user');
        $userId      = is_array($userSession) ? ($userSession['id'] ?? '1') : ($session->get('user_id') ?? '1');

        $school   = (new SchoolModel())->first();
        $settings = new SettingsModel();

        // Kumpulkan data terupdate
        $letterData      = $this->extractLetterData($letterType);
        $recipientDetail = $this->extractRecipientDetail();
        $useKop          = (int) ($this->request->getPost('use_kop') ?? 1);
        $templateId      = $this->request->getPost('template_id') ?: null;
        $classificationCode = trim($this->request->getPost('classification_code') ?? '');

        $updateData = [
            'issued_at'           => $this->request->getPost('issued_at'),
            'subject'             => $this->request->getPost('subject'),
            'sifat'               => $this->request->getPost('sifat') ?: 'Biasa',
            'recipient_name'      => $this->request->getPost('recipient_name'),
            'recipient_detail'    => json_encode($recipientDetail),
            'letter_data'         => json_encode($letterData),
            'use_kop'             => $useKop,
            'template_id'         => $templateId,
        ];

        if (!empty($classificationCode)) {
            $updateData['classification_code'] = $classificationCode;
        }

        // Catat Audit Trail sebelum & sesudah
        if ($this->model->db->tableExists('letter_audit_logs')) {
            $this->model->db->table('letter_audit_logs')->insert([
                'user_id'     => (string) $userId,
                'action'      => 'update_letter',
                'entity_type' => 'outgoing_letter',
                'entity_id'   => $id,
                'before_data' => json_encode([
                    'subject'          => $letter['subject'],
                    'issued_at'        => $letter['issued_at'],
                    'recipient_name'   => $letter['recipient_name'],
                    'recipient_detail' => $letter['recipient_detail'],
                    'letter_data'      => $letter['letter_data'],
                    'use_kop'          => $letter['use_kop'] ?? 1,
                ]),
                'after_data'  => json_encode($updateData),
                'ip_address'  => $this->request->getIPAddress(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $this->model->update($id, $updateData);

        // Regenerasi file PDF
        $useSig = (bool) $settings->getValue('use_signature');
        $useSeal = (bool) $settings->getValue('use_seal');
        $pdfResult = $this->generateAndSavePdf($id, $letter['qr_code_id'], $school, $settings, $useSig, $useSeal);
        if ($pdfResult) {
            $this->model->update($id, [
                'pdf_path'        => $pdfResult['path'],
                'pdf_url'         => $pdfResult['url'],
                'file_size_bytes' => $pdfResult['size'],
            ]);
        }

        return redirect()->to(base_url('admin/surat-keluar/detail/' . $id))
                         ->with('success', 'Surat <strong>' . esc($letter['letter_number']) . '</strong> berhasil diperbarui dan PDF telah digenerate ulang!');
    }

    /**
     * Duplikat / Salin Surat
     */
    public function duplicate(int $id)
    {
        $letter = $this->model->find($id);
        if (!$letter) {
            return redirect()->to(base_url('admin/surat-keluar'))->with('error', 'Surat tidak ditemukan.');
        }

        $letter = $this->model->decodeJson($letter);
        session()->setFlashdata('duplicated_letter', $letter);

        return redirect()->to(base_url('admin/surat-keluar/create?duplicated_from=' . $id))
                         ->with('info', 'Konten surat berhasil disalin. Silakan periksa kembali dan sesuaikan nomor atau tanggal jika diperlukan.');
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

        // Ambil riwayat audit log jika tabel ada
        $auditLogs = [];
        if ($this->model->db->tableExists('letter_audit_logs')) {
            $auditLogs = $this->model->db->table('letter_audit_logs')
                ->where('entity_id', $id)
                ->where('entity_type', 'outgoing_letter')
                ->orderBy('created_at', 'DESC')
                ->get()->getResultArray();
        }

        return view('admin/surat/keluar/detail', [
            'title'          => 'Detail Surat — ' . $letter['letter_number'],
            'letter'         => $letter,
            'letter_types'   => self::LETTER_TYPES,
            'processed_body' => $processedBody,
            'audit_logs'     => $auditLogs,
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

        $school   = (new SchoolModel())->first();
        $settings = new SettingsModel();

        // Ambil opsi tanda tangan & cap dari query param (dari modal download)
        // with_signature (1/0), with_seal (1/0)
        $withSignatureParam = $this->request->getGet('with_signature');
        $withSealParam      = $this->request->getGet('with_seal');

        $useSignature = $withSignatureParam !== null
            ? ($withSignatureParam === '1')
            : (bool) $settings->getValue('use_signature');

        $useSeal = $withSealParam !== null
            ? ($withSealParam === '1')
            : (bool) $settings->getValue('use_seal');

        // Selalu regenerate agar pembubuhan ttd/cap sesuai opsi yang dipilih di modal
        $result = $this->generateAndSavePdf($id, $letter['qr_code_id'], $school, $settings, $useSignature, $useSeal);

        if ($result) {
            $this->model->update($id, [
                'pdf_path'        => $result['path'],
                'pdf_url'         => $result['url'],
                'file_size_bytes' => $result['size'],
            ]);
            $path     = WRITEPATH . 'uploads/' . $result['path'];
            $filename = str_replace('/', '_', $letter['letter_number']) . '.pdf';
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
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
        $q          = $this->request->getGet('q') ?? '';
        $activeYear = $this->model->db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        $yearId     = $activeYear['id'] ?? null;

        $builder = (new StudentModel())
            ->select('students.*, classes.name AS class_name');

        if ($yearId) {
            $builder->join('student_records', 'student_records.student_id = students.id AND student_records.status = "aktif" AND student_records.academic_year_id = ' . (int) $yearId, 'left');
        } else {
            $builder->join('student_records', 'student_records.student_id = students.id AND student_records.status = "aktif"', 'left');
        }

        $students = $builder
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->groupStart()
                ->like('students.name', $q)
                ->orLike('students.nisn', $q)
            ->groupEnd()
            ->groupBy('students.id')
            ->limit(10)
            ->findAll();

        $result = array_map(fn($s) => [
            'id'               => $s['id'],
            'text'             => $s['name'] . ' — NISN: ' . $s['nisn'] . ' (Kelas: ' . ($s['class_name'] ?? '-') . ')',
            'nisn'             => $s['nisn'],
            'nis'              => $s['nis'] ?? '',
            'nik'              => $s['nik'] ?? '',
            'gender'           => ($s['gender'] ?? 'L') === 'L' ? 'Laki-laki' : 'Perempuan',
            'ttl'              => ($s['birth_place'] ?? '') . (!empty($s['birth_place']) && !empty($s['birth_date']) ? ', ' : '') . ($s['birth_date'] ?? ''),
            'birth_date'       => $s['birth_date'] ?? '',
            'kelas'            => $s['class_name'] ?? '',
            'name'             => $s['name'],
            'alamat'           => $s['address'] ?? '',
            'nama_ayah'        => $s['father_name'] ?? '',
            'pekerjaan_ayah'   => $s['father_job'] ?? '',
            'penghasilan_ayah' => $s['father_income'] ?? '',
            'nama_ibu'         => $s['mother_name'] ?? '',
            'pekerjaan_ibu'    => $s['mother_job'] ?? '',
            'nama_wali'        => $s['guardian_name'] ?? '',
            'alamat_ortu'      => $s['parent_address'] ?: ($s['address'] ?? ''),
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
            'id'                 => $t['id'],
            'text'               => $t['name'] . ' — NIP: ' . ($t['nip'] ?? '-') . ' (' . ($t['functional_position'] ?: 'Guru') . ')',
            'nip'                => $t['nip'] ?? '',
            'nik'                => $t['nik'] ?? '',
            'nuptk'              => $t['nuptk'] ?? '',
            'jabatan'            => $t['functional_position'] ?: 'Guru',
            'pangkat_golongan'   => $t['rank_grade'] ?? '',
            'status_kepegawaian' => $t['employment_status'] ?: 'Pegawai Tetap / PNS',
            'name'               => $t['name'],
            'phone'              => $t['phone'] ?? '',
            'address'            => $t['address'] ?? '',
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
                'header_style'          => $post['header_style'] ?? 'tengah',
                'body_html'             => isset($post['body_html']) ? strip_tags($post['body_html'], '<p><a><strong><em><u><s><br><table><thead><tbody><tr><td><th><ul><ol><li><h1><h2><h3><h4><h5><h6><span><div><hr><kbd><mark><sub><sup><del><ins>') : '',
                'hari_tanggal_kegiatan' => $post['hari_tanggal_kegiatan'] ?? '',
                'waktu_kegiatan'        => $post['waktu_kegiatan'] ?? '',
                'tempat_kegiatan'       => $post['tempat_kegiatan'] ?? '',
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
            'nisn'               => $post['nisn'] ?? null,
            'nis'                => $post['nis'] ?? null,
            'nik'                => $post['nik'] ?? null,
            'gender'             => $post['gender'] ?? null,
            'ttl'                => $post['ttl'] ?? null,
            'kelas'              => $post['kelas'] ?? null,
            'alamat'             => $post['alamat_domisili'] ?? $post['alamat'] ?? null,
            'nama_ayah'          => $post['nama_ayah'] ?? null,
            'pekerjaan_ayah'     => $post['pekerjaan_ayah'] ?? null,
            'penghasilan_ayah'   => $post['penghasilan_ayah'] ?? null,
            'nama_ibu'           => $post['nama_ibu'] ?? null,
            'pekerjaan_ibu'      => $post['pekerjaan_ibu'] ?? null,
            'nama_wali'          => $post['nama_wali'] ?? null,
            'alamat_ortu'        => $post['alamat_ortu'] ?? null,
            'nip'                => $post['nip'] ?? null,
            'nuptk'              => $post['nuptk'] ?? null,
            'jabatan'            => $post['jabatan'] ?? null,
            'pangkat_golongan'   => $post['pangkat_golongan'] ?? null,
            'status_kepegawaian' => $post['status_kepegawaian'] ?? null,
        ], fn($v) => $v !== null && $v !== '');
    }

    private function generateAndSavePdf(
        int $letterId,
        string $qrCodeId,
        ?array $school,
        SettingsModel $settings,
        bool $useSignature = false,
        bool $useSeal = false
    ): ?array
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

            // Robust principal name & NIP fallback for PDF generation
            $pInfo = $this->getPrincipalInfo($school, $settings);

            $pName = trim($letter['principal_name_snapshot'] ?? '');
            if ($pName === '' || $pName === '-') {
                $pName = $pInfo['name'];
            }
            $letter['principal_name_snapshot'] = $pName;

            $pNip = trim($letter['principal_nip_snapshot'] ?? '');
            if ($pNip === '' || $pNip === '-') {
                $pNip = $pInfo['nip'];
            }
            $letter['principal_nip_snapshot'] = $pNip;

            // Update database snapshot if previously empty or '-'
            $this->model->update($letterId, [
                'principal_name_snapshot' => $pName,
                'principal_nip_snapshot'  => $pNip,
            ]);

            $activeYear = $settings->getValue('active_school_year') ?? (date('Y') . '/' . (date('Y') + 1));

            $useKop = (int) ($letter['use_kop'] ?? 1);

            $viewData = [
                'letter'      => $letter,
                'school'      => $school,
                'qr_data_url' => $qrDataUrl,
                'active_year' => $activeYear,
                'use_kop'     => $useKop,
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

            // Gunakan PdfGenerator (inject KOP otomatis dari settings HANYA jika use_kop = 1)
            $pdfGen  = new PdfGenerator();
            $kopB64  = $pdfGen->getKopBase64();
            $viewData['kop_base64'] = $kopB64;

            $html = view($viewName, $viewData);

            // Inject KOP ke <body> HANYA jika use_kop bernilai 1
            if ($kopB64 && $useKop === 1) {
                $kopHtml = '<div style="width:100%;text-align:center;margin-bottom:4px;">'
                    . '<img src="' . $kopB64 . '" style="width:100%;height:auto;display:block;margin:0 auto;" />'
                    . '</div>'
                    . '<div style="border-top:2px solid #000;margin-top:5px;margin-bottom:15px;"></div>';
                $html = preg_replace('/(<body[^>]*>)/i', '$1' . $kopHtml, $html, 1);
            }

            // ── Inject Tanda Tangan KS jika diminta ──
            if ($useSignature) {
                $sigBase64 = \App\Helpers\SignatureHelper::getSignatureBase64();
                if ($sigBase64) {
                    $sigHtml = '<div class="signature-block" style="text-align:center;margin:2px 0;position:relative;z-index:1;">'
                        . '<img src="' . $sigBase64 . '" class="signature-img" style="width:180px;height:auto;max-height:70px;display:block;margin:0 auto;" />'
                        . '</div>';
                    $html = preg_replace('/<div[^>]*class=["\']signature-space["\'][^>]*>\s*<\/div>/i', $sigHtml, $html, 1);
                }
            }

            // ── Inject Cap / Stempel Sekolah jika diminta ──
            if ($useSeal) {
                $sealBase64 = \App\Helpers\SignatureHelper::getSealBase64();
                if ($sealBase64) {
                    $sealHtml = '<div class="seal-block" style="position:absolute;left:-25px;top:12px;z-index:10;pointer-events:none;">'
                        . '<img src="' . $sealBase64 . '" class="seal-img" style="width:3.8cm;height:3.8cm;max-width:3.8cm;max-height:3.8cm;display:block;" />'
                        . '</div>';
                    $html = preg_replace('/<div[^>]*class=["\']seal-space["\'][^>]*>\s*<\/div>/i', $sealHtml, $html, 1);
                }
            }

            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

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

            $dompdf = new \Dompdf\Dompdf($options);
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
        $recName = $letter['recipient_name'] ?? '-';
        if (str_contains($recName, '|')) {
            $parts   = array_map('trim', explode('|', $recName));
            $recName = implode(', ', array_filter($parts));
        }

        $placeholders = [
            '{nama_penerima}'       => $recName,
            '{nisn}'                => $recipientDetail['nisn'] ?? '-',
            '{nis}'                 => $recipientDetail['nis'] ?? '-',
            '{nik}'                 => $recipientDetail['nik'] ?? '-',
            '{jenis_kelamin}'       => $recipientDetail['gender'] ?? '-',
            '{kelas}'               => $recipientDetail['kelas'] ?? '-',
            '{ttl}'                 => $recipientDetail['ttl'] ?? '-',
            '{alamat}'              => $recipientDetail['alamat'] ?? '-',
            '{nama_ayah}'           => $recipientDetail['nama_ayah'] ?? '-',
            '{pekerjaan_ayah}'      => $recipientDetail['pekerjaan_ayah'] ?? '-',
            '{penghasilan_ayah}'    => $recipientDetail['penghasilan_ayah'] ?? '-',
            '{nama_ibu}'            => $recipientDetail['nama_ibu'] ?? '-',
            '{pekerjaan_ibu}'       => $recipientDetail['pekerjaan_ibu'] ?? '-',
            '{nama_wali}'           => $recipientDetail['nama_wali'] ?? '-',
            '{alamat_ortu}'         => $recipientDetail['alamat_ortu'] ?? ($recipientDetail['alamat'] ?? '-'),
            '{nip}'                 => $recipientDetail['nip'] ?? '-',
            '{nuptk}'               => $recipientDetail['nuptk'] ?? '-',
            '{jabatan}'             => $recipientDetail['jabatan'] ?? '-',
            '{pangkat_golongan}'    => $recipientDetail['pangkat_golongan'] ?? '-',
            '{status_kepegawaian}'  => $recipientDetail['status_kepegawaian'] ?? '-',
            '{nomor_surat}'         => $letter['letter_number'] ?? '-',
            '{tanggal_surat}'       => !empty($letter['issued_at']) ? date('d F Y', strtotime($letter['issued_at'])) : '-',
            '{tahun_pelajaran}'     => $activeYear,
            '{kepala_sekolah}'      => $letter['principal_name_snapshot'] ?? '-',
            '{nip_kepala_sekolah}'  => $letter['principal_nip_snapshot'] ?? '-',
            '{nama_sekolah}'        => $school['name'] ?? '-',
            '{alamat_sekolah}'      => $school['address'] ?? '-',
            '{hari_tanggal_kegiatan}' => $ld['hari_tanggal_kegiatan'] ?? ($ld['event_date'] ?? '-'),
            '{waktu_kegiatan}'        => $ld['waktu_kegiatan'] ?? ($ld['event_time'] ?? '-'),
            '{tempat_kegiatan}'       => $ld['tempat_kegiatan'] ?? ($ld['event_venue'] ?? '-'),
            '{sifat}'                 => $letter['sifat'] ?? 'Biasa',
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

        // Generate dynamic table for multi-recipients if {tabel_penerima} is used
        if (str_contains($bodyHtml, '{tabel_penerima}')) {
            $tabelHtml = '';
            if (!empty($letter['recipients']) && is_array($letter['recipients'])) {
                $tabelHtml = '<table class="tbl-bordered" style="width:100%; border-collapse:collapse; margin:10px 0; font-size:13px;">';
                $tabelHtml .= '<thead><tr><th style="width:30px; text-align:center;">No</th><th>Nama Lengkap</th><th>NISN / NIP</th><th>Kelas / Jabatan</th><th>Keterangan</th></tr></thead><tbody>';
                foreach ($letter['recipients'] as $idx => $r) {
                    $no = $idx + 1;
                    $rName = esc($r['name'] ?? '-');
                    $rCode = esc($r['nisn'] ?? $r['nip'] ?? '-');
                    $rRole = esc($r['kelas'] ?? $r['jabatan'] ?? '-');
                    $rKet  = esc($r['cabang'] ?? $r['keterangan'] ?? '-');
                    $tabelHtml .= "<tr><td style=\"text-align:center;\">{$no}</td><td>{$rName}</td><td>{$rCode}</td><td>{$rRole}</td><td>{$rKet}</td></tr>";
                }
                $tabelHtml .= '</tbody></table>';
            } elseif (!empty($letter['recipient_name']) && str_contains($letter['recipient_name'], '|')) {
                $names = array_filter(array_map('trim', explode('|', $letter['recipient_name'])));
                $tabelHtml = '<table class="tbl-bordered" style="width:100%; border-collapse:collapse; margin:10px 0; font-size:13px;">';
                $tabelHtml .= '<thead><tr><th style="width:30px; text-align:center;">No</th><th>Nama Penerima</th></tr></thead><tbody>';
                foreach ($names as $idx => $n) {
                    $no = $idx + 1;
                    $tabelHtml .= "<tr><td style=\"text-align:center;\">{$no}</td><td>" . esc($n) . "</td></tr>";
                }
                $tabelHtml .= '</tbody></table>';
            }
            $bodyHtml = str_replace('{tabel_penerima}', $tabelHtml, $bodyHtml);
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

        $pInfo         = $this->getPrincipalInfo($school, $settings);
        $principalName = $pInfo['name'];
        $principalNip  = $pInfo['nip'];
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

        $pInfo         = $this->getPrincipalInfo($school, $settings);
        $principalName = $pInfo['name'];
        $principalNip  = $pInfo['nip'];

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

        $school   = (new SchoolModel())->first();
        $settings = new SettingsModel();

        $withSignatureParam = $this->request->getGet('with_signature');
        $withSealParam      = $this->request->getGet('with_seal');

        // Jika parameter query disediakan secara eksplisit, SELALU regenerate sesuai pilihan
        if ($withSignatureParam !== null || $withSealParam !== null) {
            $useSignature = $withSignatureParam === '1';
            $useSeal      = $withSealParam === '1';
            $result       = $this->generateAndSavePdf($id, $letter['qr_code_id'], $school, $settings, $useSignature, $useSeal);
        } elseif (! empty($letter['pdf_path']) && file_exists(WRITEPATH . 'uploads/' . $letter['pdf_path'])) {
            $path = WRITEPATH . 'uploads/' . $letter['pdf_path'];
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="surat_' . $letter['id'] . '.pdf"');
            readfile($path);
            exit;
        } else {
            $useSignature = (bool) $settings->getValue('use_signature');
            $useSeal      = (bool) $settings->getValue('use_seal');
            $result       = $this->generateAndSavePdf($id, $letter['qr_code_id'], $school, $settings, $useSignature, $useSeal);
        }

        if ($result) {
            $this->model->update($id, ['pdf_path' => $result['path'], 'pdf_url' => $result['url'], 'file_size_bytes' => $result['size']]);
            $path = WRITEPATH . 'uploads/' . $result['path'];
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
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

    private function getPrincipalInfo(?array $school, SettingsModel $settings): array
    {
        $pName = trim($settings->getValue('principal_name') ?? '');
        if ($pName === '' || $pName === '-') {
            $pName = trim($school['headmaster'] ?? '');
        }
        if ($pName === '' || $pName === '-') {
            $pName = trim($school['principal_name'] ?? '');
        }
        if ($pName === '' || $pName === '-') {
            $kepsek = (new TeacherModel())->like('functional_position', 'Kepala Sekolah')->first();
            if ($kepsek) {
                $pName = trim($kepsek['name']);
            }
        }

        $pNip = trim($settings->getValue('principal_nip') ?? '');
        if ($pNip === '' || $pNip === '-') {
            $pNip = trim($school['principal_nip'] ?? '');
        }
        if ($pNip === '' || $pNip === '-') {
            if (!isset($kepsek)) {
                $kepsek = (new TeacherModel())->like('functional_position', 'Kepala Sekolah')->first();
            }
            if ($kepsek && !empty($kepsek['nip'])) {
                $pNip = trim($kepsek['nip']);
            }
        }

        return [
            'name' => ($pName !== '' && $pName !== '-') ? $pName : '-',
            'nip'  => ($pNip !== '' && $pNip !== '-') ? $pNip : '-',
        ];
    }
}
