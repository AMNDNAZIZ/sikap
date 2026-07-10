<?php

namespace App\Models;

use CodeIgniter\Model;

class LicenseModel extends Model
{
    protected $table = 'app_license';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['license_key', 'domain', 'machine_id', 'hardware_signature', 'status', 'last_check', 'expires_at', 'hash'];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Encrypt field before saving to database
     */
    protected function encryptField($value)
    {
        if (empty($value)) {
            return $value;
        }

        try {
            return \App\Libraries\LicenseGuard::encrypt($value);
        } catch (\Exception $e) {
            log_message('error', 'Failed to encrypt license field: ' . $e->getMessage());
            return $value; // Fallback to plain text
        }
    }

    /**
     * Decrypt field after reading from database
     */
    protected function decryptField($value)
    {
        if (empty($value)) {
            return $value;
        }

        // Check if value is encrypted (base64 encoded)
        if (base64_encode(base64_decode($value, true)) === $value) {
            try {
                return \App\Libraries\LicenseGuard::decrypt($value);
            } catch (\Exception $e) {
                log_message('error', 'Failed to decrypt license field: ' . $e->getMessage());
                return $value; // Fallback to plain text
            }
        }

        return $value; // Already plain text
    }

    /**
     * Override insert to encrypt sensitive fields
     */
    public function insert($data = null, bool $returnID = true)
    {
        if (isset($data['license_key'])) {
            $data['license_key'] = $this->encryptField($data['license_key']);
        }
        if (isset($data['machine_id'])) {
            $data['machine_id'] = $this->encryptField($data['machine_id']);
        }

        return parent::insert($data, $returnID);
    }

    /**
     * Override update to encrypt sensitive fields
     */
    public function update($id = null, $data = null): bool
    {
        if (isset($data['license_key'])) {
            $data['license_key'] = $this->encryptField($data['license_key']);
        }
        if (isset($data['machine_id'])) {
            $data['machine_id'] = $this->encryptField($data['machine_id']);
        }

        return parent::update($id, $data);
    }

    /**
     * Override find to decrypt sensitive fields
     */
    public function find($id = null)
    {
        $result = parent::find($id);

        if ($result && is_array($result)) {
            if (isset($result['license_key'])) {
                $result['license_key'] = $this->decryptField($result['license_key']);
            }
            if (isset($result['machine_id'])) {
                $result['machine_id'] = $this->decryptField($result['machine_id']);
            }
        }

        return $result;
    }

    /**
     * Override first to decrypt sensitive fields
     */
    public function first()
    {
        $result = parent::first();

        if ($result && is_array($result)) {
            if (isset($result['license_key'])) {
                $result['license_key'] = $this->decryptField($result['license_key']);
            }
            if (isset($result['machine_id'])) {
                $result['machine_id'] = $this->decryptField($result['machine_id']);
            }
        }

        return $result;
    }

    public function getActiveLicense()
    {
        // Gunakan parent::first() langsung untuk menghindari double-decrypt
        // karena first() sudah di-override dan melakukan decrypt
        $result = $this->where('status', 'active')->first();

        // first() sudah melakukan decrypt, tidak perlu decrypt lagi
        return $result;
    }

    /**
     * Centralized license validation logic
     * @param array $license
     * @param string|null &$error Output variable for error message
     * @return bool
     */
    public function isValidLicense($license, &$error = null)
    {
        if (!$license || $license['status'] !== 'active') {
            $error = 'Aplikasi belum diaktivasi.';
            return false;
        }

        // Check Expiry — bandingkan langsung tanpa konversi timezone
        // strtotime() pada string datetime tanpa timezone menggunakan timezone PHP default
        if ($license['expires_at']) {
            $expiresTs = strtotime($license['expires_at']);
            if ($expiresTs !== false && $expiresTs < time()) {
                $error = 'Lisensi Anda telah kedaluwarsa. Silakan lakukan aktivasi ulang.';
                return false;
            }
        }

        // Hash Integrity Verification
        $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
        $expectedHash = hash('sha256', $license['license_key'] . $license['expires_at'] . $license['machine_id'] . $hashSecret);

        if ($license['hash'] !== $expectedHash) {
            // Log detail untuk debugging — jangan expose ke user
            log_message('debug', 'License hash mismatch. expected=' . substr($expectedHash, 0, 16) . ' stored=' . substr($license['hash'], 0, 16));
            $error = 'Data lisensi tidak valid (integrity mismatch). Silakan aktivasi ulang.';
            return false;
        }

        return true;
    }
}
