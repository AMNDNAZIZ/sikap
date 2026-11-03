<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedCbtDefaultAuditKeyV3 extends Migration
{
    public function up()
    {
        $db = $this->db;

        // 1. Ensure cbt_keys_v3 has default HMAC key to satisfy fk_at_key FK constraint
        if ($db->tableExists('cbt_keys_v3')) {
            $keyId = 'KEY-HMAC-V1';
            $exists = $db->table('cbt_keys_v3')->where('key_id', $keyId)->countAllResults();
            if ($exists === 0) {
                $maxVer = $db->table('cbt_keys_v3')
                    ->where('key_purpose', 'AUDIT_SIGNING')
                    ->selectMax('key_version', 'max_v')
                    ->get()
                    ->getRowArray();
                $nextVer = !empty($maxVer['max_v']) ? ((int)$maxVer['max_v'] + 1) : 1;

                $db->table('cbt_keys_v3')->ignore(true)->insert([
                    'key_id'              => $keyId,
                    'key_purpose'         => 'AUDIT_SIGNING',
                    'key_version'         => $nextVer,
                    'encrypted_key_bytes' => hex2bin(str_repeat('0', 64)),
                    'iv_bytes'            => hex2bin(str_repeat('0', 32)),
                    'auth_tag'            => hex2bin(str_repeat('0', 32)),
                    'status'              => 'ACTIVE',
                    'activated_at'        => date('Y-m-d H:i:s'),
                    'created_at'          => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 2. Ensure cbt_audit_head_v3 singleton row exists with valid active_key_id
        if ($db->tableExists('cbt_audit_head_v3') && $db->tableExists('cbt_keys_v3')) {
            $head = $db->table('cbt_audit_head_v3')->where('id', 1)->get()->getRowArray();
            if (!$head) {
                $key = $db->table('cbt_keys_v3')->where('status', 'ACTIVE')->orderBy('key_version', 'DESC')->get()->getRowArray();
                $activeKeyId = $key['key_id'] ?? 'KEY-HMAC-V1';

                $db->table('cbt_audit_head_v3')->ignore(true)->insert([
                    'id'               => 1,
                    'last_seq_no'      => 0,
                    'last_record_hash' => '0000000000000000000000000000000000000000000000000000000000000000',
                    'active_key_id'    => $activeKeyId,
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        // Safe no-op down to preserve cryptographic audit trail keys
    }
}

