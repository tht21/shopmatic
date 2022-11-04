<?php

namespace App\Http\Controllers\Api;

use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketCategoryController extends Controller
{
    /**
     * Return a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function index(Request $request)
    {
        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $category = new TicketCategory;
        $category = $category->newQuery();

        $searchFields = ['name', 'id'];

        if ($request->has('filter')) {
            $category->where(function ($query) use ($request, $searchFields) {
                $searchWildcard = '%' . $request->filter . '%';
                foreach($searchFields as $field){
                    $query->orWhere($field, 'LIKE', $searchWildcard);
                }
            });
        };

        $data = $category->with('parent')->orderBy('id', 'desc')->paginate($limit);

        return $this->respondPagination($request, $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function store(Request $request)
    {
        $input = $request->input();

        $validator = Validator::make($input, [
            'name' => 'required|string',
        ]);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        $category = TicketCategory::create($input);

        return $this->respondCreated($category->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TicketCategory $category
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function update(TicketCategory $category, Request $request)
    {
        $input = $request->input();

        $validator = Validator::make($input, [
            'name' => 'required|string',
        ]);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        $category->update($input);

        return $this->respondCreated($category->toArray());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param TicketCategory $category
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */

    public function destroy(TicketCategory $category)
    {
        $category->delete();

        return $this->respond($category);
    }
}
