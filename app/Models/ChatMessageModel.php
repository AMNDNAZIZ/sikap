<?php

namespace App\Models;
use CodeIgniter\Model;

class ChatMessageModel extends Model
{
    protected $table = 'chat_messages';
    protected $allowedFields = ['room_id', 'user_id', 'message', 'attachment', 'reply_to', 'created_at'];
    protected $useTimestamps = false;
}
