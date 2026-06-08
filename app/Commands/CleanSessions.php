<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanSessions extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'session:clean';
    protected $description = 'Hapus session yang sudah expired dari database';
    protected $usage       = 'session:clean [--dry-run]';
    protected $options     = [
        '--dry-run' => 'Tampilkan jumlah yang akan dihapus tanpa benar-benar menghapus',
    ];

    public function run(array $params)
    {
        $db = db_connect();

        $isDryRun = array_key_exists('dry-run', $params) || in_array('--dry-run', $params);

        // Ambil expiration dari config
        $expiration = config('Session')->expiration ?: 7200;
        $cutoff = time() - $expiration;

        // Hitung dulu berapa yang akan dihapus
        $count = $db->table('ci_sessions')
            ->where('timestamp <', $cutoff)
            ->countAllResults();

        if ($isDryRun) {
            CLI::write("[DRY RUN] Akan menghapus {$count} session expired (cutoff: " . date('Y-m-d H:i:s', $cutoff) . ")", 'yellow');
            return;
        }

        if ($count === 0) {
            CLI::write('Tidak ada session expired yang perlu dihapus.', 'green');
            return;
        }

        $db->table('ci_sessions')
            ->where('timestamp <', $cutoff)
            ->delete();

        CLI::write("✓ Berhasil menghapus {$count} session expired.", 'green');
        CLI::write("  Cutoff: " . date('Y-m-d H:i:s', $cutoff), 'dark_gray');

        log_message('info', "CleanSessions: {$count} expired sessions deleted.");
    }
}
