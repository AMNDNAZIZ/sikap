<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(base_url('dashboard'));
        }
        return view('auth/login');
    }

    public function attemptLogin()
    {
        $session = session();
        $model = new UserModel();

        $rules = [
            'username' => 'required',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->to(base_url('login'))->withInput()->with('error', 'Username dan password wajib diisi');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $forceLogin = $this->request->getPost('force_login');

        // 🔒 SECURITY: Rate limiting untuk prevent brute force
        if (!$this->checkRateLimit($username)) {
            return redirect()->to(base_url('login'))
                ->with('error', 'Terlalu banyak percobaan login. Silakan tunggu 5 menit.');
        }

        $user = $model->where('username', $username)->first();

        if ($user && password_verify($password, $user['password'])) {
            // 🔒 Reset rate limit on successful login
            $this->resetRateLimit($username);

            // 🔒 CEK SESSION LOCK (Pencegahan double login)
            if (!empty($user['active_session_id']) && !empty($user['last_activity'])) {
                $lastActivity = strtotime($user['last_activity']);
                $now = time();
                $threshold = 10 * 60; // 10 menit
                $timeSinceLastActivity = $now - $lastActivity;

                $currentIp = $this->request->getIPAddress();
                $currentUserAgent = $this->request->getUserAgent()->getAgentString();

                // 🔹 FIX: "Same-Device Takeover"
                // Trust the User Agent even if IP changed (WiFi roaming) for students
                $isSameDevice = ($user['last_ip'] === $currentIp && $user['user_agent'] === $currentUserAgent);
                
                if (!$isSameDevice && $user['user_agent'] === $currentUserAgent && (int)$user['role_id'] === 3) {
                    $isSameDevice = true;
                }

                if ($timeSinceLastActivity < $threshold && !$isSameDevice) {
                    // Session masih aktif di perangkat LAIN
                    if ($forceLogin === '1') {
                        log_message('info', "[Auth] Force login by user: {$username}, clearing session lock from other device");
                    } else {
                        log_message('warning', "[Auth] Session lock detected for user: {$username} from DIFFERENT device");
                        return redirect()->to(base_url('login'))
                            ->withInput()
                            ->with('error', 'Akun sedang digunakan di perangkat lain. Silakan logout dari perangkat sebelumnya atau tunggu 10 menit.')
                            ->with('show_force_login', true);
                    }
                } else if ($isSameDevice) {
                    log_message('info', "[Auth] Same device detected for user: {$username}, allowing seamless re-login");
                }
            }
            $sessionData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'role_id' => $user['role_id'],
                'related_id' => $user['related_id'] ?? null,
                'related_type' => $user['related_type'] ?? null,
            ];

            // 🔹 Jika Guru
            if ($user['role_id'] == 2) {
                $teacher = db_connect()
                    ->table('teachers')
                    ->where('user_id', $user['id'])
                    ->get()
                    ->getRowArray();

                if ($teacher) {
                    $sessionData['teacher_id'] = $teacher['id'];

                    // cari class berdasarkan teacher_id
                    $class = db_connect()
                        ->table('classes')
                        ->where('teacher_id', $teacher['id'])
                        ->get()
                        ->getRowArray();

                    if ($class) {
                        $sessionData['class_id'] = $class['id']; // simpan ke session
                    }
                }
            }

            // 🔹 Jika Siswa
            if ($user['role_id'] == 3) {
                $sessionData['student_id'] = $user['related_id'];
            }

            $session->set('user', $sessionData);
            $session->set('logged_in', true);

            // Update active session in DB
            $model->update($user['id'], [
                'active_session_id' => session_id(),
                'last_ip' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()->getAgentString(),
                'last_activity' => date('Y-m-d H:i:s')
            ]);

            return redirect()->to(base_url('dashboard'));
        }

        // 🔒 Increment failed attempts
        $this->incrementRateLimit($username);

        return redirect()->to(base_url('login'))->with('error', 'Username atau password salah');
    }

    /**
     * 🔒 SECURITY: Check rate limit for login attempts
     * Prevents brute force attacks
     */
    private function checkRateLimit($username)
    {
        $cache = \Config\Services::cache();
        $key = 'login_attempt_' . md5($username);
        $attempts = $cache->get($key) ?? 0;

        // Max 5 attempts in 5 minutes
        return $attempts < 5;
    }

    /**
     * 🔒 SECURITY: Increment failed login attempts
     */
    private function incrementRateLimit($username)
    {
        $cache = \Config\Services::cache();
        $key = 'login_attempt_' . md5($username);
        $attempts = $cache->get($key) ?? 0;

        // Save for 5 minutes (300 seconds)
        $cache->save($key, $attempts + 1, 300);
    }

    /**
     * 🔒 SECURITY: Reset rate limit on successful login
     */
    private function resetRateLimit($username)
    {
        $cache = \Config\Services::cache();
        $key = 'login_attempt_' . md5($username);
        $cache->delete($key);
    }

    public function logout()
    {
        $user = session()->get('user');
        if ($user) {
            $model = new UserModel();
            $model->update($user['id'], [
                'active_session_id' => null,
                'last_activity' => null
            ]);
        }
        session()->destroy();
        return redirect()->to(base_url('login'));
    }
}
