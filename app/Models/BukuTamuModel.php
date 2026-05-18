<?php

namespace App\Models;

use CodeIgniter\Model;

class BukuTamuModel extends Model
{
    protected $table      = 'buku_tamu';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'guest_type',
        'nama',
        'no_hp',
        'nip',
        'instansi',
        'alamat',
        'is_ortu_siswa',
        'tujuan',
        'bertemu_dengan',
        'ip_address',
    ];

    protected $useTimestamps = true;      // CI4 otomatis set created_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';         // tidak ada kolom updated_at

    protected $validationRules = [
        'guest_type' => 'required|in_list[umum,dinas]',
        'nama'       => 'required|max_length[100]',
        'tujuan'     => 'required',
    ];

    /**
     * Get filtered data for admin dashboard
     */
    public function getFiltered(string $type = '', string $month = '', string $year = '', string $search = ''): array
    {
        $builder = $this->db->table('buku_tamu');
        $builder->orderBy('created_at', 'DESC');

        if ($type && $type !== 'semua') {
            $builder->where('guest_type', $type);
        }

        if ($year) {
            $builder->where('YEAR(created_at)', $year);
        }

        if ($month) {
            $builder->where('MONTH(created_at)', $month);
        }

        if ($search) {
            $builder->groupStart();
            $builder->like('nama', $search);
            $builder->orLike('instansi', $search);
            $builder->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get statistics for dashboard
     */
    public function getStats(): array
    {
        $db    = $this->db;
        $today = date('Y-m-d');

        return [
            'hari_ini'    => $db->table('buku_tamu')->where('DATE(created_at)', $today)->countAllResults(),
            'bulan_ini'   => $db->table('buku_tamu')->where('MONTH(created_at)', date('m'))->where('YEAR(created_at)', date('Y'))->countAllResults(),
            'total_umum'  => $db->table('buku_tamu')->where('guest_type', 'umum')->countAllResults(),
            'total_dinas' => $db->table('buku_tamu')->where('guest_type', 'dinas')->countAllResults(),
        ];
    }
}
