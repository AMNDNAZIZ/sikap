<?php

namespace App\Models;

use CodeIgniter\Model;

class ForumThreadModel extends Model
{
    protected $table         = 'forum_threads';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'subject_id', 'class_id', 'academic_year_id',
        'related_type', 'related_id',
        'title', 'body', 'user_id',
        'is_pinned', 'is_locked', 'is_answered',
        'reply_count', 'view_count', 'last_reply_at',
    ];

    // ── Daftar thread untuk satu kelas + mapel, dengan info penulis ──────
    public function getList(int $classId, int $subjectId, int $yearId, int $userId = 0): array
    {
        $db   = \Config\Database::connect();

        $rows = $db->table('forum_threads ft')
            ->select('ft.*, u.fullname as author_name, u.role_id as author_role,
                      s.name as subject_name, c.name as class_name,
                      (SELECT read_at FROM forum_reads fr
                       WHERE fr.thread_id = ft.id AND fr.user_id = ' . (int)$userId . '
                       LIMIT 1) as read_at')
            ->join('users u', 'u.id = ft.user_id', 'left')
            ->join('subjects s', 's.id = ft.subject_id', 'left')
            ->join('classes c', 'c.id = ft.class_id', 'left')
            ->where('ft.class_id', $classId)
            ->where('ft.subject_id', $subjectId)
            ->where('ft.academic_year_id', $yearId)
            ->orderBy('ft.is_pinned', 'DESC')
            ->orderBy('(ft.last_reply_at IS NULL)', 'ASC', false)
            ->orderBy('COALESCE(ft.last_reply_at, ft.created_at)', 'DESC', false)
            ->get()->getResultArray();

        // Tandai "baru" jika ada reply setelah read_at
        foreach ($rows as &$r) {
            $r['is_new'] = $userId > 0 && (
                $r['read_at'] === null ||
                ($r['last_reply_at'] && $r['last_reply_at'] > $r['read_at'])
            );
        }
        unset($r);

        return $rows;
    }

    // ── Detail satu thread + info penulis ─────────────────────────────────
    public function getDetail(int $threadId): ?array
    {
        return $this->db->table('forum_threads ft')
            ->select('ft.*, u.fullname as author_name, u.role_id as author_role,
                      s.name as subject_name, c.name as class_name')
            ->join('users u', 'u.id = ft.user_id', 'left')
            ->join('subjects s', 's.id = ft.subject_id', 'left')
            ->join('classes c', 'c.id = ft.class_id', 'left')
            ->where('ft.id', $threadId)
            ->get()->getRowArray() ?: null;
    }

    // ── Increment view_count (sekali per session, dikelola controller) ────
    public function incrementView(int $threadId): void
    {
        $this->db->query(
            'UPDATE forum_threads SET view_count = view_count + 1 WHERE id = ?',
            [$threadId]
        );
    }

    // ── Update reply_count dan last_reply_at setelah reply baru ──────────
    public function refreshStats(int $threadId): void
    {
        $count = $this->db->table('forum_replies')
            ->where('thread_id', $threadId)
            ->where('is_deleted', 0)
            ->countAllResults();

        $last = $this->db->table('forum_replies')
            ->select('created_at')
            ->where('thread_id', $threadId)
            ->where('is_deleted', 0)
            ->orderBy('created_at', 'DESC')
            ->limit(1)
            ->get()->getRowArray();

        $this->update($threadId, [
            'reply_count'   => $count,
            'last_reply_at' => $last['created_at'] ?? null,
        ]);
    }

    // ── Ambil mapel yang punya thread aktif di kelas ini (untuk filter tab) ─
    public function getSubjectsWithThreads(int $classId, int $yearId): array
    {
        return $this->db->table('forum_threads ft')
            ->select('s.id as subject_id, s.name as subject_name, COUNT(ft.id) as thread_count')
            ->join('subjects s', 's.id = ft.subject_id')
            ->where('ft.class_id', $classId)
            ->where('ft.academic_year_id', $yearId)
            ->groupBy('ft.subject_id')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();
    }

    // ── Jumlah thread belum dibaca user di kelas+mapel ───────────────────
    public function countUnread(int $classId, int $subjectId, int $yearId, int $userId): int
    {
        // Thread yang belum pernah dibaca atau ada reply baru setelah read_at
        $sql = 'SELECT COUNT(*) as cnt FROM forum_threads ft
                LEFT JOIN forum_reads fr ON fr.thread_id = ft.id AND fr.user_id = ?
                WHERE ft.class_id = ? AND ft.subject_id = ? AND ft.academic_year_id = ?
                  AND (fr.read_at IS NULL
                       OR (ft.last_reply_at IS NOT NULL AND ft.last_reply_at > fr.read_at))';

        $row = $this->db->query($sql, [$userId, $classId, $subjectId, $yearId])->getRowArray();
        return (int) ($row['cnt'] ?? 0);
    }
}
