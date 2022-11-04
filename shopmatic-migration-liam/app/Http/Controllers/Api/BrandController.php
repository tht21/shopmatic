<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use App\Models\Shop;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', Brand::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $id = $request->input('id');
        $integrationId = $request->input('integration_id');
        $search = $request->input('search');
        $regionId = $request->input('region_id');
        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT); // will always cap the limit under DEFAULT_MAX_LIMIT

        $query = Brand::orderBy('id', 'ASC');

        if (!empty($integrationId)) {
            $query = $query->where('integration_id', $integrationId);
        }

        if (!empty($regionId) && $regionId !== '0') {
            $query = $query->where('region_id', $regionId);
        }

        if (!empty($id) && $id !== '0') {
            $query = $query->where('id', $id);
        }

        if (!empty($search) && $id !== '0') {
            $query = $query->where('name', 'LIKE', '%' . $search . '%');
        }
        
        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
