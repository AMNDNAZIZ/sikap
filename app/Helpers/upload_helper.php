<?php

/**
 * Upload Helper — validasi dan simpan file gambar dengan aman.
 *
 * Mencegah file berbahaya (.php, .phtml, .js, dll.) tersimpan di folder upload
 * dengan cara:
 * 1. Memvalidasi MIME type berdasarkan konten file (bukan header/ekstensi yang bisa dipalsukan)
 * 2. Memvalidasi ekstensi client
 * 3. Memaksa ekstensi output dari MIME type — bukan dari nama file asli
 * 4. Menggunakan nama file random dari random_bytes (bukan getRandomName yang pakai ekstensi asli)
 */

/**
 * Validasi dan pindahkan file gambar ke folder upload.
 *
 * @param \CodeIgniter\HTTP\Files\UploadedFile $file      File dari request->getFile()
 * @param string                               $uploadDir Path folder tujuan (tanpa trailing slash)
 * @param int                                  $maxSizeMB Ukuran maksimal dalam MB (default 5)
 *
 * @return array ['success' => bool, 'name' => string|null, 'error' => string|null]
 */
function safe_upload_image($file, string $uploadDir, int $maxSizeMB = 5): array
{
    if (!$file || !$file->isValid() || $file->hasMoved()) {
        return ['success' => false, 'name' => null, 'error' => 'File tidak valid atau sudah dipindahkan.'];
    }

    // 1. Validasi MIME type dari konten file (bukan dari header yang bisa dipalsukan)
    $allowedMime = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $mime = $file->getMimeType();
    if (!in_array($mime, $allowedMime, true)) {
        return ['success' => false, 'name' => null,
            'error' => 'Tipe file tidak diizinkan. Hanya gambar JPG, PNG, GIF, atau WEBP.'];
    }

    // 2. Validasi ekstensi client sebagai lapisan kedua
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $clientExt  = strtolower($file->getClientExtension());
    if (!in_array($clientExt, $allowedExt, true)) {
        return ['success' => false, 'name' => null,
            'error' => 'Ekstensi file tidak diizinkan. Hanya .jpg, .jpeg, .png, .gif, .webp.'];
    }

    // 3. Validasi ukuran
    if ($file->getSize() > $maxSizeMB * 1024 * 1024) {
        return ['success' => false, 'name' => null,
            'error' => "Ukuran file terlalu besar. Maksimal {$maxSizeMB}MB."];
    }

    // 4. Buat nama file aman: ekstensi dari MIME (bukan dari nama file asli)
    $safeExt  = _mime_to_safe_ext($mime);
    $safeName = bin2hex(random_bytes(12)) . '_' . time() . '.' . $safeExt;

    // 5. Buat direktori jika belum ada
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 6. Pindahkan file
    try {
        $file->move($uploadDir, $safeName);
    } catch (\Throwable $e) {
        return ['success' => false, 'name' => null,
            'error' => 'Gagal memindahkan file: ' . $e->getMessage()];
    }

    return ['success' => true, 'name' => $safeName, 'error' => null];
}

/**
 * Hapus file gambar lama dari folder upload secara aman.
 *
 * @param string $uploadDir  Folder upload
 * @param string $filename   Nama file yang akan dihapus
 */
function safe_delete_upload(string $uploadDir, string $filename): void
{
    if (empty($filename)) return;

    // Pastikan tidak ada directory traversal
    $basename = basename($filename);
    $fullPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $basename;

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

/**
 * Konversi MIME type ke ekstensi file yang aman.
 * Tidak mempercayai ekstensi dari nama file asli.
 */
function _mime_to_safe_ext(string $mime): string
{
    return match ($mime) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png'               => 'png',
        'image/gif'               => 'gif',
        'image/webp'              => 'webp',
        default                   => 'jpg',
    };
}
