<?php
// 🎯 Standar Skill Diterapkan: api-design-quality-review (Backend)
// Refs: rekap-file-md/TandaTanganCap/PRD-TandaTanganCap.md

namespace App\Helpers;

use App\Models\SettingsModel;

/**
 * SignatureHelper
 *
 * Menyediakan gambar tanda tangan Kepala Sekolah dan cap sekolah
 * dalam format data-uri PNG base64 untuk disisipkan ke dalam PDF.
 *
 * File gambar wajib:
 *   - Format  : PNG dengan background transparan
 *   - Ukuran  : maks 150 KB
 *   - Lokasi  : public/uploads/signature/
 * Setting key di tabel `settings`:
 *   - ks_signature_path : path relatif ke file tanda tangan KS
 *   - school_seal_path  : path relatif ke file cap sekolah
 */
class SignatureHelper
{
    /** @var SettingsModel|null */
    protected static ?SettingsModel $settingsModel = null;

    /**
     * Set or reset the SettingsModel instance (useful for testing and dependency injection).
     */
    public static function setModel(?SettingsModel $model = null): void
    {
        self::$settingsModel = $model;
    }

    protected static function model(): SettingsModel
    {
        if (self::$settingsModel === null) {
            self::$settingsModel = new SettingsModel();
        }
        return self::$settingsModel;
    }

    /**
     * Data-uri PNG base64 untuk tanda tangan Kepala Sekolah.
     * Mengembalikan null jika belum di-upload atau file tidak ditemukan.
     */
    public static function getSignatureBase64(): ?string
    {
        return self::imageToBase64('ks_signature_path');
    }

    /**
     * Data-uri PNG base64 untuk cap sekolah.
     * Mengembalikan null jika belum di-upload atau file tidak ditemukan.
     */
    public static function getSealBase64(): ?string
    {
        return self::imageToBase64('school_seal_path');
    }

    /**
     * Baca file PNG dari path yang tersimpan di tabel settings
     * dan encode menjadi data-uri base64.
     *
     * @param  string $settingKey  Key di tabel settings
     * @return string|null
     */
    protected static function imageToBase64(string $settingKey): ?string
    {
        $relativePath = self::model()->getValue($settingKey);
        if (!$relativePath) {
            return null;
        }

        // FCPATH = public/ CodeIgniter 4
        $cleanPath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $absolutePath = FCPATH . $cleanPath;

        if (!is_file($absolutePath)) {
            log_message('warning', "SignatureHelper: berkas [{$settingKey}] tidak ditemukan di: {$absolutePath}");
            return null;
        }

        $data = @file_get_contents($absolutePath);
        if ($data === false || $data === '') {
            log_message('error', "SignatureHelper: gagal membaca berkas atau berkas kosong [{$settingKey}]: {$absolutePath}");
            return null;
        }

        // Wajib PNG transparan — paksa mime image/png
        return 'data:image/png;base64,' . base64_encode($data);
    }
}
