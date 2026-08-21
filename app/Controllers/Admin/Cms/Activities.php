<?php
namespace App\Controllers\Admin\Cms;

use App\Controllers\BaseController;
use App\Models\ActivityModel;

class Activities extends BaseController
{
    protected $activityModel;

    // MIME type yang diizinkan — hanya gambar
    private const ALLOWED_MIME = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    // Ekstensi yang diizinkan — hanya gambar
    private const ALLOWED_EXT  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct()
    {
        $this->activityModel = new ActivityModel();
    }

    public function index()
    {
        $data['activities'] = $this->activityModel->getActivitiesWithAuthor();
        $data['title'] = 'Manajemen Dokumentasi Kegiatan';
        return view('admin/cms/activities/index', $data);
    }

    public function create()
    {
        $data['title'] = 'Tambah Dokumentasi';
        return view('admin/cms/activities/create', $data);
    }

    public function store()
    {
        $file = $this->request->getFile('image');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Gagal mengunggah gambar. File tidak valid.');
        }

        // Validasi MIME type berdasarkan konten file (bukan header yang bisa dipalsukan)
        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return redirect()->back()->with('error', 'Tipe file tidak diizinkan. Hanya gambar (JPG, PNG, GIF, WEBP) yang diperbolehkan.');
        }

        // Validasi ekstensi file
        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return redirect()->back()->with('error', 'Ekstensi file tidak diizinkan. Hanya .jpg, .jpeg, .png, .gif, .webp yang diperbolehkan.');
        }

        // Validasi ukuran maksimal 5MB
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Ukuran gambar terlalu besar. Maksimal 5MB.');
        }

        // Buat nama file aman: hash random + ekstensi dari MIME (bukan dari nama file asli)
        $safeName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $this->mimeToExt($mime);

        $uploadDir = UPLOAD_PATH . 'activities';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $safeName);

        $this->activityModel->insert([
            'title'       => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'image'       => $safeName,
            'date'        => $this->request->getPost('date') ?? date('Y-m-d'),
            'created_by'  => session()->get('user')['id'],
        ]);

        return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi berhasil ditambahkan');
    }

    public function edit($id)
    {
        $data['activity'] = $this->activityModel->find($id);
        $data['title'] = 'Edit Dokumentasi';
        return view('admin/cms/activities/edit', $data);
    }

    public function update($id)
    {
        $activity = $this->activityModel->find($id);
        if (!$activity) {
            return redirect()->to('admin/cms/activities')->with('error', 'Data tidak ditemukan.');
        }

        $data = [
            'title'       => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'date'        => $this->request->getPost('date'),
        ];

        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && !$file->hasMoved()) {

            // Validasi MIME type
            $mime = $file->getMimeType();
            if (!in_array($mime, self::ALLOWED_MIME, true)) {
                return redirect()->back()->with('error', 'Tipe file tidak diizinkan. Hanya gambar yang diperbolehkan.');
            }

            // Validasi ekstensi
            $ext = strtolower($file->getClientExtension());
            if (!in_array($ext, self::ALLOWED_EXT, true)) {
                return redirect()->back()->with('error', 'Ekstensi file tidak diizinkan.');
            }

            // Validasi ukuran
            if ($file->getSize() > 5 * 1024 * 1024) {
                return redirect()->back()->with('error', 'Ukuran gambar terlalu besar. Maksimal 5MB.');
            }

            $safeName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $this->mimeToExt($mime);

            $uploadDir = UPLOAD_PATH . 'activities';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file->move($uploadDir, $safeName);
            $data['image'] = $safeName;

            // Hapus file lama
            $oldFile = $uploadDir . '/' . ($activity['image'] ?? '');
            if (!empty($activity['image']) && is_file($oldFile)) {
                unlink($oldFile);
            }
        }

        $this->activityModel->update($id, $data);
        return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi berhasil diperbarui');
    }

    public function delete($id)
    {
        $activity = $this->activityModel->find($id);
        if ($activity) {
            $filePath = UPLOAD_PATH . 'activities/' . ($activity['image'] ?? '');
            if (!empty($activity['image']) && is_file($filePath)) {
                unlink($filePath);
            }
            $this->activityModel->delete($id);
        }
        return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi berhasil dihapus');
    }

    /**
     * Mapping MIME type ke ekstensi aman.
     * Tidak mempercayai ekstensi dari nama file asli.
     */
    private function mimeToExt(string $mime): string
    {
        return match($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png'               => 'png',
            'image/gif'               => 'gif',
            'image/webp'              => 'webp',
            default                   => 'jpg',
        };
    }
}
