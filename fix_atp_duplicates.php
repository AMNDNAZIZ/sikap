<?php
/**
 * Fix ATP Duplicate atp_elemen entries
 * Jalankan sekali via browser: https://yourdomain.com/fix_atp_duplicates.php?token=FIXATP2026
 * HAPUS FILE INI SETELAH DIJALANKAN.
 */

$token = $_GET['token'] ?? '';
if ($token !== 'FIXATP2026') {
    http_response_code(403);
    die('Forbidden. Tambahkan ?token=FIXATP2026 di URL.');
}

// Koneksi langsung tanpa CI4 bootstrap
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'siakad';

// Coba baca dari .env jika ada
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    if (preg_match('/^database\.default\.hostname\s*=\s*(.+)$/m', $env, $m)) $host = trim($m[1]);
    if (preg_match('/^database\.default\.username\s*=\s*(.+)$/m', $env, $m)) $user = trim($m[1]);
    if (preg_match('/^database\.default\.password\s*=\s*(.+)$/m', $env, $m)) $pass = trim($m[1]);
    if (preg_match('/^database\.default\.database\s*=\s*(.+)$/m', $env, $m)) $dbname = trim($m[1]);
}

$pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo '<pre>';
echo "=== Fix ATP Duplikat di atp_elemen ===\n\n";
echo "DB: {$dbname} @ {$host}\n\n";

// 1. Tampilkan semua atp_elemen
$rows = $pdo->query("
    SELECT ae.id, ae.atp_id, ae.cp_master_id, ae.urutan, cp.elemen,
           COUNT(*) OVER (PARTITION BY ae.atp_id, ae.cp_master_id) as dup_count
    FROM atp_elemen ae
    JOIN cp_master cp ON cp.id = ae.cp_master_id
    ORDER BY ae.atp_id, ae.urutan
")->fetchAll(PDO::FETCH_ASSOC);

echo "Total baris di atp_elemen: " . count($rows) . "\n\n";

$hasDup = false;
foreach ($rows as $r) {
    $flag = $r['dup_count'] > 1 ? ' <<< DUPLIKAT' : '';
    if ($r['dup_count'] > 1) $hasDup = true;
    echo "atp_id={$r['atp_id']} | ae.id={$r['id']} | urutan={$r['urutan']} | {$r['elemen']}{$flag}\n";
}

if (!$hasDup) {
    echo "\n✅ Tidak ada duplikat di atp_elemen.\n";
    echo "\nKemungkinan masalah ada di CODE (JS scope) bukan di data.\n";
    echo "Pastikan file app/Views/admin/administrasi_guru/atp.php sudah diupdate.\n";
} else {
    echo "\n\n--- Membersihkan duplikat ---\n";

    $dups = $pdo->query("
        SELECT atp_id, cp_master_id
        FROM atp_elemen
        GROUP BY atp_id, cp_master_id
        HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dups as $d) {
        $dup_rows = $pdo->query("
            SELECT id FROM atp_elemen
            WHERE atp_id = {$d['atp_id']} AND cp_master_id = {$d['cp_master_id']}
            ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $keepId = $dup_rows[0]['id'];
        for ($i = 1; $i < count($dup_rows); $i++) {
            $removeId = $dup_rows[$i]['id'];
            $pdo->exec("UPDATE tujuan_pembelajaran SET atp_elemen_id = {$keepId} WHERE atp_elemen_id = {$removeId}");
            $pdo->exec("DELETE FROM atp_elemen WHERE id = {$removeId}");
            echo "Hapus ae.id={$removeId}, TP dipindah ke ae.id={$keepId}\n";
        }
    }
    echo "\n✅ Selesai membersihkan duplikat.\n";
}

echo "\n⚠️  HAPUS FILE INI SEKARANG!\n";
echo '</pre>';
