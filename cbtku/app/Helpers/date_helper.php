<?php

if (!function_exists('tanggal_indo')) {
    function tanggal_indo($tanggal)
    {
        $hari = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];

        $bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $timestamp = strtotime($tanggal);
        $namaHari = $hari[date('l', $timestamp)];
        $tgl = date('j', $timestamp);
        $namaBulan = $bulan[(int)date('n', $timestamp)];
        $tahun = date('Y', $timestamp);

        return "$namaHari, $tgl $namaBulan $tahun";
    }
}
