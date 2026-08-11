<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'setting';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['nama_aplikasi', 'nama_sekolah', 'alamat', 'no_hp', 'kepala_sekolah', 'logo', 'website', 'email', 'persentase_yayasan'];
    protected $useTimestamps    = true;
}
