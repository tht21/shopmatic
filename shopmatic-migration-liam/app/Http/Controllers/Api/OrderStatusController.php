<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;

class OrderStatusController extends Controller
{

    /**
     * Show the product inventory index
     *
     * @param Order $order
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function listing(Order $order)
    {
        $this->authorize('index', $order);

        if (empty($order->account)) {
            return $this->respondBadRequestError('This order does not have an integration to support your actions.');
        }

        $adapter = $order->account->getOrderAdapter();

        if (empty($adapter)) {
            return $this->respondBadRequestError('This integration does not support any actions.');
        }

        $response = $adapter->getOrderState();
        return $this->respond($response);
    }

}
