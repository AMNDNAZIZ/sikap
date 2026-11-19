<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (!function_exists('purify_html')) {
    /**
     * Membersihkan konten HTML dari potensi Stored XSS menggunakan HtmlSanitizer (HTMLPurifier).
     *
     * @param string|null $html Konten HTML yang akan dibersihkan
     * @return string Konten HTML aman atau plain text escaped (fail-closed)
     */
    function purify_html(?string $html): string
    {
        return \App\Libraries\HtmlSanitizer::clean($html);
    }
}

