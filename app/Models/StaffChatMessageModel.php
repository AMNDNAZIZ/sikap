<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffChatMessageModel extends Model
{
    protected $table         = 'staff_chat_messages';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['room_id', 'user_id', 'message', 'attachment', 'reply_to', 'created_at'];
}
