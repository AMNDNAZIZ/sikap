<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use App\Models\ClassModel;
use App\Models\TeacherModel;
use App\Models\TeachingAssignmentModel;

class Announcement extends BaseController
{
    protected $announcementModel;
    protected $classModel;
    protected $teacherModel;
    protected $teachingAssignmentModel;
    protected $db;

    public function __construct()
    {
        $this->announcementModel = new AnnouncementModel();
        $this->classModel = new ClassModel();
        $this->teacherModel = new TeacherModel;
        $this->teachingAssignmentModel = new TeachingAssignmentModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;
        $userId = $user['id'] ?? null;

        // class map untuk menampilkan nama kelas di view
        $classMap = [];
        foreach ($this->classModel->getActiveClasses() as $c) {
            $classMap[$c['id']] = $c['name'];
        }

        // default kosong supaya view tidak error
        $announcements = [];
        $adminAnnouncements = [];
        $myAnnouncements = [];

        $select = 'announcements.*, users.fullname as creator_name, classes.name as class_name';

        // ADMIN / KEPEK -> semua pengumuman
        if (in_array($roleId, [1, 2])) {
            $announcements = $this->announcementModel
                ->select($select)
                ->join('users', 'users.id = announcements.created_by', 'left')
                ->join('classes', 'classes.id = announcements.class_id', 'left')
                ->orderBy('announcements.created_at', 'DESC')
                ->findAll();

            // tetap kirim adminAnnouncements/myAnnouncements kosong agar view aman
            $adminAnnouncements = [];
            $myAnnouncements = [];

            return view('admin/announcements/index', compact(
                'announcements',
                'adminAnnouncements',
                'myAnnouncements',
                'roleId',
                'userId',
                'classMap'
            ));
        }

        // GURU
        if ($roleId == 3) {
            // ambil teacher record (teachers.user_id -> users.id)
            $teacher = $this->teacherModel->where('user_id', $userId)->first();

            // 1) pengumuman dari admin/kepsek yang target = guru (cards)
            $adminAnnouncements = $this->announcementModel
                ->select($select)
                ->join('users', 'users.id = announcements.created_by', 'left')
                ->join('classes', 'classes.id = announcements.class_id', 'left')
                ->where("FIND_IN_SET('guru', announcements.target) > 0")
                ->orderBy('announcements.created_at', 'DESC')
                ->findAll();

            // jika teacher ditemukan lanjutkan ambil pengumuman "milik" guru
            if ($teacher) {
                // cek wali kelas (classes.teacher_id -> teachers.id)
                $teacherClass = $this->classModel->where('teacher_id', $teacher['id'])->first();

                if ($teacherClass) {
                    // wali kelas: lihat pengumuman yang dia buat OR untuk kelasnya
                    $myAnnouncements = $this->announcementModel
                        ->select($select)
                        ->join('users', 'users.id = announcements.created_by', 'left')
                        ->join('classes', 'classes.id = announcements.class_id', 'left')
                        ->groupStart()
                        ->where('announcements.created_by', $userId)
                        ->orWhere('announcements.class_id', $teacherClass['id'])
                        ->groupEnd()
                        ->orderBy('announcements.created_at', 'DESC')
                        ->findAll();
                } else {
                    // guru mapel: ambil daftar class_id dari teaching_assignments
                    $tas = $this->teachingAssignmentModel
                        ->select('class_id')
                        ->where('teacher_id', $teacher['id'])
                        ->findAll();

                    $classIds = array_column($tas, 'class_id');

                    if (!empty($classIds)) {
                        $myAnnouncements = $this->announcementModel
                            ->select($select)
                            ->join('users', 'users.id = announcements.created_by', 'left')
                            ->join('classes', 'classes.id = announcements.class_id', 'left')
                            ->groupStart()
                            ->where('announcements.created_by', $userId)
                            ->orWhereIn('announcements.class_id', $classIds)
                            ->groupEnd()
                            ->orderBy('announcements.created_at', 'DESC')
                            ->findAll();
                    } else {
                        // gak punya kelas: tampilkan minimal yang dia buat sendiri
                        $myAnnouncements = $this->announcementModel
                            ->select($select)
                            ->join('users', 'users.id = announcements.created_by', 'left')
                            ->join('classes', 'classes.id = announcements.class_id', 'left')
                            ->where('announcements.created_by', $userId)
                            ->orderBy('announcements.created_at', 'DESC')
                            ->findAll();
                    }
                }
            } else {
                // tidak ada teacher record -> minimal tampilkan yg dia buat sendiri
                $myAnnouncements = $this->announcementModel
                    ->select($select)
                    ->join('users', 'users.id = announcements.created_by', 'left')
                    ->join('classes', 'classes.id = announcements.class_id', 'left')
                    ->where('announcements.created_by', $userId)
                    ->orderBy('announcements.created_at', 'DESC')
                    ->findAll();
            }

            return view('admin/announcements/index', compact(
                'announcements',
                'adminAnnouncements',
                'myAnnouncements',
                'roleId',
                'userId',
                'classMap'
            ));
        }

        // DEFAULT (ORTU / SISWA)
        if (in_array($roleId, [4, 5])) {
            $classId = null;
            $target = ($roleId == 4) ? 'ortu' : 'siswa';

            if ($roleId == 5) {
                // ambil class_id siswa
                $record = $this->db->table('student_records')
                    ->where('student_id', $user['student_id'] ?? 0)
                    ->where('status', 'aktif')
                    ->orderBy('id', 'DESC')
                    ->get()->getRowArray();
                $classId = $record['class_id'] ?? null;
            } else {
                // ORTU: ambil class_id anak (student_id di user record ortu?)
                // Asumsi: ortu punya student_id atau semacamnya. 
                // Namun untuk sekarang kita fokus ke Siswa sesuai permintaan user.
            }

            $announcements = $this->announcementModel
                ->select($select)
                ->join('users', 'users.id = announcements.created_by', 'left')
                ->join('classes', 'classes.id = announcements.class_id', 'left')
                ->where("FIND_IN_SET('$target', announcements.target) > 0")
                ->groupStart()
                ->where('announcements.class_id', $classId)
                ->orWhere('announcements.class_id', null)
                ->groupEnd()
                ->orderBy('announcements.created_at', 'DESC')
                ->findAll();

            return view('admin/announcements/index', compact(
                'announcements',
                'adminAnnouncements',
                'myAnnouncements',
                'roleId',
                'userId',
                'classMap'
            ));
        }

        // fallback
        return view('admin/announcements/index', compact(
            'announcements',
            'adminAnnouncements',
            'myAnnouncements',
            'roleId',
            'userId',
            'classMap'
        ));
    }


    public function create()
    {
        $user   = session()->get('user');
        $roleId = $user['role_id'] ?? null;

        // Ambil tahun ajaran aktif untuk filter kelas
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $yearId     = (int)($activeYear['id'] ?? 0);

        $classes         = [];
        $teacherClass    = null;
        $teachingClasses = [];
        $isHomeroom      = false;

        if (in_array($roleId, [1, 2])) {
            // Admin / Kepsek — kelas yang aktif di tahun ajaran ini
            $classes = $yearId
                ? $this->db->table('classes c')
                    ->select('c.id, c.name, c.level')
                    ->join('student_records sr',
                           "sr.class_id = c.id AND sr.academic_year_id = {$yearId} AND sr.status = 'aktif'",
                           'left')
                    ->where('c.is_active', 1)
                    ->groupBy('c.id')
                    ->orderBy('c.level', 'ASC')
                    ->orderBy('c.name', 'ASC')
                    ->get()->getResultArray()
                : $this->classModel->getActiveClasses();
        } elseif ($roleId == 3) {
            $teacher = $this->teacherModel->where('user_id', $user['id'])->first();
            if ($teacher) {
                // Wali kelas
                $teacherClass = $this->classModel->where('teacher_id', $teacher['id'])->first();
                if ($teacherClass) {
                    $isHomeroom = true;
                }

                // Kelas yang diajar di tahun aktif
                $builder = $this->classModel
                    ->select('classes.*')
                    ->join('teaching_assignments ta', 'ta.class_id = classes.id')
                    ->where('ta.teacher_id', $teacher['id'])
                    ->groupBy('classes.id');

                if ($yearId) {
                    $builder->where('ta.academic_year_id', $yearId);
                }

                $teachingClasses = $builder->findAll();
            }
        }

        return view('admin/announcements/create', [
            'classes'         => $classes,
            'teacherClass'    => $teacherClass,
            'teachingClasses' => $teachingClasses,
            'isHomeroom'      => $isHomeroom,
            'activeYear'      => $activeYear,
        ]);
    }


    public function store()
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;
        $userId = $user['id'] ?? null;

        $title = trim($this->request->getPost('title') ?? '');
        $content = trim($this->request->getPost('content') ?? '');
        $isPublic = $this->request->getPost('is_public') ? 1 : 0;

        // Ambil target (array jika checkbox)
        $targets = $this->request->getPost('target') ?? [];
        if (!is_array($targets)) {
            $targets = $targets ? [$targets] : [];
        }

        // Ambil class_id[] (bisa multi)
        $classIds = $this->request->getPost('class_id') ?? [];
        if (!is_array($classIds)) {
            // kalau single value atau empty
            $classIds = $classIds === null || $classIds === '' ? [] : [$classIds];
        }
        // sanitize -> hanya integer values
        $classIds = array_values(array_unique(array_filter(array_map('intval', $classIds), function ($v) {
            return $v > 0;
        })));

        // ROLE handling
        if (in_array($roleId, [1, 2])) {
            // Admin / Kepsek
            if (empty($targets)) {
                return redirect()->back()->withInput()->with('error', 'Pilih minimal satu target.');
            }
            $finalTargets = implode(',', $targets);
            // classIds boleh kosong (artinya semua)
        } elseif ($roleId == 3) {
            // Guru => hanya target siswa
            $finalTargets = 'siswa';

            // dapatkan teacher.id
            $teacher = $this->teacherModel->where('user_id', $userId)->first();
            if (!$teacher) {
                return redirect()->back()->withInput()->with('error', 'Data guru tidak ditemukan. Hubungi admin.');
            }

            // cek wali kelas dulu
            $teacherClass = $this->classModel->where('teacher_id', $teacher['id'])->first();
            if ($teacherClass) {
                // wali kelas: wajib 1 kelas (kelas wali)
                $classIds = [$teacherClass['id']];
            } else {
                // guru mapel: harus pilih minimal satu kelas dari kelas yang dia ampu
                if (empty($classIds)) {
                    return redirect()->back()->withInput()->with('error', 'Pilih minimal satu kelas yang Anda ampu.');
                }
                // validasi bahwa semua classIds termasuk dalam teaching_assignments untuk guru ini
                $validRows = $this->teachingAssignmentModel
                    ->where('teacher_id', $teacher['id'])
                    ->whereIn('class_id', $classIds)
                    ->findAll();
                $validIds = array_column($validRows, 'class_id');
                // intersect
                $classIds = array_values(array_intersect($classIds, $validIds));
                if (empty($classIds)) {
                    return redirect()->back()->withInput()->with('error', 'Kelas yang dipilih tidak valid untuk Anda.');
                }
            }
        } else {
            return redirect()->back()->with('error', 'Anda tidak berhak membuat pengumuman.');
        }

        // final check: dedup
        $classIds = array_values(array_unique($classIds));

        // Ambil tahun ajaran aktif
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $yearId     = $activeYear ? (int)$activeYear['id'] : null;

        // Insert: jika tidak ada classIds -> satu row dengan class_id = NULL
        $inserted = 0;
        if (empty($classIds)) {
            $data = [
                'title'            => $title,
                'content'          => $content,
                'target'           => $finalTargets,
                'class_id'         => null,
                'academic_year_id' => null,   // pengumuman global tidak terikat tahun
                'is_public'        => $isPublic,
                'created_by'       => $userId,
                'created_at'       => date('Y-m-d H:i:s'),
            ];
            $id = $this->announcementModel->insert($data);
            if ($id)
                $inserted++;
        } else {
            foreach ($classIds as $cid) {
                $data = [
                    'title'            => $title,
                    'content'          => $content,
                    'target'           => $finalTargets,
                    'class_id'         => $cid,
                    'academic_year_id' => $yearId,   // terikat ke tahun ajaran aktif
                    'is_public'        => $isPublic,
                    'created_by'       => $userId,
                    'created_at'       => date('Y-m-d H:i:s'),
                ];
                $id = $this->announcementModel->insert($data);
                if ($id)
                    $inserted++;
            }
        }

        if ($inserted > 0) {
            // ── Notifikasi: kirim ke penerima sesuai target & kelas ──────────
            $notifMsg = strip_tags($content);
            $notifMsg = mb_strlen($notifMsg) > 120 ? mb_substr($notifMsg, 0, 117) . '...' : $notifMsg;

            if (!empty($classIds)) {
                // Pengumuman per kelas → kirim ke siswa/ortu kelas tersebut
                foreach ($classIds as $cid) {
                    // Ambil ID insert terakhir untuk kelas ini
                    $annId = $this->announcementModel
                        ->where('class_id', $cid)
                        ->where('created_by', $userId)
                        ->orderBy('id', 'DESC')
                        ->first()['id'] ?? 0;

                    $notifUrl = $annId ? base_url('siswa/announcement/show/' . $annId) : null;

                    // Target: siswa (role 5) saja dari kelas ini
                    notify_class($cid, 'pengumuman_baru', $title, $notifMsg, $notifUrl, 'announcement', $annId ?: null);
                }
            } else {
                // Pengumuman global (class_id null) → kirim ke semua target sesuai $finalTargets
                $annId = $this->announcementModel
                    ->where('created_by', $userId)
                    ->where('class_id IS NULL')
                    ->orderBy('id', 'DESC')
                    ->first()['id'] ?? 0;

                $notifUrl = $annId ? base_url('siswa/announcement/show/' . $annId) : null;

                $targetParts = explode(',', $finalTargets ?? '');
                $roleMap     = ['siswa' => 5, 'ortu' => 4, 'guru' => 3];
                $roleIds     = [];
                foreach ($targetParts as $t) {
                    if (isset($roleMap[trim($t)])) {
                        $roleIds[] = $roleMap[trim($t)];
                    }
                }
                if (!empty($roleIds)) {
                    notify_by_role($roleIds, 'pengumuman_baru', $title, $notifMsg, $notifUrl);
                }
            }
            // ─────────────────────────────────────────────────────────────────

            return redirect()->to(base_url('admin/announcements'))->with('success', 'Pengumuman berhasil dibuat.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan pengumuman. Periksa log.');
        }
    }



    public function edit($id)
    {
        $announcement = $this->announcementModel->find($id);
        if (!$announcement) {
            return redirect()->to('admin/announcements')->with('error', 'Pengumuman tidak ditemukan.');
        }

        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $yearId     = (int)($activeYear['id'] ?? 0);

        // Kelas yang aktif di tahun ajaran ini
        $classes = $yearId
            ? $this->db->table('classes c')
                ->select('c.id, c.name, c.level')
                ->where('c.is_active', 1)
                ->orderBy('c.level', 'ASC')
                ->orderBy('c.name', 'ASC')
                ->get()->getResultArray()
            : $this->classModel->getActiveClasses();

        return view('admin/announcements/edit', [
            'announcement' => $announcement,
            'classes'      => $classes,
            'activeYear'   => $activeYear,
        ]);
    }

    public function update($id)
    {
        $target = $this->request->getPost('target');
        if (is_array($target))
            $target = implode(',', $target);

        $classId = $this->request->getPost('class_id') ?: null;

        // Jika ada class_id, tautkan ke tahun ajaran aktif
        $yearId = null;
        if ($classId) {
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $yearId     = $activeYear ? (int)$activeYear['id'] : null;
        }

        $data = [
            'title'            => $this->request->getPost('title'),
            'content'          => $this->request->getPost('content'),
            'target'           => $target,
            'is_public'        => $this->request->getPost('is_public') ? 1 : 0,
            'class_id'         => $classId,
            'academic_year_id' => $yearId,
        ];

        $this->announcementModel->update($id, $data);

        return redirect()->to('/admin/announcements')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->announcementModel->delete($id);
        return redirect()->to('/admin/announcements')->with('success', 'Pengumuman berhasil dihapus.');
    }
}
