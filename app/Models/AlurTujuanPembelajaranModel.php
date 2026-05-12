<?php

namespace App\Models;

use CodeIgniter\Model;

class AlurTujuanPembelajaranModel extends Model
{
    protected $table = 'alur_tujuan_pembelajaran';
    protected $primaryKey = 'id';
    protected $allowedFields = ['subject_id', 'class_id', 'cp_master_id', 'lingkup_materi', 'tp_id', 'alur_tujuan', 'urutan', 'semester', 'alokasi_waktu'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
