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
        $roleId = $user['role_id'] ?? null;
        $relatedId = $user['related_id'] ?? null;

        if (!$relatedId) {
            return redirect()->to('/dashboard')->with('error', 'Data siswa tidak ditemukan.');
        }

        // Ambil NIS siswa
        $studentModel = new StudentModel();
        $student = $studentModel->find($relatedId);

        if (!$student || empty($student['nis'])) {
            return redirect()->to('/dashboard')->with('error', 'NIS siswa tidak ditemukan. Hubungi admin.');
        }

        $nis = $student['nis'];

        // Ambil konfigurasi API pembayaran dari settings
        $settings = new SettingsModel();
        $apiUrl = $settings->getValue('payment_api_url');
        $apiKey = $settings->getValue('payment_api_key');

        if (empty($apiUrl)) {
            return view('siswa/payment/index', [
                'title'   => 'Informasi Pembayaran',
                'student' => $student,
                'error'   => 'Fitur pembayaran belum dikonfigurasi. Hubungi admin sekolah.',
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
        $url = rtrim($apiUrl, '/') . '/api/payment-info?nis=' . urlencode($nis);

        try {
            $client = \Config\Services::curlrequest();
            $headers = ['Accept' => 'application/json'];

            if (!empty($apiKey)) {
                $headers['X-API-Key'] = $apiKey;
            }

            $response = $client->get($url, [
                'headers'     => $headers,
                'timeout'     => 10,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $body = json_decode($response->getBody(), true);
                if ($body && isset($body['success']) && $body['success']) {
                    return ['data' => $body['data'] ?? $body, 'error' => null];
                }
                return ['data' => null, 'error' => $body['message'] ?? 'Data tidak ditemukan di sistem pembayaran.'];
            } elseif ($statusCode === 404) {
                return ['data' => null, 'error' => 'Data pembayaran untuk NIS ini tidak ditemukan.'];
            } else {
                return ['data' => null, 'error' => 'Gagal menghubungi server pembayaran (HTTP ' . $statusCode . ').'];
            }
        } catch (\Exception $e) {
            log_message('error', 'Payment API Error: ' . $e->getMessage());
            return ['data' => null, 'error' => 'Tidak dapat terhubung ke server pembayaran. Silakan coba lagi nanti.'];
        }
    }
}
