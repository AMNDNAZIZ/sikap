<?php

namespace App\Models;

use CodeIgniter\Model;

class BkQuestionOptionModel extends Model
{
    protected $table            = 'bk_question_options';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'question_id',
        'option_text',
        'score_value',
        'sort_order',
        'created_at',
    ];
}
