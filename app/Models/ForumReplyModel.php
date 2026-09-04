<?php

namespace App\Models;

use CodeIgniter\Model;

class ForumReplyModel extends Model
{
    protected $table         = 'forum_replies';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'thread_id', 'parent_id', 'user_id', 'body',
        'is_best_answer', 'is_deleted', 'deleted_by', 'upvotes',
    ];

    // ── Semua reply untuk satu thread (flat + info penulis) ─────────────
    public function getByThread(int $threadId, int $viewerUserId = 0): array
    {
        $rows = $this->db->table('forum_replies fr')
            ->select('fr.*, u.fullname as author_name, u.role_id as author_role,
                      (SELECT COUNT(*) FROM forum_upvotes fu
                       WHERE fu.reply_id = fr.id AND fu.user_id = ' . (int)$viewerUserId . ') as viewer_upvoted')
            ->join('users u', 'u.id = fr.user_id', 'left')
            ->where('fr.thread_id', $threadId)
            ->orderBy('fr.is_best_answer', 'DESC')         // jawaban terbaik duluan
            ->orderBy('(fr.parent_id IS NULL)', 'DESC', false) // root reply duluan (raw)
            ->orderBy('fr.created_at', 'ASC')
            ->get()->getResultArray();

        return $rows;
    }

    // ── Bangun struktur hierarki reply (flat 2 level: root + semua turunan) ─
    // Semua balasan diratakan ke root parent-nya, sehingga tidak ada reply
    // yang hilang meski user membalas sebuah child reply.
    public static function buildTree(array $flat): array
    {
        // Index semua reply by id
        $index = [];
        foreach ($flat as $r) {
            $r['children'] = [];
            $index[(int)$r['id']] = $r;
        }

        // Cari root parent tertinggi dari sebuah id
        $getRootId = function(int $id) use (&$index, &$getRootId): int {
            if (!isset($index[$id])) return $id;
            $parentId = isset($index[$id]['parent_id']) ? (int)$index[$id]['parent_id'] : 0;
            if ($parentId === 0) return $id;
            return $getRootId($parentId);
        };

        $roots    = [];
        $children = []; // rootId => [child, ...]

        foreach ($index as $id => $r) {
            $parentId = isset($r['parent_id']) ? (int)$r['parent_id'] : 0;

            if ($parentId === 0) {
                // Ini root reply
                $roots[$id] = $r;
            } else {
                // Temukan root parent tertinggi
                $rootId = $getRootId($parentId);
                $children[$rootId][] = $r;
            }
        }

        // Gabungkan children ke root masing-masing
        foreach ($roots as $rootId => $root) {
            $roots[$rootId]['children'] = $children[$rootId] ?? [];
        }

        return array_values($roots);
    }

    // ── Soft-delete satu reply ────────────────────────────────────────────
    public function softDelete(int $replyId, int $deletedBy): bool
    {
        return $this->where('id', $replyId)->set([
            'is_deleted' => 1,
            'deleted_by' => $deletedBy,
            'body'       => '[Balasan ini telah dihapus oleh moderator]',
        ])->update();
    }

    // ── Toggle upvote (tambah atau hapus) — kembalikan jumlah baru ────────
    public function toggleUpvote(int $replyId, int $userId): int
    {
        $existing = $this->db->table('forum_upvotes')
            ->where('reply_id', $replyId)
            ->where('user_id', $userId)
            ->get()->getRowArray();

        if ($existing) {
            // Hapus upvote
            $this->db->table('forum_upvotes')
                ->where('reply_id', $replyId)
                ->where('user_id', $userId)
                ->delete();
            $this->db->query(
                'UPDATE forum_replies SET upvotes = GREATEST(0, upvotes - 1) WHERE id = ?',
                [$replyId]
            );
        } else {
            // Tambah upvote
            $this->db->table('forum_upvotes')->insert([
                'reply_id'   => $replyId,
                'user_id'    => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->db->query(
                'UPDATE forum_replies SET upvotes = upvotes + 1 WHERE id = ?',
                [$replyId]
            );
        }

        $row = $this->db->table('forum_replies')
            ->select('upvotes')->where('id', $replyId)->get()->getRowArray();
        return (int) ($row['upvotes'] ?? 0);
    }

    // ── Tandai satu reply sebagai jawaban terbaik ─────────────────────────
    public function setBestAnswer(int $threadId, int $replyId): void
    {
        // Hapus flag sebelumnya di thread yang sama
        $this->where('thread_id', $threadId)->set(['is_best_answer' => 0])->update();
        // Set yang baru
        $this->where('id', $replyId)->set(['is_best_answer' => 1])->update();
        // Tandai thread sebagai sudah terjawab
        $this->db->table('forum_threads')
            ->where('id', $threadId)
            ->set(['is_answered' => 1])
            ->update();
    }
}
