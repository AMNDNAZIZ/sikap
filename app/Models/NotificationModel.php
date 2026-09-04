<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false; // kita kelola created_at manual
    protected $allowedFields = [
        'user_id', 'type', 'title', 'message', 'url',
        'related_type', 'related_id', 'is_read', 'read_at', 'created_at',
    ];

    // ─── Kirim notifikasi ke satu user ───────────────────────────────────
    public function send(
        int    $userId,
        string $type,
        string $title,
        string $message  = '',
        ?string $url     = null,
        ?string $relType = null,
        ?int   $relId    = null
    ): int|string|false {
        return $this->insert([
            'user_id'      => $userId,
            'type'         => $type,
            'title'        => $title,
            'message'      => $message,
            'url'          => $url,
            'related_type' => $relType,
            'related_id'   => $relId,
            'is_read'      => 0,
            'read_at'      => null,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── Kirim notifikasi ke banyak user sekaligus (bulk insert) ─────────
    public function sendBulk(
        array  $userIds,
        string $type,
        string $title,
        string $message  = '',
        ?string $url     = null,
        ?string $relType = null,
        ?int   $relId    = null
    ): bool {
        if (empty($userIds)) {
            return true;
        }

        $now  = date('Y-m-d H:i:s');
        $rows = [];
        foreach (array_unique($userIds) as $uid) {
            $rows[] = [
                'user_id'      => (int) $uid,
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'url'          => $url,
                'related_type' => $relType,
                'related_id'   => $relId,
                'is_read'      => 0,
                'read_at'      => null,
                'created_at'   => $now,
            ];
        }

        return $this->insertBatch($rows) !== false;
    }

    // ─── Hitung notifikasi belum dibaca milik user ────────────────────────
    public function countUnread(int $userId): int
    {
        return (int) $this->where('user_id', $userId)
                          ->where('is_read', 0)
                          ->countAllResults();
    }

    // ─── Ambil notifikasi terbaru (untuk dropdown) ────────────────────────
    public function getRecent(int $userId, int $limit = 10): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    // ─── Ambil semua notifikasi dengan pagination (untuk halaman penuh) ───
    public function getPaginated(int $userId, int $perPage = 20): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->paginate($perPage);
    }

    // ─── Tandai satu notifikasi sebagai sudah dibaca ──────────────────────
    public function markRead(int $notifId, int $userId): bool
    {
        return $this->where('id', $notifId)
                    ->where('user_id', $userId) // keamanan: hanya milik user ini
                    ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
                    ->update();
    }

    // ─── Tandai semua notifikasi user sebagai sudah dibaca ───────────────
    public function markAllRead(int $userId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
                    ->update();
    }

    // ─── Hapus notifikasi lama (untuk cleanup, default > 90 hari) ────────
    public function deleteOld(int $days = 90): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->where('created_at <', $cutoff)->delete();
    }

    // ─── Ikon Bootstrap Icons berdasarkan type ────────────────────────────
    public static function getIcon(string $type): string
    {
        return match ($type) {
            'tugas_baru'     => 'bi-journal-text',
            'pengumuman_baru'=> 'bi-megaphone',
            'nilai_masuk'    => 'bi-award',
            'agenda_baru'    => 'bi-calendar-event',
            'tugas_dinilai'  => 'bi-check2-circle',
            'cbt_dibuka'     => 'bi-clipboard2-check',
            'materi_baru'    => 'bi-book',
            default          => 'bi-bell',
        };
    }

    // ─── Warna badge berdasarkan type ─────────────────────────────────────
    public static function getColor(string $type): string
    {
        return match ($type) {
            'tugas_baru'     => 'primary',
            'pengumuman_baru'=> 'warning',
            'nilai_masuk'    => 'success',
            'agenda_baru'    => 'info',
            'tugas_dinilai'  => 'success',
            'cbt_dibuka'     => 'danger',
            'materi_baru'    => 'secondary',
            default          => 'secondary',
        };
    }

    // ─── Label tipe notifikasi (Indonesia) ───────────────────────────────
    public static function getLabel(string $type): string
    {
        return match ($type) {
            'tugas_baru'     => 'Tugas Baru',
            'pengumuman_baru'=> 'Pengumuman',
            'nilai_masuk'    => 'Nilai',
            'agenda_baru'    => 'Agenda',
            'tugas_dinilai'  => 'Tugas Dinilai',
            'cbt_dibuka'     => 'Ujian',
            'materi_baru'    => 'Materi Baru',
            default          => 'Info',
        };
    }
}
