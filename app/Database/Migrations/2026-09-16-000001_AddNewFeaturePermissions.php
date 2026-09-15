<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan permission untuk fitur-fitur baru ke tabel `permissions`
 * dan menetapkannya ke role default (Guru=3, Kepala Sekolah=2), agar
 * halaman /admin/role-permission menampilkan opsi baru dan role yang
 * relevan langsung mendapat akses.
 *
 * Fitur baru: Kuis Mandiri (quiz), Forum Diskusi (forum), Analitik
 * Pembelajaran (learning_analytics), Soal AI (soal_ai), Konversi Nilai
 * CBT (cbt.convert).
 *
 * Idempotent: cek keberadaan sebelum insert. Aman dijalankan berulang
 * dan otomatis saat update online (migration).
 */
class AddNewFeaturePermissions extends Migration
{
    public function up()
    {
        $permissions = [
            ['module' => 'quiz',               'action' => 'manage', 'label' => 'Kelola Kuis Mandiri (LMS)',       'group_name' => 'Akademik',  'sort_order' => 38],
            ['module' => 'forum',              'action' => 'manage', 'label' => 'Kelola Forum Diskusi (LMS)',      'group_name' => 'Akademik',  'sort_order' => 39],
            ['module' => 'learning_analytics', 'action' => 'view',   'label' => 'Analitik Pembelajaran',           'group_name' => 'Akademik',  'sort_order' => 40],
            ['module' => 'soal_ai',            'action' => 'manage', 'label' => 'Pembuatan Soal dengan AI',        'group_name' => 'Kurikulum', 'sort_order' => 45],
            ['module' => 'cbt',                'action' => 'convert','label' => 'Konversi Nilai CBT ke Rapor',     'group_name' => 'CBT',       'sort_order' => 49],
        ];

        $newIds = [];
        foreach ($permissions as $perm) {
            $existing = $this->db->table('permissions')
                ->where('module', $perm['module'])
                ->where('action', $perm['action'])
                ->get()->getRowArray();

            if ($existing) {
                $newIds[] = (int) $existing['id'];
            } else {
                $this->db->table('permissions')->insert($perm);
                $newIds[] = (int) $this->db->insertID();
            }
        }

        // Tetapkan ke role default: Guru (3), Kepala Sekolah (2), Admin (1).
        // (Admin sebenarnya selalu full-access via helper, tapi tetap dicatat.)
        $roleTargets = [1, 2, 3];
        foreach ($roleTargets as $roleId) {
            foreach ($newIds as $permId) {
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permId)
                    ->get()->getRowArray();
                if (!$exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $keys = [
            ['quiz', 'manage'],
            ['forum', 'manage'],
            ['learning_analytics', 'view'],
            ['soal_ai', 'manage'],
            ['cbt', 'convert'],
        ];
        foreach ($keys as [$module, $action]) {
            $row = $this->db->table('permissions')
                ->where('module', $module)->where('action', $action)
                ->get()->getRowArray();
            if ($row) {
                $this->db->table('role_permissions')->where('permission_id', $row['id'])->delete();
                $this->db->table('permissions')->where('id', $row['id'])->delete();
            }
        }
    }
}
