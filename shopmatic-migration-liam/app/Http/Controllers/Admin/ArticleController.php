<?php

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ArticleController extends Controller
{
    /**
     * Display a listing of the articles.
     *
     * @param Request $request
     *
     * @return Renderable
     * @throws AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', Article::class);

        return view('admin.articles.index');
    }

    /**
     * Show the form for creating a new article.
     *
     * @return Renderable
     * @throws AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', Article::class);

        return view('admin.articles.create');
    }

    /**
     * Display the specified article.
     *
     * @param Article $article
     * @return Renderable
     * @throws AuthorizationException
     */
    public function edit(Article $article)
    {
        $this->authorize('edit', $article);

        return view('admin.articles.edit', compact('article'));
    }
}
