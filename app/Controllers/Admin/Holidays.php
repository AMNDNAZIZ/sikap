<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HolidayModel;

class Holidays extends BaseController
{
    protected $holidayModel;

    public function __construct()
    {
        $this->holidayModel = new HolidayModel();
    }

    public function index()
    {
        $data = [
            'title'    => 'Hari Libur',
            'holidays' => $this->holidayModel->orderBy('date', 'DESC')->paginate(10),
            'pager'    => $this->holidayModel->pager,
        ];
        return view('admin/holidays/index', $data);
    }



    public function create()
    {
        return view('admin/holidays/create', ['title' => 'Tambah Hari Libur']);
    }

    public function store()
    {
        $post = $this->request->getPost();

        $this->holidayModel->insert([
            'date'        => $post['date'],
            'description' => $post['description'],
        ]);

        return redirect()->to('/admin/holidays')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $holiday = $this->holidayModel->find($id);
        if (!$holiday) {
            return redirect()->to('/admin/holidays')->with('error', 'Hari libur tidak ditemukan.');
        }

        return view('admin/holidays/edit', [
            'title'   => 'Edit Hari Libur',
            'holiday' => $holiday,
        ]);
    }

    public function update($id)
    {
        $post = $this->request->getPost();

        $this->holidayModel->update($id, [
            'date'        => $post['date'],
            'description' => $post['description'],
        ]);

        return redirect()->to('/admin/holidays')->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->holidayModel->delete($id);
        return redirect()->to('/admin/holidays')->with('success', 'Hari libur berhasil dihapus.');
    }
}
