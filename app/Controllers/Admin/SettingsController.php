<?php
// 🎯 Standar Skill Diterapkan: api-design-quality-review (Backend)
// Refs: rekap-file-md/TandaTanganCap/Implementation-Plan-TandaTanganCap.md

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingsModel;

class SettingsController extends BaseController
{
    protected $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
    }

    public function kopSurat()
    {
        $year = (int) date('Y');
        $db   = \Config\Database::connect();
        $seqRow = $db->table('letter_number_sequences')->where('year', $year)->get()->getRowArray();
        $maxRow = $db->table('outgoing_letters')
                     ->selectMax('sequence_number')
                     ->where('YEAR(issued_at)', $year)
                     ->get()->getRowArray();
        $curLastSeq = max((int)($seqRow['last_sequence'] ?? 0), (int)($maxRow['sequence_number'] ?? 0));

        $data = [
            'title'                       => 'Pengaturan Persuratan & Kop Surat',
            'kop_surat'                   => $this->settingsModel->getValue('kop_surat'),
            'current_year'                => $year,
            'last_sequence'               => $curLastSeq,
            'letter_number_padding'       => (int) ($this->settingsModel->getValue('letter_number_padding') ?? 3),
            'default_classification_code' => $this->settingsModel->getValue('default_classification_code') ?? 'PK.01.01',
            // Tanda Tangan & Cap Sekolah
            'ks_signature_path'           => $this->settingsModel->getValue('ks_signature_path'),
            'school_seal_path'            => $this->settingsModel->getValue('school_seal_path'),
            'use_signature'               => (bool) $this->settingsModel->getValue('use_signature'),
            'use_seal'                    => (bool) $this->settingsModel->getValue('use_seal'),
        ];

        return view('admin/settings/kop_surat', $data);
    }

    /**
     * Simpan urutan penomoran surat keluar (auto increment)
     */
    public function saveNomorSurat()
    {
        $year         = (int) ($this->request->getPost('year') ?? date('Y'));
        $lastSequence = max(0, (int) ($this->request->getPost('last_sequence') ?? 0));
        $padding      = max(1, min(6, (int) ($this->request->getPost('letter_number_padding') ?? 3)));
        $defaultCode  = trim((string)$this->request->getPost('default_classification_code') ?? 'PK.01.01');

        $db = \Config\Database::connect();
        $db->query("INSERT INTO letter_number_sequences (year, last_sequence, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        last_sequence = ?,
                        updated_at    = NOW()", [$year, $lastSequence, $lastSequence]);

        $this->settingsModel->setValue('letter_number_padding', (string) $padding);
        $this->settingsModel->setValue('default_classification_code', $defaultCode ?: 'PK.01.01');

        $nextNum = \App\Models\OutgoingLetterModel::buildLetterNumber($lastSequence + 1, $year, $padding, $defaultCode);

        return redirect()->back()->with('success', "Penomoran surat berhasil diperbarui. Nomor urut terakhir tahun {$year} diset ke {$lastSequence}. Surat berikutnya akan otomatis bernomor <strong>{$nextNum}</strong>.");
    }

    public function uploadKopSurat()
    {
        $file = $this->request->getFile('kop_file');

        if (!$file->isValid()) {
            return redirect()->back()->with('error', $file->getErrorString());
        }

        $rules = [
            'kop_file' => 'uploaded[kop_file]|is_image[kop_file]|mime_in[kop_file,image/png,image/jpeg,image/jpg]|max_size[kop_file,5120]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'File tidak valid. Pastikan format PNG/JPG dan ukuran maksimal 5MB.');
        }

        $targetDir  = FCPATH . 'uploads/settings/';
        $targetName = 'kop_surat_active.' . $file->getExtension();

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if ($file->move($targetDir, $targetName, true)) {
            $relativePath = 'uploads/settings/' . $targetName;
            $this->settingsModel->setValue('kop_surat', $relativePath);
            return redirect()->back()->with('success', 'Kop surat berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah file.');
    }

    /**
     * Upload Tanda Tangan Kepala Sekolah (PNG transparan, maks 150KB)
     */
    public function uploadSignature()
    {
        $file = $this->request->getFile('signature_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tanda tangan tidak valid.');
        }

        $rules = [
            'signature_file' => 'uploaded[signature_file]|mime_in[signature_file,image/png]|max_size[signature_file,150]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'File tidak valid. Wajib format PNG transparan, maksimal 150 KB.');
        }

        $targetDir  = FCPATH . 'uploads/signature/';
        $targetName = 'ks_signature_active.png';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if ($file->move($targetDir, $targetName, true)) {
            $this->settingsModel->setValue('ks_signature_path', 'uploads/signature/' . $targetName);
            return redirect()->back()->with('success', 'Tanda tangan Kepala Sekolah berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah tanda tangan.');
    }

    /**
     * Upload Cap Sekolah (PNG transparan, maks 500KB)
     */
    public function uploadSeal()
    {
        $file = $this->request->getFile('seal_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File cap sekolah tidak valid.');
        }

        $rules = [
            'seal_file' => 'uploaded[seal_file]|mime_in[seal_file,image/png]|max_size[seal_file,500]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'File tidak valid. Wajib format PNG transparan, maksimal 500 KB.');
        }

        $targetDir  = FCPATH . 'uploads/signature/';
        $targetName = 'school_seal_active.png';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if ($file->move($targetDir, $targetName, true)) {
            $this->settingsModel->setValue('school_seal_path', 'uploads/signature/' . $targetName);
            return redirect()->back()->with('success', 'Cap sekolah berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah cap sekolah.');
    }

    /**
     * Simpan preferensi penggunaan tanda tangan & cap (checkbox)
     */
    public function saveSignaturePrefs()
    {
        $useSignature = $this->request->getPost('use_signature') === '1' ? '1' : '0';
        $useSeal      = $this->request->getPost('use_seal')      === '1' ? '1' : '0';

        $this->settingsModel->setValue('use_signature', $useSignature);
        $this->settingsModel->setValue('use_seal', $useSeal);

        return redirect()->back()->with('success', 'Preferensi tanda tangan & cap berhasil disimpan.');
    }

    /**
     * Halaman pengaturan integrasi pembayaran
     */
    public function payment()
    {
        $data = [
            'title'           => 'Integrasi Pembayaran',
            'payment_api_url' => $this->settingsModel->getValue('payment_api_url') ?? '',
            'payment_api_key' => $this->settingsModel->getValue('payment_api_key') ?? '',
        ];

        return view('admin/settings/payment', $data);
    }

    /**
     * Simpan pengaturan integrasi pembayaran
     */
    public function savePayment()
    {
        $apiUrl = trim((string)$this->request->getPost('payment_api_url'));
        $apiKey = trim((string)$this->request->getPost('payment_api_key'));

        $this->settingsModel->setValue('payment_api_url', $apiUrl);
        $this->settingsModel->setValue('payment_api_key', $apiKey);

        if (!empty($apiUrl) && empty($apiKey)) {
            return redirect()->back()->with('warning', 'Pengaturan disimpan, tetapi API Key masih kosong. Aplikasi pembayaran kini mewajibkan API Key agar data siswa dapat diakses.');
        }

        return redirect()->back()->with('success', 'Pengaturan integrasi pembayaran berhasil disimpan.');
    }

    /**
     * Uji koneksi ke API pembayaran
     */
    public function testPayment()
    {
        $apiUrl = trim((string)($this->request->getPost('payment_api_url') ?: $this->settingsModel->getValue('payment_api_url')));
        $apiKey = trim((string)($this->request->getPost('payment_api_key') ?: $this->settingsModel->getValue('payment_api_key')));

        if (empty($apiUrl)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'URL Aplikasi Pembayaran belum diisi.'
            ]);
        }

        if (empty($apiKey)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API Key wajib diisi untuk otentikasi ke aplikasi pembayaran.'
            ]);
        }

        // Uji koneksi ke endpoint /api/payment-info
        $testUrl = rtrim($apiUrl, '/') . '/api/payment-info?test=1';

        try {
            $client = \Config\Services::curlrequest();
            $response = $client->get($testUrl, [
                'headers' => [
                    'Accept'    => 'application/json',
                    'X-API-Key' => $apiKey,
                ],
                'timeout'     => 10,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $body       = json_decode($rawBody, true);

            if ($statusCode === 401) {
                $err = $body['messages']['error'] ?? $body['message'] ?? 'API Key tidak valid atau belum dikonfigurasi di aplikasi pembayaran.';
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Otentikasi Gagal (HTTP 401): ' . $err . ' Pastikan API Key sama persis dengan yang ada di menu Pengaturan Sekolah aplikasi pembayaran.'
                ]);
            }

            // HTTP 400 (Parameter nis diperlukan) atau 200 atau 404 menandakan API Key VALID dan endpoint merespons!
            if ($statusCode === 400 || $statusCode === 200 || $statusCode === 404) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Koneksi Berhasil! API Key valid dan ESPATA berhasil terhubung dengan sistem pembayaran.'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => "Menerima respons HTTP $statusCode dari server pembayaran: " . substr(strip_tags($rawBody), 0, 150)
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal terhubung ke URL pembayaran: ' . $e->getMessage()
            ]);
        }
    }
}

