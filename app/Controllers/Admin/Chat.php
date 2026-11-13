<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ChatRoomModel;
use App\Models\ChatMessageModel;
use App\Models\ChatMentionModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use CodeIgniter\I18n\Time;

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

    // index: daftar kelas / entry point
    public function index()
    {
        $user = $this->currentUser();
        $classModel = new \App\Models\ClassModel();
        $db = \Config\Database::connect();

        if ($user['role_id'] == 1) {
            // Admin → lihat semua kelas aktif
            $classes = $classModel->getActiveClasses();
        } elseif ($user['role_id'] == 2) {
            // Kepala Sekolah → lihat semua kelas aktif
            $classes = $classModel->getActiveClasses();
        } elseif ($user['role_id'] == 3) {
            // Guru → cek apakah dia wali kelas
            $teacher = $db->table('teachers')
                ->where('user_id', $user['id'])
                ->get()
                ->getRowArray();

            $waliClass = [];
            if ($teacher) {
                $class = $db->table('classes')
                    ->where('teacher_id', $teacher['id']) // 🔹 pakai teacher.id
                    ->get()
                    ->getRowArray();

                if ($class) {
                    // Beri opsi masuk ke kelas walinya di atas daftar
                    $waliClass = [$class];
                }
            }

            // guru mapel (tetap ambil)
            $mapelClasses = $db->table('teaching_assignments ta')
                ->join('classes c', 'c.id = ta.class_id')
                ->select('c.id, c.name')
                ->where('ta.teacher_id', $teacher['id'] ?? 0)
                ->groupBy('c.id')
                ->get()
                ->getResultArray();

            // Gabungkan, unikkan berdasarkan ID
            $classes = array_merge($waliClass, $mapelClasses);
            $temp = [];
            foreach ($classes as $c) {
                $temp[$c['id']] = $c;
            }
            $classes = array_values($temp);
        } else {
            // Role lain → tidak ada kelas
            $classes = [];
        }

        return view('chat/index', [
            'title' => 'Chat Room Kelas',
            'classes' => $classes
        ]);
    }


    // 🔹 Buka room kelas
    public function room($classId)
    {
        $user = $this->currentUser();

        $room = $this->roomModel->where('class_id', $classId)->first();
        if (!$room) {
            $roomId = $this->roomModel->insert(['class_id' => $classId]);
            $room = $this->roomModel->find($roomId);
        }

        $db = \Config\Database::connect();

        // Cek akses
        if (!$this->canAccessClass($user, $classId)) {
            return redirect()->to(site_url('admin/chat'))->with('error', 'Anda tidak memiliki akses ke kelas ini.');
        }

        $class = $db->table('classes')->where('id', $classId)->get()->getRowArray();

        // Bersihkan pesan lama (otomatis)
        $this->cleanOldMessages();

        return view('chat/room', [
            'title' => 'Obrolan Kelas',
            'room' => $room,
            'class' => $class,
            'role' => 'admin',   // ✅ pastikan ada role
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


    // AJAX: jumlah mention unread (return JSON {count: n})
    // chat_mentions hanya berisi mention dari obrolan kelas
    // (staff chat pakai tabel staff_chat_mentions yang terpisah)
    public function mentions()
    {
        $userId = $this->currentUserId();
        $db = \Config\Database::connect();

        $count = $db->table('chat_mentions')
            ->where('mentioned_user_id', $userId)
            ->where('is_read', 0)
            ->countAllResults();

        return $this->response->setJSON(['count' => (int) $count]);
    }

    // AJAX: clear mentions untuk class tertentu (dipanggil saat buka room)
    public function clearMentions($classId)
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $db = \Config\Database::connect();

        // temukan room untuk class ini
        $room = $this->roomModel->where('class_id', $classId)->first();
        if (!$room) {
            return $this->response->setJSON(['status' => 'ok', 'cleared' => 0]);
        }

        // buat subquery: semua message id di room ini
        $sub = $db->table('chat_messages')->select('id')->where('room_id', $room['id'])->getCompiledSelect();

        // update chat_mentions yang message_id IN (subquery) dan untuk user ini
        $builder = $db->table('chat_mentions');
        $builder->where('mentioned_user_id', $userId);
        $builder->where('is_read', 0);
        $builder->where("message_id IN ($sub)", null, false);
        $updated = $builder->update(['is_read' => 1]);

        // update() mengembalikan true/false, kita hitung affectedRows jika ingin jumlah:
        $affected = $db->affectedRows();

        return $this->response->setJSON(['status' => 'ok', 'cleared' => (int) $affected]);
    }

    // helper: cek akses user ke class
    private function canAccessClass($user, $classId)
    {
        if (!$user)
            return false;
        $db = \Config\Database::connect();

        switch ($user['role_id']) {
            case 1: // Admin
            case 2: // Kepala Sekolah
                return true;

            case 3: // Guru
                // 🔹 cek apakah guru wali kelas
                $isWali = $db->table('classes')
                    ->where('teacher_id', $user['related_id'])
                    ->where('id', $classId)
                    ->countAllResults();
                if ($isWali > 0)
                    return true;

                // 🔹 cek guru mapel
                $isMapel = $db->table('teaching_assignments')
                    ->where('teacher_id', $user['related_id'])
                    ->where('class_id', $classId)
                    ->countAllResults();
                return $isMapel > 0;

            case 5: // Siswa
                $student = $db->table('students')
                    ->where('user_id', $user['id'])
                    ->get()
                    ->getRowArray();
                return $student && $student['class_id'] == $classId;

            case 4: // Orang Tua
            default:
        }
    }

    private function cleanOldMessages()
    {
        // Hapus pesan obrolan kelas yang lebih dari 7 hari
        $limitDate = date('Y-m-d H:i:s', strtotime('-7 days'));

        // Hapus file attachment terlebih dahulu
        $oldMessages = $this->messageModel->where('created_at <', $limitDate)->findAll();
        foreach ($oldMessages as $msg) {
            if (!empty($msg['attachment'])) {
                $filePath = UPLOAD_PATH . 'chat/' . $msg['attachment'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        $deleted = $this->messageModel->where('created_at <', $limitDate)->delete();
        if ($deleted) {
            log_message('info', 'Chat kelas: pesan lama (>7 hari) dihapus.');
        }
        return $deleted;
    }

}
