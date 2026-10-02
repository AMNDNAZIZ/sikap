<?php

declare(strict_types=1);

namespace App\Libraries\Session;

use CodeIgniter\Session\Handlers\Database\MySQLiHandler;

/**
 * FastMySQLiHandler
 *
 * Session handler MySQLi kustom untuk skenario konkurensi tinggi (ujian CBT
 * dengan 100+ peserta bersamaan) saat driver session = database.
 *
 * Perbedaan dari handler bawaan CodeIgniter:
 * - GET_LOCK timeout dipangkas dari 300 detik menjadi beberapa detik saja.
 *
 * ALASAN:
 * Handler bawaan memakai GET_LOCK(key, 300). Saat lock diperebutkan (banyak
 * request paralel untuk session id yang sama, atau kontensi umum di server),
 * request akan MENUNGGU hingga 5 menit sambil menahan koneksi PHP-FPM/Apache.
 * Pada 80-100+ peserta, koneksi MySQL (max_connections) dan worker web cepat
 * habis → request baru gagal mendapat koneksi → session gagal dibaca →
 * CodeIgniter memulai session kosong → filter Auth me-logout peserta.
 *
 * Dengan timeout pendek, request yang tidak segera memperoleh lock akan gagal
 * cepat (lalu bisa dicoba ulang oleh klien) alih-alih menyandera koneksi lama,
 * sehingga throughput keseluruhan jauh lebih tinggi dan logout massal dicegah.
 */
class FastMySQLiHandler extends MySQLiHandler
{
    /**
     * Durasi maksimum (detik) menunggu GET_LOCK sebelum menyerah.
     * Sengaja pendek agar koneksi tidak tertahan lama saat konkurensi tinggi.
     */
    protected int $lockTimeout = 10;

    /**
     * Lock the session (timeout pendek).
     */
    protected function lockSession(string $sessionID): bool
    {
        $arg = md5($sessionID . ($this->matchIP ? '_' . $this->ipAddress : ''));

        $timeout = (int) $this->lockTimeout;
        $row = $this->db
            ->query("SELECT GET_LOCK('{$arg}', {$timeout}) AS ci_session_lock")
            ->getRow();

        if ($row && $row->ci_session_lock) {
            $this->lock = $arg;

            return true;
        }

        return $this->fail();
    }

    /**
     * Releases the lock, if any — versi aman dari fatal error.
     *
     * Handler bawaan memanggil fail() saat RELEASE_LOCK gagal. fail() menjalankan
     * ini_set('session.save_path', ...) yang MELEMPAR fatal error bila session
     * masih aktif (mis. saat baris ci_sessions ter-truncate/hilang di tengah
     * request, atau koneksi DB terputus). Advisory lock MySQL (GET_LOCK) otomatis
     * dilepas ketika koneksi ditutup, jadi kegagalan RELEASE_LOCK tidak fatal dan
     * cukup dicatat ke log tanpa memicu ini_set().
     */
    protected function releaseLock(): bool
    {
        if (! $this->lock) {
            return true;
        }

        try {
            $result = $this->db
                ->query("SELECT RELEASE_LOCK('{$this->lock}') AS ci_session_lock")
                ->getRow();

            if ($result && $result->ci_session_lock) {
                $this->lock = false;

                return true;
            }
        } catch (\Throwable $e) {
            log_message('warning', '[Session] RELEASE_LOCK gagal (diabaikan, lock lepas saat koneksi tutup): ' . $e->getMessage());
        }

        // Jangan panggil fail() (menghindari ini_set fatal). Reset state; lock
        // akan otomatis terlepas oleh MySQL saat koneksi ditutup di akhir request.
        $this->lock = false;

        return true;
    }
}
