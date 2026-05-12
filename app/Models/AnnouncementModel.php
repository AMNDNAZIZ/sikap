<?php

namespace App\Models;

use CodeIgniter\Model;

class AnnouncementModel extends Model
{
    protected $table = 'announcements';
    protected $primaryKey = 'id';
    protected $allowedFields = ['title', 'content', 'target', 'class_id', 'is_public', 'created_by'];
    protected $useTimestamps = true;

    public function getForUser($user)
    {
        $roleId = $user['role_id'];
        $userId = $user['id'];
        $classId = $user['class_id'] ?? null;

        $builder = $this->select('announcements.*, users.fullname as creator')
            ->join('users', 'users.id=announcements.created_by', 'left')
            ->orderBy('created_at', 'DESC');

        // Filter sesuai role
        if ($roleId == 1) { // admin
            return $builder->findAll();
        } elseif ($roleId == 2) { // kepala sekolah
            return $builder->whereIn('target', ['all', 'teachers', 'students', 'parents'])->findAll();
        } elseif ($roleId == 3) { // guru kelas
            return $builder->groupStart()
                ->where('target', 'all')
                ->orGroupStart()
                ->where('target', 'class')
                ->where('class_id', $classId)
                ->groupEnd()
                ->groupEnd()
                ->findAll();
        } elseif ($roleId == 4) { // orang tua
            return $builder->whereIn('target', ['all', 'parents'])->findAll();
        } elseif ($roleId == 5) { // siswa
            return $builder->groupStart()
                ->whereIn('target', ['all', 'students'])
                ->orGroupStart()
                ->where('target', 'class')
                ->where('class_id', $classId)
                ->groupEnd()
                ->groupEnd()
                ->findAll();
        }

        return [];
    }
}
