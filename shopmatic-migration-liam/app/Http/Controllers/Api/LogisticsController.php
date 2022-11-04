<?php

namespace App\Http\Controllers\Api;

use App\Constants\LogisticServiceType;
use Illuminate\Http\Request;
use function foo\func;

class LogisticsController extends Controller
{
    /**
     * Show the product inventory index
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
//        $this->authorize('index', Order::class);

        $query = $this->data();
        $service_type = $request->get('service_type', 'PICK_UP');
        if (!is_null($service_type)) {
            try {
                $service_type = LogisticServiceType::searchKey(strtoupper($service_type));
            } catch (\ErrorException $e) {
                if ($e->getCode() === 0) {
                    return $this->respondBadRequestError('Invalid logistic_service_type');
                }
            }
            $query = array_filter($query, function($d) use ($service_type) {
                return $d['service_type'] == $service_type;
            });
        }


        return $this->respondPagination($request, $this->paginate($request, $query));
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
    public function show($id)
    {
        $query = $this->data();
        $query = $query->where('id', $id);
        return $this->respond($query->toArray());
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

    private function data()
    {

        return [
            [
                'id' => 1,
                'courier' => '/images/marketplaces/Amazon.png',
                'service_type' => 1,
                'service_requires_min' => 4,
                'service_rating' => 3,
                'estimated_delivery_duration' => 2,
                'rate' => 'rm5.90',
                'image_notice' => null,
                'notices' => [
                    [
                        'image' => '/images/marketplaces/Amazon.png',
                        'title' => '(i) Printer Ready?',
                        'content' => 'To use EasyParcel service, kindly have a printer ready to print the airway bill, and pass it to the courier service personnel during pick-up.',
                    ],
                    [
                        'image' => '/images/marketplaces/Amazon.png',
                        'title' => '(ii) Misdeclared parcel weight',
                        'content' => 'By entering the weight and dimensions of your shipment, you are pre-paying for the postage. Extra charges for the additional weight will be automatically charged to this account based on the final weight validated by courier company. Additional charges will be at EasyParcel\'s normal rate.',
                    ],
                ],
            ],
            [
                'id' => 2,
                'courier' => '/images/marketplaces/Ebay.png',
                'service_type' => 0,
                'service_requires_min' => null,
                'service_rating' => 5,
                'estimated_delivery_duration' => 1,
                'rate' => 'rm5.90',
                'image_notice' => '/images/marketplaces/Ebay.png',
                'notices' => null,
            ]
        ];
    }


}
