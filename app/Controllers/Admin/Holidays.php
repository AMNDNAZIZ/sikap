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

    public function sync()
    {
        $year = $this->request->getPost('year') ?: date('Y');
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->get('https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/calendar.json', [
                'timeout' => 15,
                'headers' => [
                    'User-Agent' => 'SIKAP-Application/1.0',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return redirect()->to('/admin/holidays')->with('error', "Gagal mengambil data dari API (HTTP Status: {$statusCode}).");
            }

            $data = json_decode($response->getBody(), true);
            if (!is_array($data)) {
                return redirect()->to('/admin/holidays')->with('error', 'Format data API tidak valid.');
            }

            $inserted = 0;
            $skipped  = 0;

            foreach ($data as $date => $info) {
                if (!is_array($info)) {
                    continue;
                }

                // Cek apakah tanggal sesuai tahun & merupakan hari libur
                $isHoliday = !empty($info['holiday']) && $info['holiday'] !== false;
                if (str_starts_with($date, (string) $year) && $isHoliday) {
                    $description = $info['summary'] ?? ($info['description'] ?? 'Hari Libur Nasional');

                    // Cek apakah tanggal sudah terdaftar di DB
                    $exists = $this->holidayModel->where('date', $date)->first();
                    if (!$exists) {
                        $this->holidayModel->insert([
                            'date'        => $date,
                            'description' => $description,
                        ]);
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                }
            }

            if ($inserted > 0) {
                $msg = "Berhasil mengimpor {$inserted} hari libur nasional untuk tahun {$year}.";
                if ($skipped > 0) {
                    $msg .= " ({$skipped} tanggal sudah ada di database).";
                }
                return redirect()->to('/admin/holidays')->with('success', $msg);
            } else {
                $msg = "Tidak ada hari libur baru yang diimpor untuk tahun {$year}.";
                if ($skipped > 0) {
                    $msg .= " ({$skipped} hari libur sudah terdaftar).";
                }
                return redirect()->to('/admin/holidays')->with('info', $msg);
            }
        } catch (\Exception $e) {
            return redirect()->to('/admin/holidays')->with('error', 'Gagal menghubungkan ke API Hari Libur: ' . $e->getMessage());
        }
    }
}
