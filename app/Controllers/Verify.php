<?php

namespace App\Controllers;

use App\Models\OutgoingLetterModel;
use App\Models\QrVerificationModel;

/**
 * Verify — Halaman publik verifikasi keaslian surat via QR Code
 * Accessible WITHOUT login: /verify/{qr_code_id}
 */
class Verify extends BaseController
{
    public function index(string $qrCodeId = '')
    {
        if (empty($qrCodeId)) {
            return redirect()->to(base_url('/'));
        }

        // Validasi format UUID sederhana
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        if (! preg_match($uuidPattern, $qrCodeId)) {
            return view('verify/index', [
                'title'     => 'Verifikasi Surat',
                'status'    => 'invalid',
                'letter'    => null,
                'qrCodeId'  => $qrCodeId,
            ]);
        }

        $model       = new OutgoingLetterModel();
        $qrModel     = new QrVerificationModel();

        $letter = $model->findByQrCode($qrCodeId);

        // Log akses verifikasi
        $qrModel->logVerification(
            $qrCodeId,
            $letter ? (int) $letter['id'] : null,
            $letter ? ($letter['status'] === 'active' ? 'valid' : 'revoked') : 'not_found'
        );

        if (! $letter) {
            return view('verify/index', [
                'title'    => 'Verifikasi Surat — Tidak Ditemukan',
                'status'   => 'not_found',
                'letter'   => null,
                'qrCodeId' => $qrCodeId,
            ]);
        }

        $status = $letter['status'] === 'active' ? 'valid' : 'revoked';

        return view('verify/index', [
            'title'    => 'Verifikasi Surat — ' . strtoupper($status),
            'status'   => $status,
            'letter'   => $letter,
            'qrCodeId' => $qrCodeId,
        ]);
    }
}
