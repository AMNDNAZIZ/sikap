<?php

namespace App\Models;

use CodeIgniter\Model;

class PembayaranModel extends Model
{
    protected $table = 'pembayaran';

    protected $allowedFields = [
        'tagihan_id',
        'tanggal',
        'jumlah_bayar',
        'cara_bayar',
        'angsuran_ke',
        'keterangan',
        'created_at',
        'updated_at',
        'sinkronisasi_pos',
        'sinkron_id',
    ];

    protected $useTimestamps = true;

    protected $validationRules = [
        'tagihan_id'   => 'required|is_natural_no_zero',
        'tanggal'      => 'required|valid_date[Y-m-d]',
        'jumlah_bayar' => 'required|greater_than[0]',
        'cara_bayar'   => 'permit_empty|in_list[Tunai,Transfer,KJP,BPMS]',
    ];
}
