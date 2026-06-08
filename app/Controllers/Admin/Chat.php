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
            // Admin → lihat semua kelas
            $classes = $classModel->findAll();
        } elseif ($user['role_id'] == 2) {
            // Kepala Sekolah → lihat semua kelas
            $classes = $classModel->findAll();
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
                ->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
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
            'room_id' => $this->request->getPost('room_id'),
            'user_id' => $user['id'],
            'message' => $this->request->getPost('message'),
            'attachment' => $attachment,
            'reply_to' => $this->request->getPost('reply_to') ?: 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->messageModel->insert($data);
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'ok']);
            }
        } catch (\Exception $e) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)
                    ->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back();
    }

    // 🔹 Ambil isi pesan (AJAX)
    public function fetch($roomId)
    {
        $db = \Config\Database::connect();
        $currentUserId = $this->currentUserId();

        $messages = $db->table('chat_messages m')
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
            ->where('m.room_id', $roomId)
            ->orderBy('m.created_at', 'ASC')
            ->get()
            ->getResultArray();

        return view('chat/_messages', [
            'messages' => $messages,
            'currentUserId' => $currentUserId,
        ]);
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
