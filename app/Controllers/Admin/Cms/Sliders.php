<?php
namespace App\Controllers\Admin\Cms;

use App\Controllers\BaseController;
use App\Models\LandingSliderModel;

class Sliders extends BaseController
{
    protected $sliderModel;

    public function __construct()
    {
        $this->sliderModel = new LandingSliderModel();
    }

    public function index()
    {
        $data['sliders'] = $this->sliderModel->orderBy('order', 'ASC')->findAll();
        $data['title'] = 'Manajemen Slider';
        return view('admin/cms/sliders/index', $data);
    }

    public function create()
    {
        $data['title'] = 'Tambah Slider';
        return view('admin/cms/sliders/create', $data);
    }

    public function store()
    {
        // Validation rules
        $rules = [
            'image' => [
                'rules' => 'uploaded[image]|max_size[image,2048]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]',
                'errors' => [
                    'uploaded' => 'Gambar harus diupload',
                    'max_size' => 'Ukuran gambar maksimal 2MB',
                    'is_image' => 'File harus berupa gambar',
                    'mime_in' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP'
                ]
            ],
            'title' => [
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'Judul maksimal 255 karakter'
                ]
            ],
            'order' => [
                'rules' => 'permit_empty|integer',
                'errors' => [
                    'integer' => 'Urutan harus berupa angka'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('image');
        
        if (!$file->isValid()) {
            return redirect()->back()->withInput()->with('error', 'File tidak valid: ' . $file->getErrorString());
        }

        try {
            // Ensure upload directory exists
            $uploadPath = UPLOAD_PATH . 'sliders';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $newName = $file->getRandomName();
            
            // Move file
            if (!$file->move($uploadPath, $newName)) {
                throw new \Exception('Gagal memindahkan file ke folder uploads');
            }

            // Insert to database
            $this->sliderModel->insert([
                'image' => $newName,
                'title' => $this->request->getPost('title'),
                'description' => $this->request->getPost('description'),
                'link' => $this->request->getPost('link'),
                'order' => $this->request->getPost('order') ?? 0,
                'is_active' => $this->request->getPost('is_active') ?? 1,
            ]);

            return redirect()->to('admin/cms/sliders')->with('success', 'Slider berhasil ditambahkan');
            
        } catch (\Exception $e) {
            // Delete uploaded file if database insert fails
            if (isset($newName) && file_exists($uploadPath . '/' . $newName)) {
                unlink($uploadPath . '/' . $newName);
            }
            
            log_message('error', 'Failed to store slider: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan slider: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data['slider'] = $this->sliderModel->find($id);
        $data['title'] = 'Edit Slider';
        return view('admin/cms/sliders/edit', $data);
    }

    public function update($id)
    {
        $slider = $this->sliderModel->find($id);
        
        if (!$slider) {
            return redirect()->to('admin/cms/sliders')->with('error', 'Slider tidak ditemukan');
        }

        // Validation rules (image is optional for update)
        $rules = [
            'image' => [
                'rules' => 'permit_empty|max_size[image,2048]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]',
                'errors' => [
                    'max_size' => 'Ukuran gambar maksimal 2MB',
                    'is_image' => 'File harus berupa gambar',
                    'mime_in' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP'
                ]
            ],
            'title' => [
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'Judul maksimal 255 karakter'
                ]
            ],
            'order' => [
                'rules' => 'permit_empty|integer',
                'errors' => [
                    'integer' => 'Urutan harus berupa angka'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $file = $this->request->getFile('image');
            $uploadPath = UPLOAD_PATH . 'sliders';

            $data = [
                'title' => $this->request->getPost('title'),
                'description' => $this->request->getPost('description'),
                'link' => $this->request->getPost('link'),
                'order' => $this->request->getPost('order') ?? 0,
                'is_active' => $this->request->getPost('is_active') ?? 1,
            ];

            // Handle new image upload
            if ($file && $file->isValid() && !$file->hasMoved()) {
                // Ensure upload directory exists
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $newName = $file->getRandomName();
                
                if (!$file->move($uploadPath, $newName)) {
                    throw new \Exception('Gagal memindahkan file ke folder uploads');
                }
                
                $data['image'] = $newName;

                // Delete old image
                $oldImagePath = $uploadPath . '/' . $slider['image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $this->sliderModel->update($id, $data);
            return redirect()->to('admin/cms/sliders')->with('success', 'Slider berhasil diperbarui');
            
        } catch (\Exception $e) {
            // Delete uploaded file if database update fails
            if (isset($newName) && file_exists($uploadPath . '/' . $newName)) {
                unlink($uploadPath . '/' . $newName);
            }
            
            log_message('error', 'Failed to update slider: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui slider: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $slider = $this->sliderModel->find($id);
        
        if (!$slider) {
            return redirect()->to('admin/cms/sliders')->with('error', 'Slider tidak ditemukan');
        }

        try {
            $uploadPath = UPLOAD_PATH . 'sliders';
            $imagePath = $uploadPath . '/' . $slider['image'];
            
            // Delete image file
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            
            // Delete from database
            $this->sliderModel->delete($id);
            
            return redirect()->to('admin/cms/sliders')->with('success', 'Slider berhasil dihapus');
            
        } catch (\Exception $e) {
            log_message('error', 'Failed to delete slider: ' . $e->getMessage());
            return redirect()->to('admin/cms/sliders')->with('error', 'Gagal menghapus slider: ' . $e->getMessage());
        }
    }
}
