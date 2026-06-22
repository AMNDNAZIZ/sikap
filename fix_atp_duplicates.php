<?php
/**
 * Fix ATP Duplicate Entries
 * Jalankan sekali via browser: https://yourdomain.com/fix_atp_duplicates.php?token=FIXATP2026
 * HAPUS FILE INI SETELAH DIJALANKAN.
 */

$token = $_GET['token'] ?? '';
if ($token !== 'FIXATP2026') {
    http_response_code(403);
    die('Forbidden. Tambahkan ?token=FIXATP2026 di URL.');
}

// Bootstrap CI4 database connection
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \CodeIgniter\Config\DotEnv::class;
$app    = new \Config\App();
$db     = \Config\Database::connect();

echo "<pre>";
echo "=== Diagnosa ATP Duplicates ===\n\n";

// 1. Cek duplikat di atp_elemen (atp_id + cp_master_id yang sama lebih dari sekali)
$dupsElemen = $db->query("
    SELECT atp_id, cp_master_id, COUNT(*) as cnt
    FROM atp_elemen
    GROUP BY atp_id, cp_master_id
    HAVING COUNT(*) > 1
")->getResultArray();

if (empty($dupsElemen)) {
    echo "✅ Tidak ada duplikat di tabel atp_elemen.\n\n";
} else {
    echo "❌ Ditemukan " . count($dupsElemen) . " duplikat di atp_elemen:\n";
    foreach ($dupsElemen as $d) {
        echo "  atp_id={$d['atp_id']} cp_master_id={$d['cp_master_id']} count={$d['cnt']}\n";
    }
    echo "\n";

    // Hapus duplikat — pertahankan id terkecil, hapus sisanya
    $deleted = 0;
    foreach ($dupsElemen as $d) {
        // Ambil semua baris dengan atp_id + cp_master_id ini
        $rows = $db->query("
            SELECT id FROM atp_elemen
            WHERE atp_id = {$d['atp_id']} AND cp_master_id = {$d['cp_master_id']}
            ORDER BY id ASC
        ")->getResultArray();

        // Pertahankan yang pertama (id terkecil), hapus sisanya
        $keepId = $rows[0]['id'];
        for ($i = 1; $i < count($rows); $i++) {
            $removeId = $rows[$i]['id'];
            // Pindahkan TP dari elemen duplikat ke elemen yang dipertahankan
            $db->query("UPDATE tujuan_pembelajaran SET atp_elemen_id = $keepId WHERE atp_elemen_id = $removeId");
            // Hapus elemen duplikat
            $db->query("DELETE FROM atp_elemen WHERE id = $removeId");
            $deleted++;
            echo "  Hapus ae.id=$removeId (TP dipindah ke ae.id=$keepId)\n";
        }
    }
    echo "\n✅ $deleted baris duplikat di atp_elemen berhasil dihapus.\n\n";
}

// 2. Cek duplikat di tujuan_pembelajaran (atp_elemen_id + kode_tp yang sama)
$dupsTp = $db->query("
    SELECT atp_elemen_id, kode_tp, COUNT(*) as cnt
    FROM tujuan_pembelajaran
    WHERE atp_elemen_id IS NOT NULL
    GROUP BY atp_elemen_id, kode_tp
    HAVING COUNT(*) > 1
")->getResultArray();

if (empty($dupsTp)) {
    echo "✅ Tidak ada duplikat di tabel tujuan_pembelajaran.\n\n";
} else {
    echo "❌ Ditemukan " . count($dupsTp) . " duplikat di tujuan_pembelajaran:\n";
    $deletedTp = 0;
    foreach ($dupsTp as $d) {
        $rows = $db->query("
            SELECT id FROM tujuan_pembelajaran
            WHERE atp_elemen_id = {$d['atp_elemen_id']} AND kode_tp = '{$d['kode_tp']}'
            ORDER BY id ASC
        ")->getResultArray();

        for ($i = 1; $i < count($rows); $i++) {
            $db->query("DELETE FROM tujuan_pembelajaran WHERE id = {$rows[$i]['id']}");
            $deletedTp++;
            echo "  Hapus tp.id={$rows[$i]['id']}\n";
        }
    }
    echo "\n✅ $deletedTp baris duplikat di tujuan_pembelajaran berhasil dihapus.\n\n";
}

echo "=== Selesai ===\n";
echo "\n⚠️  HAPUS FILE INI SEKARANG dari hosting!\n";
echo "</pre>";
