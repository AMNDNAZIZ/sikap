<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BehaviorModel;

class Behaviors extends BaseController
{
    protected $behaviorModel;

    public function __construct()
    {
        $this->behaviorModel = new BehaviorModel();
    }

    public function index()
    {
        $behaviors = $this->behaviorModel->findAll();

        return view('admin/behaviors/index', [
            'behaviors' => $behaviors
        ]);
    }

    public function create()
    {
        return view('admin/behaviors/create');
    }

    public function store()
    {
        $data = [
            'name'  => $this->request->getPost('name'),
            'points'=> $this->request->getPost('points'),
            'type'  => $this->request->getPost('type')
        ];

        $this->behaviorModel->insert($data);

        return redirect()->to(base_url('admin/behaviors'))
                         ->with('success', 'Perilaku berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $behavior = $this->behaviorModel->find($id);

        return view('admin/behaviors/edit', [
            'behavior' => $behavior
        ]);
    }

    public function update($id)
    {
        $data = [
            'name'  => $this->request->getPost('name'),
            'points'=> $this->request->getPost('points'),
            'type'  => $this->request->getPost('type')
        ];

        $this->behaviorModel->update($id, $data);

        return redirect()->to(base_url('admin/behaviors'))
                         ->with('success', 'Perilaku berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->behaviorModel->delete($id);

        return redirect()->to(base_url('admin/behaviors'))
                         ->with('success', 'Perilaku berhasil dihapus.');
    }
}
