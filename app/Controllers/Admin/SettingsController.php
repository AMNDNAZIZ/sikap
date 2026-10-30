<?php

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
        $data = [
            'title' => 'Pengaturan Kop Surat',
            'kop_surat' => $this->settingsModel->getValue('kop_surat')
        ];

        return view('admin/settings/kop_surat', $data);
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

