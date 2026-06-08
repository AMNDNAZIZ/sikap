<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StaffChatRoomModel;
use App\Models\StaffChatMessageModel;
use App\Models\StaffChatMentionModel;

/**
 * StaffChat — Grup chat internal untuk Admin, Kepala Sekolah, Guru, dan Staf.
 * Berbeda dari Chat (obrolan kelas) yang berbasis class_id,
 * StaffChat menggunakan satu room global yang diakses oleh role 1,2,3,7.
 */
class StaffChat extends BaseController
{
    protected $roomModel;
    protected $messageModel;
    protected $mentionModel;

    // Role yang boleh mengakses Staff Chat
    const ALLOWED_ROLES = [1, 2, 3, 7]; // Admin, Kepsek, Guru, Staf

    public function __construct()
    {
        $this->roomModel    = new StaffChatRoomModel();
        $this->messageModel = new StaffChatMessageModel();
        $this->mentionModel = new StaffChatMentionModel();
        helper(['form', 'url']);
    }

    private function currentUser(): ?array
    {
        return session()->get('user') ?? null;
    }

    private function currentUserId(): ?int
    {
        $u = $this->currentUser();
        return $u ? (int) $u['id'] : null;
    }

    private function canAccess(): bool
    {
        $user = $this->currentUser();
        if (!$user) return false;
        return in_array((int) $user['role_id'], self::ALLOWED_ROLES);
    }

    /**
     * Halaman utama — langsung masuk ke room staff (hanya satu room global)
     */
    public function index()
    {
        if (!$this->canAccess()) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        // Paksa browser tidak cache halaman ini
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');

        // Auto-create room jika belum ada
        $room = $this->roomModel->first();
        if (!$room) {
            $roomId = $this->roomModel->insert(['name' => 'Ruang Diskusi Staff']);
            $room   = $this->roomModel->find($roomId);
        }

        // Bersihkan pesan lama (> 30 hari)
        $this->cleanOldMessages();

        // Ambil pesan awal langsung dari server (SSR) — tidak perlu AJAX untuk load pertama
        // Ini menghilangkan masalah cache browser/bfcache sepenuhnya
        $db = \Config\Database::connect();
        $initialMessages = $db->table('staff_chat_messages m')
            ->select("
                m.id, m.message, m.attachment, m.created_at, m.user_id,
                COALESCE(t.name, u.fullname, u.username) as display_name,
                r.name as role_name,
                m.reply_to as reply_id,
                rm.message as reply_message,
                rm.attachment as reply_attachment,
                COALESCE(rt.name, ru.fullname, ru.username) as reply_user
            ")
            ->join('users u', 'u.id = m.user_id', 'left')
            ->join('teachers t', 't.user_id = u.id', 'left')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->join('staff_chat_messages rm', 'rm.id = m.reply_to', 'left')
            ->join('users ru', 'ru.id = rm.user_id', 'left')
            ->join('teachers rt', 'rt.user_id = ru.id', 'left')
            ->where('m.room_id', $room['id'])
            ->orderBy('m.created_at', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil daftar anggota untuk fitur @mention
        $members = $db->table('users')
            ->select('id, username, fullname, role_id')
            ->whereIn('role_id', self::ALLOWED_ROLES)
            ->orderBy('fullname', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($members as &$m) {
            $m['mention_key'] = preg_replace(
                '/[^a-z0-9_]/', '',
                strtolower(str_replace(' ', '_', $m['fullname'] ?? $m['username']))
            );
        }
        unset($m);

        return view('admin/staff_chat/room', [
            'title'           => 'Obrolan Staff',
            'room'            => $room,
            'members'         => $members,
            'currentUserId'   => $this->currentUserId(),
            'initialMessages' => $initialMessages,
        ]);
    }

    /**
     * Kirim pesan (AJAX POST)
     */
    public function send()
    {
        if (!$this->canAccess()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $user = $this->currentUser();

        $attachment = '';
        $file = $this->request->getFile('photo');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($file->getMimeType(), $allowed)) {
                $newName = $file->getRandomName();
                $file->move(UPLOAD_PATH . 'chat', $newName);
                $attachment = $newName;
            }
        }

        $roomId  = (int) $this->request->getPost('room_id');
        $message = trim($this->request->getPost('message') ?? '');
        $replyTo = (int) ($this->request->getPost('reply_to') ?: 0);

        if (empty($message) && empty($attachment)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => 'error', 'message' => 'Pesan tidak boleh kosong.']);
        }

        $data = [
            'room_id'    => $roomId,
            'user_id'    => $user['id'],
            'message'    => $message,
            'attachment' => $attachment,
            'reply_to'   => $replyTo ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->messageModel->insert($data);
            $msgId = $this->messageModel->getInsertID();

            // Proses @mention — cari @username dalam pesan
            if (!empty($message)) {
                $this->processMentions($message, $msgId, $user['id']);
            }

            return $this->response->setJSON(['status' => 'ok']);
        } catch (\Exception $e) {
            log_message('error', 'StaffChat::send error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON(['status' => 'error', 'message' => 'Gagal mengirim pesan.']);
        }
    }

    /**
     * Ambil pesan (AJAX GET — polling setiap 5 detik)
     */
    public function fetch(int $roomId)
    {
        if (!$this->canAccess()) {
            return $this->response->setStatusCode(403)->setBody('');
        }

        // Pastikan response tidak di-cache oleh browser
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        $this->response->setHeader('Pragma', 'no-cache');

        // Jika roomId tidak valid, ambil room pertama yang ada
        if ($roomId <= 0) {
            $room = $this->roomModel->first();
            $roomId = $room ? (int)$room['id'] : 0;
        }

        if ($roomId <= 0) {
            return $this->response->setBody('');
        }

        $db = \Config\Database::connect();

        $messages = $db->table('staff_chat_messages m')
            ->select("
                m.id, m.message, m.attachment, m.created_at, m.user_id,
                COALESCE(t.name, u.fullname, u.username) as display_name,
                r.name as role_name,
                m.reply_to as reply_id,
                rm.message as reply_message,
                rm.attachment as reply_attachment,
                COALESCE(rt.name, ru.fullname, ru.username) as reply_user
            ")
            ->join('users u', 'u.id = m.user_id', 'left')
            ->join('teachers t', 't.user_id = u.id', 'left')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->join('staff_chat_messages rm', 'rm.id = m.reply_to', 'left')
            ->join('users ru', 'ru.id = rm.user_id', 'left')
            ->join('teachers rt', 'rt.user_id = ru.id', 'left')
            ->where('m.room_id', $roomId)
            ->orderBy('m.created_at', 'ASC')
            ->get()
            ->getResultArray();

        log_message('debug', 'StaffChat::fetch room_id=' . $roomId . ' messages=' . count($messages));

        return view('admin/staff_chat/_messages', [
            'messages'      => $messages,
            'currentUserId' => $this->currentUserId(),
        ]);
    }

    /**
     * Jumlah mention yang belum dibaca (AJAX GET — untuk badge di sidebar)
     */
    public function mentions()
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            return $this->response->setJSON(['count' => 0]);
        }

        $db = \Config\Database::connect();

        // Ambil room staff
        $room = $this->roomModel->first();
        if (!$room) {
            return $this->response->setJSON(['count' => 0]);
        }

        // Hitung mention unread di room staff saja
        $sub = $db->table('staff_chat_messages')
            ->select('id')
            ->where('room_id', $room['id'])
            ->getCompiledSelect();

        $count = $db->table('staff_chat_mentions')
            ->where('mentioned_user_id', $userId)
            ->where('is_read', 0)
            ->where("message_id IN ($sub)", null, false)
            ->countAllResults();

        return $this->response->setJSON(['count' => (int) $count]);
    }

    /**
     * Tandai semua mention di room ini sebagai sudah dibaca (AJAX GET)
     */
    public function clearMentions()
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error']);
        }

        $db   = \Config\Database::connect();
        $room = $this->roomModel->first();
        if (!$room) {
            return $this->response->setJSON(['status' => 'ok', 'cleared' => 0]);
        }

        $sub = $db->table('staff_chat_messages')
            ->select('id')
            ->where('room_id', $room['id'])
            ->getCompiledSelect();

        $db->table('staff_chat_mentions')
            ->where('mentioned_user_id', $userId)
            ->where('is_read', 0)
            ->where("message_id IN ($sub)", null, false)
            ->update(['is_read' => 1]);

        return $this->response->setJSON(['status' => 'ok', 'cleared' => $db->affectedRows()]);
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    private function processMentions(string $message, int $msgId, int $senderId): void
    {
        preg_match_all('/@(\w+)/', $message, $matches);
        if (empty($matches[1])) return;

        $db      = \Config\Database::connect();
        $members = $db->table('users')
            ->select('id, fullname')
            ->whereIn('role_id', self::ALLOWED_ROLES)
            ->get()
            ->getResultArray();

        // Buat lookup: mention_key → user_id
        $mentionMap = [];
        foreach ($members as $m) {
            $key = preg_replace(
                '/[^a-z0-9_]/', '',
                strtolower(str_replace(' ', '_', $m['fullname']))
            );
            $mentionMap[$key] = (int) $m['id'];
        }

        foreach (array_unique($matches[1]) as $mentionKey) {
            $mentionedId = $mentionMap[strtolower($mentionKey)] ?? null;

            if ($mentionedId && $mentionedId !== $senderId) {
                $this->mentionModel->insert([
                    'message_id'        => $msgId,
                    'mentioned_user_id' => $mentionedId,
                    'is_read'           => 0,
                ]);
            }
        }
    }

    private function cleanOldMessages(): void
    {
        $room = $this->roomModel->first();
        if (!$room) return;

        // Hapus pesan obrolan staff yang lebih dari 7 hari
        $limitDate   = date('Y-m-d H:i:s', strtotime('-7 days'));
        $oldMessages = $this->messageModel
            ->where('room_id', $room['id'])
            ->where('created_at <', $limitDate)
            ->findAll();

        foreach ($oldMessages as $msg) {
            if (!empty($msg['attachment'])) {
                $filePath = UPLOAD_PATH . 'chat/' . $msg['attachment'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        $deleted = $this->messageModel
            ->where('room_id', $room['id'])
            ->where('created_at <', $limitDate)
            ->delete();

        if ($deleted) {
            log_message('info', 'Chat staff: pesan lama (>7 hari) dihapus.');
        }
    }
}
