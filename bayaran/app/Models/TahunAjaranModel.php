<?php

namespace App\Models;

use CodeIgniter\Model;

class TahunAjaranModel extends Model
{
    protected $table      = 'tahun_ajaran';
    protected $allowedFields = ['nama', 'mulai', 'selesai', 'urutan', 'aktif'];
    protected $useTimestamps  = true;
}
