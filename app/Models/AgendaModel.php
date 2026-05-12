<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendaModel extends Model
{
    protected $table         = 'agendas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'title', 'description', 'date', 'start_time', 'end_time', 'class_id',
        'created_by', 'is_public'
    ];
    protected $useTimestamps = true;
}
