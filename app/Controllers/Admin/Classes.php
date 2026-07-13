<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\TeacherModel;

class Classes extends BaseController
{
    public function index()
    {
        $model = new ClassModel();
        $data['classes'] = $model->select('classes.*, teachers.name as teacher_name')
            ->join('teachers', 'teachers.id = classes.teacher_id', 'left')
            ->orderBy('classes.level', 'ASC')
            ->orderBy('classes.name', 'ASC')
            ->findAll();

        // Get school level info
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first() ?: [];
        $data['school_level'] = $school['level'] ?? 1;
        $data['school_level_name'] = match((int)($school['level'] ?? 1)) {
            1 => 'SD / Sederajat',
            2 => 'SMP / Sederajat',
            3 => 'SMA / Sederajat',
            default => 'Unknown'
        };
        $data['max_level'] = $this->getMaxLevel();
        $data['level_range'] = match((int)($school['level'] ?? 1)) {
            1 => '1-6',
            2 => '7-9',
            3 => '10-12',
            default => '1-6'
        };

        return view('admin/classes/index', $data);
    }

    private function getMaxLevel()
    {
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first() ?: [];
        $levelMapping = [
            1 => 6,  // SD
            2 => 9,  // SMP
            3 => 12, // SMA
        ];
        return $levelMapping[$school['level'] ?? 1] ?? 6;
    }

    public function create()
    {
        $teacherModel = new TeacherModel();
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first() ?: [];
        
        $maxLevel = $this->getMaxLevel();
        $minLevel = match((int)($school['level'] ?? 1)) {
            1 => 1,  // SD: 1-6
            2 => 7,  // SMP: 7-9
            3 => 10, // SMA: 10-12
            default => 1
        };
        
        $data = [
            'teachers' => $teacherModel->findAll(),
            'maxLevel' => $maxLevel,
            'minLevel' => $minLevel,
            'school_level' => $school['level'] ?? 1,
            'school_level_name' => match((int)($school['level'] ?? 1)) {
                1 => 'SD / Sederajat',
                2 => 'SMP / Sederajat',
                3 => 'SMA / Sederajat',
                default => 'Unknown'
            },
            'level_range' => match((int)($school['level'] ?? 1)) {
                1 => '1-6',
                2 => '7-9',
                3 => '10-12',
                default => '1-6'
            },
        ];

        return view('admin/classes/create', $data);
    }

    public function store()
    {
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first() ?: [];
        $schoolLevel = $school['level'] ?? 1;
        
        $maxLevel = $this->getMaxLevel();
        $minLevel = match((int)$schoolLevel) {
            1 => 1,  // SD: 1-6
            2 => 7,  // SMP: 7-9
            3 => 10, // SMA: 10-12
            default => 1
        };
        
        $inputLevel = $this->request->getPost('level');
        
        // Validate level range based on school level
        if ($inputLevel < $minLevel || $inputLevel > $maxLevel) {
            $levelRange = match((int)$schoolLevel) {
                1 => '1-6 (SD)',
                2 => '7-9 (SMP)',
                3 => '10-12 (SMA)',
                default => '1-6'
            };
            return redirect()->back()
                ->withInput()
                ->with('error', "Tingkat kelas harus antara {$levelRange} sesuai dengan level sekolah.");
        }
        
        $rules = [
            'name' => 'required',
            'level' => "required|is_natural_no_zero|greater_than_equal_to[$minLevel]|less_than_equal_to[$maxLevel]",
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Validasi gagal: Tingkat harus antara ' . $minLevel . ' dan ' . $maxLevel);
        }

        $model = new ClassModel();
        $model->save([
            'name' => $this->request->getPost('name'),
            'level' => $inputLevel,
            'teacher_id' => $this->request->getPost('teacher_id') ?: null,
        ]);

        return redirect()->to('/admin/classes')->with('success', 'Kelas berhasil ditambahkan');
    }

    public function edit($id)
    {
        $model = new ClassModel();
        $teacherModel = new TeacherModel();
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first() ?: [];
        
        $maxLevel = $this->getMaxLevel();
        $minLevel = match((int)($school['level'] ?? 1)) {
            1 => 1,  // SD: 1-6
            2 => 7,  // SMP: 7-9
            3 => 10, // SMA: 10-12
            default => 1
        };

        $data = [
            'class' => $model->find($id),
            'teachers' => $teacherModel->findAll(),
            'maxLevel' => $maxLevel,
            'minLevel' => $minLevel,
            'school_level' => $school['level'] ?? 1,
            'school_level_name' => match((int)($school['level'] ?? 1)) {
                1 => 'SD / Sederajat',
                2 => 'SMP / Sederajat',
                3 => 'SMA / Sederajat',
                default => 'Unknown'
            },
            'level_range' => match((int)($school['level'] ?? 1)) {
                1 => '1-6',
                2 => '7-9',
                3 => '10-12',
                default => '1-6'
            },
        ];

        return view('admin/classes/edit', $data);
    }

    public function update($id)
    {
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first() ?: [];
        $schoolLevel = $school['level'] ?? 1;
        
        $maxLevel = $this->getMaxLevel();
        $minLevel = match((int)$schoolLevel) {
            1 => 1,  // SD: 1-6
            2 => 7,  // SMP: 7-9
            3 => 10, // SMA: 10-12
            default => 1
        };
        
        $inputLevel = $this->request->getPost('level');
        
        // Validate level range based on school level
        if ($inputLevel < $minLevel || $inputLevel > $maxLevel) {
            $levelRange = match((int)$schoolLevel) {
                1 => '1-6 (SD)',
                2 => '7-9 (SMP)',
                3 => '10-12 (SMA)',
                default => '1-6'
            };
            return redirect()->back()
                ->withInput()
                ->with('error', "Tingkat kelas harus antara {$levelRange} sesuai dengan level sekolah.");
        }
        
        $rules = [
            'name' => 'required',
            'level' => "required|is_natural_no_zero|greater_than_equal_to[$minLevel]|less_than_equal_to[$maxLevel]",
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Validasi gagal: Tingkat harus antara ' . $minLevel . ' dan ' . $maxLevel);
        }

        $model = new ClassModel();
        $model->update($id, [
            'name' => $this->request->getPost('name'),
            'level' => $inputLevel,
            'teacher_id' => $this->request->getPost('teacher_id') ?: null,
        ]);

        return redirect()->to('/admin/classes')->with('success', 'Kelas berhasil diperbarui');
    }

    public function delete($id)
    {
        $model = new ClassModel();
        $db = db_connect();

        // Cegah hapus kelas jika masih ada ATP
        $atpCount = $db->table('alur_tujuan_pembelajaran')
            ->where('class_id', $id)->countAllResults();
        if ($atpCount > 0) {
            return redirect()->back()->with('error',
                'Kelas tidak dapat dihapus karena masih memiliki data ATP (' . $atpCount . ' alur). ' .
                'Hapus ATP terlebih dahulu melalui menu Administrasi Guru.'
            );
        }

        // Cegah hapus kelas jika masih ada Modul Ajar
        $modulCount = $db->table('modul_ajar')
            ->where('class_id', $id)->countAllResults();
        if ($modulCount > 0) {
            return redirect()->back()->with('error',
                'Kelas tidak dapat dihapus karena masih memiliki ' . $modulCount . ' Modul Ajar. ' .
                'Hapus Modul Ajar terlebih dahulu melalui menu Administrasi Guru.'
            );
        }

        $model->delete($id);
        return redirect()->to('/admin/classes')->with('success', 'Kelas berhasil dihapus');
    }

    public function toggleActive($id)
    {
        $model  = new ClassModel();
        $class  = $model->find($id);

        if (!$class) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan.');
        }

        $newStatus = $class['is_active'] ? 0 : 1;
        $model->update($id, ['is_active' => $newStatus]);

        $label = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Kelas '{$class['name']}' berhasil {$label}.");
    }
}
