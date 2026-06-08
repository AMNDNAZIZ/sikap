<?php

namespace App\Models;
use CodeIgniter\Model;

class ChatRoomModel extends Model {
    protected $table = 'chat_rooms';
    protected $allowedFields = ['class_id'];
    public $timestamps = false;
}
