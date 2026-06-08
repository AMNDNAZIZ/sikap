<?php

/**
 * UPDATE MODEL
 * 
 * File ini untuk diupload ke server update
 * Lokasi: update.cbtku.com/app/Models/UpdateModel.php
 */

namespace App\Models;

use CodeIgniter\Model;

class UpdateModel extends Model
{
    protected $table = 'updates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'version',
        'release_date',
        'title',
        'description',
        'changelog',
        'file_name',
        'file_size',
        'file_hash',
        'is_critical',
        'is_active',
        'download_count'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'version' => 'required|is_unique[updates.version]',
        'release_date' => 'required|valid_date',
        'title' => 'required|max_length[255]',
        'file_name' => 'required',
        'file_size' => 'required|integer',
        'file_hash' => 'required|exact_length[64]'
    ];
    
    protected $validationMessages = [
        'version' => [
            'required' => 'Version harus diisi',
            'is_unique' => 'Version sudah ada'
        ]
    ];
    
    /**
     * Get latest active update
     */
    public function getLatest()
    {
        return $this->where('is_active', 1)
            ->orderBy('version', 'DESC')
            ->first();
    }
    
    /**
     * Get updates newer than specified version
     */
    public function getNewerThan($version)
    {
        return $this->where('is_active', 1)
            ->where('version >', $version)
            ->orderBy('version', 'ASC')
            ->findAll();
    }
    
    /**
     * Increment download count
     */
    public function incrementDownload($id)
    {
        $this->set('download_count', 'download_count + 1', false)
            ->where('id', $id)
            ->update();
    }
}
