<?php

namespace App\Libraries;

/**
 * Service sanitasi HTML berbasis HTMLPurifier untuk ESPATA.
 *
 * Menerapkan arsitektur Dual-Track Managed Dependency:
 * 1. Prioritas 1: Memuat via Composer autoloader (vendor/autoload.php).
 * 2. Fallback: Memuat via bundle runtime mandiri (app/ThirdParty/HTMLPurifier/library/HTMLPurifier.auto.php).
 *
 * Menjamin prinsip fail-closed: jika library tidak dapat dimuat atau sanitasi
 * menghasilkan error/exception, konten dikembalikan dalam bentuk teks terekskapi aman
 * (nl2br(esc($content, 'html'))) dan BUKAN HTML mentah ataupun strip_tags().
 */
class HtmlSanitizer
{
    /**
     * Cache instance HTMLPurifier
     */
    private static ?\HTMLPurifier $purifier = null;

    /**
     * Sumber autoloader yang aktif ('vendor', 'thirdparty', atau null jika gagal)
     */
    private static ?string $loadedSource = null;

    /**
     * Bersihkan konten HTML dari potensi Stored XSS sesuai allowlist Summernote agenda.
     *
     * @param string|null $dirty Konten HTML mentah dari input pengguna
     * @return string Konten HTML tersanitasi aman, atau plain-text escaped jika fail-closed
     */
    public static function clean(?string $dirty): string
    {
        if ($dirty === null || $dirty === '') {
            return '';
        }

        $purifier = self::getPurifier();
        if ($purifier === null) {
            return self::failClosed($dirty);
        }

        try {
            return $purifier->purify($dirty);
        } catch (\Throwable $e) {
            self::logError('HtmlSanitizer purify exception: ' . $e->getMessage());
            return self::failClosed($dirty);
        }
    }

    /**
     * Dapatkan instance HTMLPurifier yang telah terkonfigurasi.
     */
    public static function getPurifier(): ?\HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        if (!self::ensureLoaded()) {
            return null;
        }

        try {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

            // Allowlist elemen HTML terverifikasi untuk konten Summernote agenda (EAO-101)
            $config->set('HTML.AllowedElements', [
                'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'ul', 'ol', 'li', 'blockquote',
                'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a'
            ]);

            // Allowlist atribut HTML
            $config->set('HTML.AllowedAttributes', [
                'a.href', 'a.target', 'a.rel', 'a.title',
                'table.class', 'th.class', 'td.class',
                'th.colspan', 'td.colspan', 'th.rowspan', 'td.rowspan'
            ]);

            // Restriksi protokol URI: hanya http, https, dan mailto
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
                'mailto' => true
            ]);

            // Batasi target tautan dan amankan atribut rel
            $config->set('Attr.AllowedFrameTargets', ['_blank']);

            // Konfigurasi direktori cache untuk shared hosting (WRITEPATH/cache/htmlpurifier)
            $cacheDir = defined('WRITEPATH')
                ? rtrim(WRITEPATH, '\\/') . '/cache/htmlpurifier'
                : dirname(__DIR__, 2) . '/writable/cache/htmlpurifier';

            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }

            if (is_dir($cacheDir) && is_writable($cacheDir)) {
                $config->set('Cache.SerializerPath', $cacheDir);
            } else {
                // Shared hosting fallback jika direktori cache tidak memiliki izin tulis
                $config->set('Cache.DefinitionImpl', null);
            }

            self::$purifier = new \HTMLPurifier($config);
            return self::$purifier;
        } catch (\Throwable $e) {
            self::logError('HtmlSanitizer configuration exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Memastikan library HTMLPurifier termuat via dual-source loader.
     */
    public static function ensureLoaded(): bool
    {
        if (class_exists('HTMLPurifier', false)) {
            return true;
        }

        $rootPath = defined('ROOTPATH') ? ROOTPATH : dirname(__DIR__, 2) . '/';
        $appPath = defined('APPPATH') ? APPPATH : dirname(__DIR__) . '/';

        // Prioritas 1: Composer Autoloader
        $vendorAutoload = rtrim($rootPath, '\\/') . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
            if (class_exists('HTMLPurifier', true)) {
                self::$loadedSource = 'vendor';
                return true;
            }
        }

        // Fallback Prioritas 2: Standalone ThirdParty Autoloader (untuk updater klien)
        $thirdPartyAuto = rtrim($appPath, '\\/') . '/ThirdParty/HTMLPurifier/library/HTMLPurifier.auto.php';
        if (file_exists($thirdPartyAuto)) {
            require_once $thirdPartyAuto;
            if (class_exists('HTMLPurifier', true)) {
                self::$loadedSource = 'thirdparty';
                return true;
            }
        }

        self::logError('HtmlSanitizer failed to load HTMLPurifier from both vendor and ThirdParty sources.');
        return false;
    }

    /**
     * Mengembalikan sumber autoloader yang aktif ('vendor', 'thirdparty', atau null).
     */
    public static function getLoadedSource(): ?string
    {
        return self::$loadedSource;
    }

    /**
     * Reset state internal (digunakan untuk testing).
     */
    public static function reset(): void
    {
        self::$purifier = null;
        self::$loadedSource = null;
    }

    /**
     * Perilaku fail-closed: konversi teks ke escaped plain-text dengan mempertahankan jeda baris.
     * DILARANG KERAS menggunakan strip_tags() sebagai fallback keamanan!
     *
     * @param string|null $content
     * @return string
     */
    public static function failClosed(?string $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        if (function_exists('esc')) {
            $escaped = esc($content, 'html');
        } else {
            $escaped = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return nl2br($escaped);
    }

    /**
     * Pencatatan log error internal yang aman.
     */
    private static function logError(string $message): void
    {
        if (function_exists('log_message') && defined('ENVIRONMENT')) {
            log_message('error', '[HtmlSanitizer] ' . $message);
        } else {
            error_log('[HtmlSanitizer] ' . $message);
        }
    }
}
