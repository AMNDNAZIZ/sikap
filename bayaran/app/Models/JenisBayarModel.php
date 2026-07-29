<?php

namespace App\Models;

use CodeIgniter\Model;

class JenisBayarModel extends Model
{
    protected $table = 'jenis_bayar';
    protected $allowedFields = [
        'pos_bayar_id',
        'tahun_ajaran_id',
        'nama_jenis_bayar',
        'tipe',
        'tarif_default',
        'wajib',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;

    public function withRelations()
    {
        return $this->select('jenis_bayar.*, pos_bayar.nama_pos, tahun_ajaran.nama as tahun')
            ->join('pos_bayar', 'pos_bayar.id = jenis_bayar.pos_bayar_id')
            ->join('tahun_ajaran', 'tahun_ajaran.id = jenis_bayar.tahun_ajaran_id');
    }
}
