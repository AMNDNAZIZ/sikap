<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use Config\Database;

class Announcement extends BaseController
{
    protected $announcementModel;
    protected $db;

    public function __construct()
    {
        $this->announcementModel = new AnnouncementModel();
        $this->db = Database::connect();
    }

    public function index()
    {
        helper('text');
        $user = session()->get('user');
        $studentId = $user['related_id'] ?? null;

        if (! $studentId) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        // ambil class_id siswa
        $classRow = $this->db->table('student_records')
            ->select('class_id')
            ->where('student_id', $studentId)
            ->orderBy('id', 'DESC')
            ->get()->getRow();

        $classId = $classRow->class_id ?? null;

        // ambil pengumuman yang targetnya siswa/kelas sesuai aturan
        $announcements = $this->announcementModel
            ->select('announcements.*, users.fullname as creator_name, classes.name as class_name')
            ->join('users', 'users.id = announcements.created_by', 'left')
            ->join('classes', 'classes.id = announcements.class_id', 'left')
            ->groupStart()
                // target siswa umum
                ->groupStart()
                    ->where('announcements.target', 'siswa')
                    ->where('announcements.class_id IS NULL', null, false)
                ->groupEnd()

                // target siswa tapi khusus kelas ini
                ->orGroupStart()
                    ->where('announcements.target', 'siswa')
                    ->where('announcements.class_id', $classId)
                ->groupEnd()

                // target kelas ini
                ->orGroupStart()
                    ->where('announcements.target', 'class')
                    ->where('announcements.class_id', $classId)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('announcements.created_at', 'DESC')
            ->findAll();

        return view('siswa/announcement/index', [
            'announcements' => $announcements
        ]);
    }

    public function show($id)
    {
        $announcement = $this->announcementModel
            ->select('announcements.*, users.fullname as creator_name, classes.name as class_name')
            ->join('users', 'users.id = announcements.created_by', 'left')
            ->join('classes', 'classes.id = announcements.class_id', 'left')
            ->find($id);

        if (! $announcement) {
            return redirect()->to('siswa/announcement')->with('error', 'Pengumuman tidak ditemukan.');
        }

        return view('siswa/announcement/show', [
            'announcement' => $announcement
        ]);
    }
}
