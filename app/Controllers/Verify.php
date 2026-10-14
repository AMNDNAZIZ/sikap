<?php

namespace App\Controllers;

use App\Models\OutgoingLetterModel;
use App\Models\QrVerificationModel;
use App\Models\SchoolModel;

/**
 * Verify — Halaman publik verifikasi keaslian surat via QR Code
 * Accessible WITHOUT login: /verify/{qr_code_id} atau /verify/cek
 */
class Verify extends BaseController
{
    public function index(string $qrCodeId = '')
    {
        $schoolModel = new SchoolModel();
        $school      = $schoolModel->first() ?: [];

        $qrCodeId = trim($qrCodeId);

        // Jika rute /verify atau /verify/cek tanpa parameter kode spesifik
        if (empty($qrCodeId) || strtolower($qrCodeId) === 'cek') {
            return view('verify/index', [
                'title'     => 'Verifikasi Keaslian Dokumen & Surat',
                'status'    => 'cek',
                'letter'    => null,
                'qrCodeId'  => '',
                'school'    => $school,
            ]);
        }

        $model       = new OutgoingLetterModel();
        $qrModel     = new QrVerificationModel();

        // Validasi format UUID
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $letter      = null;

        if (preg_match($uuidPattern, $qrCodeId)) {
            $letter = $model->findByQrCode($qrCodeId);
        } else {
            // Dukung pencarian alternatif dengan Nomor Surat jika user memasukkan nomor surat
            $letter = $model->where('letter_number', $qrCodeId)->first();
            if ($letter && !empty($letter['qr_code_id'])) {
                $qrCodeId = $letter['qr_code_id'];
            }
        }

        // Jika bukan UUID dan tidak ditemukan via nomor surat
        if (! $letter && ! preg_match($uuidPattern, $qrCodeId)) {
            return view('verify/index', [
                'title'     => 'Verifikasi Surat — Format Tidak Valid',
                'status'    => 'invalid',
                'letter'    => null,
                'qrCodeId'  => $qrCodeId,
                'school'    => $school,
            ]);
        }

        // Log akses verifikasi jika format UUID valid
        if (preg_match($uuidPattern, $qrCodeId)) {
            $qrModel->logVerification(
                $qrCodeId,
                $letter ? (int) $letter['id'] : null,
                $letter ? ($letter['status'] === 'active' ? 'valid' : 'revoked') : 'not_found'
            );
        }

        if (! $letter) {
            return view('verify/index', [
                'title'    => 'Verifikasi Surat — Tidak Ditemukan',
                'status'   => 'not_found',
                'letter'   => null,
                'qrCodeId' => $qrCodeId,
                'school'   => $school,
            ]);
        }

        $status = ($letter['status'] ?? 'active') === 'active' ? 'valid' : 'revoked';

        return view('verify/index', [
            'title'    => 'Verifikasi Surat — ' . strtoupper($status),
            'status'   => $status,
            'letter'   => $letter,
            'qrCodeId' => $qrCodeId,
            'school'   => $school,
        ]);
    }

    /**
     * Proses input pencarian manual dari form /verify/cek
     */
    public function check()
    {
        $code = trim((string) ($this->request->getPost('qr_code_id') ?: $this->request->getPost('code')));
        
        if (empty($code)) {
            return redirect()->to(base_url('verify/cek'))->with('error', 'Silakan masukkan ID Dokumen atau Nomor Surat.');
        }

        // Jika input berupa full URL (misal hasil scan QR: https://.../verify/UUID)
        if (preg_match('/verify\/([0-9a-fA-F\-]+)/i', $code, $matches)) {
            $code = $matches[1];
        }

        return redirect()->to(base_url('verify/' . rawurlencode($code)));
    }
}
