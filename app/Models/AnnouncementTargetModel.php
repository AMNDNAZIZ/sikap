<?php namespace App\Models;

use CodeIgniter\Model;

class AnnouncementTargetModel extends Model
{
    protected $table = 'announcement_targets';
    protected $primaryKey = 'id';
    protected $allowedFields = ['announcement_id', 'target_type', 'target_value'];
}
