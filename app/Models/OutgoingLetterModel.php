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
        'classification_code',
        'subject',
        'sifat',
        'recipient_type',
        'recipient_ref_id',
        'recipient_name',
        'recipient_detail',
        'is_multi_recipient',
        'recipients',
        'letter_data',
        'use_kop',
        'template_id',
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

        // Whitelist kolom pengurutan yang aman
        $allowedSort = [
            'issued_at'       => 'issued_at',
            'letter_number'   => 'sequence_number',
            'sequence_number' => 'sequence_number',
            'recipient_name'  => 'recipient_name',
            'subject'         => 'subject',
            'letter_type'     => 'letter_type',
            'status'          => 'status',
            'created_at'      => 'created_at',
        ];

        $sortBy = $params['sort_by'] ?? 'issued_at';
        $order  = strtoupper($params['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $sortColumn = $allowedSort[$sortBy] ?? 'issued_at';

        $builder->orderBy($sortColumn, $order);

        // Pengurutan sekunder untuk menjamin konsistensi baris data
        if ($sortColumn !== 'sequence_number' && $sortColumn !== 'id') {
            $builder->orderBy('sequence_number', $order);
        }

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
     * Get next sequence number (atomic via DB transaction & collision-free)
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

        $seq = (int) ($row['last_sequence'] ?? 1);

        // Ensure seq is at least MAX(sequence_number) + 1 from outgoing_letters for this year
        $maxRow = $db->table('outgoing_letters')
                     ->selectMax('sequence_number')
                     ->where('YEAR(issued_at)', $year)
                     ->get()->getRowArray();

        $maxSeq = (int) ($maxRow['sequence_number'] ?? 0);
        if ($maxSeq >= $seq) {
            $seq = $maxSeq + 1;
            $db->table('letter_number_sequences')
               ->where('year', $year)
               ->update(['last_sequence' => $seq, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        // Double check letter_number uniqueness in outgoing_letters
        $settings = new \App\Models\SettingsModel();
        $padding  = (int) ($settings->getValue('letter_number_padding') ?? 3);

        while (true) {
            $letterNo = self::buildLetterNumber($seq, $year, $padding);
            $exists   = $db->table('outgoing_letters')->where('letter_number', $letterNo)->countAllResults();
            if ($exists == 0) {
                break;
            }
            $seq++;
            $db->table('letter_number_sequences')
               ->where('year', $year)
               ->update(['last_sequence' => $seq, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        return $seq;
    }

    /**
     * Convert integer month to Roman numeral
     */
    public static function monthToRoman(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$month] ?? 'I';
    }

    /**
     * Build formatted letter number: [seq]/[code]/[year] or customizable
     */
    public static function buildLetterNumber(int $seq, int $year, int $padding = 3, ?string $code = null, ?int $month = null): string
    {
        $code = !empty($code) ? trim($code) : 'PK.01.01';
        $seqPadded = str_pad((string) $seq, $padding, '0', STR_PAD_LEFT);

        return $seqPadded . '/' . $code . '/' . $year;
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
