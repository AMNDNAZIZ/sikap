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
        helper('upload');
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
        $file      = $this->request->getFile('image');
        $imageName = null;

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $result = safe_upload_image($file, UPLOAD_PATH . 'facilities');
            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['error']);
            }
            $imageName = $result['name'];
        }

        $this->facilityModel->insert([
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'image'       => $imageName,
        ]);

        return redirect()->to('admin/cms/facilities')->with('success', 'Fasilitas berhasil ditambahkan');
    }

    public function edit($id)
    {
        $data['facility'] = $this->facilityModel->find($id);
        $data['title']    = 'Edit Fasilitas';
        return view('admin/cms/facilities/edit', $data);
    }

    public function update($id)
    {
        $facility = $this->facilityModel->find($id);
        if (!$facility) {
            return redirect()->to('admin/cms/facilities')->with('error', 'Data tidak ditemukan.');
        }

        $data = [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $result = safe_upload_image($file, UPLOAD_PATH . 'facilities');
            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['error']);
            }
            safe_delete_upload(UPLOAD_PATH . 'facilities', $facility['image'] ?? '');
            $data['image'] = $result['name'];
        }

        $this->facilityModel->update($id, $data);
        return redirect()->to('admin/cms/facilities')->with('success', 'Fasilitas berhasil diperbarui');
    }

    public function delete($id)
    {
        $facility = $this->facilityModel->find($id);
        if ($facility) {
            safe_delete_upload(UPLOAD_PATH . 'facilities', $facility['image'] ?? '');
            $this->facilityModel->delete($id);
        }
        return redirect()->to('admin/cms/facilities')->with('success', 'Fasilitas berhasil dihapus');
    }
}
