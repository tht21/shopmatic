<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        return view('articles.index');
    }

        public function show(Article $article)
    {
        return view('articles.show', compact('article'));
    }
}
