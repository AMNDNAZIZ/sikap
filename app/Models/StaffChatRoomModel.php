<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffChatRoomModel extends Model
{
    protected $table      = 'staff_chat_rooms';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['name'];
}
