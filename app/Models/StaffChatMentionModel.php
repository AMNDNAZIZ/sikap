<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffChatMentionModel extends Model
{
    protected $table         = 'staff_chat_mentions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['message_id', 'mentioned_user_id', 'is_read'];
}
