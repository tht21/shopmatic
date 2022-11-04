<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use App\Models\ArticleTag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

class ArticleController extends Controller
{
    /**
     * Display a listing of the articles
     *
     * @param Request $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function index(Request $request, Article $article)
    {
        $this->authorize('index', Article::class);

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $article = $article->newQuery();

        $searchFields = ['title', 'description'];

        if ($request->has('filter')){
            $article->where(function ($query) use ($request, $searchFields) {
                $searchWildcard = '%' . $request->filter . '%';
                foreach ($searchFields as $field) {
                    $query->orWhere($field, 'LIKE', $searchWildcard);
                }
            });
        };

        $searchCategory = ['title', 'article_categories.name'];

        if ($request->has('category')) {
            $article->whereHas('category', function ($query) use ($request, $searchCategory, $article) {
                $searchWildcard = '%' . $request->category . '%';

                foreach ($searchCategory as $key => $field) {
                    if ($key === 0) {
                        $query->where($field, 'LIKE', $searchWildcard);
                        continue;
                    }
                    $query->orWhere($field, 'LIKE', $searchWildcard);
                }
            });
        }

        $data = $article->with('category', 'user', 'tags')->paginate($limit);

        return $this->respondPagination($request, $data);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return $this->respondNotFound();
    }

    /**
     * Store a newly created article in storage
     *
     * @param Request $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Article::class);

        $input = $request->input();
        $validator = Validator::make($input, [
            'title' => 'required',
            'outline' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        $article = new Article();
        $article->fill($input);
        $article->user_id = Auth::user()->id;
        $article->save();

        if (!empty($request->article_tags)) {

            $tags = explode(',', $request->article_tags);
            foreach ($tags as $tag) {
                if (ArticleTag::where('name', $tag)->doesntExist()){
                    $at = new ArticleTag();
                    $at->name = $tag;
                    $at->save();
                }
            }
            $articleTags = ArticleTag::whereIn('name', $tags)->pluck('id');
            $article->tags()->sync($articleTags);
        }

        $article = $article->fresh();
        $article->load('tags');

        return $this->respondCreated($article->toArray());
    }

    /**
     * Display the specified article
     *
     * @param Article $article
     * @return mixed
     * @throws AuthorizationException
     */
    public function show(Article $article)
    {
        $this->authorize('view', $article);

        $article->load('tags');

        return $this->respond($article);
    }

    /**
     * @param Request $request
     * @param Article $article
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Article $article)
    {
        return $this->respondNotFound();
    }

    /**
     * Update the specified article
     *
     * @param Request $request
     * @param Article $article
     * @return mixed
     * @throws AuthorizationException
     */
    public function update(Request $request, Article $article)
    {
        $this->authorize('update', $article);

        $input = $request->input();

        $validator = Validator::make($input, [
            'title' => 'required',
            'outline' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }
        $article = $article->fill($input);
        $article->user_id = Auth::user()->id;
        $article->save();

        if (!empty($request->article_tags)) {

            $tags = explode(',', $request->article_tags);
            foreach ($tags as $tag) {
                if (ArticleTag::where('name', $tag)->doesntExist()){
                    $at = new ArticleTag();
                    $at->name = $tag;
                    $at->save();
                }
            }
            $articleTags = ArticleTag::whereIn('name', $tags)->pluck('id');
            $article->tags()->sync($articleTags);
        }

        $article = $article->fresh();
        $article->load('tags');

        return $this->respond($article);
    }

    /**
     * Delete the specified resource
     *
     * @param Article $article
     * @return mixed
     * @throws \Exception
     */
    public function destroy(Article $article)
    {
        $this->authorize('delete', $article);

        $article->delete();

        return $this->respond($article);
    }
}
