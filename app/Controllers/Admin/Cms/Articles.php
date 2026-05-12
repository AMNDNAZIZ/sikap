<?php
namespace App\Controllers\Admin\Cms;

use App\Controllers\BaseController;
use App\Models\ArticleModel;

class Articles extends BaseController
{
    protected $articleModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        helper('text');
    }

    public function index()
    {
        $data['articles'] = $this->articleModel->getArticlesWithAuthor();
        $data['title'] = 'Manajemen Berita & Artikel';
        return view('admin/cms/articles/index', $data);
    }

    public function create()
    {
        $data['title'] = 'Tambah Artikel';
        return view('admin/cms/articles/create', $data);
    }

    public function store()
    {
        $file = $this->request->getFile('image');
        $imageName = null;

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $imageName = $file->getRandomName();
            $file->move(UPLOAD_PATH . 'articles', $imageName);
        }

        $title = $this->request->getPost('title');
        $this->articleModel->insert([
            'title' => $title,
            'slug' => url_title($title, '-', true) . '-' . time(),
            'content' => $this->request->getPost('content'),
            'image' => $imageName,
            'category' => $this->request->getPost('category') ?? 'Berita',
            'is_published' => $this->request->getPost('is_published') ?? 1,
            'created_by' => session()->get('user')['id'],
        ]);

        return redirect()->to('admin/cms/articles')->with('success', 'Artikel berhasil ditambahkan');
    }

    public function edit($id)
    {
        $data['article'] = $this->articleModel->find($id);
        $data['title'] = 'Edit Artikel';
        return view('admin/cms/articles/edit', $data);
    }

    public function update($id)
    {
        $article = $this->articleModel->find($id);
        $file = $this->request->getFile('image');

        $data = [
            'title' => $this->request->getPost('title'),
            'content' => $this->request->getPost('content'),
            'category' => $this->request->getPost('category') ?? 'Berita',
            'is_published' => $this->request->getPost('is_published') ?? 1,
        ];

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(UPLOAD_PATH . 'articles', $newName);
            $data['image'] = $newName;

            if ($article['image'] && file_exists(UPLOAD_PATH . 'articles/' . $article['image'])) {
                unlink(UPLOAD_PATH . 'articles/' . $article['image']);
            }
        }

        $this->articleModel->update($id, $data);
        return redirect()->to('admin/cms/articles')->with('success', 'Artikel berhasil diperbarui');
    }

    public function delete($id)
    {
        $article = $this->articleModel->find($id);
        if ($article) {
            if ($article['image'] && file_exists(UPLOAD_PATH . 'articles/' . $article['image'])) {
                unlink(UPLOAD_PATH . 'articles/' . $article['image']);
            }
            $this->articleModel->delete($id);
        }
        return redirect()->to('admin/cms/articles')->with('success', 'Artikel berhasil dihapus');
    }

    /**
     * Upload image untuk CKEditor 5
     * Endpoint untuk upload gambar dari dalam editor
     */
    public function uploadImage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request'
            ]);
        }

        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'File tidak valid'
            ]);
        }

        // Validasi tipe file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tipe file tidak diizinkan. Hanya JPG, PNG, GIF, dan WEBP yang diperbolehkan.'
            ]);
        }

        // Validasi ukuran file (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ukuran file terlalu besar. Maksimal 5MB.'
            ]);
        }

        try {
            // Generate nama file unik
            $newName = $file->getRandomName();
            
            // Pastikan folder exists
            $uploadPath = FCPATH . 'uploads/articles/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Move file
            $file->move($uploadPath, $newName);

            // Return URL untuk CKEditor
            return $this->response->setJSON([
                'success' => true,
                'url' => base_url('uploads/articles/' . $newName)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Upload image error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal upload gambar: ' . $e->getMessage()
            ]);
        }
    }
}
