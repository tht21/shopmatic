<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use App\Models\ArticleTag;

class ArticleTagController extends Controller
{
    /**
     * @param Request $request
     * @param ArticleTag $tag
     * @return mixed
     * @throws AuthorizationException
     */
    public function index(Request $request, ArticleTag $tag)
    {
        $this->authorize('index', ArticleTag::class);

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $tag = $tag->newQuery();

        $data = $tag->paginate($limit);

        return $this->respondPagination($request, $data);
    }
}
