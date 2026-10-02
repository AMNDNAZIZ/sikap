<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan permission modul BP/BK ke tabel permissions dan menetapkannya
 * ke role default yang relevan.
 *
 * Pertimbangan SD:
 *   Di SD tidak ada Guru BK khusus — fungsi BK dirangkap guru kelas (role 3).
 *   Oleh karena itu bk.manage & bk.case_manage diberikan ke Guru (role 3) agar
 *   seluruh guru kelas dapat mengakses modul BK sesuai kewenangannya.
 *   Kepala Sekolah (role 2) mendapat akses lihat + laporan eksekutif saja,
 *   bukan akses kelola/penanganan kasus.
 *
 * Permission granular yang ditambahkan (group_name = 'BK'):
 *   bk.view             — Lihat dashboard BK & profil siswa (semua role terkait)
 *   bk.manage           — Kelola program, RPL, layanan, pemetaan (guru/admin)
 *   bk.case_manage      — Penanganan kasus sensitif (guru/admin)
 *   bk.report           — Cetak laporan rekap layanan & kasus
 *   bk.report_executive — Laporan eksekutif Kepala Sekolah
 */
class AddBkPermissions extends Migration
{
    public function up()
    {
        $perms = [
            ['module' => 'bk', 'action' => 'view',             'label' => 'Lihat Data BP/BK (Dashboard & Profil Siswa)',    'group_name' => 'BK', 'sort_order' => 70],
            ['module' => 'bk', 'action' => 'manage',           'label' => 'Kelola BP/BK (Program, RPL, Layanan, Pemetaan)', 'group_name' => 'BK', 'sort_order' => 71],
            ['module' => 'bk', 'action' => 'case_manage',      'label' => 'Penanganan Kasus BK (data sensitif)',            'group_name' => 'BK', 'sort_order' => 72],
            ['module' => 'bk', 'action' => 'report',           'label' => 'Cetak Laporan Rekap BK (Layanan & Kasus)',       'group_name' => 'BK', 'sort_order' => 73],
            ['module' => 'bk', 'action' => 'report_executive', 'label' => 'Laporan Eksekutif BK (Kepala Sekolah)',          'group_name' => 'BK', 'sort_order' => 74],
        ];

        $newIds = [];
        foreach ($perms as $p) {
            $existing = $this->db->table('permissions')
                ->where('module', $p['module'])->where('action', $p['action'])
                ->get()->getRowArray();
            if ($existing) {
                $newIds[$p['action']] = (int) $existing['id'];
            } else {
                $this->db->table('permissions')->insert($p);
                $newIds[$p['action']] = (int) $this->db->insertID();
            }
        }

        // ── Admin (1) — semua BK permission ─────────────────────────────────
        $this->grantAll(1, $newIds);

        // ── Kepala Sekolah (2) — lihat + laporan saja ────────────────────────
        $this->grantIds(2, [
            $newIds['view'],
            $newIds['report'],
            $newIds['report_executive'],
        ]);

        // ── Guru (3) — full BK kecuali laporan eksekutif (khusus kepsek) ─────
        // Di SD, guru kelas merangkap BK: diberikan manage + case_manage + report.
        // Di SMP/SMA, hanya guru yg ditunjuk admin sbg guru BK yg mengisi data;
        // semua guru tetap bisa melihat dashboard BK untuk pantau siswa di kelas.
        $this->grantIds(3, [
            $newIds['view'],
            $newIds['manage'],
            $newIds['case_manage'],
            $newIds['report'],
        ]);
    }

    public function down()
    {
        $actions = ['view','manage','case_manage','report','report_executive'];
        foreach ($actions as $action) {
            $row = $this->db->table('permissions')
                ->where('module','bk')->where('action',$action)
                ->get()->getRowArray();
            if ($row) {
                $this->db->table('role_permissions')
                    ->where('permission_id', $row['id'])->delete();
                $this->db->table('permissions')
                    ->where('id', $row['id'])->delete();
            }
        }
    }

    private function grantAll(int $roleId, array $ids): void
    {
        foreach ($ids as $permId) {
            $this->grantIds($roleId, [$permId]);
        }
    }

    private function grantIds(int $roleId, array $permIds): void
    {
        foreach ($permIds as $permId) {
            if (!$permId) continue;
            $exists = $this->db->table('role_permissions')
                ->where('role_id', $roleId)->where('permission_id', $permId)
                ->countAllResults();
            if (!$exists) {
                $this->db->table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                ]);
            }
        }
    }
}
