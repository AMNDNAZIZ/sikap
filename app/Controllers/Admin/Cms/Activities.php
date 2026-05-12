<?php
namespace App\Controllers\Admin\Cms;

use App\Controllers\BaseController;
use App\Models\ActivityModel;

class Activities extends BaseController
{
    protected $activityModel;

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
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $imageName = $file->getRandomName();
            $file->move(UPLOAD_PATH . 'activities', $imageName);

            $this->activityModel->insert([
                'title' => $this->request->getPost('title'),
                'description' => $this->request->getPost('description'),
                'image' => $imageName,
                'date' => $this->request->getPost('date') ?? date('Y-m-d'),
                'created_by' => session()->get('user')['id'],
            ]);

            return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi berhasil ditambahkan');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah gambar');
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
        $file = $this->request->getFile('image');

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'date' => $this->request->getPost('date'),
        ];

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(UPLOAD_PATH . 'activities', $newName);
            $data['image'] = $newName;

            if (file_exists(UPLOAD_PATH . 'activities/' . $activity['image'])) {
                unlink(UPLOAD_PATH . 'activities/' . $activity['image']);
            }
        }

        $this->activityModel->update($id, $data);
        return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi berhasil diperbarui');
    }

    public function delete($id)
    {
        $activity = $this->activityModel->find($id);
        if ($activity) {
            if (file_exists(UPLOAD_PATH . 'activities/' . $activity['image'])) {
                unlink(UPLOAD_PATH . 'activities/' . $activity['image']);
            }
            $this->activityModel->delete($id);
        }
        return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi berhasil dihapus');
    }
}
