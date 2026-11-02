<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LetterTemplateModel;

class SuratKeluarTemplate extends BaseController
{
    protected LetterTemplateModel $model;

    public function __construct()
    {
        $this->model = new LetterTemplateModel();
    }

    /**
     * AJAX — Ambil daftar semua template aktif
     */
    public function getTemplates()
    {
        $category = $this->request->getGet('category');
        $query = $this->model->where('is_active', 1);

        if (!empty($category)) {
            $query->where('category', $category);
        }

        $templates = $query->orderBy('is_system', 'DESC')
                           ->orderBy('title', 'ASC')
                           ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $templates,
        ]);
    }

    /**
     * AJAX — Simpan konten CKEditor saat ini sebagai template sekolah baru
     */
    public function saveAsTemplate()
    {
        $rules = [
            'title'           => 'required|min_length[3]|max_length[255]',
            'category'        => 'required|in_list[siswa,guru,umum,undangan]',
            'header_style'    => 'required|in_list[tengah,kiri_kanan]',
            'default_subject' => 'required|min_length[3]|max_length[255]',
            'body_html'       => 'required|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $session = session();
        $userSession = $session->get('user');
        $userId = is_array($userSession) ? ($userSession['id'] ?? '1') : ($session->get('user_id') ?? '1');

        $data = [
            'title'           => trim($this->request->getPost('title')),
            'code'            => url_title(strtolower($this->request->getPost('title')), '_', true) . '_' . time(),
            'category'        => $this->request->getPost('category'),
            'header_style'    => $this->request->getPost('header_style'),
            'default_subject' => trim($this->request->getPost('default_subject')),
            'body_html'       => $this->request->getPost('body_html'),
            'is_system'       => 0,
            'is_active'       => 1,
            'created_by'      => (string) $userId,
        ];

        $insertedId = $this->model->insert($data);

        if (!$insertedId) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Gagal menyimpan template baru ke database.',
            ]);
        }

        $savedTemplate = $this->model->find($insertedId);

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Template "' . esc($savedTemplate['title']) . '" berhasil disimpan dan siap digunakan!',
            'template' => $savedTemplate,
        ]);
    }

    /**
     * AJAX — Hapus template kustom buatan sekolah
     */
    public function deleteTemplate(int $id)
    {
        $tpl = $this->model->find($id);

        if (!$tpl) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Template tidak ditemukan.',
            ]);
        }

        if ((int)$tpl['is_system'] === 1) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Template bawaan sistem tidak dapat dihapus.',
            ]);
        }

        $this->model->delete($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Template kustom berhasil dihapus.',
        ]);
    }
}

