<?php
namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table = 'landing_activities';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['title', 'description', 'image', 'date', 'created_by'];

    public function getActivitiesWithAuthor()
    {
        return $this->select('landing_activities.*, users.fullname as uploader_name')
            ->join('users', 'users.id = landing_activities.created_by', 'left')
            ->orderBy('landing_activities.date', 'DESC')
            ->findAll();
    }
}
