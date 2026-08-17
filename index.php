<?php

/*
 *---------------------------------------------------------------
 * ROOT INDEX.PHP — PROXY KE PUBLIC/INDEX.PHP
 *---------------------------------------------------------------
 *
 * File ini menjadi entry point saat aplikasi berjalan dari root
 * (baik localhost/siakad/ maupun hosting public_html/).
 *
 * Ia mendelegasikan eksekusi ke public/index.php agar FCPATH
 * selalu menunjuk ke direktori public/, sehingga:
 * - Upload file tersimpan di public/uploads/ (bukan root)
 * - Asset (CSS/JS/img) ditemukan dengan benar
 * - Behavior identik di semua environment
 *
 * JANGAN definisikan FCPATH di sini.
 */

require __DIR__ . '/public/index.php';
