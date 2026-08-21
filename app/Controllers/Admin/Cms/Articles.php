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
        helper(['text', 'upload']);
    }

    public function index()
    {
        $data['articles'] = $this->articleModel->getArticlesWithAuthor();
        $data['title']    = 'Manajemen Berita & Artikel';
        return view('admin/cms/articles/index', $data);
    }

    public function create()
    {
        $data['title'] = 'Tambah Artikel';
        return view('admin/cms/articles/create', $data);
    }

    public function store()
    {
        $file      = $this->request->getFile('image');
        $imageName = null;

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $result = safe_upload_image($file, UPLOAD_PATH . 'articles');
            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['error']);
            }
            $imageName = $result['name'];
        }

        $title = $this->request->getPost('title');
        $this->articleModel->insert([
            'title'        => $title,
            'slug'         => url_title($title, '-', true) . '-' . time(),
            'content'      => $this->request->getPost('content'),
            'image'        => $imageName,
            'category'     => $this->request->getPost('category') ?? 'Berita',
            'is_published' => $this->request->getPost('is_published') ?? 1,
            'created_by'   => session()->get('user')['id'],
        ]);

        return redirect()->to('admin/cms/articles')->with('success', 'Artikel berhasil ditambahkan');
    }

    public function edit($id)
    {
        $data['article'] = $this->articleModel->find($id);
        $data['title']   = 'Edit Artikel';
        return view('admin/cms/articles/edit', $data);
    }

    public function update($id)
    {
        $article = $this->articleModel->find($id);
        if (!$article) {
            return redirect()->to('admin/cms/articles')->with('error', 'Artikel tidak ditemukan.');
        }

        $data = [
            'title'        => $this->request->getPost('title'),
            'content'      => $this->request->getPost('content'),
            'category'     => $this->request->getPost('category') ?? 'Berita',
            'is_published' => $this->request->getPost('is_published') ?? 1,
        ];

        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $result = safe_upload_image($file, UPLOAD_PATH . 'articles');
            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['error']);
            }
            safe_delete_upload(UPLOAD_PATH . 'articles', $article['image'] ?? '');
            $data['image'] = $result['name'];
        }

        $this->articleModel->update($id, $data);
        return redirect()->to('admin/cms/articles')->with('success', 'Artikel berhasil diperbarui');
    }

    public function delete($id)
    {
        $article = $this->articleModel->find($id);
        if ($article) {
            safe_delete_upload(UPLOAD_PATH . 'articles', $article['image'] ?? '');
            $this->articleModel->delete($id);
        }
        return redirect()->to('admin/cms/articles')->with('success', 'Artikel berhasil dihapus');
    }

    /**
     * Upload gambar untuk CKEditor 5
     */
    public function uploadImage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Forbidden']);
        }

        $file = $this->request->getFile('file');
        $result = safe_upload_image($file, UPLOAD_PATH . 'articles');

        if (!$result['success']) {
            return $this->response->setJSON(['success' => false, 'message' => $result['error']]);
        }

        return $this->response->setJSON([
            'success' => true,
            'url'     => base_url('uploads/articles/' . $result['name']),
        ]);
    }
}
