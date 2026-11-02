<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtBapDocumentV3Model
 *
 * ESPATA Assessment Intelligence V3 - Digital BAP Document Management.
 * Manages physical/digital proctor exam record headers with cryptographic hash sealing.
 */
class CbtBapDocumentV3Model extends Model
{
    protected $table            = 'cbt_bap_documents_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'test_id',
        'bap_number',
        'room_code',
        'proctor_name',
        'supervisor_name',
        'total_registered',
        'total_present',
        'total_absent',
        'document_sha256',
        'signer_identity',
        'signed_at',
        'verification_status',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    public const STATUS_UNVERIFIED    = 'UNVERIFIED';
    public const STATUS_PROBABLE      = 'PROBABLE';
    public const STATUS_AUTHENTICATED = 'AUTHENTICATED';

    /**
     * Find document by unique SHA-256 hash.
     */
    public function findByDocHash(string $hash): ?array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            return null;
        }
        return $this->where('document_sha256', $hash)->first();
    }

    /**
     * Get BAP by Test ID and Room Code.
     */
    public function getByTestAndRoom(int $testId, string $roomCode): ?array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            return null;
        }
        return $this->where('test_id', $testId)
            ->where('room_code', $roomCode)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * List documents with optional filtering.
     */
    public function listDocuments(array $filters = []): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            return [];
        }
        $builder = $this->builder();

        if (!empty($filters['test_id'])) {
            $builder->where('test_id', $filters['test_id']);
        }
        if (!empty($filters['room_code'])) {
            $builder->where('room_code', $filters['room_code']);
        }
        if (!empty($filters['verification_status'])) {
            $builder->where('verification_status', $filters['verification_status']);
        }
        if (!empty($filters['proctor_name'])) {
            $builder->like('proctor_name', $filters['proctor_name']);
        }

        return $builder->orderBy('id', 'DESC')->get()->getResultArray();
    }
}

