<?php

namespace App\Models;

use CodeIgniter\Model;

class TagihanSiswaModel extends Model
{
    protected $table            = 'tagihan_siswa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'siswa_id',
        'jenis_bayar_id',
        'jumlah',
        'terbayar',
        'status',
        'tipe',
        'bulan',
        'tahun_ajaran_id',
        'cara_bayar',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'siswa_id'        => 'required|integer',
        'jenis_bayar_id'  => 'required|integer',
        'jumlah'          => 'required|numeric',
        'terbayar'        => 'permit_empty|numeric',
        'status'          => 'required|in_list[lunas,belum]',
        'tipe'            => 'required|in_list[bulanan,bebas]',
        'bulan'           => 'permit_empty|in_list[1,2,3,4,5,6,7,8,9,10,11,12]',
        'tahun_ajaran_id' => 'required|integer',
        'cara_bayar'      => 'permit_empty|in_list[Tunai,Transfer,KJP,BPMS]',
    ];
}
