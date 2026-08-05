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

        $config = [
            'driver'        => 'file',
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

        $cacheHandler = \Config\Services::cache();
        $activeHandler = get_class($cacheHandler);

        $cacheStats = null;
        try {
            $cacheInfo = $cacheHandler->getCacheInfo();
            $cacheStats = is_array($cacheInfo) ? count($cacheInfo) : null;
        } catch (\Throwable $e) {
            $cacheStats = null;
        }

        return view('admin/settings/session', [
            'title'         => 'Pengaturan Cache CBT',
            'config'        => $config,
            'activeHandler' => $activeHandler,
            'cacheStats'    => $cacheStats,
        ]);
    }

    public function update()
    {
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses ditolak.');
        }

        $driver        = $this->request->getPost('driver');
        $redisHost     = $this->request->getPost('redis_host') ?: '127.0.0.1';
        $redisPort     = (int) ($this->request->getPost('redis_port') ?: 6379);
        $redisPassword = $this->request->getPost('redis_password') ?: '';
        $redisDatabase = (int) ($this->request->getPost('redis_database') ?: 0);
        $cacheTtl      = (int) ($this->request->getPost('cache_ttl') ?: 3600);

        if (!in_array($driver, ['file', 'database', 'redis'])) {
            return redirect()->back()->with('error', 'Driver tidak valid.');
        }

        if ($cacheTtl < 60 || $cacheTtl > 86400) {
            return redirect()->back()->with('error', 'TTL Cache harus antara 60 hingga 86400 detik.');
        }

        $config = [
            'driver'         => $driver,
            'redis_host'     => $redisHost,
            'redis_port'     => $redisPort,
            'redis_password' => $redisPassword,
            'redis_database' => $redisDatabase,
            'cache_ttl'      => $cacheTtl,
        ];

        if (file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT))) {
            return redirect()->back()->with('success', 'Konfigurasi cache berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Gagal menyimpan konfigurasi.');
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
            return $this->response->setJSON(['success' => $result, 'message' => $result ? 'Cache berhasil dihapus.' : 'Gagal menghapus cache.']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
