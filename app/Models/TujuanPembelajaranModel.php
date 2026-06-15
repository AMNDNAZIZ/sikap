<?php

namespace App\Models;

use CodeIgniter\Model;

class TujuanPembelajaranModel extends Model
{
    protected $table = 'tujuan_pembelajaran';
    protected $primaryKey = 'id';
    protected $allowedFields = ['subject_id', 'cp_master_id', 'atp_id', 'atp_elemen_id', 'elemen', 'lingkup_materi', 'kode_tp', 'deskripsi', 'fase', 'kelas'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
