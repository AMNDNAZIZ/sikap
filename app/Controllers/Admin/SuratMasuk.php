<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\IncomingLetterModel;
use App\Models\SchoolModel;

class SuratMasuk extends BaseController
{
    protected IncomingLetterModel $model;

    public const CATEGORIES = [
        'dinas_pendidikan'      => 'Dinas Pendidikan',
        'orang_tua'             => 'Orang Tua / Wali',
        'instansi_pemerintah'   => 'Instansi Pemerintah',
        'swasta'                => 'Swasta / LSM',
        'lainnya'               => 'Lainnya',
    ];

    public function __construct()
    {
        $this->model = new IncomingLetterModel();
    }

    /**
     * Dashboard / Index surat masuk
     */
    public function index()
    {
        $params = [
            'search'    => $this->request->getGet('search') ?? '',
            'date_from' => $this->request->getGet('date_from') ?? '',
            'date_to'   => $this->request->getGet('date_to') ?? '',
            'category'  => $this->request->getGet('category') ?? '',
            'page'      => (int) ($this->request->getGet('page') ?? 1),
            'limit'     => 50,
        ];

        $result = $this->model->getFiltered($params);
        $stats  = $this->model->getStats();

        return view('admin/surat/masuk/index', [
            'title'      => 'Surat Masuk',
            'letters'    => $result['data'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'limit'      => $result['limit'],
            'totalPages' => ceil($result['total'] / max(1, $result['limit'])),
            'stats'      => $stats,
            'filter'     => $params,
            'categories' => self::CATEGORIES,
        ]);
    }

    /**
     * Form tambah surat masuk (dengan Tesseract.js OCR client-side)
     */
    public function create()
    {
        return view('admin/surat/masuk/create', [
            'title'      => 'Catat Surat Masuk',
            'categories' => self::CATEGORIES,
        ]);
    }

    /**
     * Simpan surat masuk
     */
    public function store()
    {
        $rules = [
            'sender_name' => 'required|min_length[3]|max_length[255]',
            'subject'     => 'required|min_length[3]',
            'received_at' => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId    = session()->get('user')['id'] ?? null;
        $scanPath  = null;
        $scanUrl   = null;
        $fileType  = null;
        $fileSize  = null;

        // Handle file upload
        $file = $this->request->getFile('scan_file');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
            if (! in_array($file->getMimeType(), $allowed)) {
                return redirect()->back()->withInput()->with('error', 'Format file tidak diizinkan. Gunakan PDF, JPG, PNG, atau WebP.');
            }
            if ($file->getSize() > 15 * 1024 * 1024) {
                return redirect()->back()->withInput()->with('error', 'Ukuran file melebihi batas 15 MB.');
            }

            $dir      = 'surat_masuk/' . date('Y') . '/' . date('m') . '/';
            $fullDir  = WRITEPATH . 'uploads/' . $dir;
            if (! is_dir($fullDir)) {
                mkdir($fullDir, 0775, true);
            }

            $newName  = $file->getRandomName();
            $file->move($fullDir, $newName);

            $scanPath = $dir . $newName;
            $scanUrl  = base_url('uploads/' . $scanPath);
            $fileType = strtolower($file->getClientExtension());
            $fileSize = $file->getSize();
        }

        $ocrConfidence = $this->request->getPost('ocr_confidence');
        $ocrRawText    = $this->request->getPost('ocr_raw_text');

        $data = [
            'letter_number'   => $this->request->getPost('letter_number') ?: null,
            'sender_name'     => $this->request->getPost('sender_name'),
            'sender_agency'   => $this->request->getPost('sender_agency') ?: null,
            'subject'         => $this->request->getPost('subject'),
            'received_at'     => $this->request->getPost('received_at'),
            'letter_date'     => $this->request->getPost('letter_date') ?: null,
            'letter_category' => $this->request->getPost('letter_category') ?: null,
            'disposition'     => $this->request->getPost('disposition') ?: null,
            'scan_url'        => $scanUrl,
            'scan_path'       => $scanPath,
            'file_type'       => $fileType,
            'file_size_bytes' => $fileSize,
            'ocr_processed'   => ! empty($ocrConfidence) ? 1 : 0,
            'ocr_confidence'  => ! empty($ocrConfidence) ? (float) $ocrConfidence : null,
            'ocr_raw_text'    => $ocrRawText ?: null,
            'created_by'      => $userId,
        ];

        $id = $this->model->insert($data);

        if (! $id) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan surat masuk.');
        }

        return redirect()->to(base_url('admin/surat-masuk'))
                         ->with('success', 'Surat masuk berhasil dicatat.');
    }

    /**
     * Detail surat masuk
     */
    public function detail(int $id)
    {
        $letter = $this->model->find($id);
        if (! $letter) {
            return redirect()->to(base_url('admin/surat-masuk'))->with('error', 'Surat tidak ditemukan.');
        }

        return view('admin/surat/masuk/detail', [
            'title'      => 'Detail Surat Masuk',
            'letter'     => $letter,
            'categories' => self::CATEGORIES,
        ]);
    }

    /**
     * Edit surat masuk
     */
    public function edit(int $id)
    {
        $letter = $this->model->find($id);
        if (! $letter) {
            return redirect()->to(base_url('admin/surat-masuk'))->with('error', 'Surat tidak ditemukan.');
        }

        return view('admin/surat/masuk/edit', [
            'title'      => 'Edit Surat Masuk',
            'letter'     => $letter,
            'categories' => self::CATEGORIES,
        ]);
    }

    /**
     * Update surat masuk
     */
    public function update(int $id)
    {
        $rules = [
            'sender_name' => 'required|min_length[3]',
            'subject'     => 'required|min_length[3]',
            'received_at' => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->model->update($id, [
            'letter_number'   => $this->request->getPost('letter_number') ?: null,
            'sender_name'     => $this->request->getPost('sender_name'),
            'sender_agency'   => $this->request->getPost('sender_agency') ?: null,
            'subject'         => $this->request->getPost('subject'),
            'received_at'     => $this->request->getPost('received_at'),
            'letter_date'     => $this->request->getPost('letter_date') ?: null,
            'letter_category' => $this->request->getPost('letter_category') ?: null,
            'disposition'     => $this->request->getPost('disposition') ?: null,
        ]);

        return redirect()->to(base_url('admin/surat-masuk/detail/' . $id))
                         ->with('success', 'Surat masuk berhasil diperbarui.');
    }

    /**
     * Hapus surat masuk (soft delete via redirect back)
     */
    public function delete(int $id)
    {
        $letter = $this->model->find($id);
        if ($letter) {
            // Hapus file jika ada
            if (! empty($letter['scan_path'])) {
                $path = WRITEPATH . 'uploads/' . $letter['scan_path'];
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            $this->model->delete($id);
        }

        return redirect()->to(base_url('admin/surat-masuk'))->with('success', 'Surat masuk berhasil dihapus.');
    }

    /**
     * View / preview file scan
     */
    public function viewScan(int $id)
    {
        $letter = $this->model->find($id);
        if (! $letter || empty($letter['scan_path'])) {
            return redirect()->back()->with('error', 'File scan tidak tersedia.');
        }

        $path = WRITEPATH . 'uploads/' . $letter['scan_path'];
        if (! file_exists($path)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        $mime = mime_content_type($path);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        readfile($path);
        exit;
    }
}
