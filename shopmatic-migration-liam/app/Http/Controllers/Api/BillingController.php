<?php

namespace App\Http\Controllers\Api;

use App\Models\Shop;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', Shop::class);
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $paymentMethods = $shop->paymentMethods();

        $data = [];
        $default = $shop->defaultPaymentMethod();

        foreach ($paymentMethods as $value) {
            $data[] = [
                'card' => [
                    'id' => $value->id,
                    'name' => $value->billing_details->name,
                    'brand' => $value->card->brand,
                    'last4' => $value->card->last4,
                    'exp_month' => $value->card->exp_month,
                    'exp_year' => $value->card->exp_year,
                    'type' => $value->card->funding,
                    'default' => $value->id == $default->id
                ],
                'created_at' => date('m/d/Y', $value->created)
            ];
        }

        return $this->respond($data);
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('update', $shop);

        $payment_method = $request->input('payment_method');
        $default = $request->input('default');

        try {
            $shop->addPaymentMethod($payment_method);

            if ($default) {
                $shop->updateDefaultPaymentMethod($payment_method);
            }
        } catch (\Exception $e) {
            return $this->respondBadRequestError($e->getMessage());
        }

        return $this->respondWithMessage([], 'Payment method created successfully.');
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
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, $id)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('update', $shop);

        $payment_method = $request->input('payment_method');

        $shop->updateDefaultPaymentMethod($payment_method);

        return $this->respondWithMessage([], 'Payment method updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $payment_method
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy($payment_method, Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('update', $shop);

        $payment_method = $shop->findPaymentMethod($payment_method);

        if (!$payment_method) {
            return $this->respondBadRequestError('Payment method not found');
        }

        $payment_method->delete();

        return $this->respondWithMessage([], 'Payment method deleted successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param $payment_method
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function setAsDefault($payment_method, Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('update', $shop);

        $shop->updateDefaultPaymentMethod($payment_method);

        return $this->respondWithMessage([], 'Payment method set as default successfully.');
    }
}
