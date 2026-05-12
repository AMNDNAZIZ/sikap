<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MapelMasterModel;
use App\Models\JenjangMasterModel;

class MapelMaster extends BaseController
{
    protected $mapelModel;
    protected $jenjangModel;

    public function __construct()
    {
        $this->mapelModel = new MapelMasterModel();
        $this->jenjangModel = new JenjangMasterModel();
    }

    public function index()
    {
        // ✅ Check permission - Kepsek can view
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;
        $isReadOnly = ($roleId == 2); // Kepsek is read-only
        
        $data['title'] = 'Mapel Master (CP)';
        $data['isReadOnly'] = $isReadOnly;
        
        // Get school level to filter mapel
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first();
        $schoolLevel = $school['level'] ?? 1; // Default to SD if not set
        
        // Filter mapel by school level (jenjang_id should match school level)
        $data['mapel'] = $this->mapelModel
            ->select('mapel_master.*, jenjang_master.nama as jenjang_nama')
            ->join('jenjang_master', 'jenjang_master.id = mapel_master.jenjang_id')
            ->where('mapel_master.jenjang_id', $schoolLevel)
            ->findAll();
            
        // Only show jenjang that matches school level
        $data['jenjang'] = $this->jenjangModel->where('id', $schoolLevel)->findAll();
        $data['school_level'] = $schoolLevel;
        $data['school_level_name'] = match((int)$schoolLevel) {
            1 => 'SD / Sederajat',
            2 => 'SMP / Sederajat',
            3 => 'SMA / Sederajat',
            default => 'Unknown'
        };
        
        return view('admin/mapel_master/index', $data);
    }

    public function store()
    {
        // ✅ Check permission - Kepsek cannot create/update
        $user = session()->get('user');
        if (($user['role_id'] ?? null) == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah/mengubah data.');
        }
        
        // Get school level for validation
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first();
        $schoolLevel = $school['level'] ?? 1;
        
        $id = $this->request->getPost('id');
        $jenjangId = $this->request->getPost('jenjang_id');
        
        // Validate that jenjang_id matches school level
        if ((int)$jenjangId !== (int)$schoolLevel) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Jenjang tidak sesuai dengan level sekolah. Silakan pilih jenjang yang sesuai.');
        }
        
        $data = [
            'jenjang_id' => $jenjangId,
            'kode'       => $this->request->getPost('kode'),
            'nama'       => $this->request->getPost('nama'),
            'kelompok'   => $this->request->getPost('kelompok'),
            'is_active'  => $this->request->getPost('is_active') ?? 1,
        ];

        if ($id) {
            $this->mapelModel->update($id, $data);
            $msg = 'Mapel Master berhasil diperbarui.';
        } else {
            $this->mapelModel->insert($data);
            $msg = 'Mapel Master berhasil ditambahkan.';
        }

        return redirect()->to(base_url('admin/mapel-master'))->with('success', $msg);
    }

    public function delete($id)
    {
        // ✅ Check permission - Kepsek cannot delete
        $user = session()->get('user');
        if (($user['role_id'] ?? null) == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menghapus data.');
        }
        
        $this->mapelModel->delete($id);
        return redirect()->to(base_url('admin/mapel-master'))->with('success', 'Mapel Master berhasil dihapus.');
    }
}
