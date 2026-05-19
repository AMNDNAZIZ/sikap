<?php

namespace App\Models;

use CodeIgniter\Model;

class QrVerificationModel extends Model
{
    protected $table         = 'qr_verifications';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'qr_code_id',
        'letter_id',
        'verified_at',
        'ip_address',
        'user_agent',
        'result',
    ];

    /**
     * Log a QR verification access
     */
    public function logVerification(string $qrCodeId, ?int $letterId, string $result): void
    {
        $this->insert([
            'qr_code_id'  => $qrCodeId,
            'letter_id'   => $letterId,
            'verified_at' => date('Y-m-d H:i:s'),
            'ip_address'  => service('request')->getIPAddress(),
            'user_agent'  => service('request')->getUserAgent()->getAgentString(),
            'result'      => $result,
        ]);
    }
}
