<?php

namespace App\Controllers\Admin\Cms;

use App\Controllers\BaseController;
use App\Models\LandingLinkModel;

class Links extends BaseController
{
    protected $linkModel;

    public function __construct()
    {
        $this->linkModel = new LandingLinkModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Kelola Tautan Pintar',
            'links' => $this->linkModel->orderBy('order_no', 'ASC')->findAll()
        ];

        return view('admin/cms/links/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Tautan Pintar'
        ];

        return view('admin/cms/links/create', $data);
    }

    public function store()
    {
        $rules = [
            'title' => 'required|min_length[3]',
            'url' => 'required|valid_url',
            'icon' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->linkModel->save([
            'title' => $this->request->getPost('title'),
            'url' => $this->request->getPost('url'),
            'icon' => $this->request->getPost('icon'),
            'description' => $this->request->getPost('description'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'order_no' => $this->request->getPost('order_no') ?: 0,
        ]);

        return redirect()->to(base_url('admin/cms/links'))->with('success', 'Tautan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $link = $this->linkModel->find($id);
        if (!$link) {
            return redirect()->to(base_url('admin/cms/links'))->with('error', 'Data tidak ditemukan.');
        }

        $data = [
            'title' => 'Edit Tautan Pintar',
            'link' => $link
        ];

        return view('admin/cms/links/edit', $data);
    }

    public function update($id)
    {
        $rules = [
            'title' => 'required|min_length[3]',
            'url' => 'required|valid_url',
            'icon' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->linkModel->update($id, [
            'title' => $this->request->getPost('title'),
            'url' => $this->request->getPost('url'),
            'icon' => $this->request->getPost('icon'),
            'description' => $this->request->getPost('description'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'order_no' => $this->request->getPost('order_no') ?: 0,
        ]);

        return redirect()->to(base_url('admin/cms/links'))->with('success', 'Tautan berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->linkModel->delete($id);
        return redirect()->to(base_url('admin/cms/links'))->with('success', 'Tautan berhasil dihapus.');
    }
}
