<?php

namespace App\Models;

use CodeIgniter\Model;

class RaporConfigModel extends Model
{
    protected $table            = 'rapor_configs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'academic_year_id',
        'semester',
        'rapor_type',
        'report_date',
        'place_city',
        'ttd_layout',
        'show_signature',
        'show_watermark',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Cache hasil pengecekan kolom rapor_type di database
     */
    private ?bool $hasRaporTypeCache = null;

    private function hasRaporType(): bool
    {
        if ($this->hasRaporTypeCache === null) {
            $this->hasRaporTypeCache = $this->db->fieldExists('rapor_type', $this->table);
        }
        return $this->hasRaporTypeCache;
    }

    /**
     * Ambil konfigurasi titimangsa untuk tahun ajaran & semester aktif
     * $type: 'semester' atau 'pts'
     */
    public function getConfig(int $yearId, string $semester, string $type = 'semester'): ?array
    {
        $hasRaporType = $this->hasRaporType();

        $builder = $this->where('academic_year_id', $yearId)
            ->where('semester', (string) $semester);

        if ($hasRaporType) {
            $builder->where('rapor_type', $type);
        }

        $cfg = $builder->first();

        // Fallback: jika type 'pts' belum disetel terpisah, fallback ke 'semester' bila ada
        if (!$cfg && $type === 'pts' && $hasRaporType) {
            $cfg = $this->where('academic_year_id', $yearId)
                ->where('semester', (string) $semester)
                ->where('rapor_type', 'semester')
                ->first();
        }

        return $cfg;
    }

    /**
     * Simpan / perbarui konfigurasi titimangsa dan cetak rapor
     */
    public function saveConfig(int $yearId, string $semester, ?string $reportDate, ?string $placeCity, string $ttdLayout = '1_baris', int $showSig = 0, int $showWm = 0, string $type = 'semester'): bool
    {
        $hasRaporType = $this->hasRaporType();

        $builder = $this->where('academic_year_id', $yearId)
            ->where('semester', (string) $semester);

        if ($hasRaporType) {
            $builder->where('rapor_type', $type);
        }

        $existing = $builder->first();

        $data = [
            'academic_year_id' => $yearId,
            'semester'         => (string) $semester,
            'report_date'      => $reportDate ?: null,
            'place_city'       => $placeCity ?: null,
            'ttd_layout'       => in_array($ttdLayout, ['1_baris', '2_baris'], true) ? $ttdLayout : '1_baris',
            'show_signature'   => $showSig ? 1 : 0,
            'show_watermark'   => $showWm ? 1 : 0,
        ];

        if ($hasRaporType) {
            $data['rapor_type'] = $type;
        }

        if ($existing) {
            return (bool) $this->update($existing['id'], $data);
        }

        return (bool) $this->insert($data);
    }
}
