<?php

namespace App\Models;
use CodeIgniter\Model;

class ChatMentionModel extends Model {
    protected $table = 'chat_mentions';
    protected $allowedFields = ['message_id','mentioned_user_id','is_read'];
    protected $useTimestamps = false;
}
