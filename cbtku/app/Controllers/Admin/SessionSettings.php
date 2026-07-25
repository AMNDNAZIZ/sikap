<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SessionSettings extends BaseController
{
    protected $configFile;

    public function __construct()
    {
        $this->configFile = WRITEPATH . 'session_config.json';
    }

    public function index()
    {
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses ditolak.');
        }

        // Default Config
        $config = [
            'driver'        => 'file',
            'savePath'      => 'tcp://127.0.0.1:6379',
            'redis_host'    => '127.0.0.1',
            'redis_port'    => 6379,
            'redis_password'=> '',
            'redis_database'=> 0,
            'cache_ttl'     => 3600,
        ];

        if (file_exists($this->configFile)) {
            $saved = json_decode(file_get_contents($this->configFile), true);
            if ($saved) {
                $config = array_merge($config, $saved);
            }
        }

        // Detect active cache handler
        $cacheHandler = \Config\Services::cache();
        $activeHandler = get_class($cacheHandler);

        // Get cache statistics
        $cacheStats = null;
        try {
            $cacheInfo = $cacheHandler->getCacheInfo();
            $cacheStats = is_array($cacheInfo) ? count($cacheInfo) : null;
        } catch (\Throwable $e) {
            $cacheStats = null;
        }

        $data = [
            'title'         => 'Pengaturan Sesi &amp; Cache',
            'config'        => $config,
            'activeHandler' => $activeHandler,
            'cacheStats'    => $cacheStats,
        ];

        return view('admin/settings/session', $data);
    }

    public function update()
    {
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses ditolak.');
        }

        $driver         = $this->request->getPost('driver');
        $savePath       = $this->request->getPost('savePath');
        $redisHost      = $this->request->getPost('redis_host') ?: '127.0.0.1';
        $redisPort      = (int) ($this->request->getPost('redis_port') ?: 6379);
        $redisPassword  = $this->request->getPost('redis_password') ?: '';
        $redisDatabase  = (int) ($this->request->getPost('redis_database') ?: 0);
        $cacheTtl       = (int) ($this->request->getPost('cache_ttl') ?: 3600);

        $allowedDrivers = ['file', 'database', 'redis'];
        if (!in_array($driver, $allowedDrivers)) {
            return redirect()->back()->with('error', 'Driver tidak valid.');
        }

        if ($redisPort < 1 || $redisPort > 65535) {
            return redirect()->back()->with('error', 'Port Redis tidak valid (1-65535).');
        }

        if ($cacheTtl < 60 || $cacheTtl > 86400) {
            return redirect()->back()->with('error', 'TTL Cache harus antara 60 hingga 86400 detik.');
        }

        $config = [
            'driver'         => $driver,
            'savePath'       => $driver === 'redis' ? ($savePath ?: "tcp://{$redisHost}:{$redisPort}") : '',
            'redis_host'     => $redisHost,
            'redis_port'     => $redisPort,
            'redis_password' => $redisPassword,
            'redis_database' => $redisDatabase,
            'cache_ttl'      => $cacheTtl,
        ];

        if (file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT))) {
            return redirect()->to(base_url('admin/settings/session'))
                ->with('success', 'Konfigurasi sesi dan cache berhasil diperbarui. Silakan login kembali jika sesi terputus.');
        } else {
            return redirect()->back()->with('error', 'Gagal menyimpan konfigurasi. Pastikan folder writable memiliki izin tulis.');
        }
    }

    public function flushCache()
    {
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }

        try {
            $cache = \Config\Services::cache();
            $result = $cache->clean();

            if ($result) {
                log_message('info', '[SessionSettings] Cache flushed by admin user_id=' . ($user['id'] ?? 'unknown'));
                return $this->response->setJSON(['success' => true, 'message' => 'Seluruh cache berhasil dihapus.']);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Gagal menghapus cache.']);
            }
        } catch (\Throwable $e) {
            log_message('error', '[SessionSettings] Cache flush error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
