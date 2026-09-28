<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BkWarningModel;

class BkWarning extends BaseController
{
    public function index()
    {
        $warningModel = new BkWarningModel();
        
        $data = [
            'title'    => 'Peta Kerawanan Siswa (Early Warning System)',
            'students' => $warningModel->getAtRiskStudents()
        ];

        return view('admin/counseling_warning/index', $data);
    }
}
