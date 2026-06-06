<?php

namespace App\Models;

use CodeIgniter\Model;

class OutgoingLetterModel extends Model
{
    protected $table         = 'outgoing_letters';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'qr_code_id',
        'sequence_number',
        'letter_number',
        'issued_at',
        'letter_type',
        'subject',
        'sifat',
        'recipient_type',
        'recipient_ref_id',
        'recipient_name',
        'recipient_detail',
        'is_multi_recipient',
        'recipients',
        'letter_data',
        'is_external',
        'external_notes',
        'reference_incoming_id',
        'pdf_url',
        'pdf_path',
        'file_size_bytes',
        'status',
        'revoked_at',
        'revoke_reason',
        'principal_name_snapshot',
        'principal_nip_snapshot',
        'created_by',
    ];

    /**
     * Get filtered + paginated outgoing letters
     */
    public function getFiltered(array $params = []): array
    {
        $builder = $this->db->table('outgoing_letters');
        $builder->orderBy('issued_at', 'DESC');
        $builder->orderBy('sequence_number', 'DESC');

        if (!empty($params['search'])) {
            $s = $params['search'];
            $builder->groupStart();
            $builder->like('letter_number', $s);
            $builder->orLike('recipient_name', $s);
            $builder->orLike('subject', $s);
            $builder->groupEnd();
        }
        if (!empty($params['date_from'])) {
            $builder->where('issued_at >=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $builder->where('issued_at <=', $params['date_to']);
        }
        if (!empty($params['letter_type'])) {
            $builder->where('letter_type', $params['letter_type']);
        }
        if (!empty($params['status'])) {
            $builder->where('status', $params['status']);
        }

        $limit  = (int) ($params['limit'] ?? 50);
        $page   = (int) ($params['page'] ?? 1);
        $offset = ($page - 1) * $limit;

        $total = $builder->countAllResults(false);
        $data  = $builder->limit($limit, $offset)->get()->getResultArray();

        return compact('data', 'total', 'page', 'limit');
    }

    /**
     * Get next sequence number (atomic via DB transaction)
     */
    public function getNextSequenceNumber(int $year): int
    {
        $db = $this->db;
        $db->query("INSERT INTO letter_number_sequences (year, last_sequence, updated_at)
                    VALUES (?, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                        last_sequence = last_sequence + 1,
                        updated_at    = NOW()", [$year]);

        $row = $db->table('letter_number_sequences')
                  ->where('year', $year)
                  ->get()->getRowArray();

        return (int) $row['last_sequence'];
    }

    /**
     * Build formatted letter number: 045/PK.01.01/2026
     */
    public static function buildLetterNumber(int $seq, int $year, int $padding = 3): string
    {
        return str_pad((string) $seq, $padding, '0', STR_PAD_LEFT) . '/PK.01.01/' . $year;
    }

    /**
     * Get quick stats for dashboard
     */
    public function getStats(): array
    {
        $db = $this->db;
        return [
            'bulan_ini' => $db->table('outgoing_letters')
                ->where('MONTH(issued_at)', date('m'))
                ->where('YEAR(issued_at)', date('Y'))
                ->where('status', 'active')
                ->countAllResults(),
            'tahun_ini' => $db->table('outgoing_letters')
                ->where('YEAR(issued_at)', date('Y'))
                ->where('status', 'active')
                ->countAllResults(),
            'total' => $db->table('outgoing_letters')
                ->countAllResults(),
        ];
    }

    /**
     * Find by QR code ID (for public verification)
     */
    public function findByQrCode(string $qrCodeId): ?array
    {
        return $this->where('qr_code_id', $qrCodeId)
                    ->select('id, letter_number, letter_type, recipient_name, subject, issued_at, status, revoke_reason, created_at')
                    ->first();
    }

    /**
     * Decode JSON fields automatically
     */
    public function decodeJson(array $row): array
    {
        foreach (['recipient_detail', 'recipients', 'letter_data'] as $field) {
            if (!empty($row[$field]) && is_string($row[$field])) {
                $row[$field] = json_decode($row[$field], true);
            }
        }
        return $row;
    }
}
