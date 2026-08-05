<?php

namespace App\Models;

use CodeIgniter\Model;

class TransaksiPosModel extends Model
{
    protected $table = 'transaksi_pos';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'pos_bayar_id', 'tanggal', 'tipe', 'jumlah', 'keterangan', 'sinkron_id',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';

    public function getRingkasan($posId)
    {
        $masuk = $this->where('pos_bayar_id', $posId)
            ->where('tipe', 'masuk')
            ->selectSum('jumlah')
            ->first()['jumlah'] ?? 0;

        $keluar = $this->where('pos_bayar_id', $posId)
            ->where('tipe', 'keluar')
            ->selectSum('jumlah')
            ->first()['jumlah'] ?? 0;

        return [
            'masuk' => (int)$masuk,
            'keluar' => (int)$keluar,
            'saldo' => (int)$masuk - (int)$keluar
        ];
    }

    public function getRiwayat($posId)
    {
        return $this->where('pos_bayar_id', $posId)
            ->orderBy('tanggal', 'desc')
            ->findAll();
    }
}
