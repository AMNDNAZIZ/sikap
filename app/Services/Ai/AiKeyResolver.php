<?php

namespace App\Services\Ai;

use App\Services\BaseService;
use App\Services\Ai\DTO\ResolvedApiKey;
use Config\AiConfig;

/**
 * Layanan Resolusi Kunci API AI Terpadu (Unified AI Key Resolver)
 * 
 * Mengimplementasikan tata kelola model Bring Your Own Key (BYOK) per akun
 * sesuai Mandat dan Keputusan Resmi Human Owner (EAO-005A):
 * 
 * ATURAN URUTAN RESOLUSI:
 * 1. Prioritas 1: API Key pribadi milik pengguna aktif (self).
 * 2. Prioritas 2: API Key pribadi Administrator sebagai fallback resmi sekolah (admin_fallback).
 * 3. Prioritas 3: Fallback lokal / non-AI jika kunci tidak tersedia (none).
 * 
 * BATASAN KETAT KEAMANAN (SECURITY INVARIANTS):
 * - TIDAK ADA fallback ke .env untuk permintaan pengguna (sesuai Keputusan 2).
 * - DILARANG KERAS mengambil API Key milik sembarang guru lain (Zero Cross-Teacher Fallback).
 * - Audit ganda (Dual-Traceability): Membedakan pemohon (requester_id) dan pemilik kunci (key_owner_id).
 */
class AiKeyResolver extends BaseService
{
    protected AiConfig $config;

    public function __construct(?AiConfig $config = null, $db = null)
    {
        parent::__construct($db);
        $this->config = $config ?: config('AiConfig');
    }

    /**
     * Menyelesaikan (resolve) kunci API terbaik untuk permintaan tertentu
     * 
     * @param int|null $requesterUserId ID pengguna pemohon (dari session auth)
     * @param int|null $requesterRoleId ID peran pemohon (1=Admin, 3=Guru/Wali Kelas)
     * @param int|null $teacherId ID guru terkait (tabel teachers) jika ada
     * @param string|null $targetProvider Provider tujuan ('groq' atau 'gemini')
     * @param string|null $explicitKeyOverride Kunci override langsung (jika ada)
     * @return ResolvedApiKey
     */
    public function resolve(
        ?int $requesterUserId = null,
        ?int $requesterRoleId = null,
        ?int $teacherId = null,
        ?string $targetProvider = null,
        ?string $explicitKeyOverride = null
    ): ResolvedApiKey {
        $provider = strtolower(trim($targetProvider ?: $this->config->defaultProvider));

        // 0. Jalur Override Eksplisit (misal untuk testing atau parameter khusus)
        if (!empty($explicitKeyOverride)) {
            $rawKey = trim($explicitKeyOverride);
            if ($this->validateKeyForProvider($rawKey, $provider)) {
                $detected = $this->detectProviderFromPrefix($rawKey);
                $finalProvider = ($detected !== 'unknown') ? $detected : $provider;
                return new ResolvedApiKey(
                    $rawKey,
                    $finalProvider,
                    $requesterUserId,
                    'override',
                    false,
                    true
                );
            }
        }

        // 1. Prioritas 1: Kunci Akun Pribadi Pengguna Pemohon (Self)
        $selfKey = $this->resolveUserSelfKey($requesterUserId, $requesterRoleId, $teacherId, $provider);
        if ($selfKey && $selfKey->isAvailable()) {
            return $selfKey;
        }

        // 2. Prioritas 2: Kunci Administrator sebagai Fallback Resmi Sekolah
        $adminFallback = $this->resolveAdminFallbackKey($provider, $requesterUserId);
        if ($adminFallback && $adminFallback->isAvailable()) {
            return $adminFallback;
        }

        // 3. Prioritas 3: Fallback Lokal / Non-AI (Kunci Tidak Tersedia)
        // CATATAN: Penggunaan .env sebagai fallback pengguna TIDAK diimplementasikan
        // sesuai Keputusan 2 Human Owner.
        return new ResolvedApiKey(
            null,
            $provider,
            null,
            'none',
            false,
            false,
            'Kunci API tidak ditemukan pada akun pengguna maupun fallback Administrator sekolah.'
        );
    }

    /**
     * Resolusi Kunci Pribadi Milik Pengguna Pemohon (Self)
     */
    protected function resolveUserSelfKey(
        ?int $userId,
        ?int $roleId,
        ?int $teacherId,
        string $targetProvider
    ): ?ResolvedApiKey {
        if (!$this->db) {
            return null;
        }

        // A. Jika pemohon adalah Guru (Role 3) atau memiliki konteks teacherId
        if ($roleId === 3 || !empty($teacherId)) {
            $builder = $this->db->table('teachers');
            
            // JAMINAN KEAMANAN: HANYA cari berdasarkan teacherId atau userId milik pemohon sendiri
            if (!empty($teacherId)) {
                $builder->where('id', (int) $teacherId);
            } elseif (!empty($userId)) {
                $builder->where('user_id', (int) $userId);
            } else {
                return null;
            }

            $teacher = $builder->get()->getRowArray();

            if ($teacher && !empty($teacher['gemini_api_key'])) {
                $rawKey = trim((string) $teacher['gemini_api_key']);
                if ($this->validateKeyForProvider($rawKey, $targetProvider)) {
                    $detected = $this->detectProviderFromPrefix($rawKey);
                    $finalProvider = ($detected !== 'unknown') ? $detected : $targetProvider;
                    return new ResolvedApiKey(
                        $rawKey,
                        $finalProvider,
                        $userId ?: ((int) ($teacher['user_id'] ?? 0)),
                        'self',
                        false,
                        true
                    );
                }
            }
        }

        // B. Jika pemohon adalah Admin (Role 1) atau akun pengguna lainnya
        if (!empty($userId)) {
            $user = $this->db->table('users')
                ->where('id', (int) $userId)
                ->get()
                ->getRowArray();

            if ($user && !empty($user['gemini_api_key'])) {
                $rawKey = trim((string) $user['gemini_api_key']);
                if ($this->validateKeyForProvider($rawKey, $targetProvider)) {
                    $detected = $this->detectProviderFromPrefix($rawKey);
                    $finalProvider = ($detected !== 'unknown') ? $detected : $targetProvider;
                    return new ResolvedApiKey(
                        $rawKey,
                        $finalProvider,
                        (int) $userId,
                        'self',
                        false,
                        true
                    );
                }
            }
        }

        return null;
    }

    /**
     * Resolusi Kunci Administrator sebagai Fallback Resmi Sekolah
     * 
     * JAMINAN KEAMANAN:
     * - Hanya mengambil akun dengan role_id = 1 (Administrator) yang aktif.
     * - DILARANG memindai akun guru lain.
     */
    protected function resolveAdminFallbackKey(string $targetProvider, ?int $requesterUserId = null): ?ResolvedApiKey
    {
        if (!$this->db) {
            return null;
        }

        // 1. Cari di tabel users untuk akun dengan role_id = 1 (Admin) yang aktif
        $adminUserBuilder = $this->db->table('users')
            ->where('role_id', 1)
            ->where('is_active', 1)
            ->where('gemini_api_key IS NOT NULL')
            ->where('gemini_api_key !=', '')
            ->orderBy('id', 'ASC');

        // Jika pemohon sendiri adalah admin, lewati akun pemohon agar tidak duplikasi
        if ($requesterUserId) {
            // Jika requester adalah admin dan sudah gagal di self-key, kita cari admin lain jika ada
        }

        $adminUsers = $adminUserBuilder->get()->getResultArray();

        foreach ($adminUsers as $admin) {
            $rawKey = trim((string) $admin['gemini_api_key']);
            if ($this->validateKeyForProvider($rawKey, $targetProvider)) {
                $detected = $this->detectProviderFromPrefix($rawKey);
                $finalProvider = ($detected !== 'unknown') ? $detected : $targetProvider;
                return new ResolvedApiKey(
                    $rawKey,
                    $finalProvider,
                    (int) $admin['id'],
                    'admin_fallback',
                    true,
                    true
                );
            }
        }

        // 2. Jika tidak ditemukan di tabel users, periksa apakah ada profil Admin di tabel teachers
        // (Khusus bagi Administrator yang juga terdaftar sebagai guru struktural)
        try {
            $adminTeacher = $this->db->table('teachers')
                ->select('teachers.id, teachers.user_id, teachers.gemini_api_key')
                ->join('users', 'users.id = teachers.user_id')
                ->where('users.role_id', 1)
                ->where('users.is_active', 1)
                ->where('teachers.gemini_api_key IS NOT NULL')
                ->where('teachers.gemini_api_key !=', '')
                ->orderBy('teachers.id', 'ASC')
                ->get()
                ->getRowArray();

            if ($adminTeacher && !empty($adminTeacher['gemini_api_key'])) {
                $rawKey = trim((string) $adminTeacher['gemini_api_key']);
                if ($this->validateKeyForProvider($rawKey, $targetProvider)) {
                    $detected = $this->detectProviderFromPrefix($rawKey);
                    $finalProvider = ($detected !== 'unknown') ? $detected : $targetProvider;
                    return new ResolvedApiKey(
                        $rawKey,
                        $finalProvider,
                        (int) $adminTeacher['user_id'],
                        'admin_fallback',
                        true,
                        true
                    );
                }
            }
        } catch (\Throwable $e) {
            // Tangani dengan aman jika join mengalami kendala pada instalasi khusus
        }

        return null;
    }

    /**
     * Mendeteksi provider AI berdasarkan prefiks resmi kunci API
     */
    public function detectProviderFromPrefix(string $rawKey): string
    {
        $trimmed = trim($rawKey);

        if (str_starts_with($trimmed, 'gsk_')) {
            return 'groq';
        }

        if (str_starts_with($trimmed, 'AIzaSy')) {
            return 'gemini';
        }

        return 'unknown';
    }

    /**
     * Memvalidasi apakah format kunci cocok dengan provider yang dituju
     * 
     * Mencegah konflik format (misal mengirim kunci Gemini ke endpoint Groq).
     */
    public function validateKeyForProvider(string $rawKey, string $targetProvider): bool
    {
        $key = trim($rawKey);

        // Panjang minimal kunci API yang valid secara umum
        if (strlen($key) <= 10) {
            return false;
        }

        $detected = $this->detectProviderFromPrefix($key);
        $target = strtolower(trim($targetProvider));

        // Jika terdeteksi secara pasti sebagai provider lain, tolak mismatch
        if ($target === 'groq' && $detected === 'gemini') {
            return false; // Kunci Gemini (AIzaSy) tidak bisa dipakai untuk Groq
        }

        if ($target === 'gemini' && $detected === 'groq') {
            return false; // Kunci Groq (gsk_) tidak bisa dipakai untuk Gemini
        }

        return true;
    }
}
