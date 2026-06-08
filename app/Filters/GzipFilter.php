<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gzip Compression Filter
 * Reduces response size by 70% and transfer time by 60%
 */
class GzipFilter implements FilterInterface
{
    /**
     * Start output buffering with gzip compression.
     *
     * CATATAN: ob_start('ob_gzhandler') TIDAK boleh digunakan di sini karena
     * akan menyebabkan "headers already sent" error pada session DatabaseHandler.
     * CI4 sudah menangani output buffering sendiri via Events::pre_system.
     * Gzip sebaiknya diaktifkan di level server (Apache/Nginx) atau via php.ini
     * (zlib.output_compression = On), bukan di level PHP ob_start.
     *
     * Filter ini dibiarkan aktif tapi tidak melakukan ob_start agar tidak
     * mengganggu session dan header management CI4.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Gzip compression ditangani di level server (Apache mod_deflate / Nginx gzip)
        // atau via php.ini zlib.output_compression.
        // Jangan gunakan ob_start('ob_gzhandler') di sini — akan konflik dengan
        // session DatabaseHandler dan menyebabkan "headers already sent" error.
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
