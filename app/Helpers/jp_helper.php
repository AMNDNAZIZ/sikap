<?php

/**
 * JP (Jam Pelajaran) Helper
 * 
 * Helper untuk menghitung jumlah jam pelajaran berdasarkan durasi dan level sekolah.
 */

if (!function_exists('hitung_jp')) {
    /**
     * Hitung jumlah JP dari durasi menit berdasarkan level sekolah.
     * 
     * SD  (level 1): 1 JP = 35 menit → batas: ≤50 = 1JP, ≤85 = 2JP, >85 = 3JP
     * SMP (level 2): 1 JP = 40 menit → batas: ≤55 = 1JP, ≤105 = 2JP, >105 = 3JP
     * SMA (level 3): sama dengan SMP
     * 
     * Toleransi ~15 menit untuk istirahat yang menyebrangi slot.
     * Maksimal 3 JP per slot.
     *
     * @param int|float $menit Durasi dalam menit
     * @param mixed $schoolLevel Level sekolah (1/SD, 2/SMP, 3/SMA). Null = auto-detect.
     * @return int Jumlah JP (0-3)
     */
    function hitung_jp($menit, $schoolLevel = null): int
    {
        if ($menit <= 0) return 0;

        // Auto-detect school level jika tidak diberikan
        if ($schoolLevel === null) {
            static $cachedLevel = null;
            if ($cachedLevel === null) {
                try {
                    $db = \Config\Database::connect();
                    $school = $db->table('school_profile')->select('level')->get()->getRowArray();
                    $cachedLevel = $school['level'] ?? 2;
                } catch (\Exception $e) {
                    $cachedLevel = 2; // Default SMP
                }
            }
            $schoolLevel = $cachedLevel;
        }

        // SD (level 1): 1 JP = 35 menit
        if ($schoolLevel == 1 || strtoupper((string)$schoolLevel) === 'SD') {
            if ($menit <= 50) return 1;
            if ($menit <= 85) return 2;
            return 3;
        }

        // SMP/SMA (level 2/3): 1 JP = 40 menit
        if ($menit <= 55) return 1;
        if ($menit <= 105) return 2;
        return 3;
    }
}

if (!function_exists('hitung_jp_dari_waktu')) {
    /**
     * Hitung JP dari start_time dan end_time string (format H:i atau H:i:s).
     *
     * @param string $startTime
     * @param string $endTime
     * @param mixed $schoolLevel Level sekolah. Null = auto-detect.
     * @return int
     */
    function hitung_jp_dari_waktu(string $startTime, string $endTime, $schoolLevel = null): int
    {
        $menit = (strtotime($endTime) - strtotime($startTime)) / 60;
        return hitung_jp($menit, $schoolLevel);
    }
}
