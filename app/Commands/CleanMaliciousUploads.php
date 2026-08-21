<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Membersihkan record database yang merujuk ke file upload berbahaya
 * atau file yang sudah tidak ada di disk.
 *
 * Usage: php spark clean:uploads [--dry-run]
 */
class CleanMaliciousUploads extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'clean:uploads';
    protected $description = 'Hapus record CMS yang merujuk ke file berbahaya atau file yang tidak ada di disk.';
    protected $usage       = 'clean:uploads [--dry-run]';
    protected $options     = [
        '--dry-run' => 'Tampilkan apa yang akan dihapus tanpa benar-benar menghapus.',
    ];

    // Ekstensi yang TIDAK boleh ada di folder upload gambar
    private const DANGEROUS_EXT = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
                                    'pl', 'py', 'rb', 'cgi', 'sh', 'bash', 'asp', 'aspx',
                                    'jsp', 'exe', 'bat', 'cmd', 'ps1', 'vbs', 'js', 'htaccess'];

    // Tabel dan kolom gambar yang perlu dicek
    private const TABLES = [
        ['table' => 'landing_activities', 'col' => 'image', 'dir' => 'activities'],
        ['table' => 'landing_articles',   'col' => 'image', 'dir' => 'articles'],
        ['table' => 'landing_facilities', 'col' => 'image', 'dir' => 'facilities'],
        ['table' => 'landing_sliders',    'col' => 'image', 'dir' => 'sliders'],
    ];

    public function run(array $params)
    {
        $dryRun = array_key_exists('dry-run', $params) || in_array('--dry-run', $params);
        $db     = \Config\Database::connect();

        if ($dryRun) {
            CLI::write('[DRY RUN] Tidak ada yang akan dihapus.', 'yellow');
        }

        $totalDeleted    = 0;
        $totalFlaggedExt = 0;
        $totalMissing    = 0;

        foreach (self::TABLES as $entry) {
            $table  = $entry['table'];
            $col    = $entry['col'];
            $dir    = UPLOAD_PATH . $entry['dir'];

            CLI::write("\n── Tabel: {$table} ──", 'cyan');

            $rows = $db->table($table)
                ->select("id, {$col}")
                ->where("{$col} IS NOT NULL")
                ->where("{$col} !=", '')
                ->get()->getResultArray();

            foreach ($rows as $row) {
                $filename  = $row[$col];
                $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $fullPath  = $dir . '/' . basename($filename);

                $isDangerous = in_array($ext, self::DANGEROUS_EXT, true);
                $isMissing   = !is_file($fullPath);

                if (!$isDangerous && !$isMissing) {
                    continue; // File aman dan ada — lewati
                }

                $reason = [];
                if ($isDangerous) { $reason[] = "ekstensi berbahaya (.{$ext})"; $totalFlaggedExt++; }
                if ($isMissing)   { $reason[] = 'file tidak ada di disk';        $totalMissing++;   }

                $reasonStr = implode(' + ', $reason);
                CLI::write("  [ID {$row['id']}] {$filename} → {$reasonStr}", $isDangerous ? 'red' : 'yellow');

                if (!$dryRun) {
                    // Hapus file fisik jika masih ada (kasus ekstensi berbahaya yang belum dihapus manual)
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                        CLI::write("    ✓ File fisik dihapus.", 'green');
                    }

                    // Kosongkan kolom image di DB (tidak hapus record agar konten tetap ada)
                    $db->table($table)->where('id', $row['id'])->update([$col => null]);
                    $totalDeleted++;
                }
            }
        }

        CLI::write("\n══ Selesai ══", 'green');
        if ($dryRun) {
            CLI::write("Ditemukan: {$totalFlaggedExt} file berbahaya, {$totalMissing} file hilang.", 'yellow');
            CLI::write("Jalankan tanpa --dry-run untuk menghapus.", 'yellow');
        } else {
            CLI::write("Total record dibersihkan: {$totalDeleted} (ekstensi berbahaya: {$totalFlaggedExt}, file hilang: {$totalMissing}).", 'green');
        }
    }
}
