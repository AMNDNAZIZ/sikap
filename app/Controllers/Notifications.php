<?php

namespace App\Controllers;

use App\Models\NotificationModel;

/**
 * NotificationController
 *
 * Menangani semua interaksi frontend dengan sistem notifikasi:
 *   - GET  /notifications/count   → jumlah belum dibaca (JSON, polling navbar)
 *   - GET  /notifications/recent  → 10 notifikasi terbaru (JSON, dropdown)
 *   - GET  /notifications         → halaman daftar lengkap
 *   - POST /notifications/read/{id}     → tandai 1 notifikasi sudah dibaca
 *   - POST /notifications/read-all      → tandai semua sudah dibaca
 *
 * Controller ini dipakai oleh SEMUA role (admin, guru, siswa, ortu, dll.)
 * melalui satu set route yang sama — autentikasi dilindungi filter 'auth'.
 */
class Notifications extends BaseController
{
    protected NotificationModel $notifModel;

    public function __construct()
    {
        $this->notifModel = new NotificationModel();
    }

    // ─── Helper: ambil user_id dari session ──────────────────────────────
    private function userId(): int
    {
        return (int) (session()->get('user')['id'] ?? 0);
    }

    // ─── Helper: pastikan request adalah AJAX ────────────────────────────
    private function requireAjax(): bool
    {
        return $this->request->isAJAX()
            || str_contains($this->request->getHeaderLine('Accept'), 'application/json');
    }

    // =========================================================
    // GET /notifications/count
    // Dipanggil secara polling oleh JS navbar setiap 30 detik.
    // =========================================================
    public function count(): \CodeIgniter\HTTP\Response
    {
        $uid   = $this->userId();
        $count = $uid ? $this->notifModel->countUnread($uid) : 0;

        return $this->response->setJSON(['count' => $count]);
    }

    // =========================================================
    // GET /notifications/recent
    // Dipanggil saat dropdown dibuka — ambil 10 notif terbaru.
    // =========================================================
    public function recent(): \CodeIgniter\HTTP\Response
    {
        $uid   = $this->userId();
        $items = $uid ? $this->notifModel->getRecent($uid, 10) : [];

        // Tambahkan metadata icon & color agar frontend tidak perlu tahu logika ini
        foreach ($items as &$n) {
            $n['icon']  = NotificationModel::getIcon($n['type']);
            $n['color'] = NotificationModel::getColor($n['type']);
            $n['label'] = NotificationModel::getLabel($n['type']);
            $n['time_ago'] = $this->timeAgo($n['created_at']);
        }
        unset($n);

        return $this->response->setJSON([
            'items'  => $items,
            'unread' => $this->notifModel->countUnread($uid),
        ]);
    }

    // =========================================================
    // GET /notifications
    // Halaman penuh daftar notifikasi (dengan pager CI4).
    // =========================================================
    public function index(): string
    {
        $uid   = $this->userId();
        $items = $this->notifModel->getPaginated($uid, 20);
        $pager = $this->notifModel->pager;

        // Tambahkan metadata
        foreach ($items as &$n) {
            $n['icon']     = NotificationModel::getIcon($n['type']);
            $n['color']    = NotificationModel::getColor($n['type']);
            $n['label']    = NotificationModel::getLabel($n['type']);
            $n['time_ago'] = $this->timeAgo($n['created_at']);
        }
        unset($n);

        // Tentukan base URL kembali sesuai role
        $user    = session()->get('user');
        $roleId  = (int) ($user['role_id'] ?? 0);
        $backUrl = match (true) {
            in_array($roleId, [1, 2, 3, 7]) => base_url('dashboard'),
            in_array($roleId, [4, 5])        => base_url('dashboard'),
            default                          => base_url('dashboard'),
        };

        $schoolModel = new \App\Models\SchoolModel();
        return view('notifications/index', [
            'title'   => 'Notifikasi',
            'items'   => $items,
            'pager'   => $pager,
            'backUrl' => $backUrl,
            'school'  => $schoolModel->first() ?: [],
        ]);
    }

    // =========================================================
    // POST /notifications/read/{id}
    // Tandai satu notifikasi sebagai sudah dibaca & redirect ke URL-nya.
    // =========================================================
    public function markRead(int $id): \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\Response
    {
        $uid = $this->userId();
        $notif = $this->notifModel->find($id);

        if ($notif && (int) $notif['user_id'] === $uid) {
            $this->notifModel->markRead($id, $uid);
            $targetUrl = $notif['url'] ?? null;
        } else {
            $targetUrl = null;
        }

        // Jika dipanggil via AJAX, kembalikan JSON
        if ($this->requireAjax()) {
            return $this->response->setJSON(['success' => true]);
        }

        // Redirect ke URL tujuan notifikasi, atau ke halaman list
        return redirect()->to($targetUrl ?? base_url('notifications'));
    }

    // =========================================================
    // POST /notifications/read-all
    // Tandai semua notifikasi user sebagai sudah dibaca.
    // =========================================================
    public function markAllRead(): \CodeIgniter\HTTP\Response
    {
        $uid = $this->userId();
        $this->notifModel->markAllRead($uid);

        return $this->response->setJSON(['success' => true]);
    }

    // ─── Helper: waktu relatif (e.g. "5 menit lalu") ─────────────────────
    private function timeAgo(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }

        $diff = time() - strtotime($datetime);

        if ($diff < 60)         return 'Baru saja';
        if ($diff < 3600)       return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400)      return floor($diff / 3600) . ' jam lalu';
        if ($diff < 2592000)    return floor($diff / 86400) . ' hari lalu';
        if ($diff < 31536000)   return floor($diff / 2592000) . ' bulan lalu';

        return floor($diff / 31536000) . ' tahun lalu';
    }
}
