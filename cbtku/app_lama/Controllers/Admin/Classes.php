<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\TeacherModel;

class Classes extends BaseController
{
    public function index()
    {
        $model = new ClassModel();
        $data['classes'] = $model->select('classes.*, teachers.name as teacher_name')
                                 ->join('teachers', 'teachers.id = classes.teacher_id', 'left')
                                 ->findAll();

        return view('admin/classes/index', $data);
    }

    public function create()
    {
        $teacherModel = new TeacherModel();
        $data['teachers'] = $teacherModel->findAll();

        return view('admin/classes/create', $data);
    }

    public function store()
    {
        $model = new ClassModel();
        $model->save([
            'name'       => $this->request->getPost('name'),
            'level'      => $this->request->getPost('level'),
        ]);

        return redirect()->to('/admin/classes')->with('success', 'Kelas berhasil ditambahkan');
    }

    public function edit($id)
    {
        $model = new ClassModel();
        $teacherModel = new TeacherModel();

        $data['class'] = $model->find($id);

        return view('admin/classes/edit', $data);
    }

    public function update($id)
    {
        $model = new ClassModel();
        $model->update($id, [
            'name'       => $this->request->getPost('name'),
            'level'      => $this->request->getPost('level'),
        ]);

        return redirect()->to('/admin/classes')->with('success', 'Kelas berhasil diperbarui');
    }

    public function delete($id)
    {
        $model = new ClassModel();
        $model->delete($id);

        return redirect()->to('/admin/classes')->with('success', 'Kelas berhasil dihapus');
    }
}
