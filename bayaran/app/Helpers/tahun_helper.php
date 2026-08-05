<?php

use App\Models\TahunAjaranModel;

if (!function_exists('getTahunAktif')) {
    function getTahunAktif(): array
    {
        $tahun = (new TahunAjaranModel())->where('aktif', 1)->first();

        return $tahun
            ? ['id' => $tahun['id'], 'nama' => $tahun['nama']]
            : ['id' => null, 'nama' => 'Belum diset'];
    }
}
