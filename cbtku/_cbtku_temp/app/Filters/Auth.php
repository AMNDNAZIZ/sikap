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

        // 🔹 FIX: Also protect post-exam pages (selesai, hasil) but with shorter timeout
        $isPostExamPath = (
            strpos($path, '/siswa/cbt/selesai/') !== false ||
            strpos($path, '/siswa/cbt/hasil/') !== false
        );

        if (!empty($user) && $session->get('logged_in')) {
            if ($isExamPath) {
                // Extend session lifetime to 4 hours for exam
                $session->setTempdata('exam_active', true, 14400); // 4 hours

                // Mark session to prevent timeout
                $session->markAsFlashdata('exam_protection');
            }

            // 🔹 FIX: Protect post-exam pages with shorter timeout (30 minutes)
            if ($isPostExamPath) {
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

        // 💓 HEARTBEAT: Update last_activity with throttling to prevent excessive DB updates
        if ($user && !empty($user['id'])) {
            helper('auth');
            update_user_last_activity_throttled($user['id'], 30); // 30 seconds throttle
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu implementasi
    }
}
