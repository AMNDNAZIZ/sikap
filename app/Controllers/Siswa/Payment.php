<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\SettingsModel;
use App\Models\StudentModel;

class Payment extends BaseController
{
    /**
     * Halaman informasi pembayaran siswa.
     * Data diambil dari aplikasi web pembayaran eksternal via API.
     */
    public function index()
    {
        $user = session()->get('user');
        $relatedId = $user['related_id'] ?? null;

        // Ambil data siswa berdasarkan related_id atau user_id
        $studentModel = new StudentModel();
        $student = null;
        if (!empty($relatedId)) {
            $student = $studentModel->find($relatedId);
        }
        if (!$student && !empty($user['id'])) {
            $student = $studentModel->where('user_id', $user['id'])->first();
        }

        if (!$student || empty($student['nis'])) {
            return redirect()->to('/dashboard')->with('error', 'Data siswa atau NIS tidak ditemukan. Hubungi admin sekolah.');
        }

        $nis = $student['nis'];

        // Ambil konfigurasi API pembayaran dari settings
        $settings = new SettingsModel();
        $apiUrl = trim((string) $settings->getValue('payment_api_url'));
        $apiKey = trim((string) $settings->getValue('payment_api_key'));

        if (empty($apiUrl)) {
            return view('siswa/payment/index', [
                'title'   => 'Informasi Pembayaran',
                'student' => $student,
                'error'   => 'URL integrasi pembayaran belum dikonfigurasi. Hubungi admin sekolah.',
                'data'    => null,
            ]);
        }

        if (empty($apiKey)) {
            return view('siswa/payment/index', [
                'title'   => 'Informasi Pembayaran',
                'student' => $student,
                'error'   => 'API Key pembayaran belum dikonfigurasi di pengaturan sistem. Hubungi admin sekolah untuk mengaturnya di menu Pengaturan Integrasi Pembayaran.',
                'data'    => null,
            ]);
        }

        // Panggil API pembayaran
        $paymentData = $this->fetchPaymentData($apiUrl, $apiKey, $nis);

        return view('siswa/payment/index', [
            'title'   => 'Informasi Pembayaran',
            'student' => $student,
            'data'    => $paymentData['data'] ?? null,
            'error'   => $paymentData['error'] ?? null,
        ]);
    }

    /**
     * Ambil data pembayaran dari API eksternal
     */
    private function fetchPaymentData(string $apiUrl, ?string $apiKey, string $nis): array
    {
        // Bangun URL endpoint
        $url = rtrim($apiUrl, '/') . '/api/payment-info?nis=' . urlencode(trim($nis));

        try {
            $client = \Config\Services::curlrequest();
            $headers = [
                'Accept' => 'application/json',
            ];

            if (!empty($apiKey)) {
                $headers['X-API-Key'] = trim($apiKey);
            }

            $response = $client->get($url, [
                'headers'     => $headers,
                'timeout'     => 12,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $body       = json_decode($rawBody, true);

            if ($statusCode === 200) {
                if ($body && isset($body['success']) && $body['success']) {
                    return ['data' => $body['data'] ?? $body, 'error' => null];
                }
                return ['data' => null, 'error' => $body['message'] ?? $body['messages']['error'] ?? 'Data pembayaran tidak valid dari server.'];
            } elseif ($statusCode === 401) {
                $errMsg = $body['messages']['error'] ?? $body['message'] ?? 'API Key tidak valid atau belum dikonfigurasi.';
                return ['data' => null, 'error' => 'Autentikasi gagal (HTTP 401): ' . $errMsg . ' Pastikan Secret API Key di Pengaturan ESPATA sama dengan di aplikasi pembayaran.'];
            } elseif ($statusCode === 404) {
                $errMsg = $body['messages']['error'] ?? $body['message'] ?? ('Data pembayaran untuk NIS ' . $nis . ' tidak ditemukan.');
                return ['data' => null, 'error' => $errMsg];
            } else {
                $errMsg = $body['messages']['error'] ?? $body['message'] ?? ('HTTP ' . $statusCode);
                return ['data' => null, 'error' => 'Gagal menghubungi server pembayaran (' . $errMsg . ').'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Payment API Error: ' . $e->getMessage());
            return ['data' => null, 'error' => 'Tidak dapat terhubung ke server pembayaran: ' . $e->getMessage()];
        }
    }
}
