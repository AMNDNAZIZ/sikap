<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SubjectModel;

class Subjects extends BaseController
{
    public function index()
    {
        $model = new SubjectModel();
        $data['subjects'] = $model->findAll();
        return view('admin/subjects/index', $data);
    }

    public function create()
    {
        return view('admin/subjects/create');
    }

    public function store()
    {
        $model = new SubjectModel();
        $model->save([
            'code' => $this->request->getPost('code'),
            'name' => $this->request->getPost('name'),
            'subject_group' => $this->request->getPost('subject_group'),
            'religion' => $this->request->getPost('religion'),
            'sort_order' => $this->request->getPost('sort_order'),
            'is_active' => $this->request->getPost('is_active') ?? 1,
        ]);
        return redirect()->to(base_url('admin/subjects'))->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $model = new SubjectModel();
        $data['subject'] = $model->find($id);
        return view('admin/subjects/edit', $data);
    }

    public function update($id)
    {
        $model = new SubjectModel();
        $model->update($id, [
            'code' => $this->request->getPost('code'),
            'name' => $this->request->getPost('name'),
            'subject_group' => $this->request->getPost('subject_group'),
            'religion' => $this->request->getPost('religion'),
            'sort_order' => $this->request->getPost('sort_order'),
            'is_active' => $this->request->getPost('is_active') ?? 1,
        ]);
        return redirect()->to(base_url('admin/subjects'))->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function delete($id)
    {
        $model = new SubjectModel();
        $model->delete($id);
        return redirect()->to(base_url('admin/subjects'))->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
