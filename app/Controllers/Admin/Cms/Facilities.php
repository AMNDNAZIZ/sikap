<?php
namespace App\Controllers\Admin\Cms;

use App\Controllers\BaseController;
use App\Models\FacilityModel;

class Facilities extends BaseController
{
    protected $facilityModel;

    public function __construct()
    {
        $this->facilityModel = new FacilityModel();
    }

    public function index()
    {
        $data['facilities'] = $this->facilityModel->findAll();
        $data['title'] = 'Manajemen Sarana Prasarana';
        return view('admin/cms/facilities/index', $data);
    }

    public function create()
    {
        $data['title'] = 'Tambah Fasilitas';
        return view('admin/cms/facilities/create', $data);
    }

    public function store()
    {
        $file = $this->request->getFile('image');
        $imageName = null;

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $imageName = $file->getRandomName();
            $file->move(UPLOAD_PATH . 'facilities', $imageName);
        }

        $this->facilityModel->insert([
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'image' => $imageName,
        ]);

        return redirect()->to('admin/cms/facilities')->with('success', 'Fasilitas berhasil ditambahkan');
    }

    public function edit($id)
    {
        $data['facility'] = $this->facilityModel->find($id);
        $data['title'] = 'Edit Fasilitas';
        return view('admin/cms/facilities/edit', $data);
    }

    public function update($id)
    {
        $facility = $this->facilityModel->find($id);
        $file = $this->request->getFile('image');

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(UPLOAD_PATH . 'facilities', $newName);
            $data['image'] = $newName;

            if ($facility['image'] && file_exists(UPLOAD_PATH . 'facilities/' . $facility['image'])) {
                unlink(UPLOAD_PATH . 'facilities/' . $facility['image']);
            }
        }

        $this->facilityModel->update($id, $data);
        return redirect()->to('admin/cms/facilities')->with('success', 'Fasilitas berhasil diperbarui');
    }

    public function delete($id)
    {
        $facility = $this->facilityModel->find($id);
        if ($facility) {
            if ($facility['image'] && file_exists(UPLOAD_PATH . 'facilities/' . $facility['image'])) {
                unlink(UPLOAD_PATH . 'facilities/' . $facility['image']);
            }
            $this->facilityModel->delete($id);
        }
        return redirect()->to('admin/cms/facilities')->with('success', 'Fasilitas berhasil dihapus');
    }
}
