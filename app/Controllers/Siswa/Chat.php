<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\ChatRoomModel;
use App\Models\ChatMessageModel;
use App\Models\ChatMentionModel;
use App\Models\UserModel;
use Config\Database;

class Chat extends BaseController
{
    protected $roomModel;
    protected $messageModel;
    protected $mentionModel;

    public function __construct()
    {
        $this->roomModel = new ChatRoomModel();
        $this->messageModel = new ChatMessageModel();
        $this->mentionModel = new ChatMentionModel();
        helper(['form', 'url']);
    }

    private function currentUser()
    {
        return session()->get('user') ?? null;
    }

    private function currentUserId()
    {
        $u = $this->currentUser();
        return $u['id'] ?? null;
    }

    // siswa langsung diarahkan ke room kelasnya
    public function index()
    {
        $user = $this->currentUser();
        if (!$user) {
            return redirect()->to('/login');
        }

        $db = Database::connect();
        $student = $db->table('students s')
            ->select('s.id as student_id, sr.class_id')
            ->join('student_records sr', 'sr.student_id = s.id')
            ->where('s.user_id', $user['id'])
            ->orderBy('sr.id', 'DESC') // ambil record terbaru
            ->get()
            ->getRowArray();

        if (!$student) {
            return view('siswa/chat/empty', ['title' => 'Obrolan Kelas']);
        }

        return redirect()->to(site_url('siswa/chat/room/' . $student['class_id']));
    }


    // 🔹 Buka room siswa (langsung kelasnya)
    public function room($classId)
    {
        $user = $this->currentUser();

        $room = $this->roomModel->where('class_id', $classId)->first();
        if (!$room) {
            $roomId = $this->roomModel->insert(['class_id' => $classId]);
            $room = $this->roomModel->find($roomId);
        }

        $db = \Config\Database::connect();

        // Cek akses (Siswa hanya bisa akses kelasnya sendiri)
        $student = $db->table('students s')
            ->select('s.id, sr.class_id')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->where('s.user_id', $user['id'])
            ->orderBy('sr.id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$student || $student['class_id'] != $classId) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Anda tidak memiliki akses ke obrolan kelas ini.');
        }

        $class = $db->table('classes')->where('id', $classId)->get()->getRowArray();

        // Bersihkan pesan lama (otomatis)
        $this->cleanOldMessages();

        return view('chat/room', [
            'title' => 'Obrolan Kelas',
            'room' => $room,
            'class' => $class,
            'role' => 'siswa',   // ✅ role siswa
        ]);
    }

    // 🔹 Kirim pesan
    public function send()
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Unauthorized', 'csrf' => csrf_hash()]);
        }

        $attachment = '';
        $file = $this->request->getFile('photo');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            if (in_array($file->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'])) {
                $newName = $file->getRandomName();
                $file->move(UPLOAD_PATH . 'chat', $newName);
                $attachment = $newName;
            }
        }

        $data = [
            'room_id' => (int) $this->request->getPost('room_id'),
            'user_id' => (int) $user['id'],
            'message' => trim((string) $this->request->getPost('message')),
            'attachment' => $attachment,
            'reply_to' => (int) ($this->request->getPost('reply_to') ?: 0),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->messageModel->insert($data);
            $msgId = (int) $this->messageModel->getInsertID();

            // 🔹 Proses notifikasi mention @user jika ada
            if (!empty($data['message']) && $msgId > 0) {
                $this->processMentions($msgId, $data['message'], (int) $user['id'], $data['room_id']);
            }

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'ok', 'csrf' => csrf_hash()]);
            }
        } catch (\Exception $e) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)
                    ->setJSON(['status' => 'error', 'message' => $e->getMessage(), 'csrf' => csrf_hash()]);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back();
    }

    // 🔹 Ambil isi pesan (AJAX) - Mendukung real-time incremental polling
    public function fetch($roomId)
    {
        // 🚀 SHARED HOSTING OPTIMIZATION: Lepas session lock segera agar tidak memblokir AJAX lain
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $db = \Config\Database::connect();
        $currentUserId = $this->currentUserId();
        $after = (int) ($this->request->getGet('after') ?? 0);

        $builder = $db->table('chat_messages m')
            ->select("
                m.id, m.message, m.attachment, m.created_at, m.user_id,
                COALESCE(s.name, u.fullname, u.username) as display_name,
                m.reply_to as reply_id,
                rm.message as reply_message,
                rm.attachment as reply_attachment,
                COALESCE(rs.name, ru.fullname, ru.username) as reply_user
            ")
            ->join('users u', 'u.id = m.user_id', 'left')
            ->join('students s', 's.user_id = u.id', 'left')
            ->join('chat_messages rm', 'rm.id = m.reply_to', 'left')
            ->join('users ru', 'ru.id = rm.user_id', 'left')
            ->join('students rs', 'rs.user_id = ru.id', 'left')
            ->where('m.room_id', $roomId);

        if ($after > 0) {
            $builder->where('m.id >', $after);
        }

        $messages = $builder->orderBy('m.created_at', 'ASC')
            ->get()
            ->getResultArray();

        $lastId = $after;
        if (!empty($messages)) {
            $lastItem = end($messages);
            $lastId = (int) $lastItem['id'];
        }

        $html = view('chat/_messages', [
            'messages' => $messages,
            'currentUserId' => $currentUserId,
        ]);

        if ($this->request->isAJAX() && ($this->request->getGet('after') !== null || $this->request->getHeaderLine('Accept') === 'application/json')) {
            return $this->response->setJSON([
                'status'  => 'ok',
                'count'   => count($messages),
                'last_id' => $lastId,
                'html'    => $html
            ]);
        }

        return $html;
    }

    // 🔹 Proses mention @username atau @nama dalam chat kelas
    protected function processMentions(int $msgId, string $message, int $senderId, int $roomId): void
    {
        if (!preg_match_all('/@([a-zA-Z0-9_]+)/', $message, $matches)) {
            return;
        }

        try {
            $db = \Config\Database::connect();
            $room = $this->roomModel->find($roomId);
            if (!$room) return;
            $classId = (int) $room['class_id'];

            // 1. Siswa di kelas ini
            $students = $db->table('students s')
                ->join('student_records sr', 'sr.student_id = s.id')
                ->join('users u', 'u.id = s.user_id')
                ->select('u.id, s.name, u.username, u.fullname')
                ->where('sr.class_id', $classId)
                ->get()
                ->getResultArray();

            // 2. Wali kelas & Guru mapel
            $class = $db->table('classes')->where('id', $classId)->get()->getRowArray();
            $teacherUserIds = [];
            if (!empty($class['teacher_id'])) {
                $wali = $db->table('teachers')->where('id', $class['teacher_id'])->get()->getRowArray();
                if (!empty($wali['user_id'])) {
                    $teacherUserIds[] = (int) $wali['user_id'];
                }
            }
            $mapelTeachers = $db->table('teaching_assignments ta')
                ->join('teachers t', 't.id = ta.teacher_id')
                ->select('t.user_id')
                ->where('ta.class_id', $classId)
                ->get()
                ->getResultArray();
            foreach ($mapelTeachers as $mt) {
                if (!empty($mt['user_id'])) {
                    $teacherUserIds[] = (int) $mt['user_id'];
                }
            }

            $teachers = [];
            if (!empty($teacherUserIds)) {
                $teachers = $db->table('users')
                    ->select('id, fullname, username')
                    ->whereIn('id', array_unique($teacherUserIds))
                    ->get()
                    ->getResultArray();
            }

            // 3. Admin & Kepsek
            $admins = $db->table('users')
                ->select('id, fullname, username')
                ->whereIn('role_id', [1, 2])
                ->get()
                ->getResultArray();

            $allMembers = array_merge($students, $teachers, $admins);

            // Lookup map
            $mentionMap = [];
            foreach ($allMembers as $m) {
                $uid = (int) $m['id'];
                if (!empty($m['username'])) {
                    $mentionMap[strtolower($m['username'])] = $uid;
                }
                $name = $m['name'] ?? $m['fullname'] ?? '';
                if ($name !== '') {
                    $slug = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace(' ', '_', $name)));
                    if ($slug !== '') {
                        $mentionMap[$slug] = $uid;
                    }
                    $firstWord = preg_replace('/[^a-z0-9_]/', '', strtolower(explode(' ', trim($name))[0]));
                    if ($firstWord !== '' && !isset($mentionMap[$firstWord])) {
                        $mentionMap[$firstWord] = $uid;
                    }
                }
            }

            foreach (array_unique($matches[1]) as $mentionKey) {
                $targetUserId = $mentionMap[strtolower($mentionKey)] ?? null;
                if ($targetUserId && $targetUserId !== $senderId) {
                    $this->mentionModel->insert([
                        'message_id' => $msgId,
                        'mentioned_user_id' => $targetUserId,
                        'is_read' => 0
                    ]);
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', '[Chat::processMentions] Gagal memproses mention: ' . $e->getMessage());
        }
    }

    // 🔹 Hapus pesan lama > 14 hari
    private function cleanOldMessages()
    {
        $limitDate = date('Y-m-d H:i:s', strtotime('-14 days'));

        // Cari pesan yang punya attachment untuk dihapus filenya
        $oldMessages = $this->messageModel->where('created_at <', $limitDate)->findAll();
        foreach ($oldMessages as $msg) {
            if (!empty($msg['attachment'])) {
                $filePath = UPLOAD_PATH . 'chat/' . $msg['attachment'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        return $this->messageModel->where('created_at <', $limitDate)->delete();
    }

    // 🔹 Clear mentions untuk class tertentu (AJAX)
    public function clearMentions($classId)
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $db = \Config\Database::connect();

        // Temukan room untuk class ini
        $room = $this->roomModel->where('class_id', $classId)->first();
        if (!$room) {
            return $this->response->setJSON(['status' => 'ok', 'cleared' => 0]);
        }

        // Buat subquery: semua message id di room ini
        $sub = $db->table('chat_messages')->select('id')->where('room_id', $room['id'])->getCompiledSelect();

        // Update chat_mentions yang message_id IN (subquery) dan untuk user ini
        $builder = $db->table('chat_mentions');
        $builder->where('mentioned_user_id', $userId);
        $builder->where('is_read', 0);
        $builder->where("message_id IN ($sub)", null, false);
        $updated = $builder->update(['is_read' => 1]);

        // Hitung affected rows
        $affected = $db->affectedRows();

        return $this->response->setJSON(['status' => 'ok', 'cleared' => (int) $affected]);
    }

    // 🔹 Refresh CSRF token (dipanggil JS saat token expired)
    public function csrf()
    {
        return $this->response->setJSON([
            'status' => 'ok',
            'csrf'   => csrf_hash(),
        ]);
    }

}
