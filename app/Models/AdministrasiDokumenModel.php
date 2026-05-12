<?php

namespace App\Models;

use CodeIgniter\Model;

class AdministrasiDokumenModel extends Model
{
    protected $table = 'dokumen_administrasi';
    protected $primaryKey = 'id';
    protected $allowedFields = ['teacher_id', 'subject_id', 'academic_year_id', 'tipe_dokumen', 'file_path', 'content_json', 'status'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
