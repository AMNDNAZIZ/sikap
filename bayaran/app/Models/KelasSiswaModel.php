<?php

namespace App\Models;

use CodeIgniter\Model;

class KelasSiswaModel extends Model
{
    protected $table = 'kelas_siswa';
    protected $allowedFields = [
        'siswa_id', 
        'kelas_id', 
        'tahun_ajaran_id', 
        'status', 
        'tanggal_masuk', 
        'tanggal_keluar'
    ];
    protected $useTimestamps = true;

    public function withRelations()
    {
        return $this->select('kelas_siswa.*, siswa.nis, siswa.nama, kelas.nama_kelas, tahun_ajaran.nama as tahun')
            ->join('siswa', 'siswa.id = kelas_siswa.siswa_id')
            ->join('kelas', 'kelas.id = kelas_siswa.kelas_id', 'left') // pakai LEFT JOIN biar aman kalau kelas null
            ->join('tahun_ajaran', 'tahun_ajaran.id = kelas_siswa.tahun_ajaran_id');
    }

    // ✅ Tambahan modern: filter by status
    public function getByStatus($tahunId, $kelasId = null, $status = null)
    {
        $builder = $this->withRelations()->where('kelas_siswa.tahun_ajaran_id', $tahunId);
        if ($kelasId) $builder->where('kelas_siswa.kelas_id', $kelasId);
        if ($status)  $builder->where('kelas_siswa.status', $status);
        return $builder->orderBy('siswa.nama', 'ASC')->findAll();
    }

    /**
     * Untuk arsip siswa (lulus/pindah/do).
     * Kelas terakhir diambil dari baris sebelumnya yang bukan status akhir (lulus/pindah/do).
     * Data lama: status '' (kosong, akibat set 'nonaktif' yang tidak ada di ENUM).
     * Data baru: status 'aktif' (baris lama tidak diubah, dibedakan oleh tahun_ajaran_id).
     */
    public function getArsip($tahunId, $status)
    {
        // Subquery: ambil nama_kelas terakhir per siswa
        // dari baris yang bukan status akhir (lulus/pindah/do) dan kelas_id valid (> 0)
        $subquery = "
            SELECT ks2.siswa_id, k2.nama_kelas
            FROM kelas_siswa ks2
            JOIN kelas k2 ON k2.id = ks2.kelas_id
            WHERE ks2.status NOT IN ('lulus', 'pindah', 'do')
              AND ks2.kelas_id > 0
              AND ks2.id = (
                  SELECT MAX(ks3.id)
                  FROM kelas_siswa ks3
                  WHERE ks3.siswa_id = ks2.siswa_id
                    AND ks3.status NOT IN ('lulus', 'pindah', 'do')
                    AND ks3.kelas_id > 0
              )
        ";

        return $this->db->table('kelas_siswa')
            ->select('kelas_siswa.*, siswa.nis, siswa.nama, kelas_terakhir.nama_kelas AS nama_kelas, tahun_ajaran.nama as tahun')
            ->join('siswa', 'siswa.id = kelas_siswa.siswa_id')
            ->join('tahun_ajaran', 'tahun_ajaran.id = kelas_siswa.tahun_ajaran_id')
            ->join("({$subquery}) kelas_terakhir", 'kelas_terakhir.siswa_id = kelas_siswa.siswa_id', 'left')
            ->where('kelas_siswa.tahun_ajaran_id', $tahunId)
            ->where('kelas_siswa.status', $status)
            ->orderBy('siswa.nama', 'ASC')
            ->get()
            ->getResultArray();
    }
}
