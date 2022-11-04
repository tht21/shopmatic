<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Show the chat inventory index
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $query = $this->data($request);
        $search = $request->get('search', 0);
        $filtered = $request->get('filtered', null);
        $filteredQuery = [];
        if ($search) {
            $filteredQuery = array_filter($query, function ($d) use ($search) {
                return strpos(strtolower($d['name']), strtolower($search)) !== false;
            });
        } elseif ($filtered) {
            foreach (explode(",", $filtered) as $data) {
                $item = array_filter($query, function ($d) use ($data) {
                    return strpos(strtolower($d['name']), strtolower($data)) !== false;
                });
                $filteredQuery += $item;
            }
        }

        usort($filteredQuery, function ($a, $b) {
            return $a['id'] > $b['id'];
        });

        return $this->respondPagination($request, $this->paginate($request, $filteredQuery));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $query = $this->data($request);
        $query = array_filter($query, function ($d) use ($id) {
            return $d['id'] == $id;
        });
        return $this->respond(current($query));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function data(Request $request)
    {
        $data = [];
        $integration = ["Shopee", "Lazada"];
        $shop = $request->session()->get('shop');
        $query = $shop->orders()->get();

        foreach (range(0, 8) as $i) {

            array_push($data, [
                'id' => $i,
                'name' => $integration[$i % 2],
                'image' => '/images/marketplaces/' . $integration[$i % 2] . '.png',
                'messages' => [
                    [
                        'message' => 'Welcome to ' . $integration[$i % 2],
                        'datetime' => Carbon::now(),
                        'image' => '/images/marketplaces/' . $integration[$i % 2] . '.png',
                        'is_me' => false,
                    ],
                    [
                        'message' => 'Welcome to ' . $integration[$i % 2],
                        'datetime' => Carbon::now(),
                        'image' => '/images/marketplaces/' . $integration[$i % 2] . '.png',
                        'is_me' => false,
                    ]
                ],
                'client' => [
                    'name' => 'Kevin',
                    'image' => '/images/knowledgebase/1.jpg',
                    'phone_number' => '+601XXXXX678',
                    'orders' => $query,
                ],
            ]);
        }

        return $data;
    }

}
