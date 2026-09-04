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
        $user      = session()->get('user');
        $studentId = $user['related_id'] ?? null;

        if (!$studentId) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        // Ambil class_id dan academic_year_id aktif siswa
        $record = $this->db->table('student_records sr')
            ->select('sr.class_id, sr.academic_year_id')
            ->join('academic_years ay', 'ay.id = sr.academic_year_id')
            ->where('sr.student_id', $studentId)
            ->where('sr.status', 'aktif')
            ->where('ay.is_active', 1)
            ->get()->getRowArray();

        // Fallback: record terbaru jika tidak ada yang aktif
        if (!$record) {
            $record = $this->db->table('student_records')
                ->select('class_id, academic_year_id')
                ->where('student_id', $studentId)
                ->orderBy('id', 'DESC')
                ->get()->getRowArray();
        }

        $classId = $record['class_id'] ?? null;
        $yearId  = $record['academic_year_id'] ?? null;

        $builder = $this->announcementModel
            ->select('announcements.*, users.fullname as creator_name, classes.name as class_name')
            ->join('users',   'users.id = announcements.created_by', 'left')
            ->join('classes', 'classes.id = announcements.class_id', 'left')
            ->groupStart()
                // target siswa umum (tidak terikat kelas → class_id NULL)
                ->groupStart()
                    ->where('announcements.target', 'siswa')
                    ->where('announcements.class_id IS NULL', null, false)
                ->groupEnd()

                // target siswa, kelas ini, tahun ini
                ->orGroupStart()
                    ->where('announcements.target', 'siswa')
                    ->where('announcements.class_id', $classId)
                    ->where('announcements.academic_year_id', $yearId)
                ->groupEnd()

                // target kelas ini, tahun ini
                ->orGroupStart()
                    ->where('announcements.target', 'class')
                    ->where('announcements.class_id', $classId)
                    ->where('announcements.academic_year_id', $yearId)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('announcements.created_at', 'DESC');

        $announcements = $builder->findAll();

        return view('siswa/announcement/index', [
            'announcements' => $announcements,
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
