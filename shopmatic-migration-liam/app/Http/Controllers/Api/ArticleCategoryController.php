<?php

namespace App\Http\Controllers\Api;

use App\Models\ArticleCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticleCategoryController extends Controller
{
    /**
     * Display a listing of the article categories.
     *
     * @param Request $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function index(Request $request, ArticleCategory $category)
    {
        $this->authorize('index', ArticleCategory::class);

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $category = $category->newQuery();

        $searchFields = ['name'];

        if ($request->has('filter')){
            $category->where(function ($query) use ($request, $searchFields) {
                $searchWildcard = '%' . $request->filter . '%';
                foreach ($searchFields as $field) {
                    $query->orWhere($field, 'LIKE', $searchWildcard);
                }
            });
        };

        $data = $category->paginate($limit);

        return $this->respondPagination($request, $data);
    }

    /**
     * Store a newly created article category in storage
     *
     * @param Request $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', ArticleCategory::class);

        $input = $request->input();

        $validator = Validator::make($input, [
            'name' => 'required|string',
        ]);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        $category = ArticleCategory::create($input);
        $category = $category->fresh();

        return $this->respondCreated($category->toArray());
    }

    /**
     * Update selected article category in storage
     *
     * @param Request $request
     * @param ArticleCategory $category
     * @return mixed
     * @throws AuthorizationException
     */
    public function update(Request $request, ArticleCategory $category)
    {
        $this->authorize('update', ArticleCategory::class);

        $input = $request->input();

        $validator = Validator::make($input, [
            'name' => 'required|string',
        ]);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        $category->update($input);

        $category = $category->fresh();

        return $this->respond($category);
    }

    /**
     * Delete selected article category in storage
     *
     * @param ArticleCategory $category
     * @return mixed
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function destroy(ArticleCategory $category)
    {
        $this->authorize('delete', ArticleCategory::class);

        $category->delete();

        return $this->respond($category);
    }
}
