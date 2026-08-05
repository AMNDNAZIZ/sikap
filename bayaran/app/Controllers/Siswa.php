<?php

namespace App\Controllers;

use App\Models\SiswaModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Siswa extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new SiswaModel();
    }

    private function getListData()
    {
        $q = $this->request->getGet('q');

        $builder = $this->model->orderBy('nama');
        if ($q) {
            $builder->groupStart()
                ->like('nama', $q)
                ->orLike('nis', $q)
                ->groupEnd();
        }

        return [
            'title' => 'Data Siswa',
            'list' => $builder->paginate(20, 'default'),
            'pager' => $this->model->pager
        ];
    }

    public function index()
    {
        $data = $this->getListData();
        $data['item'] = null;

        return view('admin/siswa/index', $data);
    }

    public function edit($id)
    {
        $data = $this->getListData();
        $data['item'] = $this->model->find($id);

        return view('admin/siswa/index', $data);
    }

    public function save()
    {
        $id = $this->request->getPost('id');
        $nis = $this->request->getPost('nis');
        $nama = $this->request->getPost('nama');

        $rules = [
            'id' => 'permit_empty',
            'nis' => "required|is_unique[siswa.nis,id,{$id}]",
            'nama' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->model->save([
            'id' => $id ?: null,
            'nis' => $nis,
            'nama' => $nama,
        ]);

        return redirect()->to('/admin/siswa')->with('msg', 'Data siswa disimpan.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->back()->with('msg', 'Data siswa dihapus.');
    }




    public function importForm()
    {
        return view('admin/siswa/import', ['title' => 'Import Siswa']);
    }

    public function import()
    {
        $file = $this->request->getFile('excel');
        if (!$file->isValid() || $file->getClientExtension() !== 'xlsx') {
            return redirect()->back()->with('msg', 'File tidak valid.');
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $data = [];
        $errors = [];
        $nisUnik = [];

        foreach ($rows as $i => $row) {
            if ($i === 0)
                continue; // Skip header

            $nis = $this->sanitizeInput($row[0] ?? '');
            $nama = $this->sanitizeInput($row[1] ?? '');

            if (!$nis || !$nama) {
                $errors[] = "Baris " . ($i + 1) . " kosong atau tidak valid.";
                continue;
            }

            if (in_array($nis, $nisUnik)) {
                $errors[] = "Baris " . ($i + 1) . ": NIS duplikat di file.";
                continue;
            }

            $nisUnik[] = $nis;

            $data[] = [
                'nis' => substr($nis, 0, 20), // Batas panjang NIS
                'nama' => substr($nama, 0, 100), // Batas panjang Nama
            ];
        }

        if (!empty($errors)) {
            return redirect()->back()->with('msg', implode('<br>', $errors));
        }

        $model = new \App\Models\SiswaModel();

        // Cek NIS sudah ada di DB
        $existing = $model->whereIn('nis', array_column($data, 'nis'))->findAll();
        if ($existing) {
            $existNis = implode(', ', array_column($existing, 'nis'));
            return redirect()->back()->with('msg', 'Beberapa NIS sudah ada di database: ' . $existNis);
        }

        $model->insertBatch($data);

        return redirect()->to('/admin/siswa')->with('msg', 'Import berhasil. Total: ' . count($data));
    }

    private function sanitizeInput($input)
    {
        return trim(strip_tags($input));
    }

}
