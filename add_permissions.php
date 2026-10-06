<?php

$db = new mysqli('localhost', 'root', '', 'siakad'); // adjust if db user/pass is different
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$permissions = [
    ['module' => 'audit_logs', 'action' => 'view', 'label' => 'Lihat Log Aktivitas', 'group_name' => 'Sistem & Keamanan', 'sort_order' => 99],
    ['module' => 'ekskul_absensi', 'action' => 'view', 'label' => 'Lihat Absensi Pembina', 'group_name' => 'Presensi', 'sort_order' => 15],
    ['module' => 'ekskul_absensi', 'action' => 'verify', 'label' => 'Verifikasi Absensi Pembina', 'group_name' => 'Presensi', 'sort_order' => 16],
    ['module' => 'ekskul', 'action' => 'manage_pembina', 'label' => 'Kelola Pembina Ekskul', 'group_name' => 'Data Induk', 'sort_order' => 25],
];

foreach ($permissions as $p) {
    $check = $db->query("SELECT id FROM permissions WHERE module = '{$p['module']}' AND action = '{$p['action']}'");
    if ($check->num_rows == 0) {
        $db->query("INSERT INTO permissions (module, action, label, group_name, sort_order) VALUES ('{$p['module']}', '{$p['action']}', '{$p['label']}', '{$p['group_name']}', {$p['sort_order']})");
        echo "Inserted: {$p['module']}.{$p['action']}\n";
    } else {
        echo "Exists: {$p['module']}.{$p['action']}\n";
    }
}

echo "Done.\n";
