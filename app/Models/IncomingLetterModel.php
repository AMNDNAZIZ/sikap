<?php

namespace App\Models;

use CodeIgniter\Model;

class IncomingLetterModel extends Model
{
    protected $table         = 'incoming_letters';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'letter_number',
        'sender_name',
        'sender_agency',
        'subject',
        'received_at',
        'letter_date',
        'letter_category',
        'disposition',
        'disposition_to',
        'scan_url',
        'scan_path',
        'file_type',
        'file_size_bytes',
        'ocr_processed',
        'ocr_confidence',
        'ocr_raw_text',
        'created_by',
    ];

    protected $validationRules = [
        'sender_name' => 'required|max_length[255]',
        'subject'     => 'required',
        'received_at' => 'required|valid_date',
    ];

    /**
     * Get filtered + paginated incoming letters
     */
    public function getFiltered(array $params = []): array
    {
        $builder = $this->db->table('incoming_letters');
        $builder->orderBy('received_at', 'DESC');

        if (!empty($params['search'])) {
            $s = $params['search'];
            $builder->groupStart();
            $builder->like('letter_number', $s);
            $builder->orLike('sender_name', $s);
            $builder->orLike('subject', $s);
            $builder->groupEnd();
        }
        if (!empty($params['date_from'])) {
            $builder->where('received_at >=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $builder->where('received_at <=', $params['date_to']);
        }
        if (!empty($params['category'])) {
            $builder->where('letter_category', $params['category']);
        }

        $limit  = (int) ($params['limit'] ?? 50);
        $page   = (int) ($params['page'] ?? 1);
        $offset = ($page - 1) * $limit;

        $total = $builder->countAllResults(false);
        $data  = $builder->limit($limit, $offset)->get()->getResultArray();

        return compact('data', 'total', 'page', 'limit');
    }

    /**
     * Get quick stats for dashboard
     */
    public function getStats(): array
    {
        $db = $this->db;
        return [
            'bulan_ini' => $db->table('incoming_letters')
                ->where('MONTH(received_at)', date('m'))
                ->where('YEAR(received_at)', date('Y'))
                ->countAllResults(),
            'tahun_ini' => $db->table('incoming_letters')
                ->where('YEAR(received_at)', date('Y'))
                ->countAllResults(),
            'total' => $db->table('incoming_letters')
                ->countAllResults(),
        ];
    }
}
