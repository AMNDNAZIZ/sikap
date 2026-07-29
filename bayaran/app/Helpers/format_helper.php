<?php

if (!function_exists('bulanIndo')) {
    function bulanIndo($n)
    {
        $bulan = [
            1 => 'Juli', 
            2 => 'Agustus', 
            3 => 'September', 
            4 => 'Oktober',
            5 => 'November', 
            6 => 'Desember', 
            7 => 'Januari', 
            8 => 'Februari',
            9 => 'Maret', 
            10 => 'April', 
            11 => 'Mei', 
            12 => 'Juni'
        ];
        return $bulan[$n] ?? '-';
    }
}

if (!function_exists('bulanPelajaran')) {
    function bulanPelajaran($bulanKalender)
    {
        // Konversi bulan kalender (1-12) ke urutan tahun pelajaran (1-12)
        $mapping = [
            7  => 1,  // Juli
            8  => 2,
            9  => 3,
            10 => 4,
            11 => 5,
            12 => 6,
            1  => 7,
            2  => 8,
            3  => 9,
            4  => 10,
            5  => 11,
            6  => 12, // Juni
        ];
        return $mapping[(int)$bulanKalender] ?? null;
    }
}


if (!function_exists('rupiah')) {
    function rupiah($value)
    {
        return 'Rp. ' . number_format($value, 0, ',', '.');
    }
}

if (!function_exists('safeUcfirst')) {
    function safeUcfirst(?string $str): string
    {
        return $str ? ucfirst($str) : '-';
    }
}