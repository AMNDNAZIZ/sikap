<?php

namespace Config;

/**
 * Sub Dimensi Profil Pelajar Pancasila
 * Berdasarkan Panduan Penilaian Rapor Kementerian
 */
class SubDimensiProfilLulusan
{
    /**
     * Mapping Dimensi Profil ke Sub Dimensi
     * Berdasarkan data resmi dari Kemendikbud
     * 
     * @return array
     */
    public static function getSubDimensi(): array
    {
        return [
            'Keimanan dan Ketakwaan Terhadap Tuhan YME' => [
                'Hubungan dengan Tuhan Yang Maha Esa',
                'Hubungan dengan sesama manusia',
                'Hubungan dengan lingkungan alam',
            ],
            
            'Kewargaan' => [
                'Kewargaan Lokal',
                'Kewargaan Nasional',
                'Kewargaan Global',
            ],
            
            'Penalaran Kritis' => [
                'Penyampaian Argumentasi',
                'Pengambilan Keputusan',
                'Penyelesaian Masalah',
            ],
            
            'Kreativitas' => [
                'Gagasan baru',
                'Fleksibilitas berpikir',
                'Karya',
            ],
            
            'Kemandirian' => [
                'Bertanggung jawab',
                'Kepemimpinan',
                'Pengembangan Diri',
            ],
            
            'Kolaborasi' => [
                'Peduli',
                'Berbagi',
                'Bekerja sama',
            ],
            
            'Kesehatan' => [
                'Hidup bersih dan sehat',
                'Kebugaran, kesehatan fisik dan kesehatan mental',
                'Kesehatan Lingkungan',
            ],
            
            'Komunikasi' => [
                'Menyimak',
                'Berbicara',
                'Membaca',
                'Menulis',
            ],
        ];
    }

    /**
     * Get sub dimensi for specific dimensi
     * 
     * @param string $dimensi
     * @return array
     */
    public static function getByDimensi(string $dimensi): array
    {
        $allSubDimensi = self::getSubDimensi();
        return $allSubDimensi[$dimensi] ?? [];
    }

    /**
     * Get all dimensi names
     * 
     * @return array
     */
    public static function getAllDimensi(): array
    {
        return array_keys(self::getSubDimensi());
    }

    /**
     * Validate if sub dimensi belongs to dimensi
     * 
     * @param string $dimensi
     * @param string $subDimensi
     * @return bool
     */
    public static function isValidSubDimensi(string $dimensi, string $subDimensi): bool
    {
        $subDimensiList = self::getByDimensi($dimensi);
        return in_array($subDimensi, $subDimensiList);
    }
}
