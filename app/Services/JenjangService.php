<?php

namespace App\Services;

class JenjangService
{
    /**
     * Konversi tingkat kelas (level 1-12) ke Fase Kurikulum Merdeka resmi
     *
     * SD:
     * - Level 1-2 : Fase A
     * - Level 3-4 : Fase B
     * - Level 5-6 : Fase C
     *
     * SMP:
     * - Level 7-9 : Fase D
     *
     * SMA:
     * - Level 10    : Fase E (Fondasi SMA)
     * - Level 11-12 : Fase F (Pengembangan & Peminatan SMA)
     */
    public static function getFaseByLevel(int $classLevel): string
    {
        if ($classLevel <= 0) return '-';
        if ($classLevel <= 2) return 'A'; // SD Kelas 1-2
        if ($classLevel <= 4) return 'B'; // SD Kelas 3-4
        if ($classLevel <= 6) return 'C'; // SD Kelas 5-6
        if ($classLevel <= 9) return 'D'; // SMP Kelas 7-9 (Fase D)
        if ($classLevel === 10) return 'E'; // SMA Kelas 10 (Fase E)
        if ($classLevel <= 12) return 'F'; // SMA Kelas 11-12 (Fase F)
        return '-';
    }

    /**
     * Dapatkan metadata rentang tingkat kelas berdasarkan level sekolah
     * Level 1: SD (1-6)
     * Level 2: SMP (7-9)
     * Level 3: SMA (10-12)
     */
    public static function getClassRange(int $schoolLevel): array
    {
        return match ($schoolLevel) {
            1 => ['min' => 1, 'max' => 6, 'name' => 'SD / Sederajat', 'range' => '1-6'],
            2 => ['min' => 7, 'max' => 9, 'name' => 'SMP / Sederajat', 'range' => '7-9'],
            3 => ['min' => 10, 'max' => 12, 'name' => 'SMA / Sederajat', 'range' => '10-12'],
            default => ['min' => 1, 'max' => 6, 'name' => 'SD / Sederajat', 'range' => '1-6'],
        };
    }

    /**
     * Standar durasi jam pelajaran (menit per JP) resmi di Indonesia
     * SD: 35 menit
     * SMP: 40 menit
     * SMA: 45 menit
     */
    public static function getMinutesPerJp(int $schoolLevel): int
    {
        return match ($schoolLevel) {
            1 => 35,
            2 => 40,
            3 => 45,
            default => 40,
        };
    }
}

