<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EbookModel;
use App\Libraries\EbookAccessService;

class EbookFile extends BaseController
{
    protected $ebookModel;
    protected $accessService;

    public function __construct()
    {
        $this->ebookModel = new EbookModel();
        $this->accessService = new EbookAccessService();
    }

    /**
     * Serve PDF inline (for viewer)
     */
    public function read($id)
    {
        $user = session()->get('user');
        $book = $this->ebookModel->find($id);

        if (!$book) {
            return $this->response->setStatusCode(404)->setBody('Buku tidak ditemukan.');
        }

        // Validate access
        if (!$this->accessService->canAccess($user, $book)) {
            log_message('warning', "Unauthorized ebook access attempt: user #{$user['id']} book #{$id}");
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        // Validate filename
        if (!$this->accessService->isValidFilename($book['filename'])) {
            log_message('warning', "Path traversal detected in ebook filename: {$book['filename']} by user #{$user['id']}");
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        $filePath = WRITEPATH . 'uploads/ebooks/' . $book['filename'];

        if (!is_file($filePath)) {
            log_message('error', "Ebook file not found: #{$id} filename={$book['filename']}");
            return $this->response->setStatusCode(404)->setBody('Buku tidak dapat ditampilkan. File tidak tersedia.');
        }

        // Serve inline
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($book['filename']) . '"')
            ->setHeader('Content-Length', (string) filesize($filePath))
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody(file_get_contents($filePath));
    }

    /**
     * Serve PDF as attachment (download)
     */
    public function download($id)
    {
        $user = session()->get('user');
        $book = $this->ebookModel->find($id);

        if (!$book) {
            return $this->response->setStatusCode(404)->setBody('Buku tidak ditemukan.');
        }

        // Validate access
        if (!$this->accessService->canAccess($user, $book)) {
            log_message('warning', "Unauthorized ebook download attempt: user #{$user['id']} book #{$id}");
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        // Validate filename
        if (!$this->accessService->isValidFilename($book['filename'])) {
            log_message('warning', "Path traversal detected in ebook download: {$book['filename']} by user #{$user['id']}");
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        $filePath = WRITEPATH . 'uploads/ebooks/' . $book['filename'];

        if (!is_file($filePath)) {
            log_message('error', "Ebook file not found for download: #{$id} filename={$book['filename']}");
            return $this->response->setStatusCode(404)->setBody('File tidak tersedia.');
        }

        // Sanitize filename for download
        $downloadName = $this->accessService->sanitizeFilename($book['title']);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $downloadName . '"')
            ->setHeader('Content-Length', (string) filesize($filePath))
            ->setBody(file_get_contents($filePath));
    }
}
