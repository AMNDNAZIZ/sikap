<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Auth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user = $session->get('user');

        // 🔒 EXAM SESSION PROTECTION: Extend session for students taking exam
        // Prevent logout during exam refresh (F5)
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Check if this is an exam-related request
        $isExamPath = (
            strpos($path, '/siswa/cbt/mulai/') !== false ||
            strpos($path, '/siswa/cbt/ujian/') !== false ||
            strpos($path, '/siswa/cbt/peraturan/') !== false ||
            strpos($path, '/siswa/cbt/saveAnswer') !== false ||
            strpos($path, '/siswa/cbt/saveAnswersBulk') !== false ||
            strpos($path, '/siswa/cbt/ping') !== false ||
            strpos($path, '/siswa/cbt/submit/') !== false
        );

        // 🔹 Request AJAX ringan & sering saat ujian (ping/save/heartbeat).
        // Untuk request ini kita HINDARI menulis session sama sekali dan
        // lepaskan lock session secepatnya (session()->close()) agar tidak
        // terjadi lock/write contention pada tabel ci_sessions di driver DB
        // ketika 100+ peserta menembak endpoint ini bersamaan.
        $isHighFreqExamPath = (
            strpos($path, '/siswa/cbt/saveAnswer') !== false ||
            strpos($path, '/siswa/cbt/saveAnswersBulk') !== false ||
            strpos($path, '/siswa/cbt/ping') !== false ||
            strpos($path, '/siswa/cbt/heartbeat') !== false
        );

        // Path ujian "berat" yang jarang dipanggil (buka halaman/refresh).
        // Hanya di sinilah kita perlu menandai proteksi sesi ujian.
        $isHeavyExamPath = $isExamPath && !$isHighFreqExamPath;

        // 🔹 FIX: Also protect post-exam pages (selesai, hasil) but with shorter timeout
        $isPostExamPath = (
            strpos($path, '/siswa/cbt/selesai/') !== false ||
            strpos($path, '/siswa/cbt/hasil/') !== false
        );

        if (!empty($user) && $session->get('logged_in')) {
            // 🔹 OPTIMASI: tulis tempdata proteksi ujian HANYA pada path berat
            // (mulai/ujian/peraturan/submit), dan HANYA bila belum ada / hampir
            // kedaluwarsa. Ini mencegah UPDATE ci_sessions di setiap request.
            if ($isHeavyExamPath) {
                if (!$session->getTempdata('exam_active')) {
                    // Set sekali; berlaku 4 jam. Tidak ditulis ulang tiap request.
                    $session->setTempdata('exam_active', true, 14400);
                }
            }

            // 🔹 FIX: Protect post-exam pages with shorter timeout (30 minutes)
            if ($isPostExamPath && !$session->getTempdata('post_exam_active')) {
                $session->setTempdata('post_exam_active', true, 1800); // 30 minutes
            }
        }

        // ✅ cek apakah sudah login
        if (empty($user) || !$session->get('logged_in')) {
            // 🔒 EXCEPTION: Allow exam page refresh if exam was active
            if ($isExamPath && $session->getTempdata('exam_active')) {
                // Session expired but exam was active - try to restore
                // This allows refresh without logout
                log_message('info', '[Auth] Exam session protection: allowing access despite session timeout');

                // Don't redirect to login, let the controller handle it
                // The controller will check if there's an active exam session in DB
                return; // 🔹 FIX: Return early to skip role check
            }

            // 🔹 FIX: Also allow post-exam pages if recently finished
            if ($isPostExamPath && $session->getTempdata('post_exam_active')) {
                log_message('info', '[Auth] Post-exam session protection: allowing access despite session timeout');
                return; // Allow access to results page
            }

            // Return 401 specifically for AJAX so frontend fetch() can intercept it
            if ($request->hasHeader('X-Requested-With') && $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return service('response')->setJSON(['error' => 'Sesi berakhir, silakan login ulang'])->setStatusCode(401);
            }

            return redirect()->to(base_url('login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        // ✅ cek role jika filter diberi argumen
        if ($arguments && $user) { // 🔹 FIX: Add null check for $user
            // $arguments biasanya array, ambil elemen pertama dan pecah jika ada koma
            $allowedRoles = is_array($arguments) ? explode(',', $arguments[0]) : [$arguments];

            // Ubah semua elemen menjadi integer agar cocok dengan tipe role_id
            $allowedRoles = array_map('intval', $allowedRoles);

            if (!in_array((int) $user['role_id'], $allowedRoles)) {
                return redirect()->to(base_url('dashboard'))->with('error', 'Anda tidak memiliki akses ke halaman ini.');
            }
        }

        // 💓 HEARTBEAT: Update last_activity with throttling to prevent excessive DB updates.
        // 🔹 OPTIMASI: untuk endpoint high-frequency saat ujian (ping/save/heartbeat)
        // JANGAN update last_activity di sini — endpoint tersebut sudah punya
        // mekanisme throttle sendiri (mis. update cbt_sessions.last_activity tiap 5 menit),
        // dan menghindari tulis tambahan mengurangi beban DB saat konkurensi tinggi.
        if ($user && !empty($user['id']) && !$isHighFreqExamPath) {
            helper('auth');
            update_user_last_activity_throttled($user['id'], 60); // throttle dilonggarkan 30 -> 60 detik
        }

        // 🔹 KUNCI OPTIMASI: lepaskan lock session (GET_LOCK) secepatnya untuk
        // request AJAX ringan & sering. Setelah otentikasi tervalidasi, session
        // tidak lagi diperlukan pada request ini, sehingga menutupnya lebih awal
        // membebaskan baris ci_sessions agar peserta lain tidak mengantre lock.
        if ($isHighFreqExamPath) {
            $session->close();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu implementasi
    }
}
