<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\SchoolModel;
use CodeIgniter\Controller;

class News extends BaseController
{
    public function index()
    {
        helper('text');
        $articleModel = new ArticleModel();
        $schoolModel = new SchoolModel();

        $data = [
            'articles' => $articleModel->where('is_published', 1)->orderBy('created_at', 'DESC')->findAll(),
            'school' => $schoolModel->first() ?: [],
            'title' => 'Berita & Artikel'
        ];

        return view('news/index', $data);
    }

    public function view($slug)
    {
        helper('text');
        $articleModel = new ArticleModel();
        $schoolModel = new SchoolModel();

        $article = $articleModel->where('slug', $slug)->where('is_published', 1)->first();

        if (!$article) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'article' => $article,
            'latestArticles' => $articleModel->where('is_published', 1)
                ->where('id !=', $article['id'])
                ->orderBy('created_at', 'DESC')
                ->findAll(5),
            'school' => $schoolModel->first() ?: [],
            'title' => $article['title']
        ];

        return view('news/view', $data);
    }
}
