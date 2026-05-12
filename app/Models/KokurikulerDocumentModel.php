<?php

namespace App\Models;

use CodeIgniter\Model;

class KokurikulerDocumentModel extends Model
{
    protected $table = 'kokurikuler_documents';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'year_id',
        'semester',
        'fase',
        'level_kelas',
        'class_id',
        'jumlah_pertemuan',
        'dimensi_profil',
        'tema',
        'jenis_kokurikuler',
        'bentuk_kegiatan_konkret',
        'kegiatan_detail',
        'tujuan_pembelajaran',
        'praktik_pedagogis',
        'lingkungan_belajar',
        'kemitraan',
        'teknologi_digital',
        'kegiatan_kokurikuler',
        'status',
        'is_template',
        'parent_id',
        'used_by_teacher_id',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get documents with creator info
     */
    public function getDocumentsWithCreator($userId = null, $roleId = null)
    {
        $builder = $this->select('kokurikuler_documents.*, users.fullname as creator_name, academic_years.year as year_name')
            ->join('users', 'users.id = kokurikuler_documents.created_by', 'left')
            ->join('academic_years', 'academic_years.id = kokurikuler_documents.year_id', 'left');

        // Filter untuk guru kelas (wali kelas)
        if ($roleId == 3 && $userId) {
            // Guru kelas HANYA melihat:
            // 1. Dokumen yang ia buat sendiri
            // 2. Dokumen yang ia gunakan dari template (used_by_teacher_id)
            // TIDAK menampilkan template dari admin/wali kelas lain
            
            // Get teacher_id dari user
            $db = \Config\Database::connect();
            $userRecord = $db->table('users')->where('id', $userId)->get()->getRow();
            $teacherId = $userRecord->related_id ?? 0;
            
            $builder->groupStart()
                ->where('kokurikuler_documents.created_by', $userId)
                ->orWhere('kokurikuler_documents.used_by_teacher_id', $teacherId)
                ->groupEnd();
        }

        return $builder->orderBy('kokurikuler_documents.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get document with full details
     */
    public function getDocumentWithDetails($id)
    {
        return $this->select('kokurikuler_documents.*, users.fullname as creator_name, academic_years.year as year_name')
            ->join('users', 'users.id = kokurikuler_documents.created_by', 'left')
            ->join('academic_years', 'academic_years.id = kokurikuler_documents.year_id', 'left')
            ->where('kokurikuler_documents.id', $id)
            ->first();
    }
}
