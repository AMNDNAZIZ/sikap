<?php
namespace App\Models;

use CodeIgniter\Model;

class ArticleModel extends Model
{
    protected $table = 'landing_articles';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['title', 'slug', 'content', 'image', 'category', 'is_published', 'created_by'];

    public function getArticlesWithAuthor()
    {
        return $this->select('landing_articles.*, users.fullname as author_name')
            ->join('users', 'users.id = landing_articles.created_by', 'left')
            ->orderBy('landing_articles.created_at', 'DESC')
            ->findAll();
    }
}
