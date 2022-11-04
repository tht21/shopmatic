<?php

namespace App\Http\Controllers\Api;

use App\Constants\AccountStatus;
use App\Constants\Subscription;
use App\Models\Account;
use App\Models\Shop;
use App\Utilities\SubscriptionHelper;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }
        $this->authorize('view', $shop);

        $type = Subscription::TYPE()->getValue();
        $subscription = $shop->subscription($type);

        $results = [
            'subscribed' => $shop->subscribed($type),
            'subscription' => $subscription,
            'onTrial' => $subscription ? $subscription->onTrial() : false,
            'cancelled' => $subscription ? $subscription->cancelled() : false,
            'onGracePeriod' => $subscription ? $subscription->onGracePeriod() : false,
            'ended' => $subscription ? $subscription->ended() : false,
            'checkUserLimit' => SubscriptionHelper::checkUserLimit(),
        ];

        return $this->respond($results);
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
     * @param Request $request
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

        if (!$shop->hasPaymentMethod()) {
            return $this->respondBadRequestError('There is no payment method added.');
        }

        $plan = $request->input('plan');
        $trial = $request->input('trial');
        $type = Subscription::TYPE()->getValue();

        try {
            if (!Subscription::isValid($plan)) {
                return $this->respondBadRequestError('Invalid plan selected.');
            }

            // We can't use searchValue because there is duplicate value in Subscription, might get the wrong key
            switch ($plan) {
                case 0:
                    $stripePlan = strtolower(Subscription::STARTER()->getKey()).'_monthly';
                    break;
                case 1:
                    $stripePlan = strtolower(Subscription::PROFESSIONAL()->getKey()).'_monthly';
                    break;
                case 2:
                    $stripePlan = strtolower(Subscription::ADVANCE()->getKey()).'_monthly';
                    break;
                case 3:
                    $stripePlan = strtolower(Subscription::E2E()->getKey()).'_monthly';
                    break;
                default:
                    $stripePlan = null;
            }

            if (!$stripePlan) {
                return $this->respondBadRequestError('Invalid plan selected.');
            }
            //$stripePlan = strtolower(Subscription::searchValue($plan)).'_monthly';
            $message = 'Subscribe to '.strtolower(Subscription::searchValue($plan)).' plan successfully';

            if ($shop->subscribed($type)) {
                // Change plan if there's active subscription
                $shop->subscription($type)->swap($stripePlan);
            } else if ($trial && is_null($shop->subscription($type))) {
                // Trial if its true, and there's no existing subscription
                $shop->newSubscription($type, $stripePlan)->trialDays(1)->create($shop->defaultPaymentMethod()->id);
            } else {
                $shop->newSubscription($type, $stripePlan)->create($shop->defaultPaymentMethod()->id);
            }

            return $this->respondWithMessage([], $message);

        } catch (\Exception $e) {
            return $this->respondBadRequestError($e->getMessage());
        }
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
        /** @var Shop $shop */
        // $shop = $request->session()->get('shop');
        // if (empty($shop)) {
        //     return $this->respondBadRequestError('There is no shop selected');
        // }

        // $payment_method = $request->input('payment_method');

        // $shop->updateDefaultPaymentMethod($payment_method);

        // return $this->respondWithMessage([], 'Payment updated successfully.');
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

    /**
     * Cancel subscription
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function cancel(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('update', $shop);

        $type = Subscription::TYPE()->getValue();
        if ($shop->subscription($type)->cancel()) {
            // disable all integrations
            foreach ($shop->accounts as $account) {
                $account = Account::find($account->id);
                $account->getClient()->disableAccount(AccountStatus::DISABLED());
            }

            return $this->respondWithMessage([], 'Subscription cancelled successfully. All your accounts are disabled.');
        }

        return $this->respondBadRequestError('Subscription cancelled failed.');
    }
}
