<?php namespace App\Models;

use CodeIgniter\Model;

class LaporanModel extends Model
{
    protected $db;
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function terima_per_tahun($tahunAjaranId, $posBayarId = null)
    {
        $builder = $this->db->table('pembayaran p')
            ->select('ta.nama AS tahun_ajaran, SUM(p.jumlah_bayar) AS total')
            ->join('tagihan_siswa t','t.id=p.tagihan_id')
            ->join('jenis_bayar j','j.id=t.jenis_bayar_id')
            ->join('pos_bayar pos','pos.id=j.pos_bayar_id')
            ->join('tahun_ajaran ta','ta.id=t.tahun_ajaran_id')
            ->where('t.tahun_ajaran_id', $tahunAjaranId);
        if ($posBayarId) $builder->where('pos.id', $posBayarId);
        return $builder->groupBy('ta.id')->get()->getRowArray();
    }

    public function terima_bulanan($tahunAjaranId): array
    {
        return $this->db->table('pembayaran p')
            ->select('MONTH(p.tanggal) AS bulan, SUM(p.jumlah_bayar) AS total')
            ->join('tagihan_siswa t', 't.id = p.tagihan_id')
            ->where('t.tahun_ajaran_id', $tahunAjaranId)
            ->where('t.tipe', 'bulanan')
            ->groupBy('MONTH(p.tanggal)')
            ->orderBy('MONTH(p.tanggal)', 'asc')
            ->get()
            ->getResultArray();
    }

    public function terima_rentang(?string $startDate, ?string $endDate, ?int $posBayarId = null): array
    {
        $builder = $this->db->table('pembayaran p')
            ->select('ta.nama AS tahun_ajaran, SUM(p.jumlah_bayar) AS total')
            ->join('tagihan_siswa t', 't.id = p.tagihan_id')
            ->join('jenis_bayar j', 'j.id = t.jenis_bayar_id')
            ->join('pos_bayar pos', 'pos.id = j.pos_bayar_id')
            ->join('tahun_ajaran ta', 'ta.id = t.tahun_ajaran_id');
    
        if ($startDate) {
            $builder->where('p.tanggal >=', $startDate);
        }
    
        if ($endDate) {
            $builder->where('p.tanggal <=', $endDate);
        }
    
        if ($posBayarId) {
            $builder->where('pos.id', $posBayarId);
        }
    
        return $builder->groupBy('ta.id')->orderBy('ta.urutan', 'desc')->get()->getResultArray();
    }

}
